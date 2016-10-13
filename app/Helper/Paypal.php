<?php

namespace App\Helpers;

class Paypal {

    public function paypal_request($data) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_URL, 'https://api-3t.sandbox.paypal.com/nvp');
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        
         $response = curl_exec($curl);
         curl_close($curl);
         return $response;
    }

}