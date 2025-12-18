@extends('layouts.app')
@section('title', 'Identifier History')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Identifier History</h1>
</section>

<!-- Main content -->
<section class="content">
<div class="row">
    <div class="col-md-12">
    @component('components.widget', ['title' => ''])
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('identifier_type', 'Identifier Type' . ':') }}
                {{ Form::select('identifier_type', ['lot_number' => 'Lot Number', 'imei' => 'IMEI', 'serial' => 'Serial Number'], old('identifier_type', 'lot_number'), array('class' => 'form-control', 'style' => 'width: 100%', 'id' => 'identifier_type')) }}
            </div>
            <div class="form-group">
                {{ Form::label('identifier_search', 'Identifier' . ':') }}
                {{ Form::text('identifier_search', old('identifier'),  array('class' => 'form-control', 'style' => 'width: 100%', 'placeholder' => 'Enter Identifier', 'id' => 'identifier')) }}
            </div>
        </div>
        
    @endcomponent
    @component('components.widget')
        <div id="product_stock_history" style="display: none;"></div>
    @endcomponent
    </div>
</div>

</section>
<!-- /.content -->
@endsection

@section('javascript')
   <script type="text/javascript">

       function load_stock_history(identifier, type) {
           $('#product_stock_history').fadeOut();
           if(identifier.length > 0){
               $.ajax({
                   url: '/reports/get_lot_history/' + type + '/' + identifier,
                   dataType: 'html',
                   success: function(result) {
                       $('#product_stock_history')
                           .html(result)
                           .fadeIn();

                       __currency_convert_recursively($('#product_stock_history'));

                       $('#stock_history_table').DataTable({
                           searching: false,
                           fixedHeader:false,
                           ordering: false
                       });
                   },
               })
           }
       }

       $(document).on('change', '#identifier', function(){
           var identifier = $('#identifier').val();
           var type = $('#identifier_type').val();
           load_stock_history(identifier, type);
       });
   </script>
@endsection
