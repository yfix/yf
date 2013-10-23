<?php

if (!defined('YF_PATH')) {
	define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
	require YF_PATH.'classes/yf_main.class.php';
	new yf_main('user', 1, 0);
}