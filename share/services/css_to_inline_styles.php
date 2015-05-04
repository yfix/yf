#!/usr/bin/php
<?php

$config = array(
	'require_services' => array('sf_css_selector'),
	'git_urls' => array('https://github.com/tijsverkoyen/CssToInlineStyles.git' => 'css_to_inline_styles/'),
	'require_once' => array(
		'css_to_inline_styles/src/Exception.php',
		'css_to_inline_styles/src/Specificity.php',
		'css_to_inline_styles/src/CssToInlineStyles.php',
	),
	'example' => function() {
		$body = '<style type="text/css">body{color:red;}</style>';
		$html = '
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta charset="utf-8">
</head>
<body>
'.$body.'
</body>
</html>
		';
		var_dump($html);
		echo PHP_EOL.'------------------'.PHP_EOL;

		$cti = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles($html);
		$cti->setEncoding('UTF-8');
		$cti->setUseInlineStylesBlock();
		// $cti->setHTML($html);
		// $cti->setCSS($css);
		$result = $cti->convert();

		var_dump($result);
#		if( $raw ) {
#			preg_match( '|<body.*>(.*)</body>|isU', $result, $matches );
#			$result = $matches[ 1 ] ?: $result;
#		}
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
