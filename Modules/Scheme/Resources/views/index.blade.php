@extends('layouts.app')

@section('title', __('scheme::lang.schemes'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('scheme::lang.schemes') }}</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('scheme::lang.all_schemes')])
        @slot('tool')
            @can('scheme.create')
                <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right tw-m-2"
                    href="{{ action([\Modules\Scheme\Http\Controllers\SchemeController::class, 'create']) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 5l0 14" />
                        <path d="M5 12l14 0" />
                    </svg>
                    @lang('messages.add')
                </a>
            @endcan
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="scheme_table">
                <thead>
                    <tr>
                        <th>@lang('scheme::lang.scheme_name')</th>
                        <th>@lang('scheme::lang.scheme_type')</th>
                        <th>Multi Level</th>
                        <th>@lang('scheme::lang.slab_calculation_type')</th>
                        <th>@lang('scheme::lang.slab_details')</th>
                        <th>@lang('scheme::lang.scheme_amount')</th>
                        <th>@lang('contact.supplier')</th>
                        <th>@lang('report.products')</th>
                        <th>@lang('scheme::lang.starts_at')</th>
                        <th>@lang('scheme::lang.ends_at')</th>
                        <th>@lang('Status')</th>
                        <th>@lang('scheme::lang.sold_qty')</th>
                        <th>@lang('scheme::lang.total_eligible_amount')</th>
                        <th>@lang('lang_v1.added_by')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
   var scheme_table = $('#scheme_table').DataTable({
       processing: true,
       serverSide: true,
       ajax: '{{ action([\Modules\Scheme\Http\Controllers\SchemeController::class, 'index']) }}',
       columns: [
           { data: 'scheme_name', name: 'schemes.scheme_name' },
           { data: 'scheme_type_display', name: 'schemes.scheme_type' },
           { data: 'multi_level', name: 'schemes.multi_level' },
           { data: 'slab_type_display', name: 'schemes.slab_calculation_type' },
           { data: 'slab_details', name: 'slab_details' },
           { data: 'scheme_amount', name: 'schemes.scheme_amount' },
           { data: 'supplier_name', name: 's.name' },
           { data: 'products', name: 'products' },
           { data: 'starts_at', name: 'schemes.starts_at' },
           { data: 'ends_at', name: 'schemes.ends_at' },
           { data: 'status', name: 'status' },
           { data: 'sold_qty', name: 'sold_qty' },
           { data: 'eligible_amount', name: 'eligible_amount' },
           { data: 'created_by_name', name: 'created_by_name' },
           { data: 'action', name: 'action', orderable: false, searchable: false }
       ]
   });

   $(document).on('click', '.delete-scheme', function(e) {
       e.preventDefault();
       var url = $(this).data('href');

       swal({
           title: LANG.sure,
           text: 'Are you sure you want to delete this scheme?',
           icon: 'warning',
           buttons: true,
           dangerMode: true,
       }).then((confirmed) => {
           if (confirmed) {
               $.ajax({
                   url: url,
                   type: 'DELETE',
                   dataType: 'json',
                   success: function(result) {
                       if (result.success) {
                           toastr.success(result.msg);
                           scheme_table.ajax.reload();
                       } else {
                           toastr.error(result.msg);
                       }
                   }
               });
           }
       });
   });
});
</script>
@endsection
