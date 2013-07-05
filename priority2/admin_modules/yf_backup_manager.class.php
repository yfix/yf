<?php

/**
* Backup manager
*/
class yf_backup_manager {

	/** @var string Path to installed tar.exe */
	public $TAR_PATH 			= "";
//	public $TAR_PATH 			= "c:/tar/bin";
	/** @var string Backup file prefix */
	public $BAK_PREFIX 		= "";
	/** @var string Backup filename format */
	public $FNAME_FORMAT 		= "d-m-Y__H_i_s";
	/** @var string Path where auto-backups will be stored */
	public $BACKUP_PATH		= "backup/";
	/** @var string Path where auto-backups will be stored */
	public $MD5_LIST_FNAME		= ".md5sums";
	/** @var int Quantity of backup files to store */
	public $MAX_BACKUP_FILES 	= 8;
	/** @var bool Use archive verify */
	public $VERIFY_ARCHIVE 	= false;
	/** @var bool Use gzip */
	public $USE_GZIP		 	= true;
	/** @var bool Create sql backup with files backup */
	public $BACKUP_SQL		 	= true;
	/** @var array @conf_skip Array of files to backup. All paths is relative to root */
	public $files_list = array(
		"/var/www/1.php",
//		"d:/gzip.exe",
//		"d:/kyivstar",
	);


	/**
	* Constructor
	*/
	function _init () {

		// Init dir class
		$this->DIR_OBJ = main()->init_class("dir", "classes/");
		$this->DB_MGR = main()->init_class("db_manager", "admin_modules/");

		$this->backup_folder_path = INCLUDE_PATH. $this->BACKUP_PATH;
		
		$paths = array(
			"@@YF_PATH@@"	=> YF_PATH,
			"@@YF_PATH@@"				=> YF_PATH,
			"@@INCLUDE_PATH@@"			=> INCLUDE_PATH,
			"@@ADMIN_REAL_PATH@@"		=> ADMIN_REAL_PATH,
		);
		$this->files_list = str_replace(array_keys($paths), array_values($paths), $this->files_list);
	}

