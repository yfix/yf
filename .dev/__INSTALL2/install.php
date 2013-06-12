<?php
function html($vars = array()) {
	ob_start();
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
				<div class="controls"><input type="text" id="install_yf_path" name="install_yf_path" placeholder="Filesystem path to YF" value="{INSTALL_YF_PATH}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_db_host">Database Host</label>
				<div class="controls"><input type="text" id="install_db_host" name="install_db_host" placeholder="Database Host" value="{INSTALL_DB_HOST}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_db_name">Database Name</label>
				<div class="controls"><input type="text" id="install_db_name" name="install_db_name" placeholder="Database Name" value="{INSTALL_DB_NAME}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_db_user">Database Username</label>
				<div class="controls"><input type="text" id="install_db_user" name="install_db_user" placeholder="Database Username" value="{INSTALL_DB_USER}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_db_pswd">Database Password</label>
				<div class="controls"><input type="text" id="install_db_pswd" name="install_db_pswd" placeholder="Database Password" value="{INSTALL_DB_PSWD}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_db_prefix">Database Prefix</label>
				<div class="controls"><input type="text" id="install_db_prefix" name="install_db_prefix" placeholder="Database Prefix" value="{INSTALL_DB_PREFIX}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_admin_login">Administrator Login</label>
				<div class="controls"><input type="text" id="install_admin_login" name="install_admin_login" placeholder="Administrator Login" value="{INSTALL_ADMIN_LOGIN}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_admin_pswd">Administrator Password</label>
				<div class="controls"><input type="text" id="install_admin_pswd" name="install_admin_pswd" placeholder="Administrator Password" value="{INSTALL_ADMIN_PSWD}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_rw_base">URL Rewrites Base</label>
				<div class="controls"><input type="text" id="install_rw_base" name="install_rw_base" placeholder="URL Rewrites Base" value="{INSTALL_RW_BASE}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_web_path">Web Path</label>
				<div class="controls"><input type="text" id="install_web_path" name="install_web_path" placeholder="Web Path" value="{INSTALL_WEB_PATH}"></div>
			</div>
			<div class="control-group">
				<label class="control-label" for="install_web_name">Website Name</label>
				<div class="controls"><input type="text" id="install_web_name" name="install_web_name" placeholder="Website Name" value="{INSTALL_WEB_NAME}"></div>
			</div>
			<div class="control-group">
				<div class="controls">
					<label class="checkbox"><input type="checkbox" name="install_checkbox_rw_enabled" value="1" {INSTALL_CHECKBOX_RW_ENABLED}>Enable URL Rewrites</label>
					<label class="checkbox"><input type="checkbox" name="install_checkbox_db_create" value="1" {INSTALL_CHECKBOX_DB_CREATE}>Create Database if not exists</label>
					<label class="checkbox"><input type="checkbox" name="install_checkbox_db_drop_existing" value="1" {INSTALL_CHECKBOX_DB_DROP_EXISTING}>Drop Existing Tables</label>
					<label class="checkbox"><input type="checkbox" name="install_checkbox_demo_data" value="1" {INSTALL_CHECKBOX_DEMO_DATA}>Load Demo Data</label>
					<label class="checkbox"><input type="checkbox" name="install_checkbox_debug_info" value="1" {INSTALL_CHECKBOX_DEBUG_INFO}>Show Debug Info</label>
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
$vars = array(
	"FORM_ACTION"	=> $_SERVER["PHP_SELF"],
);
$defaults = array(
	"INSTALL_YF_PATH"		=> dirname(dirname(dirname(__FILE__)))."/",
	"INSTALL_DB_HOST"		=> "localhost",
	"INSTALL_DB_NAME"		=> "test_".substr(md5(microtime()), 0, 6),
	"INSTALL_DB_USER"		=> "root",
#	"INSTALL_DB_PSWD"		=> "",
	"INSTALL_DB_PREFIX"		=> "test_",
	"INSTALL_WEB_PATH"		=> _get_default_web_path(),
	"INSTALL_ADMIN_LOGIN"	=> "admin",
	"INSTALL_ADMIN_PSWD"	=> "123456",
	"INSTALL_RW_BASE"		=> "/",
	"INSTALL_WEB_NAME"		=> "YF Website",
	"INSTALL_CHECKBOX_RW_ENABLED"		=> "1",
	"INSTALL_CHECKBOX_DB_CREATE"		=> "1",
	"INSTALL_CHECKBOX_DB_DROP_EXISTING"	=> "1",
	"INSTALL_CHECKBOX_DEMO_DATA"		=> "1",
	"INSTALL_CHECKBOX_DEBUG_INFO"		=> "",
);
$keys = array(
	"INSTALL_YF_PATH",
	"INSTALL_DB_HOST",
	"INSTALL_DB_NAME",
	"INSTALL_DB_USER",
	"INSTALL_DB_PSWD",
	"INSTALL_DB_PREFIX",
	"INSTALL_WEB_PATH",
	"INSTALL_ADMIN_LOGIN",
	"INSTALL_ADMIN_PSWD",
	"INSTALL_RW_BASE",
	"INSTALL_WEB_NAME",
	"INSTALL_CHECKBOX_RW_ENABLED",
	"INSTALL_CHECKBOX_DB_CREATE",
	"INSTALL_CHECKBOX_DB_DROP_EXISTING",
	"INSTALL_CHECKBOX_DEMO_DATA",
	"INSTALL_CHECKBOX_DEBUG_INFO",
);
foreach ((array)$keys as $k) {
	$val = isset($_POST[$k]) ? $_POST[$k] : $defaults[$k];
	if (false !== strpos($k, 'INSTALL_CHECKBOX_') && $val) {
		$val = "checked";
	}
	$vars[$k] = $val;
}
html($vars);
