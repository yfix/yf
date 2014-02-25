<?php

class yf_test {
	function show() {
		return form2()->file_uploader('test');
	}
	
	function ajax_file_uploader() {
		_class('form2_file_handler','classes/form2/')->process();
		exit;
	}
}