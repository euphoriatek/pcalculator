<?php

use App\User;

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It's a breeze. Simply tell Laravel the URIs it should respond to
  | and give it the controller to call when that URI is requested.
  |
 */

Route::get('dashboard', function () {
    return redirect('home/dashboard');
});
Route::get('/', function () {
            return view('welcome');
        });


$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {

            $api->group(['middleware' => 'cors'], function ($api) {

                        $api->any('login', 'App\Http\Controllers\AuthenticateController@authenticate');
                        $api->any('facebook/login', 'App\Http\Controllers\UsersController@facebook_redirect');
                        $api->any('google/login', 'App\Http\Controllers\UsersController@gmail_redirect');
                        $api->get('user/account', 'App\Http\Controllers\UsersController@facebook');
                        $api->get('user/gmail_account', 'App\Http\Controllers\UsersController@gmail');
                        $api->any('logout', 'App\Http\Controllers\AuthenticateController@logout');
                        $api->any('subscriptions', 'App\Http\Controllers\SubscriptionsController@index');
                        $api->any('get_detail', 'App\Http\Controllers\ScheduleController@getExpressCheckoutDetails');
                        $api->any('get_ipn', 'App\Http\Controllers\PaypalController@getipn');
                        $api->post('change_password', 'App\Http\Controllers\UsersController@forgetPassword');
                        $api->post('ipn', 'App\Http\Controllers\PaypalController@getipn');
                       $api->get('properties/{id}', 'App\Http\Controllers\PropertyController@show');
                        $api->group([ 'middleware' => 'jwt.auth'], function ($api) {

                                    $api->get('user', 'App\Http\Controllers\UsersController@index');
                                    $api->post('user', 'App\Http\Controllers\UsersController@store');
                                    $api->put('user', 'App\Http\Controllers\UsersController@update');
                                    $api->delete('user', 'App\Http\Controllers\UsersController@deactive');
                                    $api->post('upload_image', 'App\Http\Controllers\UsersController@upload_image');

                                    $api->get('properties', 'App\Http\Controllers\PropertyController@index');
                                    $api->post('properties', 'App\Http\Controllers\PropertyController@store');
                                 
                                    $api->put('properties/{id}', 'App\Http\Controllers\PropertyController@update');
                                    $api->delete('properties/{id}', 'App\Http\Controllers\PropertyController@destroy');
                                    $api->any('payment', 'App\Http\Controllers\PaypalController@setExpressCheckout');
                      $api->post('upload_property_image', 'App\Http\Controllers\PropertyController@upload_property_image');
                                    });

                        $api->post('/registration', function () {


                                    $credentials = json_decode(file_get_contents("php://input"), true);

                                    $credentials['password'] = Hash::make($credentials['password']);
                                    $credentials['last_login'] = date("Y-m-d H:i:s");

                                    $rules = array(
                                        'first_name' => 'required|max:255',
                                        'last_name' => 'required|max:255',
                                        'email' => 'required|email|max:255|unique:users',
                                        'password' => 'required',
                                    );

                                    $validator = app('validator')->make($credentials, $rules);
                                    if ($validator->fails()) {
                                        throw new Dingo\Api\Exception\StoreResourceFailedException('Could not create new user.', $validator->errors());
                                    } else {
                                        $user = User::create($credentials);
                                        $token = JWTAuth::fromUser($user);
                                        return Response::json(compact('token'));
                                    }
                                });
                    });
        });

