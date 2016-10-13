<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExpressCheckoutDetails extends Model
{
      protected $table = 'expresscheckoutdetails';
      
         protected $fillable = ['user_id','token','email','ack','payerid','payerstatus','firstname','lastname','amt','taxamt','timestamp','package_type','package_name','package_id'];
      
}
