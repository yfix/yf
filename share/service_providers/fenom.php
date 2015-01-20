#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/bzick/fenom.git' => 'fenom/');
$autoload_config = array('fenom/src/Fenom/' => 'Fenom');
require __DIR__.'/_config.php';

require $libs_root.'fenom/src/Fenom.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$fenom = Fenom::factory('.', '/tmp', Fenom::AUTO_ESCAPE/* | Fenom::FORCE_COMPILE | Fenom::DISABLE_CACHE*/);
	$str = '<!DOCTYPE html>
<html>
	<head><title>My Webpage</title></head>
	<body>
		<ul id="navigation">
{foreach $navigation as $item}
			<li><a href="{$item.href}">{$item.caption}</a></li>
{/foreach}
		</ul>
		<h1>My Webpage</h1>
		{$a_variable}
	</body>
</html>';

	$tpl = $fenom->compileCode($str, $name);
	$replace = array();
	echo $tpl->fetch($replace);

}
