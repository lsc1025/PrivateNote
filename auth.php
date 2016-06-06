<?php

	session_start();
	
	if (!$_POST && !$_GET)
		header("Location: index.html"); // No direct access
	
	$auth = new Auth;
	$uid = md5($auth->getIP());
	
	if (array_key_exists('LOGIN', $_COOKIE)) {
		if (!array_key_exists('flag', $_COOKIE['LOGIN'])) {
			$_SESSION['flag'] = "NO";
		} else {
			foreach($_COOKIE['LOGIN'] as $k => $v) 
				$_SESSION[$k] = $v;
		}
	} else if (!array_key_exists('flag', $_SESSION))
		$_SESSION['flag'] = "NO";
	
	$pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
    $info_post = array();
	
	$return_pak = array(
		'token' => "fail",
		'message' => "",
		'tempUsrName' => "",
		'tempEmail' => "",
	);
	
	if ($_GET) { // log out process
		if ($_GET['cmd'] == "logout") {
			foreach ($_SESSION as $k => $v)
				setcookie("LOGIN[".$k."]", "", 1, '/projects/PrivateNotes');
			
			session_destroy();
			echo "Logged out";
			header("Location: index.html");
			die();
		}
	}
	
	$con = mysqli_connect("localhost", "cl60-prnotes", "cDq9VD9b!", "cl60-prnotes");
	
	if (mysqli_connect_error())
		echo "Internal Server Error, We will fix this ASAP!";
	
	if ($_POST['mode'] === "signUp") { // sign up process
		
		$return_pak['tempUsrName'] = $_POST["usrName_new"];
		$return_pak['tempEmail'] = $_POST["email"];
	
		if (!$_POST['usrName_new'] OR !$_POST['email'] OR !$_POST['psd_new'] OR !$_POST['repsd_new']){ // Server side error detection
			
			$return_pak['message'] = "Invalid submission!";						
		
		} else if (!preg_match($pattern, $_POST['email'])) {
			
			$return_pak['message'] .= "Invalid email address!";

		} else if (($_POST['psd_new'] === $_POST['repsd_new']) == false) {
			
			$return_pak['message'] .= "Passwords mismatch!";
			
		} else if (!$auth->charValidate($info_post, $con, $return_pak)){
				
			$return_pak['message'] .= "Invalid characters detected!";
			
		} else {
			if ($auth->dbValidate($info_post, $con, $return_pak)) {
				if ($auth->signUp($info_post, $con, $return_pak)) {
					$_SESSION['flag'] = "YES";
					$_SESSION['usrName'] = $info_post['usrName_new'];
					$auth->email($info_post);
					$return_pak['token'] = "success";
					$return_pak['message'] = "Success, redircting...";
				}
			}		
		}	
		
	} else if ($_POST['mode'] == "signIn") { // sign in process
		
		if (array_key_exists('key', $_POST)) {
			
			if ($_POST['usrName'] === $_SESSION['usrName'] || $_POST['key'] == "pass")
				$auth->redirect();
			
		} else if (!$_POST['usrName'] OR !$_POST['psd']){ // Server side error detection
			
			$return_pak['message'] = "Invalid submission!";						
		
		} else {
			
			if ($auth->charValidate($info_post, $con, $return_pak)) {
				if ($auth->logIn($info_post, $con, $return_pak)) {
					
					$return_pak['token'] = "success";
					if ($_POST['keep'] == "on")
						$auth->keepSignIn();
				
				}
			} else {
				$return_pak['message'] = "Invalid username or password!";
			}
			
			
		}
		
	} else if ($_POST['mode'] == "check") {
		
		$isLoggedIn = false;
		$isKept = false;
		if ((array_key_exists('LOGIN', $_COOKIE))) {
			if ($_COOKIE['LOGIN']['flag'] === "YES") {
				$isLoggedIn = true;
				$isKept = true;
			} else if ($_SESSION['flag'] == "YES")
				$isLoggedIn = true;
		}
			
		if ($isLoggedIn) {
			$return_pak['token'] = "success";
			$return_pak['message'] = "You now logged in as ".$_SESSION['usrName'];
			$return_pak['tempUsrName'] = $_SESSION['usrName'];
			if ($isKept)
				$return_pak['status'] = "Kept";
		}
		
	}
	
	echo json_encode($return_pak); // Return JSON
	
class Auth {
	
	public function email($info_post){
		
		$conf_to = $info_post['usrName_new']." <".$info_post['email'].">";
		$conf_subject = "Private Notes Sign Up Comfirmation";
        $conf_content = "Greetings,\n\nThis is an auto generated eamil indicates that your account has been created successfully.\n\nPlease enjoy!\n\nRegards,\nShichen";
        $conf_header = "From: LSC Inc. <non-reply@xeoninside.tk>";
		mail($conf_to, $conf_subject, $conf_content, $conf_header);
		
	}
	
	public function keepSignIn() {
		
		foreach ($_SESSION as $k => $v)
			setcookie("LOGIN[".$k."]", $v, time() + 30*24*3600, '/projects/PrivateNotes');//set cookie
		
	}
	
