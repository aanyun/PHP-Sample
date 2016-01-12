<?php
class AuthController 
{
    public static function getToken($headers)
    {
        $token = $headers->get('X_Authorization');
        //some apache servers change underscores to dashes, so check both
        if (!$token)
           $token = $app->request->headers->get('X-Authorization');
        return ($token ? $token : false);
    }
    
    /**
     * Login
     *
     * @param  Request  $request
     * @return Response
     */
    public static function login()
    {   
        $app = \Slim\Slim::getInstance();
        $validata = $app->validata;
        $data = (object) $app->request->post();
        $emailValid = isset($data->email)&&$validata::email()->notEmpty()->validate($data->email);
        $passwordValid = isset($data->password)&&$validata::stringType()->notEmpty()->validate($data->password);

        if (!$emailValid||!$passwordValid){
            $app->response->setStatus(400);
            return json_encode("Invalid Input");
        }

        if(UserController::isExist($data->email)){
            if(UserController::isActive($data->email)){
                $user = User::where('email', 'like', $data->email)->first();
                if ($user->password == md5($data->password)){
                    $app->response->setStatus(200);
                    $token = md5(uniqid(rand(), true));
                    $response = array(
                        "id"=>$user->id,
                        "first_name"=>$user->first_name,
                        "last_name"=>$user->last_name,
                        "last_login"=>$user->last_login,
                        "token"=>$token,
    					"superadmin"=>self::isSuperadmin($user->id)
                        );
                    self::updateLoginInfo($user->id);
                    self::saveToken($user->id,$token,$app->request);
                    return json_encode($response);
                }
            }
        }
        $app->response->setStatus(401);
        return json_encode("Username or Password Incorrect");
    }
    protected static function updateLoginInfo($idUser){
        User::where('id', '=', $idUser)->update(array('last_login' => date("Y-m-d H:i:s")));
    }
    protected static function saveToken($idUser,$token,$request){
        $app = \Slim\Slim::getInstance();
        $data = array(
            "user_id"=>$idUser,
            "ip_address"=>$request->getIp(),
            "device_details"=>$request->getUserAgent()
        );
        $auth = Auth_Token::firstOrNew($data);
        $auth->token = $token;
        $auth->save();
    }
    
    protected static function removeToken($idUserLogout){
    	$app = \Slim\Slim::getInstance();
    	$data = array(
    			"user_id"=>$idUserLogout
    	);
    	$auth = Auth_Token::destroy($data);
    }
    
    
    public static function authentication($idUser,$token){
        if (!empty($token)) {
            $auth = Auth_Token::where('token', '=', $token)->first();
            $app = \Slim\Slim::getInstance();
            
            if ($auth&&$auth->user_id==$idUser){
                return 1;
            }
        }
        return 0;
    }
    public static function authorization($idUser,$role){
        $user = UserController::findUser($idUser);
        $roles = $user->roles->lists('id')->toArray();
        return in_array($role, $roles);
    }
    
    
    /**
     * @api {get} /admin/idUserLogin/login Login as different user
     * @apiName Let Ignitor Labs Super Admin login as different uer 
     * @apiDescription Returns a new token
     * @apiGroup Ignitor Super Admin
     * @apiHeader (Header) {String} X_Authorization Token
     * @apiParam  (url Parameter) {Number} idUserLogin Users unique ID.
     *
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 404 User not found.
     * @apiError 403 Permission denied. This will happen if the user is not a ignitor super admin.
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     [
	*			{
  	*				"id": 26,
 	*				 "first_name": "Billie Joe",
  	*				 "last_name": "Armstrong",
 	*				 "last_login": "2015-09-22 15:42:30",
 	*				 "token": "8c069cdffd2dde8626b44efae9c4c9a4"
	*			}
     *   ]
     */
    
    
    public static function adminLogin($idUserLogin){
    	$app = \Slim\Slim::getInstance();
    	$user = User::find($idUserLogin);
    	if (!$user){
    		$app->response->setStatus(400);
    		return "User does not exist";
    	} else {
    		$app->response->setStatus(200);
    		$token = md5(uniqid(rand(), true));
    		$response = array(
    				"id"=>$user->id,
    				"first_name"=>$user->first_name,
    				"last_name"=>$user->last_name,
    				"last_login"=>$user->last_login,
    				"token"=>$token,
    				"superadmin"=>self::isSuperadmin($user->id)
    		);
    		self::updateLoginInfo($user->id);
    		self::saveToken($user->id,$token,$app->request);
    		return json_encode($response);
    	}
    }
    
    
    /**
     * @api {get} /admin/idUserLogout/logout Logout a user
     * @apiName Let Ignitor Labs Super Admin force logout a user
     * @apiDescription  - Destroys the tocken
     * @apiGroup Ignitor Super Admin
     * @apiHeader (Header) {String} X_Authorization Token
     * @apiParam  (url Parameter) {Number} idUserLogout Users unique ID.
     *
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 404 User not found.
     * @apiError 403 Permission denied. This will happen if the user is not a ignitor super admin.
     * @apiSuccessExample {boolean} Success-Response:
     *     HTTP/1.1 200 OK
	 *     1
     */
       
