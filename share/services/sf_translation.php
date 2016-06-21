#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/symfony/Translation.git' => 'sf_translation/'],
	'autoload_config' => ['sf_translation/' => 'Symfony\Component\Translation'],
	'example' => function() {
		$translator = new \Symfony\Component\Translation\Translator('fr_FR', new Symfony\Component\Translation\MessageSelector());
		$translator->setFallbackLocales(['fr']);
		$translator->addLoader('array', new Symfony\Component\Translation\Loader\ArrayLoader());
		$translator->addResource('array', [
			'Hello World!' => 'Bonjour',
		], 'fr');
		echo $translator->trans('Hello World!')."\n";
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
