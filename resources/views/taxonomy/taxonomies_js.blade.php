<script type="text/javascript">
    $(document).ready( function() {

        function getTaxonomiesIndexPage () {
            var data = {category_type : $('#category_type').val()};
            $.ajax({
                method: "GET",
                dataType: "html",
                url: '/taxonomies-ajax-index-page',
                data: data,
                async: false,
                success: function(result){
                    $('.taxonomy_body').html(result);
                }
            });
        }

        function initializeTaxonomyDataTable() {
            //Category table
            if ($('#category_table').length) {
                var category_type = $('#category_type').val();
                category_table = $('#category_table').DataTable({
                    processing: true,
                    serverSide: true,
                    fixedHeader:false,
                    ajax: '/taxonomies?type=' + category_type,
                    columns: [
                        { data: 'name', name: 'name' },
                        @if($cat_code_enabled)
                            { data: 'short_code', name: 'short_code' },
                        @endif
                        { data: 'description', name: 'description' },
                        { data: 'action', name: 'action', orderable: false, searchable: false},
                    ],
                    fnDrawCallback: function () {
                        const data = this.fnGetData();
                        const categories = [];

                        
                        data.forEach(category => {
                             categories[category.name.split("</br>")[0]] = {};
                              categories[category.name.split("</br>")[0]].name = category.name.split("</br>")[0];
                              categories[category.name.split("</br>")[0]].edit =category.action.split("&nbsp;")[0];
                              categories[category.name.split("</br>")[0]].delete = category.action.split("&nbsp;")[1];
                              categories[category.name.split("</br>")[0]].sub_categoies = [];
                            category.name.split("</br>").forEach((sub_category, index) => {
                                if(index !== 0 && sub_category.split("---")[0] !== "" ) {
                                   
                                    const new_sub_category =  {}
                                    new_sub_category.name = sub_category.split("---")[1].split("</span>")[0];
                                    new_sub_category.edit = sub_category.split("---")[1].split("</span>")[1].split("&nbsp;")[1];
                                    new_sub_category.delete =   sub_category.split("---")[1].split("</span>")[1].split("&nbsp;")[2];
                                    categories[category.name.split("</br>")[0]].sub_categoies.push(new_sub_category);
                                }
                            })
                        })
                        
                        const categoryTable = document.getElementById("category_table");
                        let result = "";
                        
                        for( property in categories) {
                             result += "<tr><td>" + categories[property].name + "</td><td></td<td></td><td></td><td>"+ categories[property].edit + categories[property].delete + "</td></tr>";
                            //  console.log(categories[property])
                             categories[property].sub_categoies.forEach(sub_category => {
                                  result += "<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;---" + sub_category.name + "</td><td><td></td></td<td></td><td>"+ sub_category.edit + sub_category.delete + "</td></tr>";
                             })
                        }
                     
                        
                        categoryTable.querySelector("tbody").innerHTML = result;
                        console.log(categories);
                    }
                });
           
            }
        }

        @if(empty(request()->get('type')))
            getTaxonomiesIndexPage();
        @endif

        initializeTaxonomyDataTable();
    });
    $(document).on('submit', 'form#category_add_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var data = form.serialize();

        $.ajax({
            method: 'POST',
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            beforeSend: function(xhr) {
                __disable_submit_button(form.find('button[type="submit"]'));
            },
            success: function(result) {
                if (result.success === true) {
                    $('div.category_modal').modal('hide');
                    toastr.success(result.msg);
                    if(typeof category_table !== 'undefined') {
                        category_table.ajax.reload();
                    }

                    var evt = new CustomEvent("categoryAdded", {detail: result.data});
                    window.dispatchEvent(evt);

                    //event can be listened as
                    //window.addEventListener("categoryAdded", function(evt) {}
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
    $(document).on('click', 'button.edit_category_button', function() {
        $('div.category_modal').load($(this).data('href'), function() {
            $(this).modal('show');

            $('form#category_edit_form').submit(function(e) {
                e.preventDefault();
                var form = $(this);
                var data = form.serialize();

                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    beforeSend: function(xhr) {
                        __disable_submit_button(form.find('button[type="submit"]'));
                    },
                    success: function(result) {
                        if (result.success === true) {
                            $('div.category_modal').modal('hide');
                            toastr.success(result.msg);
                            category_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });
        });
    });

    $(document).on('click', 'button.delete_category_button', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                var data = $(this).serialize();

                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success === true) {
                            toastr.success(result.msg);
                            category_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
    
    
</script>