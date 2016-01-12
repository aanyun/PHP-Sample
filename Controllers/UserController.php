<?php
class UserController
{
    private static $pass="ignitorA";
    private static $iv = "1234567812345678";
    private static $active_api = 'activate?source=';
    private static $email_verify_api = 'verify?source=';
    /**
     * Create a new User instance.
     *
     * @param  Object  $request
     * @return Response
     */
    public static function addUser()
    {   
        $app = \Slim\Slim::getInstance();
        $request = (object)$app->request->post();
        //validate input
        $validata = $app->validata;
        $validator = $validata::key('email', $validata::email()->notEmpty())
                                ->key('first_name', $validata::stringType()->notEmpty())
                                ->key('last_name', $validata::stringType()->notEmpty())
                                ->key('password', $validata::stringType()->notEmpty())
                                ->key('company', $validata::intVal())
                                ->key('city', $validata::stringType()->notEmpty())
                                ->key('province', $validata::stringType()->notEmpty())
                                ->key('zip_code', $validata::stringType()->notEmpty())
                                ->key('address', $validata::stringType()->notEmpty());
        $errors = array();
        try{
            $validator->assert((array) $request);
        } catch (\InvalidArgumentException $e) {
            $errors = $e->findMessages(array(
                'email'     => '{{name}} must be a valid email',
                'first_name'   => '{{name}} is required',
                'last_name'   => '{{name}} is required',
                'password'   => 'Password is required',
                'company'   => 'Company is required',
                'city'   => 'City is required',
                'province'   => 'State/Province is required',
                'address'   => 'Address is required',
                'zip_code'   => 'Zipcode is required'
            ));
        }

        if ($validator->validate((array)$request)) {
            if(!PasswordController::isValid($request->password,$request->email)){
                $app->halt('400',json_encode("Password Formart Wrong"));
            }
            
            if(!Company::find($request->company)){
                $app->halt('400',json_encode("Company does not exist"));
            }
            if (self::isExist($request->email)){
                $app->response->setStatus(400);
                return json_encode("Email already taken");
            }

            $user = new User;
            //$user->name = $request->name;
            $user->email = $request->email;
            $user->password = PasswordController::encryptPassword($request->password);
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            if(isset($request->phone))$user->phone = $request->phone;
            $user->city = $request->city;
            $user->address = $request->address;
            $user->province = $request->province;
            $user->zip_code = $request->zip_code;
            $user->country = $request->country;
            $user->active = 0;
            $user->save();
            $app->response->setStatus(200);

            //send confirm email
            if ($user->id) {
                $user->companies()->attach($request->company);
                $link = WEBSITELINK.'/'.self::$active_api.openssl_encrypt ($user->id, 'AES-256-CBC', self::$pass, 0, self::$iv);
                EmailController::newUserConfirmation($user->id,$request->password,$link);
            }
            return $user->id;
        } else {
            $app->response->setStatus(400);
            $return = [];
            foreach (array_values($errors) as $key => $error) {
                if($error!="") 
                    array_push($return,array("code"=>$key,"data"=>$error));
            }
            return json_encode($return);
        }

    }
    /**
     * Check user if exist in our system
     *
     * @param  string  $email
     * @return Response
     */
    public static function findUser($id)
    {
        $app = \Slim\Slim::getInstance();
        $user = User::find($id);
        if(!$user){
            $app->halt('404',json_encode("Use not found."));
        }
        return $user;
    }
    /**
     * Check user if exist in our system
     *
     * @param  string  $email
     * @return Response
     */
    public static function isExist($email)
    {
        return User::where('email', '=', $email)->count();
    }

