@extends('layouts.app')

@section('title', __('daybook::lang.monthly_dashboard'))

@section('content')
<section class="content-header">
    <h1>@lang('daybook::lang.monthly_dashboard_report')</h1>
</section>

<section class="content">
    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('daybook::lang.monthly_dashboard_report')</h3>
            <div class="box-tools">
                <button type="button" class="btn btn-sm btn-info" onclick="window.print()">
                    <i class="fa fa-print"></i> @lang('messages.print')
                </button>
            </div>
        </div>

        <div class="box-body">
            <div class="row">
                <!-- Date Range Picker -->
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
                
                <!-- Hidden date fields -->
                {!! Form::hidden('start_date', '', ['id' => 'start_date']) !!}
                {!! Form::hidden('end_date', '', ['id' => 'end_date']) !!}
                
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
                        {!! Form::label('account_id', __('account.accounts') . ':') !!}
                        {!! Form::select('account_id', $accounts, null, 
                            ['class' => 'form-control select2', 'id' => 'account_id', 'placeholder' => __('messages.all')]) 
                        !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('scope', __('daybook::lang.balance_scope') . ':') !!}
                        {!! Form::select('scope', [
                            'cash_bank' => __('daybook::lang.cash_bank'),
                            'cash_only' => __('daybook::lang.cash_only'),
                            'bank_only' => __('daybook::lang.bank_only'),
                            'all' => __('daybook::lang.all_accounts')
                        ], 'all', ['class' => 'form-control select2', 'id' => 'scope'])
                        !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <button type="button" class="btn btn-primary" id="filter_btn">
                        <i class="fa fa-filter"></i> @lang('daybook::lang.filter')
                    </button>
                    <button type="button" class="btn btn-default" id="reset_btn">
                        <i class="fa fa-refresh"></i> @lang('daybook::lang.reset')
                    </button>
                </div>
            </div>

            <hr>

            <!-- Summary Cards -->
            <div class="row" id="summary_cards" style="display: none;">
                <div class="col-md-3">
                    <div class="info-box bg-green">
                        <span class="info-box-icon"><i class="fa fa-arrow-down"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">@lang('daybook::lang.total_cash_in')</span>
                            <span class="info-box-number" id="total_cash_in">0.00</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-red">
                        <span class="info-box-icon"><i class="fa fa-arrow-up"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">@lang('daybook::lang.total_cash_out')</span>
                            <span class="info-box-number" id="total_cash_out">0.00</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-blue">
                        <span class="info-box-icon"><i class="fa fa-balance-scale"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">@lang('daybook::lang.net_balance')</span>
                            <span class="info-box-number" id="net_balance">0.00</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-yellow">
                        <span class="info-box-icon"><i class="fa fa-book"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">@lang('daybook::lang.opening_balance')</span>
                            <span class="info-box-number" id="opening_balance">0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Summary Table -->
            <div class="row" id="dashboard_section" style="display: none; margin-top: 20px;">
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('daybook::lang.monthly_summary')</h3>
                        </div>
                        <div class="box-body table-responsive">
                            <table class="table table-bordered table-striped" id="monthly_dashboard_table">
                                <thead>
                                    <tr style="background-color: #f4f4f4;">
                                        <th style="width: 25%;">@lang('daybook::lang.month')</th>
                                        <th style="text-align: right;">@lang('daybook::lang.cash_in')</th>
                                        <th style="text-align: right;">@lang('daybook::lang.cash_out')</th>
                                        <th style="text-align: right;">@lang('daybook::lang.net_balance')</th>
                                    </tr>
                                </thead>
                                <tbody id="monthly_dashboard_tbody">
                                    <!-- Data will be loaded here -->
                                </tbody>
                                <tfoot id="monthly_dashboard_footer" style="display: none;">
                                    <tr style="background-color: #f0f0f0; font-weight: bold;">
                                        <td>@lang('daybook::lang.grand_total')</td>
                                        <td style="text-align: right;" id="footer_cash_in">0.00</td>
                                        <td style="text-align: right;" id="footer_cash_out">0.00</td>
                                        <td style="text-align: right;" id="footer_net_balance">0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="row" id="chart_section" style="display: none; margin-top: 20px;">
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('daybook::lang.monthly_summary') - @lang('daybook::lang.cash_in') / @lang('daybook::lang.cash_out')</h3>
                        </div>
                        <div class="box-body">
                            <canvas id="dashboard_chart" height="80"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading indicator -->
            <div class="row" id="loading_section" style="display: none;">
                <div class="col-md-12 text-center">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p>@lang('lang_v1.loading')</p>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        var dashboardChart = null;
        
        // Calculate financial year dates
        var currentYear = moment().year();
        var currentMonth = moment().month() + 1; // 1-12
        var startYear, endYear;
        if (currentMonth >= 4) {
            startYear = currentYear;
            endYear = currentYear + 1;
        } else {
            startYear = currentYear - 1;
            endYear = currentYear;
        }
        var start = moment(startYear + '-04-01');
        var end = moment(endYear + '-03-31');

        // Initialize date range picker
        $('#date_range').daterangepicker({
            locale: {
                format: 'DD/MM/YYYY'
            },
            ranges: {
                'This Year': [moment().startOf('year'), moment().endOf('year')],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                'Last 6 Months': [moment().subtract(6, 'months'), moment()],
                'Last 12 Months': [moment().subtract(12, 'months'), moment()]
            },
            startDate: start,
            endDate: end
        }, function(start, end) {
            $('#start_date').val(start.format('YYYY-MM-DD'));
            $('#end_date').val(end.format('YYYY-MM-DD'));
        });

        // Set initial dates
        $('#start_date').val(start.format('YYYY-MM-DD'));
        $('#end_date').val(end.format('YYYY-MM-DD'));
        $('#date_range').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));

        // Filter button click
        $('#filter_btn').click(function() {
            loadDashboard();
        });

        // Reset button click
        $('#reset_btn').click(function() {
            $('#date_range').val('');
            $('#start_date').val('');
            $('#end_date').val('');
            $('#location_id').val('').trigger('change');
            $('#account_id').val('').trigger('change');
            $('#scope').val('all').trigger('change');
            $('#summary_cards').hide();
            $('#dashboard_section').hide();
            $('#chart_section').hide();
            if (dashboardChart) {
                dashboardChart.destroy();
                dashboardChart = null;
            }
        });

        // Load on page load
        loadDashboard();

        function loadDashboard() {
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            var location_id = $('#location_id').val();
            var account_id = $('#account_id').val();
            var scope = $('#scope').val();

            if (!start_date || !end_date) {
                toastr.error('@lang("lang_v1.please_select_date_range")');
                return;
            }

            $('#loading_section').show();
            $('#summary_cards').hide();
            $('#dashboard_section').hide();
            $('#chart_section').hide();

            $.ajax({
                url: "{{ action([\Modules\Daybook\Http\Controllers\DaybookController::class, 'getMonthlyDashboardData']) }}",
                method: 'GET',
                data: {
                    start_date: start_date,
                    end_date: end_date,
                    location_id: location_id,
                    account_id: account_id,
                    scope: scope
                },
                success: function(response) {
                    $('#loading_section').hide();
                    
                    if (response.success && response.data) {
                        renderSummaryCards(response.data);
                        renderTable(response.data);
                        renderChart(response.data);
                        $('#summary_cards').show();
                        $('#dashboard_section').show();
                        $('#chart_section').show();
                    } else {
                        toastr.error(response.msg || '@lang("messages.something_went_wrong")');
                    }
                },
                error: function(xhr) {
                    $('#loading_section').hide();
                    var errorMsg = xhr.responseJSON && xhr.responseJSON.msg 
                        ? xhr.responseJSON.msg 
                        : '@lang("messages.something_went_wrong")';
                    toastr.error(errorMsg);
                }
            });
        }

        function renderSummaryCards(data) {
            $('#total_cash_in').text(formatMoney(data.total_cash_in));
            $('#total_cash_out').text(formatMoney(data.total_cash_out));
            $('#net_balance').text(formatMoney(data.net_balance));
            $('#opening_balance').text(formatMoney(data.opening_balance || 0));
        }

        function renderTable(data) {
            var tbody = $('#monthly_dashboard_tbody');
            tbody.empty();

            if (!data.monthly_data || data.monthly_data.length === 0) {
                tbody.append('<tr><td colspan="4" class="text-center">@lang("lang_v1.no_data_available")</td></tr>');
                return;
            }

            var totalCashIn = 0;
            var totalCashOut = 0;

            data.monthly_data.forEach(function(row) {
                var tr = $('<tr>');
                
                tr.append($('<td>').text(row.month_name));
                
                var cashInTd = $('<td>').css('text-align', 'right').text(formatMoney(row.cash_in));
                if (row.cash_in > 0) {
                    cashInTd.css('color', '#5cb85c');
                }
                tr.append(cashInTd);
                
                var cashOutTd = $('<td>').css('text-align', 'right').text(formatMoney(row.cash_out));
                if (row.cash_out > 0) {
                    cashOutTd.css('color', '#d9534f');
                }
                tr.append(cashOutTd);
                
                var netTd = $('<td>').css('text-align', 'right');
                var net = row.net_balance;
                if (net >= 0) {
                    netTd.css('color', '#5cb85c').text(formatMoney(net));
                } else {
                    netTd.css('color', '#d9534f').text('(' + formatMoney(Math.abs(net)) + ')');
                }
                tr.append(netTd);

                tbody.append(tr);
                
                totalCashIn += row.cash_in;
                totalCashOut += row.cash_out;
            });

            // Update footer
            $('#footer_cash_in').text(formatMoney(totalCashIn));
            $('#footer_cash_out').text(formatMoney(totalCashOut));
            $('#footer_net_balance').text(formatMoney(totalCashIn - totalCashOut));
            $('#monthly_dashboard_footer').show();
        }

        function renderChart(data) {
            // Destroy existing chart
            if (dashboardChart) {
                dashboardChart.destroy();
            }

            var months = [];
            var cashIn = [];
            var cashOut = [];

            data.monthly_data.forEach(function(row) {
                months.push(row.month_name.substring(0, 3)); // First 3 letters
                cashIn.push(row.cash_in);
                cashOut.push(row.cash_out);
            });

            if (months.length === 0) {
                return;
            }

            var ctx = document.getElementById('dashboard_chart').getContext('2d');
            dashboardChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: '@lang("daybook::lang.cash_in")',
                            data: cashIn,
                            backgroundColor: 'rgba(92, 184, 92, 0.8)',
                            borderColor: 'rgba(92, 184, 92, 1)',
                            borderWidth: 1
                        },
                        {
                            label: '@lang("daybook::lang.cash_out")',
                            data: cashOut,
                            backgroundColor: 'rgba(220, 53, 69, 0.8)',
                            borderColor: 'rgba(220, 53, 69, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    return formatMoney(value);
                                }
                            }
                        }],
                        xAxes: [{
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                return data.datasets[tooltipItem.datasetIndex].label + ': ' + formatMoney(tooltipItem.yLabel);
                            }
                        }
                    }
                }
            });
        }

        function formatMoney(amount) {
            return parseFloat(amount).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    });
</script>
@endsection

