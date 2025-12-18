$(document).ready(function() {
    $('table#product_table tbody').find('.label-date-picker').each( function(){
        $(this).datepicker({
            autoclose: true
        });
    });
    //Add products
    if ($('#search_product_for_label').length > 0) {
        $('#search_product_for_label')
            .autocomplete({
                source: '/purchases/get_products?check_enable_stock=false',
                minLength: 2,
                response: function(event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        $(this)
                            .data('ui-autocomplete')
                            ._trigger('select', 'autocompleteselect', ui);
                        $(this).autocomplete('close');
                    } else if (ui.content.length == 0) {
                        swal(LANG.no_products_found);
                    }
                },
                select: function(event, ui) {
                    $(this).val(null);
                    get_label_product_row(ui.item.product_id, ui.item.variation_id);
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
            return $('<li>')
                .append('<div>' + item.text + '</div>')
                .appendTo(ul);
        };
    }

    $('input#is_show_price').change(function() {
        if ($(this).is(':checked')) {
            $('div#price_type_div').show();
        } else {
            $('div#price_type_div').hide();
        }
    });

    $('button#labels_preview').click(function() {
        if ($('form#preview_setting_form table#product_table tbody tr').length > 0) {
            
            var url = base_path + '/labels/preview?' + $('form#preview_setting_form').serialize();
            const csrfToken = document.querySelector('[name="csrf-token"]').content;
            //  window.open(url, 'newwindow');
            // browsers doses not support loading with url characters more than 2000 charaxters.
function urlToJson(urlEncoded) {
  try {
    const decoded = decodeURIComponent(urlEncoded);
    const params = new URLSearchParams(decoded);
    const json = {};
    for (const [key, value] of params) {
      json[key] = value;
    }
    return json;
  } catch (error) {
    console.error("Error decoding or parsing URL:", error);
    return null;
  }
}

function openWindowWithPost(url, data, windowName) {
  const form = document.createElement('form');
  form.method = 'post';
  form.action = url;
  form.target = windowName || '_blank';

  for (const key in data) {
    if (data.hasOwnProperty(key)) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = key;
      input.value = data[key];
      form.appendChild(input);
    }
  }
const tokenEle = document.createElement('input');
tokenEle.type = 'hidden';
tokenEle.name = '_token';
tokenEle.value =$('meta[name=csrf-token]').attr('content');
 form.appendChild(tokenEle);
  document.body.appendChild(form);
  const newWindow = window.open('', form.target);

console.log(form);
  if (newWindow) {
      form.submit();
  } else {
      alert('Popup blocked! Please allow popups for this site.');
  }

  document.body.removeChild(form);
}

openWindowWithPost(url.split("?")[0], urlToJson(url), 'newwindow');
            // if(url.length > 2000){
            //     localStorage.setItem("test", "sucessful");
            //     localStorage.setItem("print_data",  url);
            //     const newUrl = url.split("?")[0];
            //     window.open(newUrl, 'newwindow');
            //     // toastr.error("Maximum amount reached", "Error");
            // }else {
            //  window.open(url, 'newwindow');
            // }
            // $.ajax({
            //     method: 'get',
            //     url: '/labels/preview',
            //     dataType: 'json',
            //     data: $('form#preview_setting_form').serialize(),
            //     success: function(result) {
            //         if (result.success) {
            //             $('div.display_label_div').removeClass('hide');
            //             $('div#preview_box').html(result.html);
            //             __currency_convert_recursively($('div#preview_box'));
            //         } else {
            //             toastr.error(result.msg);
            //         }
            //     },
            // });
        } else {
            swal(LANG.label_no_product_error).then(value => {
                $('#search_product_for_label').focus();
            });
        }
    });

    $(document).on('click', 'button#print_label', function() {
        // console.log(localStorage.getItem("test"));
        // const printData = localStorage.getItem("print_data");
        // if (printData !== undefined && printData.length > 0){
        //     document.URL = printData;
        //     window.print();
        // } else {
        //     window.print();
        // }
        window.print();
    });
});

function get_label_product_row(product_id, variation_id) {
    if (product_id) {
        var row_count = $('table#product_table tbody tr').length;
        $.ajax({
            method: 'GET',
            url: '/labels/add-product-row',
            dataType: 'html',
            data: { product_id: product_id, row_count: row_count, variation_id: variation_id },
            success: function(result) {
                $('table#product_table tbody').append(result);

                $('table#product_table tbody').find('.label-date-picker').each( function(){
                    $(this).datepicker({
                        autoclose: true
                    });
                });
                   fetchPreview();
            },
             error: function (xhr, status, error) {
                console.error('Error adding product row:', error);
            },
        });
    }
}