    /**
     * Check the user is active or not
     *
     * @param  string  $email
     * @return Response
     */
    public static function isActive($email)
    {
        $user = User::where('email', '=', $email)->first();
        return is_null($user)?0:$user->active;
    }
    /**
     * @api {get} /activate Active User
     * @apiName GetUser
     * @apiGroup User
     *
     * @apiParam {String} sourse encrypt string.
     *
     * @apiError 400 Link Invalid. This will happen if param is not sent out.
     * @apiError 404 Not found. This will happen if the role id/user id/group id is not in our system.
     * @apiError 409 Link already activated.
     * @apiError 409 Link Expired. 24h.
     */
    public static function setActive()
    {   
        $app = \Slim\Slim::getInstance();
        $data = $app->request->get();
        if(!isset($data['source'])){
            $app->halt('400',json_encode("Link is invalid."));
        }
        $idUser = openssl_decrypt($data['source'], 'AES-256-CBC', self::$pass, 0, self::$iv);
        $user = User::find($idUser);
        if(!$user){
            $app->halt('404',json_encode("Link is invalid. Please Sign Up."));
        }
        if($user->active){
            $app->halt('409',json_encode("Link is invalid. You already activated your acount."));
        }
        $created_at = new DateTime($user->created_at);
        $interval = date_create('now')->diff($created_at);
        if( $interval->d >=1 ){
           $app->halt('408',json_encode("Link expired."));
        }
        $user->active = 1;
        $saved = $user->save();
        if($saved){
            GroupController::activeEnroll($idUser);
            EmailController::newUserWelcome($idUser);
            return json_encode("success");
        } else {
            $app->halt('500',json_encode("update to db error"));
        }
    }
    /**
     * @api {get} /user/:id Request User information
     * @apiName GetUser
     * @apiGroup User
     *
     * @apiParam {Number} id Users unique ID.
     *
     * @apiSuccess {String} first_name Firstname of the User.
     * @apiSuccess {String} last_name  Lastname of the User.
     */
    public static function getUserInfo($id)
    {   
        $user = User::with('companies')->find($id);
        unset($user->password);

        return $user;
    }

    /**
     * @api {post} /user/:id/save Save User information
     * @apiName save user basic info
     * @apiGroup User
     *
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     *
     * @apiParam {Number} id Mandatory  Users unique ID.
     * @apiParam {String} [first_name] Optional Firstname of the User.
     * @apiParam {String} [last_name] Optional Lastname of the User.
     * @apiParam {String} [theme] Optional Theme of the User.
     * @apiParam {String} [phone] Optional Phone of the User.
     * @apiParam {String} [city] Optional City of the User.
     * @apiParam {String} [province] Optional Province of the User.
     * @apiParam {String} [zip_code] Optional Zipcode of the User.
     * @apiParam {String} [country] Optional Country of the User.
     * @apiParam {String} [address] Optional Address of the User.
     */
    public static function saveUserInfo($idUser)
    {   
        $app = \Slim\Slim::getInstance();
        $data = $app->request->post();
        $validata = $app->validata;
        $rules = array(
            'first_name'=> $validata::stringType()->notEmpty(),
            'last_name'=> $validata::stringType()->notEmpty(),
            'theme'=> $validata::stringType()->notEmpty(),
            'phone'=> $validata::stringType(),
            'city'=> $validata::stringType()->notEmpty(),
            'province'=> $validata::stringType()->notEmpty(),
            'zip_code'=> $validata::stringType()->notEmpty(),
            'country'=> $validata::stringType()->notEmpty(),
            'address'=> $validata::stringType()->notEmpty()
        );
        foreach ($data as $key => $value) {
            if(array_key_exists($key, $rules)){
                if(!$rules[$key]->validate($value)){
                    $app->halt("400",json_encode($key." has error."));
                }
            }else{
                unset($data[$key]);
            }
        }

        try {
            if(empty($data)){
                $app->halt("400",json_encode("Invaild Input"));
            } else {
                $effectRow = User::where('id','=',$idUser)->update($data);
            }
        } catch (Exception $e) {
            $app->halt("400",json_encode($e->getMessage()));
        }
    }

    /**
     * change user email
     *
     * @param  string  $idUser
     * @return Response
     */
    public static function changeEmail($idUser)
    {   
        $app = \Slim\Slim::getInstance();
        $data = $app->request->post();
        $validata = $app->validata;
        if(!isset($data['email']) || !$validata::email()->validate($data['email'])){
            $app->halt('400',json_encode("Input invalid"));
        }
        $email = $data['email'];
        $user = User::find($idUser);
        if(!$user){
            $app->halt('404',json_encode("User not found"));
        }
        if($user->email == trim($data['email'])){
            $app->halt('412',json_encode("Email not change"));
        }
        if (self::isExist($email)){
            $app->halt('409',json_encode("Email already taken"));
        }
        $data = array(
            'user_id'=>$idUser,
            'tmp_email'=>$email
        );
        $tmp = Tmp_Email::firstOrCreate($data);
        $link = 'http://'.$_SERVER['HTTP_HOST'].'/'.DOCUMENTPATH.'/'.self::$email_verify_api.openssl_encrypt ($tmp->id, 'aes128', self::$pass, 0, self::$iv);
        $result = EmailController::changeEmail($idUser,$email,$link);
        echo json_encode($result);
    }

