#!/usr/bin/php
<?php

$config = [
    'require_services' => [ 'sf_dc' ],
    'git_urls' => [ 'https://github.com/twigphp/Twig.git' => 'twig/' ],
    'autoload_config' => [
        'twig/src/' => 'Twig',
    ],
    'example' => function () {
        $loader = new \Twig\Loader\FilesystemLoader();
        $twig   = new \Twig\Environment($loader, [
            'cache' => STORAGE_PATH .'twig_cache/',
        ]);
        $this->env->addExtension( new \Twig\Extension\StringLoaderExtension() );
        $this->env->addExtension( new \Twig\Extension\DebugExtension() );
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
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
