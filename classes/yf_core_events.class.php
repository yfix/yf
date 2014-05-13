<?php

/**
* Core events/observer handler
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_core_events {

// TODO: events system

	/**
	* Listen to fired events by name, second argument is closure function
	*/
	function listen($name, $func, $params = array()) {
// TODO
	}

	/**
	* Fire named event
	*/
	function fire($name, $extra = array()) {
// TODO
	}

	/**
	* Put item to queue, instead of firing it in this process
	*/
	function queue($name, $func, $params = array()) {
// TODO
	}

	/**
	* Flush queued event
	*/
	function flush($name, $extra = array()) {
// TODO
	}

	/**
	*/
	function _find_hooks() {
// TODO: will search through active modules for _event_hook() methods, where class/method can subscribe to any events
	}
}
