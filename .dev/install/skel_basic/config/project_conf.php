<?php
define('CSS_FRAMEWORK',		'bootstrap2');

define('SITE_UPLOADS_DIR',	'uploads/');				// Root folder for all uploads
define('SITE_AVATARS_DIR',	'uploads/avatars/');		// avatars folder

if (!defined('SITE_ADVERT_NAME')) {
	define('SITE_ADVERT_NAME',	'Site name');	// Advertisement name
}
define('SITE_ADVERT_TITLE',	'Site name');	// Advertisement title
define('SITE_ADVERT_URL',	defined('WEB_PATH')?WEB_PATH:'');	// Advertisement URL
define('SITE_ADMIN_NAME',	'Site admin');		// Site Admin name
define('SITE_ADMIN_EMAIL',	'info@'.$_SERVER['HTTP_HOST']);	// Admin's email used in common cases

define('AVATAR_MAX_X',	100);	// Avatar max sizes
define('AVATAR_MAX_Y',	100);
define('THUMB_WIDTH',	120);	// Thumbnail width (default value)
define('THUMB_HEIGHT',	1000);	// Thumbnail maximum height (default value)
define('THUMB_QUALITY',	75);	// JPEG quality
define('MAX_IMAGE_SIZE',5000000);// Max image file size (in bytes)
define('FORCE_RESIZE_IMAGE_SIZE',500000);
define('FORCE_RESIZE_WIDTH',	1280);	// width for force resize
define('FORCE_RESIZE_HEIGHT',	1024);	// height for force resize

if (!function_exists('my_array_merge')) {
	function my_array_merge($a1, $a2) {
		foreach ((array)$a2 as $k => $v) { if (isset($a1[$k]) && is_array($a1[$k])) { if (is_array($a2[$k])) { 
			foreach ((array)$a2[$k] as $k2 => $v2) { if (isset($a1[$k][$k1]) && is_array($a1[$k][$k1])) { $a1[$k][$k2] += $v2; } else { $a1[$k][$k2] = $v2; } 
		} } else { $a1[$k] += $v; } } else { $a1[$k] = $v; } }
		return $a1;
	}
}
$PROJECT_CONF = my_array_merge((array)$PROJECT_CONF, [
	'main'	=> [
		'USE_CUSTOM_ERRORS'		=> 1,
//		'USE_SYSTEM_CACHE'		=> 1,
//		'NO_CACHE_HEADERS'		=> 1,
//		'SPIDERS_DETECTION'		=> 1,
//		'OVERLOAD_PROTECTION'	=> 0,
//		'ALLOW_FAST_INIT'		=> 1,
//		'USE_GEO_IP'			=> 1,
//		'OUTPUT_CACHING'		=> 1,
//		'OUTPUT_GZIP_COMPRESS'	=> 1,
//		'LOG_EXEC'				=> 1,
		'STATIC_PAGES_ROUTE_TOP'=> 1,
	],
	'auth_user' => [
		'URL_SUCCESS_LOGIN' => './?object=account', 
		'EXEC_AFTER_LOGIN'		=> [
			['_add_login_activity'],
		],
	],
	'send_mail'	=> [
		'USE_MAILER'	=> 'phpmailer',
	],
	'tpl' => [
		'ALLOW_LANG_BASED_STPLS' => 1,
//		'REWRITE_MODE'			=> 1,
//		'CUSTOM_META_INFO'		=> 1,
	],
	'graphics'	=> [
//		'META_KEYWORDS'			=> 'keyword',
//		'META_DESCRIPTION'		=> 'description',
	],
	'i18n' => [
		'TRACK_TRANSLATED'  => 1,
	],
	'rewrite'	=> [
		'_rewrite_add_extension'	=> '/',
	],
	'comments'	=> [
		'USE_TREE_MODE' => 1,
	],
	'logs'	=> [
		'_LOGGING'			=> 1,
		'STORE_USER_AUTH'	=> 1,
		'UPDATE_LAST_LOGIN'	=> 1,
	],
]);

$OVERRIDE_CONF_FILE = dirname(__DIR__).'/.dev/override_conf_after.php';
if (file_exists($OVERRIDE_CONF_FILE)) {
	include_once $OVERRIDE_CONF_FILE;
}
// Load auto-configured file
$AUTO_CONF_FILE = __DIR__.'/_auto_conf.php';
if (file_exists($AUTO_CONF_FILE)) {
	@eval('?>'.file_get_contents($AUTO_CONF_FILE));
}
