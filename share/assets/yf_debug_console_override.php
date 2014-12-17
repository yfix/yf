<?php

return DEBUG_MODE ? function() {
// TODO: replace with current links got from assets bootstrap2, font-awesome3 and jquery
	$js = '
	    var debug_console_override_head = [
	        \'<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>\',
    	    \'<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet">\',
	        \'<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>\',
    	    \'<link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.min.css" rel="stylesheet">\',
			\'<link href="//bootswatch.com/2/slate/bootstrap.css" rel="stylesheet">\'
    	];
	';
	return array(
		'versions' => array('master' => array(
#			'js' => $js,
		)),
	);
} : null;
