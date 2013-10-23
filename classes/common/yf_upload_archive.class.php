<?php

/**
* Archive file uploader
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_upload_archive {

	/** @var array */
	public $ALLOWED_MIME_TYPES = array(
    	'application/zip'   => 'zip',
    	'application/rar'   => 'rar',
    	'application/x-tar' => 'tar',
    	'application/x-gzip'=> 'gz',
	);

	/**
	* Do upload archive to server
	*/
	function go ($new_file_path, $name_in_form = 'archive') {
		ignore_user_abort(true);
		if (empty($new_file_path)) {
			trigger_error(__CLASS__.': New file path id required', E_USER_WARNING);
			return false;
		}
		if (empty($name_in_form)) {
			$name_in_form = 'archive';
		}
		$ARCHIVE = is_array($name_in_form) ? $name_in_form : $_FILES[$name_in_form];
		if ($ARCHIVE['type'] && !isset($this->ALLOWED_MIME_TYPES[$ARCHIVE['type']])) {
			_re('Invalid file mime type');
		}
		if (common()->_error_exists()) {
			return false;
		}
		$ARCHIVE_DIR = dirname($new_file_path);
		if (!file_exists($ARCHIVE_DIR)) {
			mkdir($ARCHIVE_DIR, 0777, true);
		}
		$ARCHIVE_PATH = $new_file_path;
		if ($is_local) {
			$move_result = false;
			if (!file_exists($ARCHIVE_PATH) && file_exists($ARCHIVE['tmp_name'])) {
				file_put_contents($ARCHIVE_PATH, file_get_contents($ARCHIVE['tmp_name']));
				unlink($ARCHIVE['tmp_name']);
				$move_result = true;
			}
		} else {
			$move_result = move_uploaded_file($ARCHIVE['tmp_name'], $ARCHIVE_PATH);
		}
		if (!$move_result || !file_exists($ARCHIVE_PATH) || !filesize($ARCHIVE_PATH) || !is_readable($ARCHIVE_PATH)) {
			trigger_error('Moving uploaded image error', E_USER_WARNING);
			return false;
		}
		return true;
	}
}
