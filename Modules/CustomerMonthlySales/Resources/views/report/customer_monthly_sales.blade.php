@extends('layouts.app')
@section('title', __('Customer Monthly Sales Report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('Customer Monthly Sales Report')
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    <!-- Filters -->
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('year', __('Year:')) !!}
                {!! Form::select('year', [], date('Y'), ['class' => 'form-control select2', 'id' => 'year_filter']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('customer', __('Customer:')) !!}
                {!! Form::select('customer', $customers, null, ['class' => 'form-control select2', 'id' => 'customer_filter', 'placeholder' => __('All Customers')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('payment_status', __('Payment Status:')) !!}
                {!! Form::select('payment_status', [], null, ['class' => 'form-control select2', 'id' => 'payment_status_filter']); !!}
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row" id="summary_cards">
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="info-box modern-widget widget-customers">
                <span class="info-box-icon modern-widget-icon">
                    <i class="fa fa-users"></i>
                </span>
                <div class="info-box-content modern-widget-content">
                    <span class="info-box-text modern-widget-text">Total Customers</span>
                    <span class="info-box-number modern-widget-number" id="summary_total_customers">0</span>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="info-box modern-widget widget-sales">
                <span class="info-box-icon modern-widget-icon">
                    <i class="fa fa-shopping-cart"></i>
                </span>
                <div class="info-box-content modern-widget-content">
                    <span class="info-box-text modern-widget-text">Total Transactions</span>
                    <span class="info-box-number modern-widget-number" id="summary_total_transactions">0</span>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="info-box modern-widget widget-transactions">
                <span class="info-box-icon modern-widget-icon">
                    <i class="fa fa-money"></i>
                </span>
                <div class="info-box-content modern-widget-content">
                    <span class="info-box-text modern-widget-text">Total Sales</span>
                    <span class="info-box-number modern-widget-number" id="summary_total_sales">0</span>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="info-box modern-widget widget-profit">
                <span class="info-box-icon modern-widget-icon">
                    <i class="fa fa-line-chart"></i>
                </span>
                <div class="info-box-content modern-widget-content">
                    <span class="info-box-text modern-widget-text">Total Profit</span>
                    <span class="info-box-number modern-widget-number" id="summary_total_profit">0</span>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="info-box modern-widget widget-margin">
                <span class="info-box-icon modern-widget-icon">
                    <i class="fa fa-percent"></i>
                </span>
                <div class="info-box-content modern-widget-content">
                    <span class="info-box-text modern-widget-text">Profit Margin</span>
                    <span class="info-box-number modern-widget-number" id="summary_profit_margin">0%</span>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="info-box modern-widget widget-average">
                <span class="info-box-icon modern-widget-icon">
                    <i class="fa fa-calculator"></i>
                </span>
                <div class="info-box-content modern-widget-content">
                    <span class="info-box-text modern-widget-text">Avg Sales/Customer</span>
                    <span class="info-box-number modern-widget-number" id="summary_avg_sales">0</span>
                </div>
            </div>
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped ajax_view" id="customer_monthly_sales_table">
                <thead>
                    <tr>
                        <th rowspan="2">Customer Name</th>
                        <th rowspan="2">Contact ID</th>
                        <th colspan="12" class="text-center">Monthly Sales</th>
                        <th rowspan="2">Total Sales</th>
                        <th rowspan="2">Total Cost</th>
                        <th rowspan="2">Gross Profit</th>
                        <th rowspan="2">Profit %</th>
                        <th rowspan="2">Transactions</th>
                        <th rowspan="2">Action</th>
                    </tr>
                    <tr>
                        <th>Jan</th>
                        <th>Feb</th>
                        <th>Mar</th>
                        <th>Apr</th>
                        <th>May</th>
                        <th>Jun</th>
                        <th>Jul</th>
                        <th>Aug</th>
                        <th>Sep</th>
                        <th>Oct</th>
                        <th>Nov</th>
                        <th>Dec</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    @endcomponent

</section>

<!-- Customer Details Modal -->
<div class="modal fade" id="customer_details_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog modal-lg" role="document" style="max-width: 1200px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modal_title">Customer Details</h4>
            </div>
            <div class="modal-body" id="customer_details_content">
                <p>Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- /.content -->
@stop

@section('javascript')
<script type="text/javascript">
    // Function to shorten numbers using Indian numbering conventions
    function shortenIndianNumber(number) {
        number = parseFloat(number);
        if (number < 1000) {
            return number.toFixed(2);
        } else if (number < 100000) {
            return (number / 1000).toFixed(2) + ' Th';
        } else if (number < 10000000) {
            return (number / 100000).toFixed(2) + ' L';
        } else {
            return (number / 10000000).toFixed(2) + ' Cr';
        }
    }

    $(document).ready(function() {
        // Populate year dropdown
        var currentYear = new Date().getFullYear();
        var yearOptions = '';
        for (var i = currentYear - 5; i <= currentYear + 1; i++) {
            yearOptions += '<option value="' + i + '"' + (i == currentYear ? ' selected' : '') + '>' + i + '</option>';
        }
        $('#year_filter').html(yearOptions);

        // Populate payment status dropdown
        $('#payment_status_filter').html('<option value="">All</option><option value="paid">Paid</option><option value="partial">Partial</option><option value="due">Due</option>');



        // Variable to store footer totals
        var footerTotalsData = null;

        // Create footer dynamically after DataTables initialization
        function createFooter() {
            var $table = $('#customer_monthly_sales_table');
            if ($table.find('tfoot').length === 0) {
                var footerHtml = '<tfoot><tr class="footer-totals-row">';
                footerHtml += '<th><strong>Total:</strong></th>';
                footerHtml += '<th></th>'; // Contact ID
                footerHtml += '<th class="text-right"></th>'; // Jan
                footerHtml += '<th class="text-right"></th>'; // Feb
                footerHtml += '<th class="text-right"></th>'; // Mar
                footerHtml += '<th class="text-right"></th>'; // Apr
                footerHtml += '<th class="text-right"></th>'; // May
                footerHtml += '<th class="text-right"></th>'; // Jun
                footerHtml += '<th class="text-right"></th>'; // Jul
                footerHtml += '<th class="text-right"></th>'; // Aug
                footerHtml += '<th class="text-right"></th>'; // Sep
                footerHtml += '<th class="text-right"></th>'; // Oct
                footerHtml += '<th class="text-right"></th>'; // Nov
                footerHtml += '<th class="text-right"></th>'; // Dec
                footerHtml += '<th class="text-right"></th>'; // Total Sales
                footerHtml += '<th class="text-right"></th>'; // Total Cost
                footerHtml += '<th class="text-right"></th>'; // Gross Profit
                footerHtml += '<th class="text-right"></th>'; // Profit %
                footerHtml += '<th class="text-right"></th>'; // Transactions
                footerHtml += '<th></th>'; // Action
                footerHtml += '</tr></tfoot>';
                $table.append(footerHtml);
            }
        }

        // Function to update footer totals
        function updateFooterTotals() {
            // Ensure footer exists
            createFooter();
            
            if (footerTotalsData) {
                var totals = footerTotalsData;
                var profitClass = totals.total_profit >= 0 ? 'text-success' : 'text-danger';
                var marginClass = totals.profit_margin >= 0 ? 'text-success' : 'text-danger';
                
                var $footer = $('#customer_monthly_sales_table tfoot tr.footer-totals-row');
                if ($footer.length && $footer.find('th').length >= 19) {
                    $footer.find('th').eq(2).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.jan || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(3).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.feb || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(4).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.mar || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(5).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.apr || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(6).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.may || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(7).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.jun || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(8).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.jul || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(9).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.aug || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(10).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.sep || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(11).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.oct || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(12).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.nov || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(13).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.dec || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(14).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.total_sales || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(15).html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(totals.total_cost || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(16).html('<span class="display_currency ' + profitClass + '" data-currency_symbol="true">' + parseFloat(totals.total_profit || 0).toFixed(2) + '</span>');
                    $footer.find('th').eq(17).html('<span class="' + marginClass + '">' + parseFloat(totals.profit_margin || 0).toFixed(2) + '%</span>');
                    $footer.find('th').eq(18).html(parseInt(totals.total_transactions || 0));
                    __currency_convert_recursively($('#customer_monthly_sales_table tfoot'));
                }
            }
        }

        // Initialize DataTable
        var customerMonthlySalesTable = $('#customer_monthly_sales_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            aaSorting: [[14, 'desc']], // Sort by Total Sales
            scrollX: true,
            "ajax": {
                "url": "{{ route('reports.customer-monthly-sales.data') }}",
                "data": function (d) {
                    d.year = $('#year_filter').val();
                    d.customer = $('#customer_filter').val();
                    d.payment_status = $('#payment_status_filter').val();
                    d = __datatable_ajax_callback(d);
                },
                "dataSrc": function (json) {
                    // Store footer totals from response
                    if (json.footer_totals) {
                        footerTotalsData = json.footer_totals;
                        // Update footer after data is loaded
                        setTimeout(function() {
                            updateFooterTotals();
                        }, 200);
                    }
                    return json.data;
                }
            },
            columns: [
                { data: 'customer_name', name: 'customer_name' },
                { data: 'contact_id', name: 'contact_id' },
                { data: 'jan', name: 'jan', orderable: false, searchable: false },
                { data: 'feb', name: 'feb', orderable: false, searchable: false },
                { data: 'mar', name: 'mar', orderable: false, searchable: false },
                { data: 'apr', name: 'apr', orderable: false, searchable: false },
                { data: 'may', name: 'may', orderable: false, searchable: false },
                { data: 'jun', name: 'jun', orderable: false, searchable: false },
                { data: 'jul', name: 'jul', orderable: false, searchable: false },
                { data: 'aug', name: 'aug', orderable: false, searchable: false },
                { data: 'sep', name: 'sep', orderable: false, searchable: false },
                { data: 'oct', name: 'oct', orderable: false, searchable: false },
                { data: 'nov', name: 'nov', orderable: false, searchable: false },
                { data: 'dec', name: 'dec', orderable: false, searchable: false },
                { data: 'total_sales_formatted', name: 'total_sales', orderable: true },
                { data: 'total_cost_formatted', name: 'total_cost', orderable: true },
                { data: 'total_profit_formatted', name: 'total_profit', orderable: true },
                { data: 'profit_margin_formatted', name: 'profit_margin', orderable: true },
                { data: 'total_transactions', name: 'total_transactions', orderable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#customer_monthly_sales_table'));
                loadSummary();
            },
            columnDefs: [
                { targets: [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13], className: 'text-right', width: '70px' },
                { targets: [14, 15, 16, 17], className: 'text-right', width: '100px' },
                { targets: [0], width: '200px' },
                { targets: [1], width: '100px' },
                { targets: [18], className: 'text-right', width: '80px' },
                { targets: [19], width: '80px' }
            ]
        });

        // Create footer after table is initialized
        setTimeout(function() {
            createFooter();
        }, 100);

        // Load summary statistics
        function loadSummary() {
            $.ajax({
                url: "{{ route('reports.customer-monthly-sales.summary') }}",
                data: {
                    year: $('#year_filter').val(),
                    customer: $('#customer_filter').val(),
                    payment_status: $('#payment_status_filter').val(),
                },
                dataType: 'json',
                success: function(data) {
                    $('#summary_total_customers').text(data.total_customers || 0);
                    $('#summary_total_transactions').text(data.total_transactions || 0);
                    var shortenedSales = shortenIndianNumber(data.total_sales || 0);
                    var salesHtml = shortenedSales.includes(' ') ?
                        '<span class="display_currency" data-currency_symbol="true">' + shortenedSales.split(' ')[0] + '</span> ' + shortenedSales.split(' ')[1] :
                        '<span class="display_currency" data-currency_symbol="true">' + shortenedSales + '</span>';
                    $('#summary_total_sales').html(salesHtml);
                    var profitValue = data.total_profit || 0;
                    var profitClass = profitValue >= 0 ? 'text-success' : 'text-danger';
                    var profitSign = profitValue < 0 ? '-' : '';
                    var absProfit = Math.abs(profitValue);
                    var shortenedProfit = shortenIndianNumber(absProfit);
                    var profitHtml;
                    if (shortenedProfit.includes(' ')) {
                        var parts = shortenedProfit.split(' ');
                        profitHtml = profitSign + '<span class="display_currency ' + profitClass + '" data-currency_symbol="true">' + parts[0] + '</span> ' + parts[1];
                    } else {
                        profitHtml = '<span class="display_currency ' + profitClass + '" data-currency_symbol="true">' + profitSign + shortenedProfit + '</span>';
                    }
                    $('#summary_total_profit').html(profitHtml);
                    var marginClass = data.profit_margin >= 0 ? 'text-success' : 'text-danger';
                    $('#summary_profit_margin').html('<span class="' + marginClass + '">' + parseFloat(data.profit_margin || 0).toFixed(2) + '%</span>');
                    var shortenedAvg = shortenIndianNumber(data.avg_sales || 0);
                    var avgHtml = shortenedAvg.includes(' ') ?
                        '<span class="display_currency" data-currency_symbol="true">' + shortenedAvg.split(' ')[0] + '</span> ' + shortenedAvg.split(' ')[1] :
                        '<span class="display_currency" data-currency_symbol="true">' + shortenedAvg + '</span>';
                    $('#summary_avg_sales').html(avgHtml);
                    __currency_convert_recursively($('#summary_cards'));
                },
                error: function() {
                    console.error('Error loading summary');
                }
            });
        }

        // Filter change handlers
        $(document).on('change', '#year_filter, #customer_filter, #payment_status_filter', function() {
            customerMonthlySalesTable.ajax.reload();
        });

        // Customer details modal
        $(document).on('click', '.view-customer-details', function() {
            var customerId = $(this).data('customer-id');
            var year = $('#year_filter').val();
            
            $('#customer_details_modal').modal('show');
            $('#customer_details_content').html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>');
            
            $.ajax({
                url: "{{ route('reports.customer-monthly-sales.details', ':id') }}".replace(':id', customerId),
                data: { year: year },
                dataType: 'json',
                success: function(data) {
                    var customerName = data.customer.name || 'N/A';
                    var businessName = data.customer.supplier_business_name || '';
                    
                    // Update modal title
                    var titleName = businessName ? businessName : customerName;
                    $('#modal_title').text('Customer Details - ' + titleName + ' (' + data.year + ')');
                    
                    var html = '<div class="row">';
                    
                    // Customer Information Section (Left)
                    html += '<div class="col-md-6">';
                    html += '<h5><i class="fa fa-user"></i> <strong>Customer Information</strong></h5>';
                    html += '<table class="table table-bordered" style="margin-bottom: 0;">';
                    html += '<tr><td style="width: 40%;"><strong>Name:</strong></td><td>' + customerName + '</td></tr>';
                    html += '<tr><td><strong>Business:</strong></td><td>' + (businessName || 'N/A') + '</td></tr>';
                    html += '<tr><td><strong>Contact ID:</strong></td><td>' + (data.customer.contact_id || 'N/A') + '</td></tr>';
                    html += '<tr><td><strong>Mobile:</strong></td><td>' + (data.customer.mobile || 'N/A') + '</td></tr>';
                    html += '<tr><td><strong>Email:</strong></td><td>' + (data.customer.email || 'N/A') + '</td></tr>';
                    
                    // Build address
                    var address = [];
                    if (data.customer.address_line_1) address.push(data.customer.address_line_1);
                    if (data.customer.address_line_2) address.push(data.customer.address_line_2);
                    if (data.customer.city) address.push(data.customer.city);
                    if (data.customer.state) address.push(data.customer.state);
                    if (data.customer.country) address.push(data.customer.country);
                    html += '<tr><td><strong>Address:</strong></td><td>' + (address.length > 0 ? address.join(', ') : 'N/A') + '</td></tr>';
                    html += '</table>';
                    html += '</div>';
                    
                    // Summary Section (Right)
                    html += '<div class="col-md-6">';
                    html += '<div class="box box-success" style="border-top: 3px solid #00a65a; border-radius: 3px;">';
                    html += '<div class="box-header with-border">';
                    html += '<h5 style="margin: 0;"><i class="fa fa-list"></i> <strong>' + data.year + ' Summary</strong></h5>';
                    html += '</div>';
                    html += '<div class="box-body" style="padding: 15px;">';
                    html += '<div class="row">';
                    
                    // Total Transactions Card
                    html += '<div class="col-md-6" style="margin-bottom: 15px; padding: 0 7px;">';
                    html += '<div style="background: white; border: 1px solid #ddd; border-radius: 3px; padding: 15px; text-align: center; height: 100px; display: flex; flex-direction: column; justify-content: center;">';
                    html += '<div style="margin-bottom: 8px;"><i class="fa fa-shopping-cart" style="color: #00a65a; font-size: 24px;"></i></div>';
                    html += '<div style="font-size: 20px; font-weight: 700; color: #204d74; margin-bottom: 5px;">' + (data.summary.total_transactions || 0) + '</div>';
                    html += '<div style="font-size: 11px; color: #777; text-transform: uppercase; font-weight: 600;">TOTAL TRANSACTIONS</div>';
                    html += '</div></div>';
                    
                    // Total Sales Card
                    html += '<div class="col-md-6" style="margin-bottom: 15px; padding: 0 7px;">';
                    html += '<div style="background: white; border: 1px solid #ddd; border-radius: 3px; padding: 15px; text-align: center; height: 100px; display: flex; flex-direction: column; justify-content: center;">';
                    html += '<div style="font-size: 20px; font-weight: 700; color: #204d74; margin-bottom: 5px;"><span class="display_currency" data-currency_symbol="true">' + parseFloat(data.summary.total_sales || 0).toFixed(2) + '</span></div>';
                    html += '<div style="font-size: 11px; color: #777; text-transform: uppercase; font-weight: 600;">TOTAL SALES</div>';
                    html += '</div></div>';
                    
                    // Avg Per Transaction Card
                    html += '<div class="col-md-6" style="margin-bottom: 15px; padding: 0 7px;">';
                    html += '<div style="background: white; border: 1px solid #ddd; border-radius: 3px; padding: 15px; text-align: center; height: 100px; display: flex; flex-direction: column; justify-content: center;">';
                    html += '<div style="margin-bottom: 8px;"><i class="fa fa-calculator" style="color: #3c8dbc; font-size: 24px;"></i></div>';
                    html += '<div style="font-size: 20px; font-weight: 700; color: #204d74; margin-bottom: 5px;"><span class="display_currency" data-currency_symbol="true">' + parseFloat(data.summary.avg_per_transaction || 0).toFixed(2) + '</span></div>';
                    html += '<div style="font-size: 11px; color: #777; text-transform: uppercase; font-weight: 600;">AVG PER TRANSACTION</div>';
                    html += '</div></div>';
                    
                    // Total Qty Card
                    html += '<div class="col-md-6" style="margin-bottom: 15px; padding: 0 7px;">';
                    html += '<div style="background: white; border: 1px solid #ddd; border-radius: 3px; padding: 15px; text-align: center; height: 100px; display: flex; flex-direction: column; justify-content: center;">';
                    html += '<div style="margin-bottom: 8px;"><i class="fa fa-cubes" style="color: #dd4b39; font-size: 24px;"></i></div>';
                    html += '<div style="font-size: 20px; font-weight: 700; color: #204d74; margin-bottom: 5px;">' + parseFloat(data.summary.total_qty || 0) + '</div>';
                    html += '<div style="font-size: 11px; color: #777; text-transform: uppercase; font-weight: 600;">TOTAL QTY</div>';
                    html += '</div></div>';
                    
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>'; // Close col-md-6 for Summary
                    html += '</div>'; // Close first row
                    
                    // Recent Transactions Section - Full Width
                    html += '<div class="row" style="margin-top: 20px;">';
                    html += '<div class="col-md-12">';
                    html += '<div style="margin-bottom: 15px;">';
                    html += '<h5 style="display: inline-block; margin: 0; font-size: 16px; font-weight: 600; color: #333;"><i class="fa fa-list" style="margin-right: 8px; color: #3c8dbc;"></i><strong>Recent Transactions</strong></h5>';
                    if (data.transactions && data.transactions.length > 0) {
                        html += '<span class="badge" style="float: right; background-color: #0073b7; color: white; padding: 5px 10px; border-radius: 3px; font-weight: normal;">' + data.transactions.length + ' transactions</span>';
                    }
                    html += '</div>';
                    
                    if (data.transactions && data.transactions.length > 0) {
                        html += '<div class="table-responsive" style="width: 100%;">';
                        html += '<table class="table table-bordered" style="margin-bottom: 15px; background: white; width: 100%;">';
                        html += '<thead style="background-color: #f4f4f4;">';
                        html += '<tr>';
                        html += '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; color: #333;">Date</th>';
                        html += '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; color: #333;">Invoice</th>';
                        html += '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; color: #333;">Product</th>';
                        html += '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; color: #333;">Month</th>';
                        html += '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; color: #333;">Qty</th>';
                        html += '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; color: #333;">Unit Price</th>';
                        html += '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; color: #333;">Line Total</th>';
                        html += '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; color: #333;">Status</th>';
                        html += '</tr>';
                        html += '</thead>';
                        html += '<tbody>';
                        
                        var totalLineTotal = 0;
                        $.each(data.transactions, function(i, transaction) {
                            var dateObj = new Date(transaction.date);
                            var formattedDate = (dateObj.getMonth() + 1) + '/' + dateObj.getDate() + '/' + dateObj.getFullYear();
                            
                            totalLineTotal += parseFloat(transaction.line_total || 0);
                            
                            var statusClass = 'label-default';
                            var statusBgColor = '#777';
                            var statusText = transaction.payment_status || 'N/A';
                            if (statusText.toLowerCase() === 'paid') {
                                statusClass = 'label-success';
                                statusBgColor = '#00a65a';
                            } else if (statusText.toLowerCase() === 'partial') {
                                statusClass = 'label-warning';
                                statusBgColor = '#f39c12';
                            } else if (statusText.toLowerCase() === 'due') {
                                statusClass = 'label-danger';
                                statusBgColor = '#dd4b39';
                            }
                            
                            html += '<tr style="background: white;">';
                            html += '<td style="border: 1px solid #ddd; padding: 8px;">' + formattedDate + '</td>';
                            html += '<td style="border: 1px solid #ddd; padding: 8px;"><strong>' + (transaction.invoice_no || 'N/A') + '</strong></td>';
                            html += '<td style="border: 1px solid #ddd; padding: 8px;">' + (transaction.product_name || 'N/A') + '</td>';
                            html += '<td style="border: 1px solid #ddd; padding: 8px;"><span class="badge" style="background-color: #3c8dbc; color: white; padding: 4px 8px; border-radius: 12px; font-weight: normal;">' + (transaction.month || 'N/A') + '</span></td>';
                            html += '<td style="border: 1px solid #ddd; padding: 8px;">' + parseFloat(transaction.quantity || 0) + '</td>';
                            html += '<td style="border: 1px solid #ddd; padding: 8px;" class="display_currency" data-currency_symbol="true">' + parseFloat(transaction.unit_price || 0).toFixed(2) + '</td>';
                            html += '<td style="border: 1px solid #ddd; padding: 8px;" class="display_currency" data-currency_symbol="true">' + parseFloat(transaction.line_total || 0).toFixed(2) + '</td>';
                            html += '<td style="border: 1px solid #ddd; padding: 8px;"><span class="badge" style="background-color: ' + statusBgColor + '; color: white; padding: 4px 8px; border-radius: 12px; font-weight: normal;">' + statusText.toUpperCase() + '</span></td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody>';
                        html += '</table>';
                        html += '</div>';
                        html += '<div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">';
                        html += '<p style="margin: 0; color: #777; font-size: 13px;"><i class="fa fa-info-circle" style="margin-right: 5px; color: #999;"></i>Showing recent ' + data.transactions.length + ' transactions for ' + data.year + '</p>';
                        html += '<p style="margin: 0; font-weight: 600; color: #333;"><strong>Total: <span class="display_currency" data-currency_symbol="true">' + parseFloat(totalLineTotal).toFixed(2) + '</span></strong></p>';
                        html += '</div>';
                    } else {
                        html += '<p class="text-muted">No transactions found for this customer in ' + data.year + '</p>';
                    }
                    html += '</div></div>';
                    
                    $('#customer_details_content').html(html);
                    __currency_convert_recursively($('#customer_details_modal'));
                },
                error: function(xhr, status, error) {
                    console.error('Error loading customer details:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    var errorMsg = 'Error loading customer details';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ': ' + xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg += ': ' + xhr.responseJSON.error;
                    }
                    $('#customer_details_content').html('<p class="text-danger text-center" style="padding: 20px;">' + errorMsg + '</p>');
                }
            });
        });

        // Initial summary load
        loadSummary();
    });
</script>

<style>
    /* Modern Widget Styles */
    .modern-widget {
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        height: 100px;
        display: flex;
        align-items: center;
        padding: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
    }

    .modern-widget:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    }

    .modern-widget-icon {
        font-size: 45px;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        margin-right: 15px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
    }

    .modern-widget-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .modern-widget-text {
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
        opacity: 0.9;
    }

    .modern-widget-number {
        font-size: 28px;
        font-weight: 700;
        line-height: 1.2;
    }

    .widget-customers {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }

    .widget-sales {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
    }

    .widget-transactions {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    }

    .widget-profit {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%) !important;
    }

    .widget-margin {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
    }

    .widget-average {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%) !important;
        color: #333 !important;
    }

    .widget-average * {
        color: #333 !important;
        text-shadow: none !important;
    }

    /* Force all text to be white except average */
    .modern-widget:not(.widget-average) * {
        color: white !important;
    }

    /* Enhanced Mobile Responsive */
    @media (max-width: 768px) {
        .modern-widget {
            height: 85px !important;
            padding: 14px !important;
            border-radius: 10px !important;
        }

        .modern-widget-icon {
            font-size: 36px !important;
            width: 45px !important;
            margin-right: 12px !important;
        }

        .modern-widget-text {
            font-size: 11px !important;
            letter-spacing: 0.5px !important;
        }

        .modern-widget-number {
            font-size: 22px !important;
        }

        .modern-widget-content {
            min-height: 55px !important;
        }
    }

    @media (max-width: 992px) {
        .modern-widget {
            height: 90px !important;
            padding: 16px !important;
        }

        .modern-widget-icon {
            font-size: 40px !important;
            width: 50px !important;
        }

        .modern-widget-text {
            font-size: 12px !important;
        }

        .modern-widget-number {
            font-size: 24px !important;
        }
    }

    /* Ensure Bootstrap columns are equal height */
    .row {
        display: flex;
        flex-wrap: wrap;
    }

    .col-xl-2,
    .col-lg-4,
    .col-md-6,
    .col-sm-6,
    .col-xs-12 {
        display: flex;
        flex-direction: column;
    }

    /* Footer Totals Row Styling */
    .footer-totals-row {
        background-color: #f5f5f5 !important;
        font-weight: bold;
    }

    .footer-totals-row th {
        background-color: #f5f5f5 !important;
        border-top: 2px solid #ddd !important;
        padding: 10px 8px !important;
    }

    #customer_monthly_sales_table tfoot th {
        background-color: #f5f5f5 !important;
    }

    /* Customer Details Modal Styling */
    #customer_details_modal .modal-body h5 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 16px;
        font-weight: 600;
    }

    #customer_details_modal .modal-body h5 i {
        margin-right: 8px;
        color: #3c8dbc;
    }

    #customer_details_modal .info-box {
        min-height: 80px;
        margin-bottom: 15px;
    }

    #customer_details_modal .info-box-icon {
        width: 70px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
    }

    #customer_details_modal .info-box-content {
        padding-left: 10px;
    }

    #customer_details_modal .info-box-text {
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 600;
    }

    #customer_details_modal .info-box-number {
        font-size: 24px;
        font-weight: 700;
    }

    #customer_details_modal .table-bordered {
        border: 1px solid #ddd;
    }

    #customer_details_modal .table-bordered td {
        border: 1px solid #ddd;
        padding: 8px;
    }

    #customer_details_modal .badge.bg-light-blue {
        background-color: #3c8dbc;
        color: white;
        padding: 4px 8px;
        border-radius: 3px;
    }

    #customer_details_modal .badge.bg-blue {
        background-color: #0073b7;
        color: white;
        padding: 5px 10px;
        border-radius: 3px;
    }

    /* 2025 Summary Section Styling */
    #customer_details_modal .box {
        background: white;
        border: 1px solid #d2d6de;
        border-radius: 3px;
        box-shadow: 0 1px 1px rgba(0,0,0,.1);
        margin-bottom: 20px;
    }

    #customer_details_modal .box-success {
        border-top: 3px solid #00a65a !important;
    }

    #customer_details_modal .box-header {
        color: #444;
        display: block;
        padding: 10px;
        position: relative;
        border-bottom: 1px solid #f4f4f4;
    }

    #customer_details_modal .box-header.with-border {
        border-bottom: 1px solid #f4f4f4;
    }

    #customer_details_modal .box-body {
        border-top-left-radius: 0;
        border-top-right-radius: 0;
        border-bottom-left-radius: 3px;
        border-bottom-right-radius: 3px;
        padding: 10px;
    }

    #customer_details_modal .box-body .row {
        margin-left: -7px;
        margin-right: -7px;
    }

    /* Recent Transactions Full Width */
    #customer_details_modal .table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    #customer_details_modal .table {
        width: 100% !important;
        table-layout: auto;
    }
</style>
@endsection

