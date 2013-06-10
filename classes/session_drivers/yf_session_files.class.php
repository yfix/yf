<?php 

/**
* Session storage in files handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_session_files {

	/** @var string */
	public $CUR_SESSION_NAME	= "PHPSESSID";
	/** @var string */
	public $SESSION_FILES_DIR	= "session_data/";
	/** @var string Session Prefix */
	public $FILES_PREFIX		= "sess_";

	/**
	* Constructor (PHP 4.x)
	*/
	function yf_session_files () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*/
	function __construct () {
		// Set absolute path to the session files
		$this->SESSION_FILES_DIR = INCLUDE_PATH. $this->SESSION_FILES_DIR;
	}

	/*
	* Open session
	*/ 
	function _open($path, $name) {
		$this->CUR_SESSION_NAME = $name;
		return true;
	}

	/*
	* Close session
	*/ 
	function _close() {
		return true;
	} 

	/*
	* Read session data
	*/
	function _read($ses_id) {
		$ses_file = $this->SESSION_FILES_DIR.$this->FILES_PREFIX.$ses_id;
		if (file_exists($ses_file)) {
			$ses_data = file_get_contents($ses_file);
			return ($ses_data);
		} else {
			return (""); // Must return "" here.
		}
	} 

	/*
	* Write new data
	*/
	function _write($ses_id, $data) {
		$ses_file = $this->SESSION_FILES_DIR.$this->FILES_PREFIX.$ses_id;
		file_put_contents($ses_file, $data);
		return true;
	}

	/*
	* Destroy session
	*/
	function _destroy($ses_id) {
		$ses_file = $this->SESSION_FILES_DIR.$this->FILES_PREFIX.$ses_id;
		return (@unlink($ses_file));
	}

	/*
	* Garbage collection, deletes old sessions
	*/
	function _gc($life_time) {
		$dh		= opendir($start_dir);
		while (false !== ($f = readdir($dh))) {
			if ($f == "." || $f == "..") {
				continue;
			}
			if (@filemtime($f) < (time() - main()->SESSION_LIFE_TIME)) {
				@unlink($f);
			}
		}
		return true;
	}
}
