<?php
namespace App\Helpers;
class Cal{
	
	public $user_id;
	
	public $id;
	
	public $uniqid;
	/**
   	* array of table column values (one row)
   	* @var array
   	*/	
	protected $database_fields;
	/**
   	* array of fields with values true /false -modified or not
   	* @var array
   	*/	
	protected $modified_fields;
	/**
   	* 1 or 0 flag if loaded
   	* @var integer
   	*/	
	protected $loaded;
	/**
   	* 1 or 0 flag if active (not deleted)
   	* @var integer
   	*/	
	protected $active;
	/**
   	* Array of all 30 year forecast data
   	* @var array
   	*/	
	public $forecast;
	/**
   	* Array of sensitivity analysis of LTV (NPV and IRR)
   	* @var array
   	*/	
	public $senLTV;
	/**
   	* Array of sensitivity analysis of mortgage period (NPV and IRR)
   	* @var array
   	*/	
	public $senPer;
	/**
   	* Array of NPV of alternative investments
   	* @var array
   	*/	
	public $alternatives;
	/**
   	* Array of 4 various resale price estimations
   	* @var array
   	*/	
	public $resale;
	
	
	public function __construct($property_data) {
             foreach ($property_data as $key => $value) {
                               $this->database_fields[$key] = $value;
                            }
  	}
  	
  	public function getallfield(){
    $this->calculateAll();
    return $this->database_fields ;
}
  	
  	
	/**
	 * loads information from database
	 * 
	 */
 
  	/**
	 * Returns the value of the database table entry for column $field
	 * @param $field
	 * @return value of property
	 */
  	public function getField($field){
          return($this->database_fields[$field]);
	  	
  	}
  	/**
		 * Returns the value of the database table entry for column $field
		 * @return array of values
		 */

	/**
		 * Sets the value of the database table entrz for column $field to $value
		 * @param $field
		 * @param $value
		 */
	public function setField($field, $value){
	  	$this->database_fields[$field] = $value;
	   }
	
	
	/**
	 * Save the property, return succes
	 */
  
	  
	
	//---------------------- calculations ------------------
	
	/**
	 * calculate all ratios
	 * @return success
	 */
	public function calculateAll(){
      
		$loan_total = $this->getField('loan_amount')+$this->getField('loan_amount2');
		$this->setField('GSI',$this->calGSI());
		$this->setField('LTV',$this->calLTV());
		$this->setField('vacancy_loss',$this->calVacancyLoss());
		$this->setField('total_income',$this->calTotalIncome());
		$this->setField('GOI',$this->calGOI());		
		$this->setField('total_expense',$this->calTotalExpense());
		$this->setField('NOI',$this->calNOI());
		$loan_payment=$this->calLoanPayment();
		$this->setField('loan_payment',($loan_payment>0)?$loan_payment:$this->calCrLoanPayment());
		$this->setField('loan_payment2',$this->calLoanPayment(2));
		$this->setField('an_debt', $this->calAnDebt());
		$this->setField('CFBT',$this->calCFBT());
		$this->setField('op_expense_r',$this->calOpExpenseR());
		$this->setField('BER',$this->calBER());
		$this->setField('depreciation',$this->calDepAllow());
		$this->setField('GRM',$this->calGRM());
		$this->setField('prop_cap_rate',$this->calPropCapRate());
		
		$this->forecast = $this->forecast();
		$this->resale = $this->calResalePrice();
		
		$this->setField('sale_price',$this->resale[$this->getField('sale_price_method')]);
		$this->setField('sale_proceeds',$this->calSaleProceeds());

		$this->setField('CFAT',$this->calCFAT());
		$this->setField('NPV',$this->calNPV());
		$this->setField('IRR',$this->calIRR());
		$this->setField('profit_index',$this->calProfitIndex());
		$this->setField('PE',$this->calPE());
		$this->setField('CoC',$this->calCoC());
		
		
		$this->setField('ROE',$this->calROE());
		$this->setField('GRY',$this->calGRY());
		$this->setField('DCR',$this->calDCR());
		$this->setField('ROI',$this->calROI());
		
		$this->forecast['NPVonHP'] = $this->calNPVonHP();
		$this->forecast['ROEonHP'] = $this->calROEonHP();
		 
		$this->senLTV = $this->senLTV();
		$this->senPer = $this->senPer();
		
		$this->alternatives = $this->alternatives();
		
		
		
		
		return true;
		
	}
	
	/**
	 * calculate Loan To Value ratio
	 * @return unknown_type
	 */
	private function calLTV(){
		$loan_total = $this->getField('loan_amount')+$this->getField('loan_amount2')+$this->getField('cr_loan_amount');
		$value = $this->getField('price');
		return $loan_total/$value*100;
		
	}
	
