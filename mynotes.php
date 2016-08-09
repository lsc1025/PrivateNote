<?php

	session_start();
	
	$con = mysqli_connect("localhost", "cl60-prnotes", "cDq9VD9b!", "cl60-prnotes");
	
	if (mysqli_connect_error())
		die ("DB error");
	
	if (array_key_exists('LOGIN', $_COOKIE)) {
		if (!($_COOKIE['LOGIN']['flag'] === "YES")) {
			if (!($_SESSION['flag'] === "YES")) {
				header("Location: index.html");
			}
		} else {
			foreach ($_COOKIE['LOGIN'] as $k => $v)
			$_SESSION[$k] = $v;
		}
	} 
		
	$readQuery = "SELECT * FROM `notes` WHERE `uid` ='".$_SESSION['uid']."'";

	$resultObj = mysqli_query($con, $readQuery);
	$result = mysqli_fetch_array($resultObj);

 ?>
 
<html>

<head>

	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
	
	<title><? echo $_SESSION['usrName']?>'s Private Notes</title>

    <link rel="stylesheet" href="/res/vendor/bootstrap.min.css" />
	
	<style type="text/css">
        html {
        }

        body {
            background: url("/res/other/weatherBg.jpg") no-repeat center center fixed;
            background-size: cover;
        }
		h6{
			margin:0;
		}
		#space{
			width:100%;
			height:50px;
		}
		textarea {
			height:80%;
			resize:none;
		}
		.center{
			text-align:center;
		}
		.inline{
			display:inline;
		}
		.space5{
			width:100%;
			height:5px;
		}
		footer{
			font-size:0.8em;
			
		}
		p{
			margin:0;
		}
		#topBtn{
			padding-top:4px;
		}
		@media(max-width:500px) {
			#tfcontainer {
				margin:0;
			}
			.space5{
				display:none;
			}
		}
		
	</style>

</head>
<body>

	<div class="container">

		<nav class="navbar navbar-fixed-top bg-faded" id="topbar">
			
			<div class="container">
				
				<h6 id="logo" class="navbar-brand"><strong>PrivateNotes</strong></h6>
				
				<div class="pull-xs-right">
					<ul class="nav nav-inline" id="topBtn">
						<li class="nav-item"><a class="nav-link" href="home.php?cmd=logout" id="logout">Log Out</a></li>
						<li class="nav-item"><a class="nav-link" href="#"><?php echo $_SESSION['usrName']?></a></li>
					</ul>
				</div>
				
			</div>
		
		</nav>
	
		<div id="space"></div>
		
			<p class="center" id="info">Last modification: <?php echo $result['time']?></p>
			
	</div>
				
	<div class="container" id="tfcontainer">	
			
		<textarea class="form-control" id="content"/><?php echo $result['content'];?></textarea> 
			
		
	</div>
	
	<div class="space5"></div>
	
	<footer class="navbar">
		<div class="container">
			<ul class="nav nav-inline">
				<p class="nav-item text-muted">&copy 2016 Shichen Liu.</p>

			</ul>
		</div>
	</footer>
	
	<script src="/res/vendor/jquery.min.js"></script>
    <script src="/res/vendor/bootstrap.min.js"></script>
	
	<script type="text/javascript">
	
		var isSaved = false;
		var time = new Date();
		
		setInterval (function(){
			
			isSaved = false;
			
		}, 2000);
		
		$('#content').bind('input propertychange', function(){
		
			if (!isSaved) {
				
				isSaved = true;
				
				var stor = $.ajax({
					type: "post",
					url: "notes.php",
					async: true,
					data: {
						content:$(this).val(),
					}
				});
				
				stor.done(function(data){
					
					time.setTime(data);
					$("#info").html("Saved: " +time.toLocaleString());
					
				});
			
			}
		
		});
		
		function initialize(){
			
		}
		
		
	
	</script>
	
</body>

</html>