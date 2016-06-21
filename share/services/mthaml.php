#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/arnaud-lb/MtHaml.git' => 'mthaml/'],
	'autoload_config' => ['mthaml/lib/MtHaml/' => 'MtHaml'],
	'example' => function() {
		$haml = new MtHaml\Environment('php');
		$executor = new MtHaml\Support\Php\Executor($haml, [
			'cache' => sys_get_temp_dir().'/haml',
		]);
		$tpl = '
%ul#users
    %li.user
';
		$path = sys_get_temp_dir().'sample.haml';
		file_put_contents($path, $tpl);
		echo $executor->render($path, [
			'var' => 'value',
		]);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