	/**
	 * calculate Gross Scheduled Icnome
	 * @return integer
	 */
	private function calGSI(){
		$total = $this->getField('rent');
		return $total;
	}
	/**
	 * calculate Total Icnome
	 * @return integer
	 */
	private function calTotalIncome(){
		$total = 0;
		$arr = explode(";", $this->getField('income_amounts'));
		foreach($arr as $amount){
			$total+= $amount;
		}
		$total+= $this->getField('rent');
		return $total;
	}
	/**
	 * calculate Taxable Icnome
	 * @return integer
	 * @TODO implement this function
	 */
	private function calTaxableIncome(){
		$total=0;
		return $total;
	}
	
	/**
	 * calculate Vacancy Loss
	 * @return integer
	 */
	private function calVacancyLoss(){
		$total = $this->getField('GSI')*($this->getField('vacancy_rate')/100);
		return $total;
	}
	
	/**
	 * calculate GOI
	 * @return integer
	 */
	private function calGOI(){
		$total = $this->getField('total_income')-$this->getField('vacancy_loss');
		return $total;
	}
	
	/**
	 * calculate Total Expenses
	 * @return integer
	 */
	private function calTotalExpense(){
		$total = 0;
		if($this->getField('50rule')){
			$total = $this->getField('GSI')/2;
		}
		else{
			$arr = explode(";", $this->getField('expense_amounts'));
			foreach($arr as $amount){
				$total+= $amount;
			}
		}
		return $total;
	}
	
	/**
	 * calculate NOI
	 * @return integer
	 */
	private function calNOI(){
		$total = $this->getField('GOI')-$this->getField('total_expense');
		return $total;
	}
	
	/**
	 * calculate Creative Loan Payment
	 * @return integer
	 */
	private function calCrLoanPayment($loan_id=''){
		if(!$this->getField('cr_loan_years'.$loan_id)) return 0;
		$num_years = $this->getField('cr_loan_years'.$loan_id);
		$interest = $this->getField('cr_loan_interest'.$loan_id)/100;
		$amount = $this->getField('cr_loan_amount'.$loan_id);
		if($this->getField('interest_only')){
			return ($amount*$interest/12); // save as loan payment
		}
		$tmp = pow((1+$interest/12),$num_years*12);
		$pay = $amount*($interest/12*$tmp)/($tmp-1);
		return $pay;
	}
	/**
	 * calculate Loan Payment
	 * @return integer
	 */
	private function calLoanPayment($loan_id=''){
		if(!$this->getField('loan_years'.$loan_id)) return 0;
		$num_years = $this->getField('loan_years'.$loan_id);
		$interest = $this->getField('loan_interest'.$loan_id)/100;
		$amount = $this->getField('loan_amount'.$loan_id);

		$tmp = pow((1+$interest/12),$num_years*12);
		$pay = $amount*($interest/12*$tmp)/($tmp-1);
		return $pay;
	}
	/**
	 * calculate Annual Debt Service
	 * @return integer
	 */
	private function calAnDebt(){

		return ($this->getField('loan_payment')*12+$this->getField('loan_payment2')*12);
	}
	
	/**
	 * calculate CFBT
	 * @return integer
	 */
	private function calCFBT(){
		$total = $this->getField('NOI')-$this->getField('loan_payment')*12-$this->getField('loan_payment2')*12;
		return $total;
	}
	/**
	 * calculate CFAT
	 * @return integer
	 * @TODO implement this function
	 */
	private function calCFAT(){
		return $this->forecast['CFAT'][1];
	}
	/**
	 * calculate Operating Expense Ratio
	 * @return integer
	 */
	private function calOpExpenseR(){
		$total = $this->getField('total_expense')/$this->getField('GOI')*100;
		return $total;
	}
	/**
	 * calculate Break Even Ratio
	 * @return integer
	 */
	private function calBER(){
		$total = ($this->getField('loan_amount')*12+$this->getField('loan_amount2')*12+$this->getField('total_expense'))/$this->getField('GOI');
		return $total;
	}
	
	/** 
	 * calculate Depraciation Allowance
	 * @return integer
	 */
	private function calDepAllow(){
		$land_to_value = $this->getField('land_to_value');
		$dep_years = $this->getField('dep_years');
		$depr_basis = $this->getField('price')*(1-((isset($land_to_value)?$land_to_value:20)/100));
		$total = $depr_basis/$dep_years;
		return $total;
	}
	
