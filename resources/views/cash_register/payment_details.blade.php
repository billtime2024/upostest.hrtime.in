@php

$closed_at =  $register_details->closed_at == null ? \Carbon\Carbon::now()->format('Y-m-d\ h:i:s') : $register_details->closed_at;

$is_same_date = explode("-", explode(" ", $closed_at)[0])[2] == explode("-", explode(" ", $register_details->open_time)[0])[2];


$repair_sell_payments = \App\CashRegisterTransaction::where('cash_register_transactions.cash_register_id', $register_details->id)
    ->join(
        "cash_registers as c",
        "c.id",
        "=",
        "cash_register_transactions.cash_register_id"
        )
    ->join(
        "transactions as t",
        "t.id",
        "=",
         "cash_register_transactions.transaction_id"
    )
    ->where("sub_type", "repair")
    ->where("cash_register_transactions.Transaction_type", "sell")
    ->select(
        "cash_register_transactions.amount as amount",
        "cash_register_transactions.pay_method as method"
    )
    ->get();
    
$new_repair_sell_payments = [];
foreach($repair_sell_payments as $repair_sell_payment){
    if(!isset($new_repair_sell_payments[$repair_sell_payment['method']])){
        $new_repair_sell_payments[$repair_sell_payment['method']] = (float) $repair_sell_payment['amount'];
    } else {
        $new_repair_sell_payments[$repair_sell_payment['method']] += (float) $repair_sell_payment['amount'];
    }
}

