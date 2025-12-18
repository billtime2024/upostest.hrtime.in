<style>
    /* body {
        margin: 0;
        padding: 0;
        background-color: #fff;
        font: 12pt "Tahoma";
    } */

    * {
        box-sizing: border-box;
        -moz-box-sizing: border-box;
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
        table td{
        border: 1px solid;
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

$IS_IGST = false;

function c2i($number)
{
    setlocale(LC_MONETARY, 'en_IN');
    return moneyFormatIndia($number); // for linux
    //return preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $number); //for windows
}

function moneyFormatIndia($amount)
    {

        $amount = round($amount,2);

        $amountArray =  explode('.', $amount);
        if(count($amountArray)==1)
        {
            $int = $amountArray[0];
            $des=00;
        }
        else {
            $int = $amountArray[0];
            $des=$amountArray[1];
        }
        if(strlen($des)==1)
        {
            $des=$des."0";
        }
        if($int>=0)
        {
            $int = numFormatIndia( $int );
            $themoney = $int.".".$des;
        }

        else
        {
            $int=abs($int);
            $int = numFormatIndia( $int );
            $themoney= "-".$int.".".$des;
        }   
        return $themoney;
    }

function numFormatIndia($num)
    {

        $explrestunits = "";
        if(strlen($num)>3)
        {
            $lastthree = substr($num, strlen($num)-3, strlen($num));
            $restunits = substr($num, 0, strlen($num)-3); // extracts the last three digits
            $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
            $expunit = str_split($restunits, 2);
            for($i=0; $i<sizeof($expunit); $i++) {
                // creates each of the 2's group and adds a comma to the end
                if($i==0) {
                    $explrestunits .= (int)$expunit[$i].","; // if is first value , convert into integer
                } else {
                    $explrestunits .= $expunit[$i].",";
                }
            }
            $thecash = $explrestunits.$lastthree;
        } else {
            $thecash = $num;
        }
        return $thecash; // writes the final format where $currency is the currency symbol.
    }

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
} ?> <table style="width:100%;">
    <thead>
        <tr>
            <td style="border:none">
                <!-- HEADER -->
                <center>
                    @if (!empty($receipt_details->header_text))
                        {!! $receipt_details->header_text !!}
                    @endif
                    <h5><strong>
                            @if (!empty($receipt_details->invoice_heading))
                                {!! $receipt_details->invoice_heading !!}
                            @endif
                        </strong></h5>
                </center>
                <div
                    style="font-family: verdana; border-width: medium; width: 100%; margin-right: auto; margin-left: auto;">
                    <table style="text-align: left; width: 100%;font-size:8pt;" >
                        <tr>
                            <td colspan=2>
                                <table style="width:100%">
                                    <tr>
                                      	@if (!empty($receipt_details->logo))
                                        <td style="width:20%; text-align:left; padding:2px;">
                                           
                                                <img src="{{ $receipt_details->logo }}" style="width:80px;"><br>
                                           
                                        </td>
                                        <td style="text-align: center; width:60%"> <span
                                                style="margin:0px; font-size:15pt;font-weight:bold; text-transform: uppercase; color:#400400;">{{ $receipt_details->display_name }}
                                          		
                                          </span>
                                          
                                          @php
					$sub_headings = implode('<br/>', array_filter([$receipt_details->sub_heading_line1, $receipt_details->sub_heading_line2, $receipt_details->sub_heading_line3, $receipt_details->sub_heading_line4, $receipt_details->sub_heading_line5]));
				@endphp

				@if(!empty($sub_headings))
					<br><span>{!! $sub_headings !!}</span>
				@endif
                                          
                                          
                                          <br>
                                        </td>
                                        <td style="width:20%"></td>
                                      @else
                                      	 <td style="text-align: center; width:100%"> <span
                                                style="margin:0px; font-size:15pt;font-weight:bold; text-transform: uppercase;">{{ $receipt_details->display_name }}</span> 
                                          @php
					$sub_headings = implode('<br/>', array_filter([$receipt_details->sub_heading_line1, $receipt_details->sub_heading_line2, $receipt_details->sub_heading_line3, $receipt_details->sub_heading_line4, $receipt_details->sub_heading_line5]));
				@endphp

				@if(!empty($sub_headings))
					<br><span>{!! $sub_headings !!}</span>
				@endif
                                        </td>
                                      @endif
                                    </tr>
                                    <table>
                            </td>
                        </tr>
                    </table>
                    <hr style="margin:0px">
                    <table style="width:100%">
                        <tr>
                            <td colspan="3">
                                @if (!empty($receipt_details->display_name))
                                    <div style=" text-align:center; ">

                                        @if (!empty($receipt_details->address))
                                            {!! $receipt_details->address !!}
                                        @endif

                                        @if (!empty($receipt_details->contact))
                                            <hr style='margin:0px'><b>{{ $receipt_details->contact }}</b>
                                        @endif

                                        @if (!empty($receipt_details->website))
                                            | {{ $receipt_details->website }}
                                        @endif

                                        @if (!empty($receipt_details->tax_info1))
                                            | {{ $receipt_details->tax_label1 }}
                                            <b>{{ $receipt_details->tax_info1 }}</b>
                                        @endif

                                        @if (!empty($receipt_details->tax_info2))
                                            | {{ $receipt_details->tax_label2 }}
                                            <b>{{ $receipt_details->tax_info2 }}</b>
                                        @endif

                                        @if (!empty($receipt_details->location_custom_fields))
                                            <hr style='margin:0px'>
                                            {{ $receipt_details->location_custom_fields }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:2px; border-top:1px solid;" colspan=2>
                                To,
                                @if (!empty($receipt_details->customer_name))
                                        <strong>{{ $receipt_details->customer_name }}</strong>
                                    @endif

                                @if (!empty($receipt_details->customer_info))
                                    {!! $receipt_details->customer_info !!}
                                @endif
                              
                                @if (!empty($receipt_details->client_id_label))
                                    <br />
                                    {{ $receipt_details->client_id_label }} :
                                    {{ $receipt_details->client_id }}
                                @endif

                                @if (!empty($receipt_details->customer_tax_number))
                                    <hr style="margin:0px">
                                    {{ $receipt_details->customer_tax_label }} :
                                    <b>{{ $receipt_details->customer_tax_number }}</b>
                                @endif
                                @if (!empty($receipt_details->customer_custom_fields))
                                    <hr style="margin:0px">{!! $receipt_details->customer_custom_fields !!}
                                @endif
                                @if (!empty($receipt_details->sales_person_label))
                                    <hr style="margin:0px">
                                    {{ $receipt_details->sales_person_label }} :
                                    {{ $receipt_details->sales_person }}
                                @endif
                             
                              	@if(!empty($receipt_details->shipping_address))
                                    <hr style='margin:0px'>
                                    Shipping Address : {{ $receipt_details->shipping_address }}
                              		<br>
                                @endif
                            
                              	
                            </td>

                            <td style="padding:2px; text-align:left; border-left:1px solid; width:30%">
                                @if (!empty($receipt_details->invoice_no_prefix))
                                    {!! $receipt_details->invoice_no_prefix !!} : 
                                @endif
                                <b>{{ $receipt_details->invoice_no }}</b><br />
                                <!-- Date-->
                                @if (!empty($receipt_details->date_label))
                                    {{ $receipt_details->date_label }} : 
                                    <strong>{{ $receipt_details->invoice_date }}</strong>
                                @endif
                              	@if (!empty($receipt_details->sell_custom_field_1_label))<br>
                                	{{ $receipt_details->sell_custom_field_1_label }} : <b>{{ $receipt_details->sell_custom_field_1_value }}</b>
                                @endif
                              	@if (!empty($receipt_details->sell_custom_field_2_label))<br>
                                	{{ $receipt_details->sell_custom_field_2_label }} : <b>{{ $receipt_details->sell_custom_field_2_value }}</b>
                                @endif
                                @if (!empty($receipt_details->sell_custom_field_3_label))<br>
                                	{{ $receipt_details->sell_custom_field_3_label }} : <b>{{ $receipt_details->sell_custom_field_3_value }}</b>
                                @endif
                                @if (!empty($receipt_details->sell_custom_field_4_label))<br>
                                	{{ $receipt_details->sell_custom_field_4_label }} : <b>{{ $receipt_details->sell_custom_field_4_value }}</b>
                                @endif
                              
                               @if (!empty($receipt_details->additional_notes))
                                       <hr style='margin:0px'>{{ $receipt_details->additional_notes }}
                                   @endif<br>
                               
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </thead>
    <tr>
        <td style="vertical-align: top;">

            @foreach ($receipt_details->lines as $line)
                @if (!empty($line['mrp_uf']))
                    @php $TOTAL_MRP += $line['mrp_uf'] * $line['quantity_uf']; @endphp
                @endif
                @if (!empty($line['tax_uf']))
                    @php $TOTAL_GST += $line['tax_uf'] * $line['quantity_uf']; @endphp
                @endif
                @if (!empty($line['line_discount_uf']))
                    @php
                        $TOTAL_DISCOUNT += $line['line_discount_uf'] * $line['quantity_uf'];
                    @endphp
                @endif
            @endforeach

            <div style="min-height:350px;">
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
                            @if ($TOTAL_DISCOUNT > 0)
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
                                @if (!empty($line['p_description']))
                                    <pre>{{ strip_tags($line['p_description']) }}</pre>
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
                                @if (!empty($line['alias']))
                                    {{ $line['alias'] }}
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
                                    {{ c2i($line['unit_price_before_discount_uf']) }}
                            </td>

                            @if ($TOTAL_DISCOUNT > 0)
                                <td style="padding:3px; text-align:right">
                                        {{ c2i($line['line_discount_uf'] * $line['quantity_uf']) }}
                                    
                                    @if (!empty($line['line_discount_percent']))
                                        {{ $line['line_discount_percent'] }}
                                    @endif
                                </td>
                            @endif

                            @if ($TOTAL_GST > 0)
                                <td style="padding:3px; text-align:right">
                                    {{ c2i(($line['unit_price_before_discount_uf'] - $line['line_discount_uf']) * $line['quantity_uf']) }}
                                    @php
                                        $TOTAL_TAXABLE += ($line['unit_price_before_discount_uf'] - $line['line_discount_uf']) * $line['quantity_uf'];
                                    @endphp
                                </td>
                                <td style="padding:3px; text-align:right; width:10%">
                                    @if (!empty($line['tax']))
                                        {{ c2i($line['tax_uf'] * $line['quantity_uf']) }}
                                    @endif
                                    @if (!empty($line['tax_name']))
                                        <br><span style="font-size: 6pt">({{ $line['tax_name'] }})</span>
                                  		 @php
                                  			if($IS_IGST == false)
                                  			{
                                                //echo $line['tax_name'];
                                  				if($line['tax_name'] == "IGST 5%" || $line['tax_name'] == "IGST 12%" || $line['tax_name'] == "IGST 18%")
                                                {
                                                    //echo "IS IGST";   
                                                    $IS_IGST = true;
                                                }
                                  			}
                                            else
                                            {
                                  				//echo "IS IGST";
                                            }
                                  		@endphp
                                    @endif
                                </td>
                            @endif

                            <td style="padding:3px; text-align:right">
                                    {{ c2i($line['line_total_uf']) }}
                                @php
                                    $TOTAL_ORDER += $line['line_total_uf'];
                                @endphp
                            </td>

                        </tr>
                    @endforeach
                  
                    <?php
					$sec_height = 350;

                    if (count($receipt_details->lines) < 11) {
                        $height = $sec_height - count($receipt_details->lines) * 50;
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
                        if ($TOTAL_DISCOUNT > 0) {
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
                            @if ($TOTAL_MRP > 0 && $TOTAL_DISCOUNT > 0)
                                <td colspan="7" style="text-align: right; padding-right: 10px;">Total</td>
                            @elseif ($TOTAL_MRP > 0 || $TOTAL_DISCOUNT > 0)
                                <td colspan="6" style="text-align: right; padding-right: 10px;">Total</td>
                            @else
                                <td colspan="5" style="text-align: right; padding-right: 10px;">Total</td>
                            @endif
                        @else
                            @if ($TOTAL_MRP > 0 && $TOTAL_DISCOUNT > 0)
                                <td colspan="5" style="text-align: right; padding-right: 10px;">Total</td>
                            @elseif ($TOTAL_MRP > 0 || $TOTAL_DISCOUNT > 0)
                                <td colspan="5" style="text-align: right; padding-right: 10px;">Total</td>
                            @else
                                <td colspan="4" style="text-align: right; padding-right: 10px;">Total</td>
                            @endif
                        @endif
                      
                      
                      	

                        @if ($TOTAL_DISCOUNT > 0)
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
              
                <center style="font-size: 7pt">Thank you for your business with us!</center>
             
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
                        <hr style='margin:0px'>
                        @if (!empty($receipt_details->additional_notes))
                            <small>{{ $receipt_details->additional_notes }}</small><br>
                        @endif
                      
                      
                      
                      	@if(!empty($receipt_details->shipping_address))
                      		<hr style='margin:0px'>
                      		Shipping Address : {{ $receipt_details->shipping_address }}
                      <br>
                      	@endif
                    </td>
                    <td style="width:33%; vertical-align:bottom; text-align:center">
                       
                            @if (!empty($receipt_details->total_due))
                                @if ($receipt_details->total_due == '0' || $receipt_details->total_due == '')
                                    <img src="{{ asset('img/paid_stamp_200.png') }}" style="width:60px" /><br>
                                @endif
                            @endif
                     
                      
                        ____________________<br>
                        <b>Authorised Signatory</b><br>
                        @if (!empty($receipt_details->display_name))
                            <span
                                style="margin:0px; font-weight:bold; text-transform: uppercase;">{{ $receipt_details->display_name }}</span><br>
                        @endif

                        <center>
                            @if ($receipt_details->show_barcode)
                                <?php echo DNS1D::getBarcodeSVG($receipt_details->invoice_no, 'C128', 1, 30); ?>
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
                                {{ session('currency')['symbol'] ?? '' }}
                                    @php echo c2i($TOTAL_TAXABLE); @endphp
                            </b>
                            <br>

                            @if($IS_IGST == false)
                            	Total GST :
                            @else
                            	Total IGST :
                            @endif
                            (+)
                            {{ session('currency')['symbol'] ?? '' }}
                                {{ c2i($TOTAL_GST) }}
                            <br>
                            @if(!$IS_IGST)
                            <small>
                                Total CGST :
                                {{ session('currency')['symbol'] ?? '' }}
                                {{ c2i($TOTAL_GST / 2) }}
                            </small>
                            <br>
                            <small>
                                Total SGST :
                                {{ session('currency')['symbol'] ?? '' }}
                                {{ c2i($TOTAL_GST / 2) }}
                                <br>
                            </small>
                            @endif
                        @endif

                        <b> {!! $receipt_details->subtotal_label !!} {{ c2i($receipt_details->subtotal_uf) }}</b><br>

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
                        <b>{!! $receipt_details->total_label !!} 
                            {{ session('currency')['symbol'] ?? '' }}{{ c2i($receipt_details->total_uf) }}</b>
                        <hr style="margin:0px" />
                        <span style="font-size:7pt; font-weight: bold"> @php echo convertToIndianCurrency($receipt_details->total_uf); @endphp </span>

                        @if ($TOTAL_MRP > $TOTAL_ORDER)
                      		
                            <hr style="margin:0px" />
                            	Hurray! You saved <b>{{ session('currency')['symbol'] ?? '' }}{{ c2i($TOTAL_MRP - $TOTAL_ORDER) }}</b> on MRP                      			
                        @endif
                        <hr style="margin:0px" />

                        @if (!empty($receipt_details->total_paid))
                            {!! $receipt_details->total_paid_label !!} : <b>{{ c2i($receipt_details->total_paid_uf) }}</b><br>
                      		
                        @endif
                      	@if (!empty($receipt_details->total_due_label))
                      			@if($receipt_details->total_due_uf > 0)
                            		{!! $receipt_details->total_due_label !!} : <b>{{ c2i($receipt_details->total_due_uf) }}</b><br>
                      			@endif
                      	@endif
                        @if (!empty($receipt_details->all_bal_label))
                            Current Due : {{ c2i($receipt_details->total_due_uf) }}<br>
                            Previous Due : {{ session('currency')['symbol'] ?? '' }}{{ c2i($receipt_details->all_due_uf - $receipt_details->total_due_uf) }}
                            <hr style="margin:0px">
                            {!! $receipt_details->all_bal_label !!} :
                            <b>{{ c2i($receipt_details->all_due_uf) }}</b>
                        @endif

                        @if (!empty($receipt_details->shipping_details))
                            {{ $receipt_details->shipping_details }}
                        @endif
                        @if (!empty($receipt_details->shipping_custom_field_1_value))
                        {{ $receipt_details->shipping_custom_field_1_label }} - {{ $receipt_details->shipping_custom_field_1_value }}
                        @endif
                        @if (!empty($receipt_details->shipping_custom_field_2_value))
                        {{ $receipt_details->shipping_custom_field_2_label }} - {{ $receipt_details->shipping_custom_field_2_value }}
                        @endif
                    </td>
                </tr>

            </table>

        </td>
    </tr>

</table>
