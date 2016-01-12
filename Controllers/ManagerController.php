<?php
class ManagerController {
    /**
     * @api {get} /managers/:id/bin Get Items in Manager's Content Bin 
     * @apiName Get Items in Manager's Content Bin
     * @apiDescription As a manager, any content I purchased will be shown in my bin.  When an item reaches its expiration date, change the date to read 'EXPIRED'. Keep listing 'EXPIRED' items in this way for 30 days.
     * @apiGroup Manager
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam {Number} id Users unique ID.
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "id": 14,
     *       "quantity": 1,
     *       "expiration_dt": "2015-11-30 00:00:00",
     *       "created_at": "2015-11-19 14:12:30",
     *       "updated_at": "2015-11-19 14:12:30",
     *       "course_id": 45950,
     *       "course_name": "Scotsman: Prodigy Eclipse Ice Cuber EH222",
     *       "course_type": "course",
     *       "restrictions":{
     *           "prerequisite":[],
     *           "expiration":""
     *       }
     *     }
     *
     */
    public static function getBin($idUser)
    {   
        GroupController::isAdmin($idUser);
        $bins = Manager_Bin::where('user_id','=',$idUser)->get();
        $result = [];
        foreach ($bins as &$bin) {
            unset($bin->user_id);
            unset($bin->company_id);
            $course = Price::find($bin->course_sale_id)->course;
            unset($bin->course_sale_id);
            $bin['course_id'] = $course->id;
            $bin['course_name'] = $course->name;
            $bin['course_type'] = $course->type;

            $expired_dt = new DateTime($bin['expiration_dt']);
            $now = new DateTime();
            $diff = $now->diff($expired_dt,false);
            //var_dump($diff);
            $restrictions = [];
            //echo $diff->format("%R%a");
            if($diff->format("%R%a")>=0){
                $pre = CourseController::getCoursePrerequisites($course->id);
                if($pre)
                    $restrictions['prerequisite']=$pre;
            } else if($diff->format("%R%a")>-30) {
                $bin['expiration_dt'] = "EXPIRED";
                $restrictions['expiration']='This content has expired. You cannot assign or transfer this content.  You may renew this content for 30 days after the expiration at the renewal price.';
            } else {
                continue;
            }
            
            $bin['restrictions'] = $restrictions;
            array_push($result, $bin);
        }
        return json_encode($result);
    }

    public static function addToBin($course_sale_id, $qty, $idUser, $end_at){
        $bin = new Manager_Bin();
        $bin->course_sale_id = $course_sale_id;
        $bin->user_id = $idUser;
        $bin->quantity = $qty;
        $bin->expiration_dt = $end_at;
        $bin->save();
        return $bin->id;
    }

