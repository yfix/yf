<?php

/**
* Generating thumbs from images handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_make_thumb {

// TODO: ability resize animated gif with saving animation

	/** @var array */
// TODO: connect this
	var $ALLOWED_MIME_TYPES = array(
		"image/jpeg"	=> "jpeg",
		"image/pjpeg"	=> "jpeg",
		"image/png"		=> "png",
		"image/gif"		=> "gif",
	);
	/** @var bool */
// TODO: connect this
	var $OUTPUT_IMAGE_TYPE	= "jpeg";
	/** @var bool */
	var $ALLOW_NETPBM			= 1;
	/** @var bool */
	var $ALLOW_IMAGICK			= 0;
	/** @var array */
	var $LIBS_PRIORITY			= array(
		"imagick",
		"netpbm",
		"gd",
	);
	/** @var bool */
	var $ENABLE_DEBUG_LOG		= 0;
	/** @var bool */
	var $LOG_EXEC_CMDS			= 0;
	/** @var string */
	var $DEBUG_LOG_FILE			= "logs/make_thumb.log";
	/** @var bool Depends on ENABLE_DEBUG_LOG */
	var $LOG_TO_FILE			= 1;
	/** @var bool Depends on ENABLE_DEBUG_LOG */
	var $LOG_TO_DB				= 1;
	/** @var bool Depends on ENABLE_DEBUG_LOG */
	var $DB_LOG_ENV				= 1;
	/** @var bool Use all available libs in their order (if one fails - the try another) */
	var $DEGRADE_GRACEFULLY		= 1;
	/** @var bool Use temporary images (more operations but more stable) */
