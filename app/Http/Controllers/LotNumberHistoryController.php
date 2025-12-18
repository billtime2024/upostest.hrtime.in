<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\VariationLocationDetails;
use App\BusinessLocation;
use App\Product;
use App\PurchaseLine;
use App\Transaction;
use App\Utils\ProductUtil;
use App\Variation;
use App\ImeiNumber;

class LotNumberHistoryController extends Controller {

    protected $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }
    
    public function index($type = null, $id = null){
        if (! auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            if (!$type || !$id) {
                return "Invalid request";
            }
            $business_id = request()->session()->get('user.business_id');

            if ($type == 'lot_number') {
                $purchases = PurchaseLine::where('lot_number', $id)
                    ->whereHas('transaction', function($q) use ($business_id) {
                        $q->where('business_id', $business_id);
                    });
            } else {
                // For IMEI or Serial
                $imei = ImeiNumber::where('type', $type)
                    ->where('identifier', $id)
                    ->whereHas('purchaseLine', function($q) use ($business_id) {
                        $q->whereHas('transaction', function($t) use ($business_id) {
                            $t->where('business_id', $business_id);
                        });
                    })
                    ->first();

                if (!$imei) {
                    return "not found";
                }

                // Collect all related transactions for IMEI
                $transactions = collect();

                // Purchase transaction
                $purchase_transaction = $imei->purchaseLine->transaction->load([
                    'contact', 'business', 'location', 'payment_lines', 'sell_lines' => function($q) {
                        $q->where('id', $imei->sell_line_id);
                    }, 'purchase_lines' => function($q) {
                        $q->where('id', $imei->purchase_item_id);
                    }
                ]);
                $transactions->push($purchase_transaction);

                // Sell transaction if exists
                if ($imei->sell_line_id) {
                    $sell_transaction = $imei->sellLine->transaction->load([
                        'contact', 'business', 'location', 'payment_lines', 'sell_lines' => function($q) {
                            $q->where('id', $imei->sell_line_id);
                        }
                    ]);
                    $transactions->push($sell_transaction);
                }

                // Transfer transaction if exists
                if ($imei->transfer_sell_line_id) {
                    $transfer_transaction = $imei->transferSellLine->transaction->load([
                        'contact', 'business', 'location', 'payment_lines', 'sell_lines' => function($q) {
                            $q->where('id', $imei->transfer_sell_line_id);
                        }
                    ]);
                    $transactions->push($transfer_transaction);

                    // Include the corresponding purchase_transfer for complete stock transfer history
                    $purchase_transfer = Transaction::where('transfer_parent_id', $transfer_transaction->id)
                        ->where('type', 'purchase_transfer')
                        ->where('business_id', $business_id)
                        ->with([
                            'contact', 'business', 'location', 'payment_lines', 'purchase_lines'
                        ])
                        ->first();

                    if ($purchase_transfer) {
                        $transactions->push($purchase_transfer);
                    }
                }

                // Return transaction if exists
                if ($imei->return_transaction_id) {
                    $return_transaction = $imei->returnTransaction->load([
                        'contact', 'business', 'location', 'payment_lines'
                    ]);
                    $transactions->push($return_transaction);
                }

                // Sort by date
                $transactions = $transactions->sortBy('transaction_date');

                // Format to match view expectations
                $purchases = $transactions->map(function($transaction) use ($imei) {
                    $data = $transaction->toArray();
                    // Handle customer/supplier display: set contact_name to contact's business name if blank or null
                    $data['contact_name'] = $data['contact']['name'] ?? '';
                    if (empty($data['contact_name'])) {
                        $data['contact_name'] = $data['contact']['supplier_business_name'] ?? '';
                    }
                    $data['transaction_type'] = $transaction->type;
                    $data['purchase_quantity'] = $imei->purchaseLine->quantity;
                    if ($transaction->type == 'sell' && $imei->sell_line_id) {
                        $sell_line = $transaction->sell_lines->first();
                        $data['sell_quantity'] = $sell_line ? $sell_line->quantity : 0;
                    }
                    // Add other fields as needed
                    $data['product_name'] = $imei->purchaseLine->product->name;
                    $data['product_type'] = $imei->purchaseLine->product->type;
                    $data['variation_name'] = $imei->purchaseLine->variation->productVariation ? $imei->purchaseLine->variation->productVariation->name : '';
                    $data['variation_value'] = $imei->purchaseLine->variation->variationValueTemplate ? $imei->purchaseLine->variation->variationValueTemplate->name : '';
                    $data['unit'] = $imei->purchaseLine->product->unit->short_name ?? '';
                    $data['sku'] = $imei->purchaseLine->variation->sub_sku ?? $imei->purchaseLine->product->sku;
                    $data['sub_sku'] = $imei->purchaseLine->variation->sub_sku;
                    return $data;
                });

                \Log::info('LotNumberHistory IMEI: type=' . $type . ', id=' . $id . ', transactions count=' . count($purchases));
                if (count($purchases) > 0) {
                    $types = collect($purchases)->pluck('transaction_type')->unique()->toArray();
                    \Log::info('LotNumberHistory IMEI: transaction types: ' . implode(', ', $types));
                }
            }

                if(count($purchases) > 0 ){
                    $purchase_line = $purchases[0];
                    
                    return view('report.lot_number_history_details')
                        ->with(compact('purchase_line', 'purchases'));
                }else{
                    return "not found";
               }
        }
        return view('report.lot_number_history' );
    }
}