    public static function adminLogout($idUserLogout){
    	$app = \Slim\Slim::getInstance();
    	$user = User::find($idUserLogout);
    	if (!$user){
    		$app->response->setStatus(400);
    		return "User does not exist";
    	} else {
    		$app->response->setStatus(200);
    		$auth = Auth_Token::where('user_id', '=', $idUserLogout)->first();
    		self::removeToken($auth->id);
    		return 1;
    	}
    }
    
    
    
    
    /**
     * @api {get} /admin/permissions Get Admin permission List
     * @apiName Get Ignitor Labs Super Admin's permissions
     * @apiDescription Returns a collection of permissions for a super admin
     * @apiGroup Ignitor Super Admin
     * @apiHeader (Header) {String} X_Authorization Token
     *
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 404 User not found.
     * @apiError 403 Permission denied. This will happen if the user is not a ignitor super admin.
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     [
     *       {
     *           "id": 1,
     *           "name": "course",
     *           "type": "all"
     *       },
     *       {
     *           "id": 3,
     *           "name": "user",
     *           "type": "all"
     *       },
     *       {
     *           "id": 8,
     *           "name": "promo",
     *           "type": "view"
     *       }
     *   ]
     */
    public static function permissions($idUser){
        $app = \Slim\Slim::getInstance();
        
//         $token = AuthController::getToken($app->request->headers);
//         $auth = Auth_Token::where('token', '=', $token)->first();
//         if (!$auth) {
//             $app->response->setStatus(401);
//             return 0;
//         }

        // $idUser = $auth->user_id;
        
        $user = UserController::findUser($idUser);
        $roles = $user->roles->lists('id')->toArray();
        $permissionList = [];
        foreach ($roles as $key => $value) {
            $list = Role::find($value)->permissions->toArray();
            foreach ($list as &$permission) {
                unset($permission['pivot']);
            }
            $permissionList = array_merge($permissionList, $list);
        }
        if(count($permissionList)==0){
            $app->halt('403');
        }
        return json_encode($permissionList);
    }
    public static function permission_check($idUser,$permission_id){
        $user = UserController::findUser($idUser);
        $roles = $user->roles->lists('id')->toArray();
        $permissionList = [];
        foreach ($roles as $key => $value) {
            $list = Role::find($value)->permissions->lists('id')->toArray();
            $permissionList = array_merge($permissionList, $list);
        }
		 return in_array($permission_id, $permissionList);
    }

    public static function isSuperadmin($idUserCheck){
    	$user  = UserController::findUser($idUserCheck);
    	if (!$user) return false; 
    	$roles = $user->roles->lists('id')->toArray();
    	if (count($roles)<1) return false;
    	foreach ($roles as $key => $value) {
            $rolename = Role::find($value)->name;
            if ($rolename=="SuperAdmin")
            	return true;
        }
		return false;
    }

    /**
     * @api {get} /admin/:id/reports Get Admin Report List
     * @apiName Get Ignitor Labs Super Admin's report list
     * @apiDescription Returns a collection of report's name of the admin by the id parameter
     * @apiGroup Ignitor Super Admin
     * @apiHeader (Header) {String} X_Authorization Token
     * @apiParam {Number} id Users unique ID.
     *                       Example Values: 123
     *
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 404 User not found.
     * @apiError 403 Permission denied. This will happen if the user do not have any permission to view any report.
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     [
     *       "company_purchase",
     *       "report_company_user"
     *     ]
     */
    public static function getReportList($idUser){
        $app = \Slim\Slim::getInstance();
        $per_json = self::permissions($idUser);
        $permissions = json_decode($per_json);
        $reports = [];
        foreach ($permissions as $key => $value) {
            if(strpos($value->name,"report_") === false){
                continue;
            }
            array_push($reports, str_replace("report_", "", $value->name));
        }
        if(count($reports)==0){
            $app->halt('403');
        }
        return json_encode($reports);

    }
}




?>