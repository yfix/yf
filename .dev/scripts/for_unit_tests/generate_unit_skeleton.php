#!/usr/bin/php
<?php

// Example: ./generate_unit_skeleton.php /home/www/test2/ /home/www/yf/classes/yf_table2.class.php
$path = $argv[2];
$custom_path = $argv[3];

require_once dirname(__DIR__) . '/scripts_init.php';

$name = basename($path);
if (substr($name, 0, 3) == 'yf_') {
    $name = substr($name, 3);
}
$name = substr($name, 0, -strlen('.class.php'));

$out[] = '<?' . 'php' . PHP_EOL
    . PHP_EOL
    . 'require __DIR__.\'/yf_unit_tests_setup.php\';' . PHP_EOL
    . PHP_EOL
    . 'class class_' . $name . '_test extends PHPUnit_Framework_TestCase {' . PHP_EOL;

preg_match_all('~function[\s\t]+(?P<func>[a-z_][a-z0-9_]+?)[\s\t]*\(~ims', file_get_contents($path), $m);
foreach ($m['func'] as $func) {
    if (substr($func, 0, 2) == '__') {
        continue;
    }
    $res = @_class($name, $custom_path)->$func();
    if (is_object($res)) {
        $res = (string) $res;
    }
    if (is_array($res)) {
        $res = str_replace('array (', 'array(', var_export($res, 1));
        $res = preg_replace('~=>[\s\t]+array\(~ims', '=> array(', $res);
    } else {
        $res = '\'' . str_replace('\"', '"', trim(substr(addslashes(var_export($res, 1)), 2, -2))) . '\'';
    }
    $out[] = PHP_EOL . "\t" . 'public function test_' . $func . '() {' . PHP_EOL
        . "\t\t" . '$this->assertEquals(' . $res . ', trim(_class(\'' . $name . '\')->' . $func . '()) );' . PHP_EOL
        . "\t" . '}';
}

$out[] = PHP_EOL . '}' . PHP_EOL;

$target = './class_' . $name . '.Test.php';
file_put_contents($target, implode($out));
passthru('php -l ' . $target);
