<?php

/**
* Bootstrap acceptance testing methods
*/
class yf_test_html5fw {
	function text () {
		return form()->text('name');
	}
	function textarea () {
		return form()->textarea('name');
	}
	function container () {
		return form()->container('name');
	}
	function hidden () {
		return form()->hidden('name');
	}
}
