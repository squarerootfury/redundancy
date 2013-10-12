<?php
	/**
	 * @file
	 * @author  squarerootfury <fury224@googlemail.com>	 
	 *
	 * @section LICENSE
	 *
	 * This program is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License as
	 * published by the Free Software Foundation; either version 3 of
	 * the License, or (at your option) any later version.
	 *
	 * This program is distributed in the hope that it will be useful, but
	 * WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
	 * General Public License for more details at
	 * http://www.gnu.org/copyleft/gpl.html
	 *
	 * @section DESCRIPTION
	 *
	 * Any functionality for the user (login, register etc.) is stored in this file.
	 */
	/**
	 * login login the user
	 * @param $pUser the user name or email
	 * @param $pPass the password
	 * @param $login determine if a log in should be done
	 * @return the result of the log in
	 */
	function login($pUser,$pPass,$login = true)
	{		
		//start a new session
		if (isset($_SESSION) == false)
			session_start();
		//Include database file
		if (strpos($pUser,"<") === false && strpos($pPass,"<") === false)
		{
			include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";			
			$user = mysqli_real_escape_string($connect,$pUser);
			$pass = mysqli_real_escape_string($connect,$pPass);		
			$tries = 0;
			$email ="";
			$name = "";
			$enabled = 1;
			$ergebnis = mysqli_query($connect,"Select ID, User, Email, Password, Salt,Storage,Role, Enabled,Failed_Logins,Session_Closed from Users where User = '$user' or Email = '$user' limit 1") or die("Error: 018 ".mysqli_error($connect));
			while ($row = mysqli_fetch_object($ergebnis)) {	
				$internalpassword = $row->Password;	
				$tries = $row->Failed_Logins;
				$email = $row->Email;
				$name = $row->User;
				$enabled = $row->Enabled;
				if ($internalpassword == hash('sha512',$pass.$row->Salt."thehoursedoesnoteatchickenandbacon") && $row->Enabled == 1)
				{			
					if ($login == true){
						$_SESSION['user_id'] = $row->ID;
						$_SESSION['user_name'] = $row->User;
						$_SESSION['user_email'] = $row->Email;
						$_SESSION["user_logged_in"] = true;
						$_SESSION["currentdir"] = "/";	
						$_SESSION["currentdir_hashed"] = "6666cd76f96956469e7be39d750cc7d9";	
						$_SESSION["space"] = $row->Storage;	
						$_SESSION["space_used"] = 0;
						$_SESSION["role"] = $row->Role;
						$_SESSION["fs_hash"] = hash('sha512',$pass.$row->Salt.$row->Email.$pass);
						$_SESSION["Session_Closed"] = $row->Session_Closed;
						//Reset Login counter;
						mysqli_query($connect,"Update Users Set Failed_logins=0,Session_Closed =0 where Email ='$user' or User='$user'");
						mysqli_close($connect);	
					}					
					return true;		
				}							
			}
			
		}		
		if ($tries == $GLOBALS["config"]["User_Max_Fails"]){
			if ($enabled != 0){
				mysqli_query($connect,"Update Users Set Enabled=0 where Email ='$user' or User='$user'");		
				sendMail($email,3,$name,getIP2(),"","");		
			}
		}		
		$tries = $tries +1;		
		mysqli_query($connect,"Update Users Set Failed_logins=$tries where Email ='$user' or User='$user'");		
		mysqli_close($connect);
		return false;
	}
	/**
	 * getNewUserName create a new user name
	 * @param $pEmail the email
	 * @return a generated user name
	 */
	function getNewUserName($pEmail){
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		$pEmail = mysqli_real_escape_string($connect,$pEmail);
		$result = "";
		$go = true;
		$parts = explode("@",$pEmail);
		$result = $parts[0];
		$try = 0;
			
		do{
			$ergebnis = mysqli_query($connect,"Select ID, User, Email, Password, Salt,Storage,Role, Enabled,Failed_Logins from Users where User = '$result' or Email = '$pEmail' limit 1") or die("Error: 018 ".mysqli_error($connect));
			if (mysqli_affected_rows($connect) == 0)
				$go = false;
			else{
				$try++;
				$result = $parts[i].$try;
			}
		}while($go == true);
		return $result;
	}
	/**
	 * getNewUserName create a new user name
	 * @param $pEmail the email
	 * @param $pPass the password
	 * @param $pPassRepeat the password repated
	 * @return the result of the registraiton (success/fail)
	 */
	function registerUser($pEmail,$pPass,$pPassRepeat)
	{
		$pUser = getNewUserName($pEmail);
		//Is the password and the repeated the same?
		if (strpos("<",$pUser) !== false || strpos("<",$pEmail) !== false || strpos("<",$pPass) !== false || strpos("<",$pPassRepeat) !== false)
			return false;
		if ($pPass == $pPassRepeat)
		{
			$passOK = true;
		}
		else
		{
			$passOK = false;
		}
		//Is there already a user with this username or email?
		if (isExisting($pEmail,$pUser) == false)
		{
			$free = true;
		}
		else
		{
			$free = false;
		}
		//start a new session
		if (isset($_SESSION) == false)
			session_start();
		//Include database file
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";			
		$user = mysqli_real_escape_string($connect,$pUser);
		$pass = mysqli_real_escape_string($connect,$pPass);	
		$email = mysqli_real_escape_string($connect,$pEmail);
		if (strpos($email,"@") === false || strpos($email,".") === false)
			return false;
		$salt = getRandomKey(200);
		$safetypass = hash('sha512',$pass.$salt."thehoursedoesnoteatchickenandbacon");	
		$registered= date("D M j G:i:s T Y",time());
		$role = $GLOBALS["config"]["User_Default_Role"];		
		$storage = $GLOBALS["config"]["User_Contingent"];
		$api_key = hash('sha512',$Email.$salt.$user."thehoursedoesnoteatchickenandbacon");	
		//Determine if the user should be activated automatically or not
		if ($GLOBALS["config"]["User_Registration_AutoDisable"] == 1)
			$enabled = 0;		
				else 
			$enabled = 1;
		if ($passOK == true && $free == true){
			$ergebnis = mysqli_query($connect,"Insert into Users (User, Email,Password,Salt,Registered,Role,Storage,Enabled,API_Key,Enable_API) Values('$user','$email','$safetypass','$salt','$registered','$role',$storage,$enabled,'$api_key',1)") or die("Error: 019 ".mysqli_error($connect));
		
		}
		else
			return false;
		//Send a activation mail when the account is not activated automatically	
		if ($ergebnis == true){
			if ($GLOBALS["config"]["User_Registration_AutoDisable"] == 1  )
				sendMail($email,1,$user,"Redundancy",$GLOBALS["config"]["User_Activation_Link"]."&email=".$email,"Redundancy");
			return true;
		}
		else
			return false;
	}
	/**
	 * sendMail sends a mail
	 * @param $pEmail the email
	 * @param $pMessageID the id of the message out of the database
	 * @param $arg0 argument
	 * @param $arg1 argument
	 * @param $arg2 argument
	 * @param $arg3 argument
	 */
	function sendMail($pEmail,$pMessageID,$arg0,$arg1,$arg2,$arg3)
	{
		//Start a new session if needed
		if (isset($_SESSION) == false)
			session_start();
		//Include database file
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";
		$result = mysqli_query($connect,"Select * from Mails where  ID = '$pMessageID' limit 1") or die("Error 020 ". mysqli_error($connect));
		while ($row = mysqli_fetch_object($result)) {			
			$text = sprintf ($row->Text,$arg0,$arg1,$arg2,$arg3);
			$name = $GLOBALS["config"]["User_Activation_Link_Sender"];
			$name_email = $GLOBALS["config"]["User_Activation_Link_Sender_Email"];
			//Only send the email if configured
			if ($name != "" && $name_email != "")
				mail($pEmail, "Redundancy", $text, "From: $name <$name_email>");
		}
		//close connection
		mysqli_close($connect);
	}
	/**
	 * isExisting check if the user is already registered
	 * @param $pEmail the email
	 * @param $pUser the username
	 * @return true or false
	 */
	function isExisting($pEmail,$pUser)
	{
		if (isset($_SESSION) == false)
			session_start();
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		$user = mysqli_real_escape_string($connect,$pUser);
		$email = mysqli_real_escape_string($connect,$pEmail);
		$ergebnis = mysqli_query($connect,"Select * from Users where  User = '$user' or Email = '$email'") or die("Error: 021 ".mysqli_error($connect));
		if (mysqli_affected_rows($connect) > 0)
		{
			mysqli_close($connect);	
			return true;
		}
		else		
		{
			mysqli_close($connect);	
			return false;
		}		
	}
	/**
	 * recover recover the pass with a mail
	 * @param $pEmail the email	
	 * @todo create a method if email support is not configured
	 */
	function recover($pEmail)
	{
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		$email = mysqli_real_escape_string($connect,$pEmail);		
		if (isExisting($email,"") && strpos("<",$email) === false)
		{		
			if ($GLOBALS["config"]["User_Enable_Recover"] == 1){
				$pass = getRandomPass($GLOBALS["config"]["User_Recover_Password_Length"]);
				
				$query = mysqli_query($connect,"Select ID,User, Email, Password, Salt from Users where Email ='$email' limit 1");
				$salt = getRandomKey(200);
				$name = "";		
				$id = -1;
				while ($row = mysqli_fetch_object($query)) {					
						$name = $row->User;
						$id = $row->ID;
				}
				$safetypass = hash('sha512',$pass.$salt."thehoursedoesnoteatchickenandbacon");	
				$query = "Update Users Set Salt='$salt',Password='$safetypass' where Email ='$email'";
				if ($GLOBALS["config"]["User_Unlock_Recover"] == 1)
						mysqli_query($connect,"Update Users Set Enabled=1 where Email ='$email'");
				mysqli_query($connect,$query);
				$history = "Insert into Pass_History (Changed,IP,Who) Values ('".date("D M j G:i:s T Y", time())."','".getIP()."',$id)";
				mysqli_query($connect,$history);				
				sendMail($email,2,$name,"Redundancy",$pass,"Redundancy");				
				//header("Location: ./index.php?module=recover&msg=success");
			}
			else
				header("Location: ./index.php?module=recover&msg=nosuccess");
		}		
		else
		{
			header("Location: ./index.php");
		}
	}
	/**
	 * setNewPassword set a new password
	 * @param $pEmail the email	
	 * @param $pass_old the old password
	 * @param $pass_new the new password
	 * @param $redir redirect after changed
	 * @param $internalchange if the change is from the system or over the administration
	 * @todo create a method if email support is not configured
	 */
	function setNewPassword($pEmail,$pass_old,$pass_new,$redir = 1,$internalchange = 0)
	{
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		$email = mysqli_real_escape_string($connect,$pEmail);		
		$pass_new = mysqli_real_escape_string($connect,$pass_new);	
		$pass_old = mysqli_real_escape_string($connect,$pass_old);	
		if ((isExisting($email,"") || isExisting("",$email))&& strpos("<",$email) === false && ($internalchange == 1 || login($pEmail,$pass_old,false) == true))
		{		
			if ($GLOBALS["config"]["User_Enable_Recover"] == 1 || $internalchange == 1){
				$pass = $pass_new;
				
				$query = mysqli_query($connect,"Select ID,User, Email, Password, Salt from Users where Email ='$email' or User = '$email' limit 1");
				$salt = getRandomKey(200);
				$name = "";			
				while ($row = mysqli_fetch_object($query)) {					
						$name = $row->User;
						$id = $row->ID;
				}
				$safetypass = hash('sha512',$pass.$salt."thehoursedoesnoteatchickenandbacon");	
				$query = "Update Users Set Salt='$salt',Password='$safetypass' where Email ='$email' or User = '$email' limit 1";
				if ($GLOBALS["config"]["User_Unlock_Recover"] == 1)
						mysqli_query($connect,"Update Users Set Enabled=1 where Email ='$email' or User = '$email' limit 1");
					
						
				mysqli_query($connect,$query);
				$history = "Insert into Pass_History (Changed,IP,Who) Values ('".date("D M j G:i:s T Y", time())."','".getIP()."',$id)";
				mysqli_query($connect,$history);							
				if ($redir == 1)
					header("Location: ./index.php");
			}
			else{
				if ($redir == 1)
					header("Location: ./index.php?module=setpass");
			}
		}		
		else
		{
				if ($redir == 1)
			header("Location: ./index.php?module=setpass");
		}
	}
	/**
	 * is_admin check if the logged in user is administrator
	 * @return if the user is admin
	 */
	function is_admin()
	{
		if (!isset($_SESSION))
			session_start();
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		$user_id = mysqli_real_escape_string($connect,$_SESSION['user_id']);
		$user_name = mysqli_real_escape_string($connect,$_SESSION['user_name']);
		$user_email = mysqli_real_escape_string($connect,$_SESSION['user_email']);
		$query = "Select ID, User, Email, Role from Users where Email = '$user_email' and User = '$user_name' and ID = '$user_id'";
		$mysql = mysqli_query($connect,$query);
		while ($row = mysqli_fetch_object($mysql)) {					
			if ($row->Role == 0)
				$result = true;
			else
				$result = false;
		}
		mysqli_close($connect);	
		return $result;
	}
	/**
	 * check if the logged in user is a guest
	 * @return if the user is a guest
	 */
	function is_guest()
	{
		if (!isset($_SESSION))
			session_start();
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		$user_id = mysqli_real_escape_string($connect,$_SESSION['user_id']);
		$user_name = mysqli_real_escape_string($connect,$_SESSION['user_name']);
		$user_email = mysqli_real_escape_string($connect,$_SESSION['user_email']);
		$query = "Select ID, User, Email, Role from Users where Email = '$user_email' and User = '$user_name' and ID = '$user_id'";
		$mysql = mysqli_query($connect,$query);
		while ($row = mysqli_fetch_object($mysql)) {					
			if ($row->Role == 3)
				$result = true;
			else
				$result = false;
		}
		mysqli_close($connect);	
		return $result;
	}
	/**
	 * is_adminuser_apply_Informations re apply storage and role informations	
	 */
	function user_apply_Informations()
	{
		if (!isset($_SESSION))
			session_start();
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		$user = mysqli_real_escape_string($connect,$_SESSION['user_name']);
		$email = mysqli_real_escape_string($connect,$_SESSION['user_email']);
		$ergebnis = mysqli_query($connect,"Select ID, User, Email, Password, Salt,Storage,Role, Enabled,Failed_Logins from Users where User = '$user' or Email = '$email' limit 1") or die("Error: 018 ".mysqli_error($connect));
		while ($row = mysqli_fetch_object($ergebnis)) {										
			$_SESSION["space"] = $row->Storage;	
			$_SESSION["role"] = $row->Role;		
		}							
		mysqli_close($connect);		
	}
	/**
	 * user_check_session check the user session
	 */
	function user_check_session()
	{
		if (!isset($_SESSION))
			session_start();
		$found = false;
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";		
		foreach($_SESSION as $key => $value)
		{			
		  if ($value != mysqli_real_escape_string($connect,$value))
			$found = true;
		}		
		if ($found == true){
			banUser(getIP(),$_SERVER['HTTP_USER_AGENT'],"SQLi");
			log_event("Kernel.User","user_check_session","SQL injection detected");
		}
		return $found;
	}
	/**
	 * user_load_settings load user settings
	 */
	function user_load_settings()
	{
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		if (!isset($_SESSION))
		{
			exit;
		}
		$userID = $_SESSION["user_id"];
		$query = "Select * from Settings where UserID = $userID";
		$ergebnis = mysqli_query($connect,$query) or die("Error: 018 ".mysqli_error($connect));
		while ($row = mysqli_fetch_object($ergebnis)) {										
			$GLOBALS["config"]["User_NoLogout_Warning"] = $row->User_NoLogout_Warning;
			$GLOBALS["config"]["Program_Display_Icons_if_needed"] = $row->Program_Display_Icons_if_needed;
			$GLOBALS["config"]["Program_Enable_JQuery"] = $row->Program_Enable_JQuery;
			$GLOBALS["config"]["Program_Enable_Preview"] = $row->Program_Enable_Preview;			
		}							
		mysqli_close($connect);		
	}
	/**
	 * user_save_settings save user settings
	 */
	function user_save_settings()
	{
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		if (!isset($_SESSION))
		{
			exit;
		}
		$userID = mysqli_real_escape_string($connect,$_SESSION["user_id"]);
		$User_NoLogout_Warning = mysqli_real_escape_string($connect,$GLOBALS["config"]["User_NoLogout_Warning"]);
		$Program_Display_Icons_if_needed = mysqli_real_escape_string($connect,$GLOBALS["config"]["Program_Display_Icons_if_needed"]);
		$Program_Enable_JQuery = mysqli_real_escape_string($connect,$GLOBALS["config"]["Program_Enable_JQuery"]);
		$Program_Enable_Preview = mysqli_real_escape_string($connect,$GLOBALS["config"]["Program_Enable_Preview"]);
		$query = "Select * from Settings where UserID = $userID";
		$ergebnis = mysqli_query($connect,$query) or die("Error: 018 ".mysqli_error($connect));

		if (mysqli_affected_rows($connect) ==  0)
			$query = "Insert into Settings (UserID,User_NoLogout_Warning,Program_Display_Icons_if_needed,Program_Enable_JQuery,Program_Enable_Preview) values ($userID,'$User_NoLogout_Warning','$Program_Display_Icons_if_needed','$Program_Enable_JQuery','$Program_Enable_Preview')";
		else			
			$query = "Update Settings SET User_NoLogout_Warning = $User_NoLogout_Warning ,Program_Display_Icons_if_needed=$Program_Display_Icons_if_needed,Program_Enable_JQuery=$Program_Enable_JQuery,Program_Enable_Preview=$Program_Enable_Preview where UserID = $userID";
		
		$ergebnis = mysqli_query($connect,$query) or die("Error: 018 ".mysqli_error($connect));
		mysqli_close($connect);		
	}
	/**
	 * user_delete_settings delete user settings
	 */
	function user_delete_settings()
	{
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
			if (!isset($_SESSION))
		{
			exit;
		}
		$userID = mysqli_real_escape_string($connect,$_SESSION["user_id"]);
		$ergebnis = mysqli_query($connect,"Delete from Settings where UserID=userID",$query) or die("Error: 018 ".mysqli_error($connect));
		mysqli_close($connect);	
	}
	/**
	 * get the user role
	 * @param $username the username or the id
	 * @return returns the role or -1
	 */
	function user_get_role($username)
	{
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		$user = mysqli_real_escape_string($connect,$username);		
		$ergebnis = mysqli_query($connect,"Select ID, User, Email, Password, Salt,Storage,Role, Enabled,Failed_Logins from Users where User = '$user' or Email = '$user' limit 1") or die("Error: 018 ".mysqli_error($connect));
		$res = -1;
		while ($row = mysqli_fetch_object($ergebnis)) {		
			$res = $row->Role;		
		}							
		mysqli_close($connect);	
		return $res;
	}
	/**
	 * get the user storage
	 * @param $username the username or the id
	 * @return returns the role or -1
	 */
	function user_get_storage($username)
	{
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		$user = mysqli_real_escape_string($connect,$username);		
		$ergebnis = mysqli_query($connect,"Select ID, User, Email, Password, Salt,Storage,Role, Enabled,Failed_Logins from Users where User = '$user' or Email = '$user' limit 1") or die("Error: 018 ".mysqli_error($connect));
		$res = -1;
		while ($row = mysqli_fetch_object($ergebnis)) {		
			$res = $row->Storage;		
		}							
		mysqli_close($connect);	
		return $res;
	}
	/**
	 * saves changes at the user profiles	
	 */
	function user_save_administration()
	{		
		if (!isset($_SESSION))
			session_start();
		$role = "";
		$user = "";
		$storage = 0;
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		if (is_admin())
		{
			if (isset($_POST["role"]) && $_POST["username_info"] != "")
			{
				$role = mysqli_real_escape_string($connect,$_POST["role"]);
				$user = mysqli_real_escape_string($connect,$_POST["username_info"]);
				$storage = mysqli_real_escape_string($connect,$_POST["storage"]);
				$newpass = mysqli_real_escape_string($connect,$_POST["user_new_pass"]);
				user_set_storage($user,$storage);				
				if ($_POST["user_new_pass"] != "")
				{					
					setNewPassword($user,$newpass,$newpass,0,1);
				}				
				user_set_role($user,$role);
				log_event("info","user_save_administration","new role $role, new storage $storage, new pass $newpass");
				header("Location: index.php?module=admin&message=user_changes_success");
			}				
		}				
	}
	/**
	 * saves changes at the user profiles	
	 * @param $user the username or email
	 * @param $newstorage the new amount of the storage in MB
	 */
	function user_set_storage($user,$newstorage)
	{
		$used_storage =getUsedSpace($user); 
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";
		$ergebnis = mysqli_query($connect,"Select Storage from  Users where User = '$user' limit 1") or die("Error: 018 ".mysqli_error($connect));	
		$res = -1;
		while ($row = mysqli_fetch_object($ergebnis)) {		
			$res = $row->Storage;		
		}	
		if ($res != $newstorage){
			if ($newstorage > $used_storage )
			{
				$ergebnis = mysqli_query($connect,"Update Users set Storage = $newstorage where User = '$user'") or die("Error: 018 ".mysqli_error($connect));	
				
			}
			else
			{
				mysqli_close($connect);	
				header("Location: index.php?module=admin&message=user_changes_failed");
			}
		}
		mysqli_close($connect);	
	}
	/**
	 * set the user role
	 * @param $username the username or the id
	 * @param $role the user role
	 * @return returns the role or -1
	 */
	function user_set_role($username,$role)
	{
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		$user = mysqli_real_escape_string($connect,$username);		
		$ergebnis = mysqli_query($connect,"Update Users set Role = '$role' where User = '$user' or Email = '$user'") or die("Error: 018 ".mysqli_error($connect));
								
		mysqli_close($connect);	
	}
	/**
	 * deletes a user
	 * @param $username the username or Email or ID
	 */
	function user_delete($username)
	{
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		$userID = mysqli_real_escape_string($connect,user_get_id($username));	
		echo "user id".$userID;
		if ($userID != -1){
			$files_query = mysqli_query($connect,"Select * from Files where UserID = '$userID'   limit 1") or die(mysqli_error($connect)) ;
			
			while ($row = mysqli_fetch_object($files_query)) {
				echo "<br>Deleting ".$row->Displayname ."...";
				if ($row->Filename != $row->Displayname)
					unlink($GLOBALS["Program_Dir"]."Storage/".$row->Filename);
				echo "<br>Removing database entry...";
				mysqli_query($connect,"Delete from Files where UserID = '$userID'");			
				mysqli_query($connect,"Delete from Share where UserID = '$userID'");
				echo "<br>Removing shares entries ...";
				echo "..Done";			
			}		
			echo "<br>Deleteing user ...";
			mysqli_query($connect,"Delete from Users where ID = '$userID'");
			echo "<br>..Done";
		header("Location: ?message=user_changes_success");			
		}		
		else
		{
			header("Location: ?message=user_changes_failed");
		}
		mysqli_close($connect);	
	}
	/**
	 * gets the user id
	 * @param $username the username or Email
	 */
	function user_get_id($username)
	{
		$id = -1;
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
		$userID = mysqli_real_escape_string($connect,$username);	
		$files_query = mysqli_query($connect,"Select * from Users where User = '$userID'  or Email = '$username' limit 1") or die(mysqli_error($connect)) ;
		
		while ($row = mysqli_fetch_object($files_query)) {
				$id = $row->ID;	
		}		
		mysqli_close($connect);	
		return $id;
	}
?>