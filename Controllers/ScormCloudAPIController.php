<?php
class ScormCloudAPIController {
	public static $scromCloudUrl=SCORMCLOUDURL;
	public static $scormCloudSecretKey=SCORMCLOUDSECRETKEY;
	public static $scromCloudAppId=SCORMCLOUDAPPID;
	public static $scormCloudMgmtSecretKey=SCORMCLOUDAPPMGMTSECRETKEY;
	public static $scromCloudMgmtAppId=SCORMCLOUDAPPMGMTID;
	public static $websiteLink=WEBSITELINK;
	public static $apiLink=APILINK;
	public static $scormcloudorigin;
	
	public static function ping(){
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService = new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId,
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin);
			$debugService = $ScormService->getdebugService();
			if ($debugService->CloudPing()){
				return json_encode("success");
			} else {
				return json_encode("failure");
			}
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return json_encode("failure");
		}
	}
	
	public static function authPing(){
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService = new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId,
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin);
			$debugService = $ScormService->getdebugService();
			if ($debugService->CloudAuthPing()){
				return json_encode("success");
			} else {
				return json_encode("failure");
			}
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return json_encode("failure");
		}
	}
	
	public static function register($idCourse,$idUser) {
		$app = \Slim\Slim::getInstance();
		if (!User::find($idUser)) {
			$app->response->setStatus(400);
			return json_encode("User does not exist");
		}
		if (!Course::find($idCourse)) {
			$app->response->setStatus(400);
			return json_encode("Course does not exist");
		}
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService = new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId, 
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin);
			$regService = $ScormService->getRegistrationService();
			$courseService = $ScormService->getCourseService();
			$regId = uniqid(rand(), true);
			$user = User::find($idUser);
			$course = Course::find($idCourse);
			if ($courseService->Exists($course->scorm_id))			
				$regService->CreateRegistration($regId, $course->scorm_id, $idUser, $user->first_name, $user->last_name, $user->email);
			else 
				return json_encode("Course cannot be found in Scorm Cloud: " . $course->scorm_id);			
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return "failure";
		}
		//TO-DO: We need to save this Regisration ID, probably in enrollment table? not sure
		return json_encode($regId);
	}
	
	public static function createApp($appName){
		$app = \Slim\Slim::getInstance();
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService = 
				new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId, 
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin, null, 
					ScormCloudAPIController::$scromCloudMgmtAppId, ScormCloudAPIController::$scormCloudMgmtSecretKey);
			$appService = $ScormService->getApplicationService();
			$appService->CreateApplication($appName);
			//TO-DO: Find out how and where to save or even if we need it
			return json_encode("Success: ");
			
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return json_encode("failure");
		}
	}
	
	public static function courseExists($idCourse){
		$app = \Slim\Slim::getInstance();
	
		if (!Course::find($idCourse)) {
			$app->response->setStatus(400);
			return json_encode("Course does not exist");
		} else {
			$course = Course::find($idCourse);
		}
	
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService =
			new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId,
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin, null,
					ScormCloudAPIController::$scromCloudMgmtAppId, ScormCloudAPIController::$scormCloudMgmtSecretKey);
			$courseService = $ScormService->getCourseService();
			if ($courseService->Exists($course->scorm_id)){
				return json_encode("Success: ");
			}else{
				return json_encode("failure: ");
			}
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return json_encode("failure");
		}
	}
	
	public static function courseDelete($idCourse){
		$app = \Slim\Slim::getInstance();
	
		if (!Course::find($idCourse)) {
			$app->response->setStatus(400);
			return json_encode("Course does not exist");
		} else {
			$course = Course::find($idCourse);
		}
	
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService =
			new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId,
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin, null,
					ScormCloudAPIController::$scromCloudMgmtAppId, ScormCloudAPIController::$scormCloudMgmtSecretKey);
			$courseService = $ScormService->getCourseService();
			if ($courseService->Exists($course->scorm_id)){
				$courseService->DeleteCourse($course->scorm_id);
				return json_encode("Success: ");
			}else{
				return json_encode("failure: ");
			}
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return json_encode("failure");
		}
	}
	
	public static function upload($idCourse){
		$app = \Slim\Slim::getInstance();
		
		if (!Course::find($idCourse)) {
			$app->response->setStatus(400);
			return json_encode("Course does not exist");
		} else {
			$course = Course::find($idCourse);
		}
		
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService =
			new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId,
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin, null,
					ScormCloudAPIController::$scromCloudMgmtAppId, ScormCloudAPIController::$scormCloudMgmtSecretKey);
			$courseService = $ScormService->getCourseService();
			$courseService->ImportCourse($idCourse, $course->scrom_zip_file_path);
			// TODO: Save course id to course table if different
			return json_encode("Success: ");
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return json_encode("failure");
		}
	}
	

	public static function getPreviewUrl($idCourse){
		$app = \Slim\Slim::getInstance();
		
		if (!Course::find($idCourse)) {
			$app->response->setStatus(400);
			return json_encode("Course does not exist");
		} else {
			$course = Course::find($idCourse);
		}
		
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService =
			new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId,
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin, null,
					ScormCloudAPIController::$scromCloudMgmtAppId, ScormCloudAPIController::$scormCloudMgmtSecretKey);
			$courseService = $ScormService->getCourseService();
			
			if ($courseService->Exists($course->scorm_id)){
				$previewURL = $courseService->GetPreviewUrl($course->scorm_id, "");
				return json_encode($previewURL);
			} else {
				return json_encode("failure");
			}
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return json_encode("failure");
		}
	}
		
	public static function registrationExists($idRegistration){
		$app = \Slim\Slim::getInstance();
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService =
			new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId,
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin, null,
					ScormCloudAPIController::$scromCloudMgmtAppId, ScormCloudAPIController::$scormCloudMgmtSecretKey);
			$registrationService = $ScormService->getRegistrationService();
			if ($registrationService->Exists($idRegistration)) {
				return json_encode("success");
			} else {
				return json_encode("failure");
			}
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return json_encode("failure");
		}
	}	
	
	public static function getLaunchUrl($idRegistration){
		$app = \Slim\Slim::getInstance();
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService =
			new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId,
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin, null,
					ScormCloudAPIController::$scromCloudMgmtAppId, ScormCloudAPIController::$scormCloudMgmtSecretKey);
			$registrationService = $ScormService->getRegistrationService();
			if ($registrationService->Exists($idRegistration)) {
				$launchURL = $registrationService ->GetLaunchUrl($idRegistration, ScormCloudAPIController::$apiLink . "/scormcloud/registration/" . $idRegistration . "/result");
				return json_encode($launchURL);
			} else {
				return json_encode("failure");
			}
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return json_encode("failure");
		}
	}
	
	public static function getRegistrationDetail($idRegistration){
		$app = \Slim\Slim::getInstance();
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService =
			new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId,
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin, null,
					ScormCloudAPIController::$scromCloudMgmtAppId, ScormCloudAPIController::$scormCloudMgmtSecretKey);
			$registrationService = $ScormService->getRegistrationService();
			if ($registrationService->Exists($idRegistration)) {
				$regDetails = $registrationService->GetRegistrationDetail($idRegistration);
				return json_encode($regDetails);
			} else {
				return json_encode("failure");
			}
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return json_encode("failure");
		}
	}
	
	public static function deleteRegistration($idRegistration){
		$app = \Slim\Slim::getInstance();
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService =
			new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId,
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin, null,
					ScormCloudAPIController::$scromCloudMgmtAppId, ScormCloudAPIController::$scormCloudMgmtSecretKey);
			$registrationService = $ScormService->getRegistrationService();
			if ($registrationService->Exists($idRegistration)) {
				$registrationService->DeleteRegistration($idRegistration);
				return json_encode("success");
			} else {
				return json_encode("failure");
			}
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return json_encode("failure");
		}
	}
	
	public static function getRegistrationList(){
		$app = \Slim\Slim::getInstance();
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService =
			new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId,
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin, null,
					ScormCloudAPIController::$scromCloudMgmtAppId, ScormCloudAPIController::$scormCloudMgmtSecretKey);
			$registrationService = $ScormService->getRegistrationService();
			$arrayResults = $registrationService->GetRegistrationListResults(null, null, 2);
			return json_encode($arrayResults);	
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return json_encode("failure");
		}
	}
	
	public static function getRegistrationResult($idRegistration){
		$app = \Slim\Slim::getInstance();
		try{
			$scormcloudorigin=ScormEngineUtilities::getCanonicalOriginString("IgnitorLabs", "Ignitor Portal", "Version 2.0");
			$ScormService =
			new ScormEngineService(ScormCloudAPIController::$scromCloudUrl, ScormCloudAPIController::$scromCloudAppId,
					ScormCloudAPIController::$scormCloudSecretKey, ScormCloudAPIController::$scormcloudorigin, null,
					ScormCloudAPIController::$scromCloudMgmtAppId, ScormCloudAPIController::$scormCloudMgmtSecretKey);
			$registrationService = $ScormService->getRegistrationService();
			if ($registrationService->Exists($idRegistration)) {
				$regResult = $registrationService->GetRegistrationResult($idRegistration, 2, 1);
				$json_result = json_encode(simplexml_load_string($regResult), JSON_PRETTY_PRINT);
				$data = array (
					"action"=>"get",
					"type"=>"scorm-cloud",
					"source"=>"api",
					"source_version"=>"1.0",
					"description"=>$json_result,
					"ip"=>$_SERVER['REMOTE_ADDR'],
					"browser_type"=>$_SERVER['HTTP_USER_AGENT'],
					"referral"=>$_SERVER['HTTP_REFERER'],
					"auth_token"=>$app->request->headers->get('X_Authorization')
				);
				$log = Log::create($data);
				$log->save();
				$app->redirect(ScormCloudAPIController::$websiteLink . "/library");
				// return "<html><head><script type=\"text/javascript\">window.location=\"" . ScormCloudAPIController::$websiteLink . "/library\"</script></head><body></body></html>";
			} else {
				return json_encode("failure");
			}
		}catch(Exception $e) {
			$app->halt("400",json_encode($e->getMessage()));
			return json_encode("failure");
		}
	}
}
?>