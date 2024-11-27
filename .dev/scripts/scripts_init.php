<?php

// $force = trim($argv[2] ?? "");
// $project_path = trim($argv[1] ?? "");
// if (! $project_path) {
//     exit('Error: missing project_path. Example: ' . basename($argv[0]) . ' /var/www/yfix.net/' . PHP_EOL);
// }
// $project_path = rtrim($project_path, '/') . '/';
// foreach (['', '*/', '*/*/', '*/*/*/'] as $g) {
//     $paths = glob($project_path . $g . 'db_setup.php');
//     if (! $paths || ! isset($paths[0])) {
//         continue;
//     }
//     $fp = $paths[0];
//     if ($fp && file_exists($fp)) {
//         if (basename(dirname($fp)) == 'config') {
//             $app_path = dirname(dirname($fp)) . '/';
//             $override_path = $app_path . '.dev/override.php';
//             if (file_exists($override_path)) {
//                 require_once $override_path;
//             }
//         }
//         require_once $fp;
//         break;
//     }
// }

$_GET['object'] = 'not_exists';
$_SERVER['HTTP_HOST'] = 'test.dev';

define('DB_TYPE', 'mysqli');
define('DB_HOST', getenv('YF_DB_HOST') ?: 'mysql-tmp');
define('DB_NAME', getenv('YF_DB_NAME') ?: 'tests');
define('DB_USER', getenv('YF_DB_USER') ?: 'root');
define('DB_PSWD', is_string(getenv('YF_DB_PSWD')) ? getenv('YF_DB_PSWD') : '123456');
define('DB_PREFIX', is_string(getenv('YF_DB_PREFIX')) ? getenv('YF_DB_PREFIX') : 'tmp_');

define('YF_PATH', '/var/www/vendor/yf31/');
if (!defined('YF_PATH')) {
    define('YF_PATH', dirname(dirname(dirname(__DIR__))) . '/');
}

define('DEBUG_MODE', false);
define('APP_PATH', __DIR__ . '/.tmp/');
define('STORAGE_PATH', __DIR__ . '/.tmp/');
define('CONFIG_PATH', __DIR__ . '/');
$_SERVER['HTTP_HOST'] = 'test.dev';
if (! function_exists('main')) {
    define('YF_IN_UNIT_TESTS', true);
    $CONF['cache']['DRIVER'] = 'tmp';
    $CONF['cache']['NO_CACHE'] = true;
    $CONF['css_framework'] = 'bs3';
    $CONF['FORCE_LOCALE'] = 'en';
    $CONF['REDIS_HOST'] = getenv('REDIS_HOST') ?: 'redis';
    $CONF['REDIS_PORT'] = getenv('REDIS_PORT') ?: 6379;
    $CONF['MEMCACHED_HOST'] = getenv('YF_MEMCACHED_HOST') ?: 'memcached';
    $CONF['MEMCACHED_PORT'] = getenv('YF_MEMCACHED_PORT') ?: '11211';
    if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
        error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING);
    }
    require YF_PATH . 'classes/yf_main.class.php';
    new \yf_main($MAIN_TYPE ?? 'user', $no_db_connect = false, $auto_init_all = false, $CONF);

    date_default_timezone_set('Europe/Kiev');
    ini_set('display_errors', 'on');
}

// if (! defined('DB_NAME_GEONAMES')) {
//     define('DB_PREFIX_GEONAMES', '');
//     define('DB_HOST_GEONAMES', getenv('YF_DB_HOST') ?: 'mysql');
//     define('DB_USER_GEONAMES', getenv('YF_DB_USER') ?: 'root');
//     define('DB_PSWD_GEONAMES', is_string(getenv('YF_DB_PSWD')) ? getenv('YF_DB_PSWD') : '123456');
//     define('DB_NAME_GEONAMES', 'geonames');
// }
// function db_geonames($tbl_name = '')
// {
//     $_instance = &$GLOBALS[__FUNCTION__];
//     if ($_instance === null) {
//         $db_class = load_db_class();
//         if ($db_class) {
//             $_instance = new $db_class('mysql5', DB_PREFIX_GEONAMES);
//             $_instance->DB_PREFIX = DB_PREFIX_GEONAMES;
//             $_instance->connect(DB_HOST_GEONAMES, DB_USER_GEONAMES, DB_PSWD_GEONAMES, DB_NAME_GEONAMES, true);
//         } else {
//             $_instance = false;
//         }
//     }
//     if (! is_object($_instance)) {
//         return $tbl_name ? $tbl_name : new yf_missing_method_handler(__FUNCTION__);
//     }
//     return $tbl_name ? $_instance->_real_name($tbl_name) : $_instance;
// }
