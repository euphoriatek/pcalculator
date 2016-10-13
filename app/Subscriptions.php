<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subscriptions extends Model
{
       protected $table = 'subscriptions';
       
      protected $fillable = ['subscriptions_name','subscriptions_name','number_of_users','online_property_report','downloadable_pdf_report','comparison_of_properties','social_sharing','customizable_reports','support_commercial_and_ multifamily_properties','num_property_photos','property_fliers','company_listings','craigslist_templates','realtor_dashboard','support_email','price_monthly','price_yearly'];
}
