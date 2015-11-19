#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/PHPExcel.git' => 'phpexcel/'),
	'require_once' => array('phpexcel/Classes/PHPExcel.php'),
	'example' => function() {
        return true;
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
