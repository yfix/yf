#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/vierbergenlars/php-semver.git' => 'php_semver/');
$autoload_config = array('php_semver/src/vierbergenlars/SemVer/' => 'vierbergenlars\SemVer');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
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
}
