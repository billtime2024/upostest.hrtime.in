<tr class="product_row">
    @php
        $max_qty_rule = $product->qty_available;
        $formatted_max_quantity = $product->formatted_qty_available;
        $max_qty_msg = __('validation.custom-messages.quantity_not_available', ['qty'=> $formatted_max_quantity, 'unit' => $product->unit  ]);
        $allow_decimal = true;
    @endphp
    <td>
        {{$product->product_name}}
        <br/>
        {{$product->sub_sku}}

        @if(in_array($product->item_type, ['imei', 'serial']))
            {{-- Display existing IMEI/Serial numbers if available --}}
            @if(!empty($product->imei_numbers))
                <br><small>{{ $product->imei_numbers->pluck('identifier')->implode(', ') }}</small>
            @endif
            {{-- IMEI/Serial selection button below product name --}}
            <br>
            <button type="button" class="btn btn-xs btn-primary select-imei-serial-btn imei-btn" data-toggle="modal" data-target="#imei-serial-modal" data-row-index="{{$row_index}}" data-variation-id="{{$product->variation_id}}" data-item-type="{{$product->item_type}}" data-location-id="{{ request()->input('location_id') ?: '' }}">
                <i class="fa fa-list"></i> @lang('Select Imei | Serial')
            </button>
            <br>
            <small class="selected-imei-serial-count text-info" id="selected-count-{{$row_index}}">0 selected</small>
            <input type="hidden" name="products[{{$row_index}}][selected_imei_serial]" class="selected-imei-serial" value="">
        @endif

            @if( session()->get('business.enable_lot_number') == 1 || session()->get('business.enable_product_expiry') == 1)
            @php
                $lot_enabled = session()->get('business.enable_lot_number');
                $exp_enabled = session()->get('business.enable_product_expiry');
                $lot_no_line_id = '';
                if(!empty($product->lot_no_line_id)){
                    $lot_no_line_id = $product->lot_no_line_id;
                }
            @endphp
            @if($product->enable_stock == 1)
                <br>
                <small class="text-muted" style="white-space: nowrap;">@lang('report.current_stock'): <span class="qty_available_text">{{$product->formatted_qty_available}}</span> {{ $product->unit }}</small>
            @endif
            @if(!empty($product->lot_numbers))
                <select class="form-control lot_number lot-select" name="products[{{$row_index}}][lot_no_line_id]" style="color: #000 !improtant;">
                    <option value="">@lang('lang_v1.lot_n_expiry')</option>
                    @foreach($product->lot_numbers as $lot_number)
                        @php
                            $selected = "";
                            if($lot_number->purchase_line_id == $lot_no_line_id){
                                $selected = "selected";

                                $max_qty_rule = $lot_number->qty_available;
                                $max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', ['qty'=> $lot_number->qty_formated, 'unit' => $product->unit  ]);
                            }

                            $expiry_text = '';
                            if($exp_enabled == 1 && !empty($lot_number->exp_date)){
                                if( \Carbon::now()->gt(\Carbon::createFromFormat('Y-m-d', $lot_number->exp_date)) ){
                                    $expiry_text = '(' . __('report.expired') . ')';
                                }
                            }
                            $default_lot_number = isset($default_lot_number) ? $default_lot_number : $lot_number->lot_number;
                        @endphp
                        <option value="{{$lot_number->purchase_line_id}}" data-qty_available="{{$lot_number->qty_available}}" data-msg-max="@lang('lang_v1.quantity_error_msg_in_lot', ['qty'=> $lot_number->qty_formated, 'unit' => $product->unit  ])" {{ ($lot_number->lot_number == $default_lot_number) ? "selected" : ""}} >@if(!empty($lot_number->lot_number) && $lot_enabled == 1){{$lot_number->lot_number}} @endif @if($lot_enabled == 1 && $exp_enabled == 1) - @endif @if($exp_enabled == 1 && !empty($lot_number->exp_date)) @lang('product.exp_date'): {{@format_date($lot_number->exp_date)}} @endif {{$expiry_text}}</option>
                    @endforeach
                </select>
            @endif
        @endif
    </td>
    <td>
        @php
            if(empty($product->quantity_ordered)) {
                $product->quantity_ordered = 1;
            }
            $multiplier = 1;
            if($product->unit_allow_decimal != 1) {
                $allow_decimal = false;
            }

            $qty_ordered = $product->quantity_ordered;
        @endphp
        @foreach($sub_units as $key => $value)
            @if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key)
                @php
                    $multiplier = $value['multiplier'];
                    $max_qty_rule = $max_qty_rule / $multiplier;
                    $unit_name = $value['name'];
                    $max_qty_msg = __('validation.custom-messages.quantity_not_available', ['qty'=> $max_qty_rule, 'unit' => $unit_name  ]);

                    if(!empty($product->lot_no_line_id)){
                        $max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', ['qty'=> $max_qty_rule, 'unit' => $unit_name  ]);
                    }

                    if($value['allow_decimal']) {
                        $allow_decimal = true;
                    }
                @endphp
            @endif
        @endforeach
        @php
            $qty_ordered = $product->quantity_ordered / $multiplier;
        @endphp

        {{-- If edit then transaction sell lines will be present --}}
        @if(!empty($product->transaction_sell_lines_id))
            <input type="hidden" name="products[{{$row_index}}][transaction_sell_lines_id]" class="form-control" value="{{$product->transaction_sell_lines_id}}">
        @endif

        <input type="hidden" name="products[{{$row_index}}][product_id]" class="form-control product_id" value="{{$product->product_id}}">

        <input type="hidden" value="{{$product->variation_id}}"
            name="products[{{$row_index}}][variation_id]">

        <input type="hidden" value="{{$product->enable_stock}}"
            name="products[{{$row_index}}][enable_stock]">

        <input type="hidden" value="{{$product->item_type}}"
            name="products[{{$row_index}}][item_type]">

        @if(empty($product->quantity_ordered))
            @php
                $product->quantity_ordered = in_array($product->item_type, ['imei', 'serial']) ? 0 : 1;
            @endphp
        @endif

        @if(in_array($product->item_type, ['imei', 'serial']))
            {{-- For IMEI/Serial products, show quantity input that auto-adjusts --}}
            <input type="text" class="form-control product_quantity input_number input_quantity imei-serial-qty" value="{{ in_array($product->item_type, ['imei', 'serial']) ? 0 : @format_quantity($qty_ordered) }}" name="products[{{$row_index}}][quantity]" readonly data-rule-required="true" data-msg-required="@lang('validation.custom-messages.this_field_is_required')">
            <input type="hidden" name="products[{{$row_index}}][selected_imei_serial]" class="selected-imei-serial imei-selected" value="">
        @else
            {{-- Regular quantity input --}}
            <input type="text" class="form-control product_quantity input_number input_quantity" value="{{@format_quantity($qty_ordered)}}" name="products[{{$row_index}}][quantity]"
            @if($product->unit_allow_decimal == 1) data-decimal=1 @else data-rule-abs_digit="true" data-msg-abs_digit="@lang('lang_v1.decimal_value_not_allowed')" data-decimal=0 @endif
            data-rule-required="true" data-msg-required="@lang('validation.custom-messages.this_field_is_required')" @if($product->enable_stock) data-rule-max-value="{{$max_qty_rule}}" data-msg-max-value="{{$max_qty_msg}}"
            data-qty_available="{{$product->qty_available}}"
            data-msg_max_default="@lang('validation.custom-messages.quantity_not_available', ['qty'=> $product->formatted_qty_available, 'unit' => $product->unit  ])" @endif >
        @endif

        @php
            $last_purchase_price = isset($default_lot_number) ? \App\PurchaseLine::where('lot_number', $default_lot_number)->get()[0]["purchase_price_inc_tax"] : $product->last_purchased_price ;
        @endphp
        <input type="hidden" class="base_unit_multiplier" name="products[{{$row_index}}][base_unit_multiplier]" value="{{$multiplier}}">

         <input type="hidden" class="hidden_base_unit_price" value="{{$last_purchase_price}}">

        <input type="hidden" name="products[{{$row_index}}][product_unit_id]" value="{{$product->unit_id}}">
        @if(!empty($sub_units))
            <br>
            <select name="products[{{$row_index}}][sub_unit_id]" class="form-control input-sm sub_unit">
                @foreach($sub_units as $key => $value)
                    <option value="{{$key}}" data-multiplier="{{$value['multiplier']}}" data-unit_name="{{$value['name']}}" data-allow_decimal="{{$value['allow_decimal']}}" @if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key) selected @endif>
                        {{$value['name']}}
                    </option>
                @endforeach
            </select>
        @else
            {{$product->unit}}
        @endif
    </td>
    <td class="show_price_with_permission">
        <input type="text" name="products[{{$row_index}}][unit_price]" class="form-control product_unit_price input_number " value="{{@num_format($last_purchase_price * $multiplier)}}">
    </td>
    <td class="show_price_with_permission">
        <input type="text" readonly name="products[{{$row_index}}][price]" class="form-control product_line_total " value="{{@num_format($product->quantity_ordered*$last_purchase_price)}}">
    </td>
    <td class="text-center">
        <i class="fa fa-trash remove_product_row cursor-pointer" aria-hidden="true"></i>
    </td>
</tr>