<?php

namespace App\Http\Controllers;

use App\Barcode;
use App\Product;
use App\SellingPriceGroup;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// use Milon\Barcode\Facades\DNS1DFacade as DNS1D;
// use Milon\Barcode\Facades\DNS2DFacade as DNS2D;
use Milon\Barcode\DNS1D;
use Milon\Barcode\DNS2D;


class LabelsController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $transactionUtil;

    protected $productUtil;

    /**
     * Constructor
     *
     * @param  TransactionUtil  $TransactionUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Display labels
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $purchase_id = $request->get('purchase_id', false);
        $product_id = $request->get('product_id', false);

        //Get products for the business
        $products = [];
        $price_groups = [];
        if ($purchase_id) {
            $products = $this->transactionUtil->getPurchaseProducts($business_id, $purchase_id);
        } elseif ($product_id) {
            $products = $this->productUtil->getDetailsFromProduct($business_id, $product_id);
        }

        //get price groups
        $price_groups = [];
        if (! empty($purchase_id) || ! empty($product_id)) {
            $price_groups = SellingPriceGroup::where('business_id', $business_id)
                                    ->active()
                                    ->pluck('name', 'id');
        }

        $barcode_settings = Barcode::where('business_id', $business_id)
                                ->orWhereNull('business_id')
                                ->select(DB::raw('CONCAT(name, ", ", COALESCE(description, "")) as name, id, is_default'))
                                ->get();
        $default = $barcode_settings->where('is_default', 1)->first();
        $barcode_settings = $barcode_settings->pluck('name', 'id');

        return view('labels.show')
            ->with(compact('products', 'barcode_settings', 'default', 'price_groups'));
    }

    /**
     * Returns the html for product row
     *
     * @return \Illuminate\Http\Response
     */
    public function addProductRow(Request $request)
    {
        if ($request->ajax()) {
            $product_id = $request->input('product_id');
            $variation_id = $request->input('variation_id');
            $business_id = $request->session()->get('user.business_id');

            if (! empty($product_id)) {
                $index = $request->input('row_count');
                $products = $this->productUtil->getDetailsFromProduct($business_id, $product_id, $variation_id);

                $price_groups = SellingPriceGroup::where('business_id', $business_id)
                                            ->active()
                                            ->pluck('name', 'id');

                return view('labels.partials.show_table_rows')
                        ->with(compact('products', 'index', 'price_groups'));
            }
        }
    }

    /**
     * Returns the html for labels preview
     *
     * @return \Illuminate\Http\Response
     */
    public function preview(Request $request)
    {
        // dd($request);
        try {
            $products = $request->get('products');
            $print = $request->get('print');
            $barcode_setting = $request->get('barcode_setting');
            $business_id = $request->session()->get('user.business_id');

            $barcode_details = Barcode::find($barcode_setting);
            $barcode_details->stickers_in_one_sheet = $barcode_details->is_continuous ? $barcode_details->stickers_in_one_row : $barcode_details->stickers_in_one_sheet;
            $barcode_details->paper_height = $barcode_details->is_continuous ? $barcode_details->height : $barcode_details->paper_height;
            if ($barcode_details->stickers_in_one_row == 1) {
                $barcode_details->col_distance = 0;
                $barcode_details->row_distance = 0;
            }
            // if($barcode_details->is_continuous){
            //     $barcode_details->row_distance = 0;
            // }

            $business_name = $request->session()->get('business.name');

            $product_details_page_wise = [];
            $total_qty = 0;
            foreach ($products as $value) {
                $details = $this->productUtil->getDetailsFromVariation($value['variation_id'], $business_id, null, false);

                if (! empty($value['exp_date'])) {
                    $details->exp_date = $value['exp_date'];
                }
                if (! empty($value['packing_date'])) {
                    $details->packing_date = $value['packing_date'];
                }
                if (! empty($value['lot_number'])) {
                    $details->lot_number = $value['lot_number'];
                }

                if (! empty($value['price_group_id'])) {
                    $tax_id = $print['price_type'] == 'inclusive' ?: $details->tax_id;

                    $group_prices = $this->productUtil->getVariationGroupPrice($value['variation_id'], $value['price_group_id'], $tax_id);

                    $details->sell_price_inc_tax = $group_prices['price_inc_tax'];
                    $details->default_sell_price = $group_prices['price_exc_tax'];
                }

                for ($i = 0; $i < $value['quantity']; $i++) {
                    $page = intdiv($total_qty, $barcode_details->stickers_in_one_sheet);

                    if ($total_qty % $barcode_details->stickers_in_one_sheet == 0) {
                        $product_details_page_wise[$page] = [];
                    }

                    $product_details_page_wise[$page][] = $details;
                    $total_qty++;
                }
            }

            $margin_top = $barcode_details->is_continuous ? 0 : $barcode_details->top_margin * 1;
            $margin_left = $barcode_details->is_continuous ? 0 : $barcode_details->left_margin * 1;
            $paper_width = $barcode_details->paper_width * 1;
            $paper_height = $barcode_details->paper_height * 1;

            // print_r($paper_height);
            // echo "==";
            // print_r($margin_left);exit;

            // $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8',
            //             'format' => [$paper_width, $paper_height],
            //             'margin_top' => $margin_top,
            //             'margin_bottom' => $margin_top,
            //             'margin_left' => $margin_left,
            //             'margin_right' => $margin_left,
            //             'autoScriptToLang' => true,
            //             // 'disablePrintCSS' => true,
            // 'autoLangToFont' => true,
            // 'autoVietnamese' => true,
            // 'autoArabic' => true
            //             ]
            //         );
            //print_r($mpdf);exit;

            $i = 0;
            $len = count($product_details_page_wise);
            $is_first = false;
            $is_last = false;

            //$original_aspect_ratio = 4;//(w/h)
            $factor = (($barcode_details->width / $barcode_details->height)) / ($barcode_details->is_continuous ? 2 : 4);
            $html = '';
            foreach ($product_details_page_wise as $page => $page_products) {
                if ($i == 0) {
                    $is_first = true;
                }

                if ($i == $len - 1) {
                    $is_last = true;
                }

                $output = view('labels.partials.preview_2')
                            ->with(compact('print', 'page_products', 'business_name', 'barcode_details', 'margin_top', 'margin_left', 'paper_width', 'paper_height', 'is_first', 'is_last', 'factor'))->render();
                print_r($output);
                //$mpdf->WriteHTML($output);

                // if($i < $len - 1){
                //     // '', '', '', '', '', '', $margin_left, $margin_left, $margin_top, $margin_top, '', '', '', '', '', '', 0, 0, 0, 0, '', [$barcode_details->paper_width*1, $barcode_details->paper_height*1]
                //     $mpdf->AddPage();
                // }

                $i++;
            }

            print_r('<script>window.print()</script>');
            //exit;
            //return $output;

            //$mpdf->Output();

            // $page_height = null;
            // if ($barcode_details->is_continuous) {
            //     $rows = ceil($total_qty/$barcode_details->stickers_in_one_row) + 0.4;
            //     $barcode_details->paper_height = $barcode_details->top_margin + ($rows*$barcode_details->height) + ($rows*$barcode_details->row_distance);
            // }

            // $output = view('labels.partials.preview')
            //     ->with(compact('print', 'product_details', 'business_name', 'barcode_details', 'product_details_page_wise'))->render();

            // $output = ['html' => $html,
            //                 'success' => true,
            //                 'msg' => ''
            //             ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = __('lang_v1.barcode_label_error');
        }

        //return $output;
    }
    
    /**
 * Enhanced label printing interface
 */
