@extends('layouts.app')

@section('title', __('scheme::lang.scheme') . ' - ' . $scheme->scheme_name)

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('scheme::lang.scheme') }}: {{ $scheme->scheme_name }}</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title">{{ __('scheme::lang.scheme_details') }}</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('scheme::lang.scheme_name') }}:</label>
                                <p class="form-control-static">{{ $scheme->scheme_name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('lang_v1.added_by') }}:</label>
                                <p class="form-control-static">{{ $scheme->creator->name ?? '' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('scheme::lang.scheme_type') }}:</label>
                                <p class="form-control-static">{{ ucfirst($scheme->scheme_type ?? 'fixed') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('scheme::lang.enable_slab') }}:</label>
                                <p class="form-control-static">{{ $scheme->enable_slab ? __('messages.yes') : __('messages.no') }}</p>
                            </div>
                        </div>
                    </div>
                    @if($scheme->enable_slab)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('scheme::lang.slab_calculation_type') }}:</label>
                                <p class="form-control-static">{{ $scheme->slab_calculation_type == 'flat' ? __('scheme::lang.flat_slab') : __('scheme::lang.incremental_slab') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="row">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('scheme::lang.starts_at') }}:</label>
                                <p class="form-control-static">{{ @format_date($scheme->starts_at) }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('scheme::lang.ends_at') }}:</label>
                                <p class="form-control-static">{{ @format_date($scheme->ends_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('contact.supplier') }}:</label>
                                <p class="form-control-static">{{ $scheme->supplier ? $scheme->supplier->name : '' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('scheme::lang.scheme_note') }}:</label>
                                <p class="form-control-static">{{ $scheme->scheme_note }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Status') }}:</label>
                                <p class="form-control-static">
                                    @php
                                        $now = now();
                                        $status = ($scheme->starts_at <= $now && $scheme->ends_at->endOfDay() >= $now) ? 'Active' : 'Closed';
                                    @endphp
                                    <span class="label {{ $status == 'Active' ? 'bg-green' : 'bg-red' }}">{{ $status }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('scheme::lang.total_eligible_amount') }}:</label>
                                <p class="form-control-static">
                                    <span class="display_currency" data-currency_symbol="true" style="font-weight: bold; background-color: green; color: white;">{{ number_format($total_eligible_amount, 2) }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>{{ __('report.products') }}:</label>
                                <div class="form-control-static" style="max-height: min(200px, 30vh); overflow-y: auto; border: 1px solid #ddd; padding: 5px; border-radius: 4px;">
                                    @if($scheme->variations->count() > 0)
                                        @foreach($scheme->variations as $variation)
                                            <span class="label bg-primary" style="display: inline-block; margin: 2px;">{{ $variation->full_name }}</span>
                                        @endforeach
                                    @else
                                        {{ $scheme->product ? $scheme->product->name : '' }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($scheme->enable_slab && $scheme->slabs->count() > 0)
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title">{{ __('scheme::lang.slab_setup') }}</h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('scheme::lang.from_quantity') }}</th>
                                    <th>{{ __('scheme::lang.to_quantity') }}</th>
                                    <th>{{ __('scheme::lang.commission_type') }}</th>
                                    <th>{{ __('scheme::lang.value') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scheme->slabs->sortBy('from_amount') as $slab)
                                <tr>
                                    <td>{{ number_format($slab->from_amount, 2) }}</td>
                                    <td>{{ $slab->to_amount ? number_format($slab->to_amount, 2) : __('scheme::lang.unlimited') }}</td>
                                    <td>{{ ucfirst($slab->commission_type) }}</td>
                                    <td>{{ $slab->commission_type == 'percentage' ? number_format($slab->value, 2) . '%' : '<span class="display_currency" data-currency_symbol="true">' . number_format($slab->value, 2) . '</span>' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title">{{ __('scheme::lang.view_sold') }}</h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="scheme_sales_table">
                            <thead>
                                <tr>
                                    <th>Business Location</th>
                                    <th>{{ __('sale.product') }}</th>
                                    <th>{{ __('product.sku') }}</th>
                                    <th>{{ __('scheme::lang.lot_number') }}</th>
                                    <th>{{ __('sale.customer_name') }}</th>
                                    <th>{{ __('sale.invoice_no') }}</th>
                                    <th>{{ __('messages.date') }}</th>
                                    <th>{{ __('sale.qty') }}</th>
                                    <th>{{ __('sale.unit_price') }}</th>
                                    <th>{{ __('sale.discount') }}</th>
                                    <th>{{ __('sale.tax') }}</th>
                                    <th>{{ __('sale.price_inc_tax') }}</th>
                                    <th>{{ __('sale.total') }}</th>
                                    <th>Eligible Scheme Value</th>
                                    <th>Service Staff</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sales_data as $sale)
                                <tr>
                                    <td>{{ $sale->business_location }}</td>
                                    <td>{{ $sale->product }}</td>
                                    <td>{{ $sale->sku }}</td>
                                    <td>{{ $sale->lot_number }}</td>
                                    <td>{{ $sale->customer_name }}</td>
                                    <td>{{ $sale->invoice_no }}</td>
                                    <td>{{ @format_date($sale->transaction_date) }}</td>
                                    <td>{{ number_format($sale->qty, 2) }}</td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ number_format($sale->unit_price, 2) }}</span></td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ number_format($sale->discount, 2) }}</span></td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ number_format($sale->tax, 2) }}</span></td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ number_format($sale->price_inc_tax, 2) }}</span></td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ number_format($sale->total, 2) }}</span></td>
                                    <td>
                                        <span class="display_currency" data-currency_symbol="true">{{ number_format($sale->eligible_amount, 2) }}</span>
                                    </td>
                                    <td>{{ $sale->service_staff }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray font-17 footer-total text-center">
                                    <td colspan="7"><strong>{{ __('sale.total') }}:</strong></td>
                                    <td id="footer_total_qty">0.00</td>
                                    <td></td>
                                    <td id="footer_total_discount"><span class="display_currency" data-currency_symbol="true">0.00</span></td>
                                    <td id="footer_total_tax"><span class="display_currency" data-currency_symbol="true">0.00</span></td>
                                    <td></td>
                                    <td><span class="display_currency" id="footer_total_amount" data-currency_symbol="true">0.00</span></td>
                                    <td><span class="display_currency" data-currency_symbol="true" id="footer_eligible_amount">0.00</span></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    $('#scheme_sales_table').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "footerCallback": function (row, data, start, end, display) {
            var api = this.api();

            // Function to extract number from cell content (handles both plain numbers and currency spans)
            var extractNumber = function (cellContent) {
                if (typeof cellContent === 'string') {
                    // Remove HTML tags and extract number
                    var text = cellContent.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '');
                    return parseFloat(text) || 0;
                }
                return parseFloat(cellContent) || 0;
            };

            // Calculate totals for visible rows only
            var qtyTotal = 0;
            var discountTotal = 0;
            var taxTotal = 0;
            var amountTotal = 0;
            var eligibleTotal = 0;

            api.rows({ page: 'current' }).every(function() {
                var data = this.data();
                qtyTotal += extractNumber(data[7]);  // Qty column
                discountTotal += extractNumber(data[9]);  // Discount column
                taxTotal += extractNumber(data[10]);  // Tax column
                amountTotal += extractNumber(data[12]);  // Total column
                eligibleTotal += extractNumber(data[13]);  // Eligible Scheme Value column
            });

            // Update footer cells
            $('#footer_total_qty').html(qtyTotal.toFixed(2));
            $('#footer_total_discount .display_currency').html(discountTotal.toFixed(2));
            $('#footer_total_tax .display_currency').html(taxTotal.toFixed(2));
            $('#footer_total_amount').html(amountTotal.toFixed(2));
            $('#footer_eligible_amount').html(eligibleTotal.toFixed(2));
        }
    });
});
</script>
@endsection