	public function charValidate(&$info_post, $con, &$return_pak) { // prevention of sql ejaction
		
		foreach ($_POST as $k => $v) {
			$info_post[$k] = mysqli_real_escape_string($con, $v); 
		}
			
		foreach ($info_post as $k => $v) {
			
			if (!($info_post[$k] === $_POST[$k])) {
		
				return false;
			
			}
		}
		return true;
		
	}
	
	public function dbValidate($info_post, $con, &$return_pak) { // check if new user info are duplicated
		
		$emailValidateQuery = "SELECT `email` FROM `auth` WHERE `email` = '".$info_post["email"]."' LIMIT 1";
		$usrNameValidateQuery = "SELECT `name` FROM `auth` WHERE `name` = '".$info_post["usrName_new"]."' LIMIT 1";
		$result_email = mysqli_query($con, $emailValidateQuery);
		$result_usrName = mysqli_query($con, $usrNameValidateQuery);
		
		if (mysqli_num_rows($result_email) > 0) {
			$return_pak['message'] = "This email already exists.";
			return false;
		}
		if (mysqli_num_rows($result_usrName) > 0) {
			$return_pak['message'] = "This user name has been taken.";
			return false;
		}
		
		return true;
	}
	
	public function signUp($info_post, $con, &$return_pak) { // sign the user into database
		
		$numQuery = "SELECT COUNT(*) FROM `auth`";
		$numResult = mysqli_query($con, $numQuery);
		$userSize = mysqli_fetch_array($numResult);
		$uid = $userSize[0] + 1;
		$date = date("Y-m-d");
		
		$salt = $this->saltGen("signUp", $info_post);
		
		$hashed = md5($salt.$info_post['psd_new']);
		
		$signUpQuery = "INSERT INTO `auth` (`uid`, `name`, `email`, `psd`, `since`) VALUES ('".$uid."', '".$info_post['usrName_new']."', '".$info_post['email']."', '".$hashed."', '".$date."')";
		
		if (!mysqli_query($con, $signUpQuery)) {
			$return_pak['message'] = "Unexpected error";
			return false;
		} else if (!$this->sync($info_post, $uid, $con, $hashed)) {
			$return_pak['message'] = "Sync error";
			return false;
		}
		
		$_SESSION['psd'] = $hashed;
		$_SESSION['uid'] = $uid;
		
		return true;
	}
	
	public function logIn($info_post, $con, &$return_pak){ // check user login info
	
		$info_post['psd'] = md5($this->saltGen($_POST['mode'], $info_post).$info_post['psd']);
		
		$findUserQuery = "SELECT * FROM `auth` WHERE `name` = '".$info_post['usrName']."' LIMIT 1";
		
		if ($_SESSION['flag'] == "YES")
			return true;
		
		$result_search = mysqli_query($con, $findUserQuery);
		
		if (mysqli_num_rows($result_search) != 1) {
			$return_pak['message'] = "User not found!"; // search user in DB
			return false;
		} else {
			
			$result_row = mysqli_fetch_array($result_search);
				
			if ($this->checkPass($con, $info_post)) {
				$_SESSION['usrName'] = $info_post['usrName'];
				$_SESSION['flag'] = "YES";
				$_SESSION['psd'] = $info_post['psd'];
				$_SESSION['uid'] = $result_row['uid'];
				$return_pak['message'] = "pass";  	
				return true;
			} else {
				$return_pak['message'] = "Incorrect password!";
				return false;
			}
		}
		
	}
	
	public function checkPass($con, $data) {
		
		$findUserQuery = "SELECT * FROM `auth` WHERE `name` = '".$data['usrName']."' LIMIT 1";
		$result_search = mysqli_query($con, $findUserQuery);
		$result_row = mysqli_fetch_array($result_search);
		
		return $data['psd'] === $result_row['psd'];

	}
	
	public function sync($info_post, $uid, $con, $hashed) { // creat user profile in the note database
		
		$salt = $this->saltGen("signUp", $info_post);
		
		$notesQuery = "INSERT INTO `notes` (`uid`, `psd`) VALUES ('".$uid."', '".$hashed."')";
		if (!mysqli_query($con, $notesQuery)) {
			return false;
		}
		return true;
		
	}
	
	public function saltGen($mode, $info_post) { // generate salt for new user
		
		if ($mode === "signUp") 
			return $info_post['usrName_new']."xeon";
		else if ($mode === "signIn")
			return $info_post['usrName']."xeon";
		
	}
	
	public function getIP() { // get current IP of user
		
		global $ip; 

		if (getenv("HTTP_CLIENT_IP")) 
		$ip = getenv("HTTP_CLIENT_IP"); 
		else if(getenv("HTTP_X_FORWARDED_FOR")) 
		$ip = getenv("HTTP_X_FORWARDED_FOR"); 
		else if(getenv("REMOTE_ADDR")) 
		$ip = getenv("REMOTE_ADDR"); 
		else 
		$ip = "Unknown"; 

		return $ip; 
	} 

}
?>