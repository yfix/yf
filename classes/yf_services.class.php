<?php

/**
* Abstraction layer over YF services
*/
class yf_services {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* We cleanup object properties when cloning
	*/
	function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	* Need to avoid calling render() without params
	*/
	function __toString() {
		return $this->render();
	}

	/**
	*/
	function require_php_lib($name, $params = array()) {
		if (isset($this->php_libs[$name])) {
			return $this->php_libs[$name];
		}
		$dir = 'share/services/';
		$file = $name.'.php';
		$paths = array(
			'app'		=> APP_PATH. $dir. $file,
			'project'	=> PROJECT_PATH. $dir. $file,
			'yf'		=> YF_PATH. $dir. $file,
		);
		$found_path = '';
		foreach ($paths as $location => $path) {
			if (file_exists($path)) {
				$found_path = $path;
				break;
			}
		}
		if (!$found_path) {
			throw new Exception('main '.__FUNCTION__.' not found: '.$name);
			return false;
		}
		ob_start();
		require_once $found_path;
		$this->php_libs[$name] = $found_path;
		return ob_get_clean();
	}

	/**
	* Process and output JADE content
	*/
	function jade($content, $params = array()) {
// TODO
	}

	/**
	* Process and output HAML content
	*/
	function haml($content, $params = array()) {
// TODO
	}
}
