<?php

require __DIR__ . '/_init.php';

queue()->listen($conf['prefix'] . $conf['queue'], function ($item) {
    var_dump($item);
});
