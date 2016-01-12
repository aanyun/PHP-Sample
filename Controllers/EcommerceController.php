<?php
class EcommerceController
{   
    private static function authCheck(){
        $app = \Slim\Slim::getInstance();
        $token = $app->request->headers->get('X_Authorization');
        $auth = Auth_Token::where('token', '=', $token)->first();
        if (!$auth) {
            $app->halt(401,json_encode('Unauthorized'));
        }
        $idUser = $auth->user_id;
        if (!User::find($idUser)) {
            $app->halt(401,json_encode('user not exist'));
        }
        return $idUser;
    }

    private static function cartCheck(){
        //Only check if the total amount is match
        $app = \Slim\Slim::getInstance();
        $data = $app->request->post();
        $data['items'] = json_decode($data['items']);
        if(!$data['items'] || !is_array($data['items'])){
            //if cart item is empty
            $app->halt(400,json_encode('Cart Info is invalid.')); 
        }
    
        $coupons = [];
        if(isset($data['coupons'])) $coupons = json_decode($data['coupons']);
        $sum = 0;
        foreach ($data['items'] as $key => $value) {
            //validate shopping cart object is valid
            StoreController::isValid($value);
            $backEndPrice = StoreController::applyCoupon($value->id,$coupons)->price;
            if($backEndPrice!=$value->price){
                $app->halt(409,json_encode('Price is invalid.'));
            }
            $sum = $sum + $backEndPrice*$value->qty;
        }
        if($sum!=$data['amount']) {
            $app->halt(409,json_encode('Total price is wrong.'));
        } 
    }
    private static function inputValid(){
        $app = \Slim\Slim::getInstance();
        $data = $app->request->post();

        //validate input
        $validata = $app->validata;
        $validator = $validata::key('items', $validata::stringType()->notEmpty())
                                ->key('amount', $validata::numeric()->notEmpty())
                                ->key('card_num', $validata::digit()->notEmpty())
                                ->key('exp_date', $validata::date('m/y')->notEmpty());
        try{
            $validator->assert((array) $data);
        } catch (\InvalidArgumentException $e) {

        }
        if (!$validator->validate((array)$data)){
            $app->halt(400,json_encode("Input Invalid"));
        }
    } 

    /**
     * @api {post} /ecommerce/purchase Make Purchase
     * @apiName Make Purchase
     * @apiGroup Transaction
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam  {String[]} items cart items in json format
     * @apiParam  {Number} amount Total amount.
     * @apiParam  {Number} card_num Credit Cart Number.
     * @apiParam  {String} exp_date Credit Cart exp date in 'm/y' format.
     *
     * @apiParam  (items) {Number} id course_sale_id
     * @apiParam  (items) {Number} course_id Course Id.
     * @apiParam  (items) {String} name Course Name.
     * @apiParam  (items) {Number} price final price.
     * @apiParam  (items) {Number} qty qty.
     * 
     * @apiError 400 Input Invalid. This will happen if the param is missing or not the valid format.
     * @apiError 404 Not found. This will happen if the role id/user id/group id is not in our system.
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * 
     * 
     */    
    public static function purchase(){
        $app = \Slim\Slim::getInstance();
        $data = $app->request->post();
        $idUser = self::authCheck();
        self::inputValid();
        self::cartCheck();
        $data['cust_id'] = $idUser;
        $transaction = new Authorize();
        $transaction->setCustomer($data);
        $transaction->addItem(json_decode($data['items']));
        $result = $transaction->AIM($data['amount'],$data['card_num'],$data['exp_date']);
        if(!$result->approved){
            $app->halt(400,json_encode($result->response_reason_text));
        }
        EnrollmentController::afterPurchaseEnroll(json_decode($data['items']),$idUser);
        return json_encode($result);
    }
    
}
?>