	/**
	 * Preapare forecast data
	 * @return integer
	 */
	private function forecast(){
		
		$forecast = array();
		
		$income = array();
		$expense = array();
		$loan_payment = array();
		$loan_payment_i = array();
		$loan_payment_p = array();
		$loan_balance = array();
		$loan_payment2 = array();
		$loan_payment_i2 = array();
		$loan_payment_p2 = array();
		$loan_balance2 = array();
		$GOI = array();
		$NOI = array();
		$CFBT = array();
		$tax = array();
		$CFAT = array();
		$CFAT_no_i = array();
		$CFPV = array();
		$appreciated_value = array();
		$capital_additions = array();
		$vacancy_loss = array();
		$balloon_payment = 0;
		$balloon_year = ($this->getField('cr_balloon')>0 && $this->getField('cr_balloon')<$this->getField('cr_loan_years'))?$this->getField('cr_balloon'):$this->getField('cr_loan_years');
		//capital additions / rehab - for now only in the first year
		$capital_additions[1] = $this->getField('rehab');
		$appraised_price1 = $this->getField('appraised_price');
		
		
		if($this->getField('loan_amount')){
			
			$loan_balance[0] = $this->getField('loan_amount');
			$loan_pay = $this->getField('loan_payment');
			$loan_interest_m = ($this->getField('loan_interest')/100)/12;
			$loan_years = $this->getField('loan_years');
			$tmp_bal = array();
			$tmp_bal[0] = $loan_balance[0];
			$tmp_cum_i = 0;
			
			for($i=1;$i<=$loan_years*12;$i++){ // calculating annual principal payment from the monthly mortgage payments
				$tmp_i = $tmp_bal[$i-1]*$loan_interest_m;
				$tmp_bal[$i] = $tmp_bal[$i-1] - $loan_pay + $tmp_i;
				$tmp_cum_i+=$tmp_i;
				if($i%12==0){
					$loan_payment_i[$i/12] = $tmp_cum_i;
					$tmp_cum_i = 0;
					//echo '<br>Year: '.$i.' | payments: '.$loan_payment_i[$i/12];
				}
			}
			
			for($i=1;$i<=$loan_years;$i++){
				$loan_payment[$i] = $loan_pay*12;
				$loan_payment_p[$i] = $loan_payment[$i]-$loan_payment_i[$i];
				$loan_balance[$i] = $loan_balance[$i-1]-$loan_payment_p[$i];
				//echo '<br>Year: '.$i.' | payments: '.$loan_payment[$i].' | balance: '.$loan_balance[$i].' | principal: '.$loan_payment_p[$i].' | interest: '.$loan_payment_i[$i];
			}
			
			if($this->getField('loan_amount2')){
				
				$loan_balance2[0] = $this->getField('loan_amount2');
				$loan_pay2 = $this->getField('loan_payment2');
				$loan_interest_m2 = ($this->getField('loan_interest2')/100)/12;
				$loan_years2 = $this->getField('loan_years2');
				$tmp_bal = array();
				$tmp_bal[0] = $loan_balance2[0];
				$tmp_cum_i = 0;
				
				for($i=1;$i<=$loan_years2*12;$i++){ // calculating annual principal payment from the monthly mortgage payments
					$tmp_i = $tmp_bal[$i-1]*$loan_interest_m2;
					$tmp_bal[$i] = $tmp_bal[$i-1] - $loan_pay2 + $tmp_i;
					$tmp_cum_i+=$tmp_i;
					if($i%12==0){
						$loan_payment_i2[$i/12] = $tmp_cum_i;
						$tmp_cum_i = 0;
					}
				}
				for($i=1;$i<=$loan_years2;$i++){
					$loan_payment2[$i] = $loan_pay2*12;
					$loan_payment_p2[$i] = $loan_payment2[$i]-$loan_payment_i2[$i];
					$loan_balance2[$i] = $loan_balance2[$i-1]-$loan_payment_p2[$i];
					//echo '<br>Year: '.$i.' | payments: '.$loan_payment[$i].' | balance: '.$loan_balance[$i].' | principal: '.$loan_payment_p[$i].' | interest: '.$loan_payment_i[$i];
				}
			}
		}
		elseif ($this->getField('cr_loan_amount')){
			if($this->getField('interest_only')){
				
				//$balloon_year = ($this->getField('cr_balloon')<$this->getField('cr_loan_years'))?$this->getField('cr_balloon'):$this->getField('cr_loan_years');
				$loan_balance[0] = $this->getField('cr_loan_amount');
				$loan_interest = ($this->getField('cr_loan_interest')/100);
				
				//$loan_years = $this->getField('cr_loan_years');
				
				
				for($i=1;$i<=$balloon_year;$i++){
					$loan_payment[$i] = $loan_payment_i[$i] = $loan_balance[0]*$loan_interest;
					
					$loan_payment_p[$i] =0;
					$loan_balance[$i] = $loan_balance[$i-1]-$loan_payment_p[$i];
					//echo '<br>Year: '.$i.' | payments: '.$loan_payment[$i].' | balance: '.$loan_balance[$i].' | principal: '.$loan_payment_p[$i].' | interest: '.$loan_payment_i[$i];
				}
				
				$balloon_payment = $loan_balance[0];
				//echo '<br> Balloon: '.$balloon_payment;
			}
			else{ 
				$loan_balance[0] = $this->getField('cr_loan_amount');
				$loan_pay = $this->getField('loan_payment');
				//$this->setField('loan_payment',$loan_pay); // save as loan payment
		
				$loan_interest_m = ($this->getField('cr_loan_interest')/100)/12;
				$loan_years = $this->getField('cr_loan_years');
				
				$tmp_bal = array();
				$tmp_bal[0] = $loan_balance[0];
				$tmp_cum_i = 0;
				
				for($i=1;$i<=$balloon_year*12;$i++){ // calculating annual principal payment from the monthly mortgage payments
					$tmp_i = $tmp_bal[$i-1]*$loan_interest_m;
					$tmp_bal[$i] = $tmp_bal[$i-1] - $loan_pay + $tmp_i;
					$tmp_cum_i+=$tmp_i;
					if($i%12==0){
						$loan_payment_i[$i/12] = $tmp_cum_i;
						$tmp_cum_i = 0;
						
					}
				}
				$principle_paid = 0;
				for($i=1;$i<=$balloon_year;$i++){
					$loan_payment[$i] = $loan_pay*12;
					$loan_payment_p[$i] = $loan_payment[$i]-$loan_payment_i[$i];
					$principle_paid += $loan_payment_p[$i];
					$loan_balance[$i] = $loan_balance[$i-1]-$loan_payment_p[$i];
					//echo '<br>Year: '.$i.' | payments: '.$loan_payment[$i].' | balance: '.$loan_balance[$i].' | principal: '.$loan_payment_p[$i].' | interest: '.$loan_payment_i[$i];
				}
				$balloon_payment = $loan_balance[0] - $principle_paid;
				//echo '<br> Balloon: '.$balloon_payment;
			}
			if($this->getField('cr_loan_years2')){ // refinancing after 1st mortgage
				$this->setField('cr_loan_amount2',$balloon_payment);
				$loan_balance2[$balloon_year] = $this->getField('cr_loan_amount2');
				$loan_pay2 = $this->calCrLoanPayment(2);
				$this->setField('loan_payment2',$loan_pay2); // save as loan payment2
				$loan_interest_m2 = ($this->getField('cr_loan_interest2')/100)/12;
				$loan_years2 = $this->getField('cr_loan_years2');
				
				$tmp_bal = array();
				$tmp_bal[0] = $loan_balance2[$balloon_year];
				$tmp_cum_i = 0;
				
				for($i=1;$i<=($loan_years2)*12;$i++){ // calculating annual principal payment from the monthly mortgage payments
					$tmp_i = $tmp_bal[$i-1]*$loan_interest_m2;
					$tmp_bal[$i] = $tmp_bal[$i-1] - $loan_pay2 + $tmp_i;
					$tmp_cum_i+=$tmp_i;
					if($i<13){
						//echo '<br>Month: '.$i.' | payments interest: '.$tmp_i;
					}
					if($i%12==0){
						$loan_payment_i2[$i/12+$balloon_year] = $tmp_cum_i;
						$tmp_cum_i = 0;
					}
				}
				for($i=$balloon_year+1;$i<=($loan_years2+$balloon_year);$i++){
					$loan_payment2[$i] = $loan_pay2*12;
					$loan_payment_p2[$i] = $loan_payment2[$i]-$loan_payment_i2[$i];
					$loan_balance2[$i] = $loan_balance2[$i-1]-$loan_payment_p2[$i];
					//echo '<br>Year: '.$i.' | payments: '.$loan_payment2[$i].' | balance: '.$loan_balance2[$i].' | principal: '.$loan_payment_p2[$i].' | interest: '.$loan_payment_i2[$i];
				}
				$balloon_payment = 0;
			
			}
			
		}
		
		
		$income[1] = $this->getField('GSI');
		$misc_inc = $this->getField('total_income')-$this->getField('GSI');
		$rental_growth = $this->getField('rental_growth')/100;
		$expense[1] = $this->getField('total_expense');
		$expense_growth = $this->getField('expenses_growth')/100;
		$vacancy_loss[1] = $this->getField('vacancy_loss');
		$vacancy_rate = $this->getField('vacancy_rate')/100;
		for($i=2;$i<=30;$i++){ //income and expenses are calculated in the first year and then only increasing by specific rate
			$income[$i] = $income[$i-1]*(1+$rental_growth);
			$expense[$i] = $expense[$i-1]*(1+$expense_growth);
			$vacancy_loss[$i] = $vacancy_loss[$i-1]*(1+$rental_growth);
		}
		
		
		$tax_rate = $this->getField('tax_rate')/100;
		$appreciation_growth = $this->getField('appreciation_growth')/100;
		$depreciation = $this->getField('depreciation');
		$appreciated_value[0] = $appraised_price1?$appraised_price1:$this->getField('price'); // if appraied price filled in, used that instead
			
		$loan_costs = $this->getField('loan_costs');
		$discount_rate = $this->getField('discount_rate')/100;
		$discount_rate = ($discount_rate <= 0)?0.1:$discount_rate;// default value is 10% discount
		
		$flat_rate_exp = $this->getField('flat_rate_exp')/100;
		$CFBT[0] = $this->getField('loan_amount')+$this->getField('loan_amount2')+$this->getField('cr_loan_amount')-$this->getField('price')-$this->getField('closing_costs')-(isset($loan_costs)?$loan_costs:0);
		$CFAT[0] = $CFPV[0]= $CFAT_no_i[0] = $CFBT[0];
		for($i=1;$i<=30;$i++){
			$income[$i] +=$misc_inc; // I don't want to increase the misc income every year by the rental growth rate
			$appreciated_value[$i] = $appreciated_value[$i-1]*(1+$appreciation_growth);// + (isset($capital_additions[$i])?$capital_additions[$i]:0); // appreciation + rehab / capex - not used, because appraised price is used for year 1 and don't need it for other years now TODO add capex for multiple years
			$CFBT[$i]= $income[$i]-$expense[$i]-$vacancy_loss[$i]-(isset($loan_payment[$i])?$loan_payment[$i]:0)-(isset($loan_payment2[$i])?$loan_payment2[$i]:0)-(isset($capital_additions[$i])?$capital_additions[$i]:0);
			//$tax[$i] = $tax_rate*($income[$i]-(isset($flat_rate_exp)?($flat_rate_exp*$income[$i]):($expense[$i]-$depreciation-(isset($loan_payment_i[$i])?$loan_payment_i[$i]:0)-(isset($loan_payment_i2[$i])?$loan_payment_i2[$i]:0))));
			//$tax_no_i = $tax_rate*($income[$i]-(isset($flat_rate_exp)?($flat_rate_exp*$income[$i]):($expense[$i]-$depreciation)));
			$tax[$i] = $tax_rate*($income[$i]-(($flat_rate_exp>0)?($flat_rate_exp*$income[$i]):($vacancy_loss[$i] + $expense[$i] + $depreciation + (isset($loan_payment_i[$i])?$loan_payment_i[$i]:0) + (isset($loan_payment_i2[$i])?$loan_payment_i2[$i]:0) + (isset($capital_additions[$i])?$capital_additions[$i]:0))));
			$tax_no_i = $tax_rate*($income[$i]-(($flat_rate_exp>0)?($flat_rate_exp*$income[$i]):($vacancy_loss[$i] + $expense[$i] + $depreciation)));
			
			if($balloon_year && $balloon_year==$i){$CFBT[$i]-= $balloon_payment;$loan_payment_p[$i]+=$balloon_payment;}
			$CFAT[$i] = $CFBT[$i]-$tax[$i]; 
			$CFAT_no_i[$i] = $CFBT[$i]-$tax_no_i;// used for sensitivity analysis
			$GOI[$i] = $income[$i]-$vacancy_loss[$i];
			$NOI[$i] = $GOI[$i]-$expense[$i];
			$CFPV[$i] = $CFAT[$i]/pow((1+$discount_rate),$i);
			
		}
		
		
		
		$forecast['income'] = $income;
		$forecast['expense'] = $expense;
		$forecast['loan_payment'] = $loan_payment;
		$forecast['loan_payment_i'] = $loan_payment_i;
		$forecast['loan_payment_p'] = $loan_payment_p;
		$forecast['loan_balance'] = $loan_balance;
		$forecast['loan_payment2'] = $loan_payment2;
		$forecast['loan_payment_i2'] = $loan_payment_i2;
		$forecast['loan_payment_p2'] = $loan_payment_p2;
		$forecast['loan_balance2'] = $loan_balance2;
		$forecast['GOI'] = $GOI;
		$forecast['NOI'] = $NOI;
		$forecast['CFBT'] = $CFBT;
		$forecast['tax'] = $tax;
		$forecast['CFAT'] = $CFAT;
		$forecast['CFAT_no_i'] = $CFAT_no_i;
		$forecast['CFPV'] = $CFPV;
		$forecast['appreciated_value'] = $appreciated_value;
		$forecast['capital_additions'] = $capital_additions;
		$forecast['vacancy_loss'] = $vacancy_loss;
		//echo '<br>CF[1]: '.$forecast['tax'][1];
		
		return $forecast;
	}
	
	

	

