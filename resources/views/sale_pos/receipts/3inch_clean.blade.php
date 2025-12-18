@php
$totalmrp = 0;
$totalsaleprice = 0;
$roundoff = 0;
$totdiscount = 0;
$total_paid = "";
@endphp

<div
    style="width: 68 mm; page-break:avoid; font-family:'Bree Serif', 'verdana'; font-size:8pt; line-height:16px; margin:0px; padding:5px; z-index:'9999'">
    <!-- Logo-->
    @if (!empty($receipt_details->logo))
        <center><img src="{{ $receipt_details->logo }}" style="width:80%;"></center>
    @endif
    <div style="text-align: center; line-height:13px">
        @if (!empty($receipt_details->display_name))
      		@if($receipt_details->display_name != "Essentials")
            <span style="margin: 0px; font-weight: bold; font-size:small">
                {{ $receipt_details->display_name }} <br>
            </span>
      		@endif
            @if (!empty($receipt_details->header_text))
                {!! $receipt_details->header_text !!}
            @endif
            @if (!empty($receipt_details->location_name))
                <b> {{ $receipt_details->location_name }} </b><br>
            @endif
            @if (!empty($receipt_details->address))
                {!! $receipt_details->address !!}<br />
            @endif

            @if (!empty($receipt_details->contact))
                {{ $receipt_details->contact }}<br />
            @endif

            @if (!empty($receipt_details->website))
                Website : {{ $receipt_details->website }}<br />
            @endif

            @if (!empty($receipt_details->location_custom_fields))
                {{ $receipt_details->location_custom_fields }}
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
                <td style="width:50%; text-align:center; font-size:7pt">
                    @if (!empty($receipt_details->invoice_no_prefix))
                        {!! $receipt_details->invoice_no_prefix !!}@endif
                    <strong>{{ $receipt_details->invoice_no }}</strong>
                    |
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
              @if($receipt_details->display_name == "BRIX NEURO SPINE CENTER")
                Patient Name : 
              @endif
                {{ $receipt_details->customer_name }}
          		@if($receipt_details->display_name != "BRIX NEURO SPINE CENTER")
                  @if (!empty($receipt_details->customer_mobile))
                      <small>Contact : {!! $receipt_details->customer_mobile !!}</small><br>
                  @endif
                  @if (!empty($receipt_details->customer_tax_number))
                      <small>(GSTIN : {{ $receipt_details->customer_tax_number }})</small><br>
                  @endif
          		@endif

            @endif
        </center>
    </div>
    <hr style="margin:0px">
    <table style="width: 100%; text-align:left; font-size:7pt; line-height:9pt;" border="0">
        <tr style="text-align: left; font-weight: bold; border-bottom:1px solid">
          	@if($receipt_details->display_name == "Essentials")
          		<td>Item</td>
            @elseif($receipt_details->display_name != "BRIX NEURO SPINE CENTER")
          		<td>MRP</td>
          	@endif
          	<td style="text-align:left;">
          		@if($receipt_details->display_name != "BRIX NEURO SPINE CENTER")
              		Rate
              	@else
              		Charges
              	@endif
          	</td>
            <td style="text-align:center;">Qty</td>
             @if($receipt_details->display_name == "BRIX NEURO SPINE CENTER")
                  <td style="text-align:right;">Discount</td>
          	@endif
             <td style="text-align:right">Total</td>
        </tr>
        @foreach ($receipt_details->lines as $line)


            <!--  ------------------------------------------------MRP CALCULATIONS------------------------------->
            @if (!@empty($line['mrp_uf']))
                @if ($line['mrp_uf'] > $line['unit_price_inc_tax_uf'])
                    @php
                        $totalmrp = $totalmrp + $line['mrp_uf'] * $line['quantity_uf'];
                        $totalsaleprice = $totalsaleprice + $line['unit_price_inc_tax_uf'] * $line['quantity_uf'];
                    @endphp
                @endif
            @endif
            <!--  ------------------------------------------------END MRP CALCULATIONS------------------------------->

			<tr style=""><td style="font-size:7pt; vertical-align:top;" colspan="4">
              @if($receipt_details->display_name == "BRIX NEURO SPINE CENTER")  
              	@if(!empty($line['brand'])) 
              		{{$line['brand']}} - 
              	@endif
              @endif
              
                  @if($receipt_details->display_name == "SK TRADING COMPANY")
                      @if(!empty($line['alias']))
                         <b> {{ $line['alias'] }}</b>
                      @else
               			  {{ $line['name'] }}
                      @endif
                  @else
              		@if($receipt_details->display_name != "ADITI MART")
						{{ $line['name'] }} {{ $line['variation'] }}
              		@else
              			@if(!empty($line['alias']))
                         {{ $line['alias'] }}
                      	@endif
                    @endif
                    @if ($receipt_details->show_cat_code == 1)
                        @if (!empty($line['hsn']))
                            hsn - {{ $line['hsn'] }}
                        @elseif(!empty($line['cat_code']))
                            hsn - {{ $line['cat_code'] }}
                        @endif
                    @endif
                  @endif
                </td></tr>
            <tr style="border-bottom:1px dotted">
                @if($receipt_details->display_name != "BRIX NEURO SPINE CENTER")
                <td style="font-size:6pt; font-weight:bold; width:15%; vertical-align:top;">
                      @if(!empty($line['mrp_uf']))
                          ₹ {{ round($line['mrp_uf'],2) }}
                      @endif
                </td>
                @endif
                <td style="font-size:6pt; font-weight:bold; width:15%;  vertical-align:top;">
                    @if($receipt_details->display_name != "BRIX NEURO SPINE CENTER")
                  		₹ {{ round($line['unit_price_inc_tax_uf'],1) }}
                  	@else
                  		₹ {{ round($line['mrp_uf'],2) }}
                  	@endif
                </td>
                <td style="font-size:6pt;  font-weight:bold; width:10%; text-align:center; vertical-align:top;">
                    {{ round($line['quantity_uf'], 2) }} @if($receipt_details->display_name != "BRIX NEURO SPINE CENTER") <small> {{ $line['units'] }} </small> @endif
                </td>
              	 @if($receipt_details->display_name == "BRIX NEURO SPINE CENTER")
              		<td style="font-size:6pt; width:15%; text-align:right; vertical-align:top;d">
                      	@if(!empty($line['mrp_uf']))
                    	{{ round((($line['mrp_uf'] - $line['unit_price_inc_tax_uf']) * $line['quantity_uf']),2) }}
                      	@endif
                    <br>
                      </td>
                 @endif
                <td
                    style="font-size:6pt; font-weight:bold; width:15%; text-align:right; vertical-align:top;">
                    ₹ {{ round($line['unit_price_inc_tax_uf'] * $line['quantity_uf'],2) }}
                </td>
            </tr>
        @endforeach
    </table>

    <table style="width: 100%; font-size:6pt; font-weight:bold">
        <tr>
            <td style="text-align: left;border-bottom:1px">{!! $receipt_details->subtotal_label !!}</td>
            <td style="text-align: right; border-bottom:1px; font-size:8pt">{{ $receipt_details->subtotal }}</td>
        </tr>

        <!-- Order Taxes -->
        @if (!empty($receipt_details->group_tax_details))
            @foreach ($receipt_details->group_tax_details as $key => $value)
                <tr>
                    <td style="text-align: left">Order {!! $key !!}</td>
                    <td style="text-align: right; font-size:8pt">(+) {{ $value }}</td>
                </tr>
            @endforeach
        @else
            @if (!empty($receipt_details->tax))
                <tr>
                    <td style="text-align: left">{!! $receipt_details->tax_label !!}</td>
                    <td style="text-align: right; font-size:8pt">(+) {{ $receipt_details->tax }}</td>
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
                <td style="text-align: right; font-size:8pt">
                    (+) <span class='display_currency' data-currency_symbol='true'>{{ $totalcgst }}</span>
                </td>
            </tr>

            <tr>
                <td style="text-align: left">
                    Total SGST
                </td>
                <td style="text-align: right; font-size:8pt">
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
                <td style="text-align: right; font-size:8pt">
                    <span class='display_currency' data-currency_symbol='true'>{{ $receipt_details->round_off }}</span>
                    @php
                        $roundoff = $receipt_details->round_off_uf;
                    @endphp
                </td>
            </tr>
        @endif


        <!-- Shipping Charges -->
        @if (!empty($receipt_details->shipping_charges))
            <tr>
                <td style="text-align: left; border-bottom:1px">{!! $receipt_details->shipping_charges_label !!}</td>
                <td style="text-align: right; border-bottom:1px; font-size:8pt">(+) {{ $receipt_details->shipping_charges }}</td>
            </tr>
        @endif
      
      
       <!-- Packing Charges -->
        @if (!empty($receipt_details->packing_charge))
            <tr>
                <td style="text-align: left; border-bottom:1px">{!! $receipt_details->packing_charge_label !!}</td>
                <td style="text-align: right; border-bottom:1px; font-size:8pt">(+) {{ $receipt_details->packing_charge }}</td>
            </tr>
        @endif

        <!-- Discount -->
        @if (!empty($receipt_details->discount))
            <tr>
                <td style="text-align: left">{!! $receipt_details->discount_label !!}</td>
                <td style="text-align: right; font-size:8pt">
                    (-) {{ $receipt_details->discount }}
                    @php
                        $totdiscount = $receipt_details->discount_uf;
                    @endphp
                </td>
            </tr>
        @endif

        <hr style="margin: 0px" />
        <tr style="font-size: small">
            <td style="text-align: left">{!! $receipt_details->total_label !!}</td>
            <td style="text-align: right;"><strong>{{ $receipt_details->total }}</strong>

            </td>
        </tr>

        @if (!empty($receipt_details->total_due))
            <tr>
                <td style="text-align: left;">{!! $receipt_details->total_due_label !!}</td>
                <td style="text-align: right; color: red; font-size:8pt">{{ $receipt_details->total_due }}</td>
            </tr>
        @endif
      
       @if (!empty($receipt_details->all_due))
            <tr>
                <td style="text-align: left;">{!! $receipt_details->all_bal_label !!}</td>
            	<td style="text-align: right; color: red; font-size:8pt">{{ $receipt_details->all_due }}</td>
      		</tr>
       @endif

        <!-- Total Paid-->
        @if (!empty($receipt_details->total_paid))
            <tr>
                <td style="text-align: left;">{!! $receipt_details->total_paid_label !!}</td>
                <td style="text-align: right; font-size:8pt">{{ $receipt_details->total_paid }}</td>
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
                    <td style='text-align: left; font-size:7pt'> {{ $key }} </td>
                    <td style='text-align: right; font-size:8pt'> {{ $value }} </td>
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
                @if ($receipt_details->show_barcode)
                    <hr style="margin:0px">
                    <center>
                        <?php echo DNS1D::getBarcodeSVG($receipt_details->invoice_no, 'C128', 1, 30); ?>
                    </center>
                @endif
                @php
                    if ($totalmrp != 0) {
                        echo "<hr style='margin:0px'>";
              			$total_savings = ($totalmrp - $totalsaleprice - $roundoff + $totdiscount);
              			echo "Hurray! You saved <span style='font-weight:bold' class='display_currency' data-currency_symbol='true'>" . $total_savings . '</span> on MRP';
                      
                        
                    }
                @endphp
              	
                <hr style="margin:0px">
                <div>
                    <center>
                        @if (!empty($receipt_details->footer_text))
                            {!! $receipt_details->footer_text !!}
                        @endif
                    </center>
                </div>
				Thank you for your visit, We hope to see you soon!
                <center><span style="font-size:7pt;">
                </table>

        </div>
    </center></div>
