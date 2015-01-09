<?php

define('APP_PATH', __DIR__.'/');
define('PROJECT_PATH', APP_PATH.'public/');

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// serve the requested resource as-is.
if (preg_match('/\.(?:jpg|jpeg|gif|gz|zip|flv|rar|wmv|avi|css|swf|png|htc|ico|mpeg|mpg|txt|mp3|mov|js|woff|ttf|svg)$/', $path) && false === strpos($path, '../../') && file_exists(PROJECT_PATH. $path)) {
	return false;
// Init full YF stack
} else {
	$CONF['css_framework'] = 'bs3';
	$CONF['DEF_BOOTSTRAP_THEME'] = 'bootstrap';
	$PROJECT_CONF['tpl']['REWRITE_MODE'] = 1;
	define('DEBUG_MODE', isset($_GET['debug']));
	define('STORAGE_PATH', APP_PATH.'tests_tmp_storage/');
	define('SITE_DEFAULT_PAGE', './?object=docs');
	define('YF_PATH', dirname(dirname(__DIR__)).'/');
	require YF_PATH.'classes/yf_main.class.php';
	new yf_main('user', $no_db_connect = 0, $auto_init_all = 1);
}
