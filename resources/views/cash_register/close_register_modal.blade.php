@php
    $closed_at =  $register_details->closed_at == null ? \Carbon\Carbon::now()->format('Y-m-d\ h:i:s') : $register_details->closed_at;

$is_same_date = explode("-", explode(" ", $closed_at)[0])[2] == explode("-", explode(" ", $register_details->open_time)[0])[2];
if($is_same_date) {
$repair_payments = \App\Transaction::where("transactions.sub_type", "repair")
    ->where("transactions.business_id", auth()->user()->business_id)
    //->where("transactions.payment_status", "paid")
->whereRaw(" DATE(transactions.created_at) = '" . explode(' ', $register_details->open_time)[0] ."'
  AND TIME(transactions.created_at) BETWEEN '" . explode(' ', $register_details->open_time)[1] ."' AND '". explode(' ', $closed_at)[1] ."'")
    ->join("transaction_payments as tp", "transactions.id", "=", "tp.transaction_id")
    ->get();
    

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
$repair_payments = \App\Transaction::where("transactions.sub_type", "repair")
    ->where("transactions.business_id", auth()->user()->business_id)
    //->where("transactions.payment_status", "paid")
    ->whereBetween('transactions.created_at', [$register_details->open_time, $closed_at])
    ->join("transaction_payments as tp", "transactions.id", "=", "tp.transaction_id")
    ->where("tp.is_advance", 1)
    ->get();
    //dd($repair_payments, $register_details->open_time, $closed_at);
    $expanse_payments = \App\Transaction::where('transactions.created_by', $register_details->user_id)
                ->whereBetween('transactions.created_at', [$register_details->open_time, $closed_at])
                ->where('transactions.type', 'expense')
                ->join('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->select(
                    'tp.amount as amount',
                    'tp.method as method',
                )->get();
                
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

$new_repair_payment = [];

foreach($repair_payments as $repair_payment){
        if(!isset($new_repair_payment[$repair_payment["method"]])){
            $new_repair_payment[ $repair_payment["method"]] = (float) $repair_payment["amount"];
        } else {
            $new_repair_payment[ $repair_payment["method"]] += (float) $repair_payment["amount"];
        }
}
//dd($repair_payments, $new_repair_payment);
$new_expense_payments = [];

$new_expense_payments['cash'] = (float) 0;
$total_expense_payments = 0;
foreach($expanse_payments as $expense_payment){
    if(!isset($new_expense_payments[$expense_payment["method"]])){
        $new_expense_payments[$expense_payment["method"]] = (float) $expense_payment["amount"];
    } else {
        $new_expense_payments[$expense_payment["method"]] += (float) $expense_payment["amount"];
    }
    $total_expense_payments += (float) $expense_payment["amount"];
}

$total_direct_sell_payments = 0;
$new_direct_sell_payments = [];
$new_direct_sell_payments['cash'] = (float) 0;
foreach($direct_sell_payments as $single_direct_sell_payment){
    if(!isset($new_direct_sell_payments[$single_direct_sell_payment["method"]])) {
        $new_direct_sell_payments[$single_direct_sell_payment["method"]] = (float) $single_direct_sell_payment["amount"];
    } else {
        $new_direct_sell_payments[$single_direct_sell_payment["method"]] += (float) $single_direct_sell_payment["amount"];
    }
    $total_direct_sell_payments += (float) $single_direct_sell_payment["amount"];
}

$total_supplier_due_payment = 0;
$new_supplier_due_payment = [];
$new_supplier_due_payment['cash'] = (float) 0;
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
$new_customer_due_payment['cash'] = (float) 0;
foreach($customer_due_payment as $single_supplier_due_payment) {
    if(!isset($new_customer_due_payment[$single_supplier_due_payment["method"]])){
        $new_customer_due_payment[$single_supplier_due_payment["method"]] = (float) $single_supplier_due_payment["amount"];
    }else {
        $new_customer_due_payment[$single_supplier_due_payment["method"]] +=  (float) $single_supplier_due_payment["amount"];
    }
    $total_customer_due_payment += (float) $single_supplier_due_payment["amount"];
}
@endphp
<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action([\App\Http\Controllers\CashRegisterController::class, 'postCloseRegister']), 'method' => 'post' ]) !!}

    {!! Form::hidden('user_id', $register_details->user_id); !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h3 class="modal-title">@lang( 'cash_register.current_register' ) ( {{ \Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->format('jS M, Y h:i A') }} - {{ \Carbon::now()->format('jS M, Y h:i A') }})</h3>
    </div>

    <div class="modal-body">
        @include('cash_register.payment_details')
        <hr>
      <div class="row">
        <div class="col-sm-4">
          <div class="form-group">
            @php
                //dd($new_direct_sell_payments['cash'], $register_details->cash_in_hand, $new_repair_payment['cash'], $register_details->total_cash, $new_customer_due_payment['cash'], $register_details->total_cash_refund, $new_supplier_due_payment['cash'], $new_expense_payments['cash']);
            @endphp
            {!! Form::label('closing_amount', __( 'cash_register.total_cash' ) . ':*') !!}
            @if(isset($new_repair_payment['cash']))
               {!! Form::text('closing_amount', @num_format($new_direct_sell_payments['cash']  + $register_details->cash_in_hand + $new_repair_payment['cash'] + $register_details->total_cash + $new_customer_due_payment['cash']  - $register_details->total_cash_refund - $new_supplier_due_payment['cash'] - $new_expense_payments['cash']), ['class' => 'form-control input_number', 'required', 'placeholder' => __( 'cash_register.total_cash' ) ]); !!}
            @else
              {!! Form::text('closing_amount', @num_format($new_direct_sell_payments['cash']  + $register_details->cash_in_hand + $new_customer_due_payment['cash']  + $register_details->total_cash - $new_supplier_due_payment['cash'] -  $register_details->total_cash_refund - $new_expense_payments['cash']), ['class' => 'form-control input_number', 'required', 'placeholder' => __( 'cash_register.total_cash' ) ]); !!}
              @endif
          </div>
        </div>
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('total_card_slips', __( 'cash_register.total_card_slips' ) . ':*') !!} @show_tooltip(__('tooltip.total_card_slips'))
              {!! Form::number('total_card_slips', $register_details->total_card_slips, ['class' => 'form-control', 'required', 'placeholder' => __( 'cash_register.total_card_slips' ), 'min' => 0 ]); !!}
          </div>
        </div> 
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('total_cheques', __( 'cash_register.total_cheques' ) . ':*') !!} @show_tooltip(__('tooltip.total_cheques'))
              {!! Form::number('total_cheques', $register_details->total_cheques, ['class' => 'form-control', 'required', 'placeholder' => __( 'cash_register.total_cheques' ), 'min' => 0 ]); !!}
          </div>
        </div> 
        <hr>
        <div class="col-md-8 col-sm-12">
          <h3>@lang( 'lang_v1.cash_denominations' )</h3>
          @if(!empty($pos_settings['cash_denominations']))
            <table class="table table-slim">
              <thead>
                <tr>
                  <th width="20%" class="text-right">@lang('lang_v1.denomination')</th>
                  <th width="20%">&nbsp;</th>
                  <th width="20%" class="text-center">@lang('lang_v1.count')</th>
                  <th width="20%">&nbsp;</th>
                  <th width="20%" class="text-left">@lang('sale.subtotal')</th>
                </tr>
              </thead>
              <tbody>
                @foreach(explode(',', $pos_settings['cash_denominations']) as $dnm)
                <tr>
                  <td class="text-right">{{$dnm}}</td>
                  <td class="text-center" >X</td>
                  <td>{!! Form::number("denominations[$dnm]", null, ['class' => 'form-control cash_denomination input-sm', 'min' => 0, 'data-denomination' => $dnm, 'style' => 'width: 100px; margin:auto;' ]); !!}</td>
                  <td class="text-center">=</td>
                  <td class="text-left">
                    <span class="denomination_subtotal">0</span>
                  </td>
                </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="4" class="text-center">@lang('sale.total')</th>
                  <td><span class="denomination_total">0</span></td>
                </tr>
              </tfoot>
            </table>
          @else
            <p class="help-block">@lang('lang_v1.denomination_add_help_text')</p>
          @endif
        </div>
        <hr>
        <div class="col-sm-12">
          <div class="form-group">
            {!! Form::label('closing_note', __( 'cash_register.closing_note' ) . ':') !!}
              {!! Form::textarea('closing_note', null, ['class' => 'form-control', 'placeholder' => __( 'cash_register.closing_note' ), 'rows' => 3 ]); !!}
          </div>
        </div>
      </div> 

      <div class="row">
        <div class="col-xs-6">
          <b>@lang('report.user'):</b> {{ $register_details->user_name}}<br>
          <b>@lang('business.email'):</b> {{ $register_details->email}}<br>
          <b>@lang('business.business_location'):</b> {{ $register_details->location_name}}<br>
        </div>
        @if(!empty($register_details->closing_note))
          <div class="col-xs-6">
            <strong>@lang('cash_register.closing_note'):</strong><br>
            {{$register_details->closing_note}}
          </div>
        @endif
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.cancel' )</button>
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'cash_register.close_register' )</button>
    </div>
    {!! Form::close() !!}
  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->