    /**
     * verify user email
     *
     * @param  string  $idUser, $email
     * @return Response
     */
    public static function verifyEmail()
    {   
        $app = \Slim\Slim::getInstance();
        $data = $app->request->post();
        $app = \Slim\Slim::getInstance();
        $data = $app->request->get();
        if(!isset($data['source'])){
            $app->halt('400',json_encode("Link is invalid."));
        }
        $id = openssl_decrypt ($data['source'], 'aes128', self::$pass, 0, self::$iv);
        $tmp = Tmp_Email::find($id);
        if(!$tmp){
            $app->halt('404',json_encode("Link is invalid."));
        }
        if(self::isExist($tmp->tmp_email)){
            $app->halt('409',json_encode("Link is invalid. Email has already been used."));
        }
        $user = User::find($tmp->user_id);
        $user->email = $tmp->tmp_email;
        $saved = $user->save();
        if($saved){
            Tmp_Email::where('user_id','=',$tmp->user_id)->delete();
            EmailController::changeEmailSuccess($tmp->user_id);
            return json_encode("success. Your email have already changed.");
        }
        $app->halt('500',json_encode("Save to DB error."));
    }
    /**
     * get user 'my course' list
     *
     * @param  string  $email
     * @return Response
     */
    public static function getMyCourses($id,$needCourseDetail=True)
    {   
        $courses = MyCourses::where('user_id', '=', $id)->get();
        foreach ($courses as &$value) {
            unset($value->updated_at);
            unset($value->created_at);
            $enrollment = Enrollment::where('user_id',$id)->where('course_id',$value->course_id)->first();
            if($needCourseDetail) {
                $course = Course::find($value->course_id);
                $value->name = $course->name;
                $value->shortDescription = $course->shortDescription;
                $value->price = $course->price;
            }
            if($enrollment) {
                $value->enrollment_status = $enrollment->status;
                $value->enrollment_status_updated_at = $enrollment->updated_at;
                $value->enrollment_at = $enrollment->created_at;
            }
        }
        return json_encode($courses);
    }
    /**
     * @api {get} courses/enrolled/users/:id Get User's course history
     * @apiName Get User's course history
     * @apiGroup Enrollment
     *
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     *
     * @apiParam {Number} idUser Mandatory  Users unique ID.
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   [
     *   {
     *    "membership_id": 1,
     *    "course_id": 26262,
     *    "name": "TurboChef: iSeries",
     *    "manufacturer": "TurboChef",
     *    "type": "3d",
     *    "note": null,
     *    "pak": " iSeries",
     *    "html5": 0,
     *    "safety": -1,
     *    "short_description": null,
     *    "status": "Active to Enroll",
     *    "end_at": "2016-11-29 11:05:07"
     *},
     *{
     *    "id": 103,
     *    "course_id": 45950,
     *    "name": "Scotsman: Prodigy Eclipse Ice Cuber EH222",
     *    "manufacturer": "Scotsman Ice Systems",
     *    "type": "course",
     *    "note": null,
     *    "pak": "Prodigy Eclipse Ice Cuber EH222",
     *    "html5": 1,
     *    "safety": -1,
     *    "short_description": null,
     *    "status": "Enrolled",
     *    "end_at": null,
     *    "enrolled_at": "2015-11-30 12:27:52"
     *}
     *   ]
     */
    public static function getEnrollments($idUser)
    {   
        $app = \Slim\Slim::getInstance();
        if (!User::find($idUser)) {
            $app->response->setStatus(400);
            return json_encode("User does not exist");
        }
        $enrollments = User::find($idUser)->enrollment;
        $active_enrollment_ids = Enrollment::where('end_at','>',date('Y-m-d H:i:s'))
                                ->orwhere('end_at',null)
                                ->where('user_id','=',$idUser)
                                ->lists('course_id')->toArray();
        $result = [];
        foreach($enrollments as &$enrollment){
            if(!is_null($enrollment->end_at)){
                if(date("Y-m-d H:i:s")>$enrollment->end_at){
                    $enrollment->status = "expired";
                    continue;
                }
            }
            $course = Enrollment::find($enrollment->id)->course;
            if($course->type=="membership"){
                if($enrollment->status=="expired"){
                    continue;
                }
                $membership_courses = Catalog::find($course->id)->course()->where("isPublished", "1"); 
                foreach ($membership_courses as $key => $value) {
                    if(is_array($active_enrollment_ids)&&in_array($value->id, $active_enrollment_ids)){
                        continue;
                    }
                    $data = array(
                        "membership_id"=>$enrollment->course_id,
                        "course_id"=>$value->id,
                        "name"=>$value->name,
                        "manufacturer"=>$value->manufacturer,
                        "type"=>$value->type,
                        "note"=>$value->note,
                        "pak"=>trim($value->pak),
                        "html5"=> $course->html5,
                        "safety"=>$enrollment->isSafety,
                        "short_description"=>$value->shortDescription,
                        "status"=>"Active to Enroll",
                    	"scorm_registration_id"=>$enrollment->scorm_registration_id,
                        "end_at"=>$enrollment->end_at
                    );
                    array_push($result, $data);
                }
            } else {
                $data = array(
                        "id"=>$enrollment->id,
                        "course_id"=>$course->id,
                        "name"=>$course->name,
                        "manufacturer"=>$course->manufacturer,
                        "type"=>$course->type,
                        "note"=>$course->note,
                        "pak"=>trim($course->pak),
                        "html5"=>$course->html5,
                        "safety"=>$enrollment->isSafety,
                        "short_description"=>$course->shortDescription,
                        "progress"=>$enrollment->progress,
                        "status"=>ucfirst($enrollment->status),
                        "end_at"=>$enrollment->end_at,
                		"scorm_registration_id"=>$enrollment->scorm_registration_id,
                        "enrolled_at"=>date($enrollment->created_at)
                    );
                array_push($result, $data);
            }
            
        }

        // Add additional recommend course to user's library
        $paks = array();
        $courses = array();
        foreach ($result as $data) {
            if(!in_array($data['pak'], $paks)){
                array_push($paks, trim($data['pak']));
            }
            array_push($courses,$data['course_id']);
        }
        foreach ($paks as $key => $value) {
            $ids = Course::where('pak','=',$value)->lists('id')->toArray();
            foreach ($ids as $id) {
                if(!in_array($id, $courses)){
                    $course = Course::find($id);
                    $price =  Price::where('course_id',$id)->orderBy('length')->first(); //priority
                    $data = array(
                        "status"=>"Recommend to buy",
                        "course_id"=>$course->id,
                        "name"=>$course->name,
                        "manufacturer"=>$course->manufacturer,
                        "type"=>$course->type,
                        "note"=>$course->note,
                        "pak"=>trim($course->pak),
                        "html5"=>$course->html5,
                        "short_description"=>$course->shortDescription
                    );
                    if($price){
                        $data['price'] = $price->price;
                        $data['idSale'] = $price->id;
                    }
                    array_push($result,$data);
                }
            }
        }
        //print_r($paks);
        return json_encode($result);
    }
    /**
     * save to my course list
     *
     * @param  string  $email
     * @return Response
     */
    public static function addToMyCourses($idCourse,$idUser)
    {   
        $data = array(
            'user_id'=>$idUser,
            'course_id'=>$idCourse
        );
        $mycourse = MyCourses::firstOrCreate($data);
        return $mycourse->id;
    }
    /**
     * unsave from my course list
     *
     * @param  string  $email
     * @return Response
     */
    public static function removeFromMyCourses($idCourse,$idUser)
    {   
        $app = \Slim\Slim::getInstance();
        if (!User::find($idUser)) {
            $app->response->setStatus(400);
            return "User does not exist";
        }
        if (!Course::find($idCourse)) {
            $app->response->setStatus(400);
            return "Course does not exist";
        }
        if(Enrollment::where('user_id',$idUser)->where('course_id',$idCourse)->count()){
            $app->response->setStatus(400);
            return "You already enrolled this course";
        }

        MyCourses::where('user_id',$idUser)->where('course_id',$idCourse)->delete();
        return "success";
    }
    /**
     * get full course list with user enroll info
     *
     * @param  string  $email
     * @return Response
     */
    public static function getCourses($idUser)
    {   
        $app = \Slim\Slim::getInstance();
        if (!User::find($idUser)) {
            $app->response->setStatus(400);
            return "User does not exist";
        }
        $courses = Course::where('isPublished',1)->get();
        foreach ($courses as &$value) {
            unset($value->isPublished);
            $enrollment = User::find($idUser)->enrollment()->where('course_id',$value->id)->first();
            unset($enrollment->user_id);
            unset($enrollment->course_id);
            $value->enrollment = $enrollment;
            $value->saved = MyCourses::where('user_id',$idUser)->where('course_id',$value->id)->count();
            //$value->info = Course::find($value->idCourse);
        }
        return json_encode($courses);
    }
    
