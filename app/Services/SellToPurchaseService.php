<?php

namespace App\Services;

use App\BusinessLocation;
use App\Contact;
use App\ImeiNumber;
use App\Product;
use App\PurchaseLine;
use App\Transaction;
use App\TransactionSellLine;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class SellToPurchaseService
{
    /**
     * All Utils instance.
     */
    protected $productUtil;

    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param ProductUtil $productUtil
     * @param TransactionUtil $transactionUtil
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Convert a sell transaction to a purchase transaction
     *
     * @param int $sellId The ID of the sell transaction
     * @param int $supplierId The ID of the supplier contact
     * @param int $purchaseLocationId The ID of the purchase location
     * @return Transaction The newly created purchase transaction
     * @throws \Exception
     */
    public function convertSellToPurchase($sellId, $supplierId, $purchaseLocationId)
    {
        $businessId = request()->session()->get('user.business_id');
        $userId = request()->session()->get('user.id');

        return DB::transaction(function () use ($sellId, $supplierId, $purchaseLocationId, $businessId, $userId) {
            // Fetch the sell transaction with sell_lines and relations
            $sellTransaction = Transaction::where('business_id', $businessId)
                ->where('type', 'sell')
                ->with(['sell_lines' => function ($query) {
                    $query->whereNull('parent_sell_line_id');
                }, 'sell_lines.product', 'sell_lines.variations', 'sell_lines.imei_numbers'])
                ->findOrFail($sellId);

            // Check if sell already has converted_from_sell_id set (prevent duplicate conversion)
            if (!empty($sellTransaction->converted_from_sell_id)) {
                throw new \Exception(__('lang_v1.sell_already_converted_to_purchase'));
            }

            // Get the supplier
            $supplier = Contact::where('business_id', $businessId)
                ->where('type', 'supplier')
                ->findOrFail($supplierId);

            // Get the purchase location
            $purchaseLocation = BusinessLocation::where('business_id', $businessId)
                ->findOrFail($purchaseLocationId);

            // Generate reference number for purchase
            $refCount = $this->productUtil->setAndGetReferenceCount('purchase');
            $refNo = $this->productUtil->generateReferenceNumber('purchase', $refCount);

            // Create new Purchase transaction
            $purchaseTransactionData = [
                'business_id' => $businessId,
                'location_id' => $purchaseLocationId,
                'type' => 'purchase',
                'status' => 'pending',
                'contact_id' => $supplierId,
                'transaction_date' => $sellTransaction->transaction_date,
                'ref_no' => $refNo,
                'created_by' => $userId,
                'shipping_charges' => $sellTransaction->shipping_charges ?? 0,
                'converted_from_sell_id' => $sellId,
                'payment_status' => 'due',
            ];

            // Copy additional fields if they exist
            $fieldsToCopy = [
                'discount_type',
                'discount_amount',
                'tax_id',
                'tax_amount',
                'total_before_tax',
                'final_total',
                'additional_notes',
                'exchange_rate',
                'pay_term_number',
                'pay_term_type',
            ];

            foreach ($fieldsToCopy as $field) {
                if (isset($sellTransaction->$field)) {
                    $purchaseTransactionData[$field] = $sellTransaction->$field;
                }
            }

            $purchaseTransaction = Transaction::create($purchaseTransactionData);

            // Create purchase lines from sell lines
            foreach ($sellTransaction->sell_lines as $sellLine) {
                $this->createPurchaseLineFromSellLine($purchaseTransaction, $sellLine, $purchaseLocationId, $businessId);
            }

            // Create activity log
            $this->transactionUtil->activityLog($purchaseTransaction, 'converted_from_sell', null, [
                'sell_id' => $sellId,
                'sell_ref_no' => $sellTransaction->ref_no,
            ]);

            return $purchaseTransaction;
        });
    }

    /**
     * Create a purchase line from a sell line
     *
     * @param Transaction $purchaseTransaction The parent purchase transaction
     * @param TransactionSellLine $sellLine The source sell line
     * @param int $locationId The location ID
     * @param int $businessId The business ID
     * @return PurchaseLine
     */
    protected function createPurchaseLineFromSellLine($purchaseTransaction, $sellLine, $locationId, $businessId)
    {
        // Get product cost price if available, otherwise use sell price
        $product = $sellLine->product;
        $variation = $sellLine->variations;
        
        $purchasePrice = !empty($variation->default_purchase_price) 
            ? $variation->default_purchase_price 
            : $sellLine->unit_price;

        // Build purchase line data
        $purchaseLineData = [
            'transaction_id' => $purchaseTransaction->id,
            'product_id' => $sellLine->product_id,
            'variation_id' => $sellLine->variation_id,
            'quantity' => $sellLine->quantity,
            'unit_price' => $purchasePrice,
            'purchase_price' => $purchasePrice,
            'item_tax' => $sellLine->item_tax ?? 0,
            'tax_id' => $sellLine->tax_id,
            'discount_type' => $sellLine->line_discount_type ?? null,
            'discount_amount' => $sellLine->line_discount_amount ?? 0,
            'unit_id' => $sellLine->product->unit_id ?? null,
            'sub_unit_id' => $sellLine->sub_unit_id ?? null,
            'secondary_unit_quantity' => $sellLine->secondary_unit_quantity ?? 0,
        ];

        // Get lot number from sell line if available
        if (!empty($sellLine->lot_no_line_id)) {
            $purchaseLineData['lot_number'] = $sellLine->lot_no_line_id;
            
            // Copy lot details if available
            if (!empty($sellLine->lot_details)) {
                $purchaseLineData['mfg_date'] = $sellLine->lot_details->mfg_date ?? null;
                $purchaseLineData['exp_date'] = $sellLine->lot_details->exp_date ?? null;
            }
        }

        $purchaseLine = PurchaseLine::create($purchaseLineData);

        // Handle IMEI/Serial numbers transfer
        $this->transferImeiNumbers($sellLine, $purchaseLine, $locationId, $businessId);

        return $purchaseLine;
    }

    /**
     * Transfer IMEI/Serial numbers from sell line to purchase line
     *
     * @param TransactionSellLine $sellLine The source sell line
     * @param PurchaseLine $purchaseLine The target purchase line
     * @param int $locationId The location ID
     * @param int $businessId The business ID
     * @return void
     */
    protected function transferImeiNumbers($sellLine, $purchaseLine, $locationId, $businessId)
    {
        // Check if product has IMEI/serial tracking
        $product = $sellLine->product;
        if (!in_array($product->item_type, ['imei', 'serial'])) {
            return;
        }

        // Get IMEI/Serial numbers associated with this sell line
        $imeiNumbers = ImeiNumber::where('sell_line_id', $sellLine->id)->get();

        foreach ($imeiNumbers as $imeiNumber) {
            // Reset the IMEI/serial number for the new purchase
            $imeiNumber->update([
                'purchase_item_id' => $purchaseLine->id,
                'sell_line_id' => null,
                'is_sold' => false,
                'sold_at' => null,
                'location_id' => $locationId,
            ]);
        }
    }

    /**
     * Get sell transaction details for conversion preview
     *
     * @param int $sellId
     * @return Transaction
     */
    public function getSellForConversion($sellId)
    {
        $businessId = request()->session()->get('user.business_id');

        return Transaction::where('business_id', $businessId)
            ->where('type', 'sell')
            ->with(['sell_lines' => function ($query) {
                $query->whereNull('parent_sell_line_id');
            }, 'sell_lines.product', 'sell_lines.variations', 'sell_lines.product.unit', 'contact'])
            ->findOrFail($sellId);
    }
}
