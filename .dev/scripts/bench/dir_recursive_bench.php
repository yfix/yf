#!/usr/bin/php
<?php

$argv[1] = '/home/www/test2/';
require dirname(dirname(__FILE__)).'/scripts_init.php';

##############

echo PHP_EOL.'== 1) _class("dir")->scan()'.PHP_EOL.PHP_EOL;

$start_mem = memory_get_usage();
$start_time = microtime(true);
$files = _class('dir')->scan(YF_PATH, 1, '-f ~gallery.*.php~ims');
print_r($files);

echo 'time: '.round(microtime(true) - $start_time, 3).', mem: '.(memory_get_usage() - $start_mem).', peakmem: '.memory_get_peak_usage(). PHP_EOL;

###############

echo PHP_EOL.'== 2) DirectoryIterator'.PHP_EOL.PHP_EOL;

function rsearch($folder, $pattern) {
    $dir = new RecursiveDirectoryIterator($folder);
    $ite = new RecursiveIteratorIterator($dir);
    $files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);
    $fileList = array();
    foreach($files as $file) {
        $fileList = array_merge($fileList, $file);
    }
    return $fileList;
}

$start_mem = memory_get_usage();
$start_time = microtime(true);
$files = rsearch(YF_PATH, '~gallery.*.php~ims');
print_r($files);

echo 'time: '.round(microtime(true) - $start_time, 3).', mem: '.(memory_get_usage() - $start_mem).', peakmem: '.memory_get_peak_usage(). PHP_EOL;

###############

echo PHP_EOL.'== 3) rglob()'.PHP_EOL.PHP_EOL;

function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags); 
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

$start_mem = memory_get_usage();
$start_time = microtime(true);
$files = rglob(YF_PATH.'*gallery*.php');
print_r($files);

echo 'time: '.round(microtime(true) - $start_time, 3).', mem: '.(memory_get_usage() - $start_mem).', peakmem: '.memory_get_peak_usage(). PHP_EOL;

###############

echo PHP_EOL.'== 4) rglob2()'.PHP_EOL.PHP_EOL;

function rglob2($pattern, $flags = 0) {
    $files = iterator_to_array(new GlobIterator($pattern, $flags));
    foreach (new GlobIterator(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

$start_mem = memory_get_usage();
$start_time = microtime(true);
$files = rglob2(YF_PATH.'*gallery*.php');
print_r($files);

echo 'time: '.round(microtime(true) - $start_time, 3).', mem: '.(memory_get_usage() - $start_mem).', peakmem: '.memory_get_peak_usage(). PHP_EOL;

###############

echo PHP_EOL.'== 5) exec + find'.PHP_EOL.PHP_EOL;

$start_mem = memory_get_usage();
$start_time = microtime(true);
$files = explode("\n", trim(shell_exec('find '.YF_PATH.' -name '.'*gallery*.php')));
print_r($files);

echo 'time: '.round(microtime(true) - $start_time, 3).', mem: '.(memory_get_usage() - $start_mem).', peakmem: '.memory_get_peak_usage(). PHP_EOL;
