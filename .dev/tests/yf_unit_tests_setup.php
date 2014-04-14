<?php

if (!defined('YF_PATH')) {
	define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
	require YF_PATH.'classes/yf_main.class.php';
	new yf_main('user', $no_db_connect = 1, $auto_init_all = 0);
	date_default_timezone_set('Europe/Kiev');
	ini_set('display_errors', 'on');
	error_reporting(E_ALL ^ E_NOTICE);
}