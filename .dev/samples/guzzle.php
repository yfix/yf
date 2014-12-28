<?php

# git clone git@github.com:yfix/guzzle.git /home/www/yf/libs/guzzle/
# git clone git@github.com:yfix/guzzle-ring.git /home/www/yf/libs/guzzle-ring/
# git clone git@github.com:yfix/guzzle-streams.git /home/www/yf/libs/guzzle-streams/
# git clone git@github.com:yfix/promise.git /home/www/yf/libs/promise/
define('YF_PATH', dirname(dirname(__DIR__)).'/');

$libs_root = YF_PATH.'libs/';
$config = array(
	$libs_root. 'guzzle/src/' => 'GuzzleHttp',
	$libs_root. 'guzzle-ring/src/' => 'GuzzleHttp\Ring',
	$libs_root. 'guzzle-streams/src/' => 'GuzzleHttp\Stream',
	$libs_root. 'promise/src/' => 'React\Promise',
);
require_once $libs_root. 'promise/src/functions.php';

spl_autoload_register(function($class) use ($config) {
#	echo '=='.$class .PHP_EOL;
	foreach ($config as $lib_root => $prefix) {
		if (strpos($class, $prefix) !== 0) {
			continue;
		}
		$path = $lib_root. str_replace("\\", '/', substr($class, strlen($prefix) + 1)).'.php';
		if (!file_exists($path)) {
			continue;
		}
#		echo $path.PHP_EOL;
		include $path;
		return true;
	}
});

$client = new GuzzleHttp\Client();
$res = $client->get('http://google.com');
echo $res->getStatusCode(). PHP_EOL;
echo $res->getHeader('content-type'). PHP_EOL;
echo $res->getBody(). PHP_EOL;
