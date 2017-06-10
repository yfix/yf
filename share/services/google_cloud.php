#!/usr/bin/php
<?php

$config = [
	'require_services' => [
		'rize_uri_template',
		'google_auth',
		'guzzlehttp_guzzle',
		'guzzlehttp_psr7',
		'monolog',
		'psr_http_message',
		'ramsey_uuid',
#		'google_gax',
#		'google_proto_client',
	],
	'git_urls' => ['https://github.com/GoogleCloudPlatform/google-cloud-php.git' => 'google_cloud/'],
	'autoload_config' => ['google_cloud/src/' => 'Google\Cloud'],
	'example' => function() {
		$translate = new Google\Cloud\Translate\TranslateClient([
			'key' => 'YOUR_API_KEY',
		]);

		// Translate text from english to french.
		$result = $translate->translate('Hello world!', [
			'target' => 'fr'
		]);
		echo $result['text'] . "\n";

		// Detect the language of a string.
		$result = $translate->detectLanguage('Greetings from Michigan!');
		echo $result['languageCode'] . "\n";

		// Get the languages supported for translation specifically for your target language.
		$languages = $translate->localizedLanguages([
			'target' => 'en'
		]);
		foreach ($languages as $language) {
			echo $language['name'] . "\n";
			echo $language['code'] . "\n";
		}

		// Get all languages supported for translation.
		$languages = $translate->languages();

		foreach ($languages as $language) {
			echo $language . "\n";
		}
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
