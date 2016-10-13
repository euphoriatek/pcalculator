<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $table='image';
     protected $fillable = ['user_id','property_id','property_image'];
     
   
}