// TODO: currently broken
	var $USE_TEMP_IMAGES		= 1;
	/** @var string Folder for temporary images */
	var $TEMP_IMAGES_DIR		= "uploads/tmp/";
	/** @var string Folder for temporary images */
	var $BAD_IMAGES_DIR			= "logs/bad_images/";
	/** @var bool */
	var $AUTO_FIND_PATHS		= 0;
	/** @var string */
	var $FOUND_NETPBM_PATH		= "";
	/** @var string */
	var $FOUND_IMAGICK_PATH		= "";
	/** @var bool Collect wrong images */
	var $COLLECT_BAD_IMAGES		= 0;
	/** @var bool Delete wrong images from destination folder */
	var $DELETE_BAD_DEST_IMAGES	= 1;
	/** @var bool Force resizing for images with lower sizes than limits, but possibly with not optimal size */
	var $FORCE_PROCESSING		= 0;

	/**
	* Module constructor
	*
	* @access	public
	* @return	void
	*/
	function _init () {
		// Prepare path to the temporary folder
		if ($this->USE_TEMP_IMAGES) {
			$tmp_dir_path = INCLUDE_PATH.$this->TEMP_IMAGES_DIR;
			if (!file_exists($tmp_dir_path)) {
				_class("dir")->mkdir_m($tmp_dir_path, 0777);
			}
		}
		// Prepare path to the temporary folder
		if ($this->COLLECT_BAD_IMAGES) {
			$bad_images_path = INCLUDE_PATH. $this->BAD_IMAGES_DIR;
			if (!file_exists($bad_images_path)) {
				_mkdir_m($bad_images_path, 0777);
			}
		}
		// Set found paths
		if (empty($this->FOUND_NETPBM_PATH) && defined("NETPBM_PATH") && NETPBM_PATH != "") {
			$this->FOUND_NETPBM_PATH	= NETPBM_PATH;
		}
		if (empty($this->FOUND_IMAGICK_PATH) && defined("IMAGICK_PATH") && IMAGICK_PATH != "") {
			$this->FOUND_IMAGICK_PATH	= IMAGICK_PATH;
		}
		// Quick check libs paths for windows
		if (OS_WINDOWS) {
			if ($this->FOUND_NETPBM_PATH{1} != ":") {
				$this->FOUND_NETPBM_PATH = "";
			}
			if ($this->FOUND_IMAGICK_PATH{1} != ":") {
				$this->FOUND_IMAGICK_PATH = "";
			}
		}
		// Try to find paths to Netpbm and ImageMagick
		if ($this->AUTO_FIND_PATHS && ($this->ALLOW_NETPBM || $this->ALLOW_IMAGICK)) {
			$this->_try_to_find_libs();
		}
		// Force turn off not found libs
		if (empty($this->FOUND_NETPBM_PATH)) {
			$this->ALLOW_NETPBM		= false;
		}
		if (empty($this->FOUND_IMAGICK_PATH)) {
			$this->ALLOW_IMAGICK	= false;
		}
		if (empty($this->LIBS_PRIORITY)) {
			$this->LIBS_PRIORITY = array("gd");
		}
	}

	/**
	* Make thumbnail using best available method
	*/
	function go($source_file_path = "", $dest_file_path = "", $LIMIT_X = -1, $LIMIT_Y = -1) {
		$_prev_num_errors = count((array)main()->_all_core_error_msgs);
		// Cleanup input params
		$LIMIT_X = intval($LIMIT_X != -1 ? $LIMIT_X : THUMB_WIDTH);
		$LIMIT_Y = intval($LIMIT_Y != -1 ? $LIMIT_Y : THUMB_HEIGHT);
		if (empty($source_file_path) || empty($dest_file_path)) {
			trigger_error("MAKE_THUMB: Source or destination path is missing", E_USER_WARNING);
			return false;
		}
		// Check source file
		if (!file_exists($source_file_path) || !filesize($source_file_path) || !is_readable($source_file_path)) {
			trigger_error("MAKE_THUMB: Source file is empty", E_USER_WARNING);
			return false;
		}
		if ($this->ENABLE_DEBUG_LOG) {
			$source_size = filesize($source_file_path);
			$_start_time = microtime(true);
		}
		// Use temporary image for manipulations (only if we have same paths for resizing)
		if ($this->USE_TEMP_IMAGES && $source_file_path == $dest_file_path) {
/*
			$tmp_source_path	= INCLUDE_PATH. $this->TEMP_IMAGES_DIR. "resize_". common()->rand_name(32). "_source";
			copy($source_file_path, $tmp_source_path);
			// Check if file was created and rotate files
			if (file_exists($tmp_source_path)) {
				$old_source_path	= $source_file_path;
				$source_file_path	= $tmp_source_path;
			}
*/
		}
		$USED_LIB	= "";
		$tried_libs	= array();
		$tried_cmds	= array();
		// Use libs in specified priority order
		foreach ((array)$this->LIBS_PRIORITY as $cur_lib) {
			$lib_result_error = false;
			if ($cur_lib == "gd") {
				$result_gd = $this->_use_gd($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y);
				if (!$result_gd) {
					$lib_result_error = true;
				}
				$USED_LIB = $cur_lib;
			} elseif ($cur_lib == "netpbm" && $this->ALLOW_NETPBM) {
				$tried_cmds[] = $this->_use_netpbm($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y);
				$USED_LIB = $cur_lib;
			} elseif ($cur_lib == "imagick" && $this->ALLOW_IMAGICK) {
				$tried_cmds[] = $this->_use_imagick($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y);
				$USED_LIB = $cur_lib;
			}
			// Skip not allowed libs
			if (empty($USED_LIB)) {
				continue;
			}
			// Save used libs order
			$tried_libs[$USED_LIB] = $USED_LIB;
			// Check resize result
			clearstatcache();
// TODO: need to upgrade success checker with checking destination image dimensions
			$resize_success = false;
			if (!$lib_result_error) {
				$resize_success = (file_exists($dest_file_path) && filesize($dest_file_path) > 0 && is_readable($dest_file_path));
			}
			// DEGRADE_GRACEFULLY, try to use another lib if previous fails
			if (!$this->DEGRADE_GRACEFULLY) {
				break;
			} elseif ($this->DEGRADE_GRACEFULLY) {
				// Possible broken lib processing result, do something with it
				if (empty($resize_success)) {
// TODO: test this
/*
					// Try to restore previous state
					if ($this->USE_TEMP_IMAGES && !empty($tmp_source_path) && !$resize_success) {
						copy($old_source_path, $tmp_source_path);
					}
*/
				} else {
					// Everything seems fine, stop here
					break;
				}
			}
		}
		// Process temporary image if needed
		if ($this->USE_TEMP_IMAGES && !empty($tmp_source_path) && $resize_success) {
// TODO: check this
//			copy($tmp_source_path, $old_source_path);
		}
		// Cleanup temp image
		if ($this->USE_TEMP_IMAGES && !empty($tmp_source_path)) {
//			unlink($tmp_source_path);
		}
		// Collect bad images
		if (!$resize_success && $this->COLLECT_BAD_IMAGES) {
			$bad_file_path = INCLUDE_PATH. $this->BAD_IMAGES_DIR. basename($source_file_path);
			copy($source_file_path, $bad_file_path);
		}
		// Do delete image if resize failed
		if (!$resize_success && $this->DELETE_BAD_DEST_IMAGES && file_exists($dest_file_path)) {
			unlink($dest_file_path);
		}
		// Save log
		if ($this->ENABLE_DEBUG_LOG && ($this->LOG_TO_FILE || $this->LOG_TO_DB)) {
			// Try to catch last error messages
			$error_message .= implode("\n", $_prev_num_errors ? array_slice((array)main()->_all_core_error_msgs, $_prev_num_errors) : (array)main()->_all_core_error_msgs);
			// Prepare log path
			$log_file_path = INCLUDE_PATH.$this->DEBUG_LOG_FILE;
			// Do create log dir
			_class("dir")->mkdir_m(dirname($log_file_path));
			$_exec_time = (float)microtime(true) - (float)$_start_time;
			// Trying to get image info
			if (file_exists($source_file_path)) {
				list($_source_width, $_source_height, , ) = @getimagesize($source_file_path);
			}
			if ($resize_success && file_exists($dest_file_path)) {
				list($_result_width, $_result_height, , ) = @getimagesize($dest_file_path);
			}
			$result_file_size = file_exists($dest_file_path) ? filesize($dest_file_path) : 0;
			// Try to get user error message source
			$backtrace = debug_backtrace();
			$cur_trace	= $backtrace[1];
			// Save log data
			if ($this->LOG_TO_FILE) {
				$log_data = "## ".date("Y-m-d H:i:s")."; ##";
				$log_data .= "\r\n";
				$log_data .= "user_id: ".main()->USER_ID."; ";
				$log_data .= "user_group: ".main()->USER_GROUP."; ";
				$log_data .= "referer: \"".$_SERVER["HTTP_REFERER"]."\"; ";
				$log_data .= "\r\n";
				$log_data .= "source_path: \"".$source_file_path."\"; ";
				$log_data .= "\r\n";
				$log_data .= "source_file_size: ".intval($source_size)."; ";
				$log_data .= "source_x: \"".intval($_source_width)."\"; source_y: \"".intval($_source_height)."\"; ";
				$log_data .= "\r\n";
				$log_data .= "result_path: \"".$dest_file_path."\"; ";
				$log_data .= "\r\n";
				$log_data .= "result_file_size: ".intval($result_file_size)."; ";
				$log_data .= "result_x: \"".intval($_result_width)."\"; result_y: \"".intval($_result_height)."\"; ";
				$log_data .= "\r\n";
				$log_data .= "result_success: \"".($resize_success ? "yes" : "no")."\"; ";
				$log_data .= "time_spent: ".common()->_format_time_value($_exec_time)."; ";
				$log_data .= "tried libs: \"".implode(",", $tried_libs)."\"; ";
				$log_data .= "used lib: \"".$USED_LIB."\"; ";
				$log_data .= "limit_x: \"".intval($LIMIT_X)."\"; limit_y: \"".intval($LIMIT_Y)."\"; ";
				if (!empty($tried_cmds) && $this->LOG_EXEC_CMDS) {
					$log_data .= "\r\n";
					$log_data .= "tried cmds (exec):  ".implode(";", $tried_cmds)."; ";
				}
				$log_data .= "\r\n";
				$log_data .= "\r\n";
				if ($fh = @fopen($log_file_path, "a")) {
					fwrite($fh, $log_data);
					@fclose($fh);
				}
				// Prepare this log to display in browser
				if (DEBUG_MODE) {
					$GLOBALS['_RESIZED_IMAGES_LOG'][] = $log_data;
				}
			}
			// Save into db
			if ($this->LOG_TO_DB) {
				if (!empty($tried_cmds) && $this->LOG_EXEC_CMDS) {
					$other_options .= "tried cmds (exec):  ".implode(";", $tried_cmds)."; ";
				}
				// Prepare SQL
				$sql = db()->INSERT("log_img_resizes", array(
					"source_path"		=> _es($source_file_path),
					"source_file_size"	=> intval($source_size),
					"source_x"			=> intval($_source_width),
					"source_y"			=> intval($_source_height),
					"result_path"		=> _es($dest_file_path),
					"result_file_size"	=> intval($result_file_size),
					"result_x"			=> intval($_result_width),
					"result_y"			=> intval($_result_height),
					"limit_x"			=> intval($LIMIT_X),
					"limit_y"			=> intval($LIMIT_Y),
					"source_file"		=> _es($cur_trace["file"]),
					"source_line"		=> intval($cur_trace["line"]),
					"date"				=> time(),
					"site_id"			=> (int)conf('SITE_ID'),
					"user_id"			=> intval($_SESSION[MAIN_TYPE_ADMIN ? "admin_id" : "user_id"]),
					"user_group"		=> intval($_SESSION[MAIN_TYPE_ADMIN ? "admin_group" : "user_group"]),
					"is_admin"			=> MAIN_TYPE_ADMIN ? 1 : 0,
					"ip"				=> _es(common()->get_ip()),
					"query_string"		=> _es(WEB_PATH."?".$_SERVER["QUERY_STRING"]),
					"user_agent"		=> _es($_SERVER["HTTP_USER_AGENT"]),
					"referer"			=> _es($_SERVER["HTTP_REFERER"]),
					"request_uri"		=> _es($_SERVER["REQUEST_URI"]),
					"env_data"			=> $this->DB_LOG_ENV ? _es(serialize(array("_GET"=>$_GET,"_POST"=>$_POST))) : "",
					"object"			=> _es($_GET["object"]),
					"action"			=> _es($_GET["action"]),
					"success"			=> intval((bool)$resize_success),
					"error_text"		=> _es($error_message),
					"process_time"		=> floatval(common()->_format_time_value($_exec_time)),
					"used_lib"			=> _es($USED_LIB),
					"tried_libs"		=> _es(implode(",", $tried_libs)),
					"other_options"		=> _es($other_options),
				), true);
				db()->_add_shutdown_query($sql);
			}
		}
		return $resize_success;
	} 

	/**
	* Use GD library
	*/
	function _use_gd($source_file_path = "", $dest_file_path = "", $LIMIT_X = -1, $LIMIT_Y = -1) {
		$I = _class("resize_images");
		$I->reduce_only = 1;
		if ($this->FORCE_PROCESSING) {
			$I->force_process = true;
		}
		$result = false;
		if ($I->set_source($source_file_path)) {
			$I->set_limits($LIMIT_X, $LIMIT_Y);
			$I->save($dest_file_path);
			$result = true;
		}
		$I->_clean_data();

		return $result;
	}

	/**
	* Use NETPBM library http://netpbm.sourceforge.net/
	*/
	function _use_netpbm($source_file_path = "", $dest_file_path = "", $LIMIT_X = -1, $LIMIT_Y = -1) {
		// Not working under WIN for now (possibly can, but we have not time to make it work correctly)
		if (OS_WINDOWS) {
			return false;
		}
		// Generate correct resize command for NETPBM library
		$pnmscale_cmd = "";
		if ($LIMIT_X > 0 && $LIMIT_Y > 0) {
			$pnmscale_cmd	= " -xysize ".intval($LIMIT_X)." ".intval($LIMIT_Y)." ";
		} elseif ($LIMIT_X > 0) {
			$pnmscale_cmd	= " -width=".intval($LIMIT_X)." ";
		} elseif ($LIMIT_Y > 0) {
			$pnmscale_cmd	= " -height=".intval($LIMIT_Y)." ";
		}
		// Prepare file paths
		$source_file_path	= "\"".$source_file_path."\"";
		$dest_file_path		= "\"".$dest_file_path."\"";
		// Prepare lib command string
		$PATH_TO_NETPBM = $this->FOUND_NETPBM_PATH;
		$netpbm_cmd	= $PATH_TO_NETPBM."anytopnm ".$source_file_path." | ".(!empty($pnmscale_cmd) ? $PATH_TO_NETPBM."pnmscale ".$pnmscale_cmd." | " : "").$PATH_TO_NETPBM."ppmtojpeg ".(defined("THUMB_QUALITY") ? " -quality=".intval(THUMB_QUALITY) : "")." > ".$dest_file_path;
		$output = exec($netpbm_cmd);
		// Check resize result
// TODO
		return $netpbm_cmd;
	}

	/**
	* Use Image Magick library	http://www.imagemagick.org/
	*/
	function _use_imagick($source_file_path = "", $dest_file_path = "", $LIMIT_X = -1, $LIMIT_Y = -1) {
		// Generate correct resize command for Imagick library
		$resize_cmd = "";
		if ($LIMIT_X > 0 && $LIMIT_Y > 0) {
			$resize_cmd	= intval($LIMIT_X)."x".intval($LIMIT_Y);
		} elseif ($LIMIT_X > 0) {
			$resize_cmd	= intval($LIMIT_X)."x";
		} elseif ($LIMIT_Y > 0) {
			$resize_cmd	= "x".intval($LIMIT_Y);
		}
		// Prepare lib command string
		$PATH_TO_IMAGICK = $this->FOUND_IMAGICK_PATH;
		$_source_image_info = $this->_image_info_imagick($source_file_path);
		if ($_source_image_info["type"] == "gif") {
			$add_cmd = " -composite ";
		}
		$imagick_cmd	= $PATH_TO_IMAGICK."convert ".$source_file_path." ".$add_cmd." ".(!empty($resize_cmd) ? "-thumbnail \"".$resize_cmd.">\"" : "")." ".(defined("THUMB_QUALITY") ? " -quality \"".intval(THUMB_QUALITY)."\"" : "")." ".$dest_file_path;
		$output = exec($imagick_cmd);
		// Check resize result
		$_dest_image_info = $this->_image_info_imagick($dest_file_path);
		if ($_dest_image_info && (
			!$_dest_image_info["width"] || 
			!$_dest_image_info["height"] || 
			($LIMIT_X > 0 && $_dest_image_info["width"] > $LIMIT_X) || 
			($LIMIT_Y > 0 && $_dest_image_info["height"] > $LIMIT_Y)
		)) {
			unlink($dest_file_path);
		}
		return $imagick_cmd;
	}

	/**
	* Get image info using "identify" binary from "imagemagick"
	*/
	function _image_info_imagick($file_path = "") {
		$result = array();
		if (!$file_path || !file_exists($file_path)) {
			return $result;
		}
		$PATH_TO_IMAGICK = $this->FOUND_IMAGICK_PATH;

		$identify_result = exec($PATH_TO_IMAGICK.'identify -format %m#%w#%h* '.$file_path);

		// Parse result
		list($result["type"], $result["width"], $result["height"]) = explode("#", substr($identify_result, 0, strpos($identify_result, "*")));
		$result["type"] = strtolower($result["type"]);

		return $result;
	}

	/**
	* Use Image Magick library	http://www.imagemagick.org/
	*/
	function _try_to_find_libs () {
		if (!$this->AUTO_FIND_PATHS) {
			return false;
		}
		// Try to find path for the NETPBM
		if ($this->ALLOW_NETPBM/* && (NETPBM_PATH == "NETPBM_PATH" || NETPBM_PATH == "")*/ && empty($this->FOUND_NETPBM_PATH)) {
			$paths = array();
			if (OS_WINDOWS) {
				$file_to_test = "pnmscale.exe";
				foreach (explode(';', getenv('PATH')) as $path) {
					$path = trim($path);
					if (empty($path)) {
						continue;
					}
					if ($path{strlen($path)-1} != $slash) {
						$path .= $slash;
					}
					$paths[] = $path;
				}
				// Double-quoting the paths removes any ambiguity about the
				// double-slashes being escaped or not
				$paths[] = "C:\\Program Files\\netpbm\\";
				$paths[] = "C:\\apps\\netpbm\\";
				$paths[] = "C:\\apps\\jhead\\";
				$paths[] = "C:\\netpbm\\";
				$paths[] = "C:\\jhead\\";
				$paths[] = "C:\\cygwin\\bin\\";
			} else {
				$file_to_test = "pnmscale";
				foreach (explode(':', getenv('PATH')) as $path) {
					$path = trim($path);
					if (empty($path)) {
						continue;
					}
					if ($path{strlen($path)-1} != $slash) {
						$path .= $slash;
					}
					$paths[] = $path;
				}
				$paths[] = '/usr/bin/';
				$paths[] = '/usr/local/bin/';
				$paths[] = '/usr/local/netpbm/bin/';
				$paths[] = '/bin/';
				$paths[] = '/sw/bin/';
			}
			// Now try each path in turn to see which ones work
			$success = false;
			foreach ((array)$paths as $_cur_path) {
				if (!file_exists($_cur_path)) {
					continue;
				}
				if (file_exists($_cur_path. $file_to_test)/* && is_executable($_cur_path. $file_to_test)*/) {
					$success = true;
					$this->FOUND_NETPBM_PATH = $_cur_path;
					break;
				}
			}
		}
		// Try to find path for the ImageMagick
		if ($this->ALLOW_IMAGICK/* && (IMAGICK_PATH == "IMAGICK_PATH" || IMAGICK_PATH == "")*/ && empty($this->FOUND_IMAGICK_PATH)) {
			$paths = array();
			if (OS_WINDOWS) {
				$file_to_test = "convert.exe";
				foreach (explode(';', getenv('PATH')) as $path) {
					$path = trim($path);
					if (empty($path)) {
						continue;
					}
					if ($path{strlen($path)-1} != $slash) {
						$path .= $slash;
					}
					$paths[] = $path;
				}
				// Double-quoting the paths removes any ambiguity about the
				// double-slashes being escaped or not
				$paths[] = "C:\\Program Files\\ImageMagick\\";
				$paths[] = "C:\\apps\ImageMagick\\";
				$paths[] = "C:\\ImageMagick\\";
				$paths[] = "C:\\ImageMagick\\VisualMagick\\bin\\";
				$paths[] = "C:\\cygwin\\bin\\";
			} else {
				$file_to_test = "convert";
				foreach (explode(':', getenv('PATH')) as $path) {
					$path = trim($path);
					if (empty($path)) {
						continue;
					}
					if ($path{strlen($path)-1} != $slash) {
						$path .= $slash;
					}
					$paths[] = $path;
				}
				$paths[] = '/usr/bin/';
				$paths[] = '/usr/local/bin/';
				$paths[] = '/bin/';
				$paths[] = '/sw/bin/';
			}
			// Now try each path in turn to see which ones work
			$success = false;
			foreach ((array)$paths as $_cur_path) {
				if (!file_exists($_cur_path)) {
					continue;
				}
				if (file_exists($_cur_path. $file_to_test)/* && is_executable($_cur_path. $file_to_test)*/) {
					$success = true;
					$this->FOUND_IMAGICK_PATH = $_cur_path;
					break;
				}
			}
		}
	}
}
