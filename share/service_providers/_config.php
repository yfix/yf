<?php

!defined('YF_PATH') && define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';
$is_console = $_SERVER['argc'] && !isset($_SERVER['REQUEST_METHOD']);

$check_error = function($dir, $check_file, $error_reason = 'git url or command is wrong') use ($name) {
	$error_reasons = array();
	if (!file_exists($check_file)) {
		if (!is_writable($dir)) {
			$error_reasons[] = $dir.' is not writable';
			if (!is_readable($dir)) {
				$error_reasons[] = $dir.' is not readable';
			} else {
				$stat = stat($dir);
				$posix = posix_getpwuid($stat['uid']);
				$error_reasons[] = ', details: file owner: '.$posix['name'].', php owner: '.$_SERVER['USER'].', file perms: '.fileperms($dir);
			}
		}
	}
	if ($error_reasons) {
		throw new Exception('lib "'.$name.'" install failed. Reasons: '.implode(', ', $error_reasons));
	}
};
// TODO: auto-install composer into /usr/local/bin with symlink
// globally: curl -s http://getcomposer.org/installer | php -- --install-dir=/usr/local/bin
// locally: curl -s http://getcomposer.org/installer | php
// ls -s /usr/local/bin/composer.phar /usr/local/bin/composer
if ($composer_names) {
#	$composer_names && passthru('composer self-update');
	$dir = $libs_root.'vendor/';
	foreach ((array)$composer_names as $composer_package) {
		$check_file = $dir. dirname($composer_package).'/'.basename($composer_package).'/';
		if (!file_exists($check_file)) {
			$cmd = 'cd '.$libs_root.' && composer require --no-interaction '.$composer_package;
			passthru($cmd);
			$check_error($dir, $check_file, 'something wrong with composer');
		}
	}
	require_once $dir. 'autoload.php';
	$autoload_config = array(); // Exclude raw git clone steps
}
foreach ((array)$git_urls as $git_url => $lib_dir) {
	$dir = $libs_root. $lib_dir;
	$check_file = $dir.'.git';
	if (!file_exists($check_file)) {
		if (false !== strpos($git_url, '~')) {
			list($git_url, $git_tag) = explode('~', $git_url);
			$cmd = '(git clone --branch '.$git_tag.' '.$git_url.' '.$dir.' && cd '.$dir.' && git checkout -b '.$git_tag.')';
		} else {
			$cmd = 'git clone --depth 1 '.$git_url.' '.$dir;
		}
		passthru($cmd);
		$check_error($dir, $check_file);
	}
}
$autoload_config && spl_autoload_register(function($class) use ($autoload_config, $libs_root) {
#	echo '=='.$class .PHP_EOL;
	foreach ((array)$autoload_config as $lib_dir => $prefix) {
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
