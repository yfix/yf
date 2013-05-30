<?php

ini_set("display_errors", "on");
error_reporting(E_ALL ^E_NOTICE);

session_name("install");
session_start();

$install_server_web_path = "http://".$_SERVER["HTTP_HOST"]."/install_server.php";

ini_set("short_open_tag", "1");
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
* Create multiple dirs at one time (eg. mkdir_m("some_dir1/some_dir2/some_dir3"))
*/
function mkdir_m($dir_name, $dir_mode = 0755, $create_index_htmls = 0, $start_folder = "") {
	if (!strlen($dir_name)) {
		return 0;
	}
	// Default start folder to look at
	if (!strlen($start_folder)) {
		$start_folder = INCLUDE_PATH;
	}
	$old_mask = umask(0);
	// Default dir mode
	if (empty($dir_mode)) {
		$dir_mode = 0755;
	}
	// Process given file name
	if (!file_exists($dir_name)) {
		$base_path = OS_WINDOWS ? "" : "/";
		preg_match_all('/([^\/]+)\/?/i', $dir_name, $atmp);
		foreach ((array)$atmp[0] as $val) {
			$base_path = $base_path. $val;
			// Skip if already exists
			if (file_exists($base_path)) {
				continue;
			}
			// Try to create sub dir
			if (!mkdir($base_path, $dir_mode)) {
				return -1;
			}
			chmod($base_path, $dir_mode);
		}
	} elseif (!is_dir($dir_name)) {
		return -2;
	}
	// Create empty index.html in new folder if needed
	if ($create_index_htmls) {
		$index_file_path = str_replace(array('\/',"//"), "/", $dir_name. "/index.html");
		if (!file_exists($index_file_path)) {
			file_put_contents($index_file_path, "");
		}
	}
	umask($old_mask);
	return 0;
}

/**
*/
function get_remote_page($page_url = "") {
	if (empty($page_url)) {
		return false;
	}
	$page_to_check = "";
	$page_url	= str_replace(" ", "%20", trim($page_url));
	$user_agent = "Mozilla/4.0 (compatible; MSIE 6.01; Windows NT 5.1)";
	$referer	= $page_url;
	if ($ch = curl_init()) {
		curl_setopt($ch, CURLOPT_URL, $page_url);
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		curl_setopt($ch, CURLOPT_REFERER, $referer);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$page_to_check = curl_exec ($ch);
		$GLOBALS['_curl_error'] = curl_error($ch);
		curl_close ($ch);
	}
	return $page_to_check;
}

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

	$log_text .= "//********* Profy Framework **********//\n";
	if (isset($_POST["framework_path"]) && file_exists($_POST["framework_path"]) && file_exists($_POST["framework_path"]."classes/profy_main.class.php")){
		$log_text .= "Profy Framework path is ".$_POST["framework_path"]." \n\n";
		add_log($log_text);

		$_GET["step"] = "language";
		
		$_SESSION['INSTALL']["framework_path"] = $_POST["framework_path"];
		$_SESSION['INSTALL']["install_path"] = $_POST["framework_path"]."__INSTALL/install/";

	} elseif (isset($_POST["framework_path"]) && $_POST["remote_install"] && $_POST["install_server_path"]) {
		$log_text .= "Profy Framework path is ".$_POST["framework_path"]." \n\n";

		// Download framework	
		$install_server_web_path = $_POST["install_server_path"];
		if (!defined("INCLUDE_PATH")) {
			define("INCLUDE_PATH", str_replace("\\", "/", dirname(__FILE__))."/");
		}
		define('OS_WINDOWS', substr(PHP_OS, 0, 3) == 'WIN');

		if (!file_exists($_POST["framework_path"])) {
			// Create folder for framework
			mkdir_m($_POST["framework_path"]);
		}
		echo "Wait please...<br />";
		// Get files list
		$files_list = get_remote_page($install_server_web_path);

		$framework_path = realpath($_POST["framework_path"])."/";
		$_text = "Getting PROFY_FRAMEWORK remotely from ".$install_server_web_path."<br /><br />\n\n";
		echo ($_POST["show_log"] ? $_text : "");
		$log_text .= $_text;
		echo ($_POST["show_log"] ? "<pre><small>" : "");
		foreach (explode("\n", trim($files_list)) as $fpath) {
			$fpath = trim($fpath);
			if (!$fpath) {
				continue;
			}
			$_text = "Get ".$fpath;
			echo ($_POST["show_log"] ? $_text : "");
			$log_text .= $_text;
			for ($i = 1; $i <= 3; $i++) {
				$file_content = get_remote_page($install_server_web_path."?action=get_file&id=".urlencode($fpath));
				if (!$GLOBALS['_curl_error']) {
					break;
				}
				sleep(1);
			}
			$_file_path	= $framework_path. $fpath;
			$_dir_path	= dirname($_file_path);
			if (!file_exists($_dir_path)) {
				mkdir_m($_dir_path);
			}
			$res = file_put_contents($_file_path, trim($file_content));
			if ($res === false) {
				$_text = " <b style='color:red;'>Failed</b>\n";
			} else {
				$_text = " ".$res."\n";
			}
			echo ($_POST["show_log"] ? $_text : "");
			$log_text .= $_text;
		}
		echo ($_POST["show_log"] ? "</small></pre>" : "");

		if (file_exists($_POST["framework_path"]) && file_exists($_POST["framework_path"]."classes/profy_main.class.php")) {
			$_GET["step"] = "language";
			$_SESSION['INSTALL']["framework_path"] = $_POST["framework_path"];
			$_SESSION['INSTALL']["install_path"] = $_POST["framework_path"]."__INSTALL/install/";
			$log_text .= "\nFramework is copied.\n";
		}
		add_log(strip_tags($log_text));
	} else {
		//step 0, get PROFY_FRAMEWORK path
		?>
<html>
<head>
	<title>Profy Framework :: Install</title>
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
		<h1>Welcome to <b style="color: #F1CF44;font-size:24px;">Profy Framework</b> installation</h1>
		<form action="./install.php" method="post">
			<table>
				<tr>
					<th colspan="2">Please specify Profy Framework Path...</th>
				</tr>
				<tr>
					<td width="30%">Profy Framework Path:</td>
					<td><input type="text" name='framework_path' value='<?=(isset($_POST["framework_path"]) ? $_POST["framework_path"] : '../PROFY_FRAMEWORK/')?>' style='width:100%;'></td>
				</tr>
				<tr>
					<th colspan="2">...or copy it from remote server</td>
				</tr>
				<tr>
					<td>Install server path:</td>
					<td>
						<input type="text" name='install_server_path' value='<?=$install_server_web_path?>' style="width:100%;">
					</td>
				</tr>
				<tr>
					<td></td>
					<td><input type="checkbox" name="remote_install" id="remote_install" value="1"><label for="remote_install">Download remotely</label></td>
				</tr>
				<tr>
					<td><br /><input type="checkbox" name="show_log" id="show_log" value="1"><label for="show_log">Show log</label></td>
					<td></td>
				</tr>
			</table>
<?php if ($_POST["framework_path"]) { ?>
				<div align="center"><b style='color:red;'>Wrong Framework Path</b></div> 
<?php } ?>
				<div align="right" class="btn"><input type="submit" value="Next &gt;"></div>
			</div>
		</div>
		</form>
		<?php
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
?>
</body>
</html>