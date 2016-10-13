<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use Illuminate\Routing\Controller as BaseController;
use JWTAuth;
use App\User;
use App\Property;
use App\ExpressCheckoutDetails;
use App\Ipn;
use App\Profile;
use Calculation;
use Paypal;

class ScheduleController extends BaseController {

    use Helpers;

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
    public function create() {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     *
     *  @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
    }

    public function getExpressCheckoutDetails(Request $request) {
        $token = $request->get('token');

        $request_data = array(
            'USER' => 'owner_api1.zilculator.com',
            'PWD' => 'XSTG2TR8HPCXH4WS',
            'SIGNATURE' => 'ACXs.mrJPxIsVOYc5al1QwsPUKQFA43JN-IMMyV4re77a2W5vBKafMHE',
            'METHOD' => 'GetExpressCheckoutDetails',
            'VERSION' => '108',
            'TOKEN' => $token
        );
        $ob = new Paypal();
        $response = $ob->paypal_request($request_data);


        $nvp = array();
        if (preg_match_all('/(?<name>[^\=]+)\=(?<value>[^&]+)&?/', $response, $matches)) {
            foreach ($matches['name'] as $offset => $name) {
                $nvp[$name] = urldecode($matches['value'][$offset]);
            }
        }

        $data['ack'] = $nvp['ACK'];
        $data['email'] = $nvp['EMAIL'];
        $data['payerid'] = $nvp['PAYERID'];
        $data['payerstatus'] = $nvp['PAYERSTATUS'];
        $data['firstname'] = $nvp['FIRSTNAME'];
        $data['lastname'] = $nvp['LASTNAME'];
        $data['amt'] = $nvp['AMT'];
        $data['taxamt'] = $nvp['TAXAMT'];

        $res = ExpressCheckoutDetails::where(array('token' => $nvp['TOKEN']))
                ->update($data);
        if ($res) {
             $user_detail =  ExpressCheckoutDetails::where('token', $nvp['TOKEN'])->get()->toArray();
             User::where(array('id' => $user_detail[0]['user_id']))
                     ->update(array('user_level'=>$user_detail[0]['package_id']));
             $status =$this->createRecurringPaymentsProfile($nvp['TOKEN']);
            return $status;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function createRecurringPaymentsProfile($token) {
        if ($token != '') {
            $token_detail = ExpressCheckoutDetails::where('token', $token)->get()->toArray();


            $request_data = array(
                'USER' => 'owner_api1.zilculator.com',
                'PWD' => 'XSTG2TR8HPCXH4WS',
                'SIGNATURE' => 'ACXs.mrJPxIsVOYc5al1QwsPUKQFA43JN-IMMyV4re77a2W5vBKafMHE',
                'METHOD' => 'CreateRecurringPaymentsProfile',
                'VERSION' => '108',
                'LOCALECODE' => 'pt_US',
                'TOKEN' => $token,
                'PayerID' => $token_detail[0]['payerid'],
                'PROFILESTARTDATE' => $token_detail[0]['timestamp'],
                'DESC' => 'Exemplo',
                'BILLINGPERIOD' => 'Month',
                'BILLINGFREQUENCY' => '1',
                'AMT' => $token_detail[0]['amt'],
                'CURRENCYCODE' => 'USD',
                'COUNTRYCODE' => 'US',
                'MAXFAILEDPAYMENTS' => 3
            );

            $ob = new Paypal();
            $response = $ob->paypal_request($request_data);
            $nvp = array();

            if (preg_match_all('/(?<name>[^\=]+)\=(?<value>[^&]+)&?/', $response, $matches)) {
                foreach ($matches['name'] as $offset => $name) {
                    $nvp[$name] = urldecode($matches['value'][$offset]);
                }
                $nvp['user_id'] = $token_detail[0]['user_id'];
                $package_name = $token_detail[0]['package_name'];
                $package_type = $token_detail[0]['package_type'];
            }

            Profile::create($nvp);
            
            return response()->json(array('status' => 'success', 'message' =>"You have $package_type"."ly subscription for a $package_name package "), 200);
        }
    }

    public function onetimePayment($token) {
        $token_detail = ExpressCheckoutDetails::where('token', $token)->get()->toArray();
        if (!empty($token_detail)) {
            $request_data = array(
                'USER' => 'owner_api1.zilculator.com',
                'PWD' => 'XSTG2TR8HPCXH4WS',
                'SIGNATURE' => 'ACXs.mrJPxIsVOYc5al1QwsPUKQFA43JN-IMMyV4re77a2W5vBKafMHE',
                'METHOD' => 'DoExpressCheckoutPayment',
                'VERSION' => '108',
                'LOCALECODE' => 'pt_US',
                'TOKEN' => $token_detail[0]['token'],
                'PayerID' => $token_detail[0]['payerid'],
                'PAYMENTREQUEST_0_CUSTOM'=>$token_detail[0]['user_id'],
                'PAYMENTREQUEST_0_AMT' => $token_detail[0]['amt'],
                'PAYMENTREQUEST_0_CURRENCYCODE' => 'USD',
                'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
                'PAYMENTREQUEST_0_ITEMAMT' => $token_detail[0]['amt'],
                'L_PAYMENTREQUEST_0_NAME0' => $token_detail[0]['package_name'],
                'L_PAYMENTREQUEST_0_DESC0' => $token_detail[0]['package_name'],
                'L_PAYMENTREQUEST_0_AMT0' => $token_detail[0]['amt'],
                'L_PAYMENTREQUEST_0_ITEMCATEGORY0' => 'Digital',
            );
            $ob = new Paypal();
            $response = $ob->paypal_request($request_data);

            $nvp = array();

            if (preg_match_all('/(?<name>[^\=]+)\=(?<value>[^&]+)&?/', $response, $matches)) {
                foreach ($matches['name'] as $offset => $name) {
                    $nvp[$name] = urldecode($matches['value'][$offset]);
                }
            }
            if (isset($nvp['PAYMENTINFO_0_ACK']) && $nvp['PAYMENTINFO_0_ACK'] == 'Success') {
                $this->createRecurringPaymentsProfile($nvp['TOKEN']);
            } 
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateRecurringPaymentsProfile() {
        
    }

}
