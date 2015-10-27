<?php

class yf_browserconfigxml {
	function show() {
		header('Content-Type: text/xml', $replace = true);
		print '<?xml version="1.0" encoding="utf-8"?><browserconfig><msapplication></msapplication></browserconfig>';
		exit;
	}
}
