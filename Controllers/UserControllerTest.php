<?php

/*
 *  Author: Brijesh Patel
 *  You will run from ignitor-api folder as
 *   phpunit --bootstrap vendor/autoload.php Controllers
 */

require_once('UserController.php');

class UserControllerTest extends PHPUnit_Framework_TestCase
{
    public function testFindUser()
    {
		$userController = new UserController();
		$user=$userController->findUser(41);
		$this->assertEquals('Demo@ignitorlabs.com', $user->email);
		$this->assertEmpty($user->country);

		
    }
    public function testIsExist()
    {
    	$userController = new UserController();
 
    	$booleanYesNo=$userController->isExist("test@test.com");
    	$this->assertEquals(1, $booleanYesNo);
 
    	$booleanYesNo=$userController->isExist("doesnotexists@test.com");
    	$this->assertEquals(0, $booleanYesNo);
    	
    }
    
    public function testIsActive()
    {
    	$userController = new UserController();
    
    	$booleanYesNo=$userController->isActive("test@test.com");
    	$this->assertEquals(1, $booleanYesNo);
    
    	$booleanYesNo=$userController->isActive("doesnotexists@test.com");
    	$this->assertEquals(0, $booleanYesNo);
    	 
    }
    
    public function testgetMyCourses()
    {
    	$userController = new UserController();
    	$jsonMyCourses = $userController->getMyCourses(0, true);
    	$jsonMyCourses = json_decode($jsonMyCourses);
    	$this->assertEmpty($jsonMyCourses);
    }
    
}
?>