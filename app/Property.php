<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $table = 'property';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['uniqid','user_id','active','name', 'address', 'city','state','zip','country','type','num_units','dist_unit','building_size','num_rooms','num_beds','num_baths','num_kitchens','description','currency','price','closing_costs','down_payment','loan_costs','loan_amount','loan_interest','loan_payment','loan_years','loan_amount2','loan_interest2','loan_payment2','loan_years2','sale_price','vacancy_rate','discount_rate','tax_rate','cap_rate','appreciation_growth','rental_growth','expenses_growth','income_labels','income_amounts','rent','expense_labels','expense_amounts','holding_period','early_penalization','appraised_price','sale_price_method','sale_cost','year_built','interest_only','cr_loan_amount','cr_loan_interest','cr_loan_years','cr_balloon','cr_loan_interest2','cr_loan_years2','rehab','0rule','land_to_value','dep_years','main_img','img_path','seourl'];
    
    
       public function images()
    {
        return $this->hasMany('App\Image');
    }
}
