<style>
.table-bordered, .table-bordered > tbody > tr > td {
    border:0.05px solid #545775;
   
}
.table-bordered > tbody > tr > td{
     padding: 0.35rem;
}
h4 {
    margin: 0;
}
.w-full {
    width: 100%;
}
.w-half {
    width: 50%;
}
.margin-top {
    margin-top: 1.25rem;
}
.footer {
    font-size: 0.875rem;
    padding: 1rem;
    background-color: rgb(241 245 249);
}
table {
    width: 100%;
    border-spacing: 0;
}
table.products {
    font-size: 0.875rem;
}
table.products tr {
    background-color: rgb(96 165 250);
}
table.products th {
    color: #ffffff;
    padding: 0.5rem;
}
table tr.items {
    background-color: rgb(241 245 249);
}
table tr.items td {
    padding: 0.5rem;
}
.total {
    text-align: right;
    margin-top: 1rem;
    font-size: 0.875rem;
}
.text-right {
    text-align: right;
}
.text-left {
    text-align:  left;
}
.text-center {
    text-align: center;
}
.word-wrap {
    word-wrap: break-word;
}
</style>
<div class="break">
<table style="width:100%;">
	<div class="print_html" style="width: 100%;">
	<div class="inner_html">
		<div class="row inner_html_row" style="color: #000000 !important;">
			<div class="header">
				<div class="col-xs-6" style="display: block; position: relative; font-weight: 700;  ">
					<p style="width: 50% !important;margin: 0; display: inline-block;" class="word-wrap text-right">
						<span class="text-right word-wrap">
						    	{!! $receipt_details->invoice_heading !!}

						</span>
					</p>
					<p style="width: 50% !important;margin: 0; position: absolute; right: 0; top: -25px;" class="word-wrap">
						<span class="text-right word-wrap" style="text-align: right !important; display: block;">
						   				DUPLICATE FOR RECIPIENT			
						</span>
					</p>
					{{-- @if(isset($pdf_warnning))
					<p style="width: 50% !important;margin: 0; position: absolute; right: 0; top: -10vh;" class="word-wrap">
						<span class="text-right word-wrap" style="text-align: right !important; display: block;">
						   	{!! $pdf_warnning !!}							
						</span>
					</p>
                    @endif --}}
				</div>
			
				<div class="col-xs-12" style="display: flex">
					<p style="width: 60% !important;margin: 0;" class="word-wrap">
						<span class="text-left" style="font-size: 21px;font-weight: 700;">
							<!-- Shop & Location Name  -->
							@if(!empty($receipt_details->display_name))
								{{$receipt_details->display_name}}
							@endif
						</span>

						<span>
							@if(!empty($receipt_details->address))
							    <br />
							<!--<small class="text-left">-->
								{!! $receipt_details->address !!}
							<!--</small>-->
							@endif
							@if(!empty($receipt_details->contact))
							    <br />{!! $receipt_details->contact !!} ,
							@endif
							@if(!empty($receipt_details->contact) && !empty($receipt_details->website))
							@endif
							@if(!empty($receipt_details->website))
								{{ $receipt_details->website }}
							@endif
							@if(!empty($receipt_details->tax_info1))
								<br />GST : {!! $receipt_details->tax_info1 !!}
							@endif
							@if(!empty($receipt_details->tax_info2))
								<br />GST : {!! $receipt_details->tax_info2 !!}
							@endif							
							@if(!empty($receipt_details->location_custom_fields))
								<br />{!! $receipt_details->location_custom_fields !!}
							@endif
						</span>
					</p>
			
					<p style="width: 40% !important;margin: 0; margin-top: -150px; float: right;" class="word-wrap">
						@if(!empty($receipt_details->logo))
							<img style="max-height: 120px; width: auto;" src="{{$receipt_details->logo}}" class="img img-responsive center-block">
						@endif
					</p>
				</div>
				<div class="col-xs-12 text-left" style="display: flex; ">
					<!-- Invoice  number, Date  -->
					<p style="margin: 0; float: left;width: 33.33%;" class="word-wrap">
						<span class="pull-left text-left word-wrap">
					    <br />
							@if(!empty($receipt_details->invoice_no_prefix))
							<span>{!! $receipt_details->invoice_no_prefix !!}</span>
							@endif
							<b>{{$receipt_details->invoice_no}}</b>
						</span>
					</p>
			
					<p style="margin: 0;  float: left;width: 33.33%;" class="word-wrap">
						<span class="pull-left text-left">
					    <br />
							<span>{{$receipt_details->date_label}}</span> <b>{{$receipt_details->invoice_date}}</b>
							<br />		
							<br />							
							@if(!empty($receipt_details->due_date_label))
								<span> {{$receipt_details->due_date_label}}</span> {{$receipt_details->due_date ?? ''}}
							@endif
							
							</span>
							</p>
							<!-- Waiter info -->
					<p style="margin:0;  float: left;width: 33.33%;" class="word-wrap">
						<span class="pull-left text-left">

					    <br />							
							@if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
							<!--<br />-->
							@if(!empty($receipt_details->service_staff_label))
							<b>{!! $receipt_details->service_staff_label !!}</b>
							@endif
							{{$receipt_details->service_staff}}
							@endif
							
			
							<!--@if(!empty($receipt_details->brand_label) || !empty($receipt_details->repair_brand))-->
							
							<!--@if(!empty($receipt_details->brand_label))-->
							<!--	<span>{!! $receipt_details->brand_label !!}</span>-->
							<!--@endif-->
							<!--	{{$receipt_details->repair_brand}}-->
							<!--@endif-->
			
			
							<!--@if(!empty($receipt_details->device_label) || !empty($receipt_details->repair_device))-->
							<!--<br>-->
							<!--@if(!empty($receipt_details->device_label))-->
							<!--<b>{!! $receipt_details->device_label !!}</b>-->
							<!--@endif-->
							<!--{{$receipt_details->repair_device}}-->
							<!--@endif-->
			
							<!--@if(!empty($receipt_details->model_no_label) || !empty($receipt_details->repair_model_no))-->
							<!--<br>-->
							<!--@if(!empty($receipt_details->model_no_label))-->
							<!--<b>{!! $receipt_details->model_no_label !!}</b>-->
							<!--@endif-->
							<!--{{$receipt_details->repair_model_no}}-->
							<!--@endif-->
			
							<!--@if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))-->
							<!--<br>-->
							<!--@if(!empty($receipt_details->serial_no_label))-->
							<!--<b>{!! $receipt_details->serial_no_label !!}</b>-->
							<!--@endif-->
							<!--{{$receipt_details->repair_serial_no}}<br>-->
							<!--@endif-->
							<!--@if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))-->
							<!--@if(!empty($receipt_details->repair_status_label))-->
							<!--<b>{!! $receipt_details->repair_status_label !!}</b>-->
							<!--@endif-->
							<!--{{$receipt_details->repair_status}}<br>-->
							<!--@endif-->
			
							<!--@if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))-->
							<!--@if(!empty($receipt_details->repair_warranty_label))-->
							<!--<b>{!! $receipt_details->repair_warranty_label !!}</b>-->
							<!--@endif-->
							<!--{{$receipt_details->repair_warranty}}-->
							<!--<br>-->
							<!--@endif-->
			
			
			
							{{-- sale order --}}
							@if(!empty($receipt_details->sale_orders_invoice_no))
							<br>
							<strong>@lang('restaurant.order_no'):</strong> {!!$receipt_details->sale_orders_invoice_no ?? ''!!}
							@endif
			
							@if(!empty($receipt_details->sale_orders_invoice_date))
							<br>
							<strong>@lang('lang_v1.order_dates'):</strong> {!!$receipt_details->sale_orders_invoice_date ?? ''!!}
							@endif
			
							@if(!empty($receipt_details->sell_custom_field_1_value))
							<br>
							<strong>{{ $receipt_details->sell_custom_field_1_label }}:</strong>
							{!!$receipt_details->sell_custom_field_1_value ?? ''!!}
							@endif
			
							@if(!empty($receipt_details->sell_custom_field_2_value))
							<br>
							<strong>{{ $receipt_details->sell_custom_field_2_label }}:</strong>
							{!!$receipt_details->sell_custom_field_2_value ?? ''!!}
							@endif
			
							@if(!empty($receipt_details->sell_custom_field_3_value))
							<br>
							<strong>{{ $receipt_details->sell_custom_field_3_label }}:</strong>
							{!!$receipt_details->sell_custom_field_3_value ?? ''!!}
							@endif
			
							@if(!empty($receipt_details->sell_custom_field_4_value))
							<br>
							<strong>{{ $receipt_details->sell_custom_field_4_label }}:</strong>
							{!!$receipt_details->sell_custom_field_4_value ?? ''!!}
							@endif
						</span>
					</p>
					<!--<p style="width: 35% !important;margin: 0;" class="word-wrap"></p>-->
				</div>
				<div class="col-xs-12 text-left" style="display: flex; margin-top: 50px; margin-bottom: 100px;">
					<!-- Head  -->
					<p style="margin:0; margin-left: -240px; width:  33.33%; float: left; " class="word-wrap">
						<span class="pull-left text-left">
							<!-- customer info -->
							@if(!empty($receipt_details->customer_info))
							<b>{{ $receipt_details->customer_label }} : </b>
							@endif
						</span>
						<br />
						<span>
							{{ $receipt_details->customer_name }}<br />
							{{ $receipt_details->customer_mobile }}
						</span>
						@if(!empty($receipt_details->customer_tax_number))
							<br/>
							<span>{{ $receipt_details->customer_tax_label }}</span> {{ $receipt_details->customer_tax_number }}
						@endif
					</p>
					<p style="margin: 0; width:  33.33%; float: left;" class="word-wrap">
						<span class="pull-left text-left">
							<b>Billing Address : </b>
						</span>
						<!--<br />-->
						@if (!empty($receipt_details->billing_address))
						Billing
						{!! $receipt_details->billing_address !!}
						@else
						{!! $receipt_details->customer_info_address !!}
						{{-- {!! $receipt_details->customer_info_address !!} --}}
						@endif
			
					</p>
					<p style="margin: 0; width:  33.33%; float: left;" class="word-wrap">
					    @if(!empty($receipt_details->shipping_address))
						<span class="pull-left text-left">
							<b>Shipping Address :</b>
						</span>
						@endif
						<br />
						@if(!empty($receipt_details->shipping_address))
						{!! $receipt_details->shipping_address !!}
						@endif
					</p>
				</div>
			</div>


			@includeIf('sale_pos.receipts.partial.common_repair_invoice')



