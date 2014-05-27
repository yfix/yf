<?php

/**
* Call self project through console call (should be used in admin section for threaded execution)
* @require 'php-cli' installed and 'php' availiable in the $PATH
* @recommended set INCLUDE_PATH absolutely in index.php
*/
class yf_threads {

	/** @var */
	public $php_path = 'php';
	/** @var */
	public $lastId = 0;
	/** @var */
	public $descriptorSpec = array(
		0 => array('pipe', 'r'),
		1 => array('pipe', 'w')
	);
	/** @var */
	public $handles = array();
	/** @var */
	public $streams = array();
	/** @var */
	public $results = array();
	/** @var */
	public $pipes = array();
	/** @var */
	public $timeout = 10;

	/**
	*/
	function __construct () {
		// Specially for the windows environment
		if (substr(PHP_OS, 0, 3) == 'WIN') {
			$this->php_path = "d:\\www2\\php5\\php -c d:\\www2\\php.ini";
		}
	}

	/**
	* Threaded execution of the given object/action
	* @example: 
	*	$data_for_threads = array(
	*		array('id' => 1), 
	*		array('id' => 2),
	* 	);
	* @example: 
	*	for ($i = 0; $i < 10; $i++) {
	*		$threads[] = array('id' => $i);
	*	}
	*	print_r(common()->threaded_exec($_GET['object'], 'console', $threads), 1);
	* @example: 
	*	function console () {
	*		main()->NO_GRAPHICS = true;
	*		session_write_close();
	*		if (!main()->CONSOLE_MODE) {
	*			exit('No direct access to method allowed');
	*		}
	*		sleep(3);
	*   	$params = common()->get_console_params();
	*		echo $params['id'];
	*		exit();
	*	}
	*/
	function threaded_exec($object, $action = 'show', $threads_params = array(), $max_threads = 10) {
		$results = array();
		// Limit max number of parallel threads
		foreach (array_chunk($threads_params, $max_threads, true) as $chunk) {
			$ids_to_params = array();
			foreach ((array)$chunk as $param_id => $_params) {
				$thread_id = _class('threads')->new_framework_thread($object, $action, $_params);
				$ids_to_params[$thread_id] = $param_id;
			}
			while (false !== ($result = _class('threads')->iteration())) {
				if (!empty($result)) {
					$thread_id	= $result[0];
					$param_id	= $ids_to_params[$thread_id];
					$results[$param_id] = $result[1];
				}
			}
		}
		return $results;
	}

	/**
	* You should avoid './' or '../' in config paths for example '../project_conf.php'
	* Need to be replaced into dirname(dirname(__FILE__)).'/project_conf.php'
	*/
	function new_framework_thread($object = '', $action = '', $params = array()) {
		return $this->new_thread(str_replace('/', DIRECTORY_SEPARATOR, INCLUDE_PATH.'admin/index.php').' --object='.$object.' --action='.$action, $params, true);
	}

	/**
	*/
	function new_thread($filename, $params=array(), $skip_file_check = false) {
		if (!$skip_file_check && !file_exists($filename)) {
			exit('THREADS: FILE_NOT_FOUND: '.$filename);
		}

		$params = $params ? addcslashes(serialize($params), '"') : '';
		$command = $this->php_path.' -q '.$filename. ($params ? ' --params "'.$params.'"' : '');
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
