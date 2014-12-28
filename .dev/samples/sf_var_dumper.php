<?php

# git clone git@github.com:yfix/var-dumper.git /home/www/yf/libs/sf_var_dumper/
define('YF_PATH', dirname(dirname(__DIR__)).'/');

spl_autoload_register(function($class){
	$lib_root = YF_PATH.'libs/sf_var_dumper/';
	$prefix = 'Symfony\Component\VarDumper';
	if (strpos($class, $prefix) === 0) {
		$path = $lib_root. str_replace("\\", '/', substr($class, strlen($prefix) + 1)).'.php';
#		echo $path.PHP_EOL;
		include $path;
	}
});

function dump($var) {
	foreach (func_get_args() as $var) {
		\Symfony\Component\VarDumper\VarDumper::dump($var);
	}
}

dump($_SERVER);