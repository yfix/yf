#!/usr/bin/php
<?php

$requires = array('sf_event_dispatcher', 'sf_http_foundation', 'sf_debug', 'psr_log', 'sf_routing');
$git_urls = array('https://github.com/symfony/HttpKernel.git' => 'sf_http_kernel/');
$autoload_config = array('sf_http_kernel/' => 'Symfony\Component\HttpKernel');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$old_level = error_reporting();
	error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_STRICT);

	$routes = new \Symfony\Component\Routing\RouteCollection();
	$routes->add('hello', new \Symfony\Component\Routing\Route('/', array('_controller' =>
    	function (\Symfony\Component\HttpFoundation\Request $request) {
	        return new \Symfony\Component\HttpFoundation\Response(sprintf("Hello %s", $request->get('name')));
    	}
	)));
	$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

	$context = new \Symfony\Component\Routing\RequestContext();
	$context->fromRequest($request);

	$matcher = new \Symfony\Component\Routing\Matcher\UrlMatcher($routes, $context);

	$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
	$dispatcher->addSubscriber(new \Symfony\Component\HttpKernel\EventListener\RouterListener($matcher));

	$resolver = new \Symfony\Component\HttpKernel\Controller\ControllerResolver();

	$kernel = new \Symfony\Component\HttpKernel\HttpKernel($dispatcher, $resolver);
	$kernel->handle($request)->send();

	echo PHP_EOL;

	error_reporting($old_level);
}
