<?php
class EmailController{
	public static function sendErrorToAdmin(){

	}
	public static function newUserConfirmation($idUser,$password,$link){
		$app = \Slim\Slim::getInstance();
        $user = User::find($idUser);
        $view = $app->view();
        $view->setData(array(
            'username' => $user->email,
            'password' => $password,
            'link'=> $link
        ));
        $body = $view->fetch('new_account_confirmation.php');
        $message = Swift_Message::newInstance('Activate your Account')
                    ->setFrom(array(MAILACCOUNT => MAILUSERNAME))
                    ->setTo(array($user->email => $user->first_name.",".$user->last_name))
                    ->setBody($body)
                    ->setContentType("text/html");
        $result = $app->mailer->send($message);
	}
	public static function newUserWelcome($idUser){
		$app = \Slim\Slim::getInstance();
        $user = User::find($idUser);
        $view = $app->view();
        $view->setData(array(
            'username' => $user->first_name,
            'id' => $user->id,
            'link' => WEBSITELINK.'/login',
        ));
        $body = $view->fetch('new_account_welcome.php');
        $message = Swift_Message::newInstance('Welcome to Ignitor labs')
                    ->setFrom(array(MAILACCOUNT => MAILUSERNAME))
                    ->setTo(array($user->email => $user->first_name.",".$user->last_name))
                    ->setBody($body)
                    ->setContentType("text/html");
        $result = $app->mailer->send($message);
	}
    public static function changePassword($idUser,$newPassword){
        $app = \Slim\Slim::getInstance();
        $user = User::find($idUser);
        $view = $app->view();
        $view->setData(array(
            'username' => $user->first_name,
            'password' => $newPassword
            ));
        $body = $view->fetch('reset_password.php');
        $message = Swift_Message::newInstance('Ignitor Labs - Password Reset')
                    ->setFrom(array(MAILACCOUNT => MAILUSERNAME))
                    ->setTo(array($user->email => $user->first_name.",".$user->last_name))
                    ->setBody($body)
                    ->setContentType("text/html");

        $result = $app->mailer->send($message);
        return $result;
    }
	public static function changeEmail($idUser,$newEmail,$link){
		$app = \Slim\Slim::getInstance();
        $user = User::find($idUser);
        $view = $app->view();
        $view->setData(array(
            'username' => $user->first_name,
            'oldEmail'=> $user->email,
            'newEmail'=> $newEmail,
            'link'=> $link
        ));
        $body = $view->fetch('change_email.php');
        $message = Swift_Message::newInstance('Change your Ignitor labs email')
                    ->setFrom(array(MAILACCOUNT => MAILUSERNAME))
                    ->setTo(array($newEmail => $user->first_name.",".$user->last_name))
                    ->setBody($body)
                    ->setContentType("text/html");
        return $app->mailer->send($message);
	}
    public static function changeEmailBounce(){
        $responses = self::emailDeliverFailBySubject('Change your Ignitor labs email');
        foreach ($responses as $key => $value) {
            $tmp = Tmp_Email::where('tmp_email','=',$value)->first();
            if($tmp){
                self::changeEmailFail($tmp->user_id);
                $tmp->delete();
            } 
        }
    }
    public static function emailDeliverFailBySubject($subject){
        $mailcnf = "outlook.office365.com:993/imap/ssl/novalidate-cert";
        $username = MAILACCOUNT;
        $pw = MAILPASSWORD;
        $conn_str = "{".$mailcnf."}INBOX";

        $inbox = imap_open($conn_str,$username,$pw) or die('Cannot connect to mail: ' . imap_last_error());
        /* grab emails */
        $emails = imap_search($inbox,'SUBJECT "Undeliverable: '.$subject.'"');

        $failedInfo = [];
        /* if emails are returned, cycle through each... */
        if($emails) {
            /* for every email... */
            foreach($emails as $email_number) {
                /* get information specific to this email */
                $body = imap_fetchbody($inbox,$email_number,2);
                $list = split('; ', $body);
                $sender = $list[2];
                $sender_email = explode("\n", $sender)[0];
                array_push($failedInfo, trim($sender_email));
            }
        }
        /* close the connection */
        imap_close($inbox); 
        return $failedInfo;
    }
	public static function changeEmailSuccess($idUser){
		$app = \Slim\Slim::getInstance();
        $user = User::find($idUser);
        $view = $app->view();
        $view->setData(array(
            'id' => $user->id,
            'link' => WEBSITELINK.'/login',
            'email'=> $user->email
        ));
        $body = $view->fetch('change_email_success.php');
        $message = Swift_Message::newInstance('Successfully changed your Ignitor labs email')
                    ->setFrom(array(MAILACCOUNT => MAILUSERNAME))
                    ->setTo(array($user->email => $user->first_name.",".$user->last_name))
                    ->setBody($body)
                    ->setContentType("text/html");
        $result = $app->mailer->send($message);
	}
    public static function changeEmailFail($idUser){
        $app = \Slim\Slim::getInstance();
        $user = User::find($idUser);
        $view = $app->view();
        $view->setData(array(
            'username' => $user->first_name,
            'email'=> $user->email
        ));
        $body = $view->fetch('error_change_email.php');
        $message = Swift_Message::newInstance('Fail to changed your Ignitor labs email')
                    ->setFrom(array(MAILACCOUNT => MAILUSERNAME))
                    ->setTo(array($user->email => $user->first_name.",".$user->last_name))
                    ->setBody($body)
                    ->setContentType("text/html");
        $result = $app->mailer->send($message);
    }
    public static function noticificationReminder($idUser){
        $app = \Slim\Slim::getInstance();
        $user = User::find($idUser);
        $view = $app->view();
        $view->setData(array(
            'username' => $user->first_name,
            'link'=> WEBSITELINK.'/login'
        ));
        $body = $view->fetch('new_noticification_reminder.php');
        $message = Swift_Message::newInstance('You got a new message')
                    ->setFrom(array(MAILACCOUNT => MAILUSERNAME))
                    ->setTo(array($user->email => $user->first_name.",".$user->last_name))
                    ->setBody($body)
                    ->setContentType("text/html");
        $result = $app->mailer->send($message);
    }
}
?>