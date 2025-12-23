<div class="modal fade" id="convertToPurchaseModal" tabindex="-1" role="dialog" aria-labelledby="convertToPurchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="convertToPurchaseModalLabel">
                    {{ __('lang_v1.convert_sell_to_purchase') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form id="convert-to-purchase-form" action="{{ route('sell.convert-to-purchase.store', ['id' => $sell->id]) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="supplier_id">{{ __('lang_v1.supplier') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="supplier_id" name="supplier_id" required>
                                    <option value="">{{ __('lang_v1.please_select') }}</option>
                                    @foreach($suppliers as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="location_id">{{ __('business.location') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="location_id" name="location_id" required>
                                    <option value="">{{ __('lang_v1.please_select') }}</option>
                                    @foreach($business_locations as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h6>{{ __('lang_v1.sell_details') }}</h6>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ __('product.product_name') }}</th>
                                            <th>{{ __('lang_v1.quantity') }}</th>
                                            <th>{{ __('lang_v1.unit_price') }}</th>
                                            <th>{{ __('lang_v1.line_total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sell->sell_lines as $line)
                                            <tr>
                                                <td>{{ $line->product->name }} ({{ $line->variations->name }})</td>
                                                <td>{{ $line->quantity }} {{ $line->product->unit->short_name }}</td>
                                                <td>{{ number_format($line->unit_price, 2) }}</td>
                                                <td>{{ number_format($line->unit_price * $line->quantity, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-right">{{ __('lang_v1.total') }}</th>
                                            <th>{{ number_format($sell->final_total, 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <strong>{{ __('lang_v1.note') }}:</strong>
                                <ul>
                                    <li>{{ __('lang_v1.sell_will_be_converted_to_purchase') }}</li>
                                    <li>{{ __('lang_v1.purchase_status_will_be_pending') }}</li>
                                    <li>{{ __('lang_v1.shipping_charges_will_be_copied') }}</li>
                                    <li>{{ __('lang_v1.imei_serial_numbers_will_be_transferred') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('lang_v1.convert_to_purchase') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize select2
    $('.select2').select2();

    // Show modal on page load
    $('#convertToPurchaseModal').modal('show');
});
</script>