public function enhancedShow(Request $request)
{
    $business_id = $request->session()->get('user.business_id');
    $purchase_id = $request->get('purchase_id', false);
    $product_id = $request->get('product_id', false);

    //Get products for the business
    $products = [];
    $price_groups = [];
    if ($purchase_id) {
        $products = $this->transactionUtil->getPurchaseProducts($business_id, $purchase_id);
    } elseif ($product_id) {
        $products = $this->productUtil->getDetailsFromProduct($business_id, $product_id);
    }

    //get price groups
    $price_groups = [];
    if (! empty($purchase_id) || ! empty($product_id)) {
        $price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->active()
            ->pluck('name', 'id');
    }

    $barcode_settings = Barcode::where('business_id', $business_id)
        ->orWhereNull('business_id')
        ->select(DB::raw('CONCAT(name, ", ", COALESCE(description, "")) as name, id, is_default'))
        ->get();
    $default = $barcode_settings->where('is_default', 1)->first();
    $barcode_settings = $barcode_settings->pluck('name', 'id');

    // Fetch the business logo path
    $business_logo = request()->session()->get('business.logo');
    if (! empty($business_logo)) {
        $business_logo = asset('uploads/business_logos/' . $business_logo);
    }

    return view('labels.enhanced-show')
        ->with(compact('products', 'barcode_settings', 'default', 'price_groups', 'business_logo'));
}

