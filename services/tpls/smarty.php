#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/smarty-php/smarty.git' => 'smarty/'],
    'require_once' => ['smarty/libs/Autoloader.php'],
    'manual' => function () {
        Smarty_Autoloader::register();
    },
    'example' => function () {
        $smarty = new Smarty();
        //		$smarty->setTemplateDir(YF_PATH. tpl()->TPL_PATH);
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
        echo $smarty->fetch('string:' . $str);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
