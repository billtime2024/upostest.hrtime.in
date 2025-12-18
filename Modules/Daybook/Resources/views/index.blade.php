@extends('layouts.app')

@section('title', __('daybook::lang.daybook'))

@section('content')
<section class="content-header">
    <h1>@lang('daybook::lang.daybook')</h1>
</section>

<section class="content">
    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('daybook::lang.daybook_report')</h3>
            <div class="box-tools">
                <button type="button" class="btn btn-sm btn-primary" id="export_pdf">
                    <i class="fa fa-file-pdf-o"></i> @lang('daybook::lang.pdf')
                </button>
                <button type="button" class="btn btn-sm btn-success" id="export_excel">
                    <i class="fa fa-file-excel-o"></i> @lang('daybook::lang.excel')
                </button>
                <button type="button" class="btn btn-sm btn-info" onclick="window.print()">
                    <i class="fa fa-print"></i> @lang('messages.print')
                </button>
            </div>
        </div>

        <div class="box-body">
            <div class="row">
                <!-- Date Range Picker (Primary - like Exchange module) -->
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('date_range', __('report.date_range') . ':') !!}
                        <div class="input-group">
                            {!! Form::text('date_range', null, [
                                'class' => 'form-control', 
                                'id' => 'date_range', 
                                'readonly', 
                                'placeholder' => __('lang_v1.select_a_date_range')
                            ]) !!}
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <!-- Quick Date Filters -->
                <div class="col-md-8">
                    <div class="form-group">
                        {!! Form::label('quick_dates', __('daybook::lang.quick_filters') . ':') !!}
                        <div class="btn-group" style="display: flex; flex-wrap: wrap; gap: 5px;">
                            <button type="button" class="btn btn-sm btn-default quick-date" data-range="today">@lang('daybook::lang.today')</button>
                            <button type="button" class="btn btn-sm btn-default quick-date" data-range="yesterday">@lang('daybook::lang.yesterday')</button>
                            <button type="button" class="btn btn-sm btn-default quick-date" data-range="this_month">@lang('daybook::lang.this_month')</button>
                            <button type="button" class="btn btn-sm btn-default quick-date" data-range="last_month">@lang('daybook::lang.last_month')</button>
                            <button type="button" class="btn btn-sm btn-default quick-date" data-range="last_7_days">@lang('daybook::lang.last_7_days')</button>
                            <button type="button" class="btn btn-sm btn-default quick-date" data-range="last_30_days">@lang('daybook::lang.last_30_days')</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hidden date fields for backend compatibility (values synced from date_range picker) -->
            {!! Form::hidden('start_date', '', ['id' => 'start_date']) !!}
            {!! Form::hidden('end_date', '', ['id' => 'end_date']) !!}
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('business.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, 
                            ['class' => 'form-control select2', 'id' => 'location_id', 'placeholder' => __('messages.all')]) 
                        !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('user_id', __('daybook::lang.user') . ':') !!}
                        {!! Form::select('user_id', $users, null, 
                            ['class' => 'form-control select2', 'id' => 'user_id', 'placeholder' => __('messages.all')]) 
                        !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('account_id', __('account.accounts') . ':') !!}
                        {!! Form::select('account_id', $accounts, $default_account ?? '',
                            ['class' => 'form-control select2', 'id' => 'account_id', 'placeholder' => __('messages.all')])
                        !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('module_filter', __('daybook::lang.module') . ':') !!}
                        {!! Form::select('module_filter', [
                            '' => __('messages.all'),
                            'sell' => __('sale.sale'),
                            'sell_return' => __('lang_v1.sell_return'),
                            'purchase' => __('lang_v1.purchase'),
                            'purchase_return' => __('lang_v1.purchase_return'),
                            'expense' => __('lang_v1.expense'),
                            'payment' => __('lang_v1.payment'),
                            'stock_adjustment' => __('lang_v1.stock_adjustment'),
                            'opening_balance' => __('lang_v1.opening_balance'),
                        ], null, ['class' => 'form-control select2', 'id' => 'module_filter']) 
                        !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('scope', __('daybook::lang.balance_scope') . ':') !!}
                        {!! Form::select('scope', [
                            'cash_bank' => __('daybook::lang.cash_bank'),
                            'cash' => __('daybook::lang.cash_only'),
                            'bank' => __('daybook::lang.bank_only'),
                            'all' => __('daybook::lang.all_accounts'),
                        ], 'all', ['class' => 'form-control select2', 'id' => 'scope'])
                        !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <br>
                        <button type="button" class="btn btn-primary" id="filter_btn">
                            <i class="fa fa-filter"></i> @lang('daybook::lang.filter')
                        </button>
                        <button type="button" class="btn btn-default" id="reset_btn">
                            <i class="fa fa-refresh"></i> @lang('daybook::lang.reset')
                        </button>
                    </div>
                </div>
            </div>

            <style>
                /* Excel-like minimal table styling */
                #daybook_table {
                    border-collapse: collapse;
                    font-size: 12px;
                }
                #daybook_table thead th {
                    background-color: #f2f2f2;
                    border: 1px solid #d0d0d0;
                    padding: 4px 6px;
                    font-weight: 600;
                    text-align: center;
                    font-size: 11px;
                }
                #daybook_table tbody td {
                    padding: 3px 6px;
                    border: 1px solid #e0e0e0;
                    vertical-align: middle;
                }
                #daybook_table tbody tr {
                    height: 22px;
                }
                #daybook_table tbody tr:hover {
                    background-color: #f9f9f9;
                }
                #daybook_table tfoot th {
                    padding: 4px 6px;
                    border: 1px solid #d0d0d0;
                    font-size: 11px;
                }
                #daybook_table tfoot tr:last-child {
                    border-top: 2px solid #333;
                }
                /* Remove button styling from module column */
                #daybook_table .label {
                    display: inline;
                    padding: 0;
                    font-weight: normal;
                    background: none !important;
                    color: #333 !important;
                    border: none;
                    font-size: 12px;
                    line-height: inherit;
                    border-radius: 0;
                }
                /* Subtle link styling */
                #daybook_table a {
                    color: #0066cc;
                    text-decoration: none;
                }
                #daybook_table a:hover {
                    text-decoration: underline;
                }
                /* Minimal footer styling */
                #daybook_table tfoot tr {
                    background-color: #fafafa;
                }
            </style>
            
            <div class="table-responsive" style="margin-top: 15px;">
                <table class="table" id="daybook_table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>@lang('daybook::lang.datetime')</th>
                            <th>@lang('daybook::lang.voucher_no')</th>
                            <th>@lang('daybook::lang.module')</th>
                            <th>@lang('daybook::lang.party')</th>
                            <th>@lang('daybook::lang.location')</th>
                            <th>@lang('daybook::lang.account')</th>
                            <th class="text-right">@lang('daybook::lang.debit')</th>
                            <th class="text-right">@lang('daybook::lang.credit')</th>
                            <th>@lang('daybook::lang.narration')</th>
                            <th>@lang('daybook::lang.user')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-right">@lang('daybook::lang.total'):</th>
                            <th class="text-right" id="total_debit">0.00</th>
                            <th class="text-right" id="total_credit">0.00</th>
                            <th colspan="2"></th>
                        </tr>
                        <tr>
                            <th colspan="6" class="text-right" style="font-weight: 600;">Opening Balance:</th>
                            <th class="text-right" id="opening_balance_debit" style="font-weight: 600;">0.00</th>
                            <th class="text-right" id="opening_balance_credit" style="font-weight: 600;">0.00</th>
                            <th colspan="2"></th>
                        </tr>
                        <tr>
                            <th colspan="6" class="text-right" style="font-weight: 600;">Current Total:</th>
                            <th class="text-right" id="current_total_debit" style="font-weight: 600;">0.00</th>
                            <th class="text-right" id="current_total_credit" style="font-weight: 600;">0.00</th>
                            <th colspan="2"></th>
                        </tr>
                        <tr style="border-top: 2px solid #333;">
                            <th colspan="6" class="text-right" style="font-weight: 700;">Closing Balance:</th>
                            <th class="text-right" id="closing_balance_debit" style="font-weight: 700; font-size: 12px;">0.00</th>
                            <th class="text-right" id="closing_balance_credit" style="font-weight: 700; font-size: 12px;">0.00</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Voucher Details Modal -->
