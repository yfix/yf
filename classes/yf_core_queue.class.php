<?php

/**
* Core queue handler
*/
class yf_core_queue {
	// TODO: abstraction layer for rabbitmq and others, get idea from laravel

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}
}
