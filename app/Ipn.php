<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ipn extends Model
{
      protected $table = 'ipn';
       
      protected $fillable = ['first_name','last_name','recurring_payment_id','payer_email','payer_id','txn_id','payment_type','package_name','amount','settle_amount','mc_fee','exchange_rate','payment_fee','ipn_track_id','payment_status','payment_date','next_payment_date','user_id','payment_cycle','data','amount_per_cycle','settle_currency','currency_code','profile_status'];
}
