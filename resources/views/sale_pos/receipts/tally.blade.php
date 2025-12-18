<style>
    * {
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        

    }

    #invoice {
        text-transform: uppercase;
    }


    .page {
        width: 21cm;
        min-height: 29.7cm;
        padding: 1.5cm;
        margin: 1cm auto;
        background: white;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    }

    .subpage {
        height: 256mm;
        outline: 2cm #fff solid;
    }

    @page {
        size: A4;
    }

    @media print {
        *{
            text-transform: uppercase;
        }
        table td {
            border: 1px solid #000;
        }
        .page {
            border: initial;
            border-radius: initial;
            width: initial;
            min-height: initial;
            box-shadow: initial;
            background: initial;
        }

        .subpage {}

        tr.page-break {
            display: block;
            page-break-before: always;
        }
    }
</style>


<?php
$totals = ['taxable_value' => 0];

$TOTAL_DISCOUNT = 0;
$TOTAL_TAXABLE = 0;
$TOTAL_GST = 0;
$TOTAL_ORDER = 0;
$TOTAL_MRP = 0;

function convertToIndianCurrency($number)
{
    $no = round($number);
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $digits_length = strlen($no);
    $i = 0;
    $str = [];
    $words = [
        0 => '',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine',
        10 => 'Ten',
        11 => 'Eleven',
        12 => 'Twelve',
        13 => 'Thirteen',
        14 => 'Fourteen',
        15 => 'Fifteen',
        16 => 'Sixteen',
        17 => 'Seventeen',
        18 => 'Eighteen',
        19 => 'Nineteen',
        20 => 'Twenty',
        30 => 'Thirty',
        40 => 'Forty',
        50 => 'Fifty',
        60 => 'Sixty',
        70 => 'Seventy',
        80 => 'Eighty',
        90 => 'Ninety',
    ];
    $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];
    while ($i < $digits_length) {
        $divider = $i == 2 ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = ($counter = count($str)) && $number > 9 ? 's' : null;
            $str[] = $number < 21 ? $words[$number] . ' ' . $digits[$counter] . $plural : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural;
        } else {
            $str[] = null;
        }
    }
    $Rupees = implode(' ', array_reverse($str));
    $paise = $decimal ? ' And ' . $words[$decimal - ($decimal % 10)] . ' ' . $words[$decimal % 10] . ' Paise' : '';
    return ($Rupees ? $Rupees . ' Rupees' : '') . $paise . ' Only';
} ?>

