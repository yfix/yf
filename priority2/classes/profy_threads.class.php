<?php

/**
* Call self project through console call (should be used in admin section for threaded execution)
* @require "php-cli" installed and "php" availiable in the $PATH
* @recommended set INCLUDE_PATH absolutely in index.php
*/
class profy_threads {

	/** @var */
	var $php_path = "php";
	/** @var */
	var $lastId = 0;
	/** @var */
	var $descriptorSpec = array(
		0 => array('pipe', 'r'),
		1 => array('pipe', 'w')
	);
	/** @var */
	var $handles = array();
	/** @var */
	var $streams = array();
	/** @var */
	var $results = array();
	/** @var */
	var $pipes = array();
	/** @var */
	var $timeout = 10;

	/**
	*/
	function __construct () {
		// Specially for the windows environment
		if (substr(PHP_OS, 0, 3) == 'WIN') {
			$this->php_path = "d:\\www2\\php5\\php -c d:\\www2\\php.ini";
		}
	}

	/**
	* You should avoid "./" or "../" in config paths for example "../project_conf.php"
	* Need to be replaced into dirname(dirname(__FILE__))."/project_conf.php"
	*/
	function new_framework_thread($object = "", $action = "", $params = array()) {
		return $this->new_thread(str_replace("/", DIRECTORY_SEPARATOR, INCLUDE_PATH."admin/index.php")." --object=".$object." --action=".$action, $params, true);
	}

	/**
	*/
	function new_thread($filename, $params=array(), $skip_file_check = false) {
		if (!$skip_file_check && !file_exists($filename)) {
			exit('THREADS: FILE_NOT_FOUND: '.$filename);
		}

		$params = $params ? addcslashes(serialize($params), '"') : "";
		$command = $this->php_path.' -q '.$filename. ($params ? ' --params "'.$params.'"' : "");
		++$this->lastId;

		$this->handles[$this->lastId] = proc_open($command, $this->descriptorSpec, $pipes);
		$this->streams[$this->lastId] = $pipes[1];
		$this->pipes[$this->lastId] = $pipes;

		return $this->lastId;
	}

	/**
	*/
	function iteration() {
		if (!$this->streams || !count($this->streams)) {
			return false;
		}
		$read = $this->streams;
		stream_select($read, $write = null, $except = null, $this->timeout);
		/*
		* Here we get one thread for ease of processing
		* But it is common to have several threads in $read array
		*/
		$stream = current($read);
		$id = array_search($stream, $this->streams);
		if (!$id) {
			return null;
		}
		$result = stream_get_contents($this->pipes[$id][1]);
		if (feof($stream)) {
			fclose($this->pipes[$id][0]);
			fclose($this->pipes[$id][1]);
			proc_close($this->handles[$id]);
			unset($this->handles[$id]);
			unset($this->streams[$id]);
			unset($this->pipes[$id]);
		}
		return array($id, $result);
	}

	/**
	* Helper to get params from command line
	*/
	function get_console_params() {
		foreach ((array)$_SERVER['argv'] as $key => $argv) {
			if ($argv == '--params' && isset($_SERVER['argv'][$key + 1])) {
				return unserialize($_SERVER['argv'][$key + 1]);
			}
		}
		return false;
	}
}
