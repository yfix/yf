<?php

require __DIR__ . '/_redis.php';

$queue = $conf['prefix'] . $conf['queue'];

while (true) {
    $item = $redis->rpop($queue);
    if ($item) {
        var_dump($item);
        usleep(200000);
    }
    //		usleep(500000);

    usleep(200000);
}
