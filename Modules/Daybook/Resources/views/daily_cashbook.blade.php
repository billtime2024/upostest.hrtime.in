@extends('layouts.app')

@section('title', __('daybook::lang.daily_cashbook'))

@section('content')
<section class="content-header">
    <h1>@lang('daybook::lang.daily_cashbook_report')</h1>
</section>

<section class="content">
    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('daybook::lang.daily_cashbook_report')</h3>
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
                        <table class="table table-bordered table-striped" id="daily_cashbook_table" style="width: 100%;">
                            <thead>
                                <tr style="background-color: #f4f4f4;">
                                    <th style="text-align: left; width: 15%;">@lang('daybook::lang.date_wise') / @lang('daybook::lang.day')</th>
                                    <th style="text-align: left; width: 15%;">@lang('daybook::lang.day')</th>
                                    <th style="text-align: right; width: 18%;">@lang('daybook::lang.debit')</th>
                                    <th style="text-align: right; width: 18%;">@lang('daybook::lang.credit')</th>
                                    <th style="text-align: right; width: 18%;">@lang('daybook::lang.closing_balance')</th>
                                </tr>
                            </thead>
                            <tbody id="daily_cashbook_tbody">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
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
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize date range picker
        $('#date_range').daterangepicker({
            locale: {
                format: 'DD/MM/YYYY'
            },
            ranges: {
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'This Year': [moment().startOf('year'), moment().endOf('year')],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
            },
            startDate: moment().startOf('month'),
            endDate: moment().endOf('month')
        }, function(start, end) {
            $('#start_date').val(start.format('YYYY-MM-DD'));
            $('#end_date').val(end.format('YYYY-MM-DD'));
            // Clear month filter when manual date range is selected
            $('#month_filter').val('').trigger('change');
        });

        // Set initial dates (current month)
        var start = moment().startOf('month');
        var end = moment().endOf('month');
        $('#start_date').val(start.format('YYYY-MM-DD'));
        $('#end_date').val(end.format('YYYY-MM-DD'));
        $('#date_range').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
        
        // Set current month in month filter
        $('#month_filter').val(moment().format('MM')).trigger('change');

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
                loadDailyCashbook();
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
                loadDailyCashbook();
            }
        });

        // Filter button click
        $('#filter_btn').click(function() {
            loadDailyCashbook();
        });

        // Reset button click
        $('#reset_btn').click(function() {
            $('#date_range').val('');
            $('#start_date').val('');
            $('#end_date').val('');
            $('#month_filter').val('').trigger('change');
            $('#year_filter').val(moment().format('YYYY')).trigger('change');
            $('#location_id').val('').trigger('change');
            $('#account_id').val($default_account).trigger('change');
            $('#scope').val('all').trigger('change');
            $('#report_section').hide();
        });

        function loadDailyCashbook() {
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

            $.ajax({
                url: "{{ action([\Modules\Daybook\Http\Controllers\DaybookController::class, 'getDailyCashbookData']) }}",
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
                        $('#report_section').show();
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
            var tbody = $('#daily_cashbook_tbody');
            tbody.empty();

            if (!data.daily_data || data.daily_data.length === 0) {
                tbody.append('<tr><td colspan="5" class="text-center">@lang("lang_v1.no_data_available")</td></tr>');
                return;
            }

            data.daily_data.forEach(function(row) {
                var tr = $('<tr>');
                
                // Style based on row type
                if (row.is_opening || row.is_total) {
                    tr.css('font-weight', 'bold');
                    tr.css('background-color', row.is_total ? '#f0f0f0' : '#f9f9f9');
                }

                // Date column
                var dateTd = $('<td>').text(row.date);
                if (row.is_opening || row.is_total) {
                    dateTd.css('font-weight', 'bold');
                }
                tr.append(dateTd);

                // Day Name column
                var dayTd = $('<td>').text(row.day_name || '-');
                if (row.is_opening || row.is_total) {
                    dayTd.css('font-weight', 'bold');
                }
                tr.append(dayTd);

                // Debit column
                var debitTd = $('<td>').css('text-align', 'right');
                if (row.debit > 0) {
                    debitTd.text(formatMoney(row.debit));
                } else {
                    debitTd.text('-');
                }
                if (row.is_opening || row.is_total) {
                    debitTd.css('font-weight', 'bold');
                }
                tr.append(debitTd);

                // Credit column
                var creditTd = $('<td>').css('text-align', 'right');
                if (row.credit > 0) {
                    creditTd.text(formatMoney(row.credit));
                } else {
                    creditTd.text('-');
                }
                if (row.is_opening || row.is_total) {
                    creditTd.css('font-weight', 'bold');
                }
                tr.append(creditTd);

                // Balance column
                var balanceTd = $('<td>').css('text-align', 'right');
                var balance = row.balance;
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

        function formatMoney(amount) {
            return parseFloat(amount).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    });
</script>
@endsection