<div class="row ">
	<div class="col-xs-12">
		<br/>
		<table class="table table-bordered table-no-top-cell-border table-slim">
			<thead>
				<tr style="background-color: #545775 !important; color: white !important; font-size: 15px !important;" class="table-no-side-cell-border table-no-top-cell-border text-center">
					<td style="background-color: #545775 !important; color: white !important;width: 3% !important">#</td>
					
					@php
						$p_width = 30;
					@endphp
					@if($receipt_details->show_cat_code != 1)
						@php
							$p_width = 40;
						@endphp
					@endif
					<td style="background-color: #545775 !important; color: white !important; width: {{$p_width}}% !important">
						{{$receipt_details->table_product_label}}
					</td>

					@if($receipt_details->show_cat_code == 1)
						    <td style="background-color: #545775 !important; color: white !important; width: 8% !important;">{{$receipt_details->cat_code_label}}</td>
					@endif
					
					<td style="background-color: #545775 !important; color: white !important;width: 10% !important;">
						{{$receipt_details->table_qty_label}}
					</td>
					<td style="background-color: #545775 !important; color: white !important;width: 10% !important;">
						{{$receipt_details->table_unit_price_label}}  (@lang('product.exc_of_tax'))
					</td>
					<td style="background-color: #545775 !important; color: white !important;width: 5% !important;">
						{{$receipt_details->line_discount_label}}
					</td>
					<td style="background-color: #545775 !important; color: white !important;width: 13% !important;">
						{{$receipt_details->line_tax_label}}
					</td>
					<td style="background-color: #545775 !important; color: white !important;width: 10% !important;">
						{{$receipt_details->table_unit_price_label}} (@lang('product.inc_of_tax'))
					</td>
					<td style="background-color: #545775 !important; color: white !important;width: 13% !important;">
						{{$receipt_details->table_subtotal_label}}
					</td>
				</tr>
			</thead>
			<tbody>
				@foreach($receipt_details->lines as $line)
					@php
                    			    $variation = \App\Variation::find($line["variation_id"]);
                    			    $combo_variations = $variation["combo_variations"];
                    			    $new_discription = "";
                    			    if(!empty($combo_variations)){
                    			        foreach($combo_variations as $single_variation_combo) {
                    			            $single_variation_combo_product_name = \App\Product::find(\App\Variation::find((int) $single_variation_combo["variation_id"])["product_id"])["name"];
                    			            $unit = \App\Unit::find((int) $single_variation_combo["unit_id"]);
                    			            $new_discription .= $single_variation_combo_product_name . " - " . $single_variation_combo["quantity"] . " " . $unit["short_name"] . "<br />"  ;
                    			        }
                    			    }
                    			@endphp
					<tr >
						<td class="text-center">
							{{$loop->iteration}}
						</td>
						<td>
							@if(!empty($line['image']))
								<img src="{{$line['image']}}" alt="Image" width="50" style="float: left; margin-right: 8px;">
							@endif
                            {{$line['name']}} {{$line['product_variation']}} {{$line['variation']}} 
                            @if(!empty($line['sub_sku'])), {{$line['sub_sku']}} @endif @if(!empty($line['brand'])), {{$line['brand']}} @endif
                           
                            
                        @if(!empty($line['product_description']))
                        	<small>
                        		{!! $new_discription . "\n" . $line['product_description']!!}
                        	</small>
                        @elseif(!empty($new_discription))
                            <small>{!! $new_discription !!}</small>
                        @endif
                            @if(!empty($line['sell_line_note']))
                            	<br>
                             <small class="text-muted">{!!$line['sell_line_note']!!}</small>
                             @endif
                            @if(!empty($line['lot_number']))<br> {{$line['lot_number_label']}}:  {{$line['lot_number']}} @endif 
                            @if(!empty($line['product_expiry'])), {{$line['product_expiry_label']}}:  {{$line['product_expiry']}} @endif 

                            @if(!empty($line['warranty_name'])) <br><small>{{$line['warranty_name']}} </small>@endif @if(!empty($line['warranty_exp_date'])) <small>- {{@format_date($line['warranty_exp_date'])}} </small>@endif
                            @if(!empty($line['warranty_description'])) <small> {{$line['warranty_description'] ?? ''}}</small>@endif

                            @if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                            <br><small>
                            	1 {{$line['units']}} = {{$line['base_unit_multiplier']}} {{$line['base_unit_name']}} <br>
                            	{{$line['base_unit_price']}} x {{$line['orig_quantity']}} = {{$line['line_total']}}
                            </small>
                            @endif
                        </td>
						@if($receipt_details->show_cat_code == 1)
	                        <td>
	                        	@if(!empty($line['cat_code']))
	                        		{{$line['cat_code']}}
	                        	@elseif (isset($line['product_custom_fields']))
	                        	     {{$line['product_custom_fields']}}
	                        	@else
	                        	    @php
	                        	    $product_custom_fiend4 = \App\Product::find(\App\Variation::find($line["variation_id"])['product_id'])['product_custom_field4'];
	                        	    @endphp
	                        	    {{ $product_custom_fiend4 }}
	                        	@endif
	                        </td>
	                    @endif

						<td class="text-right">
							{{$line['quantity']}} {{$line['units']}}

							@if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                            <br><small>
                            	{{$line['quantity']}} x {{$line['base_unit_multiplier']}} = {{$line['orig_quantity']}} {{$line['base_unit_name']}}
                            </small>
                            @endif
						</td>
						<td class="text-right">
							{{$line['unit_price_before_discount']}}
						</td>
						<td class="text-right">
							{{$line['total_line_discount'] ?? 0}}
							@if(!empty($line['line_discount_percent']))
							 	({{$line['line_discount_percent']}}%)
							@endif
						</td>
						<td class="text-right">
							{{$line['tax']}} 
							<br><small>
							{{$line['tax_name']}}
							</small>
						</td>
						<td class="text-right">
							{{$line['unit_price_inc_tax']}}
						</td>
						<td class="text-right">
							{{$line['line_total']}}
						</td>
					</tr>
					@if(!empty($line['modifiers']))
						@foreach($line['modifiers'] as $modifier)
							<tr>
								<td class="text-center">
									&nbsp;
								</td>
								<td>
		                            {{$modifier['name']}} {{$modifier['variation']}} 
		                            @if(!empty($modifier['sub_sku'])), {{$modifier['sub_sku']}} @endif 
		                            @if(!empty($modifier['sell_line_note']))({!!$modifier['sell_line_note']!!}) @endif 
		                        </td>

								@if($receipt_details->show_cat_code == 1)
			                        <td>
			                        	@if(!empty($modifier['cat_code']))
			                        		{{$modifier['cat_code']}}
			                        	@endif
			                        </td>
			                    @endif

								<td class="text-right">
									{{$modifier['quantity']}} {{$modifier['units']}}
								</td>
								<td class="text-right">
									&nbsp;
								</td>
								<td class="text-center">
									&nbsp;
								</td>
								<td class="text-center">
									&nbsp;
								</td>
								<td class="text-center">
									&nbsp;
								</td>
								<td class="text-center">
									{{$modifier['unit_price_exc_tax']}}
								</td>
								<td class="text-right">
									{{$modifier['line_total']}}
								</td>
							</tr>
						@endforeach
					@endif
				@endforeach

				@php
					$lines = count($receipt_details->lines);
				@endphp

				@for ($i = $lines; $i < 5; $i++)
    				<tr>
    					<td>&nbsp;</td>
    					<td>&nbsp;</td>
    					<td>&nbsp;</td>
    					<td>&nbsp;</td>
    					<td>&nbsp;</td>
    					<td>&nbsp;</td>
    					<td>&nbsp;</td>
    					<td>&nbsp;</td>
    					<td>&nbsp;</td>
    					@if($receipt_details->show_cat_code == 1)
    						<td>&nbsp;</td>
    					@endif
    				</tr>
				@endfor

			</tbody>
		</table>
			<div class="row invoice-info ">
	<div class="col-md-6 invoice-col width-60" style="width; 60%; float: left;">
		<table class="table table-slim">
			@if(!empty($receipt_details->payments))
				@foreach($receipt_details->payments as $payment)
					<tr>
						<td style="width: 33%; float: left; padding: 0.25rem;">{{$payment['method']}}</td>
						<td style="width: 33%; float: left; padding: 0.25rem;">{{join("Rs", explode("₹", $payment['amount']))}}</td>
						<td style="width: 33%; float: left; padding: 0.25rem;">{{$payment['date']}}</td>
					</tr>
				@endforeach
			@endif				
				@if (!empty($receipt_details->total_due) && !empty($receipt_details->total_due_label))
				    <tr>
    				    <td style="font-weight: bold;">{!! $receipt_details->total_due_label !!}</td>
            	        <td style="font-weight: bold;">{{ $receipt_details->total_due }}</td>
        		        <td></td>
		            </tr>
				@endif
		</table>
	</div>
	    
	<div class="col-md-6 invoice-col width-40" style="width: 40%; float: right;">
		<table class="table-no-side-cell-border table-no-top-cell-border width-100 table-slim" style="width: 100%;">
			<tbody>
				@if(!empty($receipt_details->total_quantity_label))
					<tr >
						<td style="width:50%">
							{!! $receipt_details->total_quantity_label !!}
						</td>
						<td class="text-right">
							{{$receipt_details->total_quantity}}
						</td>
					</tr>
				@endif

				@if(!empty($receipt_details->total_items_label))
					<tr >
						<td style="width:50%">
							{!! $receipt_details->total_items_label !!}
						</td>
						<td class="text-right">
							{{$receipt_details->total_items}}
						</td>
					</tr>
				@endif
				<tr >
					<td style="width:50%">
						{!! $receipt_details->subtotal_label !!}
					</td>
					<td class="text-right">
						<!--&#8377;--> Rs. {{$receipt_details->subtotal_unformatted}}
					</td>
				</tr>
				
				<!-- Shipping Charges -->
				@if(!empty($receipt_details->shipping_charges))
					<tr >
						<td style="width:50%">
							{!! $receipt_details->shipping_charges_label !!}
						</td>
						<td class="text-right">
							{{$receipt_details->shipping_charges}}
						</td>
					</tr>
				@endif

				@if(!empty($receipt_details->packing_charge))
					<tr >
						<td style="width:50%">
							{!! $receipt_details->packing_charge_label !!}
						</td>
						<td class="text-right">
							{{$receipt_details->packing_charge}}
						</td>
					</tr>
				@endif

				<!-- Discount -->
				@if( !empty($receipt_details->discount) )
					<tr >
						<td>
							{!! $receipt_details->discount_label !!}
						</td>

						<td class="text-right">
							(-) {{$receipt_details->discount}}
						</td>
					</tr>
				@endif

				@if( !empty($receipt_details->total_line_discount) )
					<tr >
						<td>
							{!! $receipt_details->line_discount_label !!}
						</td>

						<td class="text-right">
							(-) {{$receipt_details->total_line_discount}}
						</td>
					</tr>
				@endif

				@if( !empty($receipt_details->additional_expenses) )
					@foreach($receipt_details->additional_expenses as $key => $val)
						<tr >
							<td>
								{{$key}}:
							</td>

							<td class="text-right">
								(+) {{$val}}
							</td>
						</tr>
					@endforeach
				@endif

				@if( !empty($receipt_details->reward_point_label) )
					<tr >
						<td>
							{!! $receipt_details->reward_point_label !!}
						</td>

						<td class="text-right">
							(-) {{$receipt_details->reward_point_amount}}
						</td>
					</tr>
				@endif

				@if(!empty($receipt_details->group_tax_details))
					@foreach($receipt_details->group_tax_details as $key => $value)
						<tr >
							<td>
								{!! $key !!}
							</td>
							<td class="text-right">
								(+) {{ $value }}
							</td>
						</tr>
					@endforeach
				@else
					@if( !empty($receipt_details->tax) )
						<tr >
							<td>
								{!! $receipt_details->tax_label !!}
							</td>
							<td class="text-right">
								(+) {{$receipt_details->tax}}
							</td>
						</tr>
					@endif
				@endif

				@if( $receipt_details->round_off_amount > 0)
					<tr >
						<td>
							{!! $receipt_details->round_off_label !!}
						</td>
						<td class="text-right">
							Rs. {{explode(" ", $receipt_details->round_off)[1]}}
						</td>
					</tr>
				@endif
				
				<!-- Total -->
				<tr>
					<th style="background-color: #545775 !important; color: white !important; padding: 10px; font-size: 20px; font-weight: bold;" class="font-23 padding-10">
						{!! $receipt_details->total_label !!}
					</th>
					<td class="text-right font-23 padding-10" style="background-color: #545775 !important; color: white !important; padding: 10px; font-size: 20px; font-weight: bold;">
					    <!--&#8377;--> Rs. {{number_format($receipt_details->total_uf, 2, ".", "")}}
					</td>
				</tr>
				@if(!empty($receipt_details->total_in_words))
				<tr>
					<td colspan="2" class="text-right">
						<small>({{$receipt_details->total_in_words}})</small>
					</td>
				</tr>
				@endif
			</tbody>
        </table>
	</div>
