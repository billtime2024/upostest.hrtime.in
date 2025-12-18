@extends('layouts.app')

@section('title', __('sale.pos_sale'))

@section('content')
<section class="content no-print">
	<input type="hidden" id="amount_rounding_method" value="{{$pos_settings['amount_rounding_method'] ?? ''}}">
	@if(!empty($pos_settings['allow_overselling']))
		<input type="hidden" id="is_overselling_allowed">
	@endif
	@if(session('business.enable_rp') == 1)
        <input type="hidden" id="reward_point_enabled">
    @endif
    @php
		$is_discount_enabled = $pos_settings['disable_discount'] != 1 ? true : false;
		$is_rp_enabled = session('business.enable_rp') == 1 ? true : false;
	@endphp
	{!! Form::open(['url' => action([\App\Http\Controllers\SellPosController::class, 'update'], [$transaction->id]), 'method' => 'post', 'id' => 'edit_pos_sell_form' ]) !!}
	{{ method_field('PUT') }}
	<div class="row mb-12">
		<div class="col-md-12 tw-pt-0 tw-mb-14">
			<div class="row tw-flex lg:tw-flex-row md:tw-flex-col sm:tw-flex-col tw-flex-col tw-items-start md:tw-gap-4">
				<div class="tw-px-3 tw-w-full  lg:tw-px-0 lg:tw-pr-0 @if(empty($pos_settings['hide_product_suggestion'])) lg:tw-w-[60%]  @else lg:tw-w-[100%] @endif">
					<div class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-rounded-2xl tw-bg-white tw-mb-2 md:tw-mb-8 tw-p-2">
						<div class="box-body pb-0">
							{!! Form::hidden('location_id', $transaction->location_id, ['id' => 'location_id', 'data-receipt_printer_type' => !empty($location_printer_type) ? $location_printer_type : 'browser', 'data-default_payment_accounts' => $transaction->location->default_payment_accounts]); !!}
							<!-- sub_type -->
							{!! Form::hidden('sub_type', isset($sub_type) ? $sub_type : null) !!}
							<input type="hidden" id="item_addition_method" value="{{$business_details->item_addition_method}}">
								@include('sale_pos.partials.pos_form_edit')

								@include('sale_pos.partials.pos_form_totals', ['edit' => true])

								@include('sale_pos.partials.payment_modal')

								@if(empty($pos_settings['disable_suspend']))
									@include('sale_pos.partials.suspend_note_modal')
								@endif

								@if(empty($pos_settings['disable_recurring_invoice']))
									@include('sale_pos.partials.recurring_invoice_modal')
								@endif
							</div>
							@if(!empty($only_payment))
								<div class="overlay"></div>
							@endif
						</div>
					</div>
				@if(empty($pos_settings['hide_product_suggestion'])  && !isMobile() && empty($only_payment))
					<div class="col-md-5 no-padding">
						@include('sale_pos.partials.pos_sidebar')
					</div>
				@endif
			</div>
		</div>
	</div>
	@include('sale_pos.partials.pos_form_actions', ['edit' => true])
	{!! Form::close() !!}
</section>

<!-- This will be printed -->
<section class="invoice print_section" id="receipt_section">
</section>
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('contact.create', ['quick_add' => true])
</div>
@if(empty($pos_settings['hide_product_suggestion']) && isMobile())
	@include('sale_pos.partials.mobile_product_suggestions')
@endif
<!-- /.content -->
<div class="modal fade register_details_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade close_register_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

@include('sale_pos.partials.configure_search_modal')

@include('sale_pos.partials.recent_transactions_modal')

@include('sale_pos.partials.weighing_scale_modal')
	@include('loadings.purchase_loading')

<!-- IMEI/Serial modal -->
<div class="modal fade imei-serial-modal" id="imei-serial-modal" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">@lang('lang_v1.select') IMEI/Serial @lang('lang_v1.numbers')</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<input type="text" class="form-control imei-serial-search" placeholder="@lang('lang_v1.search')">
				</div>
				<div class="imei-serial-list" style="max-height: 300px; overflow-y: auto;">
					<!-- Checkboxes will be loaded here -->
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.cancel')</button>
				<button type="button" class="btn btn-primary confirm-imei-serial-selection">@lang('Confirm')</button>
			</div>
		</div>
	</div>
</div>

<!--  <button id="loading_btn" style="display: none;" type="button" class="btn btn-primary" >-->
<!--</button>-->
    
<!--    <div style="display: none; background: black; opacity: 0.5;  position: absolute; top: 0; left: 0; width: 100%; height: 100%; justify-content:center; align-items: center; z-index: 999;" id="loading">-->
<!--        <img src="{{ asset('images/loading/bill_loading.gif') }}" style="width: 20vw; height: 30vh;" alt="loading" >-->
<!--    </div>-->

@stop

