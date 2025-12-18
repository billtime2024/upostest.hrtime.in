@php
	$common_settings = session()->get('business.common_settings');
    $total_purchase = 0;
    $total_sell = 0;
    $total_opening_stock = 0;
    $total_closing_stock = 0;
    $total_sell_transfer = 0;
    $total_purchase_transfer = 0;

    foreach ($purchases as $key => $transaction) {

        if($transaction['transaction_type'] === 'purchase') {
            $total_purchase += 1;
        }else if ($transaction['transaction_type'] === 'sell') {
            $total_sell += 1;
        }else if ($transaction['transaction_type'] === 'purchase_transfer') {
            $total_purchase_transfer += 1;
        }else if ($transaction['transaction_type'] === 'sell_transfer') {
            $total_sell_transfer += 1;
        }else if ($transaction['transaction_type'] === 'opening_stock') {
            $total_opening_stock += 1;
        }else if ($transaction['transaction_type'] === 'closing_stock') {
            $total_opening_stock += 1;
        }
    }
@endphp
<div class="row">
	<div class="col-md-12">
       <h3> {{$purchases[0]['product_name']}} - {{ $purchases[0]['variation_name']}} - {{ $purchases[0]['variation_value']}} ( @if ($purchases[0]['product_type'] === 'single') {{$purchases[0]['sku']}} @else {{$purchases[0]['sub_sku']}} @endif )</h3>
	</div>
    @if (count($purchases) > 0)
	<div class="col-md-4 col-xs-4">
		<strong>@lang('lang_v1.quantities_in')</strong>
		<table class="table table-condensed">
			<tr>
				<th>@lang('report.total_purchase')</th>
				<td>
					<span class="display_currency" data-is_quantity="true">{{$total_purchase}}</span> {{$purchase_line['unit']}}
				</td>
			</tr>
            <tr>
				<th>@lang('report.total_sell')</th>
				<td>
					<span class="display_currency" data-is_quantity="true">{{$total_sell}}</span> {{$purchase_line['unit']}}
				</td>
			</tr>
            <tr>
				<th>Purchase Transfer</th>
				<td>
					<span class="display_currency" data-is_quantity="true">{{$total_purchase_transfer}}</span> {{$purchase_line['unit']}}
				</td>
			</tr>
            <tr>
				<th>Sell Transfer</th>
				<td>
					<span class="display_currency" data-is_quantity="true">{{$total_sell_transfer}}</span> {{$purchase_line['unit']}}
				</td>
			</tr>
            <tr>
				<th>@lang('report.opening_stock')</th>
				<td>
					<span class="display_currency" data-is_quantity="true">{{$total_opening_stock}}</span> {{$purchase_line['unit']}}
				</td>
			</tr>
            <tr>
				<th>@lang('report.closing_stock')</th>
				<td>
					<span class="display_currency" data-is_quantity="true">{{$total_closing_stock}}</span> {{$purchase_line['unit']}}
				</td>
			</tr>
		</table>
	</div>
    @endif
</div>
<div class="row">
	<div class="col-md-12">
		<hr>
		<table class="table table-slim" id="stock_history_table">
			<thead>
			<tr>
				<th>@lang('lang_v1.quantity_change')</th>
				@if(!empty($common_settings['enable_secondary_unit']))
					<th>@lang('lang_v1.quantity_change') (@lang('lang_v1.secondary_unit'))</th>
				@endif
				@if(!empty($common_settings['enable_secondary_unit']))
					<th>@lang('lang_v1.new_quantity') (@lang('lang_v1.secondary_unit'))</th>
				@endif
				<th>@lang('lang_v1.date')</th>
				<th>@lang('purchase.ref_no')</th>
				<th>@lang('lang_v1.customer_supplier_info')</th>
				<th>Type</th>
				<th>Status</th>
			</tr>
			</thead>
			<tbody>
            @if (count($purchases) > 0)
			@foreach ($purchases as $key => $history)
			 @if($history['transaction_type'] !== 'sell_transfer')
				<tr>
				    
				    @if($history['transaction_type'] === 'purchase')
				    						<td class="text-success"> +<span class="display_currency" data-is_quantity="true">{{$history['purchase_quantity']}}</span>
						</td>
						@elseif($history['transaction_type'] === 'sell')
						    					<td class="text-danger"> -<span class="display_currency" data-is_quantity="true">{{$history['sell_quantity']}}</span>
						</td>
						@elseif($history['transaction_type'] === 'stock_adjustment')
									<td class="text-danger"> -<span class="display_currency" data-is_quantity="true">{{$history['stock_adjustment_lines_quantity']}}</span>
						</td>
						@elseif ($history['transaction_type'] === 'sell_transfer' || $history['transaction_type'] === 'purchase_transfer')
						 					<td class="text-success"> +<span class="display_currency" data-is_quantity="true">{{$history['purchase_quantity']}}</span>
						</td>
						@else
						    						<td class="text-danger">-<span class="display_currency text-danger" data-is_quantity="true">{{$history['quantity']}}</span>
						</td>
						@endif
					{{-- @if($history['quantity'] > 0 )
						<td class="text-success"> +<span class="display_currency" data-is_quantity="true">{{$history['quantity']}}</span>
						</td>
					@else
						<td class="text-danger"><span class="display_currency text-danger" data-is_quantity="true">{{$history['quantity']}}</span>
						</td>
					@endif --}}

					<td>{{@format_datetime($history['transaction_date'])}}</td>
					<td>
					    @if ($history['transaction_type'] === 'sell' )
					       {{$history['invoice_no']}}
					    @else
						{{$history['ref_no']}}
						@endif

						@if(!empty($history['additional_notes']))
							@if(!empty($history['ref_no']))
							<br>
							@endif
							{{$history['additional_notes']}}
						
						@endif
					</td>
					@if ( $history['transaction_type'] === 'purchase_transfer')
					<td>
					    @if(!empty($purchases[$key - 1 ]['business_locations']))
					        {{$purchases[$key - 1 ]['business_locations'] }}
					    @endif
					   	@if(!empty($history['business_locations']))
						 - {{$history['business_locations']}}
						@endif
					</td>
					@elseif ( $history['transaction_type'] === 'purchase')
						<td>
						{{$history['contact_name'] ?? '--'}} 
						@if(!empty($history['business_locations']))
						 /{{$history['business_locations']}}
						@endif
					</td>
					@else
					<td>
						{{$history['contact_name'] ?? '--'}} 
						@if(!empty($history['business_locations']))
						 - {{$history['business_locations']}}
						@endif
					</td>
					@endif

                    @if ( $history['transaction_type'] === 'purchase_transfer')
                        <td>
                        Stock Transfer
                    </td>
                   @elseif($history['transaction_type'] === 'sell_transfer')
                   <td></td>
                    @else
                   <td>
                        {{$history['transaction_type']}}
                    </td>
                    @endif

				</tr>
				@endif
			@endforeach
            @else
                <tr><td colspan="5" class="text-center">
				    No history found
				</td></tr>
            @endif
			</tbody>
		</table>
	</div>
</div>
