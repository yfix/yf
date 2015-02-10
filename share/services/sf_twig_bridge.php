#!/usr/bin/php
<?php

$requires = array('twig');
$git_urls = array('https://github.com/symfony/TwigBridge.git' => 'sf_twig_bridge/');
$autoload_config = array('sf_twig_bridge/' => 'Symfony\Bridge\Twig');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	passthru('ls -l '.$libs_root.'sf_twig_bridge/');

}
