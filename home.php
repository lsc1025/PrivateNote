<?php
	require("auth.php");

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

?>