<div id="invoice">
    @if (!empty($receipt_details->invoice_heading))
        {!! $receipt_details->invoice_heading !!}
    @endif
    <table style="width:98%; font-size: xx-small">
        <tr>
            <td style="width: 50%;">
                <table style=" width:100%">
                    <tr>
                        @if (!empty($receipt_details->logo))
                            <td style="text-align: center; padding:3px;">
                                <img src="{{ $receipt_details->logo }}" style="width:80px;"><br>
                            </td>
                        @endif
                        <td style="text-align: left; padding:5px; border:none">
                            <h3>
                                {{ $receipt_details->display_name }}</h3>
                            @if (!empty($receipt_details->business_address))
                                {!! $receipt_details->business_address !!}
                                
                                @if (!empty($receipt_details->business_city))
                                    {!! $receipt_details->business_city !!}
                                @endif
                                @if (!empty($receipt_details->business_state))
                                    , {!! $receipt_details->business_state !!}
                                @endif
                                @if (!empty($receipt_details->business_zip_code))
                                    , {!! $receipt_details->business_zip_code !!}
                                @endif
                                @if (!empty($receipt_details->business_country))
                                    , {!! $receipt_details->business_country !!}
                                @endif
                            @endif
                            <br>
                            @if (!empty($receipt_details->business_contact))
                                {{ $receipt_details->business_contact }}
                                @if (!empty($receipt_details->business_alternate_contact))
                                    , {{ $receipt_details->business_alternate_contact }}
                                @endif
                            @endif
                            @if (!empty($receipt_details->business_email))
                                , Email Us : {{ $receipt_details->business_email }} <br>
                            @endif

                            @if (!empty($receipt_details->tax_info1))
                                {{ $receipt_details->tax_label1 }} <b>{{ $receipt_details->tax_info1 }}</b><br>
                            @endif
                            @if (!empty($receipt_details->tax_info2))
                                {{ $receipt_details->tax_label2 }} <b>{{ $receipt_details->tax_info2 }}</b><br>
                            @endif
                            @if (!empty($receipt_details->website))
                                <span style="text-transform: lowercase;">{{ $receipt_details->website }}</span><br>
                            @endif
                            @if (!empty($receipt_details->location_custom_field_1_label))
                                {{ $receipt_details->location_custom_field_1_label }} :
                                <strong>{{ $receipt_details->location_custom_field_1_value }}</strong><br>
                            @endif
                            @if (!empty($receipt_details->location_custom_field_2_label))
                                {{ $receipt_details->location_custom_field_2_label }} :
                                <strong>{{ $receipt_details->location_custom_field_2_value }}</strong><br>
                            @endif
                            @if (!empty($receipt_details->location_custom_field_3_label))
                                {{ $receipt_details->location_custom_field_3_label }} :
                                <strong>{{ $receipt_details->location_custom_field_3_value }}</strong><br>
                            @endif
                            @if (!empty($receipt_details->location_custom_field_4_label))
                                {{ $receipt_details->location_custom_field_4_label }} :
                                <strong>{{ $receipt_details->location_custom_field_4_value }}</strong><br>
                            @endif
                        </td>
                        
                    </tr>
                </table>                
            </td>
            <td style="width: 50%; vertical-align:top">
                <table style="width:100%">
                    <tr>
                        <td style="vertical-align:top">
                            <div style="padding: 2px">
                                # {{ $receipt_details->invoice_no_prefix }}<br>
                                <strong>{{ $receipt_details->invoice_no }}</strong>
                                @if (!empty($receipt_details->sell_custom_field_1_label))
                                    <hr style="margin: 0px">
                                    {{ $receipt_details->sell_custom_field_1_label }}<br>
                                    @if (!empty($receipt_details->sell_custom_field_1_value))
                                        <strong>{{ $receipt_details->sell_custom_field_1_value }}</strong>
                                    @else
                                        -
                                    @endif
                                @endif
                                @if (!empty($receipt_details->sell_custom_field_3_label))
                                    <hr style="margin: 0px">
                                    {{ $receipt_details->sell_custom_field_3_label }}<br>
                                    @if (!empty($receipt_details->sell_custom_field_3_value))
                                        <strong>{{ $receipt_details->sell_custom_field_3_value }}</strong>
                                    @else
                                        -
                                    @endif
                                @endif
                                @if (!empty($receipt_details->shipping_charges_uf))
                                    <hr style="margin: 0px">
                                    {{ $receipt_details->shipping_charges_label }}<br>
                                    <strong>₹
                                        {{ round($receipt_details->shipping_charges_uf, 2) }}</strong>
                                @endif
                                @if (!@empty($receipt_detials->sales_person_label))
                                    <hr style="margin: 0px">
                                    {{ $receipt_detials->sales_person_label }} :
                                    <strong> {{ $receipt_details->sales_person }}</strong>
                                @endif
                            </div>
                        </td>
                        <td style="vertical-align:top">
                            <div style="padding: 2px">
                                Invoice Date<br>
                                <strong>{{ $receipt_details->invoice_date }}</strong>
                                @if (!empty($receipt_details->sell_custom_field_2_label))
                                    <hr style="margin: 0px">
                                    {{ $receipt_details->sell_custom_field_2_label }}<br>

                                    @if (!empty($receipt_details->sell_custom_field_2_value))
                                        <strong>{{ $receipt_details->sell_custom_field_2_value }}</strong>
                                    @else
                                        -
                                    @endif
                                @endif
                                @if (!empty($receipt_details->sell_custom_field_4_label))
                                    <hr style="margin: 0px">
                                    {{ $receipt_details->sell_custom_field_4_label }}<br>

                                    @if (!empty($receipt_details->sell_custom_field_4_value))
                                        <strong>{{ $receipt_details->sell_custom_field_4_value }}</strong>
                                    @else
                                        -
                                    @endif
                                @endif
                                @if (!empty($receipt_details->shipping_details_label))
                                    <hr style="margin: 0px">
                                    {{ $receipt_details->shipping_details_label }}<br>
                                    @if (!empty($receipt_details->shipping_details))
                                        <strong> {{ $receipt_details->shipping_details }}</strong>
                                    @else
                                        -
                                    @endif
                                @endif

                                @if (!empty($receipt_details->packing_charge_unfromatted))
                                    <hr style="margin: 0px">
                                    {{ $receipt_details->packing_charge_label }}<br>
                                    <strong>₹
                                        {{ round($receipt_details->packing_charge_unfromatted, 2) }}</strong>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>

                @if (!empty($receipt_details->shipping_address))
                    <div style="border:1px solid; padding: 3px">
                        <strong>Shipping Address</strong> <br> {{ $receipt_details->shipping_address }}
                    </div>
                @endif
                @if (!empty($receipt_details->additional_notes))
                    <div style="border:1px solid; padding: 3px">
                        <strong>Notes</strong> : {{ $receipt_details->additional_notes }}
                    </div>
                @endif
            </td>
        </tr>
        <tr>
            <td style="border:1px solid">
                <div style="padding: 3px">
                    @if (!empty($receipt_details->customer_label))
                        <strong>{{ $receipt_details->customer_label }}</strong>
                    @else
                        BUYER
                    @endif
                    <br>
                    @if (!empty($receipt_details->customer_business_name))
                        <strong>{!! $receipt_details->customer_business_name !!}</strong><br>
                    @endif
                    @if (!empty($receipt_details->customer_name))
                        <strong>{!! $receipt_details->customer_name !!}</strong><br>
                    @endif
                    @if ($receipt_details->customer_name != 'Walk-In Customer')
                        @if (!empty($receipt_details->customer_address))
                            Address : {!! $receipt_details->customer_address !!}<br>
                        @endif
                        @if (!empty($receipt_details->customer_state))
                            STATE : {{ $receipt_details->customer_state }}<br>
                        @endif
                        @if (!empty($receipt_details->customer_tax_label))
                            {{ $receipt_details->customer_tax_label }} : <strong>
                                {{ $receipt_details->customer_tax_number }}</strong><br>
                        @endif
                        @if (!empty($receipt_details->customer_mobile))
                            Mobile : {{ $receipt_details->customer_mobile }}<br>
                        @endif
                        @if (!empty($receipt_details->customer_landline))
                            Landline : {{ $receipt_details->customer_landline }}<br>
                        @endif
                        @if (!empty($receipt_details->customer_custom_fields))
                            <strong>{{ $receipt_details->customer_custom_fields }}</strong><br>
                        @endif
                    @endif
                </div> 
            </td>
            <td></td>
        </tr>
    </table>
    
