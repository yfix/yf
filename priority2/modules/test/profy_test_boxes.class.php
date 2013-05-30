<?php

/**
* Test sub-class
*/
class profy_test_boxes {

	/**
	* Profy module constructor
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