    /**
     * @api {get} /users/get Get all users
     * @apiName Get all Active Users
     * @apiGroup User
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam  (url Parameter) {Number} idUser Users unique ID.
     *
     * @apiError 400 Input Invalid. This will happen if the param is missing or not the valid format.
     * @apiError 404 Not found. This will happen if the role id/user id/group id is not in our system.
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   [
     *   {
     *    "id": 2,
     *    "last_name": <last name>,
     *    "first_name": <first_name>,
     *    "email": <email>,
     *    "last_login": "2015-11-11 00:00:00",
     *    "account_creation": "2015-11-11 00:00:00"
     *   }
     *   ]
     *
     */
    public static function getAllUsers(){
    	$users = User::where('active',1)->get();
    	$tmpUsers = User::where('email',"")->get();
    	// per requirements - filtering out superadmins
    	 foreach ($users as $key => $user) {
    	 	if (AuthController::isSuperadmin($user->id)) {
    	 	//	unset($users[$key]);
    	 	} else {
	    		unset($user->phone);
	    		unset($user->theme);
	    		unset($user->active);
	    		unset($user->address);
	    		unset($user->city);
	    		unset($user->province);
	    		unset($user->country);
	    		unset($user->zip_code);
	    		unset($user->updated_at);
	    		unset($user->password);
	    		unset($user->avatar);
	    		unset($user->username);
	    		$tmpUsers->add($user);
    	 	}
    	}

    	return $tmpUsers->toJson();
    }
    

