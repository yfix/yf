<?php

/**
* YF Mail driver abstract
*/
abstract class yf_mail_driver {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}
	abstract protected function send(array $params = [], &$error_message = '');
}
