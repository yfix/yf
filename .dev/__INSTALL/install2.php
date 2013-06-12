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
{install_log}
	</div>
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
	exit();
}
/*
$errors = array(
	'install_db_pswd'	=> 'Wrong Password',
);
*/
if ($errors) {
	html('form', $vars, $errors);
	exit();
}
//////////////////////////////////
define('DB_TYPE',	'mysql5');
define('DB_HOST',	$_POST['install_db_host']);
define('DB_NAME',	$_POST['install_db_name']);
define('DB_USER',	$_POST['install_db_user']);
define('DB_PSWD',	$_POST['install_db_pswd']);
define('DB_PREFIX',	$_POST['install_db_prefix']);
define('DB_CHARSET','utf8');

define('DEBUG_MODE', true);

define('DB_PREFIX', $_POST['install_db_prefix']);
define('YF_PATH',	$_POST['install_yf_path']);
require YF_PATH. 'classes/yf_main.class.php';
new yf_main("user", $no_db_connect = false, $auto_init_all = false);
///////////////////////////////////
$index_file_content = '<?php
define("DEBUG_MODE", false);
define("YF_PATH", "'.YF_PATH.'");
define("WEB_PATH", "'.$_POST['install_web_path'].'");
define("SITE_DEFAULT_PAGE", "./?object=home_page");
define("SITE_ADVERT_NAME", "'.$_POST['install_web_name'].'");
require dirname(__FILE__)."/project_conf.php";
$GLOBALS["PROJECT_CONF"]["tpl"]["REWRITE_MODE"] = true;
require YF_PATH."classes/yf_main.class.php";
new yf_main("user", $no_db_connect = false, $auto_init_all = true);
';
$fpath = PROJECT_PATH.'index.php';
if (!file_exists($fpath)) {
	file_put_contents($fpath, $index_file_content);
}

$db_setup_file_content = '<?php
define("DB_TYPE",	"mysql5");
define("DB_HOST",	$_POST["install_db_host"]);
define("DB_NAME",	$_POST["install_db_name"]);
define("DB_USER",	$_POST["install_db_user"]);
define("DB_PSWD",	$_POST["install_db_pswd"]);
define("DB_PREFIX",	$_POST["install_db_prefix"]);
define("DB_CHARSET","utf8");
';
$fpath = PROJECT_PATH.'db_setup.php';
if (!file_exists($fpath)) {
	file_put_contents($fpath, $db_setup_file_content);
}

