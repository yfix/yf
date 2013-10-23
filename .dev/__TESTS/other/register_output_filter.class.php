<?php

class register_output_filter {
	function show() {
		tpl()->register_output_filter(function($body){
			return preg_replace('/<head>/ims', '<head>'.PHP_EOL.'<meta name="keywords" value="test">', $body);
		});
	}
}
