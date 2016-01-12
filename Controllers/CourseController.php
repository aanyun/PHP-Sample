<?php
class CourseController
{   
    private static function getCourseType($id)
    {   
        $types = array(
        0=>'E-Course',
        1=>'RefPak',
        2=>'3D-Viewer',
        );
        return $types[$id];
        
    }
    /**
     * @api {get} /membership/:id Get Courses that Belong to a Membership
     * @apiName Get Courses that Belong to a Membership
     * @apiGroup Course
     * @apiDescription Returns a list of courses that belong to the membership specified by the id parameter.
     * @apiParam {id} id Membership unique ID.
     * @apiError 404 Membership not found.
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   [
     *       {
     *           "id": 45951,
     *           "code": "Test",
     *           "name": "TurboChef: High h Conveyor C",
     *           "manufacturer": "TurboChef",
     *           "shortDescription": null,
     *           "description": null,
     *           "note": null,
     *           "isPublished": 1,
     *           "time": 90,
     *           "type": "course",
     *           "safety": 1,
     *           "html5": 1,
     *           "video": null,
     *           "thumbnail": "http://localhost:9090/ignitor-api/courses/thumbnail/45951"
     *       }
     *   ]    
     */
    public static function getCoursesByMembership($idMembership) {
        $app = \Slim\Slim::getInstance();

        $data = Course::find($idMembership);
        if (!$data || !$data->isPublished) {  //Course does not exist or not published (Null Check)
            $app->halt(404,json_encode("Course does not exist"));
        } 

        $catalog = Catalog::find($idMembership);
        if (!$catalog) {  //Catalog does not exist or not published (Null Check)
            $app->halt(404,json_encode("Membership not found"));
        }

        return $catalog->course; 
    }
    /**
     * @api {get} /courses/:id Get Course Info
     * @apiName Get Course Info
     * @apiGroup Course
     * @apiDescription Get details on the course specified by the id parameter.
     *
     * @apiParam {id} id Course unique ID.
     * @apiError 404 Course not found.
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   [
     *       {
     *           "id": 45951,
     *           "code": "Test",
     *           "name": "TurboChef: High h Conveyor C",
     *           "manufacturer": "TurboChef",
     *           "shortDescription": null,
     *           "description": null,
     *           "note": null,
     *           "isPublished": 1,
     *           "time": 90,
     *           "type": "course",
     *           "safety": 1,
     *           "html5": 1,
     *           "video": null,
     *           "thumbnail": "http://localhost:9090/ignitor-api/courses/thumbnail/45951"
     *       }
     *   ]    
     */
    public static function getCourseDetails($idCourse,$published=true)
    {   
        $app = \Slim\Slim::getInstance();
        $v = $app->validata;
        $isNum = $v::numeric()->validate($idCourse);
        if(!$isNum){ //the id must be a number
            $app->halt("400");
        }
        $data = Course::find($idCourse);
        if (!$data) { 
            $app->halt('404',json_encode("Course does not exist"));
        }
        if($published && $data->isPublished == 0){
            $app->halt('404',json_encode("Course does not exist"));
        } 
        return $data;
        
    }
    /**
     * @api {get} /courses/:id/prices Get Course Sale Options
     * @apiName Get Course Sale Options 
     * @apiGroup Course
     * @apiDescription Get sale options of the course specified by the id parameter.
     *                 <p>Only works with a published course.</p> 
     *                 <p>One course might have multiple sale price options. 
     *                 This API will return all the available options. </p>
     * @apiParam {id} id Course unique ID.
     * @apiError 400 Course id invalid.
     * @apiError 404 Course not found.
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   [
     *       {
     *           "id": 9,
     *           "course_id": 1,
     *           "price": null,
     *           "length": 365,
     *           "note":"one year subscription"
     *       }
     *   ]    
     */
    public static function getCoursePrices($idCourse){
        $data = self::getCourseDetails($idCourse);
        $prices =  Price::where('course_id',$idCourse)->get();
        return $prices->toJson();
    }
    /**
     * @api {get} /courses/:id/prerequisite Get Course Prerequisite
     * @apiName Get Course Prerequisite
     * @apiGroup Course
     * @apiDescription Returns a collection of prerequisite courses belonging to the course specified by the id parameter.
     *                 <p>Even if the course is not public, you can still get the prerequisite info about this course.</p>
     * @apiParam {Number} id Course unique ID.
     * @apiError 404 Course not found.
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   [
     *       {
     *           "id": 45951,
     *           "code": "Test",
     *           "name": "TurboChef: High h Conveyor C",
     *           "manufacturer": "TurboChef",
     *           "shortDescription": null,
     *           "description": null,
     *           "note": null,
     *           "isPublished": 1,
     *           "time": 90,
     *           "type": "course",
     *           "safety": 1,
     *           "html5": 1,
     *           "video": null,
     *           "thumbnail": "http://localhost:9090/ignitor-api/courses/thumbnail/45951"
     *       }
     *   ]    
     */
    public static function getCoursePrerequisites($idCourse){
        self::getCourseDetails($idCourse,false);
        return Course::find($idCourse)->prerequisite;
    }

