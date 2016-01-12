<?php
class FileController{
	public static $profileAddress=USER_AVATAR_DOCUMENTPATH;
	public static $manufactureLogo=MANUFACTURE_LOGO_DOCUMENTPATH;
	public static $thumbnailAddress=COURSE_THUMBNAIL_DOCUMENTPATH;
	public static $courseAddress=COURSEDOCUMENTPATH;
	public static $maxSize = 2048000;
	public static function uploadAvatar($idUser){
		$app = \Slim\Slim::getInstance();
		$expensions= array("jpeg","jpg","png");
		if(!User::find($idUser)){
			$app->halt('401');
		}
		if(!isset($_FILES['photo'])){
			$app->halt('400',json_encode("Cannot find Image"));
		}
		$type = explode('.',$_FILES['photo']['name']);
		$file_ext=strtolower(end($type)); 
		if(in_array($file_ext,$expensions)=== false){
			$app->halt('400',json_encode("Extension not allowed, please choose a JPEG or PNG file."));
		}
		if($_FILES['photo']['size'] > FileController::$maxSize){
			$app->halt('400',json_encode("Image is too large."));
		}
		$image = getimagesize($_FILES['photo']['tmp_name']);
		if($image[0] > 500 || $image[1] > 500){
			$app->halt('400',json_encode("Image has to be less than or equal to 500px * 500px."));
		}
		move_uploaded_file($_FILES['photo']['tmp_name'],FileController::$profileAddress."/".$idUser);
		return json_encode("http://".$_SERVER['HTTP_HOST'].'/'.DOCUMENTPATH.'/users/avatar/'.$idUser);
	}
	public static function downloadCourseThumbnail($idCourse){
		$app = \Slim\Slim::getInstance();
		$file = FileController::$thumbnailAddress."/".$idCourse.'.png';
	    self::download($file);
	}
	public static function downloadCourse($idCourse){
		$app = \Slim\Slim::getInstance();
		$file = FileController::$courseAddress."/".$idCourse.'.zip';
	    self::download($file);
	}
	private static function getFileMIMEType($filename){
		$mime_types = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            //font
            'woff' => 'application/x-font-woff',
            'woff2' => 'application/x-font-woff2',
            'ttf' => 'application/x-font-ttf',
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );
		$tmp = explode('.', $filename);
		$file_extension = end($tmp);
        $ext = strtolower($file_extension);
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        }
        else {
            return 'application/octet-stream';
        }
	}

	public static function readCourse($idCourse,$page = "index.html"){
		$app = \Slim\Slim::getInstance();
		if(is_array($page)) $page = implode("/", $page);
		$file = FileController::$courseAddress."/".$idCourse."/".$page;
		if( file_exists($file) ){
		    $app->response->headers->set('Content-Type',  self::getFileMIMEType($page));
			readfile($file);
		} else {
			$app->response->headers->set('Content-Type', 'text/html');
	        $app->render(
			    'course_launch_404.php'
			);
	    }
	}
	public static function readAvatar($idUser){
		$app = \Slim\Slim::getInstance();
		$file = FileController::$profileAddress."/".$idUser;
		if( file_exists($file) ){
		    $app->response->headers->set('Content-Type',  'image/png');
			readfile($file);
		} else {
			$app->response->headers->set('Content-Type', 'image/png');
	        readfile(FileController::$profileAddress."/default");
	    }
	}
	public static function readThumb($idCourse){
		$app = \Slim\Slim::getInstance();
		$file = FileController::$thumbnailAddress."/".$idCourse.".png";
		if( file_exists($file) ){
		    $app->response->headers->set('Content-Type',  'image/png');
			readfile($file);
		} else {
			$app->response->headers->set('Content-Type', 'image/png');
	        readfile(FileController::$thumbnailAddress."/default.png");
	    }
	}
	public static function readManufactureLogo($name){
		$app = \Slim\Slim::getInstance();
		$file = FileController::$manufactureLogo."/".$name;
		if( file_exists($file) ){
		    $app->response->headers->set('Content-Type',  'image/png');
			readfile($file);
		} else {
			$app->halt("404");
	    }
	}
	public static function downloadPDFViewer(){
		echo FileController::$courseAddress;
	}
	private static function download($file){
		$app = \Slim\Slim::getInstance();
	    if( file_exists($file) ){
	        $res = $app->response();
	        $res['Content-Description'] = 'File Transfer';
	        $res['Content-Type'] = 'application/octet-stream';
	        $res['Content-Disposition'] ='attachment; filename=' . basename($file);
	        $res['Content-Transfer-Encoding'] = 'binary';
	        $res['Expires'] = '0';
	        $res['Cache-Control'] = 'must-revalidate';
	        $res['Pragma'] = 'public';
	        $res['Content-Length'] = filesize($file);
	        readfile($file);
	    } else {
	        $app->response->setStatus(404);
	        echo "File not found.";
	    }
	}
}
?>