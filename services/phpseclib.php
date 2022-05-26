#!/usr/bin/env php
<?php

$config = [
    'require_services' => [
        'paragonie_constant_time_encoding',
        'paragonie_random_compat',
    ],
    'git_urls' => ['https://github.com/phpseclib/phpseclib.git~2.0' => 'phpseclib/'],
    'autoload_config' => ['phpseclib/phpseclib/' => 'phpseclib'],
    'example' => function () {
        $aes = new \phpseclib\Crypt\AES(\phpseclib\Crypt\Base::MODE_CFB);
        $aes->setKey('abcdefghijklmnop');
        var_dump($aes);

        echo PHP_EOL . '------------------' . PHP_EOL;

        $a = new \phpseclib\Math\BigInteger('10');
        $b = new \phpseclib\Math\BigInteger('20');
        $c = new \phpseclib\Math\BigInteger('30');
        $c = $a->modPow($b, $c);
        echo $c->toString() . PHP_EOL; // outputs 10
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
