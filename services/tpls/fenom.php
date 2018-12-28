#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/bzick/fenom.git' => 'fenom/'],
    'autoload_config' => ['fenom/src/Fenom/' => 'Fenom'],
    'require_once' => ['fenom/src/Fenom.php'],
    'example' => function () {
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
        $replace = [];
        echo $tpl->fetch($replace);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