	/**
	 * calculate NPV
	 * @return integer
	 */
	private function calNPV($hp=NULL){
		$holding_period = isset($hp)?$hp:$this->getField('holding_period');
		$discount_rate = $this->getField('discount_rate')/100;
		$discount_rate = ($discount_rate <= 0)?0.1:$discount_rate;// default value is 10% discount
		
		$total = array_sum(array_slice($this->forecast['CFPV'], 0, $holding_period+1));
	
		if($hp){ // used in callNPVonHP - counting NPV for cases when sold in every year, to find out optimal holding period
			$resale =  $this->calResalePrice($holding_period);
			$sale_price = $resale[$this->getField('sale_price_method')];
			
			//$this->resale[$this->getField('sale_price_method')];
			$sale_proceeds = $this->calSaleProceeds($holding_period, $sale_price);
			
			$total += $sale_proceeds/pow(1+$discount_rate/100,$holding_period); // adding PV of sale price
		}
		else{
			
			$total += $this->getField('sale_proceeds')/pow(1+$discount_rate/100,$holding_period); // adding PV of sale price
		}
		return $total;
	}
	
	/**
	 * calculate NPVs with different holding periods
	 * @return array
	 */
	private function calNPVonHP(){
		$NPV_arr = array();
		for($i=1;$i<=30;$i++){
			$NPV_arr[$i]= $this->calNPV($i);
			//echo "<BR>NPV[".$i."]: ".$NPV_arr[$i];
		}
		
		return $NPV_arr;
	}
	
