<?php

# git clone git@github.com:yfix/CssSelector.git /home/www/yf/libs/sf_css_selector/
define('YF_PATH', dirname(dirname(__DIR__)).'/');

spl_autoload_register(function($class){
	$lib_root = YF_PATH.'libs/sf_css_selector/';
	$prefix = 'Symfony\Component\CssSelector';
	if (strpos($class, $prefix) === 0) {
		$path = $lib_root. str_replace("\\", '/', substr($class, strlen($prefix) + 1)).'.php';
#		echo $path.PHP_EOL;
		include $path;
	}
});

print \Symfony\Component\CssSelector\CssSelector::toXPath('div.item > h4 > a');
print PHP_EOL;