	/**
	* Default method
	*/
	function show () {

		// Find all backups in backup folder
		$backup_files = $this->DIR_OBJ->scan_dir($this->backup_folder_path, true, "/\.(tar|tgz|gz)$/i");

		if ($_FILES['import_file']['tmp_name']){
			$import_data = file_get_contents($_FILES['import_file']['tmp_name']);
			file_put_contents($this->backup_folder_path. $_FILES['import_file']['name'], $import_data);
		}

		if (!empty($backup_files)) {
			$_files_infos = array();
			foreach ((array)$backup_files as $fpath) {
				$_files_infos[] = array(
					"fpath"		=> $fpath,
					"file_mtime"=> filemtime($fpath),
					"file_size"	=> filesize($fpath),
				);
			}
			usort($_files_infos, array(&$this, "_sort_by_date"));
		}

		foreach ((array)$_files_infos as $_info) {
			$fpath = $_info["fpath"];
			$id = urlencode(basename($fpath));
			$replace2 = array(
				"backup_date"	=> _format_date($_info["file_mtime"], "long"),
				"backup_fsize"	=> common()->format_file_size($_info["file_size"]),
				"backup_name"	=> basename($fpath),
				"delete_url"	=> "./?object=".$_GET["object"]."&action=delete&id=".$id,
				"restore_url"	=> "./?object=".$_GET["object"]."&action=restore&id=".$id,
				"download_url"	=> "./?object=".$_GET["object"]."&action=export&id=".$id,
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}

		// Show form
		$replace = array(
			"items"				=> $items,
			"import_form_action"=> "./?object=".$_GET["object"],
			"form_action"		=> "./?object=".$_GET["object"]."&action=backup",
			"error_message"		=> _e(),
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Create backup
	*/
	function backup () {

		if (empty($this->files_list)) {
			_re(t("Backup failed")."!");			
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}

		if ($this->BACKUP_SQL){
			// Make db backup
			$db_backup_path = $this->DB_MGR->backup(true); 
			$this->files_list[] = $db_backup_path;
		}

		$this->_tar($this->files_list, $this->backup_folder_path);

		// Garbage collect
		$files = $this->DIR_OBJ->scan_dir($this->backup_folder_path, true, "/\.(tar|gz|tgz)$/i");
		foreach ((array)$files as $item_name) {
			$mtimes[filemtime($item_name)] = $item_name;						
		}

		$max_files = $this->MAX_BACKUP_FILES; // Number of old files to leave
		$num_files = count($files);
		if ($num_files > $max_files) {
			ksort($mtimes);
			foreach ((array)$mtimes as $v) {
				unlink($v);
				$num_files--;
				if ($num_files <= $max_files) {
					break;
				}
			}
		}

		return js_redirect($_SERVER["HTTP_REFERER"], 0);

	}

	/**
	* Delete backup file
	*/
	function delete() {
		$fname = urldecode($_GET["id"]);
		$fpath = $this->backup_folder_path. $fname;
		if (file_exists($fpath)) {
			unlink($fpath);
		}
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Export backup
	*/
	function export() {
		$fname = urldecode($_GET["id"]);
		$fpath = $this->backup_folder_path. $fname;
		if (file_exists($fpath)) {

			$body = file_get_contents($fpath);
			main()->NO_GRAPHICS = true;
			// Throw headers
			header("Content-Type: application/force-download; name=\"".$fname."\"");
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".strlen($body));
			header("Content-Disposition: attachment; filename=\"".$fname."\"");
			// Throw content
			echo $body;
		}
		exit;
	}

	/**
	* Restore from backup
	*/
	function restore() {
		$fname = urldecode($_GET["id"]);
		$full_fpath = $this->backup_folder_path. $fname;
		$tar_file_path = $this->backup_folder_path;

		if (!file_exists($full_fpath) || !$full_fpath) {
			_re(t("Resotre failed")."!. " .t("No such file"));
			return false;
		}
/*
		if($this->VERIFY_ARCHIVE){
		}
*/
		$this->_untar($full_fpath);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Sort array of files by creation date (use for usort)
	*/
	function _sort_by_date ($a, $b) {
		if ($a["file_mtime"] == $b["file_mtime"]) {
			return 0;
		}
		return ($a["file_mtime"] > $b["file_mtime"]) ? -1 : 1;
	}

	/**
	* Compress files to tar archive
	*/
	function _tar ($files_list = array(), $destination_folder = "", $destination_fname = "") {
		if (empty($files_list)) {
			return false;
		}
		if (!file_exists($destination_folder)) {
			// Create folder
			_mkdir_m($destination_folder);
		}

		if (!$destination_fname) {
			$destination_fname = gmdate($this->FNAME_FORMAT).".tar";
		}

		if($this->VERIFY_ARCHIVE){
/*

			$string = "Created on "._format_date(time(),"long")."\n\n";
			$string .= $this->_md5sum_list($files_list);

			$tmp_folder = INCLUDE_PATH.SITE_UPLOADS_DIR."tmp/";
			if (!file_exists($tmp_folder)) {
				// Create folder
				_mkdir_m($tmp_folder);
			}
			file_put_contents($tmp_folder. $this->MD5_LIST_FNAME, $string);
			
			$files_list[] = $tmp_folder. $this->MD5_LIST_FNAME;
*/
		}

		$result_filepath = $destination_folder. $destination_fname;
		if (file_exists($result_filepath)){
			unset($result_filepath);
		}
		
		chdir($destination_folder);		
		foreach ((array)$files_list as $fpath) {
			if (!file_exists($result_filepath)) {
				$command = $this->TAR_PATH."tar --create ".($this->USE_GZIP ? "-z" : "")." -f ".$destination_fname." ".$fpath."";
			} else {
				$command = $this->TAR_PATH."tar --append ".($this->USE_GZIP ? "-z" : "")." -f ".$destination_fname." ".$fpath."";
			}
			exec($command);
		}
/*
		if (file_exists($tmp_folder. $this->MD5_LIST_FNAME)) {
			// Delete md5sum list file
			unlink($tmp_folder. $this->MD5_LIST_FNAME);
		}
*/
		if (file_exists($result_filepath)){
			return $result_filepath;
		} else {
			_re(t("Backup failed")."!");
			return false;

		}
	}

	/**
	* Extract files from tar archive
	*/
	function _untar ($full_fpath = "") {

		$full_fpath = trim($full_fpath);
 		$fname = basename($full_fpath);

		$tar_file_path = substr($full_fpath, 0, -strlen($fname));
		if (!file_exists($full_fpath) || !$full_fpath) {
			_re(t("Resotre failed")."!. " .t("No such file"));
			return false;
		}

		if($this->VERIFY_ARCHIVE){
// TODO verification - find optimal method

		}
		chdir("/");
 		$command = $this->TAR_PATH."tar --extract -f ".$full_fpath;
		exec($command);
		return true;
	}

	/**
	* Create md5sum list
	*/
	function _md5sum_list($files_array = array()) {
		foreach((array)$files_array as $fpath) {
			if (!file_exists($fpath)){
				continue;
			}
			if (is_dir($fpath)) {
				$folder_files_array = $this->DIR_OBJ->scan_dir($fpath, true);
				$this->STRING .= $this->_md5sum_list($folder_files_array, $this->STRING);
			} else {
				$this->STRING .= $fpath."\t".md5_file($fpath)."\n";
			}
		}
		return $this->STRING;
	}
}
