#!/usr/bin/php
<?php

$requires = array('sf_translation');
$git_urls = array('https://github.com/symfony/Validator.git' => 'sf_validator/');
$autoload_config = array('sf_validator/' => 'Symfony\Component\Validator');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$validator = \Symfony\Component\Validator\Validation::createValidator();
	$violations = $validator->validateValue('Bernhard', new \Symfony\Component\Validator\Constraints\Length(array('min' => 10)));
	var_dump($violations);
}
