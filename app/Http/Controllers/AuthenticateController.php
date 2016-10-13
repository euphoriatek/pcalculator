<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Dingo\Api\Exception\StoreResourceFailedException as store_exception;
use App\User;

class AuthenticateController extends Controller {

    public function index() {
        // TODO: show users
    }

    public function authenticate(Request $request) {
//        $credentials = $request->only('email', 'password');
        $credentials = json_decode(file_get_contents("php://input"), true);

        $rules = array(
            'login' => 'required',
            'password' => 'required'
        );
        $messages = ['required' => 'this field is required'];
        $validator = app('validator')->make($credentials, $rules, $messages);
        if ($validator->fails()) {
            throw new store_exception('Could not login.', $validator->errors());
        }
        try {

            if ($token = JWTAuth::attempt(['email' => $credentials['login'], 'password' => $credentials['password'], 'active' => 1])) {
                User::where('email', '=', $credentials['login'])->update(array('last_login' => date("Y-m-d H:i:s")));
                return response()->json(compact('token'));
            } elseif ($token = JWTAuth::attempt(['user_name' => $credentials['login'], 'password' => $credentials['password'], 'active' => 1])) {
                User::where('user_name', '=', $credentials['login'])->update(array('last_login' => date("Y-m-d H:i:s")));
                return response()->json(compact('token'));
            } else {
                return response()->json(['error' => 'invalid_credentials or account is deactive'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        // if no errors are encountered we can return a JWT
    }

    public function logout() {
        $token = JWTAuth::getToken();
        JWTAuth::invalidate($token);
        return response()->json(array('status' => 'success', 'message' => 'User logout successfully!'), 200);
    }

}
