#!/usr/bin/php
<?php

$argv[1] = '/home/www/test2/';
require dirname(__DIR__).'/scripts_init.php';

function find($folder, $pattern) {
	return explode("\n", trim(shell_exec('find -L '.escapeshellarg($folder).' -iname '.escapeshellarg($pattern))));
}
function rsearch($folder, $pattern) {
	$out = array();
	$flags = FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::FOLLOW_SYMLINKS;
	foreach(new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, $flags)), $pattern, RegexIterator::GET_MATCH) as $path => $f) {
		$out[] = $path;
	}
	return $out;
}
function rglob($folder, $pattern) {
	$folder = rtrim($folder, '/');
	// http://php.net/sql_regcase   !Warning! This function has been DEPRECATED as of PHP 5.3.0. Relying on this feature is highly discouraged.
	if (false === strpos($pattern, '[')) {
		$pattern = sql_regcase($pattern);
	}
	$files = (array)glob($folder.'/'.$pattern, GLOB_BRACE|GLOB_NOSORT);
	$dirs = (array)glob($folder.'/*', GLOB_BRACE|GLOB_ONLYDIR|GLOB_NOSORT);
	// Dotted dirs
	foreach (glob($folder.'/.**', GLOB_BRACE|GLOB_ONLYDIR|GLOB_NOSORT) as $path) {
		$d = basename($path);
		if ($d === '.' || $d === '..' || $d === '.git' || $d === '.svn') {
			continue;
		}
		$dirs[] = $path;
	}
	$func = __FUNCTION__;
	foreach ((array)$dirs as $dir) {
		$files = array_merge($files, $func($dir, $pattern));
	}
	return $files;
}
#function rglob2($folder, $pattern) {
#	$files = iterator_to_array(new GlobIterator($pattern, $flags));
#	foreach (new GlobIterator(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
#		$files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
#		$files = array_merge($files, rglob2($dir.'/'.basename($pattern), $flags));
#	}
#	return $files;
#}

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
		$files = find(YF_PATH, '*gallery*.php');
		return array('exec(find ...)', $files);
	}
	function rglob() {
		$files = rglob(YF_PATH, '*gallery*.php');
		return array('rglob()', $files);
	}
#	function rglob2() {
#		$files = rglob2(YF_PATH, '*gallery*.php');
#		return array('rglob2()', $files);
#	}
}

print new bench();