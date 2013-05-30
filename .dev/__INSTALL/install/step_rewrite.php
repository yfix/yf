<?php

require dirname(__FILE__)."/header.php";

if(isset($_POST["next"])){

	if(isset($_POST["rewrite"])){
		$_SESSION['INSTALL']["rewrite"] = $_POST["rewrite"];
	}else{
		unset($_SESSION['INSTALL']["rewrite"]);
	}
	
	$_SESSION['INSTALL']["rewrite_base"] = $_POST["rewrite_base"];
	$_SESSION['INSTALL']["language_in_project"] = $_POST["language_in_project"];
	$_SESSION['INSTALL']["site_name"]	= $_POST["site_name"];
	$_SESSION['INSTALL']["theme"]		= $_POST["theme"];

	$_SESSION['INSTALL']["show_install_debug_info"] = $_POST["show_install_debug_info"];
	
	$log_text .= "\n//********* Other options **********//\n";
	$log_text .= "Enable rewrite: ".isset($_POST["rewrite"]) ? "yes\n" : "no\n";
	$log_text .= "Rewrite base: ".$_POST["rewrite_base"]."\n";
	$log_text .= "Site name: ".$_POST["site_name"]."\n";
	$log_text .= "Language in project: ".$_POST["language_in_project"]."\n";
	$log_text .= "Theme: ".$_POST["theme"]."\n";

	add_log($log_text);

	header("Refresh: 0;URL=".$_SESSION['INSTALL']['_WEB_PATH']."install.php?step=".$next_step);
}

if(isset($_POST["prev"])){
	header("Refresh: 0;URL=".$_SESSION['INSTALL']['_WEB_PATH']."install.php?step=".$back_step);
}

	$themes_path = dirname(__FILE__)."/themes/";
	$themes_for_template = array();
	if (is_dir($themes_path)) {
		if ($dh = opendir($themes_path)) {
			while (($_file = readdir($dh)) !== false) {
				if ($_file == "." || $_file == ".." || $_file == ".svn" || $_file == ".git" || !is_dir($themes_path. $_file)) {
					continue;
				}
				$themes_for_template[basename($_file)] = basename($_file);
			}
			closedir($dh);
		}
	}

	$install_lang = $_SESSION['INSTALL']["language_in_project"] ? $_SESSION['INSTALL']["language_in_project"] : $_SESSION['INSTALL']["language_select"];

	include $_SESSION['INSTALL']["install_path"]."template/header.stpl";
	include $_SESSION['INSTALL']["install_path"]."template/step_".$_GET["step"].".stpl";
	include $_SESSION['INSTALL']["install_path"]."template/footer.stpl";

?>