<?php

// TODO: form validation
// TODO: add language selector $_POST['install_project_lang']

class yf_core_install {

	/**
	*/
	function show_html($page = 'form', $vars = array(), $errors = array()) {
		if (php_sapi_name() == 'cli' || !$_SERVER['PHP_SELF']) {
			return print '__CONSOLE_INSTALL__'.PHP_EOL;
		}
		$cur_dir = realpath('./');
		if (!is_writable($cur_dir)) {
			$error = 'Current dir: '.$cur_dir.' is not writable, please fix filesystem permissions.';
		}
		ob_start();
?>
<!DOCTYPE html>
<html>
<head>
	<title>YF Installation</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="//netdna.bootstrapcdn.com/bootswatch/2.3.2/<?php echo installer()->bs_current_theme(); ?>/bootstrap.min.css" rel="stylesheet">
	<style type="text/css">
.sidebar-nav { padding: 9px 0; }
.dropdown-menu .sub-menu { left: 100%; position: absolute; top: 0; visibility: hidden; margin-top: -1px; }
.dropdown-menu li:hover .sub-menu { visibility: visible; }
.dropdown:hover .dropdown-menu { display: block; }
.nav-tabs .dropdown-menu, .nav-pills .dropdown-menu, .navbar .dropdown-menu { margin-top: 0; }
.navbar .sub-menu:before { border-bottom: 7px solid transparent; border-left: none; border-right: 7px solid rgba(0, 0, 0, 0.2); border-top: 7px solid transparent; left: -7px; top: 10px; }
.navbar .sub-menu:after { border-top: 6px solid transparent; border-left: none; border-right: 6px solid #fff; border-bottom: 6px solid transparent; left: 10px; top: 11px; left: -6px; }
	</style>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js" type="text/javascript"></script>
	<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js" type="text/javascript"></script>
	<script type="text/javascript">
	$(function(){
		$(".theme-selector > li > a").click(function(){
			var theme = this.id.substr(9) // 9 == strlen('theme_id_')
			document.cookie='yf_theme=' + theme;
			window.location.reload();
			return false;
		})
	})
	</script>
</head>
<body>
	<div class="navbar">
		<div class="navbar-inner">
			<a class="brand" href="https://github.com/yfix/yf">YF Framework</a>
			<ul class="nav">
				<li class=""><a href="https://github.com/yfix/yf">Home</a></li>
				<li class="dropdown">
					<a class="dropdown-toggle" data-toggle="dropdown">Select theme <b class="caret"></b></a>
					<ul class="dropdown-menu theme-selector">
<?php
			foreach ((array)installer()->bs_get_avail_themes() as $theme) {
				echo '<li><a href="#" id="theme_id_'.$theme.'">'.$theme.'</a></li>';
			}
?>
					</ul>
				</li>
			</ul>
		</div>
	</div>
<?php
		if ($error) {
			echo '<div class="alert alert-danger">'.$error.'</div>';
		}
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
</body>
</html>
<?php
		$html = ob_get_clean();
		$replace = array();
		foreach ((array)$vars as $k => $v) {
			$replace['{'.$k.'}'] = htmlspecialchars($v, ENT_QUOTES);
		}
		echo str_replace(array_keys($replace), array_values($replace), $html);
		return installer();
	}

	/**
	*/
	function bs_get_avail_themes() {
		return array('amelia','cerulean','cosmo','cyborg','flatly','journal','readable','simplex','slate','spacelab','spruce','superhero','united');
	}

	/**
	*/
	function bs_current_theme() {
		$theme = 'slate'; // Default
		$avail_themes = installer()->bs_get_avail_themes();
		if ($_COOKIE['yf_theme'] && in_array($_COOKIE['yf_theme'], $avail_themes)) {
			$theme = $_COOKIE['yf_theme'];
		}
		return $theme;
	}

	/**
	*/
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

	/**
	*/
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
			'install_rw_base'					=> installer()->get_default_rewrite_base(),
			'install_web_name'					=> 'YF Website',
			'install_checkbox_rw_enabled'		=> '',
			'install_checkbox_db_create'		=> '1',
			'install_checkbox_db_drop_existing'	=> '1',
			'install_checkbox_demo_data'		=> '',
			'install_checkbox_debug_info'		=> '',
		);
	}

	/**
	*/
	function get_default_web_path() {
		$request_uri	= $_SERVER['REQUEST_URI'];
		$cur_web_path	= $request_uri[strlen($request_uri) - 1] == '/' ? substr($request_uri, 0, -1) : dirname($request_uri);
		return '//'.$_SERVER['HTTP_HOST'].str_replace(array("\\",'//'), '/', $cur_web_path.'/');
	}

	/**
	*/
	function get_default_rewrite_base() {
		return dirname($_SERVER['REQUEST_URI']) != '/' ? dirname($_SERVER['REQUEST_URI']) .'/' : '/';
	}

	/**
	*/
	function prepare_vars() {
		$vars = array(
			'FORM_ACTION'	=> $_SERVER['PHP_SELF'],
		);
		$defaults = installer()->get_form_defaults();
		foreach ((array)installer()->get_form_keys() as $k => $desc) {
			$val = isset($_POST[$k]) ? $_POST[$k] : $defaults[$k];
			if (false !== strpos($k, 'install_checkbox_') && $val) {
				$val = 'checked';
			}
			$vars[$k] = $val;
		}
		return $vars;
	}

	/**
	*/
	function set_php_conf() {
		define('PROJECT_PATH', $_POST['install_project_path'] ?: realpath('./').'/');
		define('INCLUDE_PATH', PROJECT_PATH);
		$GLOBALS['PROJECT_CONF']['main']['USE_CUSTOM_ERRORS'] = 1;
		$GLOBALS['PROJECT_CONF']['main']['SESSION_OFF'] = 1;
		$GLOBALS['PROJECT_CONF']['db']['ALLOW_AUTO_CREATE_DB'] = 1;
		$GLOBALS['PROJECT_CONF']['db']['RECONNECT_NUM_TRIES'] = 1;
		ini_set('display_errors', 'on');
		ini_set('memory_limit', '512M');
		error_reporting(E_ALL ^E_NOTICE);
		return installer();
	}

	/**
	*/
	function init_yf_core() {
		define('DB_TYPE',	'mysql5');
		define('DB_HOST',	$_POST['install_db_host']);
		define('DB_NAME',	$_POST['install_db_name']);
		define('DB_USER',	$_POST['install_db_user']);
		define('DB_PSWD',	$_POST['install_db_pswd']);
		define('DB_PREFIX',	$_POST['install_db_prefix']);
		define('DB_CHARSET','utf8');

		define('DEBUG_MODE', (int)$_POST['install_checkbox_debug_info']);

		define('DB_PREFIX', $_POST['install_db_prefix']);
		if (!defined('YF_PATH')) {
			define('YF_PATH',	$_POST['install_yf_path']);
			require YF_PATH. 'classes/yf_main.class.php';
			new yf_main('user', $no_db_connect = false, $auto_init_all = false);
		}

		define('INSTALLER_PATH', YF_PATH.'.dev/'.basename(__DIR__).'/');
		return installer();
	}

	/**
	*/
	function test_db_connection() {
		$test = db()->query('SHOW TABLES');
		if (!$test && !db()->db_connect_id) {
			$error = db()->error();
			if ($error['code'] == 9999) { // YF special code when cannot connect
				installer()->cannot_connect_to_db = true;
			}
		}
		return !installer()->cannot_connect_to_db;
	}

	/**
	*/
	function write_db_setup() {
		$db_setup_file_content = '<?php
define(\'DB_TYPE\',	\'mysql5\');
define(\'DB_HOST\',	\''.$_POST['install_db_host'].'\');
define(\'DB_NAME\',	\''.$_POST['install_db_name'].'\');
define(\'DB_USER\',	\''.$_POST['install_db_user'].'\');
define(\'DB_PSWD\',	\''.$_POST['install_db_pswd'].'\');
define(\'DB_PREFIX\',	\''.$_POST['install_db_prefix'].'\');
define(\'DB_CHARSET\',\'utf8\');';
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

	/**
	*/
	function write_user_index_php($rewrite_enabled = true) {
		$index_file_content = '<?php
$dev_settings = dirname(dirname(__FILE__)).\'/.dev/override.php\';
if (file_exists($dev_settings)) {
    require_once $dev_settings;
}
$saved_settings = dirname(__FILE__).\'/saved_settings.php\';
if (file_exists($saved_settings)) {
    require_once $saved_settings;
}
define(\'DEBUG_MODE\', false);
define(\'YF_PATH\', \''.YF_PATH.'\');
define(\'WEB_PATH\', \''.rtrim($_POST['install_web_path'], '/').'/\');
define(\'SITE_DEFAULT_PAGE\', \'./?object=home_page\');
define(\'SITE_ADVERT_NAME\', \''.$_POST['install_web_name'].'\');
require dirname(__FILE__).\'/project_conf.php\';'.PHP_EOL
.($rewrite_enabled ? '$PROJECT_CONF[\'tpl\'][\'REWRITE_MODE\'] = true;'.PHP_EOL : '')
.'require YF_PATH.\'classes/yf_main.class.php\';
new yf_main(\'user\', $no_db_connect = false, $auto_init_all = true);';
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

	/**
	*/
	function write_admin_index_php($rewrite_enabled = true) {
		$admin_index_file_content = '<?php
$dev_settings = dirname(dirname(dirname(__FILE__))).\'/.dev/override.php\';
if (file_exists($dev_settings)) {
    require_once $dev_settings;
}
$saved_settings = dirname(dirname(__FILE__)).\'/saved_settings.php\';
if (file_exists($saved_settings)) {
    require_once $saved_settings;
}
define(\'DEBUG_MODE\', false);
define(\'YF_PATH\', \''.YF_PATH.'\');
define(\'WEB_PATH\', \''.rtrim($_POST['install_web_path'], '/').'/\');
define(\'ADMIN_WEB_PATH\', WEB_PATH. basename(dirname(__FILE__)).\'/\');
//define(\'ADMIN_WEB_PATH\', \'//\'.$_SERVER[\'HTTP_HOST\'].\'/\'.basename(dirname(__FILE__)).\'/\');
define(\'ADMIN_SITE_PATH\', dirname(__FILE__).\'/\');
define(\'SITE_DEFAULT_PAGE\', \'./?object=admin_home\');
define(\'ADMIN_FRAMESET_MODE\', 1);
require dirname(dirname(__FILE__)).\'/project_conf.php\';
require YF_PATH.\'classes/yf_main.class.php\';
new yf_main(\'admin\', $no_db_connect = false, $auto_init_all = true);';
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

	/**
	*/
	function import_table_data ($table, $dir = '') {
		if (!$dir) {
			$dir = INSTALLER_PATH.'install/data/';
		}
		$file = $dir. $table.'.data.php';
		if (!file_exists($file)) {
			return false;
		}
		include $file;
		if (empty($data)) {
			return false;
		}
		return db()->replace_safe(DB_PREFIX.$table, $data);
	}

	/**
	*/
	function import_base_db_structure() {
		$import_tables = array(
			'static_pages',
			'user',
		);
		$suffix = '.sql.php';
		$suffix_len = strlen($suffix);
		$sql_paths = array(
			'yf'		=> YF_PATH.'share/db_installer/sql/sys_*'.$suffix,
			'yf_p2'		=> YF_PATH.'priority2/share/db_installer/sql/sys_*'.$suffix,
			'yf_plugins'=> YF_PATH.'plugins/*/share/db_installer/sql/sys_*'.$suffix,
			'yf_install'=> INSTALLER_PATH.'installer/data/*'.$suffix,
		);
		foreach ((array)$sql_paths as $pattern) {
			foreach ((array)glob($pattern) as $f) {
				$import_tables[] = substr(basename($f), 0, -$suffix_len);
			}
		}
		$import_tables = array_combine($import_tables, $import_tables);

		// delete or ignore already existed tables
		$Q = db()->query('SHOW TABLES LIKE "'.DB_PREFIX.'%"');
		while ($A = db()->fetch_row($Q)){
			$existing_db_tables[$A[0]] = $A[0];
		} 
		if (!empty($existing_db_tables)) {
			if ($_POST['install_checkbox_db_drop_existing']) {
				foreach ((array)$existing_db_tables as $value){
					db()->query('DROP TABLE IF EXISTS `'.$value.'`');
				}
			} else {
				foreach ((array)$existing_db_tables as $value) {
					$value = str_replace(DB_PREFIX,'', $value);
					unset($import_tables[$value]);
				}
			}
		}
		foreach ((array)$import_tables as $table) {
// TODO: replace with direct CREATE TABLE from _class('db_installer')
			//db()->query('SELECT * FROM '.db($table).' LIMIT 1');
			db()->utils()->create_table($table);
		}
		foreach ((array)$import_tables as $table) {
			$this->import_table_data($table);
		}
		return installer();
	}

	/**
	*/
	function import_demo_data() {
		$lang = $_POST['install_project_lang'];

		$suffix = '.data.php';
		$suffix_len = strlen($suffix);
		$data_paths['en'] = INSTALLER_PATH.'install/data_en/*'.$suffix;
		if ($lang && $lang != 'en') {
			$data_paths[$lang] = INSTALLER_PATH.'install/data_'.$lang.'/*'.$suffix;
		}
		$tables = array();
		foreach ((array)$data_paths as $pattern) {
			foreach ((array)glob($pattern) as $f) {
				$tables[] = substr(basename($f), 0, -$suffix_len);
			}
		}
		$tables = array_combine($tables, $tables);
		// Important: this is needed to initialize YF database structure installer and create these tables, if not done before
		foreach ((array)$tables as $table) {
			db()->query('SELECT * FROM '.db($table).' LIMIT 1');
		}
		$dir = INSTALLER_PATH.'install/data_en/';
		foreach ((array)glob($dir.'*.data.php') as $f) {
			$_table = substr(basename($f), 0, -strlen('.data.php'));
			$this->import_table_data($_table, $dir);
		}
		if ($lang) {
			$dir = INSTALLER_PATH.'install/data_'.$lang.'/';
			foreach ((array)glob($dir.'*.data.php') as $f) {
				$_table = substr(basename($f), 0, -strlen('.data.php'));
				$this->import_table_data($_table, $dir);
			}
		}
		db()->update_safe('sys_menu_items', array('active' => 1), '1=1');
		return installer();
	}

	/**
	*/
	function write_htaccess($rewrite_enabled = true) {
		if ($rewrite_enabled) {
			$htaccess_file_content = file_get_contents(INSTALLER_PATH.'install/htaccess.txt');
			db()->update_safe('sys_settings', array('value' => 1), 'id=4');
		} else {
			$htaccess_file_content = file_get_contents(INSTALLER_PATH.'install/htaccess2.txt');
		}
		file_put_contents(PROJECT_PATH.'.htaccess', str_replace('%%%#path#%%%', $_POST['install_rw_base'], $htaccess_file_content));
		return installer();
	}

	/**
	*/
	function set_admin_login_pswd() {
		db()->replace_safe('sys_admin', array(
			'id'		=> 1,
			'login'		=> $_POST['install_admin_login'],
			'password'	=> md5($_POST['install_admin_pswd']),
			'first_name'=> $_POST['install_admin_login'],
			'add_date'	=> gmmktime(),
			'group'		=> 1,
			'active'	=> 1,
		));
		return installer();
	}

	/**
	*/
	function copy_project_skeleton() {
		_class('dir')->copy_dir(INSTALLER_PATH.'skel/', PROJECT_PATH, '', '/\.(svn|git)/');
		return installer();
	}
}

/**
*/
function installer() {
	static $installer;
	if (!is_object($installer)) {
		$installer = new yf_core_install();
	}
	return $installer;
}
/////////////////////////////
$errors = array();
if (empty($_POST)) { // Initial page
	installer()->show_html('form', installer()->prepare_vars());
	exit();
}

installer()
	->set_php_conf()
	->init_yf_core()
	->test_db_connection()
;
if (!installer()->test_db_connection()) {
	$msg = 'Cannot connect to db server';
	$errors['install_db_host'] = $msg;
	$errors['install_db_user'] = $msg;
	$errors['install_db_pswd'] = $msg;
}
if ($errors) {
	installer()->show_html('form', installer()->prepare_vars(), $errors);
	exit();
}
$url_rewrite = $_POST['install_checkbox_rw_enabled'];
installer()
	->import_base_db_structure()
	->write_db_setup()
	->write_user_index_php($url_rewrite)
	->write_admin_index_php($url_rewrite)
	->write_htaccess($url_rewrite)
	->set_admin_login_pswd()
	->copy_project_skeleton()
;
if ($_POST['install_checkbox_demo_data']) {
	installer()->import_demo_data();
	_class('dir')->copy_dir(INSTALLER_PATH.'install/demo_skel/', PROJECT_PATH, '', '/\.(svn|git)/');
}
$debug_info = $_POST['install_checkbox_debug_info'] ? tpl()->parse('debug_console_js'). common()->show_debug_info() : '';
$vars = installer()->prepare_vars();

# TODO: create some log
$vars['install_log'] = '';

installer()->show_html('results', $vars);
echo $debug_info;
