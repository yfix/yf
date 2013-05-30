<?php

require dirname(__FILE__)."/header.php";

if(isset($_POST["next"])){
	$log_text .= "\n//********* Compilation **********//\n";
	$log_text .= "Selected predefined compilation \"".$_POST["compilations"]."\"\n";
	add_log($log_text);

	$_SESSION['INSTALL']["compilations"]		= $_POST["compilations"];
	$_SESSION['INSTALL']["user_info_dynamic"]	= $_POST["user_info_dynamic"];
	
	if ($_SESSION['INSTALL']["compilations"] != "all"){
		$next_step = "rewrite";
	}

	header("Refresh: 0;URL=".$_SESSION['INSTALL']['_WEB_PATH']."install.php?step=".$next_step);
}

if(isset($_POST["prev"])){
	header("Refresh: 0;URL=".$_SESSION['INSTALL']['_WEB_PATH']."install.php?step=".$back_step);
}

	$compilations_path = dirname(__FILE__)."/compilations/";
	if (is_dir($compilations_path)) {
		if ($dh = opendir($compilations_path)) {
			while (($_file = readdir($dh)) !== false) {
				if (substr($_file, -9) != ".comp.php") {
					continue;
				}
				include ($compilations_path. $_file);
			}
			closedir($dh);
		}
	}


	include $_SESSION['INSTALL']["install_path"]."template/header.stpl";
	include $_SESSION['INSTALL']["install_path"]."template/step_".$_GET["step"].".stpl";
	include $_SESSION['INSTALL']["install_path"]."template/footer.stpl";

?>