$new_credit_sell = \App\Transaction::where("transactions.type", "sell")
    ->where("transactions.payment_status", "partial")
      ->whereRaw(" DATE(transactions.created_at) = '" . explode(' ', $register_details->open_time)[0] ."'
  AND TIME(transactions.created_at) BETWEEN '" . explode(' ', $register_details->open_time)[1] ."' AND '". explode(' ', $closed_at)[1] ."'")
    ->join("transaction_payments as tp", "transactions.id", "=", "tp.transaction_id")
    ->select(
        "tp.amount as amount",
        "tp.method as method",
        "transactions.total_before_tax as total_amount",
    )
    ->get();
$due_sell = \App\Transaction::where("transactions.type", "sell")
    ->where("transactions.payment_status", "due")
    ->whereRaw(" DATE(transactions.created_at) = '" . explode(' ', $register_details->open_time)[0] ."'
  AND TIME(transactions.created_at) BETWEEN '" . explode(' ', $register_details->open_time)[1] ."' AND '". explode(' ', $closed_at)[1] ."'")
        ->select(
            "total_before_tax as amount"
        )
        ->get();
$total_credit_sell = 0;
foreach($new_credit_sell as $signle_credit_sell) {
    $total_credit_sell += ((float) $signle_credit_sell["total_amount"] - (float) $signle_credit_sell["amount"]);
}

foreach($due_sell as $single_due_sell) {
    $total_credit_sell += $single_due_sell["amount"];
}


$total_direct_credit_sell = 0;
$direct_credit_sell = \App\CashRegisterTransaction::where('cash_register_transactions.cash_register_id', $register_details->id)
    ->join(
        "cash_registers as c",
        "c.id",
        "=",
        "cash_register_transactions.cash_register_id"
        )
    ->join(
        "transactions as t",
        "t.id",
        "=",
         "cash_register_transactions.transaction_id"
    )
    ->where("cash_register_transactions.type", "credit")
    ->where("cash_register_transactions.Transaction_type", "sell")
    ->select(
        "cash_register_transactions.amount as amount",
        "cash_register_transactions.pay_method as method",
        "t.payment_status as status"
    )
    ->get();
    
    $direct_credit_sell_payments = \App\CashRegisterTransaction::where('cash_register_transactions.cash_register_id', $register_details->id)
    ->join(
        "cash_registers as c",
        "c.id",
        "=",
        "cash_register_transactions.cash_register_id"
        )
    ->join(
        "transactions as t",
        "t.id",
        "=",
         "cash_register_transactions.transaction_id"
    )
    ->where("cash_register_transactions.type", "credit")
    ->where("cash_register_transactions.Transaction_type", "sell")
    ->where("t.payment_status", "partial")
    ->select(
        "cash_register_transactions.amount as amount",
        "cash_register_transactions.pay_method as method",
        
        
    )
    ->get();
 
    $new_direct_credit_sell_payments = [];
foreach($direct_credit_sell as $single_direct_credit_sell) {
    $total_direct_credit_sell += (float) $single_direct_credit_sell["amount"];
}

$total_direct_credit_sell_payment =0;

foreach($direct_credit_sell_payments as $single_direct_credit_sell_payment) {
    $total_direct_credit_sell_payment += (float) $single_direct_credit_sell_payment["amount"];
}


if($is_same_date) {

$expanse_payments = \App\Transaction::where('transactions.created_by', $register_details->user_id)
                ->whereRaw(" DATE(transactions.created_at) = '" . explode(' ', $register_details->open_time)[0] ."'
  AND TIME(transactions.created_at) BETWEEN '" . explode(' ', $register_details->open_time)[1] ."' AND '". explode(' ', $closed_at)[1] ."'")
                ->where('transactions.type', 'expense')
                ->join('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->select(
                    'tp.amount as amount',
                    'tp.method as method',
                )
                ->get();
                

$total_direct_sell = \App\Transaction::where('transactions.created_by', $register_details->user_id)
                ->whereRaw(" DATE(transactions.created_at) = '" . explode(' ', $register_details->open_time)[0] ."'
  AND TIME(transactions.created_at) BETWEEN '" . explode(' ', $register_details->open_time)[1] ."' AND '". explode(' ', $closed_at)[1] ."'")
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->where('transactions.is_direct_sale', 1)
                ->join('transaction_sell_lines AS TSL', 'transactions.id', '=', 'TSL.transaction_id')
                ->select(
                    DB::raw('SUM(TSL.quantity) as total_quantity'),
                    DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity) as total_amount')
                )
                ->get();
                
$direct_sell_payments = \App\Transaction::where('transactions.created_by', $register_details->user_id)
               ->whereRaw(" DATE(transactions.created_at) = '" . explode(' ', $register_details->open_time)[0] ."'
  AND TIME(transactions.created_at) BETWEEN '" . explode(' ', $register_details->open_time)[1] ."' AND '". explode(' ', $closed_at)[1] ."'")
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->where('transactions.is_direct_sale', 1)
                ->join('transaction_sell_lines AS TSL', 'transactions.id', '=', 'TSL.transaction_id')
                ->join('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->where('TSL.children_type', '!=', 'combo')
                ->select(
                    'tp.amount as amount',
                    'tp.method as method',
                )
                ->get();
;
$repair_payments = \App\Transaction::where("transactions.sub_type", "repair")
    ->where("transactions.business_id", auth()->user()->business_id)
->whereRaw(" DATE(transactions.created_at) = '" . explode(' ', $register_details->open_time)[0] ."'
  AND TIME(transactions.created_at) BETWEEN '" . explode(' ', $register_details->open_time)[1] ."' AND '". explode(' ', $closed_at)[1] ."'")
    ->join("transaction_payments as tp", "transactions.id", "=", "tp.transaction_id")
   // ->where("tp.is_advance", 1)
    ->get();
    
        
  $repair_payments_advance = \App\Transaction::where("transactions.sub_type", "repair")
    ->where("transactions.business_id", auth()->user()->business_id)
    ->whereRaw(" DATE(transactions.created_at) = '" . explode(' ', $register_details->open_time)[0] ."'
  AND TIME(transactions.created_at) BETWEEN '" . explode(' ', $register_details->open_time)[1] ."' AND '". explode(' ', $closed_at)[1] ."'")
    ->join("transaction_payments as tp", "transactions.id", "=", "tp.transaction_id")
    ->where("tp.is_advance", 1)
    ->get();
   
$supplier_due_payment = \App\TransactionPayment::where('Business_id', auth()->user()->business_id)
    ->where('payment_type', 'debit')
    ->whereNull('transaction_id')
    ->whereRaw(" DATE(transaction_payments.created_at) = '" . explode(' ', $register_details->open_time)[0] ."'
  AND TIME(transaction_payments.created_at) BETWEEN '" . explode(' ', $register_details->open_time)[1] ."' AND '". explode(' ', $closed_at)[1] ."'")
    ->select(
        'id',
        'amount',
        'method',
        'payment_type',
        'transaction_no',
    )
    ->get();
    
$customer_due_payment = \App\TransactionPayment::where('Business_id', auth()->user()->business_id)
    ->where('payment_type', 'credit')
    ->whereNull('transaction_id')
    ->whereRaw(" DATE(transaction_payments.created_at) = '" . explode(' ', $register_details->open_time)[0] ."'
  AND TIME(transaction_payments.created_at) BETWEEN '" . explode(' ', $register_details->open_time)[1] ."' AND '". explode(' ', $closed_at)[1] ."'")
  ->select(
        'id',
        'amount',
        'method',
        'payment_type',
        'transaction_no',
    )
    ->get();

} else {
    
$expanse_payments = \App\Transaction::where('transactions.created_by', $register_details->user_id)
                ->whereBetween('transactions.created_at', [$register_details->open_time, $closed_at])
                ->where('transactions.type', 'expense')
                ->join('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->select(
                    'tp.amount as amount',
                    'tp.method as method',
                )->get();
                

$total_direct_sell = \App\Transaction::where('transactions.created_by', $register_details->user_id)
                ->whereBetween('transactions.created_at', [$register_details->open_time, $closed_at])
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->where('transactions.is_direct_sale', 1)
                ->join('transaction_sell_lines AS TSL', 'transactions.id', '=', 'TSL.transaction_id')
                ->select(
                    DB::raw('SUM(TSL.quantity) as total_quantity'),
                    DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity) as total_amount')
                )
                ->get();
                
$direct_sell_payments = \App\Transaction::where('transactions.created_by', $register_details->user_id)
                ->whereBetween('transactions.created_at', [$register_details->open_time, $closed_at])
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->where('transactions.is_direct_sale', 1)
                ->join('transaction_sell_lines AS TSL', 'transactions.id', '=', 'TSL.transaction_id')
                ->join('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->where('TSL.children_type', '!=', 'combo')
                ->select(
                    'tp.amount as amount',
                    'tp.method as method',
                )
                ->get();
                
$repair_payments = \App\Transaction::where("transactions.sub_type", "repair")
    ->where("transactions.business_id", auth()->user()->business_id)
    ->whereBetween('transactions.created_at', [$register_details->open_time, $closed_at])
    ->join("transaction_payments as tp", "transactions.id", "=", "tp.transaction_id")
    ->get();
    
  $repair_payments_advance = \App\Transaction::where("transactions.sub_type", "repair")
    ->where("transactions.business_id", auth()->user()->business_id)
    ->whereBetween('transactions.created_at', [$register_details->open_time, $closed_at])
    ->join("transaction_payments as tp", "transactions.id", "=", "tp.transaction_id")
    ->where("tp.is_advance", 1)
    ->get();
    
$supplier_due_payment = \App\TransactionPayment::where('Business_id', auth()->user()->business_id)
    ->where('payment_type', 'debit')
    ->whereNull('transaction_id')
    ->whereBetween('created_at', [$register_details->open_time, $closed_at])
    ->select(
        'id',
        'amount',
        'method',
        'payment_type',
        'transaction_no',
    )
    ->get();
    
$customer_due_payment = \App\TransactionPayment::where('Business_id', auth()->user()->business_id)
    ->where('payment_type', 'credit')
    ->whereNull('transaction_id')
    ->whereBetween('created_at', [$register_details->open_time, $closed_at])
    ->select(
        'id',
        'amount',
        'method',
        'payment_type',
        'transaction_no',
    )
    ->get();
}

            //    dd($expanse_payments->toSql(), $register_details->open_time, $closed_at);
$new_expense_payments = [];
$total_expense_payments = 0;
foreach($expanse_payments as $expense_payment){
    if(!isset($new_expense_payments[$expense_payment["method"]])){
        $new_expense_payments[$expense_payment["method"]] = (float) $expense_payment["amount"];
    } else {
        $new_expense_payments[$expense_payment["method"]] += (float) $expense_payment["amount"];
    }
    $total_expense_payments += (float) $expense_payment["amount"];
}
//dd($total_expense_payments, $expanse_payments, $new_expense_payments);

$total_direct_sell_payments = 0;
$new_direct_sell_payments = [];
foreach($direct_sell_payments as $single_direct_sell_payment){
    if(!isset($new_direct_sell_payments[$single_direct_sell_payment["method"]])) {
        $new_direct_sell_payments[$single_direct_sell_payment["method"]] = (float) $single_direct_sell_payment["amount"];
    } else {
        $new_direct_sell_payments[$single_direct_sell_payment["method"]] += (float) $single_direct_sell_payment["amount"];
    }
    $total_direct_sell_payments += (float) $single_direct_sell_payment["amount"];
}

$new_repair_payment_advance = [];
//dd($repair_payments);
foreach($repair_payments as $repair_payment){
        if(!isset($new_repair_payment_advance[$repair_payment["method"]])){
            $new_repair_payment_advance[ $repair_payment["method"]] = (float) $repair_payment["amount"];
        } else {
            $new_repair_payment_advance[ $repair_payment["method"]] += (float) $repair_payment["amount"];
        }
}

$new_repair_payment_advance_advance = [];
foreach( $repair_payments_advance as $repair_payment_advance) {
    if(!isset($new_repair_payment_advance_advance[$repair_payment_advance["method"]])) {
        $new_repair_payment_advance_advance[$repair_payment_advance["method"]] = (float) $repair_payment_advance["amount"];
    } else {
     $new_repair_payment_advance_advance[$repair_payment_advance["method"]] += (float) $repair_payment_advance["amount"];
    }
}

$total_supplier_due_payment = 0;
$new_supplier_due_payment = [];
foreach($supplier_due_payment as $single_supplier_due_payment) {
    if(!isset($new_supplier_due_payment[$single_supplier_due_payment["method"]])){
        $new_supplier_due_payment[$single_supplier_due_payment["method"]] = (float) $single_supplier_due_payment["amount"];
    }else {
        $new_supplier_due_payment[$single_supplier_due_payment["method"]] +=  (float) $single_supplier_due_payment["amount"];
    }
    $total_supplier_due_payment += (float) $single_supplier_due_payment["amount"];
}

$total_customer_due_payment = 0;
$new_customer_due_payment = [];
foreach($customer_due_payment as $single_supplier_due_payment) {
    if(!isset($new_customer_due_payment[$single_supplier_due_payment["method"]])){
        $new_customer_due_payment[$single_supplier_due_payment["method"]] = (float) $single_supplier_due_payment["amount"];
    }else {
        $new_customer_due_payment[$single_supplier_due_payment["method"]] +=  (float) $single_supplier_due_payment["amount"];
    }
    $total_customer_due_payment += (float) $single_supplier_due_payment["amount"];
}

$is_repair_module = isset(\App\Business::find(auth()->user()->business_id)->subscriptions[0]['package_details']["repair_module"]) && (int) \App\Business::find(auth()->user()->business_id)->subscriptions[0]['package_details']["repair_module"] == 1 ? true : false;

@endphp

<div class="row mini_print">
  <div class="col-sm-12">
    <table class="table table-condensed">
      <tr>
        <th>@lang('lang_v1.payment_method')</th>
        <th>Direct Sale</th>
        <th>Pos Sale</th>
        @if( $is_repair_module)
           {{-- <th>Repair Sale</th>--}}
            <th>Repair Advance</th>
        @endif
        <th>@lang('lang_v1.supplier_due')</th>
        <th>@lang('lang_v1.customer_due')</th>
        <th>@lang('lang_v1.expense')</th>
      </tr>
      <tr>
        <td>
          @lang('cash_register.cash_in_hand'):
        </td>
        <td>--</td>
        <td>
          <span class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand }}</span>
        </td>

        @if($is_repair_module)
            {{--<td>--</td>--}}
            <td>--</td>
        @endif
        <td>--</td>
        <td>--</td>
        <td>--</td>
      </tr>
      <tr>
        <td>
          @lang('cash_register.cash_payment'):
        </td>
        <td>@if(isset($new_direct_sell_payments["cash"])) <span class="display_currency" data-currency_symbol="true">{{$new_direct_sell_payments["cash"]}}</span>@else -- @endif</td>
        <td>
          <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash }} </span>
        </td>
        @if($is_repair_module)
            {{--<td>
                @if(isset($new_repair_sell_payments["cash"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_sell_payments["cash"]}}</span> @else -- @endif
            </td>--}}
            <td>@if (isset($new_repair_payment_advance_advance["cash"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_payment_advance_advance["cash"]}}</span> @else -- @endif</td>
        @endif
        <td>@if(isset($new_supplier_due_payment["cash"])) <span class="display_currency" data-currency_symbol="true">{{$new_supplier_due_payment["cash"]}}</span> @else -- @endif</td>
        <td>@if(isset($new_customer_due_payment["cash"])) <span class="display_currency" data-currency_symbol="true">{{$new_customer_due_payment["cash"]}}</span> @else -- @endif</td>
            <td>
                @if(isset($new_expense_payments['cash']))
          <span class="display_currency" data-currency_symbol="true">{{ $new_expense_payments['cash'] }}</span>
          @else
          --
          @endif
        </td>
      </tr>
      <tr>
        <td>
          @lang('cash_register.checque_payment'):
        </td>
        <td>@if(isset($new_direct_sell_payments["checque_payment"])) <span class="display_currency" data-currency_symbol="true">{{$new_direct_sell_payments["checque_payment"]}}</span>@else -- @endif</td>
        <td>
          <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cheque }}</span>
        </td>
        @if( $is_repair_module)
         {{--<td>
                @if(isset($new_repair_sell_payments["checque_payment"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_sell_payments["checque_payment"]}}</span> @else -- @endif
            </td>--}}
            <td>@if (isset($new_repair_payment_advance_advance["checque_payment"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_payment_advance_advance["checque_payment"]}}</span> @else -- @endif</td>
        @endif
          <td>@if(isset($new_supplier_due_payment["checque_payment"])) <span class="display_currency" data-currency_symbol="true">{{$new_supplier_due_payment["checque_payment"]}}</span> @else -- @endif</td>
        <td>@if(isset($new_customer_due_payment["checque_payment"])) <span class="display_currency" data-currency_symbol="true">{{$new_customer_due_payment["checque_payment"]}}</span> @else -- @endif</td>
            <td>
                @if(isset($new_expense_payments['checque_payment']))
          <span class="display_currency" data-currency_symbol="true">{{ $new_expense_payments['checque_payment'] }}</span>
          @else
          --
          @endif
        </td>
      </tr>
      <tr>
        <td>
          @lang('cash_register.card_payment'):
        </td>
         <td>@if(isset($new_direct_sell_payments["card"])) <span class="display_currency" data-currency_symbol="true">{{$new_direct_sell_payments["card"]}}</span>@else -- @endif</td>
        <td>
          <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_card }}</span>
        </td>
        @if($is_repair_module)
        {{-- <td>
                @if(isset($new_repair_sell_payments["card"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_sell_payments["card"]}}</span> @else -- @endif
            </td> --}}
            <td>@if (isset($new_repair_payment_advance_advance["card"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_payment_advance_advance["card"]}}</span> @else -- @endif</td>
        @endif
           <td>@if(isset($new_supplier_due_payment["card"])) <span class="display_currency" data-currency_symbol="true">{{$new_supplier_due_payment["card"]}}</span> @else -- @endif</td>
        <td>@if(isset($new_customer_due_payment["card"])) <span class="display_currency" data-currency_symbol="true">{{$new_customer_due_payment["card"]}}</span> @else -- @endif</td>
                     <td>
                @if(isset($new_expense_payments['card']))
          <span class="display_currency" data-currency_symbol="true">{{ $new_expense_payments['card'] }}</span>
          @else
          --
          @endif
        </td>
      </tr>
      <tr>
        <td>
          @lang('cash_register.bank_transfer'):
        </td>
        <td>@if(isset($new_direct_sell_payments["bank_transfer"])) <span class="display_currency" data-currency_symbol="true">{{$new_direct_sell_payments["bank_transfer"]}}</span>@else -- @endif</td>
        <td>
          <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_bank_transfer }}</span>
        </td>
        @if($is_repair_module)
         {{--<td>
              @if(isset($new_repair_sell_payments["bank_transfer"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_sell_payments["bank_transfer"]}}</span> @else -- @endif
            </td>--}}
            <td>@if (isset($new_repair_payment_advance_advance["bank_transfer"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_payment_advance_advance["bank_transfer"]}}</span> @else -- @endif</td>
        @endif
        <td>@if(isset($new_supplier_due_payment["bank_transfer"])) <span class="display_currency" data-currency_symbol="true">{{$new_supplier_due_payment["bank_transfer"]}}</span> @else -- @endif</td>
        <td>@if(isset($new_customer_due_payment["bank_transfer"])) <span class="display_currency" data-currency_symbol="true">{{$new_customer_due_payment["bank_transfer"]}}</span> @else -- @endif</td>
           <td>
                @if(isset($new_expense_payments['bank_transfer']))
          <span class="display_currency" data-currency_symbol="true">{{ $new_expense_payments['bank_transfer'] }}</span>
          @else
          --
          @endif
        </td>
      </tr>
      <!--<tr>-->
      <!--  <td>-->
      <!--    @lang('lang_v1.advance_payment'):-->
      <!--  </td>-->
      <!--  <td>-->
      <!--    <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_advance }} ( @if($is_repair_module) + {{$register_details->total_repair_advance}}) @endif</span>-->
      <!--  </td>-->
      <!--  <td>-->
      <!--    <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_advance_expense }}</span>-->
      <!--  </td>-->
      <!--</tr>-->

      @if(array_key_exists('custom_pay_1', $payment_types))
        <tr>
          <td>
            {{$payment_types['custom_pay_1']}}:
          </td>
               <td>@if(isset($new_direct_sell_payments["custom_pay_1"])) <span class="display_currency" data-currency_symbol="true">{{$new_direct_sell_payments["custom_pay_1"]}}</span>@else -- @endif</td>
          <td>
            <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_1 }}</span>
          </td>
          @if($is_repair_module)
                   {{--<td>
                @if(isset($new_repair_sell_payments["custom_pay_1"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_sell_payments["custom_pay_1"]}}</span> @else -- @endif
            </td>--}}
              <td>@if (isset($new_repair_payment_advance_advance['custom_pay_1'])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_payment_advance_advance['custom_pay_1']}}</span> @endif</td>
            @endif
            <td>@if(isset($new_supplier_due_payment["custom_pay_1"])) <span class="display_currency" data-currency_symbol="true">{{$new_supplier_due_payment["custom_pay_1"]}}</span> @else -- @endif</td>
        <td>@if(isset($new_customer_due_payment["custom_pay_1"])) <span class="display_currency" data-currency_symbol="true">{{$new_customer_due_payment["custom_pay_1"]}}</span> @else -- @endif</td>
              <td>
                @if(isset($new_expense_payments['custom_pay_1']))
          <span class="display_currency" data-currency_symbol="true">{{ $new_expense_payments['custom_pay_1'] }}</span>
          @else
          --
          @endif
        </td>
        </tr>
      @endif
      @if(array_key_exists('custom_pay_2', $payment_types))
        <tr>
          <td>
            {{$payment_types['custom_pay_2']}}:
          </td>
            <td>@if(isset($new_direct_sell_payments["custom_pay_2"])) <span class="display_currency" data-currency_symbol="true">{{$new_direct_sell_payments["custom_pay_2"]}}</span>@else -- @endif</td>
          <td>
            <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_2 }}</span>
          </td>
          @if($is_repair_module)
               {{-- <td>
                @if(isset($new_repair_sell_payments["custom_pay_2"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_sell_payments["custom_pay_2"]}}</span> @else -- @endif
            </td> --}}
              <td>
                 @if (isset($new_repair_payment_advance_advance['custom_pay_2'])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_payment_advance_advance['custom_pay_2']}}</span> @else -- @endif
              </td>
            @endif
             <td>@if(isset($new_supplier_due_payment["custom_pay_2"])) <span class="display_currency" data-currency_symbol="true">{{$new_supplier_due_payment["custom_pay_2"]}}</span> @else -- @endif</td>
        <td>@if(isset($new_customer_due_payment["custom_pay_2"])) <span class="display_currency" data-currency_symbol="true">{{$new_customer_due_payment["custom_pay_2"]}}</span> @else -- @endif</td>
          <td>
                @if(isset($new_expense_payments['custom_pay_2']))
          <span class="display_currency" data-currency_symbol="true">{{ $new_expense_payments['custom_pay_2'] }}</span>
          @else
          --
          @endif
        </td>
        </tr>
      @endif
      @if(array_key_exists('custom_pay_3', $payment_types))
        <tr>
          <td>
            {{$payment_types['custom_pay_3']}}:
          </td>
            <td>@if(isset($new_direct_sell_payments["custom_pay_3"])) <span class="display_currency" data-currency_symbol="true">{{$new_direct_sell_payments["custom_pay_3"]}}</span>@else -- @endif</td>
          <td>
            <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_3 }}</span>
          </td>
          @if($is_repair_module)
           {{--<td>
                @if(isset($new_repair_sell_payments["custom_pay_3"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_sell_payments["custom_pay_3"]}}</span> @else -- @endif
            </td>--}}
              <td>
                  @if (isset($new_repair_payment_advance_advance['custom_pay_3'])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_payment_advance_advance['custom_pay_3']}}</span> @else -- @endif
              </td>
            @endif
            <td>@if(isset($new_supplier_due_payment["custom_pay_3"])) <span class="display_currency" data-currency_symbol="true">{{$new_supplier_due_payment["custom_pay_3"]}}</span> @else -- @endif</td>
        <td>@if(isset($new_customer_due_payment["custom_pay_3"])) <span class="display_currency" data-currency_symbol="true">{{$new_customer_due_payment["custom_pay_3"]}}</span> @else -- @endif</td>
