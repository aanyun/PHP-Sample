<?php
class EnrollmentController
{   
    public static function isEnroll($idCourse,$idUser){   
        $app = \Slim\Slim::getInstance();
        $user = UserController::findUser($idUser);
        $course = CourseController::getCourseDetails($idCourse);
        return Enrollment::where('user_id','=',$idUser)->where('course_id','=',$idCourse)->count()>0;
    }

    public static function meetPrerequisite($idCourse,$idUser){   
        $app = \Slim\Slim::getInstance();
        $user = UserController::findUser($idUser);
        $prerequisites = CourseController::getCoursePrerequisites($idCourse)->toArray();
        $enrolled = [];
        foreach ($prerequisites as &$value) {
            if(self::isEnroll($value['id'],$idUser)){  
                //completed->meet requisite
                $enrollment = Enrollment::where('user_id','=',$idUser)->where('course_id','=',$value['id'])->first();
                if($enrollment->status=="completed"){
                    $key = array_search($value, $prerequisites);
                    unset($prerequisites[$key]);
                }     
            }
        }
        if(count($prerequisites)==0){
            return;
        }
        $app->halt("412",json_encode($prerequisites));
    }
    /**
     * @api {get} /enrollment/courses/:idCourse/users/:idUser/membership/:id Enroll Course from Membership
     * @apiName Enroll Course from Membership
     * @apiGroup Enrollment
     * @apiDescription
     * @apiParam {Number} id Membership id.
     * @apiParam {Number} idUser User id.
     * @apiParam {Number} idCourse Course id.
     *
     * @apiError 400 Link Invalid. This will happen if param is not sent out.
     * @apiError 404 Not found. This will happen if the role id/user id/group id is not in our system.
     * @apiError 409 Link already activated.
     * @apiError 409 Enrollment exist.
     */
    public static function enrollByMembership($idMembership,$idUser,$idCourse)
    {   
        $app = \Slim\Slim::getInstance();
        if (!User::find($idUser)) {
            $app->halt(404,json_encode("User does not exist"));
        }
        $course = Course::find($idCourse);
        if (!$course || !Course::find($idMembership) || !self::isEnroll($idMembership,$idUser)) {
            $app->halt(404,json_encode("Course does not exist"));
        }
        $courses = Catalog::find($idMembership)->course->lists('id')->toArray();
        if(!in_array($idCourse, $courses)){
            $app->halt(404,json_encode("Course does not exist"));
        }
        // if(self::isEnroll($idCourse,$idUser)){
        //     $app->halt(409,json_encode("Enrollment already exist"));
        // }
        $membership = Enrollment::where('course_id',$idMembership)->where('user_id',$idUser)->first();
        echo self::enroll($idCourse,$idUser,$membership->end_at);
    }
	/**
     * Create a new Enrollment instance.
     *
     * @param  int  $idCourse
     * @param  int  $idUser
     * @return int $idEnrollment
     */
    public static function enroll($idCourse,$idUser,$end_at=Null)
    {   
    	$app = \Slim\Slim::getInstance();
        $user = UserController::findUser($idUser);
        $course = CourseController::getCourseDetails($idCourse);
        if($end_at && date('Y-m-d H:i:s')>$end_at){
            $app->halt("400",json_encode("Enrollment already expired. Please check the end date."));
        }
    	$data = array(
    		'user_id'=>$idUser,
    		'course_id'=>$idCourse
    	);
    	$enrollment = Enrollment::firstOrNew($data);
    	
    	// if there is scorm cloud id then enroll in scorm cloud
    	if (isset($course->scorm_id)){
    		$scormRegistrationId = ScormCloudAPIController::register($idCourse, $idUser);
    		if (isset($scormRegistrationId)) {
    			$enrollment->scorm_registration_id=$scormRegistrationId;
    			$enrollment->scorm_status="enrolled";
    		}
    	}
    	
        if(!$enrollment->id){
            $enrollment->isSafety = $course->safety;
            $enrollment->end_at = $end_at;
            $enrollment->save();
        } else {
            $enrollment->end_at = $end_at;
            //echo $enrollment->end_at;
            $enrollment->save();
        }
        return $enrollment->id;
	}
    /**
     * Sync enrollment.
     *
     * @param  int  $idEnrollment
     * @param  object  enrollments
     * @return int $idEnrollment
     */
    public static function sync()
    {   
        $app = \Slim\Slim::getInstance();
        $data = $app->request->post();
        $enrollments = json_decode($data['data']);
        foreach ($enrollments as $key => $enrollment) {
            $server = Enrollment::find($enrollment->id);
            if (!$server) {
                //$app->response->setStatus(400);
                //return "Enrollment does not exist";
            } else {
                if (is_null($server->progress)){
                    $server->progress = $enrollment->progress;
                    if(property_exists($enrollment,'safety')) $server->isSafety = $enrollment->safety;
                    $server->save();
                } else {
                    //compare
                    //always follow the structure from cilent side
                    $localprogress = json_decode($enrollment->progress);
                    $serverprogress = json_decode($server->progress);

                    foreach ($localprogress as $key => $value) {
                        if(isset($serverprogress->$key)&&$serverprogress->$key){
                            $localprogress->$key = 1;
                        }   
                    }
                    if(property_exists($enrollment,'safety')) $server->isSafety = $enrollment->safety;
                    $server->progress = json_encode($localprogress);
                    $server->save();
                }
            }
        }
        //print_r($enrollments);
        return 1;
    }

