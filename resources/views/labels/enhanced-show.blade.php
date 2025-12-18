@extends('layouts.app')
@section('title', __('barcode.print_labels'))

@section('css')
<!-- Enhanced Labels CSS -->
<link rel="stylesheet" href="{{ asset('css/enhanced-labels.css?v=' . $asset_v) }}">
@endsection

@section('content')
<!-- Enhanced Label Printing Interface -->
<div class="enhanced-labels-container">
    <!-- Header -->
    <div class="enhanced-header">
        <div class="enhanced-header-content">
            <div class="enhanced-header-inner">
                <h1 class="enhanced-title">@lang('barcode.print_labels')</h1>
                <p class="enhanced-subtitle">Enhanced interface for generating product labels</p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="enhanced-main">
        {!! Form::open(['url' => '#', 'method' => 'post', 'id' => 'preview_setting_form', 'onsubmit' => 'return false'])
        !!}

        <!-- Product Search Section -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                <h2 class="enhanced-card-title">@lang('product.add_product_for_labels')</h2>
            </div>
            <div class="enhanced-card-content">
                <div class="enhanced-input-group">
                    <div class="enhanced-input-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    {!! Form::text('search_product', null, [
                    'class' => 'enhanced-input',
                    'id' => 'search_product_for_label',
                    'placeholder' => __('lang_v1.enter_product_name_to_print_labels'),
                    'autofocus'
                    ]); !!}
                </div>

                <!-- Products Table -->
                <div class="enhanced-table-container">
                    <table class="enhanced-table" id="product_table">
                        <thead>
                            <tr>
                                <th>@lang('barcode.products')</th>
                                <th>@lang('barcode.no_of_labels')</th>
                                @if(request()->session()->get('business.enable_lot_number') == 1)
                                <th>@lang('lang_v1.lot_number')</th>
                                @endif
                                @if(request()->session()->get('business.enable_product_expiry') == 1)
                                <th>@lang('product.exp_date')</th>
                                @endif
                                <th>@lang('lang_v1.packing_date')</th>
                                <th>@lang('lang_v1.selling_price_group')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('labels.partials.show_table_rows', ['index' => 0])
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="enhanced-grid">

            <!-- Left Column - Settings Panel -->
            <div class="enhanced-card">
                <div class="enhanced-card-header">
                    <h2 class="enhanced-card-title">Label Settings</h2>
                </div>
                <div class="enhanced-card-content">

                    <!-- Barcode Setting -->
                    <div class="enhanced-input-group" style="max-width: 100%;">
                        <label class="nested-label">@lang('barcode.barcode_setting')</label>
                        <div style="position: relative;">
                            <div class="enhanced-input-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            {!! Form::select('barcode_setting', $barcode_settings, !empty($default) ? $default->id :
                            null, [
                            'class' => 'enhanced-select',
                            'onchange' => 'fetchPreview()'
                            ]); !!}
                        </div>
                    </div>

                    <!-- Label Elements -->
                    <div class="label-elements">
                        <h3 class="label-elements-title">Label Elements</h3>

                        <!-- Business Image -->
                        <div class="toggle-container">
                            <div class="toggle-label-wrapper">
                                <input type="checkbox" name="print[image]" value="1" class="hidden label-toggle">
                                <div class="toggle-switch"></div>
                                <span class="toggle-label-text">Business Image</span>
                            </div>
                            <div class="size-controls">
                                <button type="button" onclick="updateInputSize('print[image_size]', -1)"
                                    class="size-btn size-btn-minus">-</button>
                                <input type="text" name="print[image_size]" value="25" class="size-input"
                                    onchange="fetchPreview()">
                                <button type="button" onclick="updateInputSize('print[image_size]', 1)"
                                    class="size-btn size-btn-plus">+</button>
                                <span class="size-unit">px</span>
                            </div>
                        </div>

                        <!-- Image Source Selection -->
                        <div class="nested-controls">
                            <div class="nested-input-group">
                                <label class="nested-label">Image Source</label>
                                <select name="print[image_source]" id="image_source" onchange="toggleImageSource()"
                                    class="nested-select">
                                    <option value="select_image">Select Image URL</option>
                                    <option value="label_image">Label Image</option>
                                </select>
                            </div>
                            <div id="select_image_url_row" class="nested-input-group hidden">
                                <input type="text" name="print[select_image_url]" placeholder="Enter image URL"
                                    class="nested-input" onchange="fetchPreview()">
                            </div>
                        </div>

                        <!-- Barcode -->
                        <div class="toggle-container">
                            <div class="toggle-label-wrapper">
                                <input type="checkbox" name="print[barcode]" value="1" checked
                                    class="hidden label-toggle">
                                <div class="toggle-switch active"></div>
                                <span class="toggle-label-text">@lang('barcode.barcodes')</span>
                            </div>
                            <div class="size-controls">
                                <button type="button" onclick="updateInputSize('print[barcode_size]', -0.1)"
                                    class="size-btn size-btn-minus">-</button>
                                <input type="text" name="print[barcode_size]" value="0.8" class="size-input"
                                    onchange="fetchPreview()">
                                <button type="button" onclick="updateInputSize('print[barcode_size]', 0.1)"
                                    class="size-btn size-btn-plus">+</button>
                                <span class="size-unit">in</span>
                            </div>
                        </div>

                        <!-- QR Code -->
                        <div class="toggle-container">
                            <div class="toggle-label-wrapper">
                                <input type="checkbox" name="print[qrcode]" value="1" class="hidden label-toggle">
                                <div class="toggle-switch"></div>
                                <span class="toggle-label-text">@lang('barcode.print_qrcode')</span>
                            </div>
                            <div class="size-controls">
                                <button type="button" onclick="updateInputSize('print[qrcode_size]', -0.1)"
                                    class="size-btn size-btn-minus">-</button>
                                <input type="text" name="print[qrcode_size]" value="1.4" class="size-input"
                                    onchange="fetchPreview()">
                                <button type="button" onclick="updateInputSize('print[qrcode_size]', 0.1)"
                                    class="size-btn size-btn-plus">+</button>
                                <span class="size-unit">in</span>
                            </div>
                        </div>

                        <!-- Product Name -->
                        <div class="toggle-container">
                            <div class="toggle-label-wrapper">
                                <input type="checkbox" name="print[name]" value="1" checked class="hidden label-toggle">
                                <div class="toggle-switch active"></div>
                                <span class="toggle-label-text">@lang('barcode.print_name')</span>
                            </div>
                            <div class="size-controls">
                                <button type="button" onclick="updateInputSize('print[name_size]', -1)"
                                    class="size-btn size-btn-minus">-</button>
                                <input type="text" name="print[name_size]" value="11" class="size-input"
                                    onchange="fetchPreview()">
                                <button type="button" onclick="updateInputSize('print[name_size]', 1)"
                                    class="size-btn size-btn-plus">+</button>
                                <span class="size-unit">px</span>
                            </div>
                        </div>

                        <!-- SKU -->
                        <div class="toggle-container">
                            <div class="toggle-label-wrapper">
                                <input type="checkbox" name="print[sku]" value="1" class="hidden label-toggle">
                                <div class="toggle-switch"></div>
                                <span class="toggle-label-text">@lang('product.sku')</span>
                            </div>
                            <div class="size-controls">
                                <button type="button" onclick="updateInputSize('print[sku_size]', -1)"
                                    class="size-btn size-btn-minus">-</button>
                                <input type="text" name="print[sku_size]" value="11" class="size-input"
                                    onchange="fetchPreview()">
                                <button type="button" onclick="updateInputSize('print[sku_size]', 1)"
                                    class="size-btn size-btn-plus">+</button>
                                <span class="size-unit">px</span>
                            </div>
                        </div>

                        <!-- Variations -->
                        <div class="toggle-container">
                            <div class="toggle-label-wrapper">
                                <input type="checkbox" name="print[variations]" value="1" class="hidden label-toggle">
                                <div class="toggle-switch"></div>
                                <span class="toggle-label-text">@lang('barcode.print_variations')</span>
                            </div>
                            <div class="size-controls">
                                <button type="button" onclick="updateInputSize('print[variations_size]', -1)"
                                    class="size-btn size-btn-minus">-</button>
                                <input type="text" name="print[variations_size]" value="17" class="size-input"
                                    onchange="fetchPreview()">
                                <button type="button" onclick="updateInputSize('print[variations_size]', 1)"
                                    class="size-btn size-btn-plus">+</button>
                                <span class="size-unit">px</span>
                            </div>
                        </div>

                        <!-- Price -->
                        <div class="toggle-container">
                            <div class="toggle-label-wrapper">
                                <input type="checkbox" name="print[price]" value="1" id="is_show_price"
                                    class="hidden label-toggle">
                                <div class="toggle-switch"></div>
                                <span class="toggle-label-text">@lang('barcode.print_price')</span>
                            </div>
                            <div class="size-controls">
                                <button type="button" onclick="updateInputSize('print[price_size]', -1)"
                                    class="size-btn size-btn-minus">-</button>
                                <input type="text" name="print[price_size]" value="9" class="size-input"
                                    onchange="fetchPreview()">
                                <button type="button" onclick="updateInputSize('print[price_size]', 1)"
                                    class="size-btn size-btn-plus">+</button>
                                <span class="size-unit">px</span>
                            </div>
                        </div>

                        <!-- Price Type Selection -->
                        <div id="price_type_div" class="nested-controls hidden">
                            <div class="nested-input-group">
                                <label class="nested-label">Price Type</label>
                                <select name="print[price_type]" class="nested-select" onchange="fetchPreview()">
                                    <option value="exclusive">Exclusive of Tax</option>
                                    <option value="inclusive">Inclusive of Tax</option>
                                </select>
                            </div>
                        </div>

                        <!-- Business Name -->
                        <div class="toggle-container">
                            <div class="toggle-label-wrapper">
                                <input type="checkbox" name="print[business_name]" value="1"
                                    class="hidden label-toggle">
                                <div class="toggle-switch"></div>
                                <span class="toggle-label-text">@lang('barcode.print_business_name')</span>
                            </div>
                            <div class="size-controls">
                                <button type="button" onclick="updateInputSize('print[business_name_size]', -1)"
                                    class="size-btn size-btn-minus">-</button>
                                <input type="text" name="print[business_name_size]" value="20" class="size-input"
                                    onchange="fetchPreview()">
                                <button type="button" onclick="updateInputSize('print[business_name_size]', 1)"
                                    class="size-btn size-btn-plus">+</button>
                                <span class="size-unit">px</span>
                            </div>
                        </div>

                        <!-- Packing Date -->
                        <div class="toggle-container">
                            <div class="toggle-label-wrapper">
                                <input type="checkbox" name="print[packing_date]" value="1" class="hidden label-toggle">
                                <div class="toggle-switch"></div>
                                <span class="toggle-label-text">@lang('lang_v1.print_packing_date')</span>
                            </div>
                            <div class="size-controls">
                                <button type="button" onclick="updateInputSize('print[packing_date_size]', -1)"
                                    class="size-btn size-btn-minus">-</button>
                                <input type="text" name="print[packing_date_size]" value="12" class="size-input"
                                    onchange="fetchPreview()">
                                <button type="button" onclick="updateInputSize('print[packing_date_size]', 1)"
                                    class="size-btn size-btn-plus">+</button>
                                <span class="size-unit">px</span>
                            </div>
                        </div>

                        @if(request()->session()->get('business.enable_lot_number') == 1)
                        <!-- Lot Number -->
                        <div class="toggle-container">
                            <div class="toggle-label-wrapper">
                                <input type="checkbox" name="print[lot_number]" value="1" class="hidden label-toggle">
                                <div class="toggle-switch"></div>
                                <span class="toggle-label-text">@lang('lang_v1.print_lot_number')</span>
                            </div>
                            <div class="size-controls">
                                <button type="button" onclick="updateInputSize('print[lot_number_size]', -1)"
                                    class="size-btn size-btn-minus">-</button>
                                <input type="text" name="print[lot_number_size]" value="12" class="size-input"
                                    onchange="fetchPreview()">
                                <button type="button" onclick="updateInputSize('print[lot_number_size]', 1)"
                                    class="size-btn size-btn-plus">+</button>
                                <span class="size-unit">px</span>
                            </div>
                        </div>
                        @endif

                        @if(request()->session()->get('business.enable_product_expiry') == 1)
                        <!-- Expiry Date -->
                        <div class="toggle-container">
                            <div class="toggle-label-wrapper">
                                <input type="checkbox" name="print[exp_date]" value="1" class="hidden label-toggle">
                                <div class="toggle-switch"></div>
                                <span class="toggle-label-text">@lang('lang_v1.print_exp_date')</span>
                            </div>
                            <div class="size-controls">
                                <button type="button" onclick="updateInputSize('print[exp_date_size]', -1)"
                                    class="size-btn size-btn-minus">-</button>
                                <input type="text" name="print[exp_date_size]" value="12" class="size-input"
                                    onchange="fetchPreview()">
                                <button type="button" onclick="updateInputSize('print[exp_date_size]', 1)"
                                    class="size-btn size-btn-plus">+</button>
                                <span class="size-unit">px</span>
                            </div>
                        </div>
                        @endif

                        @php
                        $custom_labels = json_decode(session('business.custom_labels'), true);
                        $product_custom_fields = !empty($custom_labels['product']) ? $custom_labels['product'] : [];
                        @endphp
                        @foreach($product_custom_fields as $index => $cf)
                        @if(!empty($cf))
                        @php
                        $field_name = 'product_custom_field' . $loop->iteration;
                        @endphp
                        <!-- Custom Field: {{ $cf }} -->
                        <div class="toggle-container">
                            <div class="toggle-label-wrapper">
                                <input type="checkbox" name="print[{{ $field_name }}]" value="1"
                                    class="hidden label-toggle">
                                <div class="toggle-switch"></div>
                                <span class="toggle-label-text">{{ $cf }}</span>
                            </div>
                            <div class="size-controls">
                                <button type="button" onclick="updateInputSize('print[{{ $field_name }}_size]', -1)"
                                    class="size-btn size-btn-minus">-</button>
                                <input type="text" name="print[{{ $field_name }}_size]" value="12" class="size-input"
                                    onchange="fetchPreview()">
                                <button type="button" onclick="updateInputSize('print[{{ $field_name }}_size]', 1)"
                                    class="size-btn size-btn-plus">+</button>
                                <span class="size-unit">px</span>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Column - Preview Panel -->
            <div class="enhanced-card">
                <div class="enhanced-card-header">
                    <div class="preview-header">
                        <h2 class="enhanced-card-title">Label Preview</h2>
                        <div class="preview-buttons">
                            <!-- Original print button -->
                            <button type="button" id="print_label" class="btn-primary">
                                <i class="fas fa-print"></i>
                                Print Label
                            </button>

                            <!-- Enhanced print button -->
                            <button type="button" id="enhanced_print_label" class="btn-success">
                                <i class="fas fa-tag"></i>
                                Label Printer
                            </button>

                            <!-- QR Code button -->
                            <button type="button" id="labels_preview_qr" class="btn-secondary">
                                <i class="fas fa-qrcode"></i>
                                QR Code
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Preview Container -->
                <div class="enhanced-card-content">
                    <!-- Info container outside preview box -->
                    <div id="label_info_container" class="mb-3">
                        <!-- Info will be populated by JavaScript -->
                    </div>

                    <div id="preview_box">
                        <div class="preview-empty">
                            <div class="preview-empty-content">
                                <i class="fas fa-eye preview-empty-icon"></i>
                                <p>Add products to see preview</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {!! Form::close() !!}
    </div>
