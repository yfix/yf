#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/composer/spdx-licenses.git' => 'composer_spdx_licenses/'],
    'autoload_config' => ['composer_spdx_licenses/src/' => 'Composer\Spdx'],
    'example' => function () {
        $licenses = new Composer\Spdx\SpdxLicenses();
        // get a license by identifier
        var_dump($licenses->getLicenseByIdentifier('MIT'));
        // get a license exception by identifier
        var_dump($licenses->getExceptionByIdentifier('Autoconf-exception-3.0'));
        // get a license identifier by name
        var_dump($licenses->getIdentifierByName('MIT License'));
        // check if a license is OSI approved by identifier
        var_dump($licenses->isOsiApprovedByIdentifier('MIT'));
        // check if input is a valid SPDX license expression
        var_dump($licenses->validate('MIT'));
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