$admin_index_file_content = '<?php
define("DEBUG_MODE", false);
define("YF_PATH", "'.YF_PATH.'");
define("SITE_DEFAULT_PAGE", "./?object=admin_home");
define("ADMIN_FRAMESET_MODE", 1);
require dirname(dirname(__FILE__))."/project_conf.php";
require YF_PATH."classes/yf_main.class.php";
new yf_main("admin", $no_db_connect = false, $auto_init_all = true);
';
$d = PROJECT_PATH.'admin/';
if (!file_exists($d)) {
	mkdir($d, 0777, true);
}
$fpath = PROJECT_PATH.'admin/index.php';
if (!file_exists($fpath)) {
	file_put_contents($fpath, $admin_index_file_content);
}
/*
//----------------------------------------------------------------
// Prepare database structure
//----------------------------------------------------------------
$GLOBALS['INSTALL']['import_tables'] = array(
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
foreach ((array)$GLOBALS['INSTALL']['import_tables'] as $value){
	$_temp_array[$value] = $value;
}
$GLOBALS['INSTALL']['import_tables'] = $_temp_array;
unset($_temp_array);


//----------------------------------------------------------------
// delete or ignore already existed tables
$Q = mysql_query("SHOW TABLES LIKE '".$_SESSION['INSTALL']['prefix']."%'");
while ($A = mysql_fetch_array($Q)){
	$tables_in_base[$A[0]] = $A[0];
} 

if (!empty($tables_in_base)) {
	if ($_SESSION['INSTALL']["delete_table"]) {
		foreach ((array)$tables_in_base as $value){
			mysql_query("DROP TABLE IF EXISTS `".$value."`");
			echo "&#160;&#160;&#160;<small>"; ti("delete table"); echo " - ".$value."</small><br>";
		}
	} else {
		foreach ((array)$tables_in_base as $value){
			$value = str_replace($_SESSION['INSTALL']['prefix'],"", $value);
			unset($GLOBALS['INSTALL']['import_tables'][$value]);
			echo "&#160;&#160;&#160;<small>"; ti("ignore table"); echo " - ".$_SESSION['INSTALL']['prefix'].$value."</small><br>";
		}
	}
}


/*
// create tables with yf
$log_text .= "Create db tables: \n";
ti("create tables : ");
db()->query("SELECT * FROM ".db("admin")." LIMIT 1");
$log_text .= "-admin\n";
db()->query("SELECT * FROM ".db("admin_groups")." LIMIT 1");
$log_text .= "-admin_groups\n";

foreach ((array)$GLOBALS['INSTALL']['import_tables'] as $value){
	$value = str_replace("sys_", "", $value);
	db()->query("SELECT * FROM ".db($value)." LIMIT 1");
	$log_text .= "-".$value."\n";
}

echo "<span class='green'>OK</span><BR>";

//----------------------------------------------------------------
ti("import tables : ");
// import tables from files
$log_text .= "Import db tables: \n";
foreach ((array)$GLOBALS['INSTALL']['import_tables'] as $value){
	if ($value == "sys_user_modules") {

		include $_SESSION['INSTALL']["install_path"]."data_user_modules.php";
		db()->REPLACE(db("user_modules"), db()->es($GLOBALS['INSTALL']["data_user_modules"]));

	} elseif ($value == "sys_menu_items") {

		include $_SESSION['INSTALL']["install_path"]."data_menu_items.php";
		db()->REPLACE(db("menu_items"), db()->es($GLOBALS['INSTALL']["data_menu_items"]));

	} else {
		import($_SESSION['INSTALL']["install_path"]."sql/".$value.".sql", $_SESSION['INSTALL']['prefix']);
	}
	echo "&#160;&#160;&#160;<small>"; ti("import table"); echo " - ".$_SESSION['INSTALL']['prefix'].$value."</small><br>";
	$log_text .= "-".$_SESSION['INSTALL']['prefix'].$value."\n";
}
if ($_SESSION['INSTALL']["user_info_dynamic"]) {
	db()->query("SELECT * FROM ".db("user_data_main")." LIMIT 1");
	db()->query("SELECT * FROM ".db("user_data_stats")." LIMIT 1");
	db()->query("SELECT * FROM ".db("user_data_info_fields")." LIMIT 1");
	db()->query("SELECT * FROM ".db("user_data_info_values")." LIMIT 1");
	db()->query("SELECT * FROM ".db("user_data_ban")." LIMIT 1");
}

echo "<span class='green'>OK</span><BR>";

//----------------------------------------------------------------
$log_text .= "Import initial data: ";
// import tables from file, initial data
if(isset($_SESSION['INSTALL']["import_initial_data"])){
	ti("import initial data : ");

	db()->query("SELECT * FROM ".db("user")." LIMIT 1");
	db()->query("SELECT * FROM ".db("gallery_photos")." LIMIT 1");
	db()->query("SELECT * FROM ".db("gallery_folders")." LIMIT 1");
	db()->query("SELECT * FROM ".db("forum_topics")." LIMIT 1");
	db()->query("SELECT * FROM ".db("forum_posts")." LIMIT 1");
	db()->query("SELECT * FROM ".db("forum_forums")." LIMIT 1");
	db()->query("SELECT * FROM ".db("blog_posts")." LIMIT 1");
	db()->query("SELECT * FROM ".db("blog_settings")." LIMIT 1");
	db()->query("SELECT * FROM ".db("articles_texts")." LIMIT 1");
	db()->query("SELECT * FROM ".db("interests_keywords")." LIMIT 1");
	db()->query("SELECT * FROM ".db("interests")." LIMIT 1");
	db()->query("SELECT * FROM ".db("news")." LIMIT 1");
	db()->query("SELECT * FROM ".db("comments")." LIMIT 1");
	db()->query("SELECT * FROM ".db("favorites")." LIMIT 1");
	db()->query("SELECT * FROM ".db("friends")." LIMIT 1");
	db()->query("SELECT * FROM ".db("friends_users")." LIMIT 1");
	db()->query("SELECT * FROM ".db("handshake")." LIMIT 1");
	db()->query("SELECT * FROM ".db("ignore_list")." LIMIT 1");
	db()->query("SELECT * FROM ".db("mailarchive")." LIMIT 1");
	db()->query("SELECT * FROM ".db("polls")." LIMIT 1");
	db()->query("SELECT * FROM ".db("poll_votes")." LIMIT 1");
	db()->query("SELECT * FROM ".db("reput_total")." LIMIT 1");
	db()->query("SELECT * FROM ".db("static_pages")." LIMIT 1");
	db()->query("SELECT * FROM ".db("tags")." LIMIT 1");
	db()->query("SELECT * FROM ".db("tags_settings")." LIMIT 1");
	db()->query("SELECT * FROM ".db("activity_logs")." LIMIT 1");
	
	import($_SESSION['INSTALL']["install_path"]."sql/_initial_data_en.sql", $_SESSION['INSTALL']['prefix']);
	$log_text .= "OK\n";

	// Load custom language initial data
	if ($_SESSION['INSTALL']["language_in_project"]){
		$_custom_lang_path = $_SESSION['INSTALL']["install_path"]."sql/_initial_data_".$_SESSION['INSTALL']["language_in_project"].".sql";
		if (file_exists($_custom_lang_path)) {
			import($_custom_lang_path, $_SESSION['INSTALL']['prefix']);
		}
	}

	$log_text .= "Synchronize forum: ";
	// Sync forum after inserting initial data
	ob_start(); // To catch any errors or content inside
		$FORUM_OBJ = main()->init_class("forum", USER_MODULES_DIR, 1);
		// Synchronize board
		$SYNC_OBJ = main()->init_class("forum_sync", FORUM_MODULES_DIR);

		$SYNC_OBJ->_sync_board();
	ob_end_clean();

	// Sync dynamic fields with single table (old-style)
	if ($_SESSION['INSTALL']["user_info_dynamic"]) {

		$GLOBALS['PROJECT_CONF']["user_data"]["MODE"] = "DYNAMIC";

		$Q = db()->query("SELECT * FROM `".db("user")."`");
		while ($A = db()->fetch_assoc($Q)) {
			$result = update_user($A["id"], $A);
		}
	}

	echo "<span class='green'>OK</span><BR>";
	$log_text .= "OK\n";
}


//----------------------------------------------------------------
// Load compilations hooks
if ($_SESSION['INSTALL']["compilations"] != "all"){
	$compilation_file = dirname(__FILE__)."/compilations/". $_SESSION['INSTALL']["compilations"]. ".comp.php";
	if (file_exists($compilation_file)) {
		include $compilation_file;
	}	
}


//----------------------------------------------------------------
$log_text .= "Create .htaccess file: ";
// htaccess settings
if (isset($_SESSION['INSTALL']["rewrite"])) {
	$htaccess_file_content = file_get_contents($_SESSION['INSTALL']["install_path"]."htaccess.txt");
	
	db()->UPDATE(db("settings"), array(
		"value"	=> 1,				
	), "`id`=4");	
	
} else {
	$htaccess_file_content = file_get_contents($_SESSION['INSTALL']["install_path"]."htaccess2.txt");
}

$htaccess_file_content = str_replace("%%%#path#%%%", $_SESSION['INSTALL']["rewrite_base"], $htaccess_file_content);

ti("set .htaccess : ");
file_put_contents('./.htaccess', $htaccess_file_content);

echo "<span class='green'>OK</span><BR>";
$log_text .= "OK\n";

//----------------------------------------------------------------
// Activate installed modules
if (!empty($_SESSION['INSTALL']["modules"])){
	$log_text .= "Set module settings: ";
	ti("set modules settings : ");

	foreach ((array)$_SESSION['INSTALL']["modules"] as $key => $value){
		db()->UPDATE(db("menu_items"), array(
			"active"	=> 1,				
		), "`id`=".$key);	
	}

	echo "<span class='green'>OK</span><BR>";
	$log_text .= "OK\n";
}

$log_text .= "Set admin login and password: ";
ti("set admin login and password : ");

db()->UPDATE(db("admin"), array(
	"login"		=> db()->es($_SESSION['INSTALL']["admin_name"]),
	"password"	=> md5($_SESSION['INSTALL']["admin_pass1"]),
	"add_date"	=> time(),
), "`id`=1");	

echo "<span class='green'>OK</span><BR>";
$log_text .= "OK\n";



//----------------------------------------------------------------
// copy files
//----------------------------------------------------------------

$log_text .= "Copy data: ";
ti("copy data : ");
$OBJ_DIR = main()->init_class("dir", "classes/");
$OBJ_DIR->copy_dir($_SESSION['INSTALL']["install_path"]."../data", "./", "", "/\.(svn|git)/");
$theme = $_SESSION['INSTALL']["theme"];
if (!empty($theme)) {
	$target_dir = str_replace("\\", "/", realpath("./"))."/templates/".$theme."/";
	if (!file_exists($target_dir)) {
		$OBJ_DIR->mkdir_m($target_dir);
	}
	$OBJ_DIR->copy_dir($_SESSION['INSTALL']["install_path"]."themes/".$theme."/", $target_dir, "", "/\.(svn|git)/");
}
echo "<span class='green'>OK</span><BR><BR>";
$log_text .= "OK\n";


//----------------------------------------------------------------
// Finishing...

rename("./install.php", "./install.php.finished");

ti("Install complete");
$log_text .= "\nInstall complete\n";
echo "<br><a href=\"./index.php\">"; ti("home page"); echo "</a> ("; ti("sample username"); echo ": <b>test</b>, "; ti("password"); echo ": <b>test</b>)<br>";
echo "<a href=\"./admin/index.php\">"; ti("admin home page"); echo "</a><br>";

add_log($log_text);
echo "</tr></td>";

include $_SESSION['INSTALL']["install_path"]."template/footer.stpl";

// Display debug console
if ($_SESSION['INSTALL']["show_install_debug_info"]) {
	echo common()->show_debug_info();
}
*/
//////////////////////////////////
$vars["install_log"] = $body;
html('results', $vars);

