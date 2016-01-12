<?php
class RecommendController{   
    public static function getCompanionCourses($idCourse,$cartArr = null){  
        if(is_null($cartArr)){
            $cartArr = [$idCourse];
        }
        $app = \Slim\Slim::getInstance(); 
        $data = Course::find($idCourse);
        if (!$data) { //course not exist 
            $app->response->setStatus(400);
            return json_encode("course not exist");
        } else {
            $result = Course::has('price')->where('name','like',$data->name)->whereNotIn('id',$cartArr)->get();
            $array = [];
            foreach ($result as $key => $value) {
                $course = Course::find($value->id);
                $price = Course::find($value->id)->price()->orderBy('length')->first(); //life time is priority
                $price['type'] = $course->type;
                $price['name'] = $course->name;
                array_push($array, $price);
            }
            return json_encode($array);
        }
    }
    public static function getManufacturerCourses($idCourse,$cartArr = null){
        if(is_null($cartArr)){
            $cartArr = [$idCourse];
        }
        $app = \Slim\Slim::getInstance();
        $data = Course::find($idCourse);
        if (!$data) { //course not exist 
            $app->halt(400,'course not exist');
        } else {
            $result = Course::has('price')->where('manufacturer','like',$data->manufacturer)->whereNotIn('id',$cartArr)->get();
            $array = [];
            foreach ($result as $key => $value) {
                $course = Course::find($value->id);
                $price = Course::find($value->id)->price()->orderBy('length')->first(); //life time is priority
                $price['type'] = $course->type;
                $price['name'] = $course->name;
                array_push($array, $price);
            }
            return json_encode($array);
        }
    }
    public static function getRecommend($idCourse,$cartArr = null){
        $recommend = array_merge(json_decode(RecommendController::getCompanionCourses($idCourse,$cartArr)),json_decode(RecommendController::getManufacturerCourses($idCourse,$cartArr)));
        $withoutDuplicate = array_unique($recommend,SORT_REGULAR);
        return json_encode(array_values($withoutDuplicate));
    }
    public static function getArrRecommend($courseArr){
        $result = [];
        foreach ($courseArr as $key => $course) {
            $result = array_merge($result,json_decode(self::getRecommend($course,$courseArr)));
        }
        $withoutDuplicate = array_unique($result,SORT_REGULAR);
        return json_encode(array_values($withoutDuplicate));
    }
    public static function shoppingCartRecommend(){
        $app = \Slim\Slim::getInstance();
        $data = $app->request->post();
        if(!isset($data['items']) || is_null($data['items']) || empty($data['items'])){
            $app->halt('400');
        }
        $cartArr = json_decode($data['items']);
        $courseArr = [];
        foreach ($cartArr as $key => $value) {
            StoreController::isValid($value);
            array_push($courseArr, $value->course_id);
        }
        return self::getArrRecommend($courseArr);
    }
}
?>