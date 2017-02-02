#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/fgrosse/PHPASN1.git' => 'phpasn1/'],
	'autoload_config' => ['phpasn1/lib/' => 'FG'],
	'example' => function() {
		var_dump(class_exists('FG\ASN1\Universal\PrintableString'));

		$integer = new FG\ASN1\Universal\Integer(123456);        
		$boolean = new FG\ASN1\Universal\Boolean(true);
		$enum = new FG\ASN1\Universal\Enumerated(1);
		$ia5String = new FG\ASN1\Universal\IA5String('Hello world');

		$asnNull = new FG\ASN1\Universal\NullObject();
		$objectIdentifier1 = new FG\ASN1\Universal\ObjectIdentifier('1.2.250.1.16.9');
		$objectIdentifier2 = new FG\ASN1\Universal\ObjectIdentifier(FG\ASN1\OID::RSA_ENCRYPTION);
		$printableString = new FG\ASN1\Universal\PrintableString('Foo bar');

		$sequence = new FG\ASN1\Universal\Sequence($integer, $boolean, $enum, $ia5String);
		$set = new FG\ASN1\Universal\Set($sequence, $asnNull, $objectIdentifier1, $objectIdentifier2, $printableString);

		$myBinary  = $sequence->getBinary();
		$myBinary .= $set->getBinary();

		echo base64_encode($myBinary);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
