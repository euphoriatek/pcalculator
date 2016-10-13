<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
  protected $table = 'user_profile';
      
         protected $fillable = ['user_id','PROFILEID','PROFILESTATUS','TIMESTAMP','CORRELATIONID','ACK'];
}
