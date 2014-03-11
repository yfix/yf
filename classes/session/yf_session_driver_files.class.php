<?php 

load('session_driver', 'framework', 'classes/session/');
class yf_session_driver_files extends yf_session_driver {

	public $CUR_SESSION_NAME	= 'PHPSESSID';
	public $SESSION_FILES_DIR	= 'session_data/';
	public $FILES_PREFIX		= 'sess_';

	function __construct () {
		$this->SESSION_FILES_DIR = INCLUDE_PATH. $this->SESSION_FILES_DIR;
	}

	function open($path, $name) {
		$this->CUR_SESSION_NAME = $name;
		return true;
	}

	function close() {
		return true;
	} 

	function read($ses_id) {
		$ses_file = $this->SESSION_FILES_DIR.$this->FILES_PREFIX.$ses_id;
		if (file_exists($ses_file)) {
			$ses_data = file_get_contents($ses_file);
			return ($ses_data);
		} else {
			return (''); // Must return '' here.
		}
	} 

	function write($ses_id, $data) {
		$ses_file = $this->SESSION_FILES_DIR.$this->FILES_PREFIX.$ses_id;
		file_put_contents($ses_file, $data);
		return true;
	}

	function destroy($ses_id) {
		$ses_file = $this->SESSION_FILES_DIR.$this->FILES_PREFIX.$ses_id;
		return (@unlink($ses_file));
	}

	function gc($life_time) {
		$dh		= opendir($start_dir);
		while (false !== ($f = readdir($dh))) {
			if ($f == '.' || $f == '..') {
				continue;
			}
			if (@filemtime($f) < (time() - main()->SESSION_LIFE_TIME)) {
				@unlink($f);
			}
		}
		return true;
	}
}
