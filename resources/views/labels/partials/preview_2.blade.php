
<table align="center" style="border-spacing: {{$barcode_details->col_distance * 1}}in {{$barcode_details->row_distance * 1}}in; overflow: hidden !important;">
@foreach($page_products as $page_product)

	@if($loop->index % $barcode_details->stickers_in_one_row == 0)
		<!-- create a new row -->
		<tr>
		<!-- <columns column-count="{{$barcode_details->stickers_in_one_row}}" column-gap="{{$barcode_details->col_distance*1}}"> -->
	@endif
		<td align="center" valign="center">
			<div style="overflow: hidden !important;align-content: center;width: {{$barcode_details->width * 1}}in; height: {{$barcode_details->height * 1}}in; justify-content: center;">
				
					{{-- Business Name --}}
					@if(!empty($print['business_name']))
						<b style="display: block !important; font-size: {{$print['business_name_size']}}px">{{$business_name}}</b>
					@endif
				<div>
					{{-- Barcode --}}
                    <div style="display: flex; justify-content: space-around; align-items: center;">
<div>
    	@if(!empty($print['lot_number']) && !empty($page_product->lot_number))
    		<img style="width: 100%; max-width:100% !important;height: {{$barcode_details->height*0.34}}in !important; display: block;" src="data:image/png;base64,{{DNS1D::getBarcodePNG($page_product->lot_number, $page_product->barcode_type, 1,30, array(0, 0, 0), false)}}">
    	@else
					<img style="width: 90%; max-width:100% !important;height: {{$barcode_details->height*0.34}}in !important; display: block;" src="data:image/png;base64,{{DNS1D::getBarcodePNG($page_product->sub_sku, $page_product->barcode_type, 1,30, array(0, 0, 0), false)}}">
					@endif
					<span style="font-size: 10px !important">
</b>
							@if(!empty($print['lot_number']) && !empty($page_product->lot_number))
								<span style="font-size: {{12*$factor}}px">
									 <b>{{$page_product->lot_number}}({{$page_product->sub_sku}})</b>
								</span>
                            @else
						    <b>{{$page_product->sub_sku}} </b>
							@endif
					</span>
</div>
					@php
						$custom_labels = json_decode(session('business.custom_labels'), true);
						$product_custom_fields = !empty($custom_labels['product']) ? $custom_labels['product'] : [];
                    @endphp
					@foreach($product_custom_fields as $index => $cf)
						@php
							$field_name = 'product_custom_field' . $loop->iteration;
						@endphp
						@if(!empty($cf) && !empty($page_product->$field_name ) && !empty($print[$field_name]))
                            <div style=" display: flex; justify-content: center; align-items: center; flex-direction: column; font-size: {{ $print[$field_name . '_size'] }}px; ">
								<!--<span>{{ $cf }}</span>-->
								<span><b>{{ $page_product->$field_name }}</b></span>
							</div>
						@endif
					@endforeach
                    </div>

					<!--{{-- Business Name --}}
					@if(!empty($print['business_name']))
						<b style="display: block !important; font-size: {{$print['business_name_size']}}px">{{$business_name}}</b>
					@endif-->

					{{-- Product Name --}}
					@if(!empty($print['name']))
						<span style="display: block !important; font-size: {{$print['name_size']}}px">
						<b>	{{$page_product->product_actual_name}}</b>

					    @if(!empty($print['variations']) && $page_product->is_dummy != 1)
					    		<!--{{$page_product->product_variation_name}}:--><b>({{$page_product->variation_name}}) 
						@if(!empty($print['lot_number']) && !empty($page_product->lot_number))
                                - {{$page_product->sub_sku}}
                        @endif
 </b>
                        @endif
							<!--@if(!empty($print['lot_number']) && !empty($page_product->lot_number))
								<span style="font-size: {{12*$factor}}px">
									 ({{$page_product->lot_number}})
								</span>
							@endif-->
						</span>

					@endif

                    <!--{{-- Variation --}}
					@if(!empty($print['variations']) && $page_product->is_dummy != 1)
						<span style="display: block !important; font-size: {{$print['variations_size']}}px">
							{{$page_product->product_variation_name}}:<b>{{$page_product->variation_name}}</b>
						</span>
					@endif-->
					<!--{{-- product_custom_fields --}}
					@php
						$custom_labels = json_decode(session('business.custom_labels'), true);
						$product_custom_fields = !empty($custom_labels['product']) ? $custom_labels['product'] : [];
                    @endphp
                        -->

					<!--@foreach($product_custom_fields as $index => $cf)
						@php
							$field_name = 'product_custom_field' . $loop->iteration;
						@endphp
						@if(!empty($cf) && !empty($page_product->$field_name ) && !empty($print[$field_name]))
							<span style="font-size: {{ $print[$field_name . '_size'] }}px">
								<b>{{ $cf }}:</b>
								{{ $page_product->$field_name }}
							</span>
						@endif
					@endforeach-->
					<!--<br>-->

					{{-- Price --}}
					@if(!empty($print['price']))
					<span style="font-size: {{$print['price_size']}}px;">
						<!--@lang('lang_v1.price'):-->
						{{session('currency')['symbol'] ?? ''}}

						
						@if($print['price_type'] == 'inclusive')
							<b>{{@num_format($page_product->sell_price_inc_tax)}}</b>
						@else
							<b>{{@num_format($page_product->default_sell_price)}}</b>
						@endif
					</span>
					@endif
					@if(!empty($print['exp_date']) && !empty($page_product->exp_date))
						<br>
						<span style="font-size: {{$print['exp_date_size']}}px">
							<!--<b>@lang('product.exp_date'):</b>-->
							<b>{{$page_product->exp_date}}</b>
						</span>
						@if($barcode_details->is_continuous)
						<br>
						@endif
					@endif
                        <br>
					@if(!empty($print['packing_date']) && !empty($page_product->packing_date))
						<span style="font-size: {{$print['packing_date_size']}}px">
							<!--<b>@lang('lang_v1.packing_date'):</b>-->
							<b>{{$page_product->packing_date}}</b>
						</span>
					@endif
				</div>
			</div>
		
		</td>

	@if($loop->iteration % $barcode_details->stickers_in_one_row == 0)
		</tr>
	@endif
@endforeach
</table>

<style type="text/css">

	/*td{*/
	/*	border: 1px dotted lightgray;*/
	/*	padding: 1rem;*/
	/*}*/
	@media print{
		
		table{
			page-break-after: always;
		}

		
		@page {
            size: {{$paper_width}}in {{$paper_height}}in;
		    width: {{$barcode_details->paper_width}}in !important;
		    height:@if($barcode_details->paper_height != 0){{$barcode_details->paper_height + 0.2}}in !important @else auto @endif;
            /*margin-top: {{$margin_top}}in !important;*/
            /*margin-bottom: {{$margin_top}}in !important; */
            /*margin-left: {{$margin_left}}in !important; */
		    /*margin-right: {{$margin_left}}in !important;*/
			margin: none !important;
	    }
	}
</style>