    /**
     * Create a new Enrollment instance.
     *
     * @param  int  $idCourse
     * @param  int  $idUser
     * @return int $idEnrollment
     */
    public static function auth($idCourse)
    {   
        $app = \Slim\Slim::getInstance();
        if (!Course::find($idCourse)) {
            $app->response->setStatus(400);
            return 0;
        }
        $token = AuthController::getToken($app->request->headers);
        $auth = Auth_Token::where('token', '=', $token)->first();
        if (!$auth) {
            $app->response->setStatus(401);
            return 0;
        }

        $idUser = $auth->user_id;

        return Enrollment::where('user_id','=',$idUser)->where('course_id','=',$idCourse)->count();
    }
    public static function getEnrollmentByCourseId($idCourse)
    {   
        $app = \Slim\Slim::getInstance();
        if (!Course::find($idCourse)) {
            $app->halt("404","course not found");
        }
        $token = AuthController::getToken($app->request->headers);
        $auth = Auth_Token::where('token', '=', $token)->first();
        if (!$auth) {
            $app->halt("401");
        }
        $idUser = $auth->user_id;
        $enrollment = Enrollment::where('user_id','=',$idUser)->where('course_id','=',$idCourse)->first();
        if(!$enrollment){
            $app->halt("401");
        }
        return $enrollment;
    }

    public static function download($idUser,$idCourse){
        $app = \Slim\Slim::getInstance();
        if (!Course::find($idCourse)) {
            $app->response->setStatus(404);
            return json_encode("Course not found");
        }
        if(!Enrollment::where('user_id','=',$idUser)->where('course_id','=',$idCourse)->count()){
            $app->response->setStatus(401);
            return json_encode("You have to enroll this course first.");
        }
        FileController::downloadCourse($idCourse);
    }

    public static function afterPurchaseEnroll($items,$idUser){
        $isAdmin = GroupController::adminCheck($idUser);
        foreach ($items as $key => $item) {
            $sale = Price::find($item->id);
            if(is_null($sale->length)) {
                $end_at = Null;
            } else {
                $date = strtotime("+".$sale->length." day");
                $end_at = date('Y-m-d H:i:s',$date);
            }
            if(!$isAdmin) {
                self::enroll($item->course_id,$idUser,$end_at);
            } else {
                ManagerController::addToBin($item->id,$item->qty,$idUser,$end_at);
            }
        }
    }
}
?>
