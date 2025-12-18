<div class="payment_row">
    @include('sale_pos.partials.payment_row_form', [
        'row_index' => 0,
        'payment_line' => $payment_line ?? [],
        'show_date' => false,
        'show_denomination' => false,
        'accounts' => $accounts ?? []
    ])
</div>