    public static function adminGetCourseDetails($idCourse){   
        $app = \Slim\Slim::getInstance();
        $data = Course::find($idCourse);
        if (!$data) {
            $app->response->setStatus(400);
            return 0;
        } else {
            return $data->toJson();
        }        
    }

    public static function getAll(){   
        $courses = Course::where('isPublished',1)
            ->get();
        return $courses->toJson();
    }

    /**
    * @api {get} /store/courses Get Available Courses for Sale
    * @apiName Get Available Courses for Sale
    * @apiGroup Course
    * @apiDescription Returns a collection of items that can be shown in the store.
    *                 <p>Return item with one purchase option.</p>
    *                 <p>Return the sale's option with the longest length. </p>
    * 
    * @apiSuccessExample {json} Success-Response:
    *   HTTP/1.1 200 OK
    *   [
    *    {
    *        "id": 1,
    *        "code": "Membership",
    *        "name": "Ignitor Labs: Membership",
    *        "pak": "Ignitor Labs Membership",
    *        "manufacturer": "Ignitor Labs",
    *        "shortDescription": null,
    *        "description": null,
    *        "note": null,
    *        "isPublished": 1,
    *        "time": null,
    *        "type": "membership",
    *        "safety": -1,
    *        "html5": 0,
    *        "video": null,
    *        "price": "795",
    *        "idSale": 9,
    *        "thumbnail": "http://localhost:9090/ignitor-api/courses/thumbnail/1"
    *    }
    *    ]
     */
    public static function getStore(){   
        $courses = Course::where('isPublished',1)
            ->get();
        foreach ($courses as &$value) {
            $price =  Price::where('course_id',$value->id)->orderBy('length')->first(); //priority
            if($price){
                $value['price'] = $price->price;
                $value['idSale'] = $price->id;
            }
        }
        return $courses->toJson();
    }
    /**
     * @api {get} /courses/:id/membership Check if a Course Belongs to any Membership
     * @apiName Check if a Course Belongs to any Membership
     * @apiGroup Course
     * @apiDescription Returns only one record of a membership this course belongs to, else return empty.
     *
     * @apiError 400 Not a course.
     * @apiError 404 Course not found.
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *    {
     *        "id": 1,
     *        "name": "Ignitor Labs: Membership",
     *        "type": "membership",
     *    }
     */
    public static function getCourseMembership($idCourse){
        $app = \Slim\Slim::getInstance();
        $course = self::getCourseDetails($idCourse,false);
        if($course->type=="membership" || $course->type=="bundle"){
            $app->halt("400",json_encode("Not a course"));
        }
        $membership = Course::find($idCourse)->catalog()->where('type','=','membership')->first();
        unset($membership['pivot']);
        unset($membership['created_at']);
        unset($membership['updated_at']);
        return $membership;
    }

