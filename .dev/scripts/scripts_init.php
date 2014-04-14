<?php

if (!defined('YF_PATH')) {
	define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
	require YF_PATH.'classes/yf_main.class.php';
	new yf_main('admin', $no_db_connect = false, $auto_init_all = true);
	ini_set('display_errors', 'on');
	error_reporting(E_ALL ^ E_NOTICE);
}