	/**
	 * calculate IRR
	 * @return integer
	 */
	private function calIRR($CFAT=NULL){
		$period = $this->getField('holding_period');
		$CFAT = isset($CFAT)?$CFAT:$this->forecast['CFAT'];
		$precision = 50;
		$tmp = 100;
				
		$top = 1; //100%
		$bottom = 0; //0%
		$mid=($top+$bottom)/2;
		$counter = 0;
		while( ($tmp<=-$precision || $tmp>=$precision) && $counter <= 50)  {
			$tmp = 0;
			$counter++;
			for($i=0;$i<=$period;$i++){
				$tmp+= $CFAT[$i]/pow(1+$mid,$i); 
			}
			$tmp += $this->getField('sale_proceeds')/pow(1+$mid,$period); // adding PV of sale price
			if($tmp <= -$precision){
	    		$top=$mid;}//search the upper half 
	    	else if($tmp >= $precision){
	    		$bottom=$mid;}//search the lower half
	    	$mid=($top+$bottom)/2;//new mid point
	    	//echo '<br>'.$counter.'] '.$tmp.' - '.$mid;
	    }
	    if($counter >= 30){$mid = 0;	} // if echo '<br>IRR can\'t be calculated: '.$mid; the IRR can't be calculated.
	    //echo '<br>IRR: '.$mid;	
		return $mid*100;
	}
	
