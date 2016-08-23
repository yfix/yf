#!/usr/bin/php
<?php

$config = [
	'require_services' => ['sf_filesystem'],
	'git_urls' => ['https://github.com/romainneutron/Temporary-Filesystem.git' => 'temporary_fs/'],
	'autoload_config' => ['temporary_fs/src/Neutron/TemporaryFilesystem/' => 'Neutron\TemporaryFilesystem'],
	'example' => function() {
		$fs = \Neutron\TemporaryFilesystem\TemporaryFilesystem::create();
		$fs->createTemporaryFile('thumb-', '.dcm', 'jpg');
		var_dump($fs);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
