#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/bitpay/php-bitpay-client.git' => 'php-bitpay-client/'],
    'require_once' => ['php-bitpay-client/src/Bitpay/Autoloader.php'],
    'manual' => function () {
        \Bitpay\Autoloader::register();
    },
    'example' => function ($loader) {
        $client = new \Bitpay\Client\Client();
        $client->setAdapter(new Bitpay\Client\Adapter\CurlAdapter());
        $client->setNetwork(new Bitpay\Network\Testnet());
        $request = new \Bitpay\Client\Request();
        $request->setHost('test.bitpay.com');
        $request->setMethod(\Bitpay\Client\Request::METHOD_GET);
        $request->setPath('rates/USD');
        $response = $client->sendRequest($request);
        $data = json_decode($response->getBody(), true);
        var_dump($data);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
