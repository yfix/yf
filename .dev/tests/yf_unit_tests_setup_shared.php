<?php

define('APP_PATH', __DIR__.'/_tmp/');
define('STORAGE_PATH', __DIR__.'/_tmp/');
$_SERVER['HTTP_HOST'] = 'test.dev';
if (!function_exists('main')) {
	$CONF['cache']['DRIVER'] = 'tmp';
	$CONF['cache']['NO_CACHE'] = true;
	$CONF['MEMCACHED_HOST'] = getenv('YF_MEMCACHED_HOST') ?: '127.0.0.1';
	$CONF['MEMCACHED_PORT'] = getenv('YF_MEMCACHED_PORT') ?: '11211';
	define('YF_IN_UNIT_TESTS', true);
	define('YF_PATH', dirname(dirname(__DIR__)).'/');
	require YF_PATH.'classes/yf_main.class.php';
	new yf_main($MAIN_TYPE ?: 'user', $no_db_connect = 0, $auto_init_all = 0, $CONF);
	date_default_timezone_set('Europe/Kiev');
	ini_set('display_errors', 'on');
	error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);
}
if (class_exists('PHPUnit_Framework_TestCase')) {
abstract class yf_unit_tests extends PHPUnit_Framework_TestCase {
	protected $backupGlobals = false;
	protected $backupStaticAttributes = false;
	protected $runTestInSeparateProcess = false;
	protected $preserveGlobalState = false;
	protected $inIsolation = false;
}
}