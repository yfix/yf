<?php

// TODO: form validation here
// TODO: check database connection
// TODO: add language selector $_POST["install_project_lang"]

class yf_core_install {
	function show_html($page = 'form', $vars = array(), $errors = array()) {
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
<?php
		if ($page == 'form') {
?>
	<header>
		<div class="container">
			<p class="lead">Welcome to YF Framework installation process. Submit form below to finish.</p>
		</div>
	</header>
	<div class="container">
		<form class="form-horizontal" method="post" action="{FORM_ACTION}">
<?php
		foreach ((array)installer()->get_form_keys() as $name => $desc) {
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
		foreach ((array)installer()->get_form_keys() as $name => $desc) {
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
<?php
		} elseif ($page == 'results') {
?>
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
	<div class="container">
{install_log}
	</div>
<?php
		}
?>
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
		return installer();
	}
	function get_default_web_path() {
		$request_uri	= $_SERVER['REQUEST_URI'];
		$cur_web_path	= $request_uri[strlen($request_uri) - 1] == '/' ? substr($request_uri, 0, -1) : dirname($request_uri);
		return '//'.$_SERVER['HTTP_HOST'].str_replace(array("\\",'//'), '/', $cur_web_path.'/');
	}
	function get_form_keys() {
		return array(
			'install_yf_path'					=> 'Filesystem path to YF',
			'install_db_host'					=> 'Database Host',
			'install_db_name'					=> 'Database Name',
			'install_db_user'					=> 'Database Username',
			'install_db_pswd'					=> 'Database Password',
			'install_db_prefix'					=> 'Database Prefix',
			'install_admin_login'				=> 'Administrator Login',
			'install_admin_pswd'				=> 'Administrator Password',
			'install_rw_base'					=> 'URL Rewrites Base',
			'install_web_path'					=> 'Web Path',
			'install_web_name'					=> 'Website Name',
			'install_checkbox_rw_enabled'		=> 'Enable URL Rewrites',
			'install_checkbox_db_create'		=> 'Create Database if not exists',
			'install_checkbox_db_drop_existing'	=> 'Drop Existing Tables',
			'install_checkbox_demo_data'		=> 'Load Demo Data',
			'install_checkbox_debug_info'		=> 'Show Debug Info',
		);
	}
	function get_form_defaults() {
		return array(
			'install_yf_path'					=> dirname(dirname(dirname(__FILE__))).'/',
			'install_db_host'					=> 'localhost',
			'install_db_name'					=> 'test_'.substr(md5(microtime()), 0, 6),
			'install_db_user'					=> 'root',
			'install_db_pswd'					=> '',
			'install_db_prefix'					=> 'test_',
			'install_web_path'					=> installer()->get_default_web_path(),
			'install_admin_login'				=> 'admin',
			'install_admin_pswd'				=> '123456',
			'install_rw_base'					=> '/',
			'install_web_name'					=> 'YF Website',
			'install_checkbox_rw_enabled'		=> '1',
			'install_checkbox_db_create'		=> '1',
			'install_checkbox_db_drop_existing'	=> '1',
			'install_checkbox_demo_data'		=> '1',
			'install_checkbox_debug_info'		=> '',
		);
	}
	function prepare_vars() {
		$vars = array(
			'FORM_ACTION'	=> $_SERVER['PHP_SELF'],
		);
		$defaults = installer()->get_form_detaults();
		foreach ((array)installer()->get_form_keys() as $k => $desc) {
			$val = isset($_POST[$k]) ? $_POST[$k] : $defaults[$k];
			if (false !== strpos($k, 'install_checkbox_') && $val) {
				$val = 'checked';
			}
			$vars[$k] = $val;
		}
		return $vars;
	}
	function set_php_conf() {
		define('PROJECT_PATH', realpath("./")."/");
		define('INCLUDE_PATH', PROJECT_PATH);
		$GLOBALS['PROJECT_CONF']['main']['USE_CUSTOM_ERRORS'] = 1;
		$GLOBALS['PROJECT_CONF']['main']['SESSION_OFF'] = 1;
		ini_set('display_errors', 'on');
		ini_set('memory_limit', '512M');
		error_reporting(E_ALL ^E_NOTICE);
		return installer();
	}
	function init_yf_core() {
		define('DB_TYPE',	'mysql5');
		define('DB_HOST',	$_POST['install_db_host']);
		define('DB_NAME',	$_POST['install_db_name']);
		define('DB_USER',	$_POST['install_db_user']);
		define('DB_PSWD',	$_POST['install_db_pswd']);
		define('DB_PREFIX',	$_POST['install_db_prefix']);
		define('DB_CHARSET','utf8');

//		define('DEBUG_MODE', $_POST['install_checkbox_debug_info']);
		define('DEBUG_MODE', 1);

		define('DB_PREFIX', $_POST['install_db_prefix']);
		define('YF_PATH',	$_POST['install_yf_path']);
		require YF_PATH. 'classes/yf_main.class.php';
		new yf_main("user", $no_db_connect = false, $auto_init_all = false);

		define('INSTALLER_PATH', YF_PATH.'.dev/__INSTALL/');
		require (INSTALLER_PATH.'install/function.php');
		return installer();
	}
	function write_db_setup() {
		$db_setup_file_content = '<?php
define("DB_TYPE",	"mysql5");
define("DB_HOST",	"'.$_POST["install_db_host"].'");
define("DB_NAME",	"'.$_POST["install_db_name"].'");
define("DB_USER",	"'.$_POST["install_db_user"].'");
define("DB_PSWD",	"'.$_POST["install_db_pswd"].'");
define("DB_PREFIX",	"'.$_POST["install_db_prefix"].'");
define("DB_CHARSET","utf8");';
		$fpath = PROJECT_PATH.'db_setup.php';
		$d = dirname($fpath);
		if (!file_exists($d)) {
			mkdir($d, 0777, true);
		}
		if (!file_exists($fpath)) {
			file_put_contents($fpath, $db_setup_file_content);
		}
		return installer();
	}
	function write_user_index_php() {
		$index_file_content = '<?php
define("DEBUG_MODE", false);
define("YF_PATH", "'.YF_PATH.'");
define("WEB_PATH", "'.$_POST['install_web_path'].'");
define("SITE_DEFAULT_PAGE", "./?object=home_page");
define("SITE_ADVERT_NAME", "'.$_POST['install_web_name'].'");
require dirname(__FILE__)."/project_conf.php";
$GLOBALS["PROJECT_CONF"]["tpl"]["REWRITE_MODE"] = true;
require YF_PATH."classes/yf_main.class.php";
new yf_main("user", $no_db_connect = false, $auto_init_all = true);';
		$fpath = PROJECT_PATH.'index.php';
		$d = dirname($fpath);
		if (!file_exists($d)) {
			mkdir($d, 0777, true);
		}
		if (!file_exists($fpath)) {
			file_put_contents($fpath, $index_file_content);
		}
		return installer();
	}
	function write_admin_index_php() {
		$admin_index_file_content = '<?php
define("DEBUG_MODE", false);
define("YF_PATH", "'.YF_PATH.'");
define("SITE_DEFAULT_PAGE", "./?object=admin_home");
define("ADMIN_FRAMESET_MODE", 1);
require dirname(dirname(__FILE__))."/project_conf.php";
require YF_PATH."classes/yf_main.class.php";
new yf_main("admin", $no_db_connect = false, $auto_init_all = true);';
		$fpath = PROJECT_PATH.'admin/index.php';
		$d = dirname($fpath);
		if (!file_exists($d)) {
			mkdir($d, 0777, true);
		}
		if (!file_exists($fpath)) {
			file_put_contents($fpath, $admin_index_file_content);
		}
		return installer();
	}
	function import_base_db_structure() {
		$import_tables = array(
			"activity_types",
			"countries",
			"forum_groups",
			"forum_users",
			"moods",
			"states",
			"static_pages",
			"sys_categories",
			"sys_category_items",
			"sys_locale_langs",
			"sys_locale_translate",
			"sys_locale_vars",
			"sys_menu_items",
			"sys_user_groups",
			"sys_user_modules",
			"tips",
			"user",
		);
		$_temp_array = array();
		foreach ((array)$import_tables as $value){
			$_temp_array[$value] = $value;
		}
		$import_tables = $_temp_array;
		unset($_temp_array);

		// delete or ignore already existed tables
		$Q = db()->query("SHOW TABLES LIKE '".DB_PREFIX."%'");
		while ($A = db()->fetch_row($Q)){
			$existing_db_tables[$A[0]] = $A[0];
		} 
		if (!empty($existing_db_tables)) {
			if ($_POST['install_checkbox_db_drop_existing']) {
				foreach ((array)$existing_db_tables as $value){
					db()->query('DROP TABLE IF EXISTS `'.$value.'`');
				}
			} else {
				foreach ((array)$existing_db_tables as $value){
					$value = str_replace(DB_PREFIX,"", $value);
					unset($import_tables[$value]);
				}
			}
		}
		foreach ((array)$import_tables as $table) {
			$table = str_replace("sys_", "", $table);
			db()->query("SELECT * FROM ".db($table)." LIMIT 1");
		}
		foreach (array("admin", "admin_groups") as $table) {
			db()->query("SELECT * FROM ".db($table)." LIMIT 1");
		}
		foreach ((array)$import_tables as $table){
			if ($table == "sys_user_modules") {
				include (INSTALLER_PATH."install/data_user_modules.php");
				db()->replace(db("user_modules"), db()->es($GLOBALS['INSTALL']["data_user_modules"]));
			} elseif ($table == "sys_menu_items") {
				include (INSTALLER_PATH."install/data_menu_items.php");
				db()->replace(db("menu_items"), db()->es($GLOBALS['INSTALL']["data_menu_items"]));
			} else {
				import (INSTALLER_PATH."install/sql/".$table.".sql", DB_PREFIX);
			}
		}
		return installer();
	}
	function import_demo_data() {
		$tables_to_create = array(
			"user",
			"gallery_photos",
			"gallery_folders",
			"forum_topics",
			"forum_posts",
			"forum_forums",
			"blog_posts",
			"blog_settings",
			"articles_texts",
			"interests_keywords",
			"interests",
			"news",
			"comments",
			"favorites",
			"friends",
			"friends_users",
			"handshake",
			"ignore_list",
			"polls",
			"poll_votes",
			"reput_total",
			"static_pages",
			"tags",
			"tags_settings",
			"activity_logs",
		);
		// Important: this is needed to initialize YF database structure installer and create these tables, if not done before
		foreach ($tables_to_create as $table) {
			db()->query("SELECT * FROM ".db($table)." LIMIT 1");
		}
		import(INSTALLER_PATH."install/sql/_initial_data_en.sql", DB_PREFIX);
		if ($_POST["install_project_lang"]){
			$_custom_lang_path = INSTALLER_PATH."install/sql/_initial_data_".$_POST["install_project_lang"].".sql";
			if (file_exists($_custom_lang_path)) {
				import ($_custom_lang_path, DB_PREFIX);
			}
		}

		ob_start();
		module("forum")->_init();
		_class("forum_sync", "modules/forum/")->_sync_board();
		ob_end_clean();

		db()->update(db("menu_items"), array("active" => 1), "1=1");
		return installer();
	}
	function write_htaccess($rewrite_enabled = true) {
		if ($rewrite_enabled) {
			$htaccess_file_content = file_get_contents(INSTALLER_PATH."install/htaccess.txt");
			db()->update(db("settings"), array("value" => 1), "id=4");
		} else {
			$htaccess_file_content = file_get_contents(INSTALLER_PATH."install/htaccess2.txt");
		}
		file_put_contents(PROJECT_PATH.'.htaccess', str_replace("%%%#path#%%%", $_POST['install_rw_base'], $htaccess_file_content));
		return installer();
	}
	function set_admin_login_pswd() {
		db()->update(db("admin"), db()->es(array(
			"login"		=> $_POST['install_admin_login'],
			"password"	=> md5($_POST['install_admin_pswd']),
			"add_date"	=> gmmktime(),
		)), "id=1");
		return installer();
	}
	function copy_project_skeleton() {
		_class("dir")->copy_dir(INSTALLER_PATH.'data/', PROJECT_PATH, '', '/\.(svn|git)/');
		return installer();
	}
}
function installer() {
	static $installer;
	if (!is_object($installer)) {
		$installer = new yf_core_install();
	}
	return $installer;
}
/////////////////////////////
if (empty($_POST)) {
	installer()->show_html('form', installer()->prepare_vars());
	exit();
}
/*
$errors = array(
	'install_db_pswd'	=> 'Wrong Password',
);
*/
if ($errors) {
	installer()->show_html('form', installer()->prepare_vars(), $errors);
	exit();
}
installer()
	->set_php_conf()
	->init_yf_core()
	->write_db_setup()
	->write_user_index_php()
	->write_admin_index_php()
	->write_htaccess($_POST['install_checkbox_rw_enabled'])
	->set_admin_login_pswd()
	->copy_project_skeleton()
;
#installer()->import_base_db_structure();
if ($_POST['install_checkbox_demo_data']) {
	installer()->import_demo_data();
}
installer()->show_html('results', array(
	"install_log" => $_POST['install_checkbox_debug_info'] ? common()->show_debug_info() : "",
));
echo common()->show_debug_info();