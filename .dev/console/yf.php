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
*/

use Symfony\Component\Console\Application;

function init_yf() {
	if (function_exists('main')) {
		return true;
	}
	if (!defined('YF_PATH')) {
		define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
	}
	require YF_PATH.'classes/yf_main.class.php';
	new yf_main('admin', $no_db_connect = false, $auto_init_all = false);

	date_default_timezone_set('Europe/Kiev');
	ini_set('display_errors', 'on');
	error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);
}

require '/usr/local/share/composer/vendor/autoload.php';
require __DIR__.'/yf_console_commands.class.php';

$application = new Application();
$application->add(new yf_console_commands);
$application->run();
