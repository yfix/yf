<?php

!defined('YF_PATH') && define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';

foreach ($git_urls as $git_url => $lib_dir) {
	$dir = $libs_root. $lib_dir;
	if (!file_exists($dir.'.git')) {
		if (false !== strpos($git_url, '~')) {
			list($git_url, $git_tag) = explode('~', $git_url);
			$cmd = '(git clone --branch '.$git_tag.' '.$git_url.' '.$dir.' && cd '.$dir.' && git checkout -b '.$git_tag.')';
		} else {
			$cmd = 'git clone --depth 1 '.$git_url.' '.$dir;
		}
		// Console mode
		if ($_SERVER['argc'] && !isset($_SERVER['REQUEST_METHOD'])) {
			passthru($cmd);
		} else {
			exec($cmd, $out);
		}
	}
}
$autoload_config && spl_autoload_register(function($class) use ($autoload_config, $libs_root) {
#	echo '=='.$class .PHP_EOL;
	foreach ($autoload_config as $lib_dir => $prefix) {
		$no_cut_prefix = false;
		if (substr($prefix, 0, strlen('no_cut_prefix:')) === 'no_cut_prefix:') {
			$no_cut_prefix = true;
		}
		if (false !== strpos($prefix, ':')) {
			list($tmp, $prefix) = explode(':', $prefix);
		}
		if (strpos($class, $prefix) !== 0) {
			continue;
		}
		if ($no_cut_prefix) {
			$path = $libs_root. $lib_dir. str_replace("\\", '/', $class).'.php';
		} else {
			$path = $libs_root. $lib_dir. str_replace("\\", '/', substr($class, strlen($prefix) + 1)).'.php';
		}
#		echo $path.PHP_EOL;
		if (!file_exists($path)) {
			continue;
		}
#		echo $path.PHP_EOL;
		require $path;
		return true;
	}
});

if ($requires) {
	ob_start();
	foreach ((array)$requires as $name) {
		require_once __DIR__.'/'.$name.'.php';
	}
	ob_end_clean();
}