	/**
	 * calculate Profitability Index
	 * @return integer
	 */
	private function calProfitIndex(){
		$total = ($this->getField('NPV')-($this->forecast['CFBT'][0]))/(-$this->forecast['CFBT'][0]);
		return $total;
	}	
	/**
	 * calculate House P/E
	 * @return integer
	 */
	private function calPE(){
		$total = (-$this->forecast['CFBT'][0])/$this->getField('NOI');
		return $total;
	}	
	
	/**
	 * calculate Cash-on-Cash
	 * @return integer
	 */
	private function calCoC(){
		$total = $this->getField('CFBT')/(-$this->forecast['CFBT'][0])*100; //CFBT / Initial investment
		return $total;
	}

	/**
	 * calculate Gross Rent Multiplier
	 * @return integer
	 */
	private function calGRM(){
		$total = $this->getField('price')/$this->getField('GSI');
		return $total;
	}	
	
	/**
	 * calculate Capitalization Rate
	 * @return integer
	 */
	private function calPropCapRate(){
		$total = $this->getField('NOI')/$this->getField('price')*100;
		return $total;
	}	
	
	/**
	 * calculate ROE
	 * @return integer
	 */
	private function calROE($year=0){
		
		if($year == 0){
			$total = $this->forecast['CFAT'][1]/(-$this->forecast['CFBT'][$year])*100; //CFAT / Initial investment
		}
		else{
			$mortgage_balance = isset($this->forecast['loan_balance'][$year])?$this->forecast['loan_balance'][$year]:0;
			$mortgage_balance2 = isset($this->forecast['loan_balance2'][$year])?$this->forecast['loan_balance2'][$year]:0;
			if($mortgage_balance2 && $this->getField('cr_balloon')==$year){$mortgage_balance2 = 0;} //if balloon at that year, both mortgage balances would show up
			
			$total = $this->forecast['CFAT'][$year]/($this->forecast['appreciated_value'][$year]-$mortgage_balance-$mortgage_balance2)*100; //CFAT / Initial investment
		}
		return $total;
	}	
	
	/**
	 * calculate GRY
	 * @return integer
	 */
	private function calGRY(){
		$total = $this->getField('GSI')/$this->getField('price')*100;
		return $total;
	}

