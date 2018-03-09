#!/usr/bin/env php
<?php
/**
* YF console tool
*
* It require symfony/console composer package. You can install it with following console commands:
* # export COMPOSER_HOME=/usr/local/share/composer/
* # curl -sS https://getcomposer.org/installer | php
* # composer self-update
* # composer global require symfony/console:~2.4
* # ln -s /home/www/yf/.dev/console/yf /usr/local/bin/yf
*/

$autoload_file = '/usr/local/share/composer/vendor/autoload.php';
if (file_exists($autoload_file)) {
	require $autoload_file;
} else {
	ob_start();
	require dirname(dirname(__DIR__)).'/services/sf_console.php';
	ob_end_clean();
}

function get_paths() {
	$paths = [
		'called_path'	=> rtrim(getcwd(), '/').'/',
		'yf_path'		=> dirname(dirname(__DIR__)).'/',
		'app_path'		=> '',
		'project_path'	=> '',
		'config_path'	=> '',
		'db_setup_path'	=> '',
	];
	$globs = [
		'',
		'config/',
		'*/',
		'*/config/',
		'*/*/',
		'*/*/config/',
		'*/*/*/',
		'*/*/*/config/',
		'../',
		'../config/',
	];
	$max_deepness = substr_count($paths['called_path'], '/') - 1; // 1 level left for basedir
	for ($i = 1; $i <= $max_deepness; $i++) {
		$globs[] = str_repeat('../', $i).'*/';
		$globs[] = str_repeat('../', $i).'*/config/';
	}
	foreach ($globs as $g) {
		$files = glob($paths['called_path']. $g. 'db_setup.php');
		if (!$files || !isset($files[0])) {
			continue;
		}
		$fp = $files[0];
		if ($fp && file_exists($fp)) {
			$paths['db_setup_path'] = realpath($fp);
			break;
		}
	}
	if ($paths['db_setup_path']) {
		if (basename(dirname($paths['db_setup_path'])) == 'config') {
			$paths['app_path'] = dirname(dirname($paths['db_setup_path'])).'/';
		} else {
			$paths['app_path'] = dirname(dirname($paths['db_setup_path'])).'/';
		}
	}
	if ($paths['app_path']) {
		$paths['config_path'] = $paths['app_path'].'config/';
		$files = glob($paths['app_path']. '*/'. 'index.php');
		if ($files && isset($files[0])) {
			$fp = $files[0];
			if ($fp && file_exists($fp)) {
				$paths['project_path'] = dirname($fp).'/';
			}
		}
	}
	return $paths;
}
function init_yf() {
	if (function_exists('main')) {
		return true;
	}
	$dev_settings = APP_PATH.'.dev/override.php';
	if (file_exists($dev_settings)) {
	    require_once $dev_settings;
	}
	$saved_settings = PROJECT_PATH.'saved_settings.php';
	if (file_exists($saved_settings)) {
	    require_once $saved_settings;
	}
	require YF_PATH.'classes/yf_main.class.php';
	$project_conf_path = CONFIG_PATH.'project_conf.php';
	if (file_exists($project_conf_path)) {
		global $PROJECT_CONF;
		require_once $project_conf_path;
	}
	new yf_main('admin', $no_db_connect = false, $auto_init_all = false);

	date_default_timezone_set('Europe/Kiev');
	ini_set('display_errors', 'on');
	error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);
}
function get_yf_console_commands() {
	$cmds = [];
	$subfolder = 'commands/';
	$prefix_project = 'console_';
	$prefix_framework = 'yf_'.$prefix_project;
	$ext = '.class.php';
	$globs = [
		'project_app'			=> APP_PATH. $subfolder. $prefix_project. '*'. $ext,
		'project_app_plugins'	=> APP_PATH. 'plugins/*/'. $subfolder. $prefix_project. '*'. $ext,
		'project_plugins'		=> PROJECT_PATH. 'plugins/*/'. $subfolder. $prefix_project. '*'. $ext,
		'project_main'			=> PROJECT_PATH. $subfolder. $prefix_project. '*'. $ext,
		'framework_plugins'		=> YF_PATH. 'plugins/*/'. $subfolder. $prefix_framework. '*'. $ext,
		'framework_main'		=> __DIR__. '/'. $subfolder. $prefix_framework. '*'. $ext,
	];
	foreach ($globs as $gname => $glob) {
		foreach (glob($glob) as $path) {
			$name = '';
			$file = basename($path);
			$inside_project = false;
			if (strpos($file, $prefix_framework) === 0) {
				$name = substr($file, strlen($prefix_framework), -strlen($ext));
			} elseif (strpos($file, $prefix_project) === 0) {
				$name = substr($file, strlen($prefix_project), -strlen($ext));
				$inside_project = true;
			}
			if ($name && !isset($cmds[$name])) {
				require_once $path;
				$class_name = ($is_project ? $prefix_project : $prefix_framework). $name;
				if (class_exists($class_name)) {
					$cmds[$name] = new $class_name;
				}
			}
		}
	}
	return $cmds;
}

$yf_paths = get_paths();
if (!defined('YF_PATH')) {
	define('YF_PATH', $yf_paths['yf_path']);
}
if (!defined('APP_PATH')) {
	define('APP_PATH', $yf_paths['app_path']);
}
if (!defined('CONFIG_PATH')) {
	define('CONFIG_PATH', $yf_paths['config_path']);
}
if (!defined('PROJECT_PATH')) {
	define('PROJECT_PATH', $yf_paths['project_path']);
}
if (!defined('SITE_PATH')) {
	define('SITE_PATH', $yf_paths['project_path']);
}

print_r($yf_paths);

$app = new \Symfony\Component\Console\Application('yf', '1.0 (stable)');
$app->addCommands(get_yf_console_commands());
$app->run();
