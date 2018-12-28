#!/usr/bin/php
<?php

$argv[1] = '/home/www/test2/';
require dirname(__DIR__) . '/scripts/scripts_init.php';

class bench
{
    public function __toString()
    {
        $out = [];
        foreach (get_class_methods($this) as $name) {
            if ($name[0] == '_') {
                continue;
            }
            $start_mem = memory_get_usage();
            $start_time = microtime(true);
            list($desc, $_files) = $this->$name();
            $files[$name] = $_files;
            $out[$name] = ++$i . ') ' . $desc . ' | time: ' . round(microtime(true) - $start_time, 3) . ' | mem: ' . (memory_get_usage() - $start_mem) . ' | peakmem: ' . memory_get_peak_usage() . ' | found: ' . count((array) $_files);
        }
        return print_r($files, 1) . PHP_EOL . implode(PHP_EOL, $out) . PHP_EOL;
    }
    public function dir_scan()
    {
        $files = _class('dir')->scan(YF_PATH, 1, '-f ~gallery.*.php$~ims');
        return ['_class("dir")->scan()', $files];
    }
    public function dir_iterate()
    {
        $files = _class('dir')->riterate(YF_PATH, '~gallery.*\.php$~ims');
        return ['_class("dir")->riterate()', $files];
    }
    public function dir_scan_fast()
    {
        $files = _class('dir')->scan_fast(YF_PATH, '~gallery.*.php$~ims');
        return ['_class("dir")->scan_fast()', $files];
    }
    public function dir_rglob()
    {
        $files = _class('dir')->rglob(YF_PATH, '*gallery*.php');
        return ['_class("dir")->rglob()', $files];
    }
    public function dir_find()
    {
        $files = _class('dir')->find(YF_PATH, '*gallery*.php');
        return ['_class("dir")->find()', $files];
    }
    //	function dir_grep() {
//		$files = _class('dir')->grep('~github~', YF_PATH, '*gallery*.php');
//		return array('_class("dir")->grep() for word github', $files);
//	}
}

echo new bench();