</div>
<div class="col-md-6 width-50"  style="width: 50%; margin-top :  {{ 40  * count($receipt_details->payments) }}px; ">
    @if(empty($receipt_details->hide_price) && !empty($receipt_details->tax_summary_label) )
        <!-- tax -->
        @if(!empty($receipt_details->taxes))
        	<table class="table table-slim table-bordered tax">
        		<tr>
        			<th colspan="2" class="text-center">{{$receipt_details->tax_summary_label}}</th>
        		</tr>
        		@foreach($receipt_details->taxes as $key => $val)
        			<tr>
        				<td class="text-left"><b>{{$key}}</b></td>
        				<td class="text-left">Rs. {{explode(" ", $val)[1]}}</td>
        			</tr>
        		@endforeach
        	</table>
        @endif
    @endif
</div>
			<br>
@if(!empty($receipt_details->additional_notes))
	<div class="row ">
		<div class="col-xs-12">
			<br>
			<p>{!! nl2br($receipt_details->additional_notes) !!}</p>
		</div>
	</div>
@endif



<div class="row" style="color: #000000 !important;">
	@if(!empty($receipt_details->footer_text))
	<div class="@if($receipt_details->show_barcode || $receipt_details->show_qr_code) col-xs-8 @else col-xs-12 @endif">
		{!! $receipt_details->footer_text !!}
	</div>
	@endif
	@if($receipt_details->show_barcode || $receipt_details->show_qr_code)
		<div class="@if(!empty($receipt_details->footer_text)) col-xs-4 @else col-xs-12 @endif text-center">
			@if($receipt_details->show_barcode)
				{{-- Barcode --}}
				<img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
			@endif
			
			@if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
				<img class="center-block mt-5" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE', 3, 3, [39, 48, 54])}}">
			@endif
		</div>
	@endif
</div>
<div class="row inner_html_row" style="color: #000000 !important;">
			<div class="header">
				<div class="col-xs-12" style="display: flex">
					<p style="width: 100% !important;margin: 0;font-weight: 1000;" class="word-wrap text-right">
						<span class="text-right word-wrap">
							For {{$receipt_details->display_name}}
						</span>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
</div>