#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/jade.php.git' => 'jade_php/'),
	'autoload_config' => array('jade_php/src/Everzet/' => 'Everzet'),
	'example' => function() {
		$template = '
div
  address
  i
  strong
';
		$dumper = new \Everzet\Jade\Dumper\PHPDumper();
#		$dumper->registerVisitor('tag', new \Everzet\Jade\Visitor\AutotagsVisitor());
#		$dumper->registerFilter('javascript', new \Everzet\Jade\Filter\JavaScriptFilter());
#		$dumper->registerFilter('cdata', new \Everzet\Jade\Filter\CDATAFilter());
#		$dumper->registerFilter('php', new \Everzet\Jade\Filter\PHPFilter());
#		$dumper->registerFilter('style', new \Everzet\Jade\Filter\CSSFilter());

		// Initialize parser & Jade
		$parser = new \Everzet\Jade\Parser(new \Everzet\Jade\Lexer\Lexer());
		$jade   = new \Everzet\Jade\Jade($parser, $dumper);

		// Parse a template (both string & file containers)
		echo $jade->render($template);;
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
