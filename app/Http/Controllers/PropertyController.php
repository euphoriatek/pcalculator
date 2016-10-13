<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Http\Response;
use Dingo\Api\Routing\Helpers;
use Illuminate\Routing\Controller as BaseController;
use JWTAuth;
use App\User;
use App\Property;
use App\Image;
use Validator;
use Dingo\Api\Exception\StoreResourceFailedException as store_exception;
use Dingo\Api\Exception\UpdateResourceFailedException as update_exception;
use Calculation;
use App\Subscriptions;
use Imageupload;
use File;

class PropertyController extends BaseController {

    use Helpers;

    public function __construct() {

//        $this->middleware('jwt.auth', ['except' => ['authenticate']]);
    }

    public function index() {

        $user = JWTAuth::parseToken()->authenticate();
        $userId = $user->id;

        $property_detail = Property::with('images')->where(array('user_id' => $userId, 'active' => 1))->get();

        if ($property_detail->isEmpty()) {
            return $this->response->noContent();
        } else {

            $all_detail = $property_detail->toArray();

            foreach ($all_detail as $val) {
                $ob = new Calculation($val);
                $new_property_data[] = $ob->getallfield();
            }
            return response()->json(array("property" => $new_property_data), 200);
        }
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

        if ($request->isMethod('post')) {
            $rules = array(
                'name' => 'required|max:40',
                'address' => 'required|max:40',
                'city' => 'required|max:40',
                'state' => 'required|max:10',
                'zip' => 'max:10',
                'country' => 'required|max:40',
                'type' => 'required',
                'num_units' => 'numeric',
                'year' => 'integer|between:1800,2015',
                'building_size' => 'numeric',
                'num_rooms' => 'numeric',
                'num_beds' => 'numeric',
                'num_baths' => 'numeric',
                'num_kitchens' => 'numeric',
                'description' => 'max:300',
                'price' => 'required|numeric',
                'closing_costs' => 'required|numeric',
                'down_payment' => 'required|numeric',
                'loan_costs' => 'numeric',
                'loan_amount' => 'numeric',
                'loan_interest' => 'numeric|max:10|between:0,60',
                'loan_payment' => 'numeric',
                'loan_years' => 'integer|between:0,50',
                'loan_amount2' => 'numeric|max:10',
                'loan_interest2' => 'numeric|max:10|between:0,60',
                'loan_payment2' => 'numeric|max:10',
                'loan_years2' => 'integer|between:0,50',
                'sale_price' => 'numeric',
                'vacancy_rate' => 'numeric|between:0,100',
                'discount_rate' => 'numeric|between:0,100',
                'tax_rate' => 'numeric|between:0,100',
                'flat_rate_exp' => 'numeric|between:0,100',
                'cap_rate' => 'numeric|between:0,100',
                'appreciation_growth' => 'required|numeric|between:0,100',
                'rental_growth' => 'required|numeric|between:0,100',
                'expenses_growth' => 'required|numeric|between:0,100',
                'rent' => 'required|numeric',
                'holding_period' => 'numeric|between:0,100',
                'early_penalization' => 'numeric',
                'appraised_price' => 'numeric',
                'sale_price_method' => 'required',
                'sale_cost' => 'numeric|max:10',
            );


            $property = json_decode(file_get_contents("php://input"), true);

            if ($property != null) {

                $ct = 0;
                if (isset($property['ct'])) {
                    $ct = 1;
                }
                $second_loan = 0;
                if (isset($property['second_loan'])) {
                    $second_loan = 1;
                }

                if ($ct == 1) {
                    $rules['cr_loan_amount'] = 'required|numeric';
                    $rules['cr_loan_interest'] = 'required|max:50';
                    $rules['cr_loan_years'] = 'required|max:10';
                    $rules['cr_balloon'] = 'required|max:10';
                    $rules['second_loan'] = 'required|max:10';
                }
                if ($second_loan == 1) {
                    $rules['cr_loan_interest2'] = 'required|max:50';
                    $rules['cr_loan_years2'] = 'required|max:10';
                }
                $validator = Validator::make($property, $rules);
                if ($validator->fails()) {
                    throw new store_exception('Could not create new property.', $validator->errors());
                } else {
                    $user = JWTAuth::parseToken()->authenticate();
                    $userId = $user->id;
                    $userLevel = $user->user_level;
                    $user_subscribe = Subscriptions::where('subscription_id', $userLevel)->get()->toArray();
                    $limit = $user_subscribe[0]['number_of_properties'];
                    $num_property = Property::where('user_id', '=', $userId)->get()->count();


                    if ($num_property < $limit) {
                        $property['user_id'] = $userId;
                        $property['uniqid'] = str_random(20);
                        $property['main_img'] = 'sds324dsf32sdfsdf3dsfsd3rdsf3rds';
                        $property['img_path'] = 'default.jpg';
                        $property['seourl'] = 'default-5655-E-Sahara-Av-Las-Vegas';
                        $property_data = Property::create($property);
                        return response()->json(array("property" => $property_data), 201);
                    } else {
                        throw new store_exception('Could not create new property.', array('limit' => 'Account limit is over'));
                    }
                }
            } else {
                return $this->response->errorBadRequest();
            }
        } else {
            return $this->response->errorBadRequest();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
      
          $property_detail =   Property::with('images')->where('id', '=', $id)
                                    ->orWhere('uniqid', $id)
                                    ->get();
       
//        $property_detail = Property::with('images')->find($id);
       
        if (is_null($property_detail)) {
            return $this->response->noContent();
        } else {
            $property_data = $property_detail->toArray();
            $ob = new Calculation($property_data[0]);
            $new_property_data = $ob->getallfield();
            return response()->json(array("property" => $new_property_data), 200);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
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
        $rules = array(
            'name' => 'max:40',
            'address' => 'max:40',
            'city' => 'max:40',
            'state' => 'max:10',
            'zip' => 'max:10',
            'country' => 'max:40',
            'num_units' => 'numeric',
            'year' => 'integer|between:1800,2015',
            'dist_unit' => 'numeric',
            'building_size' => 'numeric',
            'num_rooms' => 'numeric',
            'num_beds' => 'numeric',
            'num_baths' => 'numeric',
            'num_kitchens' => 'numeric',
            'description' => 'max:300',
            'price' => 'numeric',
            'closing_costs' => 'numeric',
            'down_payment' => 'numeric',
            'loan_costs' => 'numeric',
            'loan_amount' => 'numeric',
            'loan_interest' => 'numeric|max:10|between:0,60',
            'loan_payment' => 'numeric|max:10',
            'loan_years' => 'integer|between:0,50',
            'loan_amount2' => 'numeric|max:10',
            'loan_interest2' => 'numeric|max:10|between:0,60',
            'loan_payment2' => 'numeric|max:10',
            'loan_years2' => 'integer|between:0,50',
            'sale_price' => 'numeric|max:10',
            'vacancy_rate' => 'numeric|between:0,100',
            'discount_rate' => 'numeric|between:0,100',
            'tax_rate' => 'numeric|between:0,100',
            'cap_rate' => 'numeric|between:0,100',
            'appreciation_growth' => 'numeric|between:0,100',
            'rental_growth' => 'numeric|between:0,100',
            'expenses_growth' => 'numeric|between:0,100',
            'income_labels' => 'max:10',
            'income_amounts' => 'numeric',
            'rent' => 'numeric',
            'expense_labels' => 'max:10',
            'expense_amounts' => 'numeric|max:10',
            'holding_period' => 'numeric|between:0,100',
            'early_penalization' => 'numeric',
            'appraised_price' => 'numeric|max:10',
            'sale_cost' => 'numeric|max:10',
            'land_to_value' => 'numeric|max:100',
        );

        $update_property = json_decode(file_get_contents("php://input"), true);

        if ($update_property != NULL) {
            $validator = Validator::make($update_property, $rules);
            if ($validator->fails()) {
                throw new update_exception('Could not update property.', $validator->errors());
            } else {
                $user = JWTAuth::parseToken()->authenticate();
                $userId = $user->id;
                $result = Property::where(array('id' => $id, 'active' => 1, 'user_id' => $userId))
                        ->update($update_property);


                if ($result) {
                    $property_detail = Property::find($id);

                    if (is_null($property_detail)) {
                        return $this->response->noContent();
                    } else {
                        $property_data = $property_detail->toArray();
                        $ob = new Calculation($property_data);
                        $new_property_data = $ob->getallfield();
                        return response()->json(array("property" => $new_property_data), 200);
                    }
                }
            }
        } else {
            return $this->response->errorBadRequest();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {

        $user = JWTAuth::parseToken()->authenticate();
        $userId = $user->id;

        $property_detail = Property::where(array('user_id' => $userId, 'id' => $id))->get();


        if ($property_detail->isEmpty()) {
            return $this->response->noContent();
        } else {
            $property_detail = Property::destroy($id);
            return response()->json(array(
                        'success' => TRUE,
                        'message' => 'Property delete successfully'), 200
            );
            return response()->json(array('status' => 'success', 'message' => 'Property deleted successfully!'), 200);
        }
    }
    
    
     public function upload_property_image(Request $request) {

        $file = $request->file('image');
     
        // Build the input for validation
        $fileArray = array('image' => $file,'property_id'=>$request->input('property_id'));
       
        $rules = array(
            'image' => 'mimes:jpeg,jpg,png,gif|required|max:10000', // max 10000kb
            'property_id'=>'required'
        );

        $validator = Validator::make($fileArray, $rules);

        if ($validator->fails()) {
          throw new update_exception('Could not upload property image.', $validator->errors());
        } else {

          
            $data['result'] = Imageupload::upload($request->file('image'));
            $image_path = $data['result']['original_filedir'];
            
            $user = JWTAuth::parseToken()->authenticate();
            $userId = $user->id;
            
            $userLevel = $user->user_level;
            $user_subscribe = Subscriptions::where('subscription_id', $userLevel)->get()->toArray();
            $limit = $user_subscribe[0]['num_property_photos'];
            $num_image = Image::where('user_id', '=', $userId)->where('property_id', '=', $fileArray['property_id'])->get()->count();
            if ($num_image < $limit) {
            $user = User::find($userId)->toArray();
            $path = public_path() . '//' . $user['profile_image'];
            if (file_exists($path)) {
                FILE::delete($path);
            }
            
            $image_data=array(
              'user_id'=>$userId,
              'property_id'=>$fileArray['property_id'],
              'property_image'=>$image_path 
            );
            
            
            $result = Image::create($image_data);
            
           
            if ($result) {
                if (is_null($result)) {
                    return $this->response->noContent();
                } else {
                    return response()->json(array('status' => 'success', 'message' => 'property image upload successfully'), 200);
                }
            }
            }
            else{
               throw new store_exception('Could not upload new property image.', array('limit' => 'Account limit is over'));  
            }
        }
    }

    public function delete_image() {
        $property_data = array();
        $ob = new Calculation($property_data);
        $ob->deleteImage();
    }

}
