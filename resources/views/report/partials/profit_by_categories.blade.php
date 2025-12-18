<div class="table-responsive">
    <table class="table table-bordered table-striped table-text-center" id="profit_by_categories_table">
        <thead>
            <tr>
                <th>@lang('product.category')</th>
                 <!--<th>Total Units Sold</th>-->
                 <th>@lang('lang_v1.gross_profit')</th>
                <th>Avg Gross Profit/Unit </th>
                    <th>Gross Profit % </th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 footer-total">
                <td><strong>@lang('sale.total'):</strong></td>
            <!--<td class="footer_total_unit"></td>-->
                <td></td>
              
                <td class="footer_total"></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <p class="text-muted">
        @lang('lang_v1.profit_note')
    </p>
</div>