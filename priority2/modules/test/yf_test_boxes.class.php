<?php

/**
* Test sub-class
*/
class yf_test_boxes {

	/**
	* YF module constructor
	*/
	function _init () {
		$this->TEST_OBJ = module("test");
	}

	/**
	*/
	function run_test () {
		return tpl()->parse($_GET["object"]."/".$_GET["action"], $replace);
	}
}
