document.addEventListener("keydown", (e) => {
    // e.preventDefault();
    const saveBtn = document.getElementById("save_stock_transfer");
    if (e.key === "Enter") {
        if (saveBtn !== null) {
            saveBtn.disabled = true;
        }
    }
})

document.addEventListener("mousemove", () => {
    const saveBtn = document.getElementById("save_stock_transfer");
    if (saveBtn !== null) {
        saveBtn.disabled = false;
    }
})

$(document).ready(function () {
    var current_search_term = null;
    //Add products
    if ($('#search_product_for_srock_adjustment').length > 0) {
        //Add Product
        $('#search_product_for_srock_adjustment')
            .autocomplete({
                source: function (request, response) {
                    current_search_term = request.term;
                    $.getJSON(
                        '/products/list',
                        { location_id: $('#location_id').val(), term: request.term, search_fields: ['name', 'sku', 'lot', 'imei'] },
                        response
                    );
                },
                minLength: 2,
                response: function (event, ui) {
                    console.log(ui);
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
                focus: function (event, ui) {
                    if (ui.item.qty_available <= 0) {
                        return false;
                    }
                },
                select: function (event, ui) {
                    if (ui.item.qty_available > 0) {
                        $(this).val(null);
                        stock_transfer_product_row(ui.item.variation_id, ui.item.lot_number, 1, 0, false, current_search_term);
                    } else {
                        alert(LANG.out_of_stock);
                    }
                },
            })
            .autocomplete('instance')._renderItem = function (ul, item) {
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

    $('select#location_id').change(function () {
        if ($(this).val()) {
            $('#search_product_for_srock_adjustment').removeAttr('disabled');
        } else {
            $('#search_product_for_srock_adjustment').attr('disabled', 'disabled');
        }
        $('table#stock_adjustment_product_table tbody').html('');
        $('#product_row_index').val(0);
        update_table_total();
    });

    if ($("div#import_product_dz").length) {
        $("div#import_product_dz").dropzone({
            url: base_path + '/stock-transfer/add/import-file',
            paramName: 'file',
            autoProcessQueue: false,
            addRemoveLinks: true,
            uploadMultiple: false,
            maxFiles: 1,
            init: function () {
                this.on("addedfile", function (file) {
                    if ($('#location_id').val() == '') {
                        this.removeFile(file);
                        toastr.error('select location first');
                    }
                });
                this.on("maxfilesexceeded", function (file) {
                    this.removeAllFiles();
                    this.addFile(file);
                });
                this.on("sending", function (file, xhr, formData) {
                    // $("#loading_btn").click();
                    formData.append("location_id", $('#location_id').val());
                    formData.append("transfer_location_id", $('#transfer_location_id').val());
                    formData.append("status", $('#status').val());
                    formData.append('ref_no', $('#ref_no').val());
                    formData.append('transaction_date', $('#transaction_date').val());
                    formData.append("shipping_charges", $('#shipping_charges').val());
                    formData.append('additional_notes', $('#additional_notes').val());
                    formData.append("final_total", $('#total_amount').val());
                });
            },
            acceptedFiles: '.csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (file, response) {

                console.log("response: ", response);
                if (response.success) {
                    // toastr.success(response.msg);
                    //console.log("products: ", response.products);
                    let total_quantity = 0;
                    response.products.forEach((product, index) => {
                        console.log(product, product.location_id, product.lot_number)
                        if ((typeof product.lot_number === "string" && product.lot_number !== "") ||
                            (typeof product.lot_number === "number" && product.lot_number > 0)
                        ) {
                            total_quantity += parseInt(product.qty);
                            $.ajax({
                                method: 'GET',
                                url: "/products/list",
                                dataType: 'json',
                                data: { location_id: product.location_id, term: product.lot_number, search_fields: ['name', 'sku', 'lot', 'imei'] },
                                success: function (result) {

                                    console.log("Result: ", result);
                                    if (result.length > 0) {
                                        stock_transfer_product_row(result[0].variation_id, result[0].lot_number, product.qty, index, true);
                                    }
                                    document.querySelector(".loader-con").style.display = "none";
                                    // document.getElementById("loading_1").style.display = "none";
                                },
                            });
                        }
                    })
                    const tfoot = document.querySelector("tfoot");
                    const tfootHtml = tfoot.innerHTML;
                    const totalEle = tfoot.querySelector("tr > td:nth-child(2)");

                    tfoot.innerHTML = `
                <tr class="text-center show_price_with_permission">
                    <td>
                        <div class="pull-right"><b>Total Item: </b> <span>${response.products.length - 1}</span></div>
                    </td>
                    <td>
                        <div class="pull-right"><b>Total Quantity: </b> <span>${total_quantity}</span></div>
                    </td>
                     <td></td>
                    `+ totalEle.outerHTML + `
                </tr>
                `;
                    this.removeAllFiles();

                    $('#import_stock_transfer_modal').modal('hide');

                } else {
                    toastr.error(response.msg);
                }
                $('#import_stock_transfer_modal').modal('hide');

            },
            error: function (error) {
                console.log(error);
            }
        });
    }

    $(document).on('click', '#import_purchase_products', function () {
        var productDz = Dropzone.forElement("#import_product_dz");
        productDz.processQueue();
    })
    $('select#transfer_location_id').change(function () {
        if ($("select#location_id").val() == $("select#transfer_location_id").val() || $('#status').val() == '') {
            $("#import_products").attr('disabled', 'disable');
            if ($('#status').val() == '') {
                toastr.error("Status Can not be empty", "Error");
            }
            if ($("select#location_id").val() == $("select#transfer_location_id").val()) {
                toastr.error("Location Form and Location To must be different", "Error");
            }
            console.log($("select#location_id").val() == $("select#transfer_location_id").val() || $('#status').val() == '');
            console.log($("select#location_id").val(), $("select#transfer_location_id").val(), $('#status').val() == '')
        } else {
            $("#import_products").removeAttr('disabled');

        }
    })

    $("#status").change(function () {
        if ($("select#location_id").val() == $("select#transfer_location_id").val() || $('#status').val() == '') {
            $("#import_products").attr('disabled', 'disable');
            if ($('#status').val() == '') {
                toastr.error("Status Can not be empty", "Error");
            }
            if ($("select#location_id").val() == $("select#transfer_location_id").val()) {
                toastr.error("Location Form and Location To must be different", "Error");
            }
            console.log($("select#location_id").val() == $("select#transfer_location_id").val() || $('#status').val() == '');
            console.log($("select#location_id").val(), $("select#transfer_location_id").val(), $('#status').val() == '')
        } else {
            $("#import_products").removeAttr('disabled');

        }
    })

    $(document).on('change', 'input.product_quantity', function () {
        update_table_row($(this).closest('tr'));
    });
    $(document).on('change', 'input.product_unit_price', function () {
        update_table_row($(this).closest('tr'));
    });

    $(document).on('click', '.remove_product_row', function () {
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

    jQuery.validator.addMethod(
        'notEqual',
        function (value, element, param) {
            return this.optional(element) || value != param;
        },
        'Please select different location'
    );

    $('form#stock_transfer_form').validate({
        rules: {
            transfer_location_id: {
                notEqual: function () {
                    return $('select#location_id').val();
                },
            },
        },
    });
    $('#save_stock_transfer').click(function (e) {
        e.preventDefault();

        // $("#loading_btn").click();
        if ($('table#stock_adjustment_product_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }
        if ($('form#stock_transfer_form').valid()) {
            $(".side-bar-collapse").click();
            document.querySelector(".loader-con").style.display = "block";
            $('form#stock_transfer_form').submit();
        } else {
            return false;
        }
        // document.getElementById("loading_1").style.display = "none";
    });

    stock_transfer_table = $('#stock_transfer_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader: false,
        aaSorting: [[0, 'desc']],
        ajax: {
            url: '/stock-transfers',
            data: function(d) {
                if ($('#stock_transfer_list_filter_location_from').length) {
                    d.location_from = $('#stock_transfer_list_filter_location_from').val();
                }
                if ($('#stock_transfer_list_filter_location_to').length) {
                    d.location_to = $('#stock_transfer_list_filter_location_to').val();
                }
                if ($('#stock_transfer_list_filter_status').length) {
                    d.status = $('#stock_transfer_list_filter_status').val();
                }

                var start = '';
                var end = '';
                if ($('#stock_transfer_list_filter_date_range').val()) {
                    start = $('input#stock_transfer_list_filter_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#stock_transfer_list_filter_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }
                d.start_date = start;
                d.end_date = end;

                d = __datatable_ajax_callback(d);
            },
        },
        columnDefs: [
            {
                targets: 8,
                orderable: false,
                searchable: false,
            },
        ],
        columns: [
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'location_from', name: 'l1.name' },
            { data: 'location_to', name: 'l2.name' },
            { data: 'status', name: 'status' },
            { data: 'shipping_charges', name: 'shipping_charges' },
            { data: 'final_total', name: 'final_total' },
            { data: 'additional_notes', name: 'additional_notes' },
            { data: 'action', name: 'action' },
        ],
        fnDrawCallback: function (oSettings) {
            __currency_convert_recursively($('#stock_transfer_table'));
        },
    });

    //Date range as a button
    $('#stock_transfer_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function(start, end) {
            $('#stock_transfer_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                moment_date_format));
            stock_transfer_table.ajax.reload();
        }
    );
    $('#stock_transfer_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#stock_transfer_list_filter_date_range').val('');
        stock_transfer_table.ajax.reload();
    });

    $(document).on('change',
        '#stock_transfer_list_filter_location_from, \
        #stock_transfer_list_filter_location_to, \
        #stock_transfer_list_filter_status',
        function() {
            stock_transfer_table.ajax.reload();
        }
    );

    $(document).on('click', '#stock_transfer_list_filter_reset', function() {
        $('#stock_transfer_list_filter_location_from').val('').trigger('change');
        $('#stock_transfer_list_filter_location_to').val('').trigger('change');
        $('#stock_transfer_list_filter_status').val('').trigger('change');
        $('#stock_transfer_list_filter_date_range').val('').trigger('change');
        stock_transfer_table.ajax.reload();
    });

    var detailRows = [];

    $('#stock_transfer_table tbody').on('click', '.view_stock_transfer', function () {
        var tr = $(this).closest('tr');
        var row = stock_transfer_table.row(tr);
        var idx = $.inArray(tr.attr('id'), detailRows);

        if (row.child.isShown()) {
            $(this)
                .find('i')
                .removeClass('fa-eye')
                .addClass('fa-eye-slash');
            row.child.hide();

            // Remove from the 'open' array
            detailRows.splice(idx, 1);
        } else {
            $(this)
                .find('i')
                .removeClass('fa-eye-slash')
                .addClass('fa-eye');

            row.child(get_stock_transfer_details(row.data())).show();

            // Add to the 'open' array
            if (idx === -1) {
                detailRows.push(tr.attr('id'));
            }
        }
    });

    // On each draw, loop over the `detailRows` array and show any child rows
    stock_transfer_table.on('draw', function () {
        $.each(detailRows, function (i, id) {
            $('#' + id + ' .view_stock_transfer').trigger('click');
        });
    });

    //Delete Stock Transfer
    $(document).on('click', 'button.delete_stock_transfer', function () {
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
                    success: function (result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            stock_transfer_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
});

function stock_transfer_product_row(variation_id, lot_number = 0, quantity = 1, row_index = 0, is_import = false, search_term = null) {
    if (!is_import) {
        if (localStorage.getItem("row_index") != null) {
            var row_index = parseInt(localStorage.getItem("row_index")) + 1;
            localStorage.setItem("row_index", row_index);
        } else {
            var row_index = parseInt($('#product_row_index').val());
        }
    }
    var location_id = $('select#location_id').val();
    $.ajax({
        method: 'POST',
        url: '/stock-adjustments/get_product_row',
        data: { row_index: row_index, variation_id: variation_id, location_id: location_id, type: 'stock_transfer', lot_number: lot_number },
        dataType: 'html',
        success: function (result) {
            $('table#stock_adjustment_product_table tbody').append(result);

            const qtyEle = [...document.querySelectorAll(".product_quantity")][$("#product_row_index").val()];
            console.log("Row index: ", row_index);
            qtyEle.value = quantity;

            // Check if product is IMEI/Serial and auto-select available numbers
            var productRow = $('table#stock_adjustment_product_table tbody tr').last();
            var itemType = productRow.find('input[name="products[' + row_index + '][item_type]"]').val();
            if (itemType && ['imei', 'serial'].includes(itemType)) {
                autoSelectImeiSerial(row_index, variation_id, itemType, location_id, search_term);
            }

            update_table_total();
            if (!is_import) {
                $('#product_row_index').val(row_index + 1);
            } else {
                if (localStorage.getItem('row_index') !== null) {
                    if (localStorage.getItem('row_index') < row_index) {
                        console.log("Storing Row index: ", row_index, "is_import: ", is_import);
                        localStorage.setItem("row_index", row_index);
                    }
                } else {
                    console.log("Storing Row index: ", row_index, "is_import: ", is_import);
                    localStorage.setItem("row_index", row_index);
                }
            }
        },
    });
}

function update_table_total() {
    var table_total = 0;
    $('table#stock_adjustment_product_table tbody tr').each(function () {
        var this_total = parseFloat(__read_number($(this).find('input.product_line_total')));
        if (this_total) {
            table_total += this_total;
        }
    });

    $('span#total_adjustment').text(__number_f(table_total));

    if ($('input#shipping_charges').length) {
        var shipping_charges = __read_number($('input#shipping_charges'));
        table_total += shipping_charges;
    }

    $('span#final_total_text').text(__number_f(table_total));
    $('input#total_amount').val(table_total);
}

$(document).on('change', '#shipping_charges', function () {
    update_table_total();
});

$(document).on('change', 'select.sub_unit', function () {
    var tr = $(this).closest('tr');
    var selected_option = $(this).find(':selected');
    var multiplier = parseFloat(selected_option.data('multiplier'));
    var allow_decimal = parseInt(selected_option.data('allow_decimal'));
    tr.find('input.base_unit_multiplier').val(multiplier);

    var base_unit_price = tr.find('input.hidden_base_unit_price').val();

    var unit_price = base_unit_price * multiplier;
    var unit_price_element = tr.find('input.product_unit_price');
    __write_number(unit_price_element, unit_price);

    var qty_element = tr.find('input.product_quantity');
    var base_max_avlbl = qty_element.data('qty_available');
    var error_msg_line = 'pos_max_qty_error';

    if (tr.find('select.lot_number').length > 0) {
        var lot_select = tr.find('select.lot_number');
        if (lot_select.val()) {
            base_max_avlbl = lot_select.find(':selected').data('qty_available');
            error_msg_line = 'lot_max_qty_error';
        }
    }
    qty_element.attr('data-decimal', allow_decimal);
    var abs_digit = true;
    if (allow_decimal) {
        abs_digit = false;
    }
    qty_element.rules('add', {
        abs_digit: abs_digit,
    });

    if (base_max_avlbl) {
        var max_avlbl = parseFloat(base_max_avlbl) / multiplier;
        var formated_max_avlbl = __number_f(max_avlbl);
        var unit_name = selected_option.data('unit_name');
        var max_err_msg = __translate(error_msg_line, {
            max_val: formated_max_avlbl,
            unit_name: unit_name,
        });
        qty_element.attr('data-rule-max-value', max_avlbl);
        qty_element.attr('data-msg-max-value', max_err_msg);
        qty_element.rules('add', {
            'max-value': max_avlbl,
            messages: {
                'max-value': max_err_msg,
            },
        });
        qty_element.trigger('change');
    }
    qty_element.valid();
    update_table_row($(this).closest('tr'));
});

$(document).on('change', 'select.lot-select', function () {
    var tr = $(this).closest('tr');
    var lot_val = $(this).val();
    var imei_btn = tr.find('.imei-btn');
    var imei_selected = tr.find('.imei-selected');
    var item_type = tr.find('input[name*="item_type"]').val();
    var qty_field = tr.find('input.product_quantity');
    if (lot_val) {
        // Lot selected, disable IMEI
        imei_btn.prop('disabled', true);
        imei_selected.val(''); // Clear IMEI selection
        tr.find('.selected-imei-serial-count').text('0 selected');
        tr.find('.imei-serial-qty').val(0);
        // If IMEI product, make quantity editable
        if (['imei', 'serial'].includes(item_type)) {
            qty_field.prop('readonly', false);
        }
    } else {
        // Lot not selected, enable IMEI
        imei_btn.prop('disabled', false);
        // If IMEI product, make quantity readonly
        if (['imei', 'serial'].includes(item_type)) {
            qty_field.prop('readonly', true);
        }
    }
    update_table_row(tr);
});

function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity')));
    var multiplier = 1;

    if (tr.find('select.sub_unit').length) {
        multiplier = parseFloat(
            tr.find('select.sub_unit')
                .find(':selected')
                .data('multiplier')
        );
    }
    quantity = quantity * multiplier;

    var unit_price = __read_number(tr.find('input.product_unit_price'));
    var row_total = 0;
    if (quantity && unit_price) {
        row_total = quantity * unit_price;
    }
    tr.find('input.product_line_total').val(__number_f(row_total));
    update_table_total();
}

function get_stock_transfer_details(rowData) {
    var div = $('<div/>')
        .addClass('loading')
        .text('Loading...');
    $.ajax({
        url: '/stock-transfers/' + rowData.DT_RowId,
        dataType: 'html',
        success: function (data) {
            div.html(data).removeClass('loading');
        },
    });

    return div;
}

$(document).on('click', 'a.stock_transfer_status', function (e) {
    e.preventDefault();
    var href = $(this).data('href');
    var status = $(this).data('status');
    $('#update_stock_transfer_status_modal').modal('show');
    $('#update_stock_transfer_status_form').attr('action', href);
    $('#update_stock_transfer_status_form #update_status').val(status);
    $('#update_stock_transfer_status_form #update_status').trigger('change');
});

$(document).on('submit', '#update_stock_transfer_status_form', function (e) {
    e.preventDefault();
    var form = $(this);
    var data = form.serialize();

    $.ajax({
        method: 'post',
        url: $(this).attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function (xhr) {
            __disable_submit_button(form.find('button[type="submit"]'));
        },
        success: function (result) {
            if (result.success == true) {
                $('div#update_stock_transfer_status_modal').modal('hide');
                toastr.success(result.msg);
                stock_transfer_table.ajax.reload();
            } else {
                toastr.error(result.msg);
            }
            $('#update_stock_transfer_status_form')
                .find('button[type="submit"]')
                .attr('disabled', false);
        },
    });
});
$(document).on('shown.bs.modal', '.view_modal', function () {
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
        toastr.error('Please select a source location first.');
        return;
    }

    // Store current row index in modal
    $('#imei-serial-modal').data('row-index', rowIndex);

    // Load available IMEI/Serial numbers
    $.ajax({
        method: 'GET',
        url: '/imei-serial/get-available',
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
        html += '<p>Select ' + label + ' numbers to transfer:</p>';
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

    // Conditional logic: if IMEI selected, disable lot select and make quantity readonly
    var row = $('input[name="products[' + rowIndex + '][variation_id]"]').closest('tr');
    var lot_select = row.find('.lot-select');
    var qty_field = row.find('input.product_quantity');
    if (selectedIdentifiers.length > 0) {
        lot_select.prop('disabled', true);
        lot_select.val(''); // Clear lot selection
        qty_field.prop('readonly', true);
    } else {
        lot_select.prop('disabled', false);
        qty_field.prop('readonly', false);
    }

    // Update row subtotal calculation
    update_table_row(row);

    // Trigger change event on quantity field to ensure all calculations are updated
    quantityField.trigger('change');

    // Close modal
    $('#imei-serial-modal').modal('hide');

    // Update table total
    update_table_total();
});

function autoSelectImeiSerial(rowIndex, variationId, itemType, locationId, searchTerm = null) {
    $.ajax({
        method: 'GET',
        url: '/imei-serial/get-available',
        data: {
            variation_id: variationId,
            item_type: itemType,
            location_id: locationId
        },
        success: function (response) {
            if (response.success && response.data.length > 0) {
                var selectedIdentifiers = [];

                if (searchTerm && response.data.some(function(item) { return item.identifier === searchTerm; })) {
                    // If search term matches an available IMEI, select only that one
                    selectedIdentifiers = [searchTerm];
                } else {
                    // Otherwise, select all available
                    selectedIdentifiers = response.data.map(function(item) {
                        return item.identifier;
                    });
                }

                // Update hidden field
                $('input[name="products[' + rowIndex + '][selected_imei_serial]"]').val(selectedIdentifiers.join(','));

                // Update quantity field
                $('input[name="products[' + rowIndex + '][quantity]"]').val(selectedIdentifiers.length);

                // Update display count
                $('#selected-count-' + rowIndex).text(selectedIdentifiers.length + ' selected');

                // Update row subtotal calculation
                var row = $('input[name="products[' + rowIndex + '][variation_id]"]').closest('tr');
                update_table_row(row);

                // Trigger change event on quantity field to ensure all calculations are updated
                $('input[name="products[' + rowIndex + '][quantity]"]').trigger('change');

                // Update table total
                update_table_total();
            }
        },
        error: function () {
            console.error('Error auto-selecting IMEI/Serial numbers');
        }
    });
}