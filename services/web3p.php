#!/usr/bin/php
<?php

$config = [
    // 'require_services' => [
    //     'web3p/rlp': '0.3.5',
    //     'web3p/ethereum-util': '~0.1.3',
    //     'kornrunner/keccak': '~1',
    //     'simplito/elliptic-php': '~1.0.6'
    // ],
    'git_urls' => [ 'https://github.com/web3p/ethereum-tx.git' => 'ethereum_tx/' ],
    'autoload_config' => [
        'ethereum_tx/src/' => 'Web3p\EthereumTx',
    ],
    'example' => function () {
        var_dump( class_exists( 'Web3p\EthereumTx' ) );
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
