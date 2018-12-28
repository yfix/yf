#!/usr/bin/php
<?php

require dirname(__DIR__) . '/bash_colors.php';

$tests = [
    'yui_css' => ['echo "%s" | yuicompressor --type css', '.test { color:red; }', '.test{color:red}'],
    'yui_js' => ['echo "%s" | yuicompressor --type js', 'var a = "123";', 'var a=123;'],
//	'cssembed' => array('echo "%s" | cssembed --root /', '@import (test.css)', '@import ""'),
];
foreach ($tests as $name => $info) {
    $cmd = $info[0];
    $input = $info[1];
    $expected = $info[2];
    $ts = microtime(true);
    $result = [];
    $result_color = '';
    $error = '';
    exec(sprintf($cmd, $input) . ' 2>/dev/null', $result);
    if ($result[0] === $expected) {
        $result_color = bash_color_success(' OK ');
    } else {
        $result_color = bash_color_error(' ERROR ');
        $error = ' | \'' . $expected . '\' differs from \'' . $result[0] . '\'' . "\t";
    }
    $name = bash_color_info(' ' . $name . ' ');
    echo $name . "\t" . $cmd . "\t" . $error . round(microtime(true) - $ts, 3) . "\t" . $result_color . PHP_EOL;
}
