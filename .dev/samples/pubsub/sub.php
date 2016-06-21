<?php

require __DIR__.'/_init.php';

pubsub()->sub([$conf['prefix']. $conf['channel']], function($redis, $channel, $msg) {
	echo $channel.' | '.$msg. PHP_EOL;
});
