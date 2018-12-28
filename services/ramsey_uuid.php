#!/usr/bin/php
<?php

$config = [
    'require_services' => ['paragonie_random_compat'],
    'git_urls' => ['https://github.com/ramsey/uuid.git' => 'ramsey_uuid/'],
    'autoload_config' => ['ramsey_uuid/src/' => 'Ramsey\Uuid'],
    'example' => function () {
        try {
            // Generate a version 1 (time-based) UUID object
            $uuid1 = \Ramsey\Uuid\Uuid::uuid1();
            echo $uuid1->toString() . "\n"; // i.e. e4eaaaf2-d142-11e1-b3e4-080027620cdd

            // Generate a version 3 (name-based and hashed with MD5) UUID object
            $uuid3 = \Ramsey\Uuid\Uuid::uuid3(\Ramsey\Uuid\Uuid::NAMESPACE_DNS, 'php.net');
            echo $uuid3->toString() . "\n"; // i.e. 11a38b9a-b3da-360f-9353-a5a725514269

            // Generate a version 4 (random) UUID object
            $uuid4 = \Ramsey\Uuid\Uuid::uuid4();
            echo $uuid4->toString() . "\n"; // i.e. 25769c6c-d34d-4bfe-ba98-e0ee856f3e7a

            // Generate a version 5 (name-based and hashed with SHA1) UUID object
            $uuid5 = \Ramsey\Uuid\Uuid::uuid5(\Ramsey\Uuid\Uuid::NAMESPACE_DNS, 'php.net');
            echo $uuid5->toString() . "\n"; // i.e. c4a760a8-dbcf-5254-a0d9-6a4474bd1b62
        } catch (\Ramsey\Uuid\Exception\UnsatisfiedDependencyException $e) {

            // Some dependency was not met. Either the method cannot be called on a
            // 32-bit system, or it can, but it relies on Moontoast\Math to be present.
            echo 'Caught exception: ' . $e->getMessage() . "\n";
        }
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