</div>

@endsection

@section('javascript')
<!-- Keep your existing labels.js -->
<script src="{{ asset('js/labels.js?v=' . $asset_v) }}"></script>

<!-- Add the new enhancement file -->
<script src="{{ asset('js/labels-enhancements.js?v=' . $asset_v) }}"></script>

<script>
$(document).ready(function() {
    // Initialize toggle switches
    initializeToggleSwitches();

    // Initialize image source functionality
    initializeImageSource();

    // Initialize price type toggle
    initializePriceToggle();
});

// Toggle switch functionality
function initializeToggleSwitches() {
    $('.label-toggle').each(function() {
        const $checkbox = $(this);
        const $toggleSwitch = $checkbox.siblings('.toggle-switch');

        // Set initial state
        if ($checkbox.is(':checked')) {
            $toggleSwitch.addClass('active');
        }

        // Handle toggle click
        $toggleSwitch.on('click', function() {
            $checkbox.prop('checked', !$checkbox.is(':checked'));
            $(this).toggleClass('active', $checkbox.is(':checked'));
            fetchPreview();
        });
    });
}

// Image source functionality
function initializeImageSource() {
    toggleImageSource(); // Set initial state
}

function toggleImageSource() {
    const selectedSource = $('select[name="print[image_source]"]').val();
    if (selectedSource === 'select_image') {
        $('#select_image_url_row').removeClass('hidden').addClass('block');
    } else {
        $('#select_image_url_row').removeClass('block').addClass('hidden');
    }
    fetchPreview();
}

