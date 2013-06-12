<?php
define('PROJECT_PATH', realpath("./")."/");
define('INCLUDE_PATH', PROJECT_PATH);
$GLOBALS['PROJECT_CONF']['main']['USE_CUSTOM_ERRORS'] = 1;

ini_set('display_errors', 'on');
error_reporting(E_ALL ^E_NOTICE);
ini_set('short_open_tag', '1');

$keys = array(
	'install_yf_path'		=> 'Filesystem path to YF',
	'install_db_host'		=> 'Database Host',
	'install_db_name'		=> 'Database Name',
	'install_db_user'		=> 'Database Username',
	'install_db_pswd'		=> 'Database Password',
	'install_db_prefix'		=> 'Database Prefix',
	'install_admin_login'	=> 'Administrator Login',
	'install_admin_pswd'	=> 'Administrator Password',
	'install_rw_base'		=> 'URL Rewrites Base',
	'install_web_path'		=> 'Web Path',
	'install_web_name'		=> 'Website Name',
	'install_checkbox_rw_enabled'		=> 'Enable URL Rewrites',
	'install_checkbox_db_create'		=> 'Create Database if not exists',
	'install_checkbox_db_drop_existing'	=> 'Drop Existing Tables',
	'install_checkbox_demo_data'		=> 'Load Demo Data',
	'install_checkbox_debug_info'		=> 'Show Debug Info',
);

function html($page = 'form', $vars = array(), $errors = array()) {
	ob_start();
	if ($page == 'form') {
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
<?php
	global $keys;
	foreach ((array)$keys as $name => $desc) {
		if (false !== strpos($name, 'install_checkbox_')) {
			continue;
		}
		echo '
			<div class="control-group '.(isset($errors[$name]) ? 'error' : '').'">
				<label class="control-label" for="'.$name.'">'.$desc.'</label>
				<div class="controls"><input type="text" id="'.$name.'" name="'.$name.'" placeholder="'.$desc.'" value="'.htmlspecialchars($vars[$name], ENT_QUOTES).'">'
					.(isset($errors[$name]) ? '<span class="help-inline">'.$errors[$name].'</span>' : '')
				.'</div>
			</div>
		';
	}

?>
			<div class="control-group">
				<div class="controls">
<?php
	foreach ((array)$keys as $name => $desc) {
		if (false === strpos($name, 'install_checkbox_')) {
			continue;
		}
		echo '
					<label class="checkbox"><input type="checkbox" name="'.$name.'" value="1" '.$vars[$name].'>'.htmlspecialchars($desc, ENT_QUOTES).'</label>
		';
	}
?>
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
	} elseif ($page == 'results') {
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
			<p class="lead">YF Framework installation complete.</p>
		</div>
	</header>
	<div class="container">
		<div class="control-group">
			<div class="controls">
				<a class="btn" href="{install_web_path}">User Side</a>
				<a class="btn" href="{install_web_path}admin/">Admin Side</a>
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
	$request_uri	= $_SERVER['REQUEST_URI'];
	$cur_web_path	= $request_uri[strlen($request_uri) - 1] == '/' ? substr($request_uri, 0, -1) : dirname($request_uri);
	return '//'.$_SERVER['HTTP_HOST'].str_replace(array("\\",'//'), '/', $cur_web_path.'/');
}
function _prepare_vars() {
	$vars = array(
		'FORM_ACTION'	=> $_SERVER['PHP_SELF'],
	);
	$defaults = array(
		'install_yf_path'		=> dirname(dirname(dirname(__FILE__))).'/',
		'install_db_host'		=> 'localhost',
		'install_db_name'		=> 'test_'.substr(md5(microtime()), 0, 6),
		'install_db_user'		=> 'root',
		'install_db_pswd'		=> '',
		'install_db_prefix'		=> 'test_',
		'install_web_path'		=> _get_default_web_path(),
		'install_admin_login'	=> 'admin',
		'install_admin_pswd'	=> '123456',
		'install_rw_base'		=> '/',
		'install_web_name'		=> 'YF Website',
		'install_checkbox_rw_enabled'		=> '1',
		'install_checkbox_db_create'		=> '1',
		'install_checkbox_db_drop_existing'	=> '1',
		'install_checkbox_demo_data'		=> '1',
		'install_checkbox_debug_info'		=> '',
	);
	global $keys;
	foreach ((array)$keys as $k => $desc) {
		$val = isset($_POST[$k]) ? $_POST[$k] : $defaults[$k];
		if (false !== strpos($k, 'install_checkbox_') && $val) {
			$val = 'checked';
		}
		$vars[$k] = $val;
	}
	return $vars;
}
$vars = _prepare_vars();
if (empty($_POST)) {
	html('form', $vars);
} else {
/*
	$errors = array(
		'install_db_pswd'	=> 'Wrong Password',
	);
*/
	if ($errors) {
		html('form', $vars, $errors);
	} else {
		html('results', $vars);
	}
}
