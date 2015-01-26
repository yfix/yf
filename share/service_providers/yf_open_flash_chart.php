#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/yf_open_flash_chart.git' => 'yf_open_flash_chart/');
$autoload_config = array();
require __DIR__.'/_config.php';

include_once $libs_root.'yf_open_flash_chart/open-flash-chart.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$bar = new bar_outline( 70, '#A2C2FC', '#0750D9' );
	$bar->data = array();
	$g = new graph();
	$g->js_path = isset($params['js_path']) ?: '/js/';
	$g->swf_path = isset($params['swf_path_path']) ?: '/js/';
	$g->title( ' ', '{font-size: 20px;}' );
	$g->set_tool_tip(  '#val# EUR on #x_label#' );
	$g->set_output_type('js');
	echo $g->render();
}
