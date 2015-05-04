#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/symfony/Intl.git' => 'sf_intl/'),
	'autoload_config' => array('sf_intl/' => 'Symfony\Component\Intl'),
	'example' => function() {
		$languages = \Symfony\Component\Intl\Intl::getLanguageBundle()->getLanguageNames();
		var_dump($languages);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
