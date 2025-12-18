@php
    $currency_symbol = \App\Business::find(auth()->user()->business_id)->currency["symbol"];
    function changeToIndianStyle ($inital_number)
    {
             $number = (float) $inital_number;
        $value_maps = [
                2 => "H",
                3 => "Th",
                4 => "Th",
                5 => "L",
                6 => "L",
                7 => "Cr",
            ];
        $lowest_map_number = 5;
        $highest_map_number = 7;
       $number_before_decimal = (int) explode(".", $inital_number)[0];
       $number_before_decimal_as_string =  explode(".", $inital_number)[0];

        if($number !== 0.0 || $inital_number == 0){
            if(strlen($number_before_decimal_as_string) <= $lowest_map_number) {
               
                return (string) round($number / pow(10, $lowest_map_number), 2) . " ". $value_maps[$lowest_map_number];
            } else if(strlen($number_before_decimal_as_string) > $highest_map_number) {

                $new_number = round($number / pow(10, $highest_map_number), 2);
                return $new_number . " " . $value_maps[$highest_map_number];
            } else {
            
                $number_before_decimal_length = strlen(explode(".", $inital_number)[0]);
                if($number_before_decimal_length % 2 === 0 ){
                    $new_number = round($number / pow(10, $number_before_decimal_length - 1), 2);
                    return $new_number . " " . $value_maps[$number_before_decimal_length - 1];
                } else if( $number_before_decimal_length % 2 === 1) {
                    $new_number = round($number / pow(10, $number_before_decimal_length - 2), 2);
                    return $new_number . " " . $value_maps[$number_before_decimal_length - 2];
                }
                 
            }
        } else {

            return "please, provide a valid number";
        }
    }

@endphp  
                    <div class="stock_transfer_cards">
                    <div class="stock_transfer_card">
                        <div class="top">
                            <div class="money">
                                <h1>{{$currency_symbol . "  ". changeToIndianStyle((($data['opening_stock'] + $data['total_purchase']) - $data['closing_stock']))}}</h1>
                                <span>@lang('lang_v1.cogs')</span>
                            </div>
                            <div class="icon">
                                  <img src="{{asset('images/others/gross_profit.svg')}}" style="width: 10vh; height: 10vh;" alt="inr_circle" />
                            </div>
                        </div>
                        <div class="bottom">
                            <div class="text"><h3> {{$currency_symbol  . " ".  round(($data['opening_stock'] + $data['total_purchase']) - $data['closing_stock'], 2) }}</h3></div>
                            <div class="arrow"><img src="{{asset('images/others/growth.svg')}}" style="width: 5vh; height: 5vh;" alt="growth" /></div>
                        </div>
                    </div>
                    <div class="stock_transfer_card">
                        <div class="top">
                            <div class="money">
                                <h1>{{$currency_symbol . "  ".changeToIndianStyle($data['gross_profit'])}}</h1>
                                <span>{{ __('lang_v1.gross_profit') }}</span>
                            </div>
                            <div class="icon">
                                <img src="{{asset('images/others/COGS.svg')}}" style="width: 10vh; height: 10vh;" alt="inr_circle" />
                            </div>
                        </div>
                        <div class="bottom">
                            <div class="text"><h3>{{$currency_symbol  . " " . round($data['gross_profit'], 2)}}</h3></div>
                            <div class="arrow"><img src="{{asset('images/others/growth.svg')}}" style="width: 5vh; height: 5vh;" alt="growth" /></div>
                        </div>
                    </div>
                    <div class="stock_transfer_card">
                        <div class="top">
                            <div class="money">
                                <h1>{{$currency_symbol . "  ".changeToIndianStyle($data['net_profit'])}}</h1>
                                <span>{{ __('report.net_profit') }}</span>
                            </div>
                            <div class="icon">
                                <img src="{{asset('images/others/profit.png')}}" style="width: 10vh; height: 10vh;" alt="inr_circle" />
                            </div>
                        </div>
                        <div class="bottom">
                            <div class="text"><h3>{{$currency_symbol . " " . round($data['net_profit'], 2)}}</h3></div>
                            <div class="arrow"><img src="{{asset('images/others/growth.svg')}}" style="width: 5vh; height: 5vh;" alt="growth" /></div>
                        </div>
                    </div>
                </div>
                
            {{--<div class="stock_total_container" style="display: flex; justify-content: space-between; align-items: center; height: 100%;">
                <div class="col-md-4" style="background-color: #649ff0; color: #fff;  min-height: 100%; border-radius: 1rem; width: 20%; height: 100%; padding: 0 !important;">
                       
                        <h5 style="color: #000 !important; text-align: center;">@lang('lang_v1.cogs')</h5>
                        <h1 id="closing_stock_by_pp_round" style="background-color: #412049 !important; color: #fff !important; width: 100%; min-height: 2rem; padding: 0.25rem; text-align: center;">{{$currency_symbol . "  ". changeToIndianStyle((($data['opening_stock'] + $data['total_purchase']) - $data['closing_stock']))}}</h1>
                        <div class="footer" style="display: flex; justify-content: center; align-items: center; min-width: 20%; ">
                            <h5  style="color: #000; " class="display_currency" data-currency_symbol="true"> {{ (($data['opening_stock'] + $data['total_purchase']) - $data['closing_stock']) }}</h5>
                        </div>
                
                </div>
                                <div class="col-md-4" style="background-color: #eff066; color: #fff; 20%; min-height: 100%; border-radius: 1rem;width: 20%;height: 100%;padding: 0 !important;">
                       
                        <h5 style="color: #000 !important; text-align:center; "> {{ __('lang_v1.gross_profit') }}</h5>
                        <h1 id="potential_profit_round" style="background-color: #412049 !important; color: #fff !important; width: 100%;  text-align: center; min-height: 2rem; padding: 0.25rem;">{{$currency_symbol . "  ".changeToIndianStyle($data['gross_profit'])}}</h1>
                        <div class="footer" style="display: flex; justify-content: center; align-items: center; min-width: 20%; ">
                            <h5  style="color: #000; " class="display_currency" data-currency_symbol="true">{{$data['gross_profit']}}</h5>
                        </div>
                  
                </div>
                <div class="col-md-4" style="background-color: #79ea86; color: #fff;  min-height: 100%; border-radius: 1rem; width: 20%; height: 100%;padding: 0 !important;">
                      
                        <h5 style="color: #000 !important;text-align:center;">{{ __('report.net_profit') }}</h5>
                        <h1 id="closing_stock_by_sp_round" style="background-color: #412049 !important; color: #fff !important; width: 100%;  text-align: center;min-height: 2rem; padding: 0.25rem;">{{$currency_symbol . "  ".changeToIndianStyle($data['net_profit'])}}</h1>
                        <div class="footer" style="display: flex; justify-content: center; align-items: center; min-width: 20%; ">
                            <h5   style="color: #000; " class="display_currency" data-currency_symbol="true">{{$data['net_profit']}}</h5>
                        </div>
                   
                </div>

                <!--<div class="col-md-4" style="background-color: #A0C878; color: #fff; min-width: 20%; min-height: 100%; border-radius: 1rem;width: 20%;height: 100%;padding: 0 !important;">-->
                      
                <!--                <h5 style="color: #000 !important;  text-align: center;">@lang('lang_v1.profit_margin')</h5>-->
                <!--                <h1 id="profit_margin_round" style="background-color: #412049 !important; color: #fff !important; width: 100%;  text-align: center; min-height: 2rem; padding: 0.25rem;"></h1>-->
                <!--                <div class="footer" style="display: flex; justify-content: center; align-items: center; min-width: 20%; ">-->
                <!--                    <h5 id="profit_margin"  style="color: #000; "></h5>-->
                <!--                </div>-->
                    
                <!--    </div>-->
                <!--</div>-->
                
            </div>--}}