    /**
     * get full group list with user enroll info
     *
     * @param  string  $email
     * @return Response
     */
    public static function getGroups($idUser){   
        $user = self::findUser($idUser);   
        $groups = $user->groups;
        foreach ($groups as $key => &$value) {
            $value['role_id'] = $value->pivot->role_id;
            $value['role'] = Role::find($value->pivot->role_id)->name;
            unset($value->pivot);
        }
        return $groups;
    }
    /**
     * @api {get} /users/:idUser/invitations Get all invitations
     * @apiName Get all invitations
     * @apiGroup User
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam  (url Parameter) {Number} idUser Users unique ID.
     * 
     * @apiError 400 Input Invalid. This will happen if the param is missing or not the valid format.
     * @apiError 404 Not found. This will happen if the role id/user id/group id is not in our system.
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   [
     *   {
     *    "id": 2,
     *    "sender_id": 12,
     *    "group_id": 1,
     *    "receiver_id": 5,
     *    "invited_at": "2015-11-11 00:00:00",
     *    "group_jointed_at": null,
     *    "group_name": "General",
     *    "sender_name": "afd adf"
     *   }
     *   ]
     * 
     */ 
    public static function getInvitations($idUser){
        $user = self::findUser($idUser);   
        $invitations = $user->invitations; 
        foreach ($invitations as $key => $value) {
            $value['group_name'] = Group::find($value->group_id)->name;
            $sender =  User::find($value->sender_id);
            $value['sender_name'] = $sender->first_name." ".$sender->last_name;
        }
        return $invitations;
    }
            
}




?>