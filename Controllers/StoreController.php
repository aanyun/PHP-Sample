<?php
class StoreController
{   
    public static function price(){
        $app = \Slim\Slim::getInstance();
        $data = $app->request->post();

        $course_sales = isset($data['course_sales'])?$data['course_sales']:null;
        $coupons = isset($data['coupons'])?$data['coupons']:null;
        
        $token = AuthController::getToken($app->request->headers);
        
        $auth = Auth_Token::where('token', '=', $token)->first();
        if (!$auth) {
            $app->response->setStatus(401);
            return 0;
        }

        $price_array = explode(",", $course_sales);
        $coupon_array = explode(",", $coupons);
        $result = array();
        
        //go get price and course details and put in memory so that we minimize hits to DB
        $details_array = array();
        
        foreach($price_array as $price_id)
        {
            try {
                $price = Price::find($price_id);
                $course = $price->course;
                array_push($details_array, array("course_id" => $course->id, "price_id" => $price->id, "price" => $course->price));
            } catch (Exception $ex) {}  
        }
                
        foreach($coupon_array as $code) {
            $code = trim($code);
            try {
                $valid_coupon = Coupon::valid()->where("code", "=", $code)->first();
            }
            catch(Exception $e) {
                $valid_coupon = false;
            }

            if ($valid_coupon) {
                //check to see if the course exists
                $course_sale_id = $valid_coupon->course_sale_id; 
                for ($i = 0; $i < sizeof($details_array); $i++) {
                    if ($course_sale_id == $details_array[$i]["price_id"]) {
                        $price = $details_array[$i]["price"];
                        $value = $valid_coupon->value;
                        $type = $valid_coupon->type;
                        
                        $price_change = 0;
                        if ($type == "percent-discount") 
                            $price_change = $price * $value;
                        elseif ($type == "flat-discount") 
                            $price_change = $value;
                        
                        array_push($result, array( "course_sale_id" => $course_sale_id, "code" => $code, "comments" => $valid_coupon->comments, "price" => $price, "price_change" => $price_change, "new_price" =>$price - $price_change));
                        array_splice($details_array, $i, 1);
                        
                        break;
                    }
                }
            }
            
        }
        
        $app->response->setStatus(200);
        return json_encode($result);
    }
    /**
     * Validate shopping cart input data
     * @author Anyun
     * @param  object $data
     * @return  
     */

    public static function isValid($data){
        $app = \Slim\Slim::getInstance();
        $validata = $app->validata;
        $validator = $validata::key('id', $validata::numeric()->notEmpty())
                                ->key('course_id', $validata::numeric()->notEmpty())
                                ->key('name', $validata::stringType()->notEmpty())
                                ->key('price', $validata::numeric())
                                ->key('qty', $validata::digit()->notEmpty());
        if (!$validator->validate((array)$data)){
            $app->halt(400,json_encode('Cart Info is invalid.'));
        }
        
        $sale = Price::find($data->id);

        if(!$sale || $sale->course_id!=$data->course_id || !Course::find($data->course_id)){
            $app->halt(400,'Cart Info is invalid.');
        }
    }

    /**
     * Validate coupon code input data
     * @author Anyun
     * @param  int $code
     * @return  
     */

    public static function isCouponAvailable($code){
        $app = \Slim\Slim::getInstance();
        $validata = $app->validata;
        
        if (!trim($code)){
            $app->halt(400);
        }
        
        $coupon = Coupon::valid()->where('code','like',trim($code))->get();

        if(!$coupon){
            $app->halt(400,'Cart Info is invalid.');
        }
    }


    /**
     * @api {post} /store/update Update Shopping cart info (amount/coupon)
     * @apiName Update Shopping cart info
     * @apiGroup Manager
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam  (url Parameter) {Number} idUser User unique ID.
     * @apiParam  {Number} idBin Bin's unique ID.
     * @apiParam  {Number} idUser User's unique ID. The change will apply to this user.
     * 
     * @apiError 400 Input Invalid. This will happen if the param is missing or not the valid format.
     * @apiError 404 Not found. This will happen if the bin id/user id/course id/sale id is not in our system.
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 409 User already enrolled.
     *
     *
     */ 

    public static function update(){
        $app = \Slim\Slim::getInstance();
        $data = $app->request->post();
        if(!isset($data['items']) || !isset($data['coupons'])){
            $app->halt(400,'Invalid Input');
        }

        $items = json_decode($data['items']);
        $coupons = json_decode($data['coupons']);
        foreach ($items as $key => &$value) {
            StoreController::isValid($value);
            $price_code = StoreController::applyCoupon($value->id,$coupons);
            $value->price = $price_code->price;
            if(isset($price_code->old_price)) $value->orig_price = $price_code->old_price;
            $value->coupon = $price_code->coupon;
        }
        return json_encode($items);
    }
    /**
     * Get the new price after applying a group of coupon.
     * @author Anyun
     * @param  int $sale_id
     * @param  array  $promo_codes
     * @return object [newPrice,promoCode]
     */

    public static function applyCoupon($sale_id,$promo_codes=[]){
        $app = \Slim\Slim::getInstance();
        $sale = Price::find($sale_id);
        if(!$sale){
            $app->halt(400,'Invalid Sale Item');
        }
        $result = array(
            "price"=>$sale->price,
            "coupon"=>""
            );
        if(!$sale){  // this sale not exist
            $app->halt('400',$sale_id);
        }
        //Important Rule: 
        //Only ONE code will be applied to one item
        //If multiply codes effect, chose the lowest one
        $prices = Coupon::where('course_sale_id','=',$sale_id)->whereIn('code',$promo_codes)->valid()->get();
        foreach ($prices as $key => $value) {
            if($value->new_price<$result['price']){
                $result['old_price'] = $sale->price;
                $result['price'] = $value->new_price;
                $result['coupon'] = $value->code;
            }
        }
        return (object)$result;
    }
}
?>
