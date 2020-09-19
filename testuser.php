<?php
	echo <<<_END
		<html><head><title>Virus Checker</title></head><body>
		<form method='post' action='midterm-user.php' enctype='multipart/form-data'>
			Upload a file that may be infected and needs to go under analysis. <br><br>
			Select File: <input type='file' name='filename' size='10'>
			<input type='submit' value='Upload' name='submit'>
		</form>
	_END;

	require_once 'login.php';

	$err = "Error ";
	$conn = new mysqli($hn,$un,$pw,$db);

	if ($conn->connect_error){
		mysql_fatal_error($err, $conn);
	}

	if($_FILES){
		$name = $_FILES['filename']['name'];
		$name = preg_replace("[^A-Za-z0-9.]", "", $name);
		$type = $_FILES['filename']['type']; //file type
		$file = $_FILES['filename']['tmp_name']; //temporary file stored on the server
		
		if($file !=null){
			$query = "SELECT * FROM malware";
			$result = $conn->query($query);

			if (!$result){
				mysql_fatal_error($err, $conn);
			}

			echo "$name was accepted.";
			echo "<br>";

			if(isset($_POST['submit'])){ //if submit
				$x=0;
				$content = file_get_contents($file,FALSE,NULL,$x,20); //get content of file
				$content = sanitizeMySQL($conn,$content); 
				$content = mysql_entities_fix_string($conn, $content);

				$boolean = false;
				while($content != ""){ //search query
					$query = "SELECT * FROM malware where content = '$content'";
	   				$result = $conn->query($query);

	   				if (!$result){
						break;
					}

					if ($result==TRUE&&$result->num_rows) {
						$row = $result->fetch_array(MYSQLI_NUM);
						$contentFromSQL = $row[1];
						//$result->close();

		   				if($contentFromSQL == $content){
		   					$boolean = true;
		   					$virusname = $row[0];
		   					break;
		   				}
		   			}

		   			$x+=1;
		   			$content = file_get_contents($file,FALSE,NULL,$x,20);

				}

				if($boolean==true){
					echo "This file has a virus known as $virusname";
				}
				else{
					echo "This file does not have a known virus.";
				}

			}
		}

		else{
			echo "No file selected.";
		}
			
	}

	$query = "SELECT * FROM malware";
	$result = $conn->query($query);

	if (!$result){
		mysql_fatal_error($err, $conn);
	}

	$result->close();
	$conn->close();


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