<?php

use Illuminate\Http\Request;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

if(!function_exists("generate_otp")){
    function generate_otp (int $len) {
        $numbers = "0123456789";
        $result = "";
        for($i = 0; $i < $len; $i++){
            $result .= $numbers[rand(0, $len - 1)];
        }
        return $result;
    }
}

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get("/send-otp/{phone_numer}/", function ($phone_number) {
 if($phone_number[0] != "9" && $phone_number[1] != "1"){
     return response()->json(array("msg" => " 91 before phone number"));
 }
 if(strlen($phone_number) != 12) {
     return response()->json(array("msg" => "Enter a valid phone number"));
 }
 $otp = generate_otp(5);
 Session::put("otp_". $phone_number, $otp);
                    $business_id = \App\Business::where("owner_id", 1)->get()[0]["id"];
                 $business_details = \App\Business::leftjoin('tax_rates AS TR', 'business.default_sales_tax', 'TR.id')
                        ->leftjoin('currencies AS cur', 'business.currency_id', 'cur.id')
                        ->select(
                            'business.*',
                            'cur.code as currency_code',
                            'cur.symbol as currency_symbol',
                            'thousand_separator',
                            'decimal_separator',
                            'TR.amount AS tax_calculation_amount',
                            'business.default_sales_discount'
                        )
                        ->where('business.id', $business_id)
                        ->first();
                $instance_id = $business_details['sms_settings']['param_val_2'];
                $whatsapp_api = $business_details['sms_settings']['param_val_3'];
                $url = $business_details["sms_settings"]["url"];

                    $response =  Http::post($url, [
                        "number" => (int) $phone_number,
                        "type" => "text",
                        "message" =>  "*" .$otp . "* 🔑 is Your OTP on *BillTime Cloud Software*.
Please use this OTP to your verification. Thank you! 😊"
,                        "instance_id" => $instance_id,
                        "access_token" =>  $whatsapp_api
                    ]);
                return response()->json(array('msg' => 'sent'));
})->name("send-otp");


Route::post("/verify-otp/", function(Request $request){
    $data = $request->json()->all();
       
    $old_otp = Session::get("otp_". $request->phone_number);
    if($request->otp == $old_otp){
        return response()->json(array("msg" => "valid otp"));
    } else {
        return response()->json(array("msg" => "invalid otp"));
    }
})->name("verify-otp");























