<?php
if (isset($_SESSION) == false)
	session_start();
	if (isset($_FILES["userfile"]))
	{		
		$basepath = $_SESSION["Program_Dir"];		
		$uploaddir =$basepath."Storage/";
		$time = date("Ymdhs", time());
		$found =false;
		$code = getRandomKey(50);
		do{				
			include $basepath ."Includes/DataBase.inc.php";
			mysql_query("Select *  from `Files` where  Filename = '$code.dat'");
			if (mysql_affected_rows() > 0)
			{
				$code = getRandomKey(50);
				$found = true;					
			}
		}while($found == true );			
		$newfilename = $code.".dat";//$time.".dat"; 
		if ($_FILES['userfile']['name'] != "index.php" && $_FILES['userfile']['name'] != "index.html") { 	
			$dbpath = $basepath ."Includes/DataBase.inc.php";					
			$userid = $_SESSION['user_id'];	
			$hash = md5($newfilename);	
			$client_ip = getIP();
			$timestamp = time();
			$uploadtime= date("D M j G:i:s T Y",$timestamp);
			$dir = $_SESSION['currentdir'];				
			$oldfilename = mysql_real_escape_string($_FILES['userfile']['name']);
			$size = filesize($_FILES['userfile']['tmp_name']);
			//TODO FIX ERROR HERE
			if (getUsedSpace($_SESSION["user_id"])  + $size < $_SESSION["space"] * 1024 * 1024){
				include $dbpath;	
				$insert = "INSERT INTO Files (Filename,Displayname,Hash,UserID,IP,Uploaded,Size,Directory,Client) VALUES ('$newfilename','$oldfilename','$hash','$userid','$client_ip','$uploadtime','$size','$dir','".$_SERVER['HTTP_USER_AGENT']."')";
				//echo $insert;
				$inser_query = mysql_query($insert) or die ("Error: 030:" .mysql_error());
				if ($inser_query == true)
					 move_uploaded_file($_FILES['userfile']['tmp_name'], $uploaddir.$newfilename);
				mysql_close($connect);	
			}
			else
			{
				header("Location: index.php?message=4");
			}
			
		} else {
			header("Location: index.php?message=5");
		}
	}
?>
<div class ="contentWrapper">
<?php	
	echo "<h2>Upload file into " .$_SESSION['currentdir'].".</h2>";
?>
<form enctype="multipart/form-data" action="index.php?module=upload" method="POST">
<p> 
	<input name="userfile" type="file"/>
</p>
    <input  type='submit' value='Upload'>
</form>
</div>