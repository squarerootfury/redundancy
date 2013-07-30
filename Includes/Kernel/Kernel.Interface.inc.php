<?php
	function ui_create_copybutton($hashcode){
		echo "<object title = 'Sharelink' classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' height='31' id='clippy' >";	
		echo "<embed src='./Lib/clippy.swf' height='31' name='clippy' quality='high' allowScriptAccess='always' type='application/x-shockwave-flash' pluginspage='http://www.macromedia.com/go/getflashplayer' FlashVars='text=$hashcode' bgcolor='#F0F0F0'";
		echo "/></object>";	
	}
	function ui_create_contextmenu($hashcode,$count)
	{
		++$count;
		$shared = isShared(str_replace("#","",$hashcode));
		if ($shared)
			$Share_Status = "<a class = 'shared' href = 'index.php?module=share&file=".str_replace("#","",$hashcode)."&delete=true'><img  src = './Images/link_go.png'> ".$GLOBALS["Program_Language"]["Shared"]."</a>";
		else
			$Share_Status = "<a href = 'index.php?module=share&file=".str_replace("#","",$hashcode)."&new=true'><img  src = './Images/link_go.png'> ".$GLOBALS["Program_Language"]["Share"]."</a>";
		$folder = fs_is_Dir(str_replace("#","",$hashcode));
		echo "<ul id='context_menu$count' style='position:absolute;font-size:small'>";
		if ($folder == true)
		{
			include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";
			$user = mysqli_real_escape_string($connect,$_SESSION["user_id"]);
			$select = "Select Displayname,Filename,Directory from Files where UserID = '$user' and Hash = '".str_replace("#","",$hashcode)."' limit 1";
			$result= mysqli_query($connect,$select);
			while ($row = mysqli_fetch_object($result)) {
					echo "<li><a href ='index.php?module=list&dir=".$row->Displayname."'><img  src = './Images/folder_magnify.png'> ".$GLOBALS["Program_Language"]["open_generic"]."</a></li>";
				echo "<li><a href ='index.php?module=delete&dir=".$row->Filename."'><img  src = './Images/folder_delete.png'> ".$GLOBALS["Program_Language"]["Delete"]."</a></li>";
				echo "<li><a class = 'delete' href ='index.php?module=list&move=true&source=".$row->Filename."&old_root=".$row->Directory."'><img  src = './Images/cut_red.png'> ".$GLOBALS["Program_Language"]["Cut"]."</a></li>";
				echo "<li><a class = 'delete' href = 'index.php?module=list&copy=true&source=".$row->Filename."&old_root=".$row->Directory."'><img src= './Images/page_copy.png'> ".$GLOBALS["Program_Language"]["Copy"]."</a></li>";
				echo "<li><a class = 'delete'  href ='index.php?module=rename&source=".$row->Displayname."&old_root=".$_SESSION["currentdir"]."'><img  src = './Images/textfield_rename.png'> ".$GLOBALS["Program_Language"]["Rename_title"]."</a></li>";
				echo "<li><a href ='index.php?module=zip&dir=".$row->Displayname."'><img  src = './Images/page_white_zip.png'> ".$GLOBALS["Program_Language"]["Zip"]."</a></li>";			
			}			
			mysqli_close($connect);	
		}
		else
		{
			include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";
			$user = mysqli_real_escape_string($connect,$_SESSION["user_id"]);
			$select = "Select Displayname,Filename,Directory,Hash from Files where UserID = '$user' and Hash = '".str_replace("#","",$hashcode)."' limit 1";
			$result= mysqli_query($connect,$select);
			while ($row = mysqli_fetch_object($result)) {
				echo "<li><a href ='index.php?module=file&file=".$row->Hash."'><img  src = './Images/page_white_magnify.png'> ".$GLOBALS["Program_Language"]["open_generic"]."</a></li>";
				echo "<li><a href ='index.php?module=delete&file=".$row->Hash."'><img  src = './Images/folder_delete.png'> ".$GLOBALS["Program_Language"]["Delete"]."</a></li>";
				echo "<li><a class = 'delete' href ='index.php?module=list&move=true&file=".$row->Hash."'><img  src = './Images/cut_red.png'> ".$GLOBALS["Program_Language"]["Cut"]."</a></li>";
				echo "<li><a class = 'delete' href = 'index.php?module=list&copy=true&file=".$row->Hash."'><img src= './Images/page_copy.png'> ".$GLOBALS["Program_Language"]["Copy"]."</a></li>";
				echo "<li><a class = 'delete'  href ='index.php?module=rename&file=".$row->Hash."'><img  src = './Images/textfield_rename.png'> ".$GLOBALS["Program_Language"]["Rename_title"]."</a></li>";
				echo "<li><a href ='index.php?module=download&file=".$row->Hash."'><img  src = './Images/arrow_down.png'> ".$GLOBALS["Program_Language"]["Download"]."</a></li>";			
			}			
			mysqli_close($connect);	
		}
		echo "<li >$Share_Status</li></ul>";
		echo "<script>$(function() {\$('#context_menu$count').menu();\$('#context_menu$count').toggle();\$('$hashcode').bind('contextmenu', function(e){var MouseX;var MouseY;e.preventDefault();MouseX = e.pageX;MouseY = e.pageY;$('#context_menu$count').css({'top':MouseY,'left':MouseX});\$('#context_menu$count').toggle('clip', {}, 100 );return false;});";
		echo "\$('#context_menu$count').mouseleave(function(){\$(this).hide();});});";  
		echo "</script>";
	}
	function ui_get_cropped_displayname($displayname)
	{
		
		if (strlen($displayname) <= $GLOBALS["config"]["Program_Max_Displayname_Length"])
			return $displayname;
		else
		{
			return substr($displayname,0,-1*(strlen($displayname)-$GLOBALS["config"]["Program_Max_Displayname_Length"]))."...";
		}
	}
	function ui_get_dirlink($Displayname,$Filename,$Filename_only)
	{
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";
		$dirlink = "";
		if (isset($_GET["move"]) && isset($_GET["file"]))
			$dirlink = "<a title = ".$Displayname." class = 'filelink' href = 'index.php?module=move&dir=".$Displayname."&file=".mysqli_real_escape_string($connect,$_GET["file"])."'>".ui_get_cropped_displayname($Filename_only)."</a>";
		else if (isset($_GET["copy"]) && isset($_GET["file"]))
			$dirlink = "<a title = ".$Displayname." class = 'filelink' href = 'index.php?module=copy&dir=".$Displayname."&file=".mysqli_real_escape_string($connect,$_GET["file"])."'>".ui_get_cropped_displayname($Filename_only)."</a>";
		else if (isset($_GET["move"]) && isset($_GET["source"]))
			$dirlink = "<a title = ".$Displayname." class = 'filelink' href = 'index.php?module=move&source=".$_GET["source"]."&target=".$Displayname."&old_root=".$_GET["old_root"]."'>".ui_get_cropped_displayname($Filename_only)."</a>";
		else if (isset($_GET["copy"]) && isset($_GET["source"]))
			$dirlink = "<a title = ".$Displayname." class = 'filelink' href = 'index.php?module=copy&source=".$_GET["source"]."&target=".$Displayname."&old_root=".$_GET["old_root"]."'>".ui_get_cropped_displayname($Filename_only)."</a>";
		else
			$dirlink = "<a title = ".$Displayname." class = 'filelink' href = 'index.php?module=list&dir=".$Displayname."'>".ui_get_cropped_displayname(getDisplayName($Filename_only,$Filename))."</a>";
		return $dirlink;
	}
	function ui_get_modulelink($Displayname)
	{
		include $GLOBALS["Program_Dir"]."Includes/DataBase.inc.php";
		$modulelink = "";
		if (isset($_GET["move"]) && isset($_GET["file"]))
				$modulelink = "module=list&dir=".$Displayname."&move=true&file=".mysqli_real_escape_string($connect,$_GET["file"])."&dir=".$Displayname;
		else if (isset($_GET["copy"]) && isset($_GET["file"]))
				$modulelink = "module=list&dir=".$Displayname."&copy=true&file=".mysqli_real_escape_string($connect,$_GET["file"])."&dir=".$Displayname;
		else if (isset($_GET["copy"]) && isset($_GET["source"]))
				$modulelink = "module=list&dir=".$Displayname."&copy=true&source=".mysqli_real_escape_string($connect,$_GET["source"])."&old_root=".mysqli_real_escape_string($connect,$_GET["old_root"])."&target=".$Displayname;
		else if (isset($_GET["move"]) && isset($_GET["source"]))
				$modulelink = "module=list&dir=".$Displayname."&move=true&source=".mysqli_real_escape_string($connect,$_GET["source"])."&old_root=".mysqli_real_escape_string($connect,$_GET["old_root"])."&target=".$Displayname;
		return $modulelink;
	}
	function ui_get_Styles($stylesdir)
	{
		$languages = scandir($stylesdir);
		echo "<select id = 'Style' name = 'Style'>";
		foreach($languages as $entry) {
			if (is_file($stylesdir.$entry) && endsWith($entry,".css")){
				echo "<option value='$entry'>".str_replace(".css","",$entry)."</option>";			
			}				
		}
		echo "</select>";
	}

	
	
?>