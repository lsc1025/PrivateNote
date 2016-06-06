<?php 

	session_start();
	
	if (!$_POST)
		header("Location: mynotes.php"); // no direct acess
	
	$con = mysqli_connect("localhost", "cl60-prnotes", "cDq9VD9b!", "cl60-prnotes");
	
	if (mysqli_connect_error())
		die ("DB error");
	
	//$auth = new Auth;
	
	//if ($auth->checkPass($con, $_SESSION))
		//echo "mobi";
	
	if (array_key_exists('content', $_POST)) {
		
		$storQuery = "UPDATE `notes` SET `content` = '". mysqli_real_escape_string($con, $_POST['content']) ."' WHERE `uid` = '".$_SESSION['uid']."' LIMIT 1";
		mysqli_query($con, $storQuery);
		echo time()*1000;
		
	}
	

?>