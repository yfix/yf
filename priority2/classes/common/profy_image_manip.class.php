<?php

/**
* Crop & rotate images
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_image_manip {

// TODO: ability resize animated gif with saving animation

	/** @var bool */
// TODO: connect this
	var $OUTPUT_IMAGE_TYPE	= "jpeg";
	/** @var bool */
	var $ALLOW_NETPBM			= 0;
	/** @var bool */
	var $ALLOW_IMAGICK			= 0;
	/** @var array */
// TODO: connect this
	var $LIBS_PRIORITY			= array(
		"imagick",
		"netpbm",
		"gd",
	);
	/** @var bool */
	var $AUTO_FIND_PATHS		= 1;
	/** @var string */
	var $FOUND_NETPBM_PATH		= "";
	/** @var string */
	var $FOUND_IMAGICK_PATH		= "";

	/**
	* Module constructor
	*/
	function _init () {
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
	* Rotate image using best available method
	*/
	function rotate($source_file_path, $dest_file_path, $ANGLE) {
		// Check source file
		if (!file_exists($source_file_path) || !filesize($source_file_path) || !is_readable($source_file_path)) {
			trigger_error("ROTATE_IMG: Source file is empty", E_USER_WARNING);
			return false;
		}
		$USED_LIB	= "";
		// Use libs in specified priority order
		foreach ((array)$this->LIBS_PRIORITY as $cur_lib) {

			if ($cur_lib == "gd") {
				$this->_gd_rotate($source_file_path, $dest_file_path, $ANGLE);
				$USED_LIB = $cur_lib;
				return $dest_file_path;
			} elseif ($cur_lib == "netpbm" && $this->ALLOW_NETPBM) {
				if (!empty($ANGLE) && $ANGLE < 90 && $ANGLE > -90) {
					$ANGLE = intval($ANGLE);
				} else {
					trigger_error("ROTATE_IMG: current angle is wrong! Use angle up -90 to 90", E_USER_ERROR);
					break;
				}
				$ANGLE = intval($ANGLE);
				$this->_netpbm_rotate($source_file_path, $dest_file_path, $ANGLE);
				$USED_LIB = $cur_lib;
				return $dest_file_path;
			} elseif ($cur_lib == "imagick" && $this->ALLOW_IMAGICK) {
				if (!empty($ANGLE) && $ANGLE > -1) {
					$ANGLE = intval($ANGLE);
				}
				$this->_imagick_rotate($source_file_path, $dest_file_path, $ANGLE);
				$USED_LIB = $cur_lib;
				return $dest_file_path;
			}
			// Skip not allowed libs
			if (empty($USED_LIB)) {
				continue;
			}
		}
	}

	/**
	* Crop box
	*/
	function crop_box($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y) {
		$CROP_SIZE = $LIMIT_X;
		// Pre-check
		if (!file_exists($source_file_path)) {
			trigger_error("CROP_BOX: Source file is empty", E_USER_WARNING);
			return false;
		}

		list($x, $y) = getimagesize($source_file_path);
		// Calculate and crop square image
		if ($x && $x != $y) {
			$is_portrait = $x < $y ? 1 : 0;
			if ($is_portrait) {
				$crop_width		= $x;
				$crop_height	= $crop_width;
				$pos_left		= 0;
				$pos_top		= floor(($y - $x) / 2);
			} else {
				$crop_height	= $y;
				$crop_width		= $crop_height;
				$pos_top		= 0;
				$pos_left		= floor(($x - $y) / 2);
			}
// echo "x:$x,y:$y,is_portrait:$is_portrait,crop_width:$crop_width,crop_height:$crop_height,pos_top:$pos_top,pos_left:$pos_left;path:$dest_file_path<br />";
			$crop_result = $this->crop($source_file_path, $dest_file_path, $crop_width, $crop_height, $pos_left, $pos_top);
		}
		// Do make thumb
		$thumb_result = common()->make_thumb($dest_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y);

		return $thumb_result;
	}

	/**
	* Crop or cut image using best available method
	*/
	function crop($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y, $pos_left, $pos_top) {
		// Check source file
		if (!file_exists($source_file_path) || !filesize($source_file_path) || !is_readable($source_file_path)) {
			trigger_error("CROP_IMG: Source file is empty", E_USER_WARNING);
			return false;
		}
		if ($LIMIT_X > -1 && $LIMIT_Y > -1) {
			$LIMIT_X	= intval($LIMIT_X);
			$LIMIT_Y	= intval($LIMIT_Y);
		}
		if ($pos_left > -1 && $pos_top > -1) {
			$pos_left	= intval($pos_left);
			$pos_top	= intval($pos_top);
		}
		$USED_LIB	= "";
		// Use libs in specified priority order
		foreach ((array)$this->LIBS_PRIORITY as $cur_lib) {
			if ($cur_lib == "gd") {
				$this->_gd_crop($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y, $pos_left, $pos_top);
				$USED_LIB = $cur_lib;
				return $dest_file_path;
			} elseif ($cur_lib == "netpbm" && $this->ALLOW_NETPBM) {
				$this->_netpbm_cut($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y, $pos_left, $pos_top);
				$USED_LIB = $cur_lib;
				return $dest_file_path;
			} elseif ($cur_lib == "imagick" && $this->ALLOW_IMAGICK) {
				$this->_imagick_crop($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y, $pos_left, $pos_top);
				$USED_LIB = $cur_lib;
				return $dest_file_path;
			}
			// Skip not allowed libs
			if (empty($USED_LIB)) {
				continue;
			}
		}
	}

	/**
	* Rotate useing GD library
	*/
	function _gd_rotate ($original_path, $dest_file_path, $angle) {
		if (!extension_loaded('gd')) {
			return false;
		}
		// Get file extentions
		$ext = common()->get_file_ext(basename($original_path));
		if (!empty($ext) && $ext == "jpg" | $ext == "jpeg") {
			$src_resource		= imagecreatefromjpeg($original_path);
		}
		if (!empty($ext) && $ext == "gif") {
			$src_resource		= imagecreatefromgif($original_path);
		}
		if (!empty($ext) && $ext == "png") {
			$src_resource		= imagecreatefrompng($original_path);
		}
		$rotate_resource	= imagerotate($src_resource, $angle, 0);
		if (!imagejpeg($rotate_resource, $dest_file_path, 100)){
			return false;
		}
		imagedestroy($src_resource);
		imagedestroy($rotate_resource);
		return $rotated_path;
	}

	/**
	* Crop useing GD library
	*/
	function _gd_crop ($source_path, $dest_file_path, $crop_width, $crop_height, $pos_left, $pos_top) {
		if (!extension_loaded('gd')) {
			return false;
		}
		$original_path 	= $source_path;
		// crop original image
		// Get file extentions
		$ext = common()->get_file_ext(basename($original_path));
		if (!empty($ext) && $ext == "jpg" | $ext == "jpeg") {
			$src_resource	= imagecreatefromjpeg($original_path);
		}
		if (!empty($ext) && $ext == "gif") {
			$src_resource	= imagecreatefromgif($original_path);
		}
		if (!empty($ext) && $ext == "png") {
			$src_resource	= imagecreatefrompng($original_path);
		}
		$dest_resource	= imagecreatetruecolor($crop_width, $crop_height);
		$result			= imagecopy($dest_resource, $src_resource, 0, 0, $pos_left, $pos_top, $crop_width, $crop_height);
		if (!imagejpeg($dest_resource, $dest_file_path, 75)) {
			return false;
		}
		imagedestroy($src_resource);
		imagedestroy($dest_resource);
		return $dest_file_path;
	}


	/**
	* Use NetPBM library http://netpbm.sourceforge.net/
	* function cut image using NetPBM
	*/
	function _netpbm_cut($source_file_path, $dest_file_path, $LIMIT_Width, $LIMIT_Height, $pos_left, $pos_top) {
		// Generate correct resize command for NetPBM library
		$PATH_TO_NETPBM = $this->FOUND_NETPBM_PATH;
//		$dest_file_path = $source_file_path;
	    // Check operation system
		if (OS_WINDOWS) {
			$ext = common()->get_file_ext(basename($source_file_path));
			// Check extentions
			if(!empty($ext) && $ext == "jpg" | $ext == "jpeg") {
				$netpbm_cmd = $PATH_TO_NETPBM."jpegtopnm \"".$source_file_path."\" | ".$PATH_TO_NETPBM."pnmcut ".$pos_left." ".$pos_top." ".$LIMIT_Width." ".$LIMIT_Height." | ".$PATH_TO_NETPBM."ppmtojpeg -quality=75 > \"".$dest_file_path."\"";
			}
			if(!empty($ext) && $ext == "gif") {
				$netpbm_cmd = $PATH_TO_NETPBM."giftopnm \"".$source_file_path."\" | ".$PATH_TO_NETPBM."pnmcut ".$pos_left." ".$pos_top." ".$LIMIT_Width." ".$LIMIT_Height." | ".$PATH_TO_NETPBM."ppmtojpeg -quality=75 > \"".$dest_file_path."\"";
			}
			if(!empty($ext) && $ext == "png") {
				$netpbm_cmd = $PATH_TO_NETPBM."pngtopnm \"".$source_file_path."\" | ".$PATH_TO_NETPBM."pnmcut ".$pos_left." ".$pos_top." ".$LIMIT_Width." ".$LIMIT_Height." | ".$PATH_TO_NETPBM."ppmtojpeg -quality=75 > \"".$dest_file_path."\"";
			}
		}
		else {
				$netpbm_cmd = $PATH_TO_NETPBM."anytopnm \"".$source_file_path."\" | ".$PATH_TO_NETPBM."pnmcut ".$pos_left." ".$pos_top." ".$LIMIT_Width." ".$LIMIT_Height." | ".$PATH_TO_NETPBM."ppmtojpeg -quality=75 > \"".$dest_file_path."\"";
		}
    	$output = @exec($netpbm_cmd);
		
		return ($dest_file_path);
	}

	/* 
	* Rotate image with netpbm
	*
	* !!!warning!!!
	* background writes in RGB & $LIMITH up -90 to 90
	* background = #FFFFFF
	*/
	function _netpbm_rotate($source_file_path, $dest_file_path, $LIMIT) {
		$PATH_TO_NETPBM = $this->FOUND_NETPBM_PATH;
		$dest_file_path = $source_file_path;
	    // Check operation system
		if (OS_WINDOWS) {
			$ext = common()->get_file_ext(basename($source_file_path));

			// Check extentions
			if(!empty($ext) && $ext == "jpg" | $ext == "jpeg") {
			$netpbm_cmd = $PATH_TO_NETPBM."jpegtopnm \"".$source_file_path."\" | ".$PATH_TO_NETPBM."pnmrotate -background=#FFFFFF ".$LIMIT." | ".$PATH_TO_NETPBM."ppmtojpeg -quality=75 > \"".$dest_file_path."\"";
			}
			if(!empty($ext) && $ext == "gif") {
				$netpbm_cmd = $PATH_TO_NETPBM."giftopnm \"".$source_file_path."\" | ".$PATH_TO_NETPBM."pnmrotate -background=#FFFFFF ".$LIMIT." | ".$PATH_TO_NETPBM."ppmtojpeg -quality=75 > \"".$dest_file_path."\"";
			}
			if(!empty($ext) && $ext == "png") {
				$netpbm_cmd = $PATH_TO_NETPBM."pngtopnm \"".$source_file_path."\" | ".$PATH_TO_NETPBM."pnmrotate -background=#FFFFFF ".$LIMIT." | ".$PATH_TO_NETPBM."ppmtojpeg -quality=75 > \"".$dest_file_path."\"";
			}
		}
		else {
				$netpbm_cmd = $PATH_TO_NETPBM."anytopnm \"".$source_file_path."\" | ".$PATH_TO_NETPBM."pnmrotate -background=#FFFFFF ".$LIMIT." | ".$PATH_TO_NETPBM."ppmtojpeg -quality=75 > \"".$dest_file_path."\"";
		}

		$output = @exec($netpbm_cmd);
		return $dest_file_path;
	}

	/**
	* Rotate image use Image Magick library	http://www.imagemagick.org/
	*/
	function _imagick_rotate($source_file_path, $dest_file_path, $LIMIT) {
		$PATH_TO_IMAGICK = $this->FOUND_IMAGICK_PATH;
		$dest_file_path = $source_file_path;
		$imagick_cmd = $PATH_TO_IMAGICK."convert ".$source_file_path." -rotate ".$LIMIT." ".$dest_file_path;		
		$output = @exec($imagick_cmd);
		return $dest_file_path;
	}

    /**
	* Crop image use Image Magick library	http://www.imagemagick.org/
	*/
	function _imagick_crop($source_file_path, $dest_file_path, $LIMIT_width, $LIMIT_height, $pos_left, $pos_top) {
		$PATH_TO_IMAGICK = $this->FOUND_IMAGICK_PATH;
//		$command = $PATH_TO_IMAGICK."convert -size {$LIMIT_width}x{$LIMIT_height} {$source_file_path} -thumbnail {$LIMIT_width}x{$LIMIT_height} -gravity center -crop {$LIMIT_width}x{$LIMIT_height}+0+0 +repage {$dest_file_path}";
		$command = $PATH_TO_IMAGICK."convert {$source_file_path} -crop {$LIMIT_width}x{$LIMIT_height}+{$pos_left}+{$pos_top} {$dest_file_path}";
		@exec($command);

		return file_exists($dest_file_path);
	}


	/**
	* find libs & paths of imagelibs
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
				$paths[] = "D:\\www\\GnuWin\\bin\\";
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
				$paths[] = "D:\\www\\ImageMagick\\";
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
