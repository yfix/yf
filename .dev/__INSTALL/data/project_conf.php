<?php
//--------------
// UPLOADS PATHS
//--------------
define("SITE_UPLOADS_DIR",			"uploads/");				// Root folder for all uploads
define("SITE_AVATARS_DIR",			"uploads/avatars/");		// avatars folder
define("SITE_BLOG_IMAGES_DIR",		"uploads/blog_images/");	// blog images folder
define("SITE_GALLERY_DIR",			"uploads/gallery/");		// gallery root folder
define("SITE_CUSTOM_DESIGN_DIR",	"uploads/custom_design/");	// custom_design root folder
define("SITE_ACCOUNT_VERIFY_DIR",	"uploads/account_verify/");	// account verification photos folder
//--------------
// COMMON USED VARS SECTION
//--------------
if (!defined('SITE_ADVERT_NAME')) {
	define("SITE_ADVERT_NAME",	"Site name");	// Advertisement name
}
define("SITE_ADVERT_TITLE",	"Site name");	// Advertisement title
define("SITE_ADVERT_URL",	defined('WEB_PATH')?WEB_PATH:"");	// Advertisement URL
define("SITE_ADMIN_NAME",	"Site admin");		// Site Admin name
define("SITE_ADMIN_EMAIL",	"info@".$_SERVER["HTTP_HOST"]);	// Admin's email used in common cases
//--------------
// IMAGE OPTIONS
//--------------
define("AVATAR_MAX_X",	100);	// Avatar max sizes
define("AVATAR_MAX_Y",	100);
//define("NETPBM_PATH",	substr(PHP_OS, 0, 3) == 'WIN' ? "d:\\www\\GnuWin32\\bin\\" : "/usr/bin/");	// Leave blank if you want to use GD lib instead of NETPBM
//define("IMAGICK_PATH",substr(PHP_OS, 0, 3) == 'WIN' ? "d:\\www\\imagick\\" : "/usr/bin/");
define("THUMB_WIDTH",	120);	// Thumbnail width (default value)
define("THUMB_HEIGHT",	1000);	// Thumbnail maximum height (default value)
define("THUMB_QUALITY",	75);	// JPEG quality
define("MAX_IMAGE_SIZE",5000000);// Max image file size (in bytes)
//--------------
// Force resizing original photos if their size is greater (in bytes)
define("FORCE_RESIZE_IMAGE_SIZE",500000);
define("FORCE_RESIZE_WIDTH",	1280);	// width for force resize
define("FORCE_RESIZE_HEIGHT",	1024);	// height for force resize
//--------------
if (!function_exists('my_array_merge')) {
	function my_array_merge($a1, $a2) {
		foreach ((array)$a2 as $k => $v) { if (isset($a1[$k]) && is_array($a1[$k])) { if (is_array($a2[$k])) { 
			foreach ((array)$a2[$k] as $k2 => $v2) { if (isset($a1[$k][$k1]) && is_array($a1[$k][$k1])) { $a1[$k][$k2] += $v2; } else { $a1[$k][$k2] = $v2; } 
		} } else { $a1[$k] += $v; } } else { $a1[$k] = $v; } }
		return $a1;
	}
}
// GLOBALS PROJECT MODULES CONFIG VARS
$GLOBALS["PROJECT_CONF"] = array();
$GLOBALS["PROJECT_CONF"] = my_array_merge((array)$GLOBALS["PROJECT_CONF"], array(
	// CORE CLASSES
	"main"	=> array(
		"USE_CUSTOM_ERRORS"		=> 1,
//		"USE_SYSTEM_CACHE"		=> 1,
//		"USE_TASK_MANAGER"		=> 1,
//		"NO_CACHE_HEADERS"		=> 1,
//		"SPIDERS_DETECTION"		=> 1,
//		"OVERLOAD_PROTECTION"	=> 0,
//		"ALLOW_FAST_INIT"		=> 1,
//		"USE_GEO_IP"			=> 1,
//		"OUTPUT_CACHING"		=> 1,
//		"OUTPUT_GZIP_COMPRESS"	=> 1,
		"STATIC_PAGES_ROUTE_TOP"=> 1,
	),
	"auth_user" => array(
		"URL_SUCCESS_LOGIN" => "./?object=account", 
		"EXEC_AFTER_LOGIN"		=> array(
			array("_add_login_activity"),
		),
	),
	"send_mail"	=> array(
		"USE_MAILER"	=> "phpmailer",
	),
	"tpl" => array(
		"ALLOW_LANG_BASED_STPLS" => 1,
		"REWRITE_MODE"			=> 1,
//		"CUSTOM_META_INFO"		=> 1,
	),
	"graphics"	=> array(
//		"META_KEYWORDS"			=> "keyword",
//		"META_DESCRIPTION"		=> "description",
//		"EMBED_CSS"			=> 0,
//		"CACHE_CSS"			=> 1,
		"CSS_ADD_RESET"		=> 1,
	),
	"i18n" => array(
		"TRACK_TRANSLATED"  => 1,
	),
	"debug_info" => array(
		"_SHOW_NOT_TRANSLATED"  => 1,
		"_SHOW_I18N_VARS"   => 1,
	),
	"rewrite"	=> array(
		"_rewrite_add_extension"	=> "/",
	),
	"comments"	=> array(
		"USE_TREE_MODE" => 1,
	),
	"_forum"	=> array(
		"USE_GLOBAL_USERS"		=> 1,
		"ALLOW_WYSIWYG_EDITOR"	=> 0,
		"BB_CODE"				=> 1,
		"ENABLE_SMILIES"		=> 1,
		"SMILIES_IMAGES"		=> 1,
		"SMILIES_SET"			=> 2,
		"ALLOW_POLLS"			=> 1,
	),
	"_forum_def_rights"	=> array(
		"make_polls"	=> 1,
		"vote_polls"	=> 1,
	),
	"gallery"	=> array(
		"ALLOW_RATE"	=> 1,
		"ALLOW_TAGGING"	=> 1,
	),
	"logs"	=> array(
		"_LOGGING"			=> 1,
		"STORE_USER_AUTH"	=> 1,
		"UPDATE_LAST_LOGIN"	=> 1,
	),
	"register"	=> array(
		"NICK_ALLOWED_SYMBOLS"	=> array("а-я","a-z","0-9","_","\-","@","#"," "),
	),
	"validate"	=> array(
		"NICK_ALLOWED_SYMBOLS"	=> array("а-я","a-z","0-9","_","\-","@","#"," "),
	),
	"bb_codes"	=> array(
		"SMILIES_DIR"	=> "uploads/forum/smilies/",
	),
));

$OVERRIDE_CONF_FILE = dirname(dirname(__FILE__))."/.dev/override_conf_after.php";
if (file_exists($OVERRIDE_CONF_FILE)) {
	include_once $OVERRIDE_CONF_FILE;
}
// Load auto-configured file
$AUTO_CONF_FILE = dirname(__FILE__)."/_auto_conf.php";
if (file_exists($AUTO_CONF_FILE)) {
	@eval("?>".file_get_contents($AUTO_CONF_FILE));
}