<div class="modal fade" id="voucher_details_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">@lang('daybook::lang.voucher_details')</h4>
            </div>
            <div class="modal-body" id="voucher_details_body">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p>@lang('lang_v1.loading')</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    // Declare daybook_table variable first (will be initialized later)
    var daybook_table;
    
    // Initialize date range picker (primary method - like Exchange module)
    if (typeof dateRangeSettings !== 'undefined' && $('#date_range').length && typeof moment !== 'undefined') {
        // Set default to today
        var today = moment();
        var todayFormatted = today.format(moment_date_format);
        $('#date_range').val(todayFormatted + ' ~ ' + todayFormatted);
        // Sync hidden fields for backend compatibility
        $('#start_date').val(todayFormatted);
        $('#end_date').val(todayFormatted);
        
        $('#date_range').daterangepicker(
            {
                ...dateRangeSettings,
                startDate: today,
                endDate: today,
                autoUpdateInput: true
            },
            function (start, end) {
                var startFormatted = start.format(moment_date_format);
                var endFormatted = end.format(moment_date_format);
                $('#date_range').val(startFormatted + ' ~ ' + endFormatted);
                // Update hidden fields for backend
                $('#start_date').val(startFormatted);
                $('#end_date').val(endFormatted);
                // Auto-reload when date range changes (after table is initialized)
                if (typeof daybook_table !== 'undefined' && daybook_table) {
                    daybook_table.ajax.reload();
                }
            }
        );
        
        $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#date_range').val(todayFormatted + ' ~ ' + todayFormatted);
            $('#start_date').val(todayFormatted);
            $('#end_date').val(todayFormatted);
            if (typeof daybook_table !== 'undefined' && daybook_table) {
                daybook_table.ajax.reload();
            }
        });
    } else {
        // Fallback: if dateRangeSettings or moment.js not available, show alert
        console.warn('Date range picker requires dateRangeSettings and moment.js');
    }
    
    // Quick date filter buttons (like AdvancedReports modules)
    $('.quick-date').click(function() {
        var range = $(this).data('range');
        var start, end;
        
        if (typeof moment === 'undefined') {
            alert('Moment.js is required for quick date filters');
            return;
        }
        
        switch(range) {
            case 'today':
                start = end = moment();
                break;
            case 'yesterday':
                start = end = moment().subtract(1, 'days');
                break;
            case 'this_month':
                start = moment().startOf('month');
                end = moment().endOf('month');
                break;
            case 'last_month':
                start = moment().subtract(1, 'month').startOf('month');
                end = moment().subtract(1, 'month').endOf('month');
                break;
            case 'last_7_days':
                start = moment().subtract(6, 'days');
                end = moment();
                break;
            case 'last_30_days':
                start = moment().subtract(29, 'days');
                end = moment();
                break;
            default:
                start = end = moment();
        }
        
        // Update date range picker
        if ($('#date_range').length && $('#date_range').data('daterangepicker')) {
            $('#date_range').data('daterangepicker').setStartDate(start);
            $('#date_range').data('daterangepicker').setEndDate(end);
            $('#date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        }
        
        // Update hidden date fields for backend compatibility
        $('#start_date').val(start.format(moment_date_format));
        $('#end_date').val(end.format(moment_date_format));
        
        // Auto-reload data
        daybook_table.ajax.reload();
    });
    
    // Initialize DataTable (assign to previously declared variable)
    daybook_table = $('#daybook_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("daybook.getData") }}',
            type: 'GET',
            data: function(d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.location_id = $('#location_id').val();
                d.user_id = $('#user_id').val();
                d.account_id = $('#account_id').val();
                d.module_filter = $('#module_filter').val();
                d.scope = $('#scope').val();
            },
            error: function(xhr, error, thrown) {
                console.error('Daybook AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                alert('Error loading daybook data. Please check console for details.');
            }
        },
        columns: [
            { data: 'datetime', name: 'datetime' },
            { data: 'voucher_no', name: 'voucher_no' },
            { data: 'module', name: 'module' },
            { data: 'party', name: 'party' },
            { data: 'location', name: 'location' },
            { data: 'account', name: 'account' },
            { data: 'debit', name: 'debit', className: 'text-right' },
            { data: 'credit', name: 'credit', className: 'text-right' },
            { data: 'narration', name: 'narration' },
            { data: 'user', name: 'user' }
        ],
        order: [[0, 'asc']],
        paging: false,
        pageLength: -1,
        lengthMenu: [[-1], ["All"]],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();
            
            // Get summary data from server response
            var json = api.ajax.json();
            var opening_balance = json.opening_balance || 0;
            var current_period_debit = json.current_period_debit || 0;
            var current_period_credit = json.current_period_credit || 0;
            var closing_balance = json.closing_balance || 0;
            
            // Calculate totals from visible rows (for display purposes)
            var total_debit = 0;
            var total_credit = 0;

            // Sum debit column (index 6) - extract numeric value from HTML
            api.column(6, {page: 'current'}).data().each(function (value, index) {
                if (value && value !== '-') {
                    // Remove HTML tags and extract number
                    var cleanVal = String(value).replace(/<[^>]*>/g, '').trim();
                    if (cleanVal !== '-' && cleanVal !== '') {
                        // Extract numeric value (remove currency symbols, commas, etc.)
                        var numStr = cleanVal.replace(/[^\d.-]/g, '');
                        var num = parseFloat(numStr) || 0;
                        total_debit += Math.abs(num); // Use absolute value
                    }
                }
            });

            // Sum credit column (index 7) - extract numeric value from HTML
            api.column(7, {page: 'current'}).data().each(function (value, index) {
                if (value && value !== '-') {
                    var cleanVal = String(value).replace(/<[^>]*>/g, '').trim();
                    if (cleanVal !== '-' && cleanVal !== '') {
                        var numStr = cleanVal.replace(/[^\d.-]/g, '');
                        var num = parseFloat(numStr) || 0;
                        total_credit += Math.abs(num); // Use absolute value
                    }
                }
            });

            // Update footer with formatted totals
            $('#total_debit').html(__currency_trans_from_en(total_debit, true));
            $('#total_credit').html(__currency_trans_from_en(total_credit, true));
            
            // Update Opening Balance
            if (opening_balance >= 0) {
                $('#opening_balance_debit').html(__currency_trans_from_en(Math.abs(opening_balance), true));
                $('#opening_balance_credit').html('-');
            } else {
                $('#opening_balance_debit').html('-');
                $('#opening_balance_credit').html(__currency_trans_from_en(Math.abs(opening_balance), true));
            }
            
            // Update Current Total
            // Current Total = Current Period Transactions + Opening Balance (if exists)
            // If opening balance is 0 or not applicable, Current Total = Total
            var current_debit = current_period_debit || 0;
            var current_credit = current_period_credit || 0;
            
            // If opening balance is 0 or negligible, Current Total should equal Total
            if (Math.abs(opening_balance) < 0.01) {
                current_debit = total_debit;
                current_credit = total_credit;
            }
            
            $('#current_total_debit').html(__currency_trans_from_en(current_debit, true));
            $('#current_total_credit').html(__currency_trans_from_en(current_credit, true));
            
            // Update Closing Balance - show in Debit if positive, Credit if negative (plain text)
            var closing_balance_formatted = closing_balance || 0;
            
            if (closing_balance_formatted >= 0) {
                // Positive balance (Debit side)
                $('#closing_balance_debit').html(__currency_trans_from_en(Math.abs(closing_balance_formatted), true));
                $('#closing_balance_credit').html('-');
            } else {
                // Negative balance (Credit side)
                $('#closing_balance_debit').html('-');
                $('#closing_balance_credit').html(__currency_trans_from_en(Math.abs(closing_balance_formatted), true));
            }
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel-o"></i> Excel',
                className: 'btn-success'
            },
            {
                extend: 'pdf',
                text: '<i class="fa fa-file-pdf-o"></i> PDF',
                className: 'btn-danger'
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print"></i> Print',
                className: 'btn-info'
            }
        ]
    });

    // Filter button - reload with current dates if empty
    $('#filter_btn').click(function() {
        // Only set to current date if BOTH fields are empty (not if one is selected)
        var startVal = $('#start_date').val().trim();
        var endVal = $('#end_date').val().trim();
        
        if (!startVal && !endVal) {
            // Both empty - set to current date
            setCurrentDate();
        } else if (!startVal) {
            // Start date empty but end date selected - set start to end date or current (whichever is earlier)
            var endDate = $('#end_date').datepicker('getDate');
            if (endDate && endDate <= todayDate) {
                $('#start_date').datepicker('setDate', endDate);
            } else {
                $('#start_date').datepicker('setDate', todayDate);
            }
        } else if (!endVal) {
            // End date empty but start date selected - set end to start date or current (whichever is later)
            var startDate = $('#start_date').datepicker('getDate');
            if (startDate && startDate >= todayDate) {
                $('#end_date').datepicker('setDate', startDate);
            } else {
                $('#end_date').datepicker('setDate', todayDate);
            }
        }
        // If both dates are selected, use them as-is - don't reset to current
        daybook_table.ajax.reload();
    });

    // Reset button - set to current date and reload
    $('#reset_btn').click(function() {
        setCurrentDate();
        $('#location_id, #user_id, #account_id, #module_filter').val('').trigger('change');
        $('#scope').val('all').trigger('change');
        daybook_table.ajax.reload();
    });

    // Auto-reload on filter change (but not on date change - user must click filter button)
    $('#scope').change(function() {
        daybook_table.ajax.reload();
    });
    
    // Don't auto-reload on date change - user should click filter button
    // This prevents date from auto-switching when user tries to select manually

    // Export buttons
    $('#export_excel').click(function() {
        daybook_table.button('.buttons-excel').trigger();
    });

    $('#export_pdf').click(function() {
        daybook_table.button('.buttons-pdf').trigger();
    });

    // Voucher Details Modal Handler
    $(document).on('click', '.voucher-details-link', function(e) {
        e.preventDefault();
        var transaction_id = $(this).data('transaction-id');
        var payment_id = $(this).data('payment-id');
        var module = $(this).data('module');
        
        $('#voucher_details_modal').modal('show');
        $('#voucher_details_body').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>@lang("lang_v1.loading")</p></div>');
        
        $.ajax({
            url: '{{ route("daybook.voucherDetails") }}',
            type: 'GET',
            data: {
                transaction_id: transaction_id,
                payment_id: payment_id,
                module: module
            },
            success: function(response) {
                if (response.success && response.data) {
                    var data = response.data;
                    var html = '<div class="row">';
                    
                    // Transaction/Payment Header
                    html += '<div class="col-md-12">';
                    html += '<div class="box box-info">';
                    html += '<div class="box-header with-border">';
                    html += '<h3 class="box-title">' + (data.type ? data.type.toUpperCase().replace('_', ' ') : 'Payment') + ' Details</h3>';
                    html += '</div>';
                    html += '<div class="box-body">';
                    
                    // Basic Information
                    html += '<div class="row">';
                    html += '<div class="col-md-6">';
                    html += '<table class="table table-bordered">';
                    html += '<tr><th style="width: 40%;">Voucher No:</th><td>' + (data.invoice_no || data.voucher_no || '-') + '</td></tr>';
                    if (data.date) {
                        html += '<tr><th>Date:</th><td>' + data.date + '</td></tr>';
                    }
                    if (data.contact) {
                        html += '<tr><th>Contact:</th><td>' + data.contact + '</td></tr>';
                    }
                    if (data.location) {
                        html += '<tr><th>Location:</th><td>' + data.location + '</td></tr>';
                    }
                    html += '</table>';
                    html += '</div>';
                    html += '<div class="col-md-6">';
                    html += '<table class="table table-bordered">';
                    if (data.status) {
                        html += '<tr><th style="width: 40%;">Status:</th><td>' + data.status + '</td></tr>';
                    }
                    if (data.payment_status) {
                        html += '<tr><th>Payment Status:</th><td>' + data.payment_status + '</td></tr>';
                    }
                    if (data.method) {
                        html += '<tr><th>Payment Method:</th><td>' + data.method + '</td></tr>';
                    }
                    if (data.account) {
                        html += '<tr><th>Account:</th><td>' + data.account + '</td></tr>';
                    }
                    html += '</table>';
                    html += '</div>';
                    html += '</div>';
                    
                    // Transaction Lines (if available)
                    if (data.lines && data.lines.length > 0) {
                        html += '<div class="row" style="margin-top: 15px;">';
                        html += '<div class="col-md-12">';
                        html += '<h4>Items</h4>';
                        html += '<table class="table table-bordered table-striped">';
                        html += '<thead><tr><th>Product</th><th class="text-right">Quantity</th><th class="text-right">Unit Price</th><th class="text-right">Subtotal</th></tr></thead>';
                        html += '<tbody>';
                        $.each(data.lines, function(i, line) {
                            html += '<tr>';
                            html += '<td>' + (line.product || '-') + '</td>';
                            html += '<td class="text-right">' + (line.quantity || '-') + '</td>';
                            html += '<td class="text-right">' + (line.unit_price || '-') + '</td>';
                            html += '<td class="text-right">' + (line.subtotal || '-') + '</td>';
                            html += '</tr>';
                        });
                        html += '</tbody>';
                        html += '</table>';
                        html += '</div>';
                        html += '</div>';
                    }
                    
                    // Financial Summary (if available)
                    if (data.subtotal || data.total || data.amount) {
                        html += '<div class="row" style="margin-top: 15px;">';
                        html += '<div class="col-md-12">';
                        html += '<h4>Summary</h4>';
                        html += '<table class="table table-bordered">';
                        if (data.subtotal) {
                            html += '<tr><th class="text-right" style="width: 50%;">Subtotal:</th><td class="text-right">' + data.subtotal + '</td></tr>';
                        }
                        if (data.tax) {
                            html += '<tr><th class="text-right">Tax:</th><td class="text-right">' + data.tax + '</td></tr>';
                        }
                        if (data.discount) {
                            html += '<tr><th class="text-right">Discount:</th><td class="text-right">' + data.discount + '</td></tr>';
                        }
                        if (data.total) {
                            html += '<tr><th class="text-right"><strong>Total:</strong></th><td class="text-right"><strong>' + data.total + '</strong></td></tr>';
                        } else if (data.amount) {
                            html += '<tr><th class="text-right"><strong>Amount:</strong></th><td class="text-right"><strong>' + data.amount + '</strong></td></tr>';
                        }
                        html += '</table>';
                        html += '</div>';
                        html += '</div>';
                    }
                    
                    // Payments (if available)
                    if (data.payments && data.payments.length > 0) {
                        html += '<div class="row" style="margin-top: 15px;">';
                        html += '<div class="col-md-12">';
                        html += '<h4>Payments</h4>';
                        html += '<table class="table table-bordered table-striped">';
                        html += '<thead><tr><th>Method</th><th class="text-right">Amount</th><th>Date</th><th>Account</th></tr></thead>';
                        html += '<tbody>';
                        $.each(data.payments, function(i, payment) {
                            html += '<tr>';
                            html += '<td>' + (payment.method || '-') + '</td>';
                            html += '<td class="text-right">' + (payment.amount || '-') + '</td>';
                            html += '<td>' + (payment.date || '-') + '</td>';
                            html += '<td>' + (payment.account || '-') + '</td>';
                            html += '</tr>';
                        });
                        html += '</tbody>';
                        html += '</table>';
                        html += '</div>';
                        html += '</div>';
                    }
                    
                    // Notes
                    if (data.notes && data.notes !== '-') {
                        html += '<div class="row" style="margin-top: 15px;">';
                        html += '<div class="col-md-12">';
                        html += '<h4>Notes</h4>';
                        html += '<div class="well">' + data.notes + '</div>';
                        html += '</div>';
                        html += '</div>';
                    }
                    
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    
                    $('#voucher_details_body').html(html);
                } else {
                    $('#voucher_details_body').html('<div class="alert alert-danger">' + (response.msg || '@lang("lang_v1.data_not_found")') + '</div>');
                }
            },
            error: function(xhr) {
                var errorMsg = '@lang("messages.something_went_wrong")';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    errorMsg = xhr.responseJSON.msg;
                }
                $('#voucher_details_body').html('<div class="alert alert-danger">' + errorMsg + '</div>');
            }
        });
    });
});
</script>
@endsection
