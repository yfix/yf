<?php

require dirname(__FILE__)."/header.php";

if(isset($_POST["next"])){

	if($_POST["admin_pass1"] != $_POST["admin_pass2"]){
	   ti("Confirmed password did not match original");
		
		echo "<br><input type=\"button\" value=\"";
		ti("Back");
		echo  "\" onclick=\"javascript:history.back()\">";
		return;
	}

	if(empty($_POST["admin_pass1"])){
		ti("The administrator's password field can't be empty");
		
		echo "<br><input type=\"button\" value=\"";
		ti("Back");
		echo  "\" onclick=\"javascript:history.back()\">";
		return;
	}

	if(empty($_POST["dbname"])){
		ti("The database name field can't be empty");
		echo "<br><input type=\"button\" value=\"";
		ti("Back");
		echo  "\" onclick=\"javascript:history.back()\">";
		return;
	} elseif (!preg_match("/[a-z0-9\-\_]/i", $_POST["dbname"])) {
		ti("The database name can have only latin symbols, numbers, underscore and -");
		echo "<br><input type=\"button\" value=\"";
		ti("Back");
		echo  "\" onclick=\"javascript:history.back()\">";
		return;
	}

	if (!preg_match("/[a-z0-9\-\_]/i", $_POST["dbuser"])) {
		ti("The database user can have only latin symbols, numbers, underscore and -");
		echo "<br><input type=\"button\" value=\"";
		ti("Back");
		echo  "\" onclick=\"javascript:history.back()\">";
		return;
	}

	$log_text .= "\n//********* Requirements test **********//\n";	
$_SESSION['INSTALL']["dbhost"] = $_POST["dbhost"];
	$log_text .= "Database Server Hostname: ".$_POST["dbhost"]."\n";
$_SESSION['INSTALL']["dbname"] = $_POST["dbname"];
	$log_text .= "Database Name: ".$_POST["dbname"]."\n";
if(empty($_POST["create_database"])){
	$_SESSION['INSTALL']["create_database"] = "off";
}else{
	$_SESSION['INSTALL']["create_database"] = $_POST["create_database"];
}
	$log_text .= "Create database if not exists: ".$_SESSION['INSTALL']["create_database"]."\n";
$_SESSION['INSTALL']["dbuser"] = $_POST["dbuser"];
	$log_text .= "Database Username: ".$_SESSION['INSTALL']["dbuser"]."\n";
$_SESSION['INSTALL']["dbpasswd"] = $_POST["dbpasswd"];
$_SESSION['INSTALL']["prefix"] = $_POST["prefix"];
	$log_text .= "Prefix for tables in database: ".$_SESSION['INSTALL']["prefix"]."\n";
if(empty($_POST["create_database"])){
	$_SESSION['INSTALL']["delete_table"] = "off";
}else{
	$_SESSION['INSTALL']["delete_table"] = $_POST["delete_table"];
}
	$log_text .= "Delete tables if exists: ".$_SESSION['INSTALL']["delete_table"]."\n";
$_SESSION['INSTALL']["web_path"] = $_POST["web_path"];
	$log_text .= "Web Path: ".$_SESSION['INSTALL']["web_path"]."\n";
$_SESSION['INSTALL']["framework_path"] = $_POST["framework_path"];
	$log_text .= "YF Framework Path: ".$_SESSION['INSTALL']["framework_path"]."\n";
$_SESSION['INSTALL']["admin_name"] = $_POST["admin_name"];
	$log_text .= "Administrator Username: ".$_SESSION['INSTALL']["admin_name"]."\n";
$_SESSION['INSTALL']["admin_pass1"] = $_POST["admin_pass1"]; 
$_SESSION['INSTALL']["admin_pass2"] = $_POST["admin_pass2"]; 
	$log_text .= "SMTP Configuration\n";
$_SESSION['INSTALL']["smtp_host"] = $_POST["smtp_host"];
	$log_text .= "Host: ".$_SESSION['INSTALL']["smtp_host"]."\n";
$_SESSION['INSTALL']["smtp_port"] = $_POST["smtp_port"];
	$log_text .= "Port: ".$_SESSION['INSTALL']["smtp_port"]."\n";
$_SESSION['INSTALL']["smtp_user_name"] = $_POST["smtp_user_name"];
	$log_text .= "User Name: ".$_SESSION['INSTALL']["smtp_user_name"]."\n";
$_SESSION['INSTALL']["smtp_password"] = $_POST["smtp_password"];
	add_log($log_text);


	header("Refresh: 0;URL=".$_SESSION['INSTALL']['_WEB_PATH']."install.php?step=".$next_step);
}

if(isset($_POST["prev"])){
	header("Refresh: 0;URL=".$_SESSION['INSTALL']['_WEB_PATH']."install.php?step=".$back_step);
}

	include $_SESSION['INSTALL']["install_path"]."smtp_settings.php";

	include $_SESSION['INSTALL']["install_path"]."template/header.stpl";
	include $_SESSION['INSTALL']["install_path"]."template/step_".$_GET["step"].".stpl";
	include $_SESSION['INSTALL']["install_path"]."template/footer.stpl";

?>