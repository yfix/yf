#!/usr/bin/php
<?php

$config = [
	'require_services' => ['random_compat','phpasn1'],
	'git_urls' => ['https://github.com/phpecc/phpecc.git' => 'mdanter_ecc/'],
	'autoload_config' => ['mdanter_ecc/src/' => 'Mdanter\Ecc'],
	'example' => function() {
		var_dump(class_exists('Mdanter\Ecc\Serializer\PrivateKey\PemPrivateKeySerializer'));
		$adapter = Mdanter\Ecc\EccFactory::getAdapter();
		$generator = Mdanter\Ecc\EccFactory::getNistCurves()->generator384();
		$private = $generator->createPrivateKey();

		$keySerializer = new Mdanter\Ecc\Serializer\PrivateKey\PemPrivateKeySerializer(new Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer($adapter));
		$data = $keySerializer->serialize($private);
		echo $data.PHP_EOL;
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
