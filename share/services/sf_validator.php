#!/usr/bin/php
<?php

$config = array(
	'require_services' => array('sf_translation'),
	'git_urls' => array('https://github.com/symfony/Validator.git' => 'sf_validator/'),
	'autoload_config' => array('sf_validator/' => 'Symfony\Component\Validator'),
	'example' => function() {
		$validator = \Symfony\Component\Validator\Validation::createValidator();
		$violations = $validator->validateValue('Bernhard', new \Symfony\Component\Validator\Constraints\Length(array('min' => 10)));
		var_dump($violations);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
