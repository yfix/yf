<?php

/**
*/
class yf_ssh_tar {

	/**
	* Compress files to tar archive (local)
	*/
	function _local_make_tar ($files_list = array(), $archive_path = '') {
		if (empty($files_list)) {
			return false;
		}
		if (!$archive_path) {
			$archive_name = gmdate('Y-m-d__H_i_s').'_'.abs(crc32(microtime(true))).'.tar';
			$archive_path = _class('ssh')->_prepare_path(realpath(PROJECT_PATH).'/'. _class('ssh')->_TMP_DIR). '/'. $archive_name;
		}
		$destination_folder = dirname($archive_path);
		if (!file_exists($destination_folder)) {
			_mkdir_m($destination_folder);
		}
		// Check if destination folder really created
		if (!file_exists($destination_folder)) {
			return false;
		}
		$cur_dir = getcwd();
		chdir($destination_folder);
		if (file_exists($archive_path)) {
			unlink($archive_path);
		}
		foreach ((array)$files_list as $fpath) {
			$cmd = _class('ssh')->TAR_PATH.'tar '
				. (!file_exists($archive_path) ? '--create' : '--append'). ' '
				. ' -f '.$archive_path.' '.$fpath.'';
			exec($cmd);
		}
		if (_class('ssh')->USE_GZIP) {
			exec(_class('ssh')->GZIP_PATH.'gzip '.$archive_path);
			if (file_exists($archive_path.'.gz')) {
				$archive_path .= '.gz';
			} else {
				// GZIP failed for some reason, turn off temporary
				_class('ssh')->USE_GZIP = false;
			}
		}
		chdir($cur_dir);
		if (file_exists($archive_path)){
			return $archive_path;
		}
		return false;
	}

	/**
	* Extract files from tar archive (local)
	*/
	function _local_extract_tar ($archive_path = '', $extract_path = '') {
		if (empty($archive_path) || empty($extract_path)) {
			return false;
		}
		$archive_path = _class('ssh')->_prepare_path($archive_path);
		$extract_path = _class('ssh')->_prepare_path($extract_path);

		$destination_folder = dirname($extract_path);
		if (!file_exists($destination_folder)) {
			_mkdir_m($destination_folder);
		}
		return false;
	}
}
