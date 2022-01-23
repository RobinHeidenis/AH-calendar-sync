<?php
    include('./consts.php');

	if($_SERVER["REQUEST_METHOD"] == "POST") {
		$original_json = json_decode(file_get_contents("./status.json"), true);

    	$run = $_POST["run"];

    	$status["run"] = ($run == "on" ? 1 : 0);
    	if($run == "on") {
    		$status["run"] = "true";
    		$message = "Running normally";
    	}
    	else {
    		$status["run"] = "false";
    		$message = "Manual start required";
    	}

    	if(empty($_POST["new-password"])) {
    		$status["password"] = $original_json["password"];
    	}
    	else {
    		$status["password"] = $_POST["new-password"];
    	}

    	$status["message"] = $message;

    	if($_POST["verification"] == VERIFICATION_PASSWORD) {
    		file_put_contents("./status.json", json_encode($status));
    	}
    	else {
    		echo '<div class="alert alert-danger" role="alert"> Wrong verification password. Please try again. </div>';
    	}
	}

	$json = json_decode(file_get_contents("./status.json"), true);

?>

<!DOCTYPE HTML>
<html>
	<head>
		<title>Status</title>

		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	</head>

	<body>

		<style>
			html, body {height: 100%;}
			.dot {
				display: inline-block;
				border-radius: 50%;
				width: 50%;
				background-color: grey;
				height: 15px;
				width: 15px;
				margin: 0 2px;
			}

			.dot-true{
				background-color: #449D44;
			}

			.dot-warning{
				background-color: #F0AD4E;
			}

			.dot-false{
				background-color: #D9534F;
			}

			.dot-lg {
    			margin: 0 4px
    			height: 20px
    			width: 20px
    		}

    		.divider {
    			border-top: 1px solid #8c8b8b;
    		}

    		#status, #form {
    			width: 50vw;
    		}

    		main {
    			padding-left: 25%; 
    			padding-right: 25%; 
    			padding-top: 10%; 
    			padding-bottom: 10%;
    		}
		</style>
		
		<main class="h-100 w-100 row align-items-center justify-content-center">
			<div id="status">
				<div class="card">
					<h3 class="card-header">Status</h3>
					<div class="card-body">
						<div class="w-100" style="height: 3vh">
				    		<p class="float-left">AH Script</p>
				    		<p class="float-right"><?php echo "<span class='dot dot-lg dot-" . $json['run'] . "'></span> " . $json['message']?></p>
				    	</div>
				    	<hr class="divider">
				    	<div class="w-100">
				    		<p class="float-left">SAH Script</p><p class="float-right"><span class="dot dot-lg dot-true"></span> Running normally</p>
				  		</div>
				  	</div>
				</div>
			</div>

			<div id="form">
				<div class="card">
					<h3 class="card-header">Password change</h3>
					<div class="card-body">
						<form id="loginform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
							<div class="form-group">
								<label for="new-password">New Password</label>
			        			<input autocomplete="new-password" type="text" name="new-password" id="new-password" class="form-control" placeholder="new password">
		        			</div>
		        			<div class="form-group">
			        			<label for="verification">Verification Password</label>
			        			<input autocomplete="current-password" type="password" name="verification" id="verification" class="form-control" placeholder="verification password">
			        		</div>
			        		<div class="form-check">
			        			<input type="checkbox" class="form-check-input" name="run" <?php if($json["run"] == "true") {echo "checked";}?>>
   		 						<label class="form-check-label" for="run">Enable script</label>
			        		</div>
		        			<button type="submit" class="btn btn-dark">Submit</button>
						</form>
					</div>
				</div>
			</div>
		</main>
	</body>
</html>