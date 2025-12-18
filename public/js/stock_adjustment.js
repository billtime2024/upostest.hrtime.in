$(document).ready(function() {
    //Add products
    if ($('#search_product_for_srock_adjustment').length > 0) {
        //Add Product
        $('#search_product_for_srock_adjustment')
            .autocomplete({
                source: function(request, response) {
                    $.getJSON(
                        '/products/list',
                        { location_id: $('#location_id').val(), term: request.term },
                        response
                    );
                },
                minLength: 2,
                response: function(event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        if (ui.item.qty_available > 0 && ui.item.enable_stock == 1) {
                            $(this)
                                .data('ui-autocomplete')
                                ._trigger('select', 'autocompleteselect', ui);
                            $(this).autocomplete('close');
                        }
                    } else if (ui.content.length == 0) {
                        swal(LANG.no_products_found);
                    }
                },
                focus: function(event, ui) {
                    if (ui.item.qty_available <= 0) {
                        return false;
                    }
                },
                select: function(event, ui) {
                    if (ui.item.qty_available > 0) {
                        $(this).val(null);
                        stock_adjustment_product_row(ui.item.variation_id);
                    } else {
                        alert(LANG.out_of_stock);
                    }
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
            if (item.qty_available <= 0) {
                var string = '<li class="ui-state-disabled">' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') (Out of stock) </li>';
                return $(string).appendTo(ul);
            } else if (item.enable_stock != 1) {
                return ul;
            } else {
                var string = '<div>' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') </div>';
                return $('<li>')
                    .append(string)
                    .appendTo(ul);
            }
        };
    }

    $('select#location_id').change(function() {
        if ($(this).val()) {
            $('#search_product_for_srock_adjustment').removeAttr('disabled');
        } else {
            $('#search_product_for_srock_adjustment').attr('disabled', 'disabled');
        }
        $('table#stock_adjustment_product_table tbody').html('');
        $('#product_row_index').val(0);
        update_table_total();
    });

    $(document).on('change', 'input.product_quantity', function() {
        update_table_row($(this).closest('tr'));
    });
    $(document).on('change', 'input.product_unit_price', function() {
        update_table_row($(this).closest('tr'));
    });

    $(document).on('click', '.remove_product_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('tr')
                    .remove();
                update_table_total();
            }
        });
    });

    //Date picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    $('form#stock_adjustment_form').validate();

    stock_adjustment_table = $('#stock_adjustment_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader:false,
        ajax: '/stock-adjustments',
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                searchable: false,
            },
        ],
        aaSorting: [[1, 'desc']],
        columns: [
            { data: 'action', name: 'action' },
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'location_name', name: 'BL.name' },
            { data: 'adjustment_type', name: 'adjustment_type' },
            { data: 'final_total', name: 'final_total' },
            { data: 'total_amount_recovered', name: 'total_amount_recovered' },
            { data: 'additional_notes', name: 'additional_notes' },
            { data: 'added_by', name: 'u.first_name' },
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#stock_adjustment_table'));
        },
    });
    var detailRows = [];

    $(document).on('click', 'button.delete_stock_adjustment', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            stock_adjustment_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
});

function stock_adjustment_product_row(variation_id) {
    var row_index = parseInt($('#product_row_index').val());
    var location_id = $('select#location_id').val();
    $.ajax({
        method: 'POST',
        url: '/stock-adjustments/get_product_row',
        data: { row_index: row_index, variation_id: variation_id, location_id: location_id },
        dataType: 'html',
        success: function(result) {
            $('table#stock_adjustment_product_table tbody').append(result);
            update_table_total();
            $('#product_row_index').val(row_index + 1);
        },
    });
}

function update_table_total() {
    var table_total = 0;
    $('table#stock_adjustment_product_table tbody tr').each(function() {
        var this_total = parseFloat(__read_number($(this).find('input.product_line_total')));
        if (this_total) {
            table_total += this_total;
        }
    });
    $('input#total_amount').val(table_total);
    $('span#total_adjustment').text(__number_f(table_total));
}

function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity')));
    var unit_price = parseFloat(__read_number(tr.find('input.product_unit_price')));
    var row_total = 0;
    if (quantity && unit_price) {
        row_total = quantity * unit_price;
    }
    tr.find('input.product_line_total').val(__number_f(row_total));
    update_table_total();
}

