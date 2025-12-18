<table
    style="padding: 2px; margin: 0 auto; border-spacing: {{$barcode_details->col_distance * 1}}in {{$barcode_details->row_distance * 1}}in;">
    @foreach($page_products as $page_product)
    @if($loop->index % $barcode_details->stickers_in_one_row == 0)
    <!-- create a new row -->
    <tr>
        @endif
        <td align="center" valign="center" style="padding: 2px; border: 1px dotted lightgray;">
            <div
                style="display: flex; flex-direction: column; align-items: center; text-align: center; margin: 0; padding: 0; width: {{$barcode_details->width * 1}}in; height: {{$barcode_details->height * 1}}in; justify-content: center;">

                <!-- Business Name -->
                @if(!empty($print['business_name']))
                <b style="font-size: {{ $print['business_name_size'] - 2 }}px; margin-bottom: 1px;">{{ $business_name
                    }}</b>
                @endif

                <!-- Product Name -->
                @if(!empty($print['name']))
                <span style="font-size: {{ $print['name_size'] - 2 }}px; margin-bottom: 1px;">
                    <b>{{ $page_product->product_actual_name }}</b>
                    @if(!empty($print['lot_number']) && !empty($page_product->lot_number))
                    <span style="font-size: 8px; margin-left: 2px;">
                        ({{ $page_product->lot_number }})
                    </span>
                    @endif
                </span>
                @endif

                <!-- Variation -->
                @if(!empty($print['variations']) && $page_product->is_dummy != 1)
                <span style="font-size: {{$print['variations_size']}}px; margin-bottom: 4px;">
                    {{$page_product->product_variation_name}}: <b>{{$page_product->variation_name}}</b>
                </span>
                @endif

                <!-- Custom Fields -->
                @php
                $custom_labels = json_decode(session('business.custom_labels'), true);
                $product_custom_fields = !empty($custom_labels['product']) ? $custom_labels['product'] : [];
                @endphp
                @foreach($product_custom_fields as $index => $cf)
                @php
                $field_name = 'product_custom_field' . $loop->iteration;
                @endphp
                @if(!empty($cf) && !empty($page_product->$field_name) && !empty($print[$field_name]))
                <span style="font-size: {{ $print[$field_name . '_size'] }}px; margin-bottom: 4px;">
                    <b>{{ $cf }}:</b> {{ $page_product->$field_name }}
                </span>
                @endif
                @endforeach

                <!-- Enhanced Price with Proper Currency Placement -->
                @if(!empty($print['price']))
                <span style="font-size: {{ $print['price_size'] ?? 10 }}px; margin-bottom: 1px;">
                    @lang('lang_v1.price'):
                    <b>
                        @php
                        $currency_symbol = session('currency')['symbol'] ?? '';
                        $currency_placement = session('business.currency_symbol_placement') ?? 'before';

                        if(!empty($print['price_type']) && $print['price_type'] == 'inclusive') {
                            $price_amount = $page_product->sell_price_inc_tax;
                        } else {
                            $price_amount = $page_product->default_sell_price;
                        }
                        @endphp

                        @if($currency_placement === 'after')
                        {{ $price_amount }} {{ $currency_symbol }}
                        @else
                        {{ $currency_symbol }} {{ $price_amount }}
                        @endif
                    </b>
                </span>
                @endif

                <!-- Expiry Date -->
                @if(!empty($print['exp_date']) && !empty($page_product->exp_date))
                <span style="font-size: {{$print['exp_date_size']}}px; margin-bottom: 4px;">
                    <b>@lang('product.exp_date'):</b> {{$page_product->exp_date}}
                </span>
                @endif

                <!-- Packing Date -->
                @if(!empty($print['packing_date']) && !empty($page_product->packing_date))
                <span style="font-size: {{$print['packing_date_size']}}px; margin-bottom: 4px;">
                    <b>@lang('lang_v1.packing_date'):</b> {{$page_product->packing_date}}
                </span>
                @endif

                <!-- Enhanced Image & Barcode Section -->
                <div style="display: flex; justify-content: center; align-items: center; margin-top: 2px;">
                    <!-- Small Image -->
                    @if (!empty($print['image']) && !empty($image_url))
                    <img src="{{ $image_url }}" alt="Label Image"
                        style="width: {{ $print['image_size'] }}px; height: {{ $print['image_size'] }}px; margin-bottom: 1px;">
                    @endif

                    <!-- Enhanced Barcode: Wide and high quality -->
                    @if(!empty($print['barcode']))
                    @php
                    // Simple inline calculations that work
                    $barcode_width_factor = 5; // Better quality than default 1
                    $barcode_height = max(40, ($print['barcode_size'] ?? 0.8) * 50);
                    @endphp
                    <img style="width: 85%; height: {{$barcode_height}}px; margin-bottom: 1px; image-rendering: crisp-edges;"
                        src="data:image/png;base64,{{DNS1D::getBarcodePNG($page_product->sub_sku, 'C128', $barcode_width_factor, $barcode_height, [0, 0, 0], false)}}">
                    @endif

                    <!-- Enhanced QR Code: High resolution -->
                    @if(!empty($print['qrcode']))
                    @php
                    // High resolution for clarity
                    $qr_size = max(120, ($print['qrcode_size'] ?? 1.4) * 80);
                    $qr_display_size = ($print['qrcode_size'] ?? 1.4) * 72;
                    @endphp
                    <img style="width: {{$qr_display_size}}px; height: {{$qr_display_size}}px; margin-top: 1px; margin-left: 4px; image-rendering: crisp-edges;"
                        src="data:image/png;base64,{{ DNS2D::getBarcodePNG($page_product->sub_sku, 'QRCODE', $qr_size, $qr_size) }}">
                    @endif
                </div>

                <!-- SKU -->
                @if(!empty($print['sku']))
                <span style="font-size: {{$print['sku_size']}}px; margin-top: 1px;">
                    <b>{{$page_product->sub_sku}}</b>
                </span>
                @endif
            </div>
        </td>
        @if($loop->iteration % $barcode_details->stickers_in_one_row == 0)
    </tr>
    @endif
    @endforeach
</table>

<style type="text/css">
    /* Enhanced CSS for Better Print Quality */
    img {
        image-rendering: crisp-edges;
        image-rendering: -moz-crisp-edges;
        image-rendering: -webkit-optimize-contrast;
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
        print-color-adjust: exact;
    }

    @media print {
        table {
            page-break-after: always;
        }

        @page {
            size: {{ $paper_width }}in {{ $paper_height }}in;
            margin: {{ $margin_top }}in {{ $margin_left }}in;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>