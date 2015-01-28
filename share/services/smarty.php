#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/smarty-php/smarty.git' => 'smarty/');
$autoload_config = array();
require __DIR__.'/_config.php';

require_once $libs_root.'smarty/libs/Autoloader.php';
Smarty_Autoloader::register();

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$smarty = new Smarty();
#	$smarty->setTemplateDir(YF_PATH. tpl()->TPL_PATH);
	$smarty->setCompileDir('/tmp/templates_c/');
	$smarty->setCacheDir('/tmp/smarty_cache/');

	$smarty->assign($replace);
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

	echo $smarty->fetch('string:'.$str);

}
