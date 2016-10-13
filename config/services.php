<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, Mandrill, and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
       'domain' => 'sandbox8269bbe531e3415c9af56e03e3b997fc.mailgun.org',
       'secret' => 'key-039a0697e37a810ea6452281bd06ccfd',
    ],

    'mandrill' => [
        'secret' => env('MANDRILL_SECRET'),
    ],

    'ses' => [
        'key'    => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'stripe' => [
        'model'  => App\User::class,
        'key'    => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
    
     'facebook' => [
    'client_id' => '877307142376128',
    'client_secret' => 'ce4e4522cb7ad6c6cf7f44e99dfbd1aa',
    'redirect' => 'http://api.zilculator.com/v1/user/account',
],
    'google' => [
    'client_id' => '204253901205-e3minpad5ouoejis29cm2ipoo9hlrj2e.apps.googleusercontent.com',
    'client_secret' =>'196KZXBuxzrk-iQkxV_lEzOL',
    'redirect' => 'http://api.zilculator.com/v1/user/gmail_account',
],

];
