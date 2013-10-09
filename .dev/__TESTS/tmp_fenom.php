<?php

define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
require YF_PATH.'classes/yf_main.class.php';
$GLOBALS['PROJECT_CONF']['tpl']['DRIVER_NAME'] = 'fenom';
new yf_main('user', 1, 0);
#error_reporting(E_ALL);

echo tpl()->parse_string( 'Hello world' );
