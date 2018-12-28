<?php

require __DIR__ . '/_redis.php';

$queue = $conf['prefix'] . $conf['queue'];

foreach (range(1, 1000) as $i) {
    echo $i . PHP_EOL;
    $redis->lpush($queue, 'hello ' . $i);
    sleep(1);
}
