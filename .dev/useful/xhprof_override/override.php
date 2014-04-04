<?php

define('XHPROF_ENABLE', true);
define('XHPROF_PATH', '/home/www/xhprof/');
define('XHPROF_WEB', 'xhprof.dev');
if (defined('XHPROF_ENABLE') && XHPROF_ENABLE) {
	// $_GLOBAL[ 'XHPROF_NAMESPACE' ] = $_SERVER[ 'REQUEST_URI' ];
	// start profiling
	xhprof_enable( XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY );
}
