@extends('layouts.app')

@section('title', __('daybook::lang.monthly_cashbook'))

@section('content')
<section class="content-header">
    <h1>@lang('daybook::lang.monthly_cashbook_report')</h1>
</section>

<section class="content">
    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('daybook::lang.monthly_cashbook_report')</h3>
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
                <!-- Month Selector (Primary Filter) -->
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('month_filter', __('daybook::lang.select_month') . ':') !!}
                        {!! Form::select('month_filter', [
                            '' => __('messages.select'),
                            '01' => __('January'),
                            '02' => __('February'),
                            '03' => __('March'),
                            '04' => __('April'),
                            '05' => __('May'),
                            '06' => __('June'),
                            '07' => __('July'),
                            '08' => __('August'),
                            '09' => __('September'),
                            '10' => __('October'),
                            '11' => __('November'),
                            '12' => __('December'),
                        ], null, [
                            'class' => 'form-control select2', 
                            'id' => 'month_filter',
                            'placeholder' => __('daybook::lang.select_month')
                        ]) !!}
                    </div>
                </div>
                
                <!-- Year Selector -->
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('year_filter', __('lang_v1.year') . ':') !!}
                        {!! Form::select('year_filter', $years, date('Y'), [
                            'class' => 'form-control select2', 
                            'id' => 'year_filter'
                        ]) !!}
                    </div>
                </div>
                
                <!-- Date Range Picker (Alternative) -->
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
                        {!! Form::select('account_id', $accounts, $default_account,
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

            <!-- Report Table -->
            <div class="row" id="report_section" style="display: none;">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="monthly_cashbook_table" style="width: 100%;">
                            <thead>
                                <tr style="background-color: #f4f4f4;">
                                    <th style="text-align: left; width: 25%;">@lang('daybook::lang.particulars')</th>
                                    <th colspan="2" style="text-align: center;">@lang('daybook::lang.transactions')</th>
                                    <th style="text-align: right; width: 20%;">@lang('daybook::lang.closing_balance')</th>
                                </tr>
                                <tr style="background-color: #f9f9f9;">
                                    <th></th>
                                    <th style="text-align: right;">@lang('daybook::lang.debit')</th>
                                    <th style="text-align: right;">@lang('daybook::lang.credit')</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="monthly_cashbook_tbody">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="row" id="chart_section" style="display: none; margin-top: 30px;">
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('daybook::lang.grand_total')</h3>
                        </div>
                        <div class="box-body" style="padding: 20px; height: 400px;">
                            <!-- Horizontal Bar Chart in Footer -->
                            <canvas id="monthly_chart_horizontal"></canvas>
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
        var monthlyChart = null;
        
        // Initialize date range picker
        $('#date_range').daterangepicker({
            locale: {
                format: 'DD/MM/YYYY'
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'This Year': [moment().startOf('year'), moment().endOf('year')],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
            },
            startDate: moment().startOf('year'),
            endDate: moment()
        }, function(start, end) {
            $('#start_date').val(start.format('YYYY-MM-DD'));
            $('#end_date').val(end.format('YYYY-MM-DD'));
            // Clear month filter when manual date range is selected
            $('#month_filter').val('').trigger('change');
        });

        // Set initial dates (current year to current date)
        var start = moment().startOf('year');
        var end = moment();
        $('#start_date').val(start.format('YYYY-MM-DD'));
        $('#end_date').val(end.format('YYYY-MM-DD'));
        $('#date_range').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
        
        // Set current month in month filter
        $('#month_filter').val(moment().format('MM')).trigger('change');

        // Set default Balance Scope to All
        $('#scope').val('all').trigger('change');

        // Month filter change handler
        $('#month_filter').on('change', function() {
            var month = $(this).val();
            var year = $('#year_filter').val() || moment().format('YYYY');
            
            if (month) {
                // Set date range to selected month
                var start = moment(year + '-' + month + '-01', 'YYYY-MM-DD').startOf('month');
                var end = moment(year + '-' + month + '-01', 'YYYY-MM-DD').endOf('month');
                
                $('#start_date').val(start.format('YYYY-MM-DD'));
                $('#end_date').val(end.format('YYYY-MM-DD'));
                $('#date_range').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                
                // Auto-load data when month is selected
                loadMonthlyCashbook();
            }
        });

        // Year filter change handler
        $('#year_filter').on('change', function() {
            var month = $('#month_filter').val();
            var year = $(this).val() || moment().format('YYYY');
            
            if (month) {
                // Set date range to selected month and year
                var start = moment(year + '-' + month + '-01', 'YYYY-MM-DD').startOf('month');
                var end = moment(year + '-' + month + '-01', 'YYYY-MM-DD').endOf('month');
                
                $('#start_date').val(start.format('YYYY-MM-DD'));
                $('#end_date').val(end.format('YYYY-MM-DD'));
                $('#date_range').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                
                // Auto-load data when year is changed
                loadMonthlyCashbook();
            }
        });

        // Filter button click
        $('#filter_btn').click(function() {
            loadMonthlyCashbook();
        });

        // Reset button click
        $('#reset_btn').click(function() {
            $('#date_range').val('');
            $('#start_date').val('');
            $('#end_date').val('');
            $('#month_filter').val('').trigger('change');
            $('#year_filter').val(moment().format('YYYY')).trigger('change');
            $('#location_id').val('').trigger('change');
            $('#account_id').val('{{ $default_account }}').trigger('change');
            $('#scope').val('all').trigger('change');
            $('#report_section').hide();
            $('#chart_section').hide();
            if (monthlyChart) {
                monthlyChart.destroy();
                monthlyChart = null;
            }
        });

        // Load data on page load (optional)
        // loadMonthlyCashbook();

        function loadMonthlyCashbook() {
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
            $('#report_section').hide();
            $('#chart_section').hide();

            $.ajax({
                url: "{{ action([\Modules\Daybook\Http\Controllers\DaybookController::class, 'getMonthlyCashbookData']) }}",
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
                        renderTable(response.data);
                        // Small delay to ensure DOM is ready for chart
                        setTimeout(function() {
                            renderChart(response.data);
                        }, 100);
                        $('#report_section').show();
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

        function renderTable(data) {
            var tbody = $('#monthly_cashbook_tbody');
            tbody.empty();

            if (!data.monthly_data || data.monthly_data.length === 0) {
                tbody.append('<tr><td colspan="4" class="text-center">@lang("lang_v1.no_data_available")</td></tr>');
                return;
            }

            var accountName = $('#account_id option:selected').text();
            if (accountName && accountName !== '@lang("messages.all")') {
                // Show account name in header
                var headerHtml = '<tr><td colspan="4" style="font-weight: bold; text-align: center; background-color: #e8e8e8; padding: 10px;">';
                headerHtml += accountName + ' - @lang("daybook::lang.monthly_cashbook_report")';
                headerHtml += '</td></tr>';
                tbody.append(headerHtml);
            }

            data.monthly_data.forEach(function(row) {
                var tr = $('<tr>');
                
                // Style based on row type
                if (row.is_opening || row.is_total) {
                    tr.css('font-weight', 'bold');
                    tr.css('background-color', row.is_total ? '#f0f0f0' : '#f9f9f9');
                }

                // Particulars column
                var particularsTd = $('<td>').text(row.particulars);
                if (row.is_opening || row.is_total) {
                    particularsTd.css('font-weight', 'bold');
                }
                tr.append(particularsTd);

                // Debit column
                var debitTd = $('<td>').css('text-align', 'right');
                if (row.debit > 0) {
                    debitTd.text(formatMoney(row.debit));
                } else {
                    debitTd.text('-');
                }
                tr.append(debitTd);

                // Credit column
                var creditTd = $('<td>').css('text-align', 'right');
                if (row.credit > 0) {
                    creditTd.text(formatMoney(row.credit));
                } else {
                    creditTd.text('-');
                }
                tr.append(creditTd);

                // Closing Balance column
                var balanceTd = $('<td>').css('text-align', 'right');
                var balance = row.closing_balance;
                if (balance >= 0) {
                    balanceTd.html(formatMoney(Math.abs(balance)) + ' <span style="color: #d9534f;">Dr</span>');
                } else {
                    balanceTd.html(formatMoney(Math.abs(balance)) + ' <span style="color: #5cb85c;">Cr</span>');
                }
                if (row.is_opening || row.is_total) {
                    balanceTd.css('font-weight', 'bold');
                }
                tr.append(balanceTd);

                tbody.append(tr);
            });
        }

        function renderChart(data) {
            // Destroy existing chart
            if (monthlyChart) {
                monthlyChart.destroy();
                monthlyChart = null;
            }

            var chartData = [];
            var chartLabels = [];

            // Extract chart data from monthly_data (exclude opening and total rows)
            data.monthly_data.forEach(function(row) {
                if (!row.is_opening && !row.is_total && row.chart_data) {
                    chartLabels.push(row.particulars.substring(0, 3)); // First 3 letters of month (Apr, May, etc.)
                    chartData.push(row.chart_data.net); // Net change (debit - credit)
                }
            });

            console.log('Chart Data:', chartData);
            console.log('Chart Labels:', chartLabels);

            if (chartData.length === 0) {
                console.warn('No chart data available');
                return;
            }

            // Create horizontal bar chart (bars extend left/right, months on Y-axis)
            var canvasEl = document.getElementById('monthly_chart_horizontal');
            if (!canvasEl) {
                console.error('Chart canvas element not found!');
                return;
            }
            
            var ctx = canvasEl.getContext('2d');
            console.log('Creating chart with', chartData.length, 'data points');
            
            monthlyChart = new Chart(ctx, {
                type: 'horizontalBar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: '@lang("daybook::lang.grand_total")',
                        data: chartData,
                        backgroundColor: 'rgba(220, 53, 69, 0.8)', // Red color for all bars
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    return formatMoney(value);
                                }
                            },
                            gridLines: {
                                zeroLineColor: '#000',
                                zeroLineWidth: 2,
                                display: true,
                                drawBorder: true
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                maxRotation: 0,
                                minRotation: 0
                            },
                            gridLines: {
                                display: false
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var value = tooltipItem.xLabel;
                                var month = data.labels[tooltipItem.index];
                                return month + ': ' + formatMoney(value);
                            }
                        }
                    },
                    legend: {
                        display: false
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

