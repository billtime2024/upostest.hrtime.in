@extends('layouts.app')
@section('title', __('repair::lang.repair') . ' '. __('business.dashboard'))

@section('content')
@include('repair::layouts.nav')
<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>
    	@lang('repair::lang.repair')
    	<small>@lang('business.dashboard')</small>
    </h1>
</section>
<!-- Main content -->
<section class="content no-print">
	<div class="row">
		{{-- location here --}}
		{!! Form::open(['route' => ['dashboard.index'], 'method' => 'GET']) !!}
		@if(count($business_locations) == 1)
		@php
		$default_location = current(array_keys($business_locations->toArray()));
		@endphp
		@else
		@php $default_location = null;
		@endphp
		@endif
		<div class="col-md-3">
			<div class="form-group">
				{!! Form::label('location_id', __('business.business_location') . ':*') !!}
				{!! Form::select('location_id', $business_locations, $location_id, ['class' => 'form-control',
				'placeholder' => 'All Locations', 'required', 'style' => 'width: 100%;', 'onchange' =>
				'this.form.submit()']); !!}
			</div>
		</div>
		{{-- location ends here --}}
		{{-- date filter --}}
		<div class="col-md-3">
			<div class="form-group">
				{!! Form::label('dashboard_filter_date', __('messages.date') . ':') !!}
				{!! Form::text('dashboard_filter_date', $date_range, ['class' => 'form-control', 'readonly', 'placeholder' => 'Select a
				date range']) !!}
			</div>
		</div>
		{{-- end date filter --}}
		{!! Form::close() !!}
		<div class="col-md-12">
			<div class="box box-solid">
				<div class="box-header with-border">
					<h4 class="box-title">@lang('repair::lang.job_sheets_by_status')</h4>
				</div>
				<div class="box-body">
					<div class="row">
						@forelse($job_sheets_by_status as $job_sheet)
						<div class="col-md-3 col-sm-6 col-xs-12">
							<div class="small-box" style="background-color: {{$job_sheet->color}};color: #fff;">
								<div class="inner">
									<p>{{$job_sheet->status_name}}</p>
									<h3>{{$job_sheet->total_job_sheets}}</h3>
								</div>
							</div>
						</div>
						@empty
						<div class="col-md-12">
							<div class="alert alert-info">
								<h4>@lang('repair::lang.no_report_found')</h4>
							</div>
						</div>
						@endforelse
					</div>
				</div>
			</div>
		</div>
	</div>
	@if(in_array('service_staff', $enabled_modules))
		<div class="row">
		    <div class="col-xs-12">
		        @component('components.widget')
		            @slot('title')
		                @lang('repair::lang.job_sheets_by_service_staff')
		            @endslot
		            <div class="table-responsive">
						<table class="table table-striped">
							<thead>
								<tr>
									<th>#</th>
									<th>@lang('restaurant.service_staff')</th>
									<th>@lang('repair::lang.total_job_sheets')</th>
								</tr>
							</thead>
							<tbody>
								@foreach($job_sheets_by_service_staff as $job_sheet)
									<tr>
										<td>{{$loop->iteration}}</td>
										<td>{{$job_sheet->service_staff}}</td>
										<td>{{$job_sheet->total_job_sheets}}</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					</div>
		        @endcomponent
		    </div>
		</div>
	@endif
	<div class="row">
	    <div class="col-xs-12">
	        @component('components.widget')
	            @slot('title')
	                @lang('repair::lang.trending_brands')
	            @endslot
	            {!!$trending_brand_chart->container()!!}
	        @endcomponent
	    </div>
	</div>
	<div class="row">
	    <div class="col-xs-12">
	        @component('components.widget')
	            @slot('title')
	                @lang('repair::lang.trending_devices')
	            @endslot
	            {!!$trending_devices_chart->container()!!}
	        @endcomponent
	    </div>
	</div>
	<div class="row">
	    <div class="col-xs-12">
	        @component('components.widget')
	            @slot('title')
	                @lang('repair::lang.trending_device_models')
	            @endslot
	            {!!$trending_dm_chart->container()!!}
	        @endcomponent
	    </div>
	</div>
</section>
@stop
@section('javascript')
	{!!$trending_devices_chart->script()!!}
	{!!$trending_dm_chart->script()!!}
	{!!$trending_brand_chart->script()!!}
	
	<script type="text/javascript">
// 		$(document).ready(function() {
	
			
// 	$('#dashboard_filter_date').daterangepicker(
// 	dateRangeSettings,
// 	function(start, end) {
// 	$('#dashboard_filter_date').val(
// 	start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
// 	);
// 	$('#dashboard_filter_date').closest('form').submit();
	
// 	}
// 	);
// console.log(moment_date_format);
// 	console.log(@json($date_range ?? ''));
			   
	
			    
// 			});
$(document).ready(function() {
// Retrieve initial date range from backend (assuming it's correctly set by the PHP)
var initialDateRange = @json($date_range ?? '');

// Initialize date range picker with the initial date range or default to the whole year
function initializeDateRangePicker(startDate, endDate) {
$('#dashboard_filter_date').daterangepicker(
$.extend({}, dateRangeSettings, {
startDate: startDate,
endDate: endDate
}),
function(start, end) {
$('#dashboard_filter_date').val(
start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
);
$('#dashboard_filter_date').closest('form').submit();
}
);
}

// If initial date range is provided by server-side
if (initialDateRange) {
var dates = initialDateRange.split(' - ');
var startDate = moment(dates[0], moment_date_format);
var endDate = moment(dates[1], moment_date_format);
initializeDateRangePicker(startDate, endDate);
// Set the input field to the initial date range
$('#dashboard_filter_date').val(initialDateRange);
} else {
// Default to the whole current year if no initial date range is provided
var startDate = moment().startOf('year');
var endDate = moment().endOf('year');
initializeDateRangePicker(startDate, endDate);
}

// Log for debugging purposes
console.log(moment_date_format);
console.log(initialDateRange);
});


			// $(document).ready(function() {
			// var initialDateRange = @json($date_range ?? '');
			// console.log(initialDateRange);
			// $('#dashboard_filter_date').daterangepicker(
			// $.extend({}, dateRangeSettings, {
			// startDate: moment(initialDateRange.split(' - ')[0], moment_date_format),
			// endDate: moment(initialDateRange.split(' - ')[1], moment_date_format)
			// }),
			// function(start, end) {
			// $('#dashboard_filter_date').val(
			// start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
			// );
			// $('#dashboard_filter_date').closest('form').submit();
			// }
			// );
			
			// // Set the initial value
			// $('#dashboard_filter_date').val(initialDateRange);
			
			// });
	</script>
	
@endsection
