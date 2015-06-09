#!/usr/bin/php
<?php

$argv[1] = '/home/www/test2/';
require dirname(__DIR__).'/scripts_init.php';

function rsearch($folder, $pattern) {
	$out = array();
	$flags = FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::FOLLOW_SYMLINKS;
	foreach(new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, $flags)), $pattern, RegexIterator::GET_MATCH) as $path => $f) {
		$out[] = $path;
	}
	return $out;
}
function rglob($pattern, $flags = 0) {
	$files = glob($pattern, $flags); 
	foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
		$files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
	}
	return $files;
}
function rglob2($pattern, $flags = 0) {
	$files = iterator_to_array(new GlobIterator($pattern, $flags));
	foreach (new GlobIterator(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
		$files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
	}
	return $files;
}

class bench {
	function __toString() {
		$out = array();
		foreach (get_class_methods($this) as $name) {
			if ($name[0] == '_') {
				continue;
			}
			$start_mem = memory_get_usage();
			$start_time = microtime(true);
			list($desc, $_files) = $this->$name();
			$files[$name] = $_files;
			$out[$name] = ++$i.') '.$desc.' | time: '.round(microtime(true) - $start_time, 3).' | mem: '.(memory_get_usage() - $start_mem).' | peakmem: '.memory_get_peak_usage().' | found: '.count($_files);
		}
		return print_r($files, 1). PHP_EOL. implode(PHP_EOL, $out). PHP_EOL;
	}
	function dir_scan() {
		$files = _class('dir')->scan(YF_PATH, 1, '-f ~gallery.*.php$~ims');
		return array('_class("dir")->scan()', $files);
	}
	function directory_iterator() {
		$files = rsearch(YF_PATH, '~gallery.*\.php$~ims');
		return array('DirectoryIterator', $files);
	}
	function exec_find() {
		$files = explode("\n", trim(shell_exec('find -L '.YF_PATH.' -iname '.'*gallery*.php')));
		return array('exec(find ...)', $files);
	}
	function rglob() {
		$files = rglob(YF_PATH.'*gallery*.php');
		return array('rglob()', $files);
	}
	function rglob2() {
		$files = rglob2(YF_PATH.'*gallery*.php');
		return array('rglob2()', $files);
	}
}

print new bench();