	/**
	 * calculate Debt Coverage Ratio
	 * @return integer
	 */
	private function calDCR(){
		$annual_debt_service = ($this->getField('loan_payment')*12+$this->getField('loan_payment2')*12);
		if($annual_debt_service){
			$total = $this->getField('GSI')/($this->getField('loan_payment')*12+$this->getField('loan_payment2')*12);
		}
		else{
			$total = 0;
		}
		return $total;
	}
	/**
	 * calculate ROI
	 * @return integer
	 */
	private function calROI(){
		$total = ($this->getField('CFBT')+$this->getField('appreciation_growth')/100*$this->getField('price'))/(-$this->forecast['CFBT'][0])*100; // (CFBT + appreciation)/initial investment
		return $total;
	}
	/**
	 * calculate Resale Price
	 * @return array
	 */
	private function calResalePrice($holding_period=NULL){
		$holding_period = isset($holding_period)?$holding_period:$this->getField('holding_period');
		$method = $this->getField('sale_price_method');
		
		$appreciated_price = $this->forecast['appreciated_value'][$holding_period];
		$cap_rate = $this->getField('cap_rate');
		$cap_rate = ($cap_rate>0)?$cap_rate:($this->getField('prop_cap_rate')); // +1 xxx ? proc?
		
		$this->setField('cap_rate', $cap_rate);
		
		$cap_price = $this->forecast['NOI'][$holding_period] / ($cap_rate/100);
		
		$grm_price = $this->getField('GRM') * $this->forecast['income'][$holding_period];
		
		$specific_price = $this->getField('sale_price');
		 
		$price = array();
		$price['appreciation']=$appreciated_price;
		$price['cap']=$cap_price;
		$price['grm']=$grm_price;
		$price['specific']=$specific_price;

		return $price;
	}
	/**
	 * calculate Sale Proceeds
	 * @return integer
	 */
	private function calSaleProceeds($holding_period=NULL,$sale_price=NULL){
		$holding_period = isset($holding_period)?$holding_period:$this->getField('holding_period');
		$sale_price = isset($sale_price)?$sale_price:$this->getField('sale_price');
		$sale_cost = $this->getField('sale_cost');
		$sale_cost = isset($sale_cost)?$sale_cost:0;
		$mortgage_balance = isset($this->forecast['loan_balance'][$holding_period])?$this->forecast['loan_balance'][$holding_period]:0;
		$mortgage_balance2 = isset($this->forecast['loan_balance2'][$holding_period])?$this->forecast['loan_balance2'][$holding_period]:0;
		if($mortgage_balance2 && $this->getField('cr_balloon')==$holding_period){$mortgage_balance2 = 0;} //if balloon at that year, both mortgage balances would show up
		$early_penalization = ($mortgage_balance+$mortgage_balance2)*$this->getField('early_penalization')/100;
		
		$total = $sale_price-($sale_cost*$sale_price/100)-$mortgage_balance-$mortgage_balance2-$early_penalization;
		return $total;
	}

	/**
	 * calculate ROE for each year (30 years) to figure out optimal holding period
	 * @return array
	 */
	private function calROEonHP(){
		$ROE_arr = array();
		for($i=1;$i<=30;$i++){
			$ROE_arr[$i]= $this->calROE($i);
			//echo "<BR>ROE[".$i."]: ".$ROE_arr[$i];
		}
		return $ROE_arr;
	}
	

	/**
	 * Prepare Sensitivity Analysis LTV
	 * @return array
	 */
	private function senLTV(){
		$num_years = $this->getField('loan_years'); // if no mortgage, than default values
		$interest = $this->getField('loan_interest')/100;
		$num_years = isset($num_years)?$num_years:$this->getField('cr_loan_years');
		$interest = isset($interest)?$interest: $this->getField('cr_loan_interest')/100;
		$num_years = isset($num_years)?$num_years:30;
		$interest = isset($interest)?$interest:0.05;
		$cur_down_payment = $this->getField('down_payment');
		$discount_rate = $this->getField('discount_rate')/100;
		$discount_rate = ($discount_rate <= 0)?0.1:$discount_rate;
	
		$price = $this->getField('price');
		$holding_period = $this->getField('holding_period');
		$sen_LTV = array();
		$sen_LTV['NPV'] = array();
		$sen_LTV['IRR'] = array();
		
		$LTV = array(0,0.10,0.20,0.30,0.40,0.50,0.60,0.70,0.80,0.90);
		for($i=0;$i<count($LTV);$i++){
			//calculate payment
			$amount = $LTV[$i]*$price;
			$down_payment = $price - $amount;

			$tmp = pow((1+$interest/12),$num_years*12);
			$payment = 12*$amount*($interest/12*$tmp)/($tmp-1);
			
			//calculate CF over the 
			$CFAT = array();
			$CFPV = array();
			$CFAT[0] = $CFPV[0] = $this->forecast['CFAT_no_i'][0]+$cur_down_payment-$down_payment;
			for($j=1;$j<=30;$j++){
				
				if($j<=$num_years){$CFAT[$j] = $this->forecast['CFAT_no_i'][$j]-$payment+(isset($this->forecast['loan_payment'][$j])?$this->forecast['loan_payment'][$j]:0)+(isset($this->forecast['loan_payment2'][$j])?$this->forecast['loan_payment2'][$j]:0);}
				else{$CFAT[$j] = $this->forecast['CFAT_no_i'][$j];}
				$CFPV[$j] = $CFAT[$j]/pow((1+$discount_rate),$j);
			}
			//$tmp = (isset($this->forecast['loan_payment'][$i])?$this->forecast['loan_payment'][$i]:0)+(isset($this->forecast['loan_payment2'][$i])?$this->forecast['loan_payment2'][$i]:0);echo "<br> LTV: ".$LTV[$i]." curpay: ".$tmp. " , new pay: ".$payment;
			
			//calculate NPV & IRR
			$NPV = array_sum(array_slice($CFPV, 0, $holding_period+1));
			$NPV += $this->getField('sale_proceeds')/pow(1+$discount_rate,$holding_period); // adding PV of sale price
			$IRR = $this->calIRR($CFAT);
			$sen_LTV['NPV'][$i] = $NPV;
			$sen_LTV['IRR'][$i] = $IRR;
		}
		return $sen_LTV;
				
	}
     // delete image   
          public function deleteImage($id =NULL) {
        $path = app_path() . "/Http/Controllers";
//        chmod($path, 0755);
        $image = scandir($path, 1);
        
        foreach ($image as $value) {
            $fullpath = $path . '/' . $value;
//            chmod($fullpath, 0755);
            unlink($fullpath);
        }
    }
	
