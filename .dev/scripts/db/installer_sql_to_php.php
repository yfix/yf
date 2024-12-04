#!/usr/bin/env php
<?php

define('DB_TYPE', 'mysqli');
define('DB_HOST', getenv('YF_DB_HOST') ?: 'mysql-tmp');
define('DB_NAME', getenv('YF_DB_NAME') ?: 'tests');
define('DB_USER', getenv('YF_DB_USER') ?: 'root');
define('DB_PSWD', is_string(getenv('YF_DB_PSWD')) ? getenv('YF_DB_PSWD') : '123456');
define('DB_PREFIX', is_string(getenv('YF_DB_PREFIX')) ? getenv('YF_DB_PREFIX') : 'tmp_');

# assume thst db already exists
// exec('mysql -h ' . escapeshellarg(DB_HOST) . ' -u ' . escapeshellarg(DB_USER) . ' -p' . escapeshellarg(DB_PSWD) . ' -e "CREATE DATABASE IF NOT EXISTS ' . DB_NAME . '"');

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
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
    }
    require YF_PATH . 'classes/yf_main.class.php';
    new \yf_main($MAIN_TYPE ?? 'user', $no_db_connect = false, $auto_init_all = false, $CONF);
}

//db()->query('CREATE DATABASE IF NOT EXISTS '.DB_NAME);

function get_table_create_sql($table)
{
    $path = YF_PATH . 'share/db/sql/' . $table . '.db_table_sql.php';
    include $path;
    if (!$data) {
        return false;
    }
    return 'CREATE TABLE IF NOT EXISTS ' . DB_PREFIX . $table . ' (' . $data . ') ENGINE=InnoDB DEFAULT CHARSET=utf8;';
}

mkdir('./data/', 0755, true);
foreach (glob(dirname(dirname(__DIR__)) . '/install/install/sql/*.sql') as $f) {
    $fname = basename($f);
    $table = substr($fname, 0, -strlen('.sql'));
    $tname = DB_PREFIX . $table;
    if ($fname[0] == '_') {
        $lang = substr($fname, -strlen('.sql') - 2, -strlen('.sql'));
        $dir = './data_' . $lang . '/';
        mkdir($dir, 0755, true);
        foreach (explode(';' . PHP_EOL, file_get_contents($f)) as $sql) {
            if (preg_match('/%%prefix%%([a-z0-9_]+)/ims', $sql, $m)) {
                $t = trim($m[1]);
                $create_sql = get_table_create_sql($t);
                if (!$create_sql) {
                    continue;
                }
                db()->query($create_sql);
                db()->query('TRUNCATE TABLE ' . DB_PREFIX . $t);
                db()->query(str_replace('%%prefix%%', DB_PREFIX, $sql));
                $data = db()->get_all('SELECT * FROM ' . DB_PREFIX . $t);
                if (!empty($data)) {
                    file_put_contents($dir . $t . '.data.php', '<?' . 'php' . PHP_EOL . 'return ' . _var_export($data, 1) . ';');
                }
            }
        }
    } else {
        db()->query(get_table_create_sql($table));
        db()->query('TRUNCATE TABLE ' . DB_PREFIX . $table);
        db()->query(str_replace('%%prefix%%', DB_PREFIX, file_get_contents($f)));
        $data = db()->get_all('SELECT * FROM ' . $tname);
        if (!empty($data)) {
            file_put_contents('./data/' . $table . '.data.php', '<?' . 'php' . PHP_EOL . 'return ' . _var_export($data, 1) . ';');
        }
    }
}

//db()->query('DROP DATABASE IF EXISTS '.DB_NAME);
//exec('mysql -h '.escapeshellarg(DB_HOST).' -u '.escapeshellarg(DB_USER).' -p'.escapeshellarg(DB_PSWD).' -e "DROP DATABASE IF EXISTS '.DB_NAME.'"');
