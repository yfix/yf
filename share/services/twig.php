#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/twigphp/Twig.git' => 'twig/');
$autoload_config = array();
require __DIR__.'/_config.php';

require_once $libs_root.'twig/lib/Twig/Autoloader.php';
Twig_Autoloader::register();

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$loader = new Twig_Loader_String();
	$twig = new Twig_Environment($loader);
	$str = '<!DOCTYPE html>
<html>
	<head><title>My Webpage</title></head>
	<body>
		<ul id="navigation">
{% for item in navigation %}
			<li><a href="{{ item.href }}">{{ item.caption }}</a></li>
{% endfor %}
		</ul>
		<h1>My Webpage</h1>
		{{ a_variable }}
	</body>
</html>';
	$replace = array();
	echo $twig->render($str, $replace);

}