</div>
</td>

</tr>
</table>


<table style="width:98%;">
    <tr>
        <td style="vertical-align: top; border:1px solid">
            @foreach ($receipt_details->lines as $line)
                @if (!empty($line['mrp_uf']))
                    @php $TOTAL_MRP += $line['mrp_uf'] * $line['quantity_uf']; @endphp
                @endif
                @if (!empty($line['tax_uf']))
                    @php $TOTAL_GST += $line['tax_uf'] * $line['quantity_uf']; @endphp
                @endif
            @endforeach

            <div style="">
                <table style="width: 100%; font-size:8pt;">
                    <thead>
                        <tr style="text-align: center; font-weight: bold;">
                            <th style="padding:3px; text-align:left">Sr</th>
                            <th style="padding:3px; text-align:left">Products</th>
                            @if ($receipt_details->show_cat_code == 1)
                                <th style="padding:3px; text-align:center">HSN/SAC</th>
                            @endif
                            <th style="padding:3px; text-align:center">QTY</th>
                            @if ($TOTAL_MRP > 0)
                                <th style="padding:3px; text-align:center">MRP</th>
                            @endif
                            <th style="padding:3px; text-align:center">Rate</th>
                            @if ($receipt_details->display_name != 'SUNITA ENTERPRISES')
                                <th style="padding:3px; text-align:center">Disc</th>
                            @endif
                            @if ($TOTAL_GST > 0)
                                <th style="padding:3px; text-align:center">Taxable</th>
                                <th style="padding:3px; text-align:center">GST</th>
                            @endif
                            <th style="padding:3px; text-align:center">Total</th>
                        </tr>
                    </thead>


                    @foreach ($receipt_details->lines as $line)
                        <tr style="vertical-align:top; border-top:none;border-bottom:none;">
                            <td style="padding:3px; text-align:left">
                                {{ $loop->iteration }}
                            </td>
                            <td style="padding:3px; text-align:left">
                                @if (!empty($line['brand']))
                                    <b><small>{{ $line['brand'] }}</small></b>
                                @endif
                                {{ $line['name'] }} {{ $line['variation'] }}
                                @if (!empty($line['sell_line_note']))
                                    ({{ $line['sell_line_note'] }})
                                @endif
                                @if (!empty($line['lot_number']))
                                    <br>
                                    <small>{{ $line['lot_number_label'] }} :
                                        {{ $line['lot_number'] }}</small>
                                @endif
                                @if (!empty($line['product_expiry']))
                                    <br><small>{{ $line['product_expiry_label'] }}:
                                        {{ $line['product_expiry'] }}
                                    </small>
                                @endif
                            </td>
                            @if ($receipt_details->show_cat_code == 1)
                                <td style="padding:3px; text-align:center">

                                    @if (!empty($line['hsn']))
                                        {{ $line['hsn'] }}
                                    @elseif(!empty($line['cat_code']))
                                        {{ $line['cat_code'] }}
                                    @endif
                                </td>
                            @endif
                            <td style="padding:3px; text-align:center">
                                {{ $line['quantity'] }} <span style="font-size: 5pt">{{ $line['units'] }}</span>
                            </td>

                            @if ($TOTAL_MRP > 0)
                                <td style="padding:3px; text-align:right">
                                    @if (!empty($line['mrp']))
                                        <span class='display_currency' data-currency_symbol='false'>
                                            {{ $line['mrp'] }}
                                        </span>
                                    @endif
                                </td>
                            @endif

                            <td style="padding:3px; text-align:right">
                                <span class='display_currency' data-currency_symbol='false'>
                                    {{ $line['unit_price_before_discount'] }}

                                </span>
                            </td>
                            @if ($receipt_details->display_name != 'SUNITA ENTERPRISES')
                                <td style="padding:3px; text-align:right">
                                    <span class='display_currency' data-currency_symbol='false'>
                                        {{ $line['line_discount_uf'] * $line['quantity_uf'] }}
                                    </span>
                                    @if (!empty($line['line_discount_percent']))
                                        {{ $line['line_discount_percent'] }}
                                    @endif

                                    @php
                                        $TOTAL_DISCOUNT += $line['line_discount_uf'] * $line['quantity_uf'];
                                    @endphp
                                </td>
                            @endif

                            @if ($TOTAL_GST > 0)
                                <td style="padding:3px; text-align:right">
                                    {{ ($line['unit_price_before_discount_uf'] - $line['line_discount_uf']) * $line['quantity_uf'] }}

                                    @php
                                        $TOTAL_TAXABLE +=
                                            ($line['unit_price_before_discount_uf'] - $line['line_discount_uf']) *
                                            $line['quantity_uf'];
                                    @endphp
                                </td>
                                <td style="padding:3px; text-align:right; width:10%">
                                    @if (!empty($line['tax']))
                                        {{ round($line['tax_uf'] * $line['quantity_uf'], 2) }}
                                    @endif
                                    @if (!empty($line['tax_name']))
                                        <br><span style="font-size: 6pt">({{ $line['tax_name'] }})</span>
                                    @endif
                                </td>
                            @endif
                            <td style="padding:3px; text-align:right">
                                <span class='display_currency' data-currency_symbol='false'>
                                    {{ $line['line_total_uf'] }}
                                </span>
                                @php
                                    $TOTAL_ORDER += $line['line_total_uf'];
                                @endphp
                            </td>

                        </tr>
                    @endforeach
                    <?php
                    if (count($receipt_details->lines) < 11) {
                        $height = 450 - count($receipt_details->lines) * 50;
                        echo "<tr style='height:" . $height . "px'>";
                        echo "<td style='padding:3px; text-align:left'></td>";
                        echo "<td style='padding:3px; text-align:left'></td>";
                        if ($receipt_details->show_cat_code == 1) {
                            echo "<td style='padding:3px; text-align:center'></td>";
                        }
                        echo "<td style='padding:3px; text-align:center'></td>";
                        if ($TOTAL_MRP > 0) {
                            echo "<td style='padding:3px; text-align:center'></td>";
                        }
                        echo "<td style='padding:3px; text-align:center'></td>";
                        if ($receipt_details->display_name != 'SUNITA ENTERPRISES') {
                            echo "<td style='padding:3px; text-align:center'></td>";
                        }
                        if ($TOTAL_GST > 0) {
                            echo "<td style='padding:3px; text-align:center'></td>";
                            echo "<td style='padding:3px; text-align:center'></td>";
                        }
                        echo "<td style='padding:3px; text-align:center'></td>";
                        echo '</tr>';
                    }
                    ?>

                    <tr style="height: 0.3in; text-align: center; font-weight: bold;">
                        @if ($receipt_details->show_cat_code == 1)
                            @if ($TOTAL_MRP > 0)
                                <td colspan="6" style="text-align: right; padding-right: 10px;">
                                    Total</td>
                            @else
                                <td colspan="5" style="text-align: right; padding-right: 10px;">
                                    Total</td>
                            @endif
                        @else
                            @if ($TOTAL_MRP > 0)
                                <td colspan="5" style="text-align: right; padding-right: 10px;">
                                    Total</td>
                            @else
                                <td colspan="4" style="text-align: right; padding-right: 10px;">
                                    Total</td>
                            @endif
                        @endif
                        @if ($receipt_details->display_name != 'SUNITA ENTERPRISES')
                            <td>
                                @php echo $TOTAL_DISCOUNT; @endphp
                            </td>
                        @endif
                        @if ($TOTAL_GST > 0)
                            <td>
                                @php echo $TOTAL_TAXABLE; @endphp
                            </td>

                            <td>
                                @php echo $TOTAL_GST; @endphp
                            </td>
                        @endif
                        <td>
                            @php echo $TOTAL_ORDER; @endphp
                        </td>
                    </tr>
                </table>
        </td>
    </tr>
    <tfoot>
        <tr>
            <td>
                @if (!empty($receipt_details->footer_text))
                    <div style="font-size:5pt; line-height:6pt;">
                        {!! $receipt_details->footer_text !!}
                    </div>
                @endif
                <center style="font-size: 7pt">Thank you for your business with us! Visit us again.</center>
            </td>
        </tr>
    </tfoot>
    <tr>
        <td>
            <table style="width:100%;font-size:8pt;" border=1>
                <tr>
                    <td style="width:33%; vertical-align:top; padding:4px">
                        @if (!empty($receipt_details->payments))
                            <strong>Payment Information</strong>
                            <div>
                                @foreach ($receipt_details->payments as $payment)
                                    <b>{{ $payment['amount'] }}</b> ({{ $payment['method'] }}) on
                                    <b>{{ $payment['date'] }}</b><br>
                                @endforeach
                            </div>
                        @endif
                        @if (!empty($receipt_details->taxes))
                            <hr style='margin:0px'>
                            <b>TAX BREAKUP</b>
                            <br>
                            @foreach ($receipt_details->taxes as $key => $value)
                                {{ $key }} : {{ $value }} <br>
                            @endforeach
                        @endif
                        <hr style="margin:0px">
                        <div style="font-size:6pt; text-transform:capitalize">
                            <span><strong>Declaration</strong></span><br>
                            We declare that this invoice shows the actual price of the goods described and that all
                            particular are true and correct.
                        </div>
                    </td>
                    <td style="width:33%; vertical-align:bottom; text-align:center">                        
                            @if ($receipt_details->total_due == '0' || $receipt_details->total_due == '')
                                <center><img src="{{ asset('img/paid_stamp_200.png') }}" style="width:150px" /></center><br>
                            @endif                       
                        ____________________<br>
                        <b>Authorised Signatory</b><br>
                        @if (!empty($receipt_details->display_name))
                            <span
                                style="margin:0px; font-weight:bold; text-transform: uppercase;">{{ $receipt_details->display_name }}</span><br>
                        @endif

                        <center>
                            @if ($receipt_details->show_barcode)
                                <?php echo DNS1D::getBarcodeSVG($receipt_details->invoice_no, 'C128', 1.5, 30); ?>
                            @endif
                            <br>
                            <span style="font-size:5pt">This invoice is system (computer)
                                generated</span>
                        </center>

                    </td>
                    <td style="width:33%; text-align:right; vertical-align:top; padding:3px;">

                        @if ($TOTAL_GST > 0)
                            Subtotal Exc Tax :
                            <b>
                                <span class='display_currency' data-currency_symbol='true'>
                                    @php echo $TOTAL_TAXABLE; @endphp
                                </span>
                            </b>
                            <br>

                            Total GST :
                            (+)
                            <span class='display_currency' data-currency_symbol='true'>
                                {{ $TOTAL_GST }}
                            </span>
                            <br>
                            <small>
                                Total CGST :
                                <span class='display_currency' data-currency_symbol='true'>
                                    {{ $TOTAL_GST / 2 }}
                                </span>
                            </small>
                            <br>
                            <small>
                                Total SGST :
                                <span class='display_currency' data-currency_symbol='true'>
                                    {{ $TOTAL_GST / 2 }}
                                </span>
                                <br>
                            </small>
                        @endif

                        <b> {!! $receipt_details->subtotal_label !!} {{ $receipt_details->subtotal }}</b><br>

                        @if (!empty($receipt_details->discount))
                            Order Discount : (-) {{ $receipt_details->discount }}<br>
                        @endif
                        @if (!empty($receipt_details->group_tax_details))
                            @foreach ($receipt_details->group_tax_details as $key => $value)
                                {!! $key !!} : (+) {{ $value }} <br>
                            @endforeach
                        @else
                            @if (!empty($receipt_details->tax))
                                {!! $receipt_details->tax_label !!} : (+) {{ $receipt_details->tax }}<br>
                            @endif
                        @endif

                        @if (!empty($receipt_details->shipping_charges))
                            {!! $receipt_details->shipping_charges_label !!} : (+)
                            {{ $receipt_details->shipping_charges }}<br>
                        @endif
                        <!-- Round Off -->
                        @if (!empty($receipt_details->round_off_label))
                            {!! $receipt_details->round_off_label !!}
                            <span class='display_currency'
                                data-currency_symbol='true'>{{ $receipt_details->round_off }}</span>
                        @endif
                        <hr style="margin:0px" />
                        <b>{!! $receipt_details->total_label !!} {{ $receipt_details->total }}</b>

                        @if ($TOTAL_MRP > $TOTAL_ORDER)
                            <hr style="margin:0px" />
                            Hurray! You saved <b><span class='display_currency'
                                    data-currency_symbol='true'>{{ $TOTAL_MRP - $TOTAL_ORDER }}</span></b>
                            on MRP
                        @endif
                        <hr style="margin:0px" />

                        @if (!empty($receipt_details->total_paid))
                            {!! $receipt_details->total_paid_label !!} : <b>{{ $receipt_details->total_paid }}</b><br>
                            {!! $receipt_details->total_due_label !!} : <b>{{ $receipt_details->total_due }}</b><br>
                        @endif
                        @if (!empty($receipt_details->all_due))
                            <hr style="margin:0px">{!! $receipt_details->all_bal_label !!} :
                            <b>{{ $receipt_details->all_due }}</b>
                        @endif

                    </td>
                </tr>

            </table>
            <div style="border:1px solid; text-align: right">
                <hr style="margin:0px" />
                Amount Payable
                <span style="font-weight: 900">
                    {{ convertToIndianCurrency($receipt_details->total_uf) }}
                </span>

            </div>
        </td>
    </tr>

</table>
</div>