    /**
     * @api {get} /courses/:id/bundle Check if a Course Belongs to any Bundle
     * @apiName Check if Course Belongs to any Bundle
     * @apiGroup Course
     * @apiDescription Returns only one record of a bundle this course belongs to, else return empty.
     *
     * @apiError 400 Not a course.
     * @apiError 404 Course not found.
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *    {
     *        "id": 11,
     *        "name": "Ignitor Labs: Bundle",
     *        "type": "bundle",
     *    }
     */
    public static function getCourseBundle($idCourse){
        $app = \Slim\Slim::getInstance();
        $course = self::getCourseDetails($idCourse,false);
        if($course->type=="membership" || $course->type=="bundle"){
            $app->halt("400",json_encode("Not a course"));
        }
        $bundle = Course::find($idCourse)->catalog()->where('type','=','bundle')->first();
        unset($bundle['pivot']);
        unset($bundle['created_at']);
        unset($bundle['updated_at']);
        return $bundle;
    }


    
    /**
     * @api {get} /courses/:id/bundle/detail Get Available Courses for bundle
     * @apiName Get Available Courses for bundle
     * @apiGroup Course
     * @apiDescription Returns a collection of items that can be shown in the a bundle.
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
		*	[
		*		{"id":26453,
		*		"code":"258010",
		*		"name":"E-Course: Alto-Shaam: Combitherm Electric Oven Steamer",
		*		"pak":"Combitherm Electric Oven Steamer",
		*		"manufacturer":"Alto-Shaam",
		*		"fuse_id":null,
		*		"fuse_status":"",
		*		"shortDescription":"short descr",
		*		"note":null,
		*		"isPublished":1,
		*		"time":180,"type":
		*		"course","safety":-1,
		*		"html5":0,"video":
		*		"https:\/\/player.vimeo.com\/video\/50304386",
		*		"scorm_id":"ScormTest5f4888ea-f79b-4487-9346-6bc77c1a266d",
		*		"scrom_zip_file_path":null,
		*		"thumbnail":"http:\/\/localhost\/ignitor-api\/courses\/thumbnail\/26453",
		*		"price" : "450",
		*       "idSale" : "321"
		*		}
		*	]
     */
    public static function getCourseBundleDetail($idCourse){
    	$app = \Slim\Slim::getInstance();
    	$course = self::getCourseDetails($idCourse,false);
    	if(!($course->type=="bundle")){
    		$app->halt("400",json_encode("Not a course"));
    	}  	
    	
    	//$catalog = Catalog::where('id','=',$idCourse)->get();
    	$courseIds = Catalog::find($idCourse)->course->lists('id')->toArray();
    	
     	$result = [];
     	foreach ($courseIds as &$courseId) {
     		$course = Course::find($courseId);
     		
     		$price =  Price::where('course_id',$course->id)->orderBy('length')->first(); //priority
     		if($price){
     			$course['price'] = $price->price;
     			$course['idSale'] = $price->id;
     		}
     		array_push($result, $course);
     	}
    	return json_encode($result);
    }
    
    
   // $bins = Manager_Bin::where('user_id','=',$idUser)->get();
   // $result = [];
    
    
    public static function launchCourse($idCourse,$token,$page){
        $app = \Slim\Slim::getInstance();
        if (!Course::find($idCourse)) {
            $app->halt("404");
        }
        $auth = Auth_Token::where('token', '=', $token)->first();
        if (!$auth || 
            Enrollment::where('user_id','=',$auth->user_id)->where('course_id','=',$idCourse)->count()==0 ) {
            $app->response->headers->set('Content-Type', 'text/html');
            $app->render(
                'course_launch_401.php'
            );
            $app->stop();
        }
        FileController::readCourse($idCourse,$page);
    }
}
?>