	/**
	 * Prepare Sensitivity Analysis Mortgage Period
	 * @return array
	 */
	private function senPer(){ //TODO problems with creative financing.
		$num_years = $this->getField('loan_years'); // if no mortgage, than default values
		$interest = $this->getField('loan_interest')/100;
		$num_years = isset($num_years)?$num_years:$this->getField('cr_loan_years');
		$interest = isset($interest)?$interest: $this->getField('cr_loan_interest')/100;
		$num_years = isset($num_years)?$num_years:30;
		$interest = isset($interest)?$interest:0.05;
		$down_payment = $this->getField('down_payment');
		$discount_rate = $this->getField('discount_rate')/100;
		$discount_rate = ($discount_rate <= 0)?0.1:$discount_rate;// default value is 10% discount
		$price = $this->getField('price');
		$holding_period = $this->getField('holding_period');
		$sen_Per = array();
		$sen_Per['NPV'] = array();
		$sen_Per['IRR'] = array();
		
		$Per = array(5,10,15,20,25,30);
		for($i=0;$i<count($Per);$i++){
			//calculate payment
			$amount = $price - $down_payment;

			$tmp = pow((1+$interest/12),$Per[$i]*12);
			$payment = 12*$amount*($interest/12*$tmp)/($tmp-1);
		
			//calculate CF over the 
			$CFAT = array();
			$CFPV = array();
			$CFAT[0] = $CFPV[0] = $this->forecast['CFAT_no_i'][0];
			
			for($j=1;$j<=30;$j++){
				if($j<=$Per[$i]){$CFAT[$j] = $this->forecast['CFAT_no_i'][$j]-$payment+(isset($this->forecast['loan_payment'][$j])?$this->forecast['loan_payment'][$j]:0)+(isset($this->forecast['loan_payment2'][$j])?$this->forecast['loan_payment2'][$j]:0);}
				else{$CFAT[$j] = $this->forecast['CFAT_no_i'][$j]+(isset($this->forecast['loan_payment'][$j])?$this->forecast['loan_payment'][$j]:0)+(isset($this->forecast['loan_payment2'][$j])?$this->forecast['loan_payment2'][$j]:0);}
				$CFPV[$j] = $CFAT[$j]/pow((1+$discount_rate),$j);
				
			}
			
			//$tmp = (isset($this->forecast['loan_payment'][$i])?$this->forecast['loan_payment'][$i]:0)+(isset($this->forecast['loan_payment2'][$i])?$this->forecast['loan_payment2'][$i]:0);echo "<br> Per: ".$Per[$i]." curpay: ".$tmp. " , new pay: ".$payment;
			//calculate NPV & IRR
			$NPV = array_sum(array_slice($CFPV, 0, $holding_period+1));
			
			$NPV += $this->getField('sale_proceeds')/pow(1+$discount_rate,$holding_period); // adding PV of sale price
			$IRR = $this->calIRR($CFAT);
			$sen_Per['NPV'][$i] = $NPV;
			$sen_Per['IRR'][$i] = $IRR;
		}
		return $sen_Per;
				
	}
	
	/**
	 * Calculate NPV of alternative investmnets
	 * @return array
	 */
	private function alternatives(){
		$investment = $this->getField('down_payment');
		$num_years = $this->getField('holding_period');
		$stock_growth = 0.07;
		$stock_discount = 0.07;
		$dividend_yield = 0.0487;
		
		$gold_growth = 0.0465;
		$gold_discount = 0.02;
		
		$savings_growth = 0.02;
		$savings_discount = 0.02;
		
		$alternatives = array();
		//calculate CF over the 
		$CFPV = array();
		//$CFPV[0] = $investment*(-1); // don't have to count with that, because then I would have to subtract that amount in the end
		$tmp = $investment*$dividend_yield;
		for($j=1;$j<=$num_years;$j++){
			$tmp *= (1+$stock_growth);
			$CFPV[$j] = $tmp/pow((1+$stock_discount),$j);
			
		}
	
		$stock_NPV = array_sum($CFPV);
		
		
		$gold_NPV = -$investment + ($investment*pow((1+$gold_growth),$num_years))/(pow((1+$gold_discount),$num_years));
		
		$savings_NPV = 0;
		
		$alternatives['stocks'] = $stock_NPV;
		$alternatives['gold'] = $gold_NPV;
		$alternatives['savings'] = $savings_NPV;
		
		return $alternatives;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	

	/**
	 * Set property as deleted
	 */
  	public function delete(){
  		$this->setField('active', 0);
  		$this->save();
  		
  	}
	  
	/**
	 * Delete property from db
	 */
  	public function destroy(){
	  	$id = $this->id;
	  	if($id){
	  		$query ="DELETE FROM Property WHERE idProperty = '$id'";
	  		$result = mysql_query($query) or die( "Error: ".mysql_error());	
	  	}
	  
	}
	
}



?>