@section('javascript')
	<script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
	  <script>
	       // const loadingModal = document.getElementById("loading");
	       // const loadingBtn = document.getElementById("loading_btn");

	       // loadingBtn.addEventListener("click", () => {
	       //     loadingModal.style.display ="flex";
	       // })
	       // loadingModal.removeEventListener("click", () => {
	       //     console.log("listener removed");
	       // })

	       // IMEI/Serial modal functionality
	       $(document).on('click', '.select-imei-serial-btn', function() {
	           var rowIndex = $(this).data('row-index');
	           var variationId = $(this).data('variation-id');
	           var itemType = $(this).data('item-type');
	           var locationId = $('input#location_id').val();
	           var sellLineId = $(this).data('sell-line-id') || null;
	           var modal = $('#imei-serial-modal');
	           modal.data('row-index', rowIndex);
	           modal.find('.modal-title').text('Select ' + (itemType == 'imei' ? 'IMEI' : 'Serial') + ' Numbers');

	           // Load IMEI/Serial numbers
	           $.ajax({
	               url: '/sells/pos/get-available-imei-serial-numbers',
	               method: 'GET',
	               data: {
	                   variation_id: variationId,
	                   location_id: locationId,
	                   sell_line_id: sellLineId
	               },
	               success: function(response) {
	                   var listHtml = '';
	                   if (response.success && response.data.length > 0) {
	                       response.data.forEach(function(item) {
	                           var isSelected = response.selected && response.selected.some(function(selected) {
	                               return selected.id === item.id;
	                           });
	                           listHtml += '<div class="checkbox"><label><input type="checkbox" class="imei-serial-checkbox" value="' + item.identifier + '" ' + (isSelected ? 'checked' : '') + '> ' + item.identifier + '</label></div>';
	                       });
	                   } else {
	                       listHtml = '<p>No available ' + itemType + ' numbers.</p>';
	                   }
	                   modal.find('.imei-serial-list').html(listHtml);
	                   modal.modal('show');
	               },
	               error: function(xhr, status, error) {
	                   console.error('AJAX error:', status, error);
	               }
	           });
	       });

	       // Search functionality in modal
	       $(document).on('input', '.imei-serial-search', function() {
	           var searchTerm = $(this).val().toLowerCase();
	           var modal = $(this).closest('.modal');
	           modal.find('.imei-serial-checkbox').each(function() {
	               var checkbox = $(this);
	               var label = checkbox.closest('label').text().toLowerCase();
	               if (label.includes(searchTerm)) {
	                   checkbox.closest('.checkbox').show();
	               } else {
	                   checkbox.closest('.checkbox').hide();
	               }
	           });
	       });

	       // Confirm selection
	       $(document).on('click', '.confirm-imei-serial-selection', function() {
	           var modal = $('#imei-serial-modal');
	           var rowIndex = modal.data('row-index');
	           var selected = modal.find('.imei-serial-checkbox:checked');
	           var quantityField = $('input[name="products[' + rowIndex + '][quantity]"]');
	           var selectedValues = [];

	           selected.each(function() {
	               selectedValues.push($(this).val());
	           });

	           // Update quantity
	           quantityField.val(selectedValues.length > 0 ? selectedValues.length : 1);
	           quantityField.trigger('change');

	           // Store selected IMEI/serial in hidden fields
	           var formRow = quantityField.closest('tr');
	           // Remove any existing hidden fields for selected_imei_serial
	           formRow.find('input[name="products[' + rowIndex + '][selected_imei_serial][]"]').remove();
	           // Add new hidden fields for each selected value
	           selectedValues.forEach(function(value) {
	               formRow.find('td').last().append('<input type="hidden" name="products[' + rowIndex + '][selected_imei_serial][]" value="' + value + '">');
	           });

	           modal.modal('hide');
	       });
	   </script>
	@include('sale_pos.partials.keyboard_shortcuts')

	<!-- Call restaurant module if defined -->
    @if(in_array('tables' ,$enabled_modules) || in_array('modifiers' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
    	<script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif

    <!-- include module js -->
    @if(!empty($pos_module_data))
	    @foreach($pos_module_data as $key => $value)
            @if(!empty($value['module_js_path']))
                @includeIf($value['module_js_path'], ['view_data' => $value['view_data']])
            @endif
	    @endforeach
	@endif
@endsection

@section('css')
	<style type="text/css">
		/*CSS to print receipts*/
		.print_section{
		    display: none;
		}
		@media print{
		    .print_section{
		        display: block !important;
		    }
		}
		@page {
		    size: 3.1in auto;/* width height */
		    height: auto !important;
		    margin-top: 0mm;
		    margin-bottom: 0mm;
		}
		.overlay {
			background: rgba(255,255,255,0) !important;
			cursor: not-allowed;
		}
	</style>
	<!-- include module css -->
    @if(!empty($pos_module_data))
        @foreach($pos_module_data as $key => $value)
            @if(!empty($value['module_css_path']))
                @includeIf($value['module_css_path'])
            @endif
        @endforeach
    @endif
@endsection