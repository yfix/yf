<?php
define('PROJECT_PATH', realpath("./")."/");
define('INCLUDE_PATH', PROJECT_PATH);
$GLOBALS['PROJECT_CONF']['main']['USE_CUSTOM_ERRORS'] = 1;

ini_set("display_errors", "on");
error_reporting(E_ALL ^E_NOTICE);
ini_set("short_open_tag", "1");

function html($page = "form", $vars = array()) {
	ob_start();
	if ($page == "form") {
?>
<!DOCTYPE html>
<html>
<head>
	<title>YF Installation</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="//netdna.bootstrapcdn.com/bootswatch/2.3.2/slate/bootstrap.min.css" rel="stylesheet">
</head>
<body>
	<div class="navbar">
		<div class="navbar-inner">
			<a class="brand" href="https://github.com/yfix/yf">YF Framework</a>
			<ul class="nav">
				<li class=""><a href="https://github.com/yfix/yf">Home</a></li>
			</ul>
		</div>
	</div>
	<header>
		<div class="container">
			<p class="lead">Welcome to YF Framework installation process. Submit form below to finish.</p>
		</div>
	</header>
	<div class="container">
		<form class="form-horizontal" method="post" action="{FORM_ACTION}">
			<div class="control-group">
				<label class="control-label" for="install_yf_path">Filesystem path to YF</label>
				<div class="controls"><input type="text" id="install_yf_path" name="install_yf_path" placeholder="Filesystem path to YF" value="{install_yf_path}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_db_host">Database Host</label>
				<div class="controls"><input type="text" id="install_db_host" name="install_db_host" placeholder="Database Host" value="{install_db_host}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_db_name">Database Name</label>
				<div class="controls"><input type="text" id="install_db_name" name="install_db_name" placeholder="Database Name" value="{install_db_name}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_db_user">Database Username</label>
				<div class="controls"><input type="text" id="install_db_user" name="install_db_user" placeholder="Database Username" value="{install_db_user}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_db_pswd">Database Password</label>
				<div class="controls"><input type="text" id="install_db_pswd" name="install_db_pswd" placeholder="Database Password" value="{install_db_pswd}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_db_prefix">Database Prefix</label>
				<div class="controls"><input type="text" id="install_db_prefix" name="install_db_prefix" placeholder="Database Prefix" value="{install_db_prefix}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_admin_login">Administrator Login</label>
				<div class="controls"><input type="text" id="install_admin_login" name="install_admin_login" placeholder="Administrator Login" value="{install_admin_login}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_admin_pswd">Administrator Password</label>
				<div class="controls"><input type="text" id="install_admin_pswd" name="install_admin_pswd" placeholder="Administrator Password" value="{install_admin_pswd}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_rw_base">URL Rewrites Base</label>
				<div class="controls"><input type="text" id="install_rw_base" name="install_rw_base" placeholder="URL Rewrites Base" value="{install_rw_base}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_web_path">Web Path</label>
				<div class="controls"><input type="text" id="install_web_path" name="install_web_path" placeholder="Web Path" value="{install_web_path}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_web_name">Website Name</label>
				<div class="controls"><input type="text" id="install_web_name" name="install_web_name" placeholder="Website Name" value="{install_web_name}"></div>
			</div>
			<div class="control-group">
				<div class="controls">
					<label class="checkbox"><input type="checkbox" name="install_checkbox_rw_enabled" value="1" {install_checkbox_rw_enabled}>Enable URL Rewrites</label>
					<label class="checkbox"><input type="checkbox" name="install_checkbox_db_create" value="1" {install_checkbox_db_create}>Create Database if not exists</label>
					<label class="checkbox"><input type="checkbox" name="install_checkbox_db_drop_existing" value="1" {install_checkbox_db_drop_existing}>Drop Existing Tables</label>
					<label class="checkbox"><input type="checkbox" name="install_checkbox_demo_data" value="1" {install_checkbox_demo_data}>Load Demo Data</label>
					<label class="checkbox"><input type="checkbox" name="install_checkbox_debug_info" value="1" {install_checkbox_debug_info}>Show Debug Info</label>
				</div>
			</div>

			<div class="control-group">
				<div class="controls">
					<button type="submit" class="btn">Install!</button>
				</div>
			</div>
		</form>
	</div>
	<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php
	} elseif ($page == "results") {
?>
<!DOCTYPE html>
<html>
<head>
	<title>YF Installation</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="//netdna.bootstrapcdn.com/bootswatch/2.3.2/slate/bootstrap.min.css" rel="stylesheet">
</head>
<body>
	<div class="navbar">
		<div class="navbar-inner">
			<a class="brand" href="https://github.com/yfix/yf">YF Framework</a>
			<ul class="nav">
				<li class=""><a href="https://github.com/yfix/yf">Home</a></li>
			</ul>
		</div>
	</div>
	<header>
		<div class="container">
			<p class="lead">Welcome to YF Framework installation process. Submit form below to finish.</p>
		</div>
	</header>
	<div class="container">
		<div class="control-group">
			<div class="controls">
				<button type="submit" class="btn">User</button>
				<button type="submit" class="btn">Admin</button>
			</div>
		</div>
	</div>
	<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php
	}
	$html = ob_get_contents();
	ob_end_clean();
	$replace = array();
	foreach ((array)$vars as $k => $v) {
		$replace['{'.$k.'}'] = htmlspecialchars($v, ENT_QUOTES);
	}
	echo str_replace(array_keys($replace), array_values($replace), $html);
}
function _get_default_web_path() {
	$request_uri	= $_SERVER["REQUEST_URI"];
	$cur_web_path	= $request_uri[strlen($request_uri) - 1] == "/" ? substr($request_uri, 0, -1) : dirname($request_uri);
	return "//".$_SERVER["HTTP_HOST"].str_replace(array("\\","//"), array("/","/"), $cur_web_path."/");
}
function _prepare_vars() {
	$vars = array(
		"FORM_ACTION"	=> $_SERVER["PHP_SELF"],
	);
	$defaults = array(
		"install_yf_path"		=> dirname(dirname(dirname(__FILE__)))."/",
		"install_db_host"		=> "localhost",
		"install_db_name"		=> "test_".substr(md5(microtime()), 0, 6),
		"install_db_user"		=> "root",
#		"install_db_pswd"		=> "",
		"install_db_prefix"		=> "test_",
		"install_web_path"		=> _get_default_web_path(),
		"install_admin_login"	=> "admin",
		"install_admin_pswd"	=> "123456",
		"install_rw_base"		=> "/",
		"install_web_name"		=> "YF Website",
		"install_checkbox_rw_enabled"		=> "1",
		"install_checkbox_db_create"		=> "1",
		"install_checkbox_db_drop_existing"	=> "1",
		"install_checkbox_demo_data"		=> "1",
		"install_checkbox_debug_info"		=> "",
	);
	$keys = array(
		"install_yf_path",
		"install_db_host",
		"install_db_name",
		"install_db_user",
		"install_db_pswd",
		"install_db_prefix",
		"install_web_path",
		"install_admin_login",
		"install_admin_pswd",
		"install_rw_base",
		"install_web_name",
		"install_checkbox_rw_enabled",
		"install_checkbox_db_create",
		"install_checkbox_db_drop_existing",
		"install_checkbox_demo_data",
		"install_checkbox_debug_info",
	);
	foreach ((array)$keys as $k) {
		$val = isset($_POST[$k]) ? $_POST[$k] : $defaults[$k];
		if (false !== strpos($k, 'install_checkbox_') && $val) {
			$val = "checked";
		}
		$vars[$k] = $val;
	}
	return $vars;
}
$vars = _prepare_vars();
html("form", $vars);
