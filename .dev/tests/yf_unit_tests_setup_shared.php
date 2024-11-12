<?php

namespace yf\tests;

abstract class wrapper extends \PHPUnit\Framework\TestCase
{
    protected $backupGlobals = false;
    protected $backupStaticAttributes = false;
    protected $runTestInSeparateProcess = false;
    protected $preserveGlobalState = false;
    protected $inIsolation = false;
    final static function _pretty_show_exception(\Exception $e)
    {
        // $trace = $e->getTrace();
        // $res = [];
        // foreach ($trace as $key => $s) {
        //     $res[] = sprintf('#%s %s(%s): %s(%s)', $key, $s['file'] ?? '', $s['line'] ?? '', $s['function'] ?? '', implode(', ', $s['args'] ?? []));
        // }
        // $res[] = '#' . ++$key . ' {main}';
        // $pretty_trace = implode(PHP_EOL, $res);

        $pretty_trace = $e->getTraceAsString();

        return get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL . $pretty_trace;
    }
}

define('DEBUG_MODE', false);
define('APP_PATH', __DIR__ . '/.tmp/');
define('STORAGE_PATH', __DIR__ . '/.tmp/');
define('CONFIG_PATH', __DIR__ . '/');
$_SERVER['HTTP_HOST'] = 'test.dev';
if (! function_exists('main')) {
    define('YF_PATH', dirname(dirname(__DIR__)) . '/');
    define('YF_IN_UNIT_TESTS', true);
    $CONF['cache']['DRIVER'] = 'tmp';
    $CONF['cache']['NO_CACHE'] = true;
    $CONF['REDIS_HOST'] = getenv('REDIS_HOST') ?: 'redis';
    $CONF['REDIS_PORT'] = getenv('REDIS_PORT') ?: 6379;
    $CONF['MEMCACHED_HOST'] = getenv('YF_MEMCACHED_HOST') ?: 'memcached';
    $CONF['MEMCACHED_PORT'] = getenv('YF_MEMCACHED_PORT') ?: '11211';
    if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
        error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING);
    }
    require YF_PATH . 'classes/yf_main.class.php';
    new \yf_main($MAIN_TYPE ?: 'user', $no_db_connect = false, $auto_init_all = false, $CONF);
}
