@php
$totalmrp = 0;
$totalsaleprice = 0;
@endphp

<div
    style="width: 68 mm; page-break:avoid; font-family:'verdana'; font-size:8pt; line-height:16px; margin:0px; padding:5px; z-index:'9999'">
    <!-- Logo-->
    @if (!empty($receipt_details->logo))
        <center><img src="{{ $receipt_details->logo }}" style="width:80%;"></center>

    @endif
    <div style="text-align: center; line-height:13px">
        @if (!empty($receipt_details->display_name))
            <span style="margin: 0px; font-weight: bold; font-size:small">
                {{ $receipt_details->display_name }} <br>
            </span>
            @if (!empty($receipt_details->location_name))
                <b> {{ $receipt_details->location_name }} </b><br><br>
            @endif
            @if (!empty($receipt_details->address))
                {!! $receipt_details->address !!}
                <br />
            @endif

            @if (!empty($receipt_details->contact))
                {{ $receipt_details->contact }}
                <br />
            @endif

            @if (!empty($receipt_details->website))
                Website : {{ $receipt_details->website }}
                <br />
            @endif

            @if (!empty($receipt_details->location_custom_fields))
                Location : {{ $receipt_details->location_custom_fields }}
            @endif

        @endif
        <hr style="margin:0px">
        @if (!empty($receipt_details->tax_info1))
            {{ $receipt_details->tax_label1 }} : <strong>{{ $receipt_details->tax_info1 }}</strong>
            <br />
        @endif

        @if (!empty($receipt_details->tax_info2))
            {{ $receipt_details->tax_label2 }} : <strong>{{ $receipt_details->tax_info2 }} </strong>
        @endif
        <hr style="margin:0px">
        <table style="width:100%">
            <tr>
                <td style="width:50%; text-align:center">
                    @if (!empty($receipt_details->invoice_no_prefix))
                        {!! $receipt_details->invoice_no_prefix !!}@endif
                    <strong>{{ $receipt_details->invoice_no }}</strong>
                    <br>
                    @if (!empty($receipt_details->date_label))
                        Date : <strong>{{ $receipt_details->invoice_date }}</strong> @endif
                </td>
            </tr>
        </table>
    </div>
    <hr style="margin:0px">
    <div style="text-align: left">
        <center>
            @if (!empty($receipt_details->customer_name))
               @if($receipt_details->display_name == "BRIX NEURO") Patient : @endif {{ $receipt_details->customer_name }}
                @if (!empty($receipt_details->customer_tax_number))
                    <small>(GSTIN : {{ $receipt_details->customer_tax_number }})</small><br>
                @endif
            @endif
			@if($receipt_details->display_name != "BRIX NEURO")
              @if (!empty($receipt_details->customer_info))
                  <small>{!! $receipt_details->customer_info !!}</small>
              @endif
          	@endif

        </center>
    </div>
    <hr style="margin:0px">
    <table style="width: 100%; text-align:left; font-size:9pt; line-height:13pt; border-collapse:collapse" border="0">
        <tr style="text-align: left; font-weight: bold; border-bottom:1px solid">
            <td style="width:50%">Items</td>
            <td style="text-align:right">Total</td>
        </tr>
        @foreach ($receipt_details->lines as $line)
      		@if($receipt_details->display_name == "BRIX NEURO")
      			<tr style="margin:0px">
      				<td colspan="2" style="margin:0px">
                      <b> {{ $line['name'] }} {{ $line['variation'] }}</b>
                   @if (!empty($line['alias']))
                        <br>{{ $line['alias'] }}
                   @endif
                    <small>
                        @if (!empty($line['cat_code']))
                            <br>HSN : {{ $line['cat_code'] }}
                        @elseif(!empty($line['hsn']))
                            <br>HSN : {{ $line['hsn'] }}
                        @endif
                    </small>                  	
                  	</td>
      			</tr>
      		@endif
      
            <tr valign="top" style="margin:0px">
                <td style="border-bottom:1px dotted;width:70%">
                  @if($receipt_details->display_name != "BRIX NEURO")
                  	@if($receipt_details->display_name != "ADITI MART")
                    <b>{{ $line['name'] }} {{ $line['variation'] }}</b><br>
                  	@endif
                   @if (!empty($line['alias']))
                    {{ $line['alias'] }}
                   @endif
                   <small>
                     @if (!empty($line['cat_code']))
                     <br>HSN : {{ $line['cat_code'] }}
                     @elseif(!empty($line['hsn']))
                     <br>HSN : {{ $line['hsn'] }}
                     @endif
                   </small>
                   <br>
                  @endif
                    @if (!@empty($line['mrp']))
                        @if ($line['mrp_uf'] > $line['unit_price_inc_tax_uf'])
                            MRP : <strong><span class='display_currency'
                                    data-currency_symbol='true'>{{ $line['mrp'] }}</span></strong><br>
                            @php
                                $totalmrp = $totalmrp + $line['mrp_uf'] * $line['quantity_uf'];
                                $totalsaleprice = $totalsaleprice + $line['unit_price_inc_tax_uf'] * $line['quantity_uf'];
                            @endphp
                            <span style="font-size:5pt">
                                @if (!empty($line['line_discount']) && $line['line_discount'] > 0)
                                    Discount :
                                    <span class='display_currency'
                                        data-currency_symbol='true'>{{ $line['line_discount'] }}</span>
                                    @if (!empty($line['line_discount_percent']))
                                        {{ $line['line_discount_percent'] }}
                                    @endif<br>
                                @endif
                            </span>
                            Offer Price :
                            <strong>
                              <span class='display_currency' data-currency_symbol='true'>{{ $line['unit_price_inc_tax'] }}</span>
                  			</strong>
                        @else
                  			Price :
                            <strong><span class='display_currency'
                                    data-currency_symbol='true'>{{ $line['unit_price_inc_tax'] }}</span></strong><br>
                            @php
                                //$totalsaleprice = $totalsaleprice + $line['unit_price_inc_tax'];
                            @endphp
                        @endif
                    @else
                        @if (!empty($line['line_discount']) && $line['line_discount'] > 0)
                            Discount :
                            <span class='display_currency'
                                data-currency_symbol='true'>{{ $line['line_discount'] }}</span>
                            @if (!empty($line['line_discount_percent']))
                                {{ $line['line_discount_percent'] }}
                            @endif<br>
                        @endif
                        <strong><span class='display_currency'
                                data-currency_symbol='true'>{{ $line['unit_price_inc_tax'] }}</span></strong>
                        @php
                            //$totalsaleprice = $totalsaleprice + $line['unit_price_inc_tax'];
                        @endphp
                    @endif
                    <br>
                    <strong><small>X {{ $line['quantity'] }} {{ $line['units'] }}</small></strong>
                </td>
                <td style="border-bottom:1px dotted; text-align:right">
                    <span class='display_currency' data-currency_symbol='true'>
                        @php
                            $total = str_replace(',', '', $line['unit_price_inc_tax_uf']) * $line['quantity_uf'];
                            echo $total;
                        @endphp
                    </span>
                    <br>
                    (+)<span class='display_currency' data-currency_symbol='true'>
                        @php
                            $total = str_replace(',', '', $line['tax_uf']) * $line['quantity_uf'];
                            echo $total;
                        @endphp
                    </span><br>
                    <small>{{ $line['tax_name'] }}</small>
                    <hr style="margin:0px">
                    <strong><span class='display_currency' data-currency_symbol='true'>
                            @php
                                $total = str_replace(',', '', $line['unit_price_inc_tax_uf']) * $line['quantity_uf'];
                                echo $total;
                            @endphp
                        </span></strong>

                </td>
            </tr>
        @endforeach
    </table>

    <table style="width: 98%">
        <tr>
            <td style="text-align: left;border-bottom:1px">{!! $receipt_details->subtotal_label !!}</td>
            <td style="text-align: right; border-bottom:1px">{{ $receipt_details->subtotal }}</td>
        </tr>

        <!-- Order Taxes -->
        @if (!empty($receipt_details->group_tax_details))
            @foreach ($receipt_details->group_tax_details as $key => $value)
                <tr>
                    <td style="text-align: left">Order {!! $key !!}</td>
                    <td style="text-align: right">(+) {{ $value }}</td>
                </tr>
            @endforeach
        @else
            @if (!empty($receipt_details->tax))
                <tr>
                    <td style="text-align: left">{!! $receipt_details->tax_label !!}</td>
                    <td style="text-align: right">(+) {{ $receipt_details->tax }}</td>
                </tr>
            @endif
        @endif


        <!-- Line Taxes -->
        @php
            $totalcgst = 0;
        @endphp
        @foreach ($receipt_details->lines as $line)
            @php
                $totaltax = str_replace(',', '', $line['tax']);
                if (is_numeric($totaltax)) {
                    $totaltax = (float) $totaltax;
                }
                $linecgst = $totaltax / 2;
                $totalcgst = $totalcgst + $linecgst * $line['quantity'];
            @endphp
        @endforeach

        @if ($totalcgst > 0)
            <tr>
                <td style="text-align: left">
                    Total CGST
                </td>
                <td style="text-align: right">
                    (+) <span class='display_currency' data-currency_symbol='true'>{{ $totalcgst }}</span>
                </td>
            </tr>

            <tr>
                <td style="text-align: left">
                    Total SGST
                </td>
                <td style="text-align: right">
                    (+) <span class='display_currency' data-currency_symbol='true'>{{ $totalcgst }}</span>
                </td>
            </tr>
        @endif


        <!-- Round Off -->
        @if (!empty($receipt_details->round_off_label))
            <tr>
                <td style="text-align: left">
                    {!! $receipt_details->round_off_label !!}
                </td>
                <td style="text-align: right">
                    <span class='display_currency' data-currency_symbol='true'>{{ $receipt_details->round_off }}</span>
                </td>
            </tr>
        @endif


        <!-- Shipping Charges -->
        @if (!empty($receipt_details->shipping_charges))
            <tr>
                <td style="text-align: left; border-bottom:1px">{!! $receipt_details->shipping_charges_label !!}</td>
                <td style="text-align: right; border-bottom:1px">(+) {{ $receipt_details->shipping_charges }}</td>
            </tr>
        @endif

        <!-- Discount -->
        @if (!empty($receipt_details->discount))
            <tr>
                <td style="text-align: left">{!! $receipt_details->discount_label !!}</td>
                <td style="text-align: right">(-) {{ $receipt_details->discount }}</td>
            </tr>
        @endif

        <hr style="margin: 0px" />
        <tr style="font-size: medium">
            <td style="text-align: left">{!! $receipt_details->total_label !!}</td>
            <td style="text-align: right"><strong>{{ $receipt_details->total }}</strong>

            </td>
        </tr>

        @if (!empty($receipt_details->total_due))
            <tr>
                <td style="text-align: left;">{!! $receipt_details->total_due_label !!}</td>
                <td style="text-align: right; color: red">{{ $receipt_details->total_due }}</td>
            </tr>
        @endif

        <!-- Total Paid-->
        @if (!empty($receipt_details->total_paid))
            <tr>
                <td style="text-align: left;">{!! $receipt_details->total_paid_label !!}</td>
                <td style="text-align: right;">{{ $receipt_details->total_paid }}</td>
            </tr>
        @endif




        @if (!empty($receipt_details->taxes))
            <tr>
                <td colspan="2">
                    <hr style='margin:0px'>
                    TAX BREAKUP
                </td>
            </tr>
            @foreach ($receipt_details->taxes as $key => $value)
                <tr>
                    <td style='text-align: left; font-size:6pt'> {{ $key }} </td>
                    <td style='text-align: right; font-size:6pt'> {{ $value }} </td>
                </tr>
            @endforeach
        @endif





    </table>




    <hr style="margin:0px">
    <center>
        @if (!empty($receipt_details->payments))

            <div>
                @foreach ($receipt_details->payments as $payment)
                    <small>Paid <strong>{{ $payment['amount'] }}</strong> by <strong>{{ $payment['method'] }}</strong>
                    </small><br>
                @endforeach
            </div>
        @endif
        <div>
            <center>
                <div>

                    <center>
                        {{ $receipt_details->additional_notes }}

                        <span style="font-size:x-small;">
                            @if (!empty($receipt_details->sales_person_label))
                                {{ $receipt_details->sales_person_label }} : {{ $receipt_details->sales_person }}
                            @endif
                        </span>
                        <hr style="margin:0px">

                    </center>
                </div>
                <hr style="margin:0px">
                <center>
                    <?php echo DNS1D::getBarcodeSVG($receipt_details->invoice_no, 'C39', 1.5, 30); ?>
                </center>
                <hr style="margin:0px">
                <div>
                    <center>
                        @php
                            if ($totalmrp != 0) {
                                echo "Hurray! You saved <span style='font-weight:bold' class='display_currency' data-currency_symbol='true'>" . ($totalmrp - $totalsaleprice) . '</span> on MRP';
                                echo "<hr style='margin:0px'>";
                            }
                            
                        @endphp
                        @if (!empty($receipt_details->footer_text))
                            {!! $receipt_details->footer_text !!}
                        @endif
                    </center>
                </div>             

        </div>
