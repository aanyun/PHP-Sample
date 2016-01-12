<?php
class Authorize{
	private $sale;
	public $sandbox = true;
	public $items;
	public $amount;
	function __construct($type = "AIM") {
        if($type=="AIM"){
       		$this->sale = new AuthorizeNetAIM;
        }
        $this->sale->setSandbox($this->sandbox);
    }
    public function setSandbox($isSandbox){
    	$this->sandbox($isSandbox);
    	$this->sale->setSandbox($this->sandbox);
    }
	public function addItem( $items ){
		$this->items = $items;
		$amount = 0;
		foreach ($items as $key => $value){
			//['id','course_id','name','code','price','qty']
			$amount = $amount + floatval($value->price)*intval($value->qty);
			$this->sale->addLineItem(
				$value->course_id, // Item Id
				substr($value->name, 0,30), // Item Name
				isset($value->coupon)?$value->coupon:"", // Item Description
				$value->qty, // Item Quantity
				$value->price, // Item Unit Price
				'N' // Item taxable
			);
		}

	}
	public function setCustomer($data){
		$customer = (object)array();
		$customer->first_name = isset($data['first_name'])?$data['first_name']:"";
		$customer->last_name = isset($data['last_name'])?$data['last_name']:"";
		$customer->company = isset($data['company'])?$data['company']:"";
		$customer->address = isset($data['address'])?$data['address']:"";
		$customer->city = isset($data['city'])?$data['city']:"";
		$customer->state = isset($data['state'])?$data['state']:"";
		$customer->zip = isset($data['zip'])?$data['zip']:"";
		$customer->country = isset($data['country'])?$data['country']:"";
		$customer->phone = isset($data['phone'])?$data['phone']:"";
		$customer->fax = isset($data['fax'])?$data['fax']:"";
		$customer->email = isset($data['email'])?$data['email']:"";
		$customer->cust_id = isset($data['cust_id'])?$data['cust_id']:"";
		$customer->customer_ip = isset($data['customer_ip'])?$data['customer_ip']:"";
		$this->sale->setFields($customer);
	}

	public function AIM($amount,$card_num,$exp_date){
		$this->sale->setFields(
		    array(
		    'amount' => $amount,
		    'card_num' => $card_num,
		    'exp_date' => $exp_date
		    )
		);
		$response = $this->sale->authorizeAndCapture();
		return $response;
	}
}

?>