#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/yf_open_flash_chart.git' => 'yf_open_flash_chart/'),
	'require_once' => array('yf_open_flash_chart/open-flash-chart.php'),
	'example' => function() {
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
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