    /**
     * @api {post} /managers/:idUser/assign Assign Content by Manager
     * @apiName Assign Content by Manager
     * @apiGroup Manager
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam  (url Parameter) {Number} idUser User unique ID.
     * @apiParam  {Number} idBin Bin's unique ID.
     * @apiParam  {Number} idUser User's unique ID. The change will apply to this user.
     * 
     * @apiError 400 Input Invalid. This will happen if the param is missing or not the valid format.
     * @apiError 404 Not found. This will happen if the bin id/user id/course id/sale id is not in our system.
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 403 The user is not a manager yet. 
     * @apiError 409 User already enrolled.
     * @apiErrorExample {json} Error-Response:
     *     HTTP/1.1 412 Precondition Failed. User doesn't complete the prerequisite course.
     *      [
     *       {
     *           "id": 45951,
     *           "code": "Test",
     *           "name": "TurboChef: High h conveyor C",
     *           "manufacturer": "TurboChef",
     *           "shortDescription": null,
     *           "description": null,
     *           "note": null,
     *          "isPublished": 1,
     *           "time": 90,
     *           "type": "course",
     *           "safety": 1,
     *           "html5": 1,
     *           "video": null,
     *           "thumbnail": "http://localhost:9090/ignitor-api/courses/thumbnail/45951"
     *       }
     *   ]    
     *
     *
     */ 
	public static function assignContent($idUser)
    {   
        $app = \Slim\Slim::getInstance();
        $request = $app->request->post();
        $validata = $app->validata;
        $validator = $validata::key('idBin', $validata::digit()->notEmpty())
                                ->key('idUser', $validata::digit()->notEmpty());
        if (!$validator->validate($request)) {
            $app->halt("400",json_encode("Input Invalid"));
        }
        if(!GroupController::isMemberOfAdmin($request['idUser'],$idUser)){
            $app->halt("403",json_encode("Permission denied."));
        }
        if($idUser!=$request['idUser']&&GroupController::isManagerOfAdmin($request['idUser'],$idUser)){
            $app->halt("403",json_encode("Permission denied. You can only transfer content to manager user."));
        }
        $bin_id = $request['idBin'];
        $bin = Manager_Bin::where('id','=',$bin_id)->lockForUpdate()->first();
        if(!$bin){
            $app->halt("404",json_encode("manager content record does not exist"));
        }
        if($bin->user_id!=$idUser){
            $app->halt("401");
        }
        if($bin->quantity<1){
            $app->halt("404",json_encode("No available seat found."));
        }
        $sale = Price::find($bin->course_sale_id);
        if(!$sale){
            $app->halt("404",json_encode("sale record does not exist"));
        }
        $isEnroll = EnrollmentController::isEnroll($sale->course_id,$request['idUser']);
        if($isEnroll){
        	$app->halt("409",json_encode("This User already has this content."));
        }
        EnrollmentController::meetPrerequisite($sale->course_id,$request['idUser']);
        $enrollment = EnrollmentController::enroll($sale->course_id,$request['idUser'],$bin->expiration_dt);
        if($enrollment){
            $bin->seats()->attach(
            $enrollment,
            array(
                'sender_id'=>$idUser,
                'receiver_id'=>$request['idUser'],
                'course_sale_id'=>$bin->course_sale_id
            ));
            $bin = Manager_Bin::where('id','=',$bin_id)->lockForUpdate()->first();
            if($bin->quantity<1){
                $app->halt("404",json_encode("No available seat found."));
            }
            $bin->quantity = $bin->quantity-1;
            $bin->save(); 
        }
    }
    /**
     * @api {post} /managers/:idUser/transfer Transfer Content by Manager
     * @apiName Transfer Content by Manager
     * @apiGroup Manager
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam  (url Parameter) {Number} idUser User unique ID.
     * @apiParam  {Number} idBin Bin's unique ID.
     * @apiParam  {Number} qty qty to transfer.
     * @apiParam  {Number} idUser User's unique ID. The change will apply to this user.
     * 
     * @apiError 400 Input Invalid. This will happen if the param is missing or not the valid format.
     * @apiError 404 Not found. This will happen if the bin id/user id/course id/sale id is not in our system.
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 403 The user is not a manager yet. 
     *
     *
     */ 
    public static function transContent($idUser){
        $app = \Slim\Slim::getInstance();
        $request = $app->request->post();
        $validata = $app->validata;
        $validator = $validata::key('idBin', $validata::digit()->notEmpty())
                                ->key('qty', $validata::digit()->notEmpty())
                                ->key('idUser', $validata::digit()->notEmpty());
        if (!$validator->validate($request)) {
            $app->halt("400",json_encode("Input Invalid"));
        }
        if(!GroupController::isManagerOfAdmin($request['idUser'],$idUser)){
            $app->halt("403",json_encode("Permission denied."));
        }
        $bin_id = $request['idBin'];
        $bin = Manager_Bin::where('id','=',$bin_id)->lockForUpdate()->first();
        if(!$bin){
            $app->halt("404",json_encode("manager content record does not exist"));
        }
        if($bin->user_id!=$idUser){
            $app->halt("401");
        }
        if($bin->quantity<$request['qty']){
            $app->halt("404",json_encode("No available seat found."));
        }
        $bin->quantity = $bin->quantity-$request['qty'];
        $bin->save();
        $new_bin = self::addToBin($bin->course_sale_id, $request['qty'], $request['idUser'], $bin->expiration_dt);
        $bin->transferOut()->attach(
            $new_bin,
            array(
                'sender_id'=>$idUser,
                'receiver_id'=>$request['idUser'],
                'course_sale_id'=>$bin->course_sale_id,
                'quantity'=>$request['qty']
            ));

    }
}
?>