<td>
                @if(isset($new_expense_payments['custom_pay_3']))
          <span class="display_currency" data-currency_symbol="true">{{ $new_expense_payments['custom_pay_3'] }}</span>
          @else
          --
          @endif
        </td>
        </tr>
      @endif
      @if(array_key_exists('custom_pay_4', $payment_types))
        <tr>
          <td>
            {{$payment_types['custom_pay_4']}}:
          </td>
           <td>@if(isset($new_direct_sell_payments["custom_pay_4"])) <span class="display_currency" data-currency_symbol="true">{{$new_direct_sell_payments["custom_pay_4"]}}</span>@else -- @endif</td>
          <td>
            <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_4 }}</span>
          </td>
          @if($is_repair_module)
             {{--<td>
                @if(isset($new_repair_sell_payments["custom_pay_4"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_sell_payments["custom_pay_4"]}}</span> @else -- @endif
            </td>--}}
              <td>@if (isset($new_repair_payment_advance_advance['custom_pay_4'])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_payment_advance_advance['custom_pay_4']}}</span> @else -- @endif</td>
              
            @endif
            <td>@if(isset($new_supplier_due_payment["custom_pay_4"])) <span class="display_currency" data-currency_symbol="true">{{$new_supplier_due_payment["custom_pay_4"]}}</span> @else -- @endif</td>
        <td>@if(isset($new_customer_due_payment["custom_pay_4"])) <span class="display_currency" data-currency_symbol="true">{{$new_customer_due_payment["custom_pay_4"]}}</span> @else -- @endif</td>
            <td>
                @if(isset($new_expense_payments['custom_pay_4']))
          <span class="display_currency" data-currency_symbol="true">{{ $new_expense_payments['custom_pay_4'] }}</span>
          @else
          --
          @endif
        </td>
        </tr>
      @endif
      @if(array_key_exists('custom_pay_5', $payment_types))
        <tr>
          <td>
            {{$payment_types['custom_pay_5']}}:
          </td>
        <td>@if(isset($new_direct_sell_payments["custom_pay_5"])) <span class="display_currency" data-currency_symbol="true">{{$new_direct_sell_payments["custom_pay_5"]}}</span>@else -- @endif</td>
          <td>
            <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_5 }}</span>
          </td>
          @if($is_repair_module)
            {{--<td>
             @if(isset($new_repair_sell_payments["custom_pay_5"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_sell_payments["custom_pay_5"]}}</span> @else -- @endif
            </td>--}}
              <td>@if (isset($new_repair_payment_advance_advance['custom_pay_5'])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_payment_advance_advance['custom_pay_5']}}</span> @else -- @endif</td>
              
            @endif
             <td>@if(isset($new_supplier_due_payment["custom_pay_5"])) <span class="display_currency" data-currency_symbol="true">{{$new_supplier_due_payment["custom_pay_5"]}}</span> @else -- @endif</td>
            <td>@if(isset($new_customer_due_payment["custom_pay_5"])) <span class="display_currency" data-currency_symbol="true">{{$new_customer_due_payment["custom_pay_5"]}}</span> @else -- @endif</td>
            <td>
                @if(isset($new_expense_payments['custom_pay_5']))
          <span class="display_currency" data-currency_symbol="true">{{ $new_expense_payments['custom_pay_5'] }}</span>
          @else
          --
          @endif
        </td>
        </tr>
      @endif
      @if(array_key_exists('custom_pay_6', $payment_types))
        <tr>
          <td>
            {{$payment_types['custom_pay_6']}}:
          </td>
        <td>@if(isset($new_direct_sell_payments["custom_pay_6"])) <span class="display_currency" data-currency_symbol="true">{{$new_direct_sell_payments["custom_pay_6"]}}</span>@else -- @endif</td>
          <td>
            <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_6 }}</span>
          </td>
          @if($is_repair_module)
            {{--<td>
             @if(isset($new_repair_sell_payments["custom_pay_6"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_sell_payments["custom_pay_6"]}}</span> @else -- @endif
            </td>--}}
              <td>
                 @if (isset($new_repair_payment_advance_advance['custom_pay_6'])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_payment_advance_advance['custom_pay_6']}}</span> @else -- @endif
              </td>
            @endif
            <td>@if(isset($new_supplier_due_payment["custom_pay_6"])) <span class="display_currency" data-currency_symbol="true">{{$new_supplier_due_payment["custom_pay_6"]}}</span> @else -- @endif</td>
            <td>@if(isset($new_customer_due_payment["custom_pay_6"])) <span class="display_currency" data-currency_symbol="true">{{$new_customer_due_payment["custom_pay_6"]}}</span> @else -- @endif</td>
          <td>
                @if(isset($new_expense_payments['custom_pay_6']))
          <span class="display_currency" data-currency_symbol="true">{{ $new_expense_payments['custom_pay_6'] }}</span>
          @else
          --
          @endif
        </td>
        </tr>
      @endif
      @if(array_key_exists('custom_pay_7', $payment_types))
        <tr>
          <td>
            {{$payment_types['custom_pay_7']}}:
          </td>
           <td>@if(isset($new_direct_sell_payments["custom_pay_7"])) <span class="display_currency" data-currency_symbol="true">{{$new_direct_sell_payments["custom_pay_7"]}}</span>@else -- @endif</td>
          <td>
            <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_7 }}</span>
          </td>
          @if($is_repair_module)
                    {{--  <td>
             @if(isset($new_repair_sell_payments["custom_pay_7"])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_sell_payments["custom_pay_7"]}}</span> @else -- @endif
            </td>--}}
              <td>
                  @if (isset($new_repair_payment_advance_advance['custom_pay_7'])) <span class="display_currency" data-currency_symbol="true">{{$new_repair_payment_advance_advance['custom_pay_7']}}</span> @else -- @endif
              </td>
            @endif
             <td>@if(isset($new_supplier_due_payment["custom_pay_7"])) <span class="display_currency" data-currency_symbol="true">{{$new_supplier_due_payment["custom_pay_7"]}}</span> @else -- @endif</td>
            <td>@if(isset($new_customer_due_payment["custom_pay_7"])) <span class="display_currency" data-currency_symbol="true">{{$new_customer_due_payment["custom_pay_7"]}}</span> @else -- @endif</td>
        <td>
                @if(isset($new_expense_payments['custom_pay_7']))
          <span class="display_currency" data-currency_symbol="true">{{ $new_expense_payments['custom_pay_7'] }}</span>
          @else
          --
          @endif
        </td>
        </tr>
      @endif
      <tr>
        <td>
          @lang('cash_register.other_payments'):
        </td>
        <td>--</td>
        <td>
        
          <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_other }}</span>
        </td>
        @if($is_repair_module)
            {{--<td>--</td>--}}
            <td>--</td>
        @endif
        <td>--</td>
        <td>--</td>
        <td>
                @if(isset($new_expense_payments['other']))
          <span class="display_currency" data-currency_symbol="true">{{ $new_expense_payments['other'] }}</span>
          @else
          --
          @endif
        </td>
      </tr>
    </table>
    <hr>
    <table class="table table-condensed">
      <tr class="success">
        <th>
          @lang('cash_register.total_sales') Direct:
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{ $total_direct_sell[0]["total_amount"] }}</span></b>
        </td>
      </tr>
        <tr class="success">
        <th>
          Total Pos Sale
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{ $details['transaction_details']->total_sales }}</span></b>
        </td>
      </tr>
      <tr class="success">
        <th>
          @lang('lang_v1.credit_sales'):
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{$total_credit_sell}}</span></b>
        </td>
      </tr>
        <tr class="success">
        <th>
          @lang('lang_v1.total_payment')
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand + $register_details->total_cash - $register_details->total_cash_refund }}</span></b>
        </td>
      </tr>
        </tr>
        <tr class="success">
        <th>
          Total Direct Sell Payment
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{ $total_direct_sell_payments }}</span></b>
        </td>
      </tr>
        @if($is_repair_module)
       <tr class="success">
        <th>
          @lang('report.total_repair_advance'):
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_repair_advance }}</span></b>
        </td>
      </tr>
     @endif
    <tr class="success">
        <th>
            Total Customer Due:
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{ $total_customer_due_payment }}</span></b>
        </td>
      </tr>
      <tr class="danger">
        <th>
          @lang('cash_register.total_refund') (Change Return, Payment Method Edit) 
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_refund }}</span></b><br>
          <small>
          @if($register_details->total_cash_refund != 0)
            Cash: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_refund }}</span><br>
          @endif
          @if($register_details->total_cheque_refund != 0) 
            Cheque: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cheque_refund }}</span><br>
          @endif
          @if($register_details->total_card_refund != 0) 
            Card: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_card_refund }}</span><br> 
          @endif
          @if($register_details->total_bank_transfer_refund != 0)
            Bank Transfer: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_bank_transfer_refund }}</span><br>
          @endif
          @if(array_key_exists('custom_pay_1', $payment_types) && $register_details->total_custom_pay_1_refund != 0)
              {{$payment_types['custom_pay_1']}}: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_1_refund }}</span>
          @endif
          @if(array_key_exists('custom_pay_2', $payment_types) && $register_details->total_custom_pay_2_refund != 0)
              {{$payment_types['custom_pay_2']}}: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_2_refund }}</span>
          @endif
          @if(array_key_exists('custom_pay_3', $payment_types) && $register_details->total_custom_pay_3_refund != 0)
              {{$payment_types['custom_pay_3']}}: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_3_refund }}</span>
          @endif
          @if($register_details->total_other_refund != 0)
            Other: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_other_refund }}</span>
          @endif
          </small>
        </td>
      </tr>
       <tr class="danger">
        <th>
            Total Supplier Due:
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{ $total_supplier_due_payment }}</span></b>
        </td>
      </tr>
      <tr class="danger">
        <th>
          @lang('report.total_expense'):
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{ $total_expense_payments }}</span></b>
        </td>
      </tr>
    </table>
    <hr>
    <span>
        @lang('sale.total') = 
        @format_currency($register_details->cash_in_hand) (@lang('messages.opening')) + 
        @if($is_repair_module)
            @format_currency($register_details->total_repair_advance) (@lang('report.total_repair_advance')) +
        @endif
        @format_currency($total_customer_due_payment) (Customer Due) +
        @format_currency($total_direct_sell[0]["total_amount"]) (Total Sale Direct) +
        @format_currency($register_details->total_sale) (Total Pos Sale) - 
        @format_currency($register_details->total_refund) (@lang('lang_v1.refund')) - 
        @format_currency($total_supplier_due_payment) (Supplier Due) -
        @format_currency($total_expense_payments) (@lang('lang_v1.expense')) 
        @if($is_repair_module)
            = @format_currency($register_details->cash_in_hand + $register_details->total_sale + $total_direct_sell[0]["total_amount"] + $total_customer_due_payment + $register_details->total_repair_advance - $register_details->total_refund - $total_supplier_due_payment - $total_expense_payments)
        @else
            = @format_currency($register_details->cash_in_hand + $total_customer_due_payment + $total_direct_sell[0]["total_amount"] + $register_details->total_sale  - $register_details->total_refund - $total_supplier_due_payment - $total_expense_payments)
        @endif
    </span>
  </div>
</div>

@include('cash_register.register_product_details')
