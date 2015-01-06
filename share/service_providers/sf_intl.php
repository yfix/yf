#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/symfony/Intl.git' => 'sf_intl/');
$autoload_config = array('sf_intl/' => 'Symfony\Component\Intl');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$languages = \Symfony\Component\Intl\Intl::getLanguageBundle()->getLanguageNames();
	var_dump($languages);
}