/**
 * Enhanced preview method (reuses existing logic)
 */
public function enhancedPreview(Request $request)
{

    // Reuse the existing show_label_preview method
    return $this->show_label_preview($request);
}

/**
 * Generate optimized barcode with dynamic sizing
 */
private function generateOptimizedBarcode($data, $label_width_inches, $barcode_size_setting)
{
    $width_factor = $this->calculateOptimalBarcodeWidth($label_width_inches);
    $height_pixels = max(40, $barcode_size_setting * 40);
    
    

    $dns1d = new DNS1D();
    $barcode = $dns1d->getBarcodePNG(
        $data,
        'C128',
        $width_factor,
        $height_pixels,
        [0, 0, 0],
        true
    );

    return $barcode;
}

/**
 * Calculate optimal barcode width factor based on label dimensions
 */
private function calculateOptimalBarcodeWidth($label_width_inches)
{
    if ($label_width_inches <= 1.5) {
        return 4; // Narrow labels
    } elseif ($label_width_inches <= 2.5) {
        return 6; // Medium labels
    } else {
        return 8; // Wide labels
    }
}

/**
 * Generate high-resolution QR code with error correction
 */
private function generateOptimizedQRCode($data, $size_setting)
{
    $pixel_size = max(120, $size_setting * 80);

    $dns2d = new DNS2D();
    $qr_code = $dns2d->getBarcodePNG(
        $data,
        'QRCODE',
        $pixel_size,
        $pixel_size,
        [0, 0, 0],
        false,
        3
    );

    return $qr_code;
}

/**
 * Get label image URL from settings
 */
public function getLabelImageUrlFromSettings($business_id)
{
    $business_logo = request()->session()->get('business.logo');
    if (! empty($business_logo)) {
        $business_logo = asset('uploads/business_logos/' . $business_logo);
    }
    return $business_logo;
}
/**
 * Enhanced show_label_preview method with optimizations
 */
