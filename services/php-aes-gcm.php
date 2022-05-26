#!/usr/bin/env php
<?php

$config = [
    'require_services' => [
        'beberlei_assert',
        'sf_polyfill_mbstring',
    ],
    'git_urls' => ['https://github.com/Spomky-Labs/php-aes-gcm.git' => 'php-aes-gcm/'],
    'require_once' => ['php-aes-gcm/src/AESGCM.php'],
    'example' => function () {
        var_dump(class_exists('AESGCM\AESGCM'));
        // The Key Encryption Key
        $K = hex2bin('feffe9928665731c6d6a8f9467308308feffe9928665731c');
        // The data to encrypt (can be null for authentication)
        $P = hex2bin('d9313225f88406e5a55909c5aff5269a86a7a9531534f7da2e4c303d8a318a721c3c0c95956809532fcf0e2449a6b525b16aedf5aa0de657ba637b39');
        // Additional Authenticated Data
        $A = hex2bin('feedfacedeadbeeffeedfacedeadbeefabaddad2');
        // Initialization Vector
        $IV = hex2bin('cafebabefacedbaddecaf888');
        // $C is the encrypted data ($C is null if $P is null)
        // $T is the associated tag
        list($C, $T) = AESGCM\AESGCM::encrypt($K, $IV, $P, $A);
        // The value of $C should be hex2bin('3980ca0b3c00e841eb06fac4872a2757859e1ceaa6efd984628593b40ca1e19c7d773d00c144c525ac619d18c84a3f4718e2448b2fe324d9ccda2710')
        // The value of $T should be hex2bin('2519498e80f1478f37ba55bd6d27618c')
        $P = AESGCM\AESGCM::decrypt($K, $IV, $C, $A, $T);
        // The value of $P should be hex2bin('d9313225f88406e5a55909c5aff5269a86a7a9531534f7da2e4c303d8a318a721c3c0c95956809532fcf0e2449a6b525b16aedf5aa0de657ba637b39')
        var_dump(bin2hex($P));
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
