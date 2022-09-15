#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/symfony/Serializer.git' => 'sf_serializer/'],
    'autoload_config' => ['sf_serializer/' => 'Symfony\Component\Serializer'],
    'example' => function () {
        $encoders = [new Symfony\Component\Serializer\Encoder\XmlEncoder(), new Symfony\Component\Serializer\Encoder\JsonEncoder()];
        $normalizers = [new Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer()];

        $serializer = new Symfony\Component\Serializer\Serializer($normalizers, $encoders);
        $person = [
            'name' => 'John',
            'surname' => 'Doe',
            'age' => '100',
        ];
        $jsonContent = $serializer->serialize($person, 'json');
        echo $jsonContent;
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
