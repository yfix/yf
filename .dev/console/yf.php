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
* Add symlink for "yf" into /usr/local/bin/yf
*/

use Symfony\Component\Console\Application;

require '/usr/local/share/composer/vendor/autoload.php';

if (!defined('YF_PATH')) {
	define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
}
// TODO
#if (!defined('PROJECT_PATH')) {
#	define('PROJECT_PATH', __DIR__.'/');
#}
function init_yf() {
	if (function_exists('main')) {
		return true;
	}
	require YF_PATH.'classes/yf_main.class.php';
	new yf_main('admin', $no_db_connect = false, $auto_init_all = false);

	date_default_timezone_set('Europe/Kiev');
	ini_set('display_errors', 'on');
	error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);
}
function get_yf_console_commands() {
	$cmds = array();
	$subfolder = 'commands/';
	$prefix_project = 'console_';
	$prefix_framework = 'yf_'.$prefix_project;
	$ext = '.class.php';
	$globs = array(
		'project_plugins'	=> PROJECT_PATH. 'plugins/*/'. $subfolder. $prefix_project. '*'. $ext,
		'project_main'		=> PROJECT_PATH. $subfolder. $prefix_project. '*'. $ext,
		'framework_plugins'	=> YF_PATH. 'plugins/*/'. $subfolder. $prefix_framework. '*'. $ext,
		'framework_main'	=> __DIR__. '/'. $subfolder. $prefix_framework. '*'. $ext,
	);
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

$app = new Application('yf', '1.0 (stable)');
$app->addCommands(get_yf_console_commands());
$app->run();
