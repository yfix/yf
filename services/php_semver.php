#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/vierbergenlars/php-semver.git' => 'php_semver/'],
    'autoload_config' => ['php_semver/src/vierbergenlars/SemVer/' => 'vierbergenlars\SemVer'],
    'example' => function () {
        // Check if a version is valid
        $semver = new vierbergenlars\SemVer\version('1.2.3');
        var_dump($semver);

        // Get a clean version string
        $semver = new vierbergenlars\SemVer\version('=v1.2.3');
        $res = $semver->getVersion(); // '1.2.3'
        var_dump($res);

        // Check if a version satisfies a range
        $semver = new vierbergenlars\SemVer\version('1.2.3');
        $res = $semver->satisfies(new vierbergenlars\SemVer\expression('1.x || >=2.5.0 || 5.0.0 - 7.2.3')); // true
        var_dump($res);

        // OR
        $range = new vierbergenlars\SemVer\expression('1.x || >=2.5.0 || 5.0.0 - 7.2.3');
        $res = $range->satisfiedBy(new vierbergenlars\SemVer\version('1.2.3')); // true
        var_dump($res);

        // Compare two versions
        $res = vierbergenlars\SemVer\version::gt('1.2.3', '9.8.7'); // false
        var_dump($res);
        $res = vierbergenlars\SemVer\version::lt('1.2.3', '9.8.7'); // true
        var_dump($res);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