{{--<h3 class="text-muted mb-0">
    @lang('lang_v1.cogs')&nbsp;&nbsp;<span class="display_currency" data-currency_symbol="true"> {{ (($data['opening_stock'] + $data['total_purchase']) - $data['closing_stock']) }}</span>
</h3>--}}
    <small class="help-block">
        <b>@lang('lang_v1.cogs')</b> @lang('lang_v1.cogs_help_text')
    </small>
{{--<h3 class="text-muted mb-0">
    {{ __('lang_v1.gross_profit') }}: 
    <span class="display_currency" data-currency_symbol="true">{{$data['gross_profit']}}</span>
</h3> --}}
<small class="help-block">
    <b>@lang('lang_v1.gross_profit')</b>&nbsp;&nbsp;
    (@lang('lang_v1.total_sell_price') - @lang('lang_v1.total_purchase_price'))
    @if(!empty($data['gross_profit_label']))
        {{-- + {{$data['gross_profit_label']}} --}}
        @foreach ($data['gross_profit_label'] as $val)
            + {{$val}}
        @endforeach
    @endif
</small>

{{--<h3 class="text-muted mb-0">
    {{ __('report.net_profit') }}: 
    <span class="display_currency" data-currency_symbol="true">{{$data['net_profit']}}</span>
</h3>--}}
<small class="help-block"><b>@lang('report.net_profit')</b>&nbsp;&nbsp;@lang('lang_v1.gross_profit') + (@lang('lang_v1.total_sell_shipping_charge') + @lang('lang_v1.sell_additional_expense') + @lang('report.total_stock_recovered') + @lang('lang_v1.total_purchase_discount') + @lang('lang_v1.total_sell_round_off') 
@foreach($data['right_side_module_data'] as $module_data)
    @if(!empty($module_data['add_to_net_profit']))
        + {{$module_data['label']}} 
    @endif
@endforeach
) <br> - ( @lang('report.total_stock_adjustment') + @lang('report.total_expense') + @lang('lang_v1.total_purchase_shipping_charge') + @lang('lang_v1.total_transfer_shipping_charge') + @lang('lang_v1.purchase_additional_expense') + @lang('lang_v1.total_sell_discount') + @lang('lang_v1.total_reward_amount') 
@foreach($data['left_side_module_data'] as $module_data)
    @if(!empty($module_data['add_to_net_profit']))
        + {{$module_data['label']}}
    @endif 
@endforeach )</small>