<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\ImeiNumber;
use App\Product;
use App\PurchaseLine;
use App\Transaction;
use App\TransactionSellLine;
use App\TransactionSellLinesPurchaseLines;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use App\Events\StockTransferCreatedOrModified;

class StockTransferController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $productUtil;

    protected $transactionUtil;

    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->status_colors = [
            'in_transit' => 'bg-yellow',
            'completed' => 'bg-green',
            'pending' => 'bg-red',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('purchase.view') && ! auth()->user()->can('purchase.create') && ! auth()->user()->can('view_own_purchase')) {
            abort(403, 'Unauthorized action.');
        }

        $statuses = $this->stockTransferStatuses();

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $edit_days = request()->session()->get('business.transaction_edit_days');

            $stock_transfers = Transaction::leftJoin(
                'business_locations AS l1',
                'transactions.location_id',
                '=',
                'l1.id'
            )
                    ->leftJoin('transactions as t2', 't2.transfer_parent_id', '=', 'transactions.id')
                    ->leftJoin(
                        'business_locations AS l2',
                        't2.location_id',
                        '=',
                        'l2.id'
                    )
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'sell_transfer');

                    if (! auth()->user()->can('purchase.view') && auth()->user()->can('view_own_purchase')) {
                        $stock_transfers->where('t2.created_by', request()->session()->get('user.id'));
                    }

                    if (! empty(request()->location_from)) {
                        $stock_transfers->where('l1.id', request()->location_from);
                    }
                    if (! empty(request()->location_to)) {
                        $stock_transfers->where('l2.id', request()->location_to);
                    }
                    if (! empty(request()->status)) {
                        $stock_transfers->where('transactions.status', request()->status);
                    }
                    if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                        $start = request()->start_date;
                        $end = request()->end_date;
                        $stock_transfers->whereDate('transactions.transaction_date', '>=', $start)
                                        ->whereDate('transactions.transaction_date', '<=', $end);
                    }

                    $stock_transfers->select(
                        'transactions.id',
                        'transactions.transaction_date',
                        'transactions.ref_no',
                        'l1.name as location_from',
                        'l2.name as location_to',
                        'transactions.final_total',
                        'transactions.shipping_charges',
                        'transactions.additional_notes',
                        'transactions.id as DT_RowId',
                        'transactions.status'
                    );



            return Datatables::of($stock_transfers)
                ->addColumn('action', function ($row) use ($edit_days) {
                    $html = '<button type="button" title="'.__('stock_adjustment.view_details').'" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-accent btn-modal" data-container=".view_modal" data-href="'.action([\App\Http\Controllers\StockTransferController::class, 'show'], [$row->id]).'"><i class="fa fa-eye" aria-hidden="true"></i> '.__('messages.view').'</button>';

                    $html .= ' <a href="#" class="print-invoice tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-info" data-href="'.action([\App\Http\Controllers\StockTransferController::class, 'printInvoice'], [$row->id]).'"><i class="fa fa-print" aria-hidden="true"></i> '.__('messages.print').'</a>';

                    $date = \Carbon::parse($row->transaction_date)
                        ->addDays($edit_days);
                    $today = today();

                    if ($date->gte($today) && auth()->user()->can('purchase.delete')) {
                        $html .= '&nbsp;
                        <button type="button" data-href="'.action([\App\Http\Controllers\StockTransferController::class, 'destroy'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-error delete_stock_transfer"><i class="fa fa-trash" aria-hidden="true"></i> '.__('messages.delete').'</button>';
                    }

                    if ($row->status != 'final' && auth()->user()->can('purchase.update')) {
                        $html .= '&nbsp;
                        <a href="'.action([\App\Http\Controllers\StockTransferController::class, 'edit'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-primary"><i class="fa fa-edit" aria-hidden="true"></i> '.__('messages.edit').'</a>';
                    }

                    return $html;
                })
                ->editColumn(
                    'final_total',
                    function($row) {
                        if (auth()->user()->can('view_purchase_price')) {
                            return '<span class="display_currency" data-currency_symbol="true">' . $row->final_total . '</span>';
                        } else {
                            return '<span>-</span>';
                        }
                    }
                )
                ->editColumn(
                    'shipping_charges',
                    '<span class="display_currency" data-currency_symbol="true">{{$shipping_charges}}</span>'
                )
                ->editColumn('status', function ($row) use ($statuses) {
                    $row->status = $row->status == 'final' ? 'completed' : $row->status;
                    $status = $statuses[$row->status];
                    $status_color = ! empty($this->status_colors[$row->status]) ? $this->status_colors[$row->status] : 'bg-gray';
                    $status = $row->status != 'completed' ? '<a href="#" class="stock_transfer_status" data-status="'.$row->status.'" data-href="'.action([\App\Http\Controllers\StockTransferController::class, 'updateStatus'], [$row->id]).'"><span class="label '.$status_color.'">'.$statuses[$row->status].'</span></a>' : '<span class="label '.$status_color.'">'.$statuses[$row->status].'</span>';

                    return $status;
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->rawColumns(['final_total', 'action', 'shipping_charges', 'status'])
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action([\App\Http\Controllers\StockTransferController::class, 'show'], [$row->id]);
                    }, ])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false, false, true, false);

        return view('stock_transfer.index')->with(compact('statuses', 'business_locations'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\StockTransferController::class, 'index']));
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $statuses = $this->stockTransferStatuses();

        return view('stock_transfer.create')
                ->with(compact('business_locations', 'statuses'));
    }

    private function stockTransferStatuses()
    {
        return [
            'pending' => __('lang_v1.pending'),
            'in_transit' => __('lang_v1.in_transit'),
            'completed' => __('restaurant.completed'),
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }
        
        //  dd($request);

        try {
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not
            if (! $this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\StockTransferController::class, 'index']));
            }

            DB::beginTransaction();

            $input_data = $request->only(['location_id', 'ref_no', 'transaction_date', 'additional_notes', 'shipping_charges', 'final_total']);
            $status = $request->input('status');
            $user_id = $request->session()->get('user.id');

            $input_data['total_before_tax'] = $input_data['final_total'];

            $input_data['type'] = 'sell_transfer';
            $input_data['business_id'] = $business_id;
            $input_data['created_by'] = $user_id;
            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            $input_data['shipping_charges'] = $this->productUtil->num_uf($input_data['shipping_charges']);
            $input_data['payment_status'] = 'paid';
            $input_data['status'] = $status == 'completed' ? 'final' : $status;

            //Update reference count
            $ref_count = $this->productUtil->setAndGetReferenceCount('stock_transfer');
            //Generate reference number
            if (empty($input_data['ref_no'])) {
                $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('stock_transfer', $ref_count);
            }

            $products = $request->input('products');
            $sell_lines = [];
            $purchase_lines = [];
            $imei_serial_transfers = [];

            // Validate and prepare IMEI/Serial transfers
            foreach ($products as $product) {
                $prod = Product::find($product['product_id']);
                if (in_array($prod->item_type, ['imei', 'serial']) && empty($product['lot_no_line_id'])) {
                    $selected_identifiers_string = $product['selected_imei_serial'] ?? '';
                    $selected_identifiers = !empty($selected_identifiers_string) ? explode(',', $selected_identifiers_string) : [];
                    if (empty($selected_identifiers)) {
                        throw new \Exception(__('lang_v1.selected_identifiers_required_for_imei_serial'));
                    }

                    // Validate identifiers are available at source location
                    $identifiers = ImeiNumber::whereIn('identifier', $selected_identifiers)
                        ->where('location_id', $input_data['location_id'])
                        ->where('is_sold', false)
                        ->where('type', $prod->item_type)
                        ->where('business_id', $business_id)
                        ->get();

                    if ($identifiers->count() != count($selected_identifiers)) {
                        throw new \Exception(__('lang_v1.some_identifiers_not_available'));
                    }

                    $imei_serial_transfers[] = [
                        'product_id' => $product['product_id'],
                        'variation_id' => $product['variation_id'],
                        'identifiers' => $identifiers,
                        'unit_price' => $product['unit_price'] ?? 0,
                        'product_unit_id' => $product['product_unit_id'] ?? null,
                        'sub_unit_id' => $product['sub_unit_id'] ?? null,
                        'base_unit_multiplier' => $product['base_unit_multiplier'] ?? null,
                        'lot_no_line_id' => $product['lot_no_line_id'] ?? null,
                    ];
                }
            }

            if (! empty($products)) {
                foreach ($products as $product) {
                    $prod = Product::find($product['product_id']);
                    // If IMEI/serial product and lot is selected, treat as regular product
                    if (in_array($prod->item_type, ['imei', 'serial']) && empty($product['lot_no_line_id'])) {
                        continue;
                    }

                    $sell_line_arr = [
                        'product_id' => $product['product_id'],
                        'variation_id' => $product['variation_id'],
                        'quantity' => $this->productUtil->num_uf($product['quantity']),
                        'item_tax' => 0,
                        'tax_id' => null, ];

                    if (! empty($product['product_unit_id'])) {
                        $sell_line_arr['product_unit_id'] = $product['product_unit_id'];
                    }
                    if (! empty($product['sub_unit_id'])) {
                        $sell_line_arr['sub_unit_id'] = $product['sub_unit_id'];
                    }

                    $purchase_line_arr = $sell_line_arr;

                    if (! empty($product['base_unit_multiplier'])) {
                        $sell_line_arr['base_unit_multiplier'] = $product['base_unit_multiplier'];
                    }

                    $sell_line_arr['unit_price'] = $this->productUtil->num_uf($product['unit_price']);
                    $sell_line_arr['unit_price_inc_tax'] = $sell_line_arr['unit_price'];

                    $purchase_line_arr['purchase_price'] = $sell_line_arr['unit_price'];
                    $purchase_line_arr['purchase_price_inc_tax'] = $sell_line_arr['unit_price'];

                    if (! empty($product['lot_no_line_id'])) {
                        //Add lot_no_line_id to sell line
                        $sell_line_arr['lot_no_line_id'] = $product['lot_no_line_id'];

                        //Copy lot number and expiry date to purchase line
                        $lot_details = PurchaseLine::find($product['lot_no_line_id']);
                        $purchase_line_arr['lot_number'] = $lot_details->lot_number;
                        $purchase_line_arr['mfg_date'] = $lot_details->mfg_date;
                        $purchase_line_arr['exp_date'] = $lot_details->exp_date;
                    }

                    if (! empty($product['base_unit_multiplier'])) {
                        $purchase_line_arr['quantity'] = $purchase_line_arr['quantity'] * $product['base_unit_multiplier'];
                        $purchase_line_arr['purchase_price'] = $purchase_line_arr['purchase_price'] / $product['base_unit_multiplier'];
                        $purchase_line_arr['purchase_price_inc_tax'] = $purchase_line_arr['purchase_price_inc_tax'] / $product['base_unit_multiplier'];
                    }

                    if (isset($purchase_line_arr['sub_unit_id']) && $purchase_line_arr['sub_unit_id'] == $purchase_line_arr['product_unit_id']) {
                        unset($purchase_line_arr['sub_unit_id']);
                    }
                    unset($purchase_line_arr['product_unit_id']);

                    $sell_lines[] = $sell_line_arr;
                    $purchase_lines[] = $purchase_line_arr;
                }
            }

            // Handle IMEI/Serial transfers
            foreach ($imei_serial_transfers as $transfer) {
                $quantity = $transfer['identifiers']->count();
                $sell_line_arr = [
                    'product_id' => $transfer['product_id'],
                    'variation_id' => $transfer['variation_id'],
                    'quantity' => $quantity,
                    'item_tax' => 0,
                    'tax_id' => null,
                ];

                if (! empty($transfer['product_unit_id'])) {
                    $sell_line_arr['product_unit_id'] = $transfer['product_unit_id'];
                }
                if (! empty($transfer['sub_unit_id'])) {
                    $sell_line_arr['sub_unit_id'] = $transfer['sub_unit_id'];
                }

                $purchase_line_arr = $sell_line_arr;

                if (! empty($transfer['base_unit_multiplier'])) {
                    $sell_line_arr['base_unit_multiplier'] = $transfer['base_unit_multiplier'];
                }

                $sell_line_arr['unit_price'] = $this->productUtil->num_uf($transfer['unit_price']);
                $sell_line_arr['unit_price_inc_tax'] = $sell_line_arr['unit_price'];

                $purchase_line_arr['purchase_price'] = $sell_line_arr['unit_price'];
                $purchase_line_arr['purchase_price_inc_tax'] = $sell_line_arr['unit_price'];

                if (! empty($transfer['lot_no_line_id'])) {
                    $sell_line_arr['lot_no_line_id'] = $transfer['lot_no_line_id'];

                    $lot_details = PurchaseLine::find($transfer['lot_no_line_id']);
                    $purchase_line_arr['lot_number'] = $lot_details->lot_number;
                    $purchase_line_arr['mfg_date'] = $lot_details->mfg_date;
                    $purchase_line_arr['exp_date'] = $lot_details->exp_date;
                }

                if (! empty($transfer['base_unit_multiplier'])) {
                    $purchase_line_arr['quantity'] = $purchase_line_arr['quantity'] * $transfer['base_unit_multiplier'];
                    $purchase_line_arr['purchase_price'] = $purchase_line_arr['purchase_price'] / $transfer['base_unit_multiplier'];
                    $purchase_line_arr['purchase_price_inc_tax'] = $purchase_line_arr['purchase_price_inc_tax'] / $transfer['base_unit_multiplier'];
                }

                if (isset($purchase_line_arr['sub_unit_id']) && $purchase_line_arr['sub_unit_id'] == $purchase_line_arr['product_unit_id']) {
                    unset($purchase_line_arr['sub_unit_id']);
                }
                unset($purchase_line_arr['product_unit_id']);

                $sell_lines[] = $sell_line_arr;
                $purchase_lines[] = $purchase_line_arr;
            }

            //Create Sell Transfer transaction
            $sell_transfer = Transaction::create($input_data);

            //Create Purchase Transfer at transfer location
            $input_data['type'] = 'purchase_transfer';
            $input_data['location_id'] = $request->input('transfer_location_id');
            $input_data['transfer_parent_id'] = $sell_transfer->id;
            $input_data['status'] = $status == 'completed' ? 'received' : $status;

            $purchase_transfer = Transaction::create($input_data);

            //Sell Product from first location
            if (! empty($sell_lines)) {
                $this->transactionUtil->createOrUpdateSellLines($sell_transfer, $sell_lines, $input_data['location_id'], false, null, [], false);
            }

            //Purchase product in second location
            if (! empty($purchase_lines)) {
                $purchase_transfer->purchase_lines()->createMany($purchase_lines);
            }

            // Associate IMEI numbers with sell lines for tracking (but don't mark as sold)
            foreach ($imei_serial_transfers as $index => $transfer) {
                if (isset($sell_lines[$index])) {
                    $sell_line_id = $sell_lines[$index]['transaction_sell_lines_id'] ?? null;
                    if (!$sell_line_id) {
                        // Find the sell line that was just created
                        $sell_line = TransactionSellLine::where('transaction_id', $sell_transfer->id)
                            ->where('variation_id', $transfer['variation_id'])
                            ->first();
                        if ($sell_line) {
                            $sell_line_id = $sell_line->id;
                        }
                    }

                    if ($sell_line_id) {
                        foreach ($transfer['identifiers'] as $imei) {
                            $imei->sell_line_id = $sell_line_id;
                            $imei->transfer_sell_line_id = $sell_line_id;
                            $imei->save();
                        }
                    }
                }
            }

            //Decrease product stock from sell location
            //And increase product stock at purchase location
            if ($status == 'completed') {
                foreach ($products as $product) {
                    $prod = Product::find($product['product_id']);
                    if ($product['enable_stock']) {
                        $decrease_qty = $this->productUtil->num_uf($product['quantity']);
                        if (! empty($product['base_unit_multiplier'])) {
                            $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                        }

                        $this->productUtil->decreaseProductQuantity(
                            $product['product_id'],
                            $product['variation_id'],
                            $sell_transfer->location_id,
                            $decrease_qty
                        );

                        $this->productUtil->updateProductQuantity(
                            $purchase_transfer->location_id,
                            $product['product_id'],
                            $product['variation_id'],
                            $decrease_qty,
                            0,
                            null,
                            false
                        );
                    }
                }

                // Update location for IMEI/Serial numbers
                foreach ($imei_serial_transfers as $transfer) {
                    foreach ($transfer['identifiers'] as $imei) {
                        $imei->transferToLocation($purchase_transfer->location_id);
                        // Keep sell_line_id for transfer tracking to enable reversal
                        $imei->save();
                    }
                }

                //Adjust stock over selling if found
                $this->productUtil->adjustStockOverSelling($purchase_transfer);

                //Map sell lines with purchase lines
                $business = ['id' => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id' => $sell_transfer->location_id,
                ];
                $this->transactionUtil->mapPurchaseSell($business, $sell_transfer->sell_lines, 'purchase');
            }

            $this->transactionUtil->activityLog($sell_transfer, 'added');

            event( new StockTransferCreatedOrModified($sell_transfer, 'added'));

            $output = ['success' => 1,
                'msg' => __('lang_v1.stock_transfer_added_successfully'),
            ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect('stock-transfers')->with('status', $output);
    }
    
    
    public function import(Request $request)
    {
        if (! auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $rows = [];
        $products = [];
        $file = $request->file("file");
        if($file->getMimeType() == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ||
        $file->getMimeType() == "application/vnd.ms-excel"
        ){
            
            return response()->json(array('success' => false, 'products' => [], 'msg' => '  use csv file instead of excel file. unsupported format.'));
            
            $file = $request->file('file'); 
            $file->move(storage_path('imports'), 'import.' . $file->getClientOriginalExtension());
            if(file_exists(storage_path('imports') . '/' . 'import.' . $file->getClientOriginalExtension())){
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile(storage_path('imports') . '/' . 'import.' . $file->getClientOriginalExtension());
                $reader->setReadDataOnly(true);
                $data = $reader->load(storage_path('imports') . '/' . 'import.' . $file->getClientOriginalExtension());
               $worksheet = $data->getActiveSheet();
             $rows = $worksheet->toArray();
                // dd($rows);
            } else {
                return response()->json(array('success' => false, 'products' => [], 'msg' => ' report this issue to us.'));
               // dd("file does not exits");
            }
        }else if($file->getMimeType() === "text/csv") {
            $file_content =   file_get_contents($file->getRealPath());

            $data = explode("\r\n", trim($file_content));
            $headings = explode(",", $data[0]);
            $sku_heading_count = 0;
            $qty_heading_count = 0;
            $lot_number_heading_count = 0;
            foreach ($headings as $heading){
              if($heading !== "SKU" &&
                $heading !== "Qty" &&
                $heading !== "Lot Number"
              )  {
                //   dd($heading);
                  return response()->json(array('success' => false, 'products' => [], 'msg' => 'Unsupported format. clean your data and try again.'));
              } else {
                  if($heading == "SKU") {
                      $sku_heading_count += 1;
                  } else if($heading == "Qty"){
                      $qty_heading_count += 1;
                  } else if($heading == "Lot Number") {
                      $lot_number_heading_count += 1;
                  }
              }
            }
            if($headings[0] !== "SKU" &&
                $headings[1] != "Qty" &&
                $headings[2] != "Lot Number"
            ){
                return response()->json(array('success' => false, 'products' => [], 'msg' => 'Unsupported format. clean your data and try again.'));
            }
            if($sku_heading_count != 1 &&
                $qty_heading_count != 1 &&
                $lot_number_heading_count != 1
            ){
                return response()->json(array('success' => false, 'products' => [], 'msg' => 'Unsupported format. clean your data and try again.'));
            }
            foreach ($data as $signle_data) {
                if(count(explode(",", $signle_data)) == 3) {
                    array_push($rows, explode(",", $signle_data));
                }else {
                    return response()->json(array('success' => false, 'products' => [], 'msg' => ' Unsupported format. clean data and try again. '));
                }
            }
        } else {
            return response()->json(array('success' => false, 'products' => [], 'msg' => 'unsupported format,  use csv file.'));
        }
       // dd($rows);
        foreach($rows as $key => $row) {
            if($key !== 0) {
            $product ;
            $product_by_sku = \App\Product::where("sku", $row[0])->get();
                $product_by_lot_number = \App\PurchaseLine::where("lot_number", $row[2])->get();
            if(count($product_by_lot_number) === 1) {
                $product = $product_by_lot_number[0];
            }else {
            $product_by_sku = \App\Product::where("sku", $row[0])->get();
                if(count($product_by_sku) === 1 ) {
                    $product = $product_by_sku[0];
                } else {
                    $product_by_sub_sku = \App\Variation::where("sub_sku", $row[0])->get();
                    if(count($product_by_sub_sku) === 1 ) {
                        $product = $product_by_sub_sku[0];
                    }
                }
            }
            }
            
            
            if(strpos($row[2], "-") || strpos($row[2], "/")){
                $product["lot_number"] =  $row[2];
            } else  {
                $product["lot_number"] = (int) $row[2] - 0;
            }
            $product['location_id'] = $request->location_id;
            $product['qty'] = $row[1];
            array_push($products, $product);
        }
        //dd($products);
        
        return response()->json(array('success' => true, 'products' => $products));
    }
    
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('purchase.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $sell_transfer = Transaction::where('business_id', $business_id)
                            ->where('id', $id)
                            ->where('type', 'sell_transfer')
                            ->with(
                                'contact',
                                'sell_lines',
                                'sell_lines.product',
                                'sell_lines.variations',
                                'sell_lines.variations.product_variation',
                                'sell_lines.lot_details',
                                'sell_lines.sub_unit',
                                'location',
                                'sell_lines.product.unit',
                                'sell_lines.transferredImeiNumbers'
                            )
                            ->first();

        foreach ($sell_transfer->sell_lines as $key => $value) {
            if (! empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);

                $sell_transfer->sell_lines[$key] = $formated_sell_line;
            }
        }

        $purchase_transfer = Transaction::where('business_id', $business_id)
                    ->where('transfer_parent_id', $sell_transfer->id)
                    ->where('type', 'purchase_transfer')
                    ->with('purchase_lines.imeiNumbers')
                    ->first();

        $location_details = ['sell' => $sell_transfer->location, 'purchase' => $purchase_transfer->location];

        $lot_n_exp_enabled = false;
        if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
            $lot_n_exp_enabled = true;
        }

        $statuses = $this->stockTransferStatuses();

        $statuses['final'] = __('restaurant.completed');

        $activities = Activity::forSubject($sell_transfer)
           ->with(['causer', 'subject'])
           ->latest()
           ->get();

        return view('stock_transfer.show')
                ->with(compact('sell_transfer', 'location_details', 'lot_n_exp_enabled', 'statuses', 'activities', 'purchase_transfer'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('purchase.delete')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (request()->ajax()) {
                $edit_days = request()->session()->get('business.transaction_edit_days');
                if (! $this->transactionUtil->canBeEdited($id, $edit_days)) {
                    return ['success' => 0,
                        'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days]), ];
                }

                //Get sell transfer transaction
                $sell_transfer = Transaction::where('id', $id)
                                    ->where('type', 'sell_transfer')
                                    ->with(['sell_lines'])
                                    ->first();

                //Get purchase transfer transaction
                $purchase_transfer = Transaction::where('transfer_parent_id', $sell_transfer->id)
                                    ->where('type', 'purchase_transfer')
                                    ->with(['purchase_lines'])
                                    ->first();

                //Check if any transfer stock is deleted and delete purchase lines
                $purchase_lines = $purchase_transfer->purchase_lines;
                foreach ($purchase_lines as $purchase_line) {
                    if ($purchase_line->quantity_sold > 0) {
                        return ['success' => 0,
                            'msg' => __('lang_v1.stock_transfer_cannot_be_deleted'),
                        ];
                    }
                }

                // Handle IMEI reversal for completed transfers
                if ($sell_transfer->status == 'final') {
                    foreach ($sell_transfer->sell_lines as $sell_line) {
                        if (in_array($sell_line->product->item_type, ['imei', 'serial'])) {
                            $imei_numbers = $sell_line->imei_numbers;
                            foreach ($imei_numbers as $imei) {
                                $imei->location_id = $sell_transfer->location_id;
                                $imei->sell_line_id = null;
                                $imei->save();
                            }
                        }
                    }
                }

                event( new StockTransferCreatedOrModified($sell_transfer, 'deleted'));

                DB::beginTransaction();
                //Get purchase lines from transaction_sell_lines_purchase_lines and decrease quantity_sold
                $sell_lines = $sell_transfer->sell_lines;
                $deleted_sell_purchase_ids = [];
                $products = []; //variation_id as array

                foreach ($sell_lines as $sell_line) {
                    $purchase_sell_line = TransactionSellLinesPurchaseLines::where('sell_line_id', $sell_line->id)->first();

                    if (! empty($purchase_sell_line)) {
                        //Decrease quntity sold from purchase line
                        PurchaseLine::where('id', $purchase_sell_line->purchase_line_id)
                                ->decrement('quantity_sold', $sell_line->quantity);

                        $deleted_sell_purchase_ids[] = $purchase_sell_line->id;

                        //variation details
                        if (isset($products[$sell_line->variation_id])) {
                            $products[$sell_line->variation_id]['quantity'] += $sell_line->quantity;
                            $products[$sell_line->variation_id]['product_id'] = $sell_line->product_id;
                        } else {
                            $products[$sell_line->variation_id]['quantity'] = $sell_line->quantity;
                            $products[$sell_line->variation_id]['product_id'] = $sell_line->product_id;
                        }
                    }
                }

                //Update quantity available in both location
                if (! empty($products)) {
                    foreach ($products as $key => $value) {
                        //Decrease from location 2
                        $this->productUtil->decreaseProductQuantity(
                            $products[$key]['product_id'],
                            $key,
                            $purchase_transfer->location_id,
                            $products[$key]['quantity']
                        );

                        //Increase in location 1
                        $this->productUtil->updateProductQuantity(
                            $sell_transfer->location_id,
                            $products[$key]['product_id'],
                            $key,
                            $products[$key]['quantity']
                        );
                    }
                }

                //Delete sale line purchase line
                if (! empty($deleted_sell_purchase_ids)) {
                    TransactionSellLinesPurchaseLines::whereIn('id', $deleted_sell_purchase_ids)
                        ->delete();
                }

                //Delete both transactions
                $sell_transfer->delete();
                $purchase_transfer->delete();
                event( new StockTransferCreatedOrModified($sell_transfer, 'deleted'));
                $output = ['success' => 1,
                    'msg' => __('lang_v1.stock_transfer_delete_success'),
                ];
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Checks if ref_number and supplier combination already exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function printInvoice($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $business_details = \App\Business::find($business_id);
            $gst_number = "";
            $gst_label = "";
            if(str_contains($business_details["tax_label_1"], "GST")){
                $gst_number = $business_details["tax_number_1"];
                $gst_label = $business_details["tax_label_1"];
            }else if(str_contains($business_details["tax_label_2"], "GST")) {
                $gst_number = $business_details["tax_number_2"];
                $gst_label = $business_details["tax_label_2"];
            }

            $sell_transfer = Transaction::where('business_id', $business_id)
                                ->where('id', $id)
                                ->where('type', 'sell_transfer')
                                ->with(
                                    'contact',
                                    'sell_lines',
                                    'sell_lines.product',
                                    'sell_lines.variations',
                                    'sell_lines.variations.product_variation',
                                    'sell_lines.lot_details',
                                    'location',
                                    'sell_lines.product.unit',
                                    'sell_lines.transferredImeiNumbers'
                                )
                                ->first();

            $purchase_transfer = Transaction::where('business_id', $business_id)
                        ->where('transfer_parent_id', $sell_transfer->id)
                        ->where('type', 'purchase_transfer')
                        ->with('purchase_lines.imeiNumbers')
                        ->first();

            $location_details = ['sell' => $sell_transfer->location, 'purchase' => $purchase_transfer->location];

            $lot_n_exp_enabled = false;
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_n_exp_enabled = true;
            }
            
            $output = ['success' => 1, 'receipt' => [], 'print_title' => $sell_transfer->ref_no];
            $output['receipt']['html_content'] = view('stock_transfer.print', compact('sell_transfer', 'location_details', 'lot_n_exp_enabled', 'gst_number', 'gst_label', 'purchase_transfer'))->render();
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id);

        $statuses = $this->stockTransferStatuses();

        $sell_transfer = Transaction::where('business_id', $business_id)
                ->where('type', 'sell_transfer')
                ->where('status', '!=', 'final')
                ->with(['sell_lines.imei_numbers'])
                ->findOrFail($id);

        $purchase_transfer = Transaction::where('business_id',
                $business_id)
                ->where('transfer_parent_id', $id)
                ->where('status', '!=', 'received')
                ->where('type', 'purchase_transfer')
                ->first();

        $products = [];
        foreach ($sell_transfer->sell_lines as $sell_line) {
            $product = $this->productUtil->getDetailsFromVariation($sell_line->variation_id, $business_id, $sell_transfer->location_id, false);
            $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available);
            $product->sub_unit_id = $sell_line->sub_unit_id;
            $product->quantity_ordered = $sell_line->quantity;
            $product->transaction_sell_lines_id = $sell_line->id;
            $product->lot_no_line_id = $sell_line->lot_no_line_id;
            $product->imei_numbers = $sell_line->imei_numbers;

            $product->unit_details = $this->productUtil->getSubUnits($business_id, $product->unit_id);

            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($sell_line->variation_id, $business_id, $sell_transfer->location_id, true);
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;

            $products[] = $product;
        }

        return view('stock_transfer.edit')
                ->with(compact('sell_transfer', 'purchase_transfer', 'business_locations', 'statuses', 'products'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not
            if (! $this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\StockTransferController::class, 'index']));
            }

            $request->validate([
                'location_id' => 'required|exists:business_locations,id,business_id,' . $business_id,
                'transfer_location_id' => 'required|exists:business_locations,id,business_id,' . $business_id,
            ]);

            $business_id = request()->session()->get('user.business_id');

            $sell_transfer = Transaction::where('business_id', $business_id)
                    ->where('type', 'sell_transfer')
                    ->findOrFail($id);

            $sell_transfer_before = $sell_transfer->replicate();

            $purchase_transfer = Transaction::where('business_id',
                    $business_id)
                    ->where('transfer_parent_id', $id)
                    ->where('type', 'purchase_transfer')
                    ->with(['purchase_lines'])
                    ->first();

            $status = $request->input('status');

            DB::beginTransaction();

            $input_data = $request->only(['transaction_date', 'additional_notes', 'shipping_charges', 'final_total']);
            $status = $request->input('status');

            $input_data['total_before_tax'] = $input_data['final_total'];

            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            $input_data['shipping_charges'] = $this->productUtil->num_uf($input_data['shipping_charges']);
            $input_data['status'] = $status == 'completed' ? 'final' : $status;

            $products = $request->input('products');
            $sell_lines = [];
            $purchase_lines = [];
            $edited_purchase_lines = [];
            if (! empty($products)) {
                foreach ($products as $product) {
                    $sell_line_arr = [
                        'product_id' => $product['product_id'],
                        'variation_id' => $product['variation_id'],
                        'quantity' => $this->productUtil->num_uf($product['quantity']),
                        'item_tax' => 0,
                        'tax_id' => null, ];

                    if (! empty($product['product_unit_id'])) {
                        $sell_line_arr['product_unit_id'] = $product['product_unit_id'];
                    }
                    if (! empty($product['sub_unit_id'])) {
                        $sell_line_arr['sub_unit_id'] = $product['sub_unit_id'];
                    }

                    $purchase_line_arr = $sell_line_arr;

                    if (! empty($product['base_unit_multiplier'])) {
                        $sell_line_arr['base_unit_multiplier'] = $product['base_unit_multiplier'];
                    }

                    $sell_line_arr['unit_price'] = $this->productUtil->num_uf($product['unit_price']);
                    $sell_line_arr['unit_price_inc_tax'] = $sell_line_arr['unit_price'];

                    $purchase_line_arr['purchase_price'] = $sell_line_arr['unit_price'];
                    $purchase_line_arr['purchase_price_inc_tax'] = $sell_line_arr['unit_price'];
                    if (isset($product['transaction_sell_lines_id'])) {
                        $sell_line_arr['transaction_sell_lines_id'] = $product['transaction_sell_lines_id'];
                    }

                    if (! empty($product['lot_no_line_id'])) {
                        //Add lot_no_line_id to sell line
                        $sell_line_arr['lot_no_line_id'] = $product['lot_no_line_id'];

                        //Copy lot number and expiry date to purchase line
                        $lot_details = PurchaseLine::find($product['lot_no_line_id']);
                        $purchase_line_arr['lot_number'] = $lot_details->lot_number;
                        $purchase_line_arr['mfg_date'] = $lot_details->mfg_date;
                        $purchase_line_arr['exp_date'] = $lot_details->exp_date;
                    }

                    if (! empty($product['base_unit_multiplier'])) {
                        $purchase_line_arr['quantity'] = $purchase_line_arr['quantity'] * $product['base_unit_multiplier'];
                        $purchase_line_arr['purchase_price'] = $purchase_line_arr['purchase_price'] / $product['base_unit_multiplier'];
                        $purchase_line_arr['purchase_price_inc_tax'] = $purchase_line_arr['purchase_price_inc_tax'] / $product['base_unit_multiplier'];
                    }

                    if (isset($purchase_line_arr['sub_unit_id']) && $purchase_line_arr['sub_unit_id'] == $purchase_line_arr['product_unit_id']) {
                        unset($purchase_line_arr['sub_unit_id']);
                    }
                    unset($purchase_line_arr['product_unit_id']);

                    $sell_lines[] = $sell_line_arr;

                    $purchase_line = [];
                    //check if purchase_line for the variation exists else create new
                    foreach ($purchase_transfer->purchase_lines as $pl) {
                        if ($pl->variation_id == $purchase_line_arr['variation_id']) {
                            $pl->update($purchase_line_arr);
                            $edited_purchase_lines[] = $pl->id;
                            $purchase_line = $pl;
                            break;
                        }
                    }
                    if (empty($purchase_line)) {
                        $purchase_line = new PurchaseLine($purchase_line_arr);
                    }

                    $purchase_lines[] = $purchase_line;
                }
            }

            //Create Sell Transfer transaction
            $sell_transfer->update($input_data);
            $sell_transfer->save();

            event( new StockTransferCreatedOrModified($sell_transfer, 'updated'));

            //Create Purchase Transfer at transfer location
            $input_data['status'] = $status == 'completed' ? 'received' : $status;

            $purchase_transfer->update($input_data);
            $purchase_transfer->save();

            //Sell Product from first location
            if (! empty($sell_lines)) {
                $this->transactionUtil->createOrUpdateSellLines($sell_transfer, $sell_lines, $sell_transfer->location_id, false, 'draft', [], false);
            }

            //Purchase product in second location
            if (! empty($purchase_lines)) {
                if (! empty($edited_purchase_lines)) {
                    PurchaseLine::where('transaction_id', $purchase_transfer->id)
                    ->whereNotIn('id', $edited_purchase_lines)
                    ->delete();
                }
                $purchase_transfer->purchase_lines()->saveMany($purchase_lines);
            }

            //Decrease product stock from sell location
            //And increase product stock at purchase location
            if ($status == 'completed') {
                foreach ($products as $product) {
                    if ($product['enable_stock']) {
                        $decrease_qty = $this->productUtil
                                    ->num_uf($product['quantity']);
                        if (! empty($product['base_unit_multiplier'])) {
                            $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                        }

                        $this->productUtil->decreaseProductQuantity(
                            $product['product_id'],
                            $product['variation_id'],
                            $sell_transfer->location_id,
                            $decrease_qty
                        );

                        $this->productUtil->updateProductQuantity(
                            $purchase_transfer->location_id,
                            $product['product_id'],
                            $product['variation_id'],
                            $decrease_qty,
                            0,
                            null,
                            false
                        );
                    }
                }

                //Adjust stock over selling if found
                $this->productUtil->adjustStockOverSelling($purchase_transfer);

                //Map sell lines with purchase lines
                $business = ['id' => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id' => $sell_transfer->location_id,
                ];
                $this->transactionUtil->mapPurchaseSell($business, $sell_transfer->sell_lines, 'purchase');
            }

            $this->transactionUtil->activityLog($sell_transfer, 'edited', $sell_transfer_before);

            $output = ['success' => 1,
                'msg' => __('lang_v1.updated_succesfully'),
            ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect('stock-transfers')->with('status', $output);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        if (! auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $sell_transfer = Transaction::where('business_id', $business_id)
                    ->where('type', 'sell_transfer')
                    ->with(['sell_lines', 'sell_lines.product'])
                    ->findOrFail($id);

            $purchase_transfer = Transaction::where('business_id',
                    $business_id)
                    ->where('transfer_parent_id', $id)
                    ->where('type', 'purchase_transfer')
                    ->with(['purchase_lines'])
                    ->first();

            $status = $request->input('status');

            DB::beginTransaction();
            if ($status == 'completed' && $sell_transfer->status != 'completed') {
                foreach ($sell_transfer->sell_lines as $sell_line) {
                    if ($sell_line->product->enable_stock) {
                        $this->productUtil->decreaseProductQuantity(
                            $sell_line->product_id,
                            $sell_line->variation_id,
                            $sell_transfer->location_id,
                            $sell_line->quantity
                        );

                        $this->productUtil->updateProductQuantity(
                            $purchase_transfer->location_id,
                            $sell_line->product_id,
                            $sell_line->variation_id,
                            $sell_line->quantity,
                            0,
                            null,
                            false
                        );
                    }
                }

                // Update IMEI/Serial locations and purchase_item_id for completed transfers
                foreach ($sell_transfer->sell_lines as $sell_line) {
                    if (in_array($sell_line->product->item_type, ['imei', 'serial'])) {
                        // Find the corresponding purchase line
                        $purchase_line = PurchaseLine::where('transaction_id', $purchase_transfer->id)
                            ->where('variation_id', $sell_line->variation_id)
                            ->first();

                        $imei_numbers = $sell_line->imei_numbers;
                        foreach ($imei_numbers as $imei) {
                            $imei->transferToLocation($purchase_transfer->location_id);
                            // Keep sell_line_id for transfer tracking to enable reversal
                            $imei->save();
                        }
                    }
                }

                //Adjust stock over selling if found
                $this->productUtil->adjustStockOverSelling($purchase_transfer);

                //Map sell lines with purchase lines
                $business = ['id' => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id' => $sell_transfer->location_id,
                ];
                $this->transactionUtil->mapPurchaseSell($business, $sell_transfer->sell_lines, 'purchase');
            }
            $purchase_transfer->status = $status == 'completed' ? 'received' : $status;
            $purchase_transfer->save();
            $sell_transfer->status = $status == 'completed' ? 'final' : $status;
            $sell_transfer->save();

            DB::commit();

            $output = ['success' => 1,
                'msg' => __('lang_v1.updated_succesfully'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => 'File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage(),
            ];
        }

        return $output;
    }

    /**
     * Get available IMEI/Serial numbers for a product at a location
     */
    public function getAvailableImeiSerial(Request $request)
    {
        $variation_id = $request->input('variation_id');
        $item_type = $request->input('item_type');
        $location_id = $request->input('location_id');
        $business_id = $request->session()->get('user.business_id');

        $identifiers = ImeiNumber::where('location_id', $location_id)
            ->where('business_id', $business_id)
            ->where('type', $item_type)
            ->where('is_sold', false)
            ->whereHas('purchaseLine', function ($query) use ($variation_id) {
                $query->where('variation_id', $variation_id);
            })
            ->get(['identifier']);

        return response()->json([
            'success' => true,
            'data' => $identifiers
        ]);
    }
}
