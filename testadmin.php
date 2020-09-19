<?php
	require_once 'login.php';

	$err = "Error ";
	$conn = new mysqli($hn,$un,$pw,$db);

	if ($conn->connect_error){
		mysql_fatal_error($err, $conn);
	}


	if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])){
		$un_temp = mysql_entities_fix_string($conn, $_SERVER['PHP_AUTH_USER']);
		$un_temp = sanitizeMySQL($conn,$un_temp);
		$pw_temp = mysql_entities_fix_string($conn, $_SERVER['PHP_AUTH_PW']);
		$pw_temp = sanitizeMySQL($conn,$pw_temp);

		$query = "SELECT * FROM users WHERE username='$un_temp'";
   		$result = $conn->query($query);

		if (!$result){
			mysql_fatal_error($err, $conn);
		}

		else if ($result->num_rows) {
			$row = $result->fetch_array(MYSQLI_NUM);
			$salt =  $row[2];
			$result->close();
			
			$token = hash('ripemd128', "$salt$pw_temp$salt");
			if ($token == $row[1]){
				echo "Hi '$row[0]'. You are now logged in as Admin."; echo "<br><br>";
				echo <<<_END
					<html><head><title>Virus Checker Admin</title></head><body>
					<form method='post' action='midterm-admin.php' enctype='multipart/form-data'>
						Upload a file that is infected and can be used to check other files. <br><br>
						Malware Name:  <input type='text' name='filename'>
						Select File: <input type='file' name='filename' size='10'>
						<input type='submit' value='Upload' name='submit'> 
					</form>
				_END;
				main();
			}
			else{
				header('WWW-Authenticate: Basic realm="Restricted Section“');
				header('HTTP/1.0 401 Unauthorized');
				die("Invalid username / password combination");
			}
		}

		else {
			header('WWW-Authenticate: Basic realm="Restricted Section“');
			header('HTTP/1.0 401 Unauthorized');
			die("Invalid username / password combination");
		}
	}

	else{
		header('WWW-Authenticate: Basic realm="Restricted Section“');
		header('HTTP/1.0 401 Unauthorized');
		die ("Please enter your username and password");
	}

	$query = "SELECT * FROM malware";
	$result = $conn->query($query);

	if (!$result){
		mysql_fatal_error($err, $conn);
	}	

	$result->close();
	$conn->close();

	function main(){
		global $conn; global $result; global $query; global $err;

		if(isset($_POST['submit'])){
			$fname = $_POST['filename'];
			$fname = sanitizeMySQL($conn,$fname);
			$fname = mysql_entities_fix_string($conn, $fname);

			if ($fname == "") {
   				echo("File name must be filled out. ");
   			}

   			else{
				if($_FILES){
					$name = $_FILES['filename']['name'];
					$name = preg_replace("[^A-Za-z0-9.]", "", $name);
					$type = $_FILES['filename']['type']; //file type
					$file = $_FILES['filename']['tmp_name']; //temporary file stored on the server

					if($file !=null){
						$content = file_get_contents($file,FALSE,NULL,0,20);
						$content = sanitizeMySQL($conn,$content);
						$content = mysql_entities_fix_string($conn, $content);

						if(strlen($content)<20){
							echo "Sorry, this file was not accepted because it is too small. ";
						}

						else{
							$query = "INSERT INTO malware VALUES ('$fname','$content')";
							$result = $conn->query($query);

							if (!$result){
								mysql_fatal_error($err, $conn);
							}

							echo "$name was accepted and named $fname.";
						}
					}

					else{
						echo "No file selected.";
					}
					
				}
			}
		}
	}


	function mysql_fatal_error($msg, $conn){
		$msg2 = mysqli_error($conn);
		echo <<< _END
		We are sorry, but it was not possible to complete the requested task.
		The error message we got was:
			<p>$msg:$msg2</p>
		Please click the back button on your browser and try again.
		_END;
	}

	function sanitizeString($var) {
		$var = stripslashes($var);
		$var = strip_tags($var);
		$var = htmlentities($var);
		return $var;
	}

	function sanitizeMySQL($connection, $var) {
		$var = $connection->real_escape_string($var);
		$var = sanitizeString($var);
		return $var;
	}

	function mysql_entities_fix_string($conn, $string){
		return htmlentities(mysql_fix_string($conn, $string));
	}

	function mysql_fix_string($conn, $string){
		if (get_magic_quotes_gpc()) $string = stripslashes($string);
		return $conn->real_escape_string($string);
	}


?>