#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/php-diff.git' => 'php_diff/'),
	'require_once' => array(
		'php_diff/lib/Diff.php',
		'php_diff/lib/Diff/Renderer/Html/SideBySide.php',
	),
	'example' => function() {
		$str1 = explode(PHP_EOL, 'aaa'.PHP_EOL.'1');
		$str2 = explode(PHP_EOL, 'aaa'.PHP_EOL.'av');

		$diff = new Diff($str1, $str2, $options);
		echo $diff->Render(new Diff_Renderer_Html_SideBySide);
		echo PHP_EOL;
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