// Price type toggle
function initializePriceToggle() {
    const $priceToggle = $('#is_show_price');
    const $priceTypeDiv = $('#price_type_div');

    function togglePriceType() {
        if ($priceToggle.is(':checked')) {
            $priceTypeDiv.removeClass('hidden').addClass('block');
        } else {
            $priceTypeDiv.removeClass('block').addClass('hidden');
        }
        fetchPreview();
    }

    // Set initial state
    togglePriceType();

    // Listen for changes
    $priceToggle.on('change', togglePriceType);
}

// Enhanced size update function
function updateInputSize(inputName, step) {
    const $inputField = $(`input[name="${inputName}"]`);
    let currentValue = parseFloat($inputField.val()) || 0;

    currentValue += step;

    // Minimum value protection
    if (currentValue < 0.1) {
        currentValue = 0.1;
    }

    // Format the value
    if (currentValue % 1 === 0) {
        $inputField.val(currentValue.toFixed(0));
    } else {
        $inputField.val(currentValue.toFixed(1));
    }

    fetchPreview();
}

// Enhanced fetchPreview function that uses the enhanced endpoint
function fetchPreview() {
    if ($('form#preview_setting_form table#product_table tbody tr').length === 0) {
        $('#preview_box').html(`
            <div class="preview-empty">
                <div class="preview-empty-content">
                    <i class="fas fa-eye preview-empty-icon"></i>
                    <p>Add products to see preview</p>
                </div>
            </div>
        `);
        $('#print_label, #enhanced_print_label').prop('disabled', false).removeClass('opacity-50');
        return;
    }

    // Disable print buttons while loading
    $('#print_label, #enhanced_print_label').prop('disabled', true).addClass('opacity-50');

    // Show loading state
    $('#preview_box').html(`
        <div class="preview-empty">
            <div class="preview-empty-content">
                <div class="loading-spinner"></div>
                <p>Generating preview...</p>
            </div>
        </div>
    `);

    const formData = $('#preview_setting_form').serialize();

    $.ajax({
        url: '{{ url("/labels/enhanced-preview") }}',
        type: 'GET',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#preview_box').html(response.html);
                __currency_convert_recursively($('#preview_box'));

                // Auto-adjust preview scale for multiple labels
                if (typeof adjustPreviewScale === 'function') {
                    setTimeout(function() {
                        adjustPreviewScale();
                        $('#print_label, #enhanced_print_label').prop('disabled', false).removeClass('opacity-50');
                    }, 100);
                } else {
                    $('#print_label, #enhanced_print_label').prop('disabled', false).removeClass('opacity-50');
                }
            } else if (response.error) {
                $('#preview_box').html('<div class="text-red-500 text-center p-4">' + response.error + '</div>');
                $('#print_label, #enhanced_print_label').prop('disabled', false).removeClass('opacity-50');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#preview_box').html('<div class="text-red-500 text-center p-4">Error generating preview</div>');
            $('#print_label, #enhanced_print_label').prop('disabled', false).removeClass('opacity-50');
        }
    });
}
</script>

@endsection