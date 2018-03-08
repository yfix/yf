#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/yfix/PHPExcel.git' => 'phpexcel/'],
	'require_once' => ['phpexcel/Classes/PHPExcel.php'],
	'example' => function() {
        var_dump(new PHPExcel);
        return true;
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