$(document).on('shown.bs.modal', '.view_modal', function() {
    __currency_convert_recursively($('.view_modal'));
});

// IMEI/Serial selection functionality
$(document).on('click', '.select-imei-serial-btn', function () {
    var btn = $(this);
    var rowIndex = btn.data('row-index');
    var variationId = btn.data('variation-id');
    var itemType = btn.data('item-type');
    var locationId = $('#location_id').val();

    if (!locationId) {
        toastr.error('Please select a location first.');
        return;
    }

    // Store current row index in modal
    $('#imei-serial-modal').data('row-index', rowIndex);

    // Load available IMEI/Serial numbers
    $.ajax({
        method: 'GET',
        url: '/stock-adjustments/get-available-imei-serial',
        data: {
            variation_id: variationId,
            item_type: itemType,
            location_id: locationId
        },
        success: function (response) {
            if (response.success) {
                populateImeiSerialModal(response.data, itemType, rowIndex);
            } else {
                toastr.error(response.msg || 'Error loading IMEI/Serial numbers');
            }
        },
        error: function () {
            toastr.error('Error loading IMEI/Serial numbers');
        }
    });
});

function populateImeiSerialModal(identifiers, itemType, rowIndex) {
    var html = '<div class="row">';
    var label = itemType === 'imei' ? 'IMEI' : 'Serial';

    if (identifiers.length === 0) {
        html += '<div class="col-md-12"><p>No available ' + label + ' numbers found.</p></div>';
    } else {
        html += '<div class="col-md-12">';
        html += '<div class="mb-3">';
        html += '<button type="button" class="btn btn-sm btn-info" id="select-all-imei-serial">Select All</button> ';
        html += '<button type="button" class="btn btn-sm btn-warning" id="deselect-all-imei-serial">Deselect All</button>';
        html += '</div>';
        html += '<p>Select ' + label + ' numbers to adjust:</p>';
        html += '<div class="imei-serial-checkboxes" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">';

        // Get currently selected identifiers
        var selectedField = $('input[name="products[' + rowIndex + '][selected_imei_serial]"]');
        var selectedIdentifiers = selectedField.val() ? selectedField.val().split(',') : [];

        identifiers.forEach(function (identifier) {
            var checked = selectedIdentifiers.includes(identifier.identifier) ? 'checked' : '';
            html += '<div class="checkbox">';
            html += '<label>';
            html += '<input type="checkbox" class="imei-serial-checkbox" value="' + identifier.identifier + '" ' + checked + '> ';
            html += identifier.identifier;
            html += '</label>';
            html += '</div>';
        });

        html += '</div>';
        html += '</div>';
    }

    html += '</div>';
    $('#imei-serial-list').html(html);

    // Add event handlers for select all/deselect all
    $('#select-all-imei-serial').click(function() {
        $('.imei-serial-checkbox').prop('checked', true);
    });

    $('#deselect-all-imei-serial').click(function() {
        $('.imei-serial-checkbox').prop('checked', false);
    });
}

$(document).on('click', '#confirm-imei-serial-selection', function () {
    var selectedCheckboxes = $('.imei-serial-checkbox:checked');
    var selectedIdentifiers = [];
    var rowIndex = $('#imei-serial-modal').data('row-index');

    selectedCheckboxes.each(function () {
        selectedIdentifiers.push($(this).val());
    });

    // Update hidden field
    $('input[name="products[' + rowIndex + '][selected_imei_serial]"]').val(selectedIdentifiers.join(','));

    // Update quantity field (visible for IMEI/serial products)
    var quantityField = $('input[name="products[' + rowIndex + '][quantity]"]');
    quantityField.val(selectedIdentifiers.length);

    // Update display count in product name column
    $('#selected-count-' + rowIndex).text(selectedIdentifiers.length + ' selected');

    // Update row subtotal calculation
    var row = $('input[name="products[' + rowIndex + '][variation_id]"]').closest('tr');
    update_table_row(row);

    // Trigger change event on quantity field to ensure all calculations are updated
    quantityField.trigger('change');

    // Close modal
    $('#imei-serial-modal').modal('hide');

    // Update table total
    update_table_total();
});
