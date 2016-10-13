<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;
use Paypal;
use App\Ipn;
use App\Subscriptions;
use App\ExpressCheckoutDetails;
use App\Profile;
use App\User;
use Validator;
use Dingo\Api\Exception\UpdateResourceFailedException as update_exception;

class PaypalController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function setExpressCheckout(Request $request) {
        $package = json_decode(file_get_contents("php://input"), true);
        $rules = array(
            'package_id' => 'required|numeric',
            'package_plan' => 'required',
        );

//        $package = array(
//            'package_plan' => 'month',
//            'package_id' => 2
//        );
        $validator = Validator::make($package, $rules);


        if ($validator->fails()) {
            throw new update_exception('Could not make payment.', $validator->errors());
        } else {
              $user = JWTAuth::parseToken()->authenticate();
                    $user_id = $user->id;
            if ($package['package_id'] > 1) {
                $package_data = Subscriptions::where(array('subscription_id' => $package['package_id']))
                                ->get()->toArray();

                if (!empty($package_data)) {
                    if ($package['package_plan'] == 'month') {
                        $amt = $package_data[0]['price_monthly'];
                        $sub_name = $package_data[0]['subscriptions_name'];
                    } else {
                        $amt = $package_data[0]['price_yearly'];
                        $sub_name = $package_data[0]['subscriptions_name'];
                    }
                  

                    $request_data = array(
                        'USER' => 'owner_api1.zilculator.com',
                        'PWD' => 'XSTG2TR8HPCXH4WS',
                        'SIGNATURE' => 'ACXs.mrJPxIsVOYc5al1QwsPUKQFA43JN-IMMyV4re77a2W5vBKafMHE',
                        'METHOD' => 'SetExpressCheckout',
                        'VERSION' => '108',
                        'LOCALECODE' => 'pt_US',
                        'PAYMENTREQUEST_0_AMT' => $amt,
                        'PAYMENTREQUEST_0_CURRENCYCODE' => 'USD',
                        'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
                        'PAYMENTREQUEST_0_ITEMAMT' => $amt,
                        'L_PAYMENTREQUEST_0_NAME0' => $sub_name,
                        'L_PAYMENTREQUEST_0_DESC0' => $sub_name,
                        'L_PAYMENTREQUEST_0_AMT0' => $amt,
                        'L_PAYMENTREQUEST_0_ITEMCATEGORY0' => 'Digital',
                        'L_BILLINGTYPE0' => 'RecurringPayments',
                        'L_BILLINGAGREEMENTDESCRIPTION0' => 'Exemplo',
                        'CANCELURL' => 'http://api.zilculator.com/',
                        'RETURNURL' => 'http://api.zilculator.com/v1/get_detail'
                    );
                    $ob = new Paypal();
                    $response = $ob->paypal_request($request_data);

                    $nvp = array();

                    if (preg_match_all('/(?<name>[^\=]+)\=(?<value>[^&]+)&?/', $response, $matches)) {
                        foreach ($matches['name'] as $offset => $name) {
                            $nvp[$name] = urldecode($matches['value'][$offset]);
                        }
                    }
                    if (isset($nvp['TOKEN'])) {
                        $tkn = $nvp['TOKEN'];
                    } else {
                        $tkn = false;
                    }
                    $data = array(
                        'token' => $tkn,
                        'timestamp' => $nvp['TIMESTAMP'],
                        'ack' => $nvp['ACK'],
                        'user_id' => $user_id,
                        'package_type' => ucfirst($package['package_plan']),
                        'package_name' => $sub_name,
                        'package_id' => $package['package_id'],
                    );
                    $res = ExpressCheckoutDetails::create($data);
                    if ($res) {
                        if (isset($nvp['ACK']) && $nvp['ACK'] == 'Success') {
                            $query = array(
                                'cmd' => '_express-checkout',
                                'token' => $nvp['TOKEN']
                            );
                            $redirectURL = sprintf('https://paypal.com/cgi-bin/webscr?%s', http_build_query($query));
                           
                            return response()->json(array('status' => 'success', 'url' =>$redirectURL), 200);
                        }
                        else{
                           throw new update_exception('Could not make payment');
                        }
                    }
                }
            }
            else{
                 User::where(array('id' => $user_id))
                     ->update(array('user_level'=>$package['package_id']));
                 
                 return response()->json(array('status' => 'success', 'message' => 'user have a free subscription '), 200);
            }
        }
    }

 
    public function update(Request $request, $id) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
    }

    public function getipn(Request $request) {
        if (isset($_REQUEST['first_name'])) {
            $data['first_name'] = $_REQUEST['first_name'];
        }
        if (isset($_REQUEST['last_name'])) {
            $data['last_name'] = $_REQUEST['last_name'];
        }
        if (isset($_REQUEST['payer_email'])) {
            $data['payer_email'] = $_REQUEST['payer_email'];
        }
        if (isset($_REQUEST['recurring_payment_id'])) {
           $res = Profile::where('PROFILEID',$_REQUEST['recurring_payment_id'])
                    ->update(array('PROFILESTATUS'=>$_REQUEST['profile_status']));
           if($res){
               $profile_data = Profile::where('PROFILEID',$_REQUEST['recurring_payment_id'])->get()->toArray();
           }
           
        }
       
        $data['recurring_payment_id'] = $_REQUEST['recurring_payment_id'];
        $data['payer_id'] = $_REQUEST['payer_id'];
        $data['txn_id'] = $_REQUEST['txn_id'];
        $data['payment_type'] = $_REQUEST['payment_type'];
        $data['package_name'] = $_REQUEST['product_name'];
        $data['amount'] = $_REQUEST['amount'];
        $data['settle_amount'] = $_REQUEST['settle_amount'];
        $data['mc_fee'] = $_REQUEST['mc_fee'];
        $data['exchange_rate'] = $_REQUEST['exchange_rate'];
        $data['payment_fee'] = $_REQUEST['payment_fee'];
        $data['ipn_track_id'] = $_REQUEST['ipn_track_id'];
        $data['payment_status'] = $_REQUEST['payment_status'];
        $data['payment_date'] = $_REQUEST['payment_date'];
        $data['next_payment_date'] = $_REQUEST['next_payment_date'];
        $data['payment_status'] = $_REQUEST['payment_status'];
        $data['payment_cycle'] = $_REQUEST['payment_cycle'];
        $data['settle_currency'] = $_REQUEST['settle_currency'];
        $data['currency_code'] = $_REQUEST['currency_code'];
        $data['user_id'] = $profile_data[0]['user_id'];
        $data['profile_status'] = $profile_data[0]['PROFILESTATUS'];
        $data['data'] = json_encode($_REQUEST);

        Ipn::create($data);
        
    }

}
