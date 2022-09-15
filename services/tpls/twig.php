#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/twigphp/Twig.git' => 'twig/'],
    'require_once' => ['twig/src/Extension/StringLoaderExtension.php'],
    'autoload_config' => ['twig/src/' => 'Twig'],
    // 'manual' => function () {
    // Twig_Autoloader::register();
    // },
    'example' => function () {
        $loader = new \Twig\Loader\FilesystemLoader();
        $twig = new \Twig\Environment($loader, [
            'cache' => STORAGE_PATH . 'twig_cache/',
        ]);
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
        $replace = [];
        echo $twig->render($str, $replace);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
