#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/Finder.git' => 'sf_finder/'),
	'autoload_config' => array('sf_finder/' => 'Symfony\Component\Finder'),
	'example' => function() {
		$finder = new \Symfony\Component\Finder\Finder();
		$iterator = $finder
			->files()
			->name('*.php')
			->depth(0)
			->size('>= 1K')
			->in(__DIR__);

		echo 'list of files with mask *.php inside current dir and in current subdir and size >= 1K'.PHP_EOL;
		foreach ($iterator as $file) {
	    	print $file->getRealpath(). PHP_EOL;
		}
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
