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
	 * This file creates the navigation for switching between several directories
	 */
	//Include uri check
	//require_once ("checkuri.inc.php");
	//start a session if needed
	if (isset($_SESSION) == false)
		session_start();	
	//include the database file
	include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";	
	//Get the path parts
	$dirs = explode("/",$_SESSION["currentdir"]);
	//parts_before is a kind of prefix to display a complete link step by step
	$parts_before = "";
	//link suffix to display
	$suffix = "";
	//Display several kinds of links for cases like copy file, move filey, copy dir, move dir and the regular display
	if (isset($_GET["move"]) && isset($_GET["file"]))
			$suffix = "module=list&dir=/&move=true&file=".mysqli_real_escape_string($connect,$_GET["file"])."&dir=/";
	else if (isset($_GET["copy"]) && isset($_GET["file"]))
			$suffix = "module=list&dir=/&copy=true&file=".mysqli_real_escape_string($connect,$_GET["file"])."&dir=/";
	else if (isset($_GET["copy"]) && isset($_GET["source"]))
			$suffix = "module=list&dir=/&copy=true&source=".mysqli_real_escape_string($connect,$_GET["source"])."&old_root=".mysqli_real_escape_string($connect,$_GET["old_root"])."&target=/";
				else if (isset($_GET["move"]) && isset($_GET["source"]))
			$suffix = "module=list&dir=/&move=true&source=".mysqli_real_escape_string($connect,$_GET["source"])."&old_root=".mysqli_real_escape_string($connect,$_GET["old_root"])."&target=/";
	else
			$suffix = "module=list&dir=/";	
?>
<ol class = 'breadcrumb'>
	<li>
		<a href= 'index.php?<?php echo $suffix;?>'>Home</a>
	</li>		
	<?php for ($i = 0; $i < count($dirs); $i++) :?>
		<?php if ($dirs[$i] != "") :?>
			<li>
				<a href= 'index.php?<?php echo $suffix.$parts_before.$dirs[$i];?>/'><?php echo $dirs[$i];?></a>
				<?php 
					$parts_before = $parts_before.$dirs[$i]."/";
				?>
			</li>
		<?php endif ;?>
	<?php endfor;?>
</ol>