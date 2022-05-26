#!/usr/bin/env php
<?php

$config = [
    'require_services' => ['sf_filesystem'],
    'git_urls' => ['https://github.com/symfony/Config.git' => 'sf_config/'],
    'autoload_config' => ['sf_config/' => 'Symfony\Component\Config'],
    'example' => function () {
        $treeBuilder = new \Symfony\Component\Config\Definition\Builder\TreeBuilder();
        $rootNode = $treeBuilder->root('database');
        $rootNode
            ->children()
            ->enumNode('gender')
            ->values(['male', 'female'])
            ->end()
            ->end();
        var_dump($treeBuilder);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
