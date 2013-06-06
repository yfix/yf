<?php

ini_set("display_errors", "on");
error_reporting(E_ALL ^E_NOTICE);
ini_set("short_open_tag", "1");

session_name("install");
session_start();

// Order is important!
$steps = array(
	"language",
	"requirements",
	"main_settings",
	"compilations",
	"menu",
	"rewrite",
	"finish",
);

/**
* Add log text
*/
function add_log ($text) {
	$log_filename = "./install.log";
	if (!isset($GLOBALS["_install_log_fh"])) {
		$GLOBALS["_install_log_fh"] = fopen($log_filename, "a");
	}
	if (!$GLOBALS["_install_log_fh"]) {
		return false;
	}
	fwrite($GLOBALS["_install_log_fh"], $text);
	return true;	
}

function show_install_html($errors = array()) {
	if ($errors) {
		$error = '<div align="center"><b style="color:red;">'.implode(',', $errors).'</b></div>';
	}
	$_yf_path = (isset($_POST["framework_path"]) ? $_POST["framework_path"] : '../yf/');
	return print '
<html>
<head>
	<title>YF :: Install</title>
	<style type="text/css">
*{
	font-family: Verdana;
	font-size: 12px;
}
body {
	background: #353639;
	color: #EFF0F0;
	min-width: 580px;
	min-height: 300px;
	text-align: center; /*For IE*/
}
h1 {
	font-size: 2em;
	color: #3697E9;
	text-align:center;
	border-bottom: 2px dotted #3697E9;
	font-weight:normal;
	padding-bottom: 0.5em;
}
table{
	text-align: left;
	width: 100%;
}	
th {
	color: #3697E9;
	padding: 1em;
}
#container {
	border-right: 5px solid #4A525E;
	border-bottom: 5px solid #4A525E;
	width: 600px;
	margin:100px auto;
}
#inner {
	border: 3px solid #626C7B;
	padding: 10px;
}
input {
	border: 1px solid #3697E9;
	background: #626C7B;
	color: #FFFFFF;
	margin: 0 5px 0 0;
	font-weight: bold;
}
.btn input {
	padding: 3px 10px;
	cursor: pointer;
	cursor: hand; 
}
.btn input:hover{
	background: #3697E9;
}
	</style>
</head>
<body>
		<div align="center" id="container">
		<div align="center" id="inner">
		<h1>Welcome to <b style="color: #F1CF44;font-size:24px;">YF</b> installation</h1>
		<form action="./install.php" method="post">
				<div align="center"><b style="color:red;">'. $error .'</b></div> 
			<table>
				<tr>
					<td width="30%">YF_PATH:</td>
					<td><input type="text" name="framework_path" value="'. $_yf_path .'" style="width:100%;"></td>
				</tr>
				<tr>
					<td><br /><input type="checkbox" name="show_log" id="show_log" value="1"><label for="show_log">Show log</label></td>
					<td></td>
				</tr>
			</table>
				<div align="right" class="btn"><input type="submit" value="Next &gt;"></div>
			</div>
		</div>
		</form>
	';
}

/************* INSTALLER BODY ************/

// Check correct step
if (isset($_GET["step"]) && !in_array($_GET["step"], $steps)) {
	unset($_GET["step"]);
}

if (!isset($_GET["step"])) {
	$_SESSION = array();

	$request_uri	= getenv("REQUEST_URI");
	$cur_web_path	= $request_uri[strlen($request_uri) - 1] == "/" ? substr($request_uri, 0, -1) : dirname($request_uri);
	$_SESSION['INSTALL']['_WEB_PATH'] = "http://".getenv("HTTP_HOST").str_replace(array("\\","//"), array("/","/"), $cur_web_path."/");
	if (file_exists("install.log")) {
		if (file_exists("install.log.bak")) {
			unlink("install.log.bak");
		}
		rename("install.log", "install.log.bak");		
	}

	$log_text .= "//********* YF **********//\n";
	if (isset($_POST["framework_path"]) && file_exists($_POST["framework_path"]) && file_exists($_POST["framework_path"]."classes/yf_main.class.php")){
		$log_text .= "YF_PATH is ".$_POST["framework_path"]." \n\n";
		add_log($log_text);

		$_GET["step"] = "language";
		
		$_SESSION['INSTALL']["framework_path"] = $_POST["framework_path"];
		$_SESSION['INSTALL']["install_path"] = $_POST["framework_path"].".dev/__INSTALL/install/";

	} else {
		//step 0, get yf path
		$errors = array();
		if ($_POST["framework_path"]) {
			$errors[] = 'Wrong YF_PATH';
		}
		show_install_html($errors);
		exit();
	}
}

if (isset($_SESSION['INSTALL']["install_path"])) {
	include $_SESSION['INSTALL']["install_path"]."language.php";
	include $_SESSION['INSTALL']["install_path"]."function.php";
} else {
	header("Refresh: 0;URL=install.php");
}

if ($_GET["step"] && isset($_SESSION['INSTALL']["install_path"])) {
	$steps_flipped = array_flip($steps);
	$_cur_step_num = $steps_flipped[$_GET["step"]];
	$next_step = $steps[$_cur_step_num + 1];
	$back_step = $steps[$_cur_step_num -1];

	include $_SESSION['INSTALL']["install_path"]."step_".$_GET["step"].".php";
}
