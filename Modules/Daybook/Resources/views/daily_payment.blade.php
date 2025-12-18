@extends('layouts.app')

@section('title', __('daybook::lang.daily_payment_report'))

@section('content')
<section class="content-header">
    <h1>@lang('daybook::lang.daily_payment_report')</h1>
</section>

<section class="content">
    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('daybook::lang.daily_payment_report')</h3>
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

                <!-- Quick Filters -->
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('quick_filter', __('daybook::lang.quick_filters') . ':') !!}
                        {!! Form::select('quick_filter', [
                            '' => __('Select'),
                            'today' => __('daybook::lang.today'),
                            'yesterday' => __('daybook::lang.yesterday'),
                            'this_month' => __('daybook::lang.this_month'),
                            'last_month' => __('daybook::lang.last_month'),
                            'last_7_days' => __('daybook::lang.last_7_days'),
                            'last_30_days' => __('daybook::lang.last_30_days')
                        ], 'today', [
                            'class' => 'form-control select2',
                            'id' => 'quick_filter'
                        ]) !!}
                    </div>
                </div>

                <!-- Hidden date fields -->
                {!! Form::hidden('start_date', '', ['id' => 'start_date']) !!}
                {!! Form::hidden('end_date', '', ['id' => 'end_date']) !!}
            </div>

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
                        {!! Form::label('user_id', __('User') . ':') !!}
                        {!! Form::select('user_id', $users, null,
                            ['class' => 'form-control select2', 'id' => 'user_id', 'placeholder' => __('messages.all')])
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
                        <table class="table table-bordered table-striped" id="daily_payment_table" style="width: 100%;">
                            <thead>
                                <tr style="background-color: #f4f4f4;">
                                    <th style="text-align: left; width: 40%;">@lang('account.account')</th>
                                    <th style="text-align: right; width: 20%;">@lang('daybook::lang.debit')</th>
                                    <th style="text-align: right; width: 20%;">@lang('daybook::lang.credit')</th>
                                    <th style="text-align: right; width: 20%;">@lang('daybook::lang.net_amount')</th>
                                </tr>
                            </thead>
                            <tbody id="daily_payment_tbody">
                                <!-- Data will be loaded here -->
                            </tbody>
                            <tfoot id="daily_payment_tfoot">
                                <!-- Totals will be loaded here -->
                            </tfoot>
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
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()]
            },
            startDate: moment(),
            endDate: moment()
        }, function(start, end) {
            $('#start_date').val(start.format('YYYY-MM-DD'));
            $('#end_date').val(end.format('YYYY-MM-DD'));
            // Clear quick filter when manual date range is selected
            $('#quick_filter').val('').trigger('change');
        });

        // Set initial dates (today)
        var start = moment();
        var end = moment();
        $('#start_date').val(start.format('YYYY-MM-DD'));
        $('#end_date').val(end.format('YYYY-MM-DD'));
        $('#date_range').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));

        // Set quick filter to today
        $('#quick_filter').val('today').trigger('change');

        // Quick filter change handler
        $('#quick_filter').on('change', function() {
            var filter = $(this).val();

            if (filter) {
                var start, end;

                switch(filter) {
                    case 'today':
                        start = moment();
                        end = moment();
                        break;
                    case 'yesterday':
                        start = moment().subtract(1, 'days');
                        end = moment().subtract(1, 'days');
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
                }

                $('#start_date').val(start.format('YYYY-MM-DD'));
                $('#end_date').val(end.format('YYYY-MM-DD'));
                $('#date_range').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));

                // Auto-load data when quick filter is selected
                loadDailyPayment();
            }
        });

        // Filter button click
        $('#filter_btn').click(function() {
            loadDailyPayment();
        });

        // Reset button click
        $('#reset_btn').click(function() {
            $('#date_range').val('');
            $('#start_date').val('');
            $('#end_date').val('');
            $('#quick_filter').val('today').trigger('change');
            $('#location_id').val('').trigger('change');
            $('#user_id').val('').trigger('change');
            $('#account_id').val('').trigger('change');
            $('#report_section').hide();
        });

        function loadDailyPayment() {
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            var location_id = $('#location_id').val();
            var user_id = $('#user_id').val();
            var account_id = $('#account_id').val();

            if (!start_date || !end_date) {
                toastr.error('@lang("lang_v1.please_select_date_range")');
                return;
            }

            $('#loading_section').show();
            $('#report_section').hide();

            $.ajax({
                url: "{{ action([\Modules\Daybook\Http\Controllers\DaybookController::class, 'getDailyPaymentData']) }}",
                method: 'GET',
                data: {
                    start_date: start_date,
                    end_date: end_date,
                    location_id: location_id,
                    user_id: user_id,
                    account_id: account_id
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
            var tbody = $('#daily_payment_tbody');
            var tfoot = $('#daily_payment_tfoot');
            tbody.empty();
            tfoot.empty();

            if (!data.payment_accounts || data.payment_accounts.length === 0) {
                tbody.append('<tr><td colspan="4" class="text-center">@lang("lang_v1.no_data_available")</td></tr>');
                return;
            }

            data.payment_accounts.forEach(function(account) {
                var tr = $('<tr>');

                // Account Name column
                var accountTd = $('<td>').text(account.account_name);
                tr.append(accountTd);

                // Debit column
                var debitTd = $('<td>').css('text-align', 'right');
                if (account.debit > 0) {
                    debitTd.text(formatMoney(account.debit));
                } else {
                    debitTd.text('-');
                }
                tr.append(debitTd);

                // Credit column
                var creditTd = $('<td>').css('text-align', 'right');
                if (account.credit > 0) {
                    creditTd.text(formatMoney(account.credit));
                } else {
                    creditTd.text('-');
                }
                tr.append(creditTd);

                // Net Amount column
                var netTd = $('<td>').css('text-align', 'right');
                var netAmount = account.net_amount;
                if (netAmount > 0) {
                    netTd.html(formatMoney(Math.abs(netAmount)) + ' <span style="color: #d9534f;">Dr</span>');
                } else if (netAmount < 0) {
                    netTd.html(formatMoney(Math.abs(netAmount)) + ' <span style="color: #5cb85c;">Cr</span>');
                } else {
                    netTd.text('-');
                }
                tr.append(netTd);

                tbody.append(tr);
            });

            // Add totals row
            var totalTr = $('<tr>').css('font-weight', 'bold').css('background-color', '#f0f0f0');

            totalTr.append($('<td>').text('@lang("daybook::lang.total")'));

            var totalDebitTd = $('<td>').css('text-align', 'right');
            if (data.total_debit > 0) {
                totalDebitTd.text(formatMoney(data.total_debit));
            } else {
                totalDebitTd.text('-');
            }
            totalTr.append(totalDebitTd);

            var totalCreditTd = $('<td>').css('text-align', 'right');
            if (data.total_credit > 0) {
                totalCreditTd.text(formatMoney(data.total_credit));
            } else {
                totalCreditTd.text('-');
            }
            totalTr.append(totalCreditTd);

            var totalNetTd = $('<td>').css('text-align', 'right');
            var netTotal = data.net_total;
            if (netTotal > 0) {
                totalNetTd.html(formatMoney(Math.abs(netTotal)) + ' <span style="color: #d9534f;">Dr</span>');
            } else if (netTotal < 0) {
                totalNetTd.html(formatMoney(Math.abs(netTotal)) + ' <span style="color: #5cb85c;">Cr</span>');
            } else {
                totalNetTd.text('-');
            }
            totalTr.append(totalNetTd);

            tfoot.append(totalTr);
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