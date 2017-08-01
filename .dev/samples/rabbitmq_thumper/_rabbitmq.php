<?php

require_once dirname(dirname(dirname(__DIR__))).'/share/services/thumper.php';

$conf = require __DIR__.'/_conf.php';

# DOCS https://github.com/php-amqplib/Thumper
$connections = ['default' => new \PhpAmqpLib\Connection\AMQPLazyConnection(
	$conf['host'] ?: 'localhost',
	$conf['port'] ?: 5672,
	$conf['login'] ?: 'user',
	$conf['password'] ?: 'password',
	$conf['vhost'] ?: '/'
)];
$registry = new \Thumper\ConnectionRegistry($connections, 'default');
