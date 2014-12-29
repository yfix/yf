#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/symfony/Translation.git' => 'sf_translation/');
$autoload_config = array('sf_translation/' => 'Symfony\Component\Translation');
require __DIR__.'/_config.php';

// Test mode when direct call
if (realpath($argv[0]) === realpath(__FILE__)) {
	$translator = new \Symfony\Component\Translation\Translator('fr_FR', new Symfony\Component\Translation\MessageSelector());
	$translator->setFallbackLocales(array('fr'));
	$translator->addLoader('array', new Symfony\Component\Translation\Loader\ArrayLoader());
	$translator->addResource('array', array(
		'Hello World!' => 'Bonjour',
	), 'fr');
	echo $translator->trans('Hello World!')."\n";
}
