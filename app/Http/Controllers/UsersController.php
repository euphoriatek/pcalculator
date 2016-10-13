<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Http\Response;
use Dingo\Api\Routing\Helpers;
use Illuminate\Routing\Controller as BaseController;
use JWTAuth;
use App\User;
use Socialize;
use Validator;
use Dingo\Api\Exception\UpdateResourceFailedException as update_exception;
use Imageupload;
use File;
use App\Property;
use Mail;


class UsersController extends BaseController {

    use Helpers;

    public function __construct() {
//       $this->middleware('jwt.auth', ['except' => ['authenticate']]);
    }

    public function index() {
        $user = JWTAuth::parseToken()->authenticate();
        return response()->json(array("user" => $user), 200);
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
    public function update(Request $request) {

        $update_profile = json_decode(file_get_contents("php://input"), true);
        $user = JWTAuth::parseToken()->authenticate();
        $id = $user->id;
        $rules = array(
            'first_name' => 'max:40',
            'last_name' => 'max:40',
            'address' => 'max:40',
            'city' => 'max:40',
            'state' => 'max:10',
            'zip' => 'max:10',
            'country' => 'max:40',
            'email' => 'email|max:255',
            'user_name' => 'max:40',
            'active' => 'boolean',
            'user_level' => 'numeric'
        );

        if (isset($update_profile['email'])) {
            $check = User::where('id', '!=', $id)->where('email', '=', $update_profile['email'])->get()->count();

//        $error  = $validator->errors()->getMessages();
            if ($check > 0) {
                $rules['email'] = 'unique:users';
            }
        }
        if (isset($update_profile['user_name'])) {
            $check = User::where('id', '!=', $id)->where('user_name', '=', $update_profile['user_name'])->get()->count();
            $validator = Validator::make($update_profile, $rules);

            if ($check > 0) {
                $rules['user_name'] = 'unique:users';
            }
        }
        $update_profile['num_properties'] = Property::where('user_id', '=', $id)->get()->count();
        $update_profile['reg_ip'] = $request->ip();

        $validator = Validator::make($update_profile, $rules);
        if ($validator->fails()) {
            throw new update_exception('Could not update user profile.', $validator->errors());
        } else {
            $result = User::where(array('id' => $id))
                    ->update($update_profile);

            if ($result) {
                $user_detail = User::find($id);

                if (is_null($user_detail)) {
                    return $this->response->noContent();
                } else {
                    $user_data = $user_detail->toArray();
                    return response()->json(array("user" => $user_data), 200);
                }
            }
        }
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

    public function deactive() {
        $user = JWTAuth::parseToken()->authenticate();
        $userId = $user->id;
        $result = User::where(array('id' => $userId))
                ->update(array('active' => 0));

        if ($result) {
            $user_detail = User::find($userId);

            if (is_null($user_detail)) {
                return $this->response->noContent();
            } else {
                return response()->json(array('status' => 'success', 'message' => 'User deactive successfully!'), 200);
            }
        }
    }

    public function facebook_redirect() {
        return Socialize::with('facebook')->redirect();
    }

    // to get authenticate user data
    public function facebook() {

        $user = Socialize::with('facebook')->user();

        $data = array(
            'user_name' => $user->getName(),
            'email' => $user->getEmail(),
            'social_id' => $user->getId(),
            'last_login' => date("Y-m-d H:i:s")
        );

        $path = public_path();
        $db_path = 'uploads/images/' . time();
        copy($user->getAvatar(), $path . '//' . $db_path . '.jpeg');
        $data['profile_image'] = $db_path . '.jpeg';
        $user_detail = User::where('email', $user->getEmail())->get()->toArray();


        if (!count($user_detail)) {
            $user_data = User::create($data);
            $token = JWTAuth::fromUser($user_data);
            return response()->json(compact('token'));
        } else {
            $user = User::where('email', '=', $user->getEmail())->where('social_id', '=', $user->getId())->first();
            $token = JWTAuth::fromUser($user);
             $query = array(
                            'token' => $token
                            );
                            $redirectURL = sprintf('https://zilculator.com/facebook-login-success?%s', http_build_query($query));
             return response()->json(array('status' => 'success', 'url' =>$redirectURL), 200);
        }
    }

    public function gmail_redirect() {
        return Socialize::with('google')->redirect();
    }

    // to get authenticate user data
    public function gmail() {

        $user = Socialize::with('google')->user();
       
        $data = array(
            'user_name' => $user->getName(),
            'email' => $user->getEmail(),
            'social_id' => $user->getId(),
            'last_login' => date("Y-m-d H:i:s")
        );
        $path = public_path();
        $db_path = '/uploads/images/' . time();
        copy($user->getAvatar(), $path . $db_path . '.jpeg');

        $data['profile_image'] = $db_path . '.jpeg';
        $user_detail = User::where('email', $user->getEmail())->get()->toArray();



        if (!count($user_detail)) {
            $user_data = User::create($data);
            $token = JWTAuth::fromUser($user_data);
            return response()->json(compact('token'));
        } else {
            $user = User::where('email', '=', $user->getEmail())->where('social_id', '=', $user->getId())->first();
            $token = JWTAuth::fromUser($user);
            return response()->json(compact('token'));
        }
    }

    public function upload_image(Request $request) {

        $file = $request->file('image');

        // Build the input for validation
        $fileArray = array('image' => $file);
        $rules = array(
            'image' => 'mimes:jpeg,jpg,png,gif|required|max:10000' // max 10000kb
        );

        $validator = Validator::make($fileArray, $rules);

        if ($validator->fails()) {
              throw new update_exception('Could not upload user profile image.', $validator->errors());
//            return response()->json(['error' => $validator->errors()->getMessages()], 400);
        } else {


            $data['result'] = Imageupload::upload($request->file('image'));
            $image_path = $data['result']['original_filedir'];

            $user = JWTAuth::parseToken()->authenticate();
            $userId = $user->id;
            $user = User::find($userId)->toArray();
            $path = public_path() . '//' . $user['profile_image'];
            if (file_exists($path)) {
                FILE::delete($path);
            }
            $result = User::where(array('id' => $userId))
                    ->update(array('profile_image' => $image_path));
            if ($result) {
                $res = User::find($userId);
                if (is_null($res)) {
                    return $this->response->noContent();
                } else {
                    return response()->json(array('status' => 'success', 'message' => 'profile image upload successfully'), 200);
                }
            }
        }
    }

    public function forgetPassword(Request $request) {
        $update_password = json_decode(file_get_contents("php://input"), true);
        $rules = array(
            'email' => 'required|email|max:255|exists:users',
        );


        $validator = Validator::make($update_password, $rules);
        if ($validator->fails()) {
            throw new update_exception('Could not update password.', $validator->errors());
        } else {

            $result = User::where(array('email' => $update_password['email']))
                            ->get()->toArray();


            if (!empty($result)) {
                $data = array();
                $data['password'] = str_random(10);
                $data['username'] = $result[0]['first_name'];
                $credentials['password'] = bcrypt($data['password']);
                $email_id = $result[0]['email'];
                $status = Mail::send('emails.forget_password', $data, function($message) use($email_id) {
                                    $message->to($email_id)->subject('Password Reset Request');
                                    $message->from('support@zilculator.com', 'Zilculator');
                                });

                if (!$status) {
                    return $this->response->noContent();
                } else {
                    User::where(array('email' => $result[0]['email']))
                            ->update($credentials);
                    return response()->json(array('status' => 'success', 'message' => 'Password change successfully please check your email!'), 200);
                }
            }
        }
    }

}
