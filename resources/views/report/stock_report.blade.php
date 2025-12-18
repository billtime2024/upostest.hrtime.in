@extends('layouts.app')
@section('title', __('report.stock_report'))

@section('content')

<!--<style>
.select2-selection__rendered > .select2-selection__choice:nth-child(1){
    display: none !important;
}
</style> -->
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('report.stock_report')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
              {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getStockReport']), 'method' => 'get', 'id' => 'stock_report_filter_form' ]) !!}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'multiple' => 'multiple']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('category_id', __('category.category') . ':') !!}
                        {!! Form::select('category', $categories, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'category_id', 'multiple' => 'multiple']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                        {!! Form::select('sub_category', array(), null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'sub_category_id', 'multiple' => 'multiple']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('brand', __('product.brand') . ':') !!}
                        {!! Form::select('brand', $brands, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'multiple' => 'multiple']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('unit',__('product.unit') . ':') !!}
                        {!! Form::select('unit', $units, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'multiple' => 'multiple']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('stock_filter', 'Stock Filter:') !!}
                        {!! Form::select('stock_filter', [
                        'exclude_zero_stock' => '> than 0 Stock',
                        'less_than_1_stock' => '< than 1 Stock',
                        'include_zero_stock' => 'All Stock',
                        ], 'exclude_zero_stock', ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                @if($show_manufacturing_data)
                    <div class="col-md-3">
                        <div class="form-group">
                            <br>
                            <div class="checkbox">
                                <label>
                                  {!! Form::checkbox('only_mfg', 1, false, 
                                  [ 'class' => 'input-icheck', 'id' => 'only_mfg_products']); !!} {{ __('manufacturing::lang.only_mfg_products') }}
                                </label>
                            </div>
                        </div>
                    </div>
                @endif
                 <div class="col-md-3">
                        <div class="form-group">
                            <br>
                            <div class="checkbox">
                                <label>
                                  {!! Form::checkbox('show_lot_number', 1, false, 
                                  [ 'class' => 'input-icheck', 'id' => 'show_lot_number']); !!} Show Lot Number
                                </label>
                            </div>
                        </div>
                    </div>
                
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    @can('view_product_stock_value')
    
     <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid'])
                <div class="stock_transfer_cards">
                    <div class="stock_transfer_card">
                        <div class="top">
                            <div class="money">
                                <h1 id="closing_stock_by_pp_round"></h1>
                                <span>@lang('report.closing_stock') (@lang('lang_v1.by_purchase_price'))</span>
                            </div>
                            <div class="icon">
                                  <img src="{{asset('images/others/purchase.svg')}}" style="width: 10vh; height: 10vh;" alt="inr_circle" />
                            </div>
                        </div>
                        <div class="bottom">
                            <div class="text"><h3 id="closing_stock_by_pp"></h3></div>
                            <div class="arrow"><img src="{{asset('images/others/growth.svg')}}" style="width: 5vh; height: 5vh;" alt="growth" /></div>
                        </div>
                    </div>
                    <div class="stock_transfer_card">
                        <div class="top">
                            <div class="money">
                                <h1 id="closing_stock_by_sp_round"></h1>
                                <span>@lang('report.closing_stock') (@lang('lang_v1.by_sale_price'))</span>
                            </div>
                            <div class="icon">
                                <img src="{{asset('images/others/sell.svg')}}" style="width: 10vh; height: 10vh;" alt="inr_circle" />
                            </div>
                        </div>
                        <div class="bottom">
                            <div class="text"><h3 id="closing_stock_by_sp"></h3></div>
                            <div class="arrow"><img src="{{asset('images/others/growth.svg')}}" style="width: 5vh; height: 5vh;" alt="growth" /></div>
                        </div>
                    </div>
                    <div class="stock_transfer_card">
                        <div class="top">
                            <div class="money">
                                <h1 id="potential_profit_round"></h1>
                                <span>@lang('lang_v1.potential_profit')</span>
                            </div>
                            <div class="icon">
                                <img src="{{asset('images/others/profit.png')}}" style="width: 10vh; height: 10vh;" alt="inr_circle" />
                            </div>
                        </div>
                        <div class="bottom">
                            <div class="text"><h3 id="potential_profit"></h3></div>
                            <div class="arrow"><img src="{{asset('images/others/growth.svg')}}" style="width: 5vh; height: 5vh;" alt="growth" /></div>
                        </div>
                    </div>
                    <div class="stock_transfer_card">
                        <div class="top">
                            <div class="money">
                                <h1 id="profit_margin_round"></h1>
                                <span>@lang('lang_v1.profit_margin')</span>
                            </div>
                            <div class="icon">
                                <img src="{{asset('images/others/percentage.png')}}" style="width: 10vh; height: 10vh;" alt="inr_circle" />
                            </div>
                        </div>
                        <div style="" class="bottom">
                            <div class="text"><h3 id="profit_margin"></h3></div>
                            <div class="arrow"><img src="{{asset('images/others/growth.svg')}}" style="width: 5vh; height: 5vh;" alt="growth" /></div>
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
        
      {{-- <div class="row">
           <div class="col-md-12">
        @component('components.widget', ['class' => 'box-solid'])
            <div class="stock_total_container" style="display: flex; justify-content: space-between; align-items: center; height: 100%;">
                <div class="col-md-3" style="background-color: #cce5e7; color: #fff;  min-height: 100%; border-radius: 1rem; width: 20%; height: 100%; padding: 0 !important;">
                       
                        <h5 style="color: #000 !important; text-align: center;">@lang('report.closing_stock') (@lang('lang_v1.by_purchase_price'))</h5>
                        <h1 id="closing_stock_by_pp_round" style="background-color: #412049 !important; color: #fff !important; width: 100%; min-height: 2rem; padding: 0.25rem; text-align: center;"></h1>
                        <div class="footer" style="display: flex; justify-content: center; align-items: center; min-width: 20%; ">
                            <h5 id="closing_stock_by_pp"  style="color: #000; "></h5>
                        </div>
                
                </div>
                <div class="col-md-3" style="background-color: #EF9651; color: #fff;  min-height: 100%; border-radius: 1rem; width: 20%; height: 100%;padding: 0 !important;">
                      
                        <h5 style="color: #000 !important;text-align:center;">@lang('report.closing_stock') (@lang('lang_v1.by_sale_price'))</h5>
                        <h1 id="closing_stock_by_sp_round" style="background-color: #412049 !important; color: #fff !important; width: 100%;  text-align: center;min-height: 2rem; padding: 0.25rem;"></h1>
                        <div class="footer" style="display: flex; justify-content: center; align-items: center; min-width: 20%; ">
                            <h5 id="closing_stock_by_sp"  style="color: #000; "></h5>
                        </div>
                   
                </div>
                <div class="col-md-3" style="background-color: #A0C878; color: #fff; 20%; min-height: 100%; border-radius: 1rem;width: 20%;height: 100%;padding: 0 !important;">
                       
                        <h5 style="color: #000 !important; text-align:center; ">@lang('lang_v1.potential_profit')</h5>
                        <h1 id="potential_profit_round" style="background-color: #412049 !important; color: #fff !important; width: 100%;  text-align: center; min-height: 2rem; padding: 0.25rem;"></h1>
                        <div class="footer" style="display: flex; justify-content: center; align-items: center; min-width: 20%; ">
                            <h5 id="potential_profit"  style="color: #000; "></h5>
                        </div>
                  
                </div>
                <div class="col-md-3" style="background-color: #A0C878; color: #fff; min-width: 20%; min-height: 100%; border-radius: 1rem;width: 20%;height: 100%;padding: 0 !important;">
                      
                                <h5 style="color: #000 !important;  text-align: center;">@lang('lang_v1.profit_margin')</h5>
                                <h1 id="profit_margin_round" style="background-color: #412049 !important; color: #fff !important; width: 100%;  text-align: center; min-height: 2rem; padding: 0.25rem;"></h1>
                                <div class="footer" style="display: flex; justify-content: center; align-items: center; min-width: 20%; ">
                                    <h5 id="profit_margin"  style="color: #000; "></h5>
                                </div>
                    
                    </div>
                </div>
                

        @endcomponent
                    </div>
    </div> --}}
    @endcan

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid'])
                @include('report.partials.stock_report_table')
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
@endsection
