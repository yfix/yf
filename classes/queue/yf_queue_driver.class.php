<?php

/**
* YF Queue driver abstract
*/
abstract class yf_queue_driver {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}
	abstract protected function connect($params);
	abstract protected function conf($params);
	abstract protected function is_ready();
	abstract protected function add($queue, $what);
	abstract protected function get($queue);
	abstract protected function del($queue);
	abstract protected function all($queue);
}