public function show_label_preview(Request $request)
{
    try {
        $products = $request->get('products');
        $print = $request->get('print');
        $barcode_setting = $request->get('barcode_setting');
        $business_id = $request->session()->get('user.business_id');

        $barcode_details = Barcode::find($barcode_setting);
        $barcode_details->stickers_in_one_sheet = $barcode_details->is_continuous ? $barcode_details->stickers_in_one_row : $barcode_details->stickers_in_one_sheet;
        $barcode_details->paper_height = $barcode_details->is_continuous ? $barcode_details->height : $barcode_details->paper_height;

        if ($barcode_details->stickers_in_one_row == 1) {
            $barcode_details->col_distance = 0;
            $barcode_details->row_distance = 0;
        }

        $business_name = $request->session()->get('business.name');

        // Handle image source selection
        $image_url = null;
        if (!empty($print['image'])) {
            if ($print['image_source'] === 'select_image' && !empty($print['select_image_url'])) {
                $image_url = $print['select_image_url'];
            } elseif ($print['image_source'] === 'label_image') {
                $image_url = $this->getLabelImageUrlFromSettings($business_id);
            }
        }

        $product_details_page_wise = [];
        $total_qty = 0;

        foreach ($products as $value) {
            $details = $this->productUtil->getDetailsFromVariation($value['variation_id'], $business_id, null, false);

            // Format prices properly
            $details->sell_price_inc_tax = $this->productUtil->num_f($details->sell_price_inc_tax) ?: $details->sell_price_inc_tax;
            $details->default_sell_price = $this->productUtil->num_f($details->default_sell_price) ?: $details->default_sell_price;

            if (!empty($value['exp_date'])) {
                $details->exp_date = $value['exp_date'];
            }
            if (!empty($value['packing_date'])) {
                $details->packing_date = $value['packing_date'];
            }
            if (!empty($value['lot_number'])) {
                $details->lot_number = $value['lot_number'];
            }

            if (!empty($value['price_group_id'])) {
                $tax_id = $print['price_type'] == 'inclusive' ?: $details->tax_id;
                $group_prices = $this->productUtil->getVariationGroupPrice($value['variation_id'], $value['price_group_id'], $tax_id);
                $details->sell_price_inc_tax = $group_prices['price_inc_tax'];
                $details->default_sell_price = $group_prices['price_exc_tax'];
            }

            for ($i = 0; $i < $value['quantity']; $i++) {
                $page = intdiv($total_qty, $barcode_details->stickers_in_one_sheet);

                if ($total_qty % $barcode_details->stickers_in_one_sheet == 0) {
                    $product_details_page_wise[$page] = [];
                }
                    //  dd($details->sub_sku);
                // OPTIMIZATION: Pre-generate optimized barcodes and QR codes
                if (!empty($print['barcode'])) {
                                        //  dd($details);
                    $details->optimized_barcode = $this->generateOptimizedBarcode(
   
                        $details->sub_sku,
                        $barcode_details->width,
                        $print['barcode_size'] ?? 0.8
                    );
                }

                if (!empty($print['qrcode'])) {
                    $details->optimized_qrcode = $this->generateOptimizedQRCode(
                        $details->sub_sku,
                        $print['qrcode_size'] ?? 1.4
                    );
                }

                $product_details_page_wise[$page][] = $details;
                $total_qty++;
            }
        }

        $margin_top = $barcode_details->is_continuous ? 0 : $barcode_details->top_margin * 1;
        $margin_left = $barcode_details->is_continuous ? 0 : $barcode_details->left_margin * 1;
        $paper_width = $barcode_details->paper_width * 1;
        $paper_height = $barcode_details->paper_height * 1;

        $i = 0;
        $len = count($product_details_page_wise);
        $is_first = false;
        $is_last = false;

        $factor = (($barcode_details->width / $barcode_details->height)) / ($barcode_details->is_continuous ? 2 : 4);
        $html = '';

        foreach ($product_details_page_wise as $page => $page_products) {
            $output = view('labels.partials.enhanced_preview')  // Use enhanced template
                ->with(compact('print', 'page_products', 'business_name', 'barcode_details', 'margin_top', 'margin_left', 'paper_width', 'paper_height', 'is_first', 'is_last', 'factor', 'image_url'))
                ->render();

            $html .= $output;
        }

        return response()->json(['success' => true, 'html' => $html]);
    } catch (\Exception $e) {
        \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

        return response()->json([
            'success' => false,
            'msg' => __('lang_v1.barcode_label_error')
        ]);
    }
}
}
