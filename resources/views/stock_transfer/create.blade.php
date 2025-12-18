@extends('layouts.app')
@section('title', __('lang_v1.add_stock_transfer'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.add_stock_transfer')</h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        {!! Form::open([
            'url' => action([\App\Http\Controllers\StockTransferController::class, 'store']),
            'method' => 'post',
            'id' => 'stock_transfer_form',
            'enctype' => 'multipart/form-data',
        ]) !!}

        @component('components.widget', ['class' => 'box-solid'])
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('transaction_date', __('messages.date') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required']) !!}
                        </div>
                    </div>
                </div>
             
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no', __('purchase.ref_no') . ':') !!}
                        {!! Form::text('ref_no', null, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('status', __('sale.status') . ':*') !!} @show_tooltip(__('lang_v1.completed_status_help'))
                        {!! Form::select('status', $statuses, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('messages.please_select'),
                            'required',
                            'id' => 'status',
                        ]) !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('location_id', __('lang_v1.location_from') . ':*') !!}
                        {!! Form::select('location_id', $business_locations, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('messages.please_select'),
                            'required',
                            'id' => 'location_id',
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('transfer_location_id', __('lang_v1.location_to') . ':*') !!}
                        {!! Form::select('transfer_location_id', $business_locations, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('messages.please_select'),
                            'required',
                            'id' => 'transfer_location_id',
                        ]) !!}
                    </div>
                </div>

            </div>
        @endcomponent

        <!-- end-->
        @component('components.widget', ['class' => 'box-solid'])
            <div class="row">
                           <!--<input type="file" name="file" placeholder="Choose File" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" />-->
                        
                <div class="col-sm-8">
                    <div class="form-group">
                        <button disabled="true" type="button" id="import_products" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm" data-toggle="modal" data-target="#import_stock_transfer_modal">Import Products</button>
                        <!--{!! Form::label('file-->
                        <!--', __('Upload File') . ':') !!}-->
                        <!--{!! Form::file('file',  ['class' => 'form-control', 'accept-->
                        <!--' => '.csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel', 'style' => 'max-width: 10vw;']) !!}-->
                        <!--{!! Form::hidden('import', 'yes') !!}-->
                    </div>
                </div>
                <div class="col-sm-8 col-sm-offset-2">
                    <div class="form-group">
                       <div class="input-group">
                           <span class="input-group-addon">
                               <i class="fa fa-search"></i>
                           </span>
                            {!! Form::text('search_product', null, [
                                'class' => 'form-control',
                                'id' => 'search_product_for_srock_adjustment',
                                'placeholder' => __('stock_adjustment.search_product'),
                                'disabled',
                            ]) !!}
                           <span class="input-group-btn">
                               <x-camera-barcode-scanner
                                   search-input-id="search_product_for_stock_transfer"
                                   button-class="transfer-scanner-btn"
                                   title="Scan Product for Transfer" />
                           </span>
                       </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-10 col-sm-offset-1">
                    <input type="hidden" id="product_row_index" value="0">
                    <input type="hidden" id="total_amount" name="final_total" value="0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-condensed" id="stock_adjustment_product_table">
                            <thead>
                                <tr>
                                    <th class="col-sm-4 text-center">
                                        @lang('sale.product')
                                    </th>
                                    <th class="col-sm-2 text-center">
                                        @lang('sale.qty')
                                    </th>
                                    <th class="col-sm-2 text-center show_price_with_permission">
                                        @lang('sale.unit_price')
                                    </th>
                                    <th class="col-sm-2 text-center show_price_with_permission">
                                        @lang('sale.subtotal')
                                    </th>
                                    <th class="col-sm-2 text-center"><i class="fa fa-trash" aria-hidden="true"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                            <tfoot>
                                <tr class="text-center show_price_with_permission">
                                    <td colspan="3"></td>
                                    <td>
                                        <div class="pull-right"><b>@lang('sale.total'): </b> <span
                                                id="total_adjustment">0.00</span></div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endcomponent


        @component('components.widget', ['class' => 'box-solid'])
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('shipping_charges', __('lang_v1.shipping_charges') . ':') !!}
                        {!! Form::text('shipping_charges', 0, [
                            'class' => 'form-control input_number',
                            'placeholder' => __('lang_v1.shipping_charges'),
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('additional_notes', __('purchase.additional_notes')) !!}
                        {!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'rows' => 3]) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 text-right show_price_with_permission">
                    <b>@lang('stock_adjustment.total_amount'):</b> <span id="final_total_text">0.00</span>
                </div>
                <br>
                <br>
                <div class="col-sm-12 text-center">
                    <button type="submit" id="save_stock_transfer" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">@lang('messages.save')</button>
                </div>
            </div>
        @endcomponent

        {!! Form::close() !!}
        
        <div class="modal fade in" tabindex="-1" role="dialog" id="import_stock_transfer_modal" style="display: none; padding-left: 12px;">
	<div class="modal-dialog modal-lg" role="document">
  		<div class="modal-content">
  			<div class="modal-header">
			    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
			    <h4 class="modal-title">Import Stock Transfer</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<strong>File To Import:</strong>
					</div>
						<div class="col-md-12">
						<div id="import_product_dz" class="dropzone"></div>
					</div>
					<div class="col-md-12 mt-10">
						<a href="{{ asset('files/import_stock_transfer_products.csv') }}" class="tw-dw-btn tw-dw-btn-success tw-text-white" download=""><i class="fa fa-download"></i> Download template file</a>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<h4>Instructions:</h4>
						<strong>@lang('lang_v1.instruction_line1')</strong><br>
		                    @lang('lang_v1.instruction_line2')<br><br>
						<table class="table table-striped">
		                    <tbody>  <tr>
		                        <th>@lang('lang_v1.col_no')</th>
		                        <th>@lang('lang_v1.col_name')</th>
		                        <th>@lang('lang_v1.instruction')</th>
		                    </tr>
		                    <tr>
		                    	<td>1</td>
		                        <td>@lang('product.sku') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
		                        <td></td>
		                    </tr>
		                    <tr>
		                    	<td>2</td>
		                        <td>@lang('lang_v1.quantity') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
		                        <td></td>
		                    </tr>
		                    <tr>
		                    	<td>3</td>
		                        <td>@lang('lang_v1.lot_number')<small class="text-muted">(@lang('lang_v1.required'))</small></td>
		                        <td></td>
		                    </tr>
		                </tbody></table>
		            </div>
				</div>
			</div>
			<div class="modal-footer">
      			<button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="import_purchase_products"> @lang( 'lang_v1.import' )</button>
      			<button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
    		</div>
  		</div>
  	</div>
</div>

<!--    <button id="loading_btn" style="display: none;" type="button" class="btn btn-primary" >-->
<!--</button>-->
    
<!--    <div style="display: none; background: black; opacity: 0.5;  position: absolute; top: 0; left: 0; width: 100%; height: 100%; justify-content:center; align-items: center; z-index: 9999;" id="loading_1">-->
<!--        <img src="{{ asset('images/loading/csvloading.gif') }}" style="width: 20vw; height: 30vh;" alt="loading" >-->
<!--    </div>-->
@include('loadings.purchase_loading')
    </section>
@stop
@section('javascript')
    <script src="{{ asset('js/stock_transfer.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        __page_leave_confirmation('#stock_transfer_form');
    </script>
    <script>
        // const loadingModal = document.getElementById("loading_1");
        // const loadingBtn = document.getElementById("loading_btn");
        
        // loadingBtn.addEventListener("click", () => {
        //     loadingModal.style.display ="flex";
        // })
    </script>
@endsection


<!-- IMEI/Serial Selection Modal -->
<div class="modal fade" id="imei-serial-modal" tabindex="-1" role="dialog" aria-labelledby="imeiSerialModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="imeiSerialModalLabel">@lang('lang_v1.select_imei_serial')</h4>
            </div>
            <div class="modal-body">
                <div id="imei-serial-list">
                    <!-- IMEI/Serial numbers will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="btn btn-primary" id="confirm-imei-serial-selection">@lang('messages.save')</button>
            </div>
        </div>
    </div>
</div>

@cannot('view_purchase_price')
    <style>
        .show_price_with_permission {
            display: none !important;
        }
    </style>
@endcannot
