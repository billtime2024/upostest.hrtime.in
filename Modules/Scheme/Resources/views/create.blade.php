@extends('layouts.app')

@section('title', __('scheme::lang.add_scheme'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('scheme::lang.add_scheme') }}</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\Modules\Scheme\Http\Controllers\SchemeController::class, 'store']), 'method' => 'post', 'id' => 'scheme_form']) !!}
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('scheme::lang.scheme_details')])
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('enable_slab', 1, false, ['id' => 'enable_slab']) !!}
                                        {{ __('scheme::lang.enable_slab') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 multi-level-field" style="display: none;">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('multi_level', 1, false, ['id' => 'multi_level']) !!}
                                        {{ __('scheme::lang.multi_level') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('scheme_name', __('scheme::lang.scheme_name') . ':*') !!}
                                {!! Form::text('scheme_name', null, ['class' => 'form-control', 'required', 'placeholder' => __('scheme::lang.scheme_name')]) !!}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('supplier_id', __('contact.supplier') . ':') !!}
                                {!! Form::select('supplier_id', $suppliers, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]) !!}
                            </div>
                        </div>
                    </div>

                    <div class="row non-slab-fields">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('scheme_amount', __('scheme::lang.scheme_amount') . ':*') !!}
                                {!! Form::text('scheme_amount', null, ['class' => 'form-control input_number', 'data-decimal-places' => '2', 'placeholder' => __('scheme::lang.scheme_amount')]) !!}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('scheme_type', __('scheme::lang.scheme_type') . ':*') !!}
                                {!! Form::select('scheme_type', ['fixed' => __('scheme::lang.fixed'), 'percentage' => __('scheme::lang.percentage')], 'fixed', ['class' => 'form-control']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="row slab-section" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('slab_calculation_type', __('scheme::lang.slab_calculation_type') . ':*') !!}
                                {!! Form::select('slab_calculation_type', ['flat' => __('scheme::lang.flat_slab'), 'incremental' => __('scheme::lang.incremental_slab')], null, ['class' => 'form-control slab-required']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="row slab-section" style="display: none;">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>{{ __('scheme::lang.slab_setup') }}</label>
                                <div id="slab-table">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>{{ __('scheme::lang.from_quantity') }}</th>
                                                <th>{{ __('scheme::lang.to_quantity') }}</th>
                                                <th>{{ __('scheme::lang.commission_type') }}</th>
                                                <th>{{ __('scheme::lang.value') }}</th>
                                                <th class="slab-products-header" style="display: none;">{{ __('report.products') }}</th>
                                                <th><button type="button" class="btn btn-sm btn-success" id="add-slab-row"><i class="fa fa-plus"></i></button></th>
                                            </tr>
                                        </thead>
                                        <tbody id="slab-rows">
                                            <tr class="slab-row">
                                                <td>{!! Form::text('slabs[0][from_amount]', null, ['class' => 'form-control input_number slab-required', 'data-decimal-places' => '2', 'placeholder' => __('scheme::lang.from_quantity')]) !!}</td>
                                                <td>{!! Form::text('slabs[0][to_amount]', null, ['class' => 'form-control input_number', 'data-decimal-places' => '2', 'placeholder' => __('scheme::lang.to_quantity')]) !!}</td>
                                                <td>{!! Form::select('slabs[0][commission_type]', ['fixed' => __('scheme::lang.fixed'), 'percentage' => __('scheme::lang.percentage')], 'percentage', ['class' => 'form-control slab-required']) !!}</td>
                                                <td>{!! Form::text('slabs[0][value]', null, ['class' => 'form-control input_number slab-required', 'data-decimal-places' => '2', 'placeholder' => __('scheme::lang.value')]) !!}</td>
                                                <td class="slab-products-cell" style="display: none;">{!! Form::select('slabs[0][variation_ids][]', [], null, ['class' => 'form-control select2 slab-products', 'multiple', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']) !!}</td>
                                                <td><button type="button" class="btn btn-sm btn-danger remove-slab-row"><i class="fa fa-trash"></i></button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                     </div>

                     <div class="row global-products-field">
                         <div class="col-md-12">
                              <div class="form-group">
                                  {!! Form::label('variation_ids', __('report.products') . ':') !!}
                                  {!! Form::select('variation_ids[]', [], null, ['id' => "variation_ids", 'class' => 'form-control select2', 'multiple', 'placeholder' => __('messages.please_select')]); !!}
                              </div>
                          </div>
                     </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('starts_at', __('scheme::lang.starts_at') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                    {!! Form::text('starts_at', null, ['class' => 'form-control', 'readonly', 'id' => 'starts_at']) !!}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('ends_at', __('scheme::lang.ends_at') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                    {!! Form::text('ends_at', null, ['class' => 'form-control', 'readonly', 'id' => 'ends_at']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('scheme_note', __('scheme::lang.scheme_note') . ':') !!}
                                {!! Form::textarea('scheme_note', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('scheme::lang.scheme_note')]) !!}
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="col-md-12 text-center">
                    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-lg">
                        <i class="fa fa-save"></i> @lang('messages.save')
                    </button>
                    <a href="{{ action([\Modules\Scheme\Http\Controllers\SchemeController::class, 'index']) }}" class="tw-dw-btn tw-dw-btn-default tw-dw-btn-lg">
                        <i class="fa fa-times"></i> @lang('messages.cancel')
                    </a>
                </div>
            </div>
        </div>
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    $('#starts_at').datepicker({
        autoclose: true,
        format: datepicker_date_format,
        minDate: false
    });

    $('#ends_at').datepicker({
        autoclose: true,
        format: datepicker_date_format,
        minDate: false
    });

    $('#starts_at').on('change', function() {
        var startDate = $(this).datepicker('getDate');
        if (startDate) {
            $('#ends_at').datepicker('option', 'minDate', startDate);
        } else {
            $('#ends_at').datepicker('option', 'minDate', false);
        }
    });

    $('#variation_ids').select2({
        ajax: {
            url: '/purchases/get_products?check_enable_stock=false&only_variations=true',
            dataType: 'json',
            delay: 250,
            processResults: function(data) {
                var results = [];
                for (var item in data) {
                    results.push({
                        id: data[item].variation_id,
                        text: data[item].text,
                    });
                }
                return {
                    results: results,
                };
            },
        },
        minimumInputLength: 1,
        closeOnSelect: false
    });

    // Initialize slab products select2
    function initializeSlabProducts() {
        $('.slab-products').select2({
            ajax: {
                url: '/purchases/get_products?check_enable_stock=false&only_variations=true',
                dataType: 'json',
                delay: 250,
                processResults: function(data) {
                    var results = [];
                    for (var item in data) {
                        results.push({
                            id: data[item].variation_id,
                            text: data[item].text,
                        });
                    }
                    return {
                        results: results,
                    };
                },
            },
            minimumInputLength: 1,
            closeOnSelect: false
        });
    }

    // Initialize on document ready
    initializeSlabProducts();

    // Toggle slab sections
    $('#enable_slab').on('change', function() {
        if ($(this).is(':checked')) {
            $('.slab-section').show();
            $('.slab-required').attr('required', true);
            $('.non-slab-fields').hide();
            $('.multi-level-field').show();
            $('#scheme_amount').removeAttr('required');
            $('#scheme_type').removeAttr('required');
            toggleGlobalProducts();
        } else {
            $('.slab-section').hide();
            $('.slab-required').attr('required', false);
            $('.non-slab-fields').show();
            $('.multi-level-field').hide();
            $('#scheme_amount').attr('required', true);
            $('#scheme_type').attr('required', true);
            $('.global-products-field').show();
        }
    });

    // Toggle global products field and slab products column based on multi-level
    function toggleGlobalProducts() {
        if ($('#enable_slab').is(':checked') && $('#multi_level').is(':checked')) {
            $('.global-products-field').hide();
            $('.slab-products-header, .slab-products-cell').show();
        } else {
            $('.global-products-field').show();
            $('.slab-products-header, .slab-products-cell').hide();
        }
    }

    // Handle multi-level checkbox change
    $('#multi_level').on('change', function() {
        toggleGlobalProducts();
    });

    // Function to renumber slab rows
    function renumberSlabs() {
        $('#slab-rows .slab-row').each(function(index) {
            $(this).find('input[name*="from_amount"]').attr('name', 'slabs[' + index + '][from_amount]');
            $(this).find('input[name*="to_amount"]').attr('name', 'slabs[' + index + '][to_amount]');
            $(this).find('select[name*="commission_type"]').attr('name', 'slabs[' + index + '][commission_type]');
            $(this).find('input[name*="value"]').attr('name', 'slabs[' + index + '][value]');
            $(this).find('select[name*="variation_ids"]').attr('name', 'slabs[' + index + '][variation_ids][]');
        });
    }

    // Add slab row
    $('#add-slab-row').on('click', function() {
        var rowCount = $('#slab-rows .slab-row').length;
        var productsCell = '';
        if ($('#multi_level').is(':checked')) {
            productsCell = `<td class="slab-products-cell"><select name="slabs[${rowCount}][variation_ids][]" class="form-control select2 slab-products" multiple placeholder="{{ __('messages.please_select') }}" style="width: 100%;"></select></td>`;
        } else {
            productsCell = `<td class="slab-products-cell" style="display: none;"></td>`;
        }
        var newRow = `
            <tr class="slab-row">
                <td><input type="text" name="slabs[${rowCount}][from_amount]" class="form-control input_number slab-required" data-decimal-places="2" placeholder="{{ __('scheme::lang.from_quantity') }}" required></td>
                <td><input type="text" name="slabs[${rowCount}][to_amount]" class="form-control input_number" data-decimal-places="2" placeholder="{{ __('scheme::lang.to_quantity') }}"></td>
                <td>
                    <select name="slabs[${rowCount}][commission_type]" class="form-control slab-required" required>
                        <option value="fixed">{{ __('scheme::lang.fixed') }}</option>
                        <option value="percentage" selected>{{ __('scheme::lang.percentage') }}</option>
                    </select>
                </td>
                <td><input type="text" name="slabs[${rowCount}][value]" class="form-control input_number slab-required" data-decimal-places="2" placeholder="{{ __('scheme::lang.value') }}" required></td>
                ${productsCell}
                <td><button type="button" class="btn btn-sm btn-danger remove-slab-row"><i class="fa fa-trash"></i></button></td>
            </tr>
        `;
        $('#slab-rows').append(newRow);
        renumberSlabs();
        initializeSlabProducts();
    });

    // Remove slab row
    $(document).on('click', '.remove-slab-row', function() {
        $(this).closest('.slab-row').remove();
        renumberSlabs();
    });

    $('#scheme_form').validate({
        rules: {
            scheme_name: {
                required: true
            },
            scheme_amount: {
                required: function() {
                    return !$('#enable_slab').is(':checked');
                },
                number: true,
                min: 0
            },
            scheme_type: {
                required: function() {
                    return !$('#enable_slab').is(':checked');
                }
            },
            starts_at: {
                required: false
            },
            ends_at: {
                required: false
            }
        },
        messages: {
            scheme_name: {
                required: 'Scheme name is required'
            },
            scheme_amount: {
                required: 'Scheme amount is required',
                number: 'Please enter a valid number',
                min: 'Amount must be greater than or equal to 0'
            },
            scheme_type: {
                required: 'Scheme type is required'
            }
        }
    });

    // Restrict decimal places to 2 for inputs with data-decimal-places="2"
    $(document).on('input', 'input[data-decimal-places="2"]', function() {
        var value = $(this).val();
        var regex = /^\d*\.?\d{0,2}$/;
        if (!regex.test(value)) {
            $(this).val(value.replace(/(\.\d{2}).*$/, '$1'));
        }
    });
});
</script>
@endsection