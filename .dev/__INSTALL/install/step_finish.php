<?php

require dirname(__FILE__)."/header.php";

include $_SESSION['INSTALL']["install_path"]."template/header.stpl";

echo "<tr><td>";

//----------------------------------------------------------------
$log_text .= "\n//********* Installation process **********//\n";
// start install
ti("connect to MySQL : ");
$log_text .= "Connect to MySQL: ";
mysql_connect($_SESSION['INSTALL']["dbhost"],$_SESSION['INSTALL']["dbuser"],$_SESSION['INSTALL']["dbpasswd"]) or error();
echo "<span class='green'>OK</span><BR>";
$log_text .= "OK\n";

$log_text .= "Check MySQL version (>=4.1): ";
ti("check MySQL version"); echo " >=4.1 : ";
	$mysql_server_info = mysql_get_server_info();
	if(!empty($mysql_server_info)){
		if (version_compare($mysql_server_info, '4.1') < 0){
			echo "<span class='red'>NO</span><BR>";
			$log_text .= "NO\n";
			return;
		} else {
			echo "<span class='green'>OK</span><BR>";
			$log_text .= "OK\n";
		}
	}else{
		$log_text .= "No MySQL\n";
		echo "<span class='red'>NO MySQL</span><BR>";
		return;
	}

//----------------------------------------------------------------
ti("connect to database");
echo " '".$_SESSION['INSTALL']["dbname"]."' : ";
if($_SESSION['INSTALL']["create_database"]){
	$log_text .= "Create database ".$_SESSION['INSTALL']["dbname"].": ";
	mysql_query("CREATE DATABASE IF NOT EXISTS `".$_SESSION['INSTALL']["dbname"]."` DEFAULT CHARACTER SET = utf8") or error();
	$log_text .= "OK\n";
}
$log_text .= "connect to database ".$_SESSION['INSTALL']["dbname"]." : ";
mysql_select_db($_SESSION['INSTALL']["dbname"]) or error();
echo "<span class='green'>OK</span><BR>";
$log_text .= "OK\n";


//----------------------------------------------------------------
ti("save settings : ");
$log_text .= "Create project structure: ";
// index.php settings

if ($_SESSION['INSTALL']["compilations"] == "all"){
	$site_default_page = "home_page";
} else {
	$site_default_page = $_SESSION['INSTALL']["compilations"];
}

//----------------------------------------------------------------
//$_color_theme = $_SESSION['INSTALL']["color_theme"] ? "define(\"DEFAULT_COLOR_THEME\", \"".$_SESSION['INSTALL']["color_theme"]."\");\r\n" : "";
$_color_theme = $_SESSION['INSTALL']["theme"] ? "define(\"DEFAULT_SKIN\", \"".$_SESSION['INSTALL']["theme"]."\");\r\n" : "";
// Convert russian symbols into unicode (standard function "utf8_encode" does not work here)
//if (preg_match("/[ -ï]+/ims", $_SESSION['INSTALL']["site_name"])) {
//	$_SESSION['INSTALL']["site_name"] = rus2uni(stripslashes($_SESSION['INSTALL']["site_name"]));
//	$_SESSION['INSTALL']["site_name"] = iconv("CP1251", "UTF-8", stripslashes($_SESSION['INSTALL']["site_name"]));
//}

// DO NOT TOUCH!!! IMPORTANT!!!
$framework_path = str_replace(array("\\", "//"), "/", realpath($_SESSION['INSTALL']["framework_path"]))."/";

if(isset($_SESSION['INSTALL']["rewrite"])){
	$rewrite_mode_status = "1";
}else{
	$rewrite_mode_status = "0";
}

//----------------------------------------------------------------
$_add_content = "";
if ($_SESSION['INSTALL']["user_info_dynamic"]) {
	// Note this is a string!
	$_add_content = '$GLOBALS["PROJECT_CONF"]["main"]["USER_INFO_DYNAMIC"] = true;';
}

$index_file_content = <<<EOD
<?php
define("DEBUG_MODE", substr(\$_SERVER["REQUEST_URI"], -5) == "debug" ? true : false);
define("YF_PATH", "{$framework_path}");
define("WEB_PATH", "{$_SESSION['INSTALL']["web_path"]}");
define("SITE_DEFAULT_PAGE", "./?object={$site_default_page}");
//define("DEFAULT_SKIN", "user");
define("DEFAULT_LANG", "{$_SESSION['INSTALL']["language_in_project"]}");
define("SITE_ADVERT_NAME", "{$_SESSION['INSTALL']["site_name"]}");
{$_color_theme}
require dirname(__FILE__)."/project_conf.php"; // Such call required to allow console calls
\$GLOBALS["PROJECT_CONF"]["tpl"]["REWRITE_MODE"] = {$rewrite_mode_status};
{$_add_content}
require YF_PATH."classes/yf_main.class.php";
new yf_main("user", 0, 1);
EOD;
$index_file_content.="\n?>";

//----------------------------------------------------------------
// db_setup.php settings
$db_setup_file_content = <<<EOT
<?php;
define('DB_TYPE',		"mysql41");		// Type of current DB Server to use";
define('DB_HOST',		"{$_SESSION['INSTALL']['dbhost']}"); 		// DB Server Host Name";
define('DB_NAME',		"{$_SESSION['INSTALL']['dbname']}");		// Database Name";
define('DB_USER',		"{$_SESSION['INSTALL']['dbuser']}");		// DB Server User";
define('DB_PSWD',		"{$_SESSION['INSTALL']['dbpasswd']}");	// DB Server Password";
define('DB_PREFIX',		"{$_SESSION['INSTALL']['prefix']}");		// Table prefix";
define('DB_CHARSET',	"utf8"); 					// Encoding
EOT;
$db_setup_file_content.="\n?>";

// admin_index.php settings
$admin_index_file_content = <<<EOD
<?php
define("DEBUG_MODE", false);
define("YF_PATH", "{$framework_path}");
define("SITE_DEFAULT_PAGE", "./?object=admin_home");
define("ADMIN_FRAMESET_MODE", 1);
require dirname(dirname(__FILE__))."/project_conf.php";
{$_add_content}
require YF_PATH."classes/yf_main.class.php";
new yf_main("admin", 0, 1);
EOD;
$admin_index_file_content.="\n?>";

//----------------------------------------------------------------
file_put_contents('./index.php', $index_file_content);
file_put_contents('./db_setup.php', $db_setup_file_content);
mkdir('./admin');
file_put_contents('./admin/index.php', $admin_index_file_content);
echo "<span class='green'>OK</span><BR>";
$log_text .= "OK\n";


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



//----------------------------------------------------------------
// Init Framework here
//----------------------------------------------------------------

define("DEBUG_MODE", true);

define("DB_PREFIX", $_SESSION['INSTALL']['prefix']);
define("YF_PATH", $framework_path);
require YF_PATH."classes/yf_main.class.php";
new yf_main("user", $no_db_connect = false, $auto_init_all = false);


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

#rename("./install.php", "./install.php.finished");

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
?>