<?php
class PasswordController
{
	/**
     * Create a new User instance.
     *
     * @param  int  $idCourse
     * @param  int  $idCourse
     * @return int $idEnrollment
     */
    public static function request()
    {   
    	$app = \Slim\Slim::getInstance();
        $data = $app->request->post();
        $user = User::where('email',$data['email'])->first();
        if (!$user) {
            $app->response->setStatus(400);
            return json_encode("User does not exist");
        }

        $newpassword = self::updatePassword($user->id);
        $result = EmailController::changePassword($user->id,$newpassword);
        return $result;
	}

    public static function reset(){
        $app = \Slim\Slim::getInstance();
        $data = $app->request->post();

        $idUser = isset($data['idUser'])?$data['idUser']:null;
        $password = isset($data['password'])?$data['password']:null;
        $oldpassword = isset($data['oldpassword'])?$data['oldpassword']:null;
        $user = User::find($idUser);
        if (!$user){
            $app->response->setStatus(400);
            return;
        }

        if(!self::isValid($password,$user->email) || is_null($idUser) || is_null($oldpassword)){
            $app->response->setStatus(400);
            return;
        }

        if ($user->password == self::encryptPassword($oldpassword)){
            self::updatePassword($idUser,self::encryptPassword($password));
            $app->response->setStatus(200);
            return;
        } else {
            $app->response->setStatus(404);
            return;
        }
        
    }
    public static function isValid($password,$username){
        $app = \Slim\Slim::getInstance();
        $v = $app->validata;
        return $v::stringType()
        ->length(6,25)
        ->not($v::alpha())
        ->not($v::contains('password'))
        ->not($v::contains('123456'))
        ->not($v::contains('654321'))
        ->not($v::contains($username))
        ->validate($password);
    }
    public static function encryptPassword($password){
        return md5($password);
    }
    protected static function updatePassword($idUser,$password = Null){
        if (is_null($password)){
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
            $ori_password = substr( str_shuffle( $chars ), 0, 6 );
            $password = self::encryptPassword($ori_password);
        } else {
            $ori_password = $password;
        }
        $user = User::find($idUser);
        $user->password = $password;
        $user->save();
        return $ori_password;
    }

}
?>
