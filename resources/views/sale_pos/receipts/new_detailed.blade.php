@php
// dd($receipt_details);
$total_quantity = 0;
$business_id = auth()->user()->business_id;
$currency = \App\Business::find($business_id)->currency;

if(str_contains($receipt_details->total_line_discount,$currency["symbol"])){
	$total_calculated_discount = 0;
	if(strlen($currency["symbol"]) <= 0){
 		$total_calculated_discount = (float) explode($currency["symbol"], str_replace(",", "", $receipt_details->total_line_discount))[1];
	} else {
		 $total_calculated_discount = (float)str_replace(",", "", $receipt_details->total_line_discount);
	}


} else {
	 $total_calculated_discount =  str_replace(",", "", $receipt_details->total_line_discount);
}
if(gettype($receipt_details->discount) == "string") {
	if(str_contains($receipt_details->total_line_discount,$currency["symbol"])){
		 $total_calculated_discount = (float) explode($currency["symbol"], str_replace(",", "", $receipt_details->total_line_discount))[1] +   (float) explode($currency["symbol"], str_replace(",", "", $receipt_details->discount))[1];
	} else {
		 $total_calculated_discount = (float) str_replace(",", "", $receipt_details->total_line_discount) +   (float) str_replace(",", "", $receipt_details->discount);
	}
   
}
@endphp
<div class="print_html" style="width: 100%;">

	<div class="inner_html">


		<div class="row inner_html_row" style="color: #000000 !important;">

			<div class="header">
				<div class="col-xs-12" style="display: flex">
					<p style="width: 55% !important;margin: 0;font-weight: 700; font-size: 1.25rem;" class="word-wrap text-right">
						<span class="text-right word-wrap">
							<!--TAX INVOICE-->
						{!! $receipt_details->invoice_heading !!}</p>														
						</span>
					</p>
					<p style="width: 45% !important;margin: 0;" class="word-wrap">
						<span class="pull-right text-right word-wrap">
							ORIGINAL FOR RECIPIENT
						</span>
					</p>
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
							@if(!empty($receipt_details->tax_info1))
								<br />GST : {!! $receipt_details->tax_info1 !!}
							@endif								
							@if(!empty($receipt_details->tax_info2))
								<br />GST : {!! $receipt_details->tax_info2 !!}
							@endif													    
							
							@if(!empty($receipt_details->address))
							<br />
							<small class="text-left">
								{!! $receipt_details->address !!}
							</small>
							@endif
							@if(!empty($receipt_details->contact))
							<br />{!! $receipt_details->contact !!}
							@endif
							@if(!empty($receipt_details->contact) && !empty($receipt_details->website))
							<br>
							@endif
							@if(!empty($receipt_details->website))
								{{ $receipt_details->website }}
							@endif
							@if(!empty($receipt_details->location_custom_fields))
								{{ $receipt_details->location_custom_fields }}
							@endif

							
						</span>
					</p>
			
					<p style="width: 40% !important;margin: 0;" class="word-wrap">
						@if(!empty($receipt_details->logo))
							<img style="max-height: 120px; width: auto;" src="{{$receipt_details->logo}}" class="img img-responsive center-block">
						@endif
					</p>
				</div>
				<div class="col-xs-12 text-left" style="display: flex">
			
					<!-- Invoice  number, Date  -->
					<p style="width: 30% !important;margin: 0;" class="word-wrap">
						<span class="pull-left text-left word-wrap">
							<br />						    
							@if(!empty($receipt_details->invoice_no_prefix))
							<span><b>{!! $receipt_details->invoice_no_prefix !!}</b></span>
							@endif
							{{$receipt_details->invoice_no}}
						</span>
					</p>
			
					<p style="width: 35% !important;margin: 0;" class="word-wrap">
						<span class="pull-left text-left">
							<br />						    
							<span><b>{{$receipt_details->date_label}}</b></span> {{$receipt_details->invoice_date}}
			
							@if(!empty($receipt_details->due_date_label))
								<span>{{$receipt_details->due_date_label}}</span> {{$receipt_details->due_date ?? ''}}
							@endif
			
							@if(!empty($receipt_details->brand_label) || !empty($receipt_details->repair_brand))
							
							@if(!empty($receipt_details->brand_label))
								<span>{!! $receipt_details->brand_label !!}</span>
							@endif
								{{$receipt_details->repair_brand}}
							@endif
			
			
							@if(!empty($receipt_details->device_label) || !empty($receipt_details->repair_device))
							<br>
							@if(!empty($receipt_details->device_label))
							<b>{!! $receipt_details->device_label !!}</b>
							@endif
							{{$receipt_details->repair_device}}
							@endif
			
							@if(!empty($receipt_details->model_no_label) || !empty($receipt_details->repair_model_no))
							<br>
							@if(!empty($receipt_details->model_no_label))
							<b>{!! $receipt_details->model_no_label !!}</b>
							@endif
							{{$receipt_details->repair_model_no}}
							@endif
			
							@if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))
							<br>
							@if(!empty($receipt_details->serial_no_label))
							<b>{!! $receipt_details->serial_no_label !!}</b>
							@endif
							{{$receipt_details->repair_serial_no}}<br>
							@endif
							@if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))
							@if(!empty($receipt_details->repair_status_label))
							<b>{!! $receipt_details->repair_status_label !!}</b>
							@endif
							{{$receipt_details->repair_status}}<br>
							@endif
			
							@if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))
							@if(!empty($receipt_details->repair_warranty_label))
							<b>{!! $receipt_details->repair_warranty_label !!}</b>
							@endif
							{{$receipt_details->repair_warranty}}
							<br>
							@endif
			
						
			
			
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
					<p style="width: 35% !important;margin: 0;" class="word-wrap"></p>
				</div>
				<div class="col-xs-12 text-left" style="display: flex; margin-top: 1rem;">
					<!-- Head  -->
					<p style="width: 30% !important;margin: 0;" class="word-wrap">
						<span class="pull-left text-left">
							<!-- customer info -->
							@if(!empty($receipt_details->customer_info))
							<span><b>{{ $receipt_details->customer_label }} : </b></span>
							@endif
						</span>
						<br />
							<span>
							<b>{{ $receipt_details->customer_name }}</b><br />
							{{ $receipt_details->customer_mobile }}
						</span>
				
						@if(!empty($receipt_details->customer_tax_number))
							<br/>
							<span><b>{{ $receipt_details->customer_tax_label }}</b></span> {{ $receipt_details->customer_tax_number }}
						@endif
					</p>
					<p style="width: 35% !important;margin: 0;" class="word-wrap">
						<span class="pull-left text-left">
							<b>Billing Address: </b>
						</span>
						@if (!empty($receipt_details->billing_address))
		
						{!! $receipt_details->billing_address !!}
						@else
		
						{!! $receipt_details->customer_info_address !!}
						{{-- {!! $receipt_details->customer_info_address !!} --}}
						@endif
			
					</p>
					<p style="width: 35% !important;margin: 0;" class="word-wrap">
						<span class="pull-left text-left">
							<b>Shipping Address :</b>
						</span>
						<br />
						@if(!empty($receipt_details->shipping_address))
						{!! $receipt_details->shipping_address !!}
						@endif
					</p>
				
				</div>
				<div class="col-xs-6 text-left" style="display: flex; space-between; width: 100%;  align-items: center;">
				    	<p  style="width: 35% !important;margin: 0;" class="word-wrap">
					    	<!-- Waiter info -->
							@if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
							<br />
							@if(!empty($receipt_details->service_staff_label))
							<b>{!! $receipt_details->service_staff_label !!}</b>
							@endif
							{{$receipt_details->service_staff}}
							@endif
					</p>
					  <p  style="width: 35% !important;margin: 0;" class="word-wrap">
						<span class="pull-left text-left">
						    	@if(!empty($receipt_details->commission_agent_label) || !empty($receipt_details->commission_agent))
							<br />
							@if(!empty($receipt_details->commission_agent_label))
							<b>{!! $receipt_details->commission_agent_label !!}</b>
							@endif
							{{$receipt_details->commission_agent}}
							@endif
						    </span>
						    </p>
						    <p style="width: 35% !important;margin: 0; margin-left: -10%" class="word-wrap">
						<span class="pull-left text-left">
						    </span>
						    </p>
				</div>
				
			</div>


			@includeIf('sale_pos.receipts.partial.common_repair_invoice')


			
			<div class="col-xs-12">
				<table class="table table-slim">
					<thead>
						<tr style="font-size: 15px !important;border: 2px solid #000;" class="text-center">
						
							<td style=" width: 3% !important;padding:2px 0;border-right: 2px solid #000;">
								#
							</td>
		
							@php
								$p_width = 30;
							@endphp
		
							@if($receipt_details->show_cat_code != 1)
								@php
									$p_width = 40;
								@endphp
							@endif
							<td style="width: {{$p_width}}% !important;padding:2px 0;border-right: 2px solid #000;">
								{{$receipt_details->table_product_label}}
							</td>
							@if($receipt_details->show_cat_code == 1)
								<td style="width: 10% !important;padding:2px 0;border-right: 2px solid #000;">
									{{$receipt_details->cat_code_label}}
								</td>
							@endif
							<td style=" width: 10% !important;padding:2px 0;border-right: 2px solid #000;">
								{{$receipt_details->table_qty_label}}
							</td>
							{{-- <td style=" width: 10% !important;padding:2px 0;">
								{{$receipt_details->table_unit_price_label}}
							</td> --}}
							<td style=" width: 13% !important;padding:2px 0;border-right: 2px solid #000;">
								{{$receipt_details->line_discount_label}}
							</td>
						 
							<td style=" width: 10% !important;padding:2px 0;border-right: 2px solid #000;">
								{{$receipt_details->table_unit_price_label}} (@lang('product.exc_of_tax'))
							</td>
							   <td style=" width: 13% !important;padding:2px 0;border-right: 2px solid #000;">
								{{$receipt_details->line_tax_label}}
							</td> 
							<td style=" width: 13% !important;padding:2px 0;">
								{{$receipt_details->table_subtotal_label}}
							</td>
		
						</tr>
					</thead>
					<tbody>
		
						@php
							$total_unit_price_exc_tax = 0;
							$total_line_discount_uf = 0;
							$total_gst = 0;
							$line_total_inc_tax = 0;
						@endphp
		
						@foreach($receipt_details->lines as $line)
		
							@php
							$total_quantity += $line["quantity_uf"];
								$total_unit_price_exc_tax += $line['price_exc_tax'];
								$total_line_discount_uf += $line['line_discount_uf']*$line['quantity_uf'];
								$total_gst += $line['tax_uf'];
								$line_total_inc_tax += $line['line_total_uf'];
							@endphp
		
							<tr style="border-bottom: 2px solid #cecccc;border-left: 1px solid #ccc;border-right: 1px solid #ccc;">
								<td class="text-center" style="padding: 5px 0;">
									{{$loop->iteration}}
								</td>
								<td style="padding: 5px 0;">
									@if(!empty($line['image']))
									<img src="{{$line['image']}}" alt="Image" width="50"
										style="float: left; margin-right: 8px;">
									@endif
									{{$line['name']}} {{$line['product_variation']}} {{$line['variation']}}
									@if(isset($line['item_type']) && ($line['item_type'] == 'imei' || $line['item_type'] == 'serial'))
									<br>{{ $line['item_type'] == 'imei' ? implode(', ', $line['sold_imei'] ?? []) : implode(', ', $line['sold_serial'] ?? []) }}
									@endif
									@if(!empty($line['sub_sku'])), {{$line['sub_sku']}} @endif
									@if(!empty($line['brand'])), {{$line['brand']}} @endif
									@php
									   $layout_settings = \App\InvoiceLayout::where("business_id", auth()->user()->business_id)->where("design", "new_detailed")->get()[0];
									@endphp
									@if($layout_settings->product_custom_fields)
    									@if(!empty($line['product_custom_fields'])), {{$line['product_custom_fields']}}
    									@endif
									@endif
									@if(!empty($line['product_description']))
									<small>
										{!!$line['product_description']!!}
									</small>
									@endif
									@if(!empty($line['sell_line_note']))
									<br>
						    			<small class="text-muted">{!!$line['sell_line_note']!!}</small>
									@endif
									@if(!empty($line['lot_number']))<br> {{$line['lot_number_label']}}:
									{{$line['lot_number']}} @endif
									@if(!empty($line['product_expiry'])), {{$line['product_expiry_label']}}:
									{{$line['product_expiry']}} @endif
		
									@if(!empty($line['warranty_name'])) <br><small>{{$line['warranty_name']}}
									</small>@endif @if(!empty($line['warranty_exp_date'])) <small>-
										{{@format_date($line['warranty_exp_date'])}} </small>@endif
									@if(!empty($line['warranty_description'])) <small>
										{{$line['warranty_description'] ?? ''}}</small>@endif
		
									@if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
										<br>
										<small>
											{{$line['units']}} = {{$line['base_unit_multiplier']}}
											{{$line['base_unit_name']}} <br>
											{{$line['base_unit_price']}} x {{$line['orig_quantity']}} =
											{{$line['line_total']}}
										</small>
									@endif
								</td>
		
							@if($receipt_details->show_cat_code == 1)
                                <td class="text-center" style="padding: 5px 0;border-right: 1px solid #ccc;border-left: 1px solid #ccc">
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
		
								<td class="text-center" style="padding: 5px 0;border-right: 1px solid #ccc;">
									{{$line['quantity']}} </br>{{$line['units']}}
		
									@if($receipt_details->show_base_unit_details && $line['quantity'] &&
									$line['base_unit_multiplier'] !== 1)
									<br><small>
										{{$line['quantity']}} x {{$line['base_unit_multiplier']}} = {{$line['orig_quantity']}} {{$line['base_unit_name']}}
									</small>
									@endif
								</td>
		
								{{-- <td class="text-right" style="padding: 5px 0;">
									{{$line['unit_price_before_discount']}}
								</td> --}}
		
								<td class="text-center" style="padding: 5px 0;border-right: 1px solid #ccc;">
									{{$line['total_line_discount'] ?? 0}} </br>
									@if(!empty($line['line_discount_percent']))
										({{$line['line_discount_percent']}}%)
									@endif
								</td>

								<td class="text-center" style="padding: 5px 0;border-right: 1px solid #ccc;">
									{{number_format( (float)  join("", explode(",", $line['unit_price_before_discount']))- (float) $line['line_discount_uf'], 2, null, null)}}

								</td>
									<td class="text-center" style="padding: 5px 0; border-right: 1px solid #ccc;">
									    
									{{$line['tax']}} </br> {{$line['tax_name']}}
								</td>
								<td class="text-center" style="padding: 5px 0;border-right: 1px solid #ccc;">

									
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
											@if(!empty($modifier['sell_line_note']))({!!$modifier['sell_line_note']!!})
											@endif
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
		
		
					</tbody>
				</table>
			</div>

		<div class="col-xs-12">
		    <small>
		      {{$receipt_details->additional_notes}}  
		    </small>
		</div>

			<div class="col-xs-12" style="display: flex">
		

		
				<table style="width: 100%;" >
					<tbody>
						<!--@if(!empty($receipt_details->total_quantity_label))-->
						<!--	<tr >-->
						<!--		<td style="width:50%">-->
						<!--			{!! $receipt_details->total_quantity_label !!}-->
						<!--		</td>-->
						<!--		<td class="text-right">-->
						<!--			{{-- $receipt_details->total_quantity --}}-->
      <!--                              {{ $total_quantity }}-->
						<!--		</td>-->
						<!--	</tr>-->
						<!--@endif-->
		
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
                        <tr>
                            <td style="width: 30%">
                                <small>Total Items/ Qty : {{$lines}} / {{$total_quantity}} {{-- $receipt_details->total_quantity --}}</small></br>
                                @if($total_calculated_discount != 0)
                                <small>Total Discount: @format_currency($total_calculated_discount)</small></br>
                                @endif
                                	@if( !empty($receipt_details->reward_point_label) )
                                		{!! $receipt_details->reward_point_label !!} 	: {{$receipt_details->reward_point_amount}}
						            @endif
                            </td>
                            <td class="text-right">
                                {!! $receipt_details->subtotal_label !!} {{$receipt_details->subtotal}}</br>
                                <b><span style="font-size: 1.25rem ;" >{!! $receipt_details->total_label !!}	{{$receipt_details->total}}</span> </b></br>
                                <small style="text-transform: capitalize"> Rupees {{$receipt_details->total_in_words}} only/- </small>
                            </td>
                        </tr>
                   

						{{--<tr>
						    <td style="50%"><small>Total Items/ Qty :</small></td>
						    <td class="text-right">
								<small class="text-right"> {{$lines}} / {{$total_quantity}} {{$receipt_details->total_quantity}} </small>
							</td>
							
							</tr>
							<tr>
							<td style="width:50%">
								{!! $receipt_details->subtotal_label !!}
							</td>
							<td class="text-right">
								{{$receipt_details->subtotal}}
							</td>
							</tr>
							<tr>
                            <td style="width: 50%;"><small style="text-transform: capitalize"> Total Amount (in words) :</small></td>
							<td class="text-right">
								<small style="text-transform: capitalize"> Rupees {{$receipt_details->total_in_words}} only/- </small>
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
										(+) {{$value}}
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
									{{$receipt_details->round_off}}
								</td>
							</tr>
						@endif
						
						<!-- Total -->
						<tr style="margin-bottom: 10px">
							<th style="" class=" font-17">
								{!! $receipt_details->total_label !!}
							</th>
							<td class="text-right font-17" style="">
								{{$receipt_details->total}}
							</td>
						</tr>
						--}}
					</tbody>
				</table>

		
			</div>

			{{-- @if ($lines<=2)
				<div class="col-xs-12" style="margin-top: 40px"></div>
			@endif --}}


			<div class="col-xs-12">
			    
				<table class="table table-slim ">
					<tbody>
					    	@php
							$total_x = 0;
						@endphp

					      @if(empty($receipt_details->hide_price) && !empty($receipt_details->tax_summary_label) )
		
	
						<tr style="" class="">
						
							<td colspan="5" class="text-center" style="border-left: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">
								{{$receipt_details->tax_summary_label}}
							</td>
					

							<td class="text-center" style="border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">
								&nbsp;
							</td>
						</tr>
						<!-- 9 cols, 6 sub head cols, 1 head cols -->
						<tr>
                            <td class="text-center" style="border-left: 1px solid #000;border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">Tax Rate</td>
                            <td class="text-center" style="border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">Qty</td>
                            <td class="text-center" style="border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">CGST</td>
                            <td class="text-center" style="border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">SGST</td>
                            <td class="text-center" style="border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">IGST</td>
                            <td class="text-center" style="border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">Total Tax</td>
                            
						</tr>
					<!--{{--	<tr style="" class="">-->
						
				
					<!--		{{-- <td class="text-center" style="border-left: 1px solid #000;border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">-->
					<!--			Taxable Amount-->
					<!--		</td> --}}-->
		
					<!--		<td class="text-center" style="border-left: 1px solid #000;border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">-->
					<!--			CGST-->
					<!--		</td>-->
					<!--		<td class="text-center" style="border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">-->
					<!--			SGST-->
					<!--		</td>-->
					<!--		<td class="text-center" style="border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">-->
					<!--			IGST-->
					<!--		</td>-->
					<!--		<td class="text-center" style="border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">-->
					<!--			Total Tax-->
					<!--		</td>-->
					<!--	</tr> --}}-->
		
		
		
		
						@php
							$taxSums = [];
						@endphp
						
						@foreach($receipt_details->lines as $line)
							@php
								$taxId = $line['tax_id'];
								$taxPercent = $line['tax_percent'] ?? null; // Ensure tax_percent is set
		                      
		                   
								if (!isset($taxSums[$taxId])) {
									$taxSums[$taxId] = [
										'total' => 0,
										'qty' => 0,
										'percent' => $taxPercent
									];
								} else {
									// Ensure percent is set only if it's not already set
									if ($taxSums[$taxId]['percent'] === null && $taxPercent !== null) {
										$taxSums[$taxId]['percent'] = $taxPercent;
									}
								}
		
								$taxSums[$taxId]['total'] += $line['tax_uf'] * $line['quantity_uf'];
								$taxSums[$taxId]['qty']  += $line["quantity_uf"];
	                   
							@endphp
						@endforeach
						
						@php
    						function cmp($a, $b) {
    						 //dd($a, $b);
                                if ($a["percent"] == $b["percent"]) {
                                    return 0;
                                }
                                return ($a["percent"] < $b["percent"] ) ? -1 : 1;
                            }
                            uasort($taxSums, 'cmp');
						    
						@endphp
						@foreach($taxSums as $taxId => $taxInfo)
						
							@if ($taxInfo['total'] > 0)
		
								@php
						
								if(empty($receipt_details->hide_price) && !empty($receipt_details->tax_summary_label) ){
									$gst_percent = $taxInfo['percent']/2;
									$gst_amount = $taxInfo['total']/2;	
									
									$total_x += $taxInfo['total'];
									}
									
								@endphp
	
	                            <tr>
	                                <td class="text-center" style="border-left: 1px solid #000;border-bottom: 1px solid #000;border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">
	                                    GST {{$taxInfo["percent"]}} %
	                                </td>
	                                <td  class="text-center" style="border-bottom: 1px solid #000;border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">{{$taxInfo["qty"]}}</td>
	                                <td  class="text-center" style="border-bottom: 1px solid #000;border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">
	                                    <table style="width: 100%">
											<tbody>
												<td style="border-right: 1px solid #000;width: 50%">
													@if (empty($receipt_details->customer->state) || strtolower($receipt_details->customer->state) == strtolower($receipt_details->location_state))
														{{ $gst_percent }} %
													@else
														&nbsp;
													@endif
												</td>
												<td style="width: 50%">
													@if (empty($receipt_details->customer->state) || strtolower($receipt_details->customer->state) == strtolower($receipt_details->location_state))
														{{ $gst_amount }}
													@else
														&nbsp;
													@endif
												</td>
											</tbody>
										</table>
	                                </td>
	                                	<td class="text-center" style="border-bottom: 1px solid #000;border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">
										<table style="width: 100%">
											<tbody>
												<td style="border-right: 1px solid #000;width: 50%">
													@if (empty($receipt_details->customer->state) || strtolower($receipt_details->customer->state) == strtolower($receipt_details->location_state))
														{{ $gst_percent }} %
													@else
														&nbsp;
													@endif
												</td>
												<td style="width: 50%">
													@if (empty($receipt_details->customer->state) || strtolower($receipt_details->customer->state) == strtolower($receipt_details->location_state))
														{{ $gst_amount }}
													@else
														&nbsp;
													@endif
												</td>
											</tbody>
										</table>
									</td>
									<td class="text-center" style="border-bottom: 1px solid #000;border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">
										<table style="width: 100%">
											<tbody>
												<td style="border-right: 1px solid #000;width: 50%">
												    
												    
													@if (empty($receipt_details->customer->state) || strtolower($receipt_details->customer->state) == strtolower($receipt_details->location_state))
														&nbsp;
													@else
														{{ $taxInfo['percent'] }} %
													@endif
												</td>
												<td style="width: 50%">
												    
													@if (empty($receipt_details->customer->state) || strtolower($receipt_details->customer->state) == strtolower($receipt_details->location_state))
														&nbsp;
													@else
														{{ $taxInfo['total'] }}
													@endif
												</td>
											</tbody>
										</table>
									</td>
									
									<td class="text-center" style="border-bottom: 1px solid #000;border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">
										<span>
											@format_currency($taxInfo['total'])
										</span>
									</td>
	                            </tr>
	                                
								{{--<tr style="" class="">
		
									<td class="text-center" style="border-left: 1px solid #000;border-bottom: 1px solid #000;border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">
										<table style="width: 100%">
											<tbody>
												<td style="border-right: 1px solid #000;width: 50%">
													@if (empty($receipt_details->customer->state) || strtolower($receipt_details->customer->state) == strtolower($receipt_details->location_state))
														{{ $gst_percent }} %
													@else
														&nbsp;
													@endif
												</td>
												<td style="width: 50%">
													@if (empty($receipt_details->customer->state) || strtolower($receipt_details->customer->state) == strtolower($receipt_details->location_state))
														{{ $gst_amount }}
													@else
														&nbsp;
													@endif
												</td>
											</tbody>
										</table>
									</td>
									<td class="text-center" style="border-bottom: 1px solid #000;border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">
										<table style="width: 100%">
											<tbody>
												<td style="border-right: 1px solid #000;width: 50%">
													@if (empty($receipt_details->customer->state) || strtolower($receipt_details->customer->state) == strtolower($receipt_details->location_state))
														{{ $gst_percent }} %
													@else
														&nbsp;
													@endif
												</td>
												<td style="width: 50%">
													@if (empty($receipt_details->customer->state) || strtolower($receipt_details->customer->state) == strtolower($receipt_details->location_state))
														{{ $gst_amount }}
													@else
														&nbsp;
													@endif
												</td>
											</tbody>
										</table>
									</td>
									<td class="text-center" style="border-bottom: 1px solid #000;border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">
										<table style="width: 100%">
											<tbody>
												<td style="border-right: 1px solid #000;width: 50%">
													@if (empty($receipt_details->customer->state) || strtolower($receipt_details->customer->state) == strtolower($receipt_details->location_state))
														&nbsp;
													@else
														{{ $taxInfo['percent'] }} %
													@endif
												</td>
												<td style="width: 50%">
													@if (empty($receipt_details->customer->state) || strtolower($receipt_details->customer->state) == strtolower($receipt_details->location_state))
														&nbsp;
													@else
														{{ $taxInfo['total'] }}
													@endif
												</td>
											</tbody>
										</table>
									</td>
									<td class="text-center" style="border-bottom: 1px solid #000;border-right: 1px solid #000;border-top: 1px solid #000;padding:2px 0;">
										<span>
											@format_currency($taxInfo['total'])
										</span>
									</td>
								</tr> --}}
		
							@endif
								
						@endforeach

						<tr style="" class="">
							<td class="text-center" style="border-bottom: 1px solid #000;border-left: 1px solid #000;padding:2px 0;">
								
							</td>
							<td class="text-center" style="border-bottom: 1px solid #000;padding:2px 0;">
								
							</td>
								<td class="text-center" style="border-bottom: 1px solid #000;padding:2px 0;">
								
							</td>
								<td class="text-center" style="border-bottom: 1px solid #000;padding:2px 0;">
								
							</td>
							<td class="text-center" style="border-bottom: 1px solid #000;padding:2px 0;">
								Total
							</td>
							<td class="text-center" style="border-bottom: 1px solid #000;border-right: 1px solid #000;border-left: 1px solid #000;padding:2px 0;">
								@format_currency($total_x)
							</td>
						</tr>
				@endif
					</tbody>
				</table>
			</div>
			<div class="col-xs-12">
				<table class="table table-slim ">
					<thead>
						<tr style="border-bottom: 2px solid #ccc;" class="table-no-side-cell-border table-no-top-cell-border">
							@if(!empty($receipt_details->payments))
							    <span style="font-weight: bold; font-size: 1.25 rem;">Payment Informations</span>
                				@foreach($receipt_details->payments as $payment)
                					<tr>
                						<td>{{$payment['method']}}</small></td>
                						<td>{{$payment['amount']}}</small></td>
                						<td>{{$payment['date']}}</small></td>
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
				          
						</tr>
					</thead>
				</table>
			</div>
			<div class="col-xs-12">
				@if(!empty($receipt_details->footer_text))
					<div class="@if($receipt_details->show_barcode || $receipt_details->show_qr_code) col-xs-8 @else col-xs-12 @endif">
						{!! $receipt_details->footer_text !!}
					</div>
				@endif
			</div>
			<div class="col-xs-12" style="display: flex">
				<p style="width: 20% !important;margin: 0;" class="word-wrap">
					@if($receipt_details->show_barcode || $receipt_details->show_qr_code)
						@if($receipt_details->show_barcode)
							<img style="width: 75%" class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
						@endif
						@if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
							<img style="width: 75%" class="center-block mt-5" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE', 3, 3, [39, 48, 54])}}">
						@endif
					@endif
				</p>
		
				<p style="width: 60% !important;margin: 0;" class="word-wrap">
					&nbsp;
				</p>
		
		
				<p style="width: 20% !important;margin: 0;text-align: end;" class="word-wrap">
					For 	
					@if(!empty($receipt_details->display_name))
						<b>{{$receipt_details->display_name}}</b>
					@endif
				</p>
		
			</div>



		</div>

	</div>
	
</div>
