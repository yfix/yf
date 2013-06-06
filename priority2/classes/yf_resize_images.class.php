<?php

/**
* Image resizing class
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_resize_images {

	/** @var string @conf_skip Image source file name */
	var $source_file	= null;
	/** @var string @conf_skip Image type (like "jpeg", "gif", "png" etc) detected from source file */
	var $source_type	= null;
	/** @var int @conf_skip Image width (source) */
	var $source_width	= null;
	/** @var int @conf_skipImage height (source) */
	var $source_height	= null;
	/** @var mixed @conf_skip Image attributes (info from source) */
	var $source_atts	= null;
	/** @var int Current limits for generating thumbnails */
	var $limit_x		= 100;
	/** @var int Current limits for generating thumbnails */
	var $limit_y		= 100;
	/** @var bool Reduce only or fit limits proportionally */
	var $reduce_only	= false;
	/** @var bool Force processing even when source and dest x/y are equal */
	var $force_process	= false;
	/** @var int @conf_skip Thumbnail width */
	var $output_width	= null;
	/** @var int @conf_skip Thumbnail height */
	var $output_height	= null;
	/** @var string Output image type (could be "jpeg" or "png") */
	var $output_type	= "jpeg";
	/** @var mixed @conf_skip For internal manipulation */
	var $tmp_img		= null;
	/** @var mixed @conf_skip Temporary resampled file stored here */
	var $tmp_resampled	= null;
	/** @var array @conf_skip Available image types */
	var $_avail_types	= array (
		1 => "gif",
		2 => "jpeg",
		3 => "png",
	);
	/** @var bool */
	var $SILENT_MODE	= false;
 
	/**
	* Constructor
	*/
	function yf_resize_images ($img_file = "") {
		if (strlen($img_file)) {
			$this->set_source($img_file);
		}
	}
 
	/**
	* Set source file for processing
	*/
	function set_source ($img_file = "") {
		// Clean all values before processing
		$this->_clean_data();
		// Check image file
		if (empty($img_file) || !file_exists($img_file) || !is_readable($img_file)) {
			if (!$this->SILENT_MODE) {
				trigger_error("Wrong image file!", E_USER_WARNING);
			}
			return false;
		}
		$this->source_file = $img_file;
		// Check image data
		$this->_get_img_info();
		if (empty($this->source_width) || empty($this->source_height) || empty($this->source_type)) {
			if (!$this->SILENT_MODE) {
				trigger_error("Cant get correct image info!", E_USER_WARNING);
			}
			return false;
		} else {
			$this->_set_new_size_auto();
			// Create processing fnuction name according to image type
			$func_name = "imagecreatefrom".$this->source_type;
			$this->tmp_img = strlen($this->source_type) ? @$func_name ($this->source_file) : null;
			if (empty($this->tmp_img)) {
				return false;
			}
		}
		return true;
	}

	/**
	* Set output image type
	*/
	function set_output_type ($new_type = "") {
		// If image type is set by number (1,2,3)
		if (!empty($new_type) && is_numeric($new_type) && isset($this->_avail_types[$new_type])) {
			$output_type = $this->_avail_types[$new_type];
		// If type is set by string name (gif, jpeg, png)
		} elseif (!empty($new_type) && in_array($new_type, $this->_avail_types)) {
			$output_type = $new_type;
		// Send error message if type is unknown
		} else {
			if (!$this->SILENT_MODE) {
				trigger_error("Unknown new output image type \"".$new_type."\"", E_USER_WARNING);
			}
			return false;
		}
		return true;
	} 
 
	/**
	* Set new limits for processing image
	*/
	function set_limits ($limit_x = null, $limit_y = null) {
		if (is_numeric($limit_x) && is_numeric($limit_y) && $limit_x > 0 && $limit_y > 0) {
			$this->limit_x = $limit_x;
			$this->limit_y = $limit_y;
			return true;
		} else {
			return false;
		}
	} 
 
	/**
	* Save processed image into specified location
	*/
	function save ($output_file = "") {
		if (empty($this->tmp_img)) {
			if (!$this->SILENT_MODE) {
				trigger_error("No temporary image for resizing!", E_USER_WARNING);
			}
			return false;
		}
		$this->_set_new_size_auto();
		// Detect if need to resize image (if something has changed)
		if (
			$this->output_width != $this->source_width 
			|| $this->output_height != $this->source_height 
			|| $this->output_type != $this->source_type
			|| $this->force_process
		) {

			$this->tmp_resampled = @imagecreatetruecolor($this->output_width, $this->output_height);
			if (!$this->tmp_resampled) {
				return false;
			}
			@imagecopyresampled ($this->tmp_resampled, $this->tmp_img, 0, 0, 0, 0, $this->output_width, $this->output_height, $this->source_width, $this->source_height);
			// Create processing fnuction name according to image type
			$func_name = "image".$this->output_type;
			strlen($this->output_type) ? @$func_name ($this->tmp_resampled, $output_file, defined("THUMB_QUALITY") ? THUMB_QUALITY : 85) : null;
			// Destroy temporary image
			imagedestroy ($this->tmp_resampled);
		// If image file has another name - just copy it there (only if non-empty)
		} else {
			if ($this->source_file != $output_file) {
				copy($this->source_file, $output_file);
			}
		}
		return true;
	} 

	/**
	* Show image (resample on the fly) NOTE: Expensive for the server CPU usage
	*/
	function output_image () {
		if (empty($this->tmp_img)) {
			if (!$this->SILENT_MODE) {
				trigger_error("No temporary image for resizing!", E_USER_WARNING);
			}
			return false;
		}
		header("Content-Type: image/".$this->output_type);
		$this->_set_new_size_auto();
		// Detect if need to resize image (if something has changed)
		if ($this->output_width != $this->source_width || $this->output_height != $this->source_height || $this->output_type != $this->source_type) {
			$this->tmp_resampled = @imagecreatetruecolor($this->output_width, $this->output_height); 
			@imagecopyresampled ($this->tmp_resampled, $this->tmp_img, 0, 0, 0, 0, $this->output_width, $this->output_height, $this->source_width, $this->source_height);
		}
		// Create processing fnuction name according to image type
		$func_name = "image".$this->output_type;
		strlen($this->output_type) ? @$func_name ($this->tmp_resampled) : null;

		return true;
	}

	/**
	* Get image details and put them into class property
	*/
	function _get_img_info () {
		// Do not check missing files
		if (!file_exists($this->source_file) || !filesize($this->source_file)) {
			return false;
		}
		list($this->source_width, $this->source_height, $type, $this->source_atts) = @getimagesize($this->source_file);
		// Check if current type is supported
		return array_key_exists($type, $this->_avail_types) ? ($this->source_type = $this->_avail_types[$type]) : false;
	}

	/**
	* Set new size of the thumbnail automatically (preserve proportions)
	*/
	function _set_new_size_auto () {
		if (empty($this->source_width) || empty($this->source_height)) {
			if (!$this->SILENT_MODE) {
				trigger_error("Missing source image sizes!", E_USER_WARNING);
			}
			return false;
		}
		// Try to find resize coef
		$k1 = $this->source_width / $this->limit_x;
		$k2 = $this->source_height / $this->limit_y;
		// Get max value from two numbers
		$k = $k1 >= $k2 ? $k1 : $k2;
		// Decide if we need to just reduce or fit the limits
		if ($this->reduce_only && $k1 <= 1 && $k2 <= 1) {
			$this->output_width		= $this->source_width;
			$this->output_height	= $this->source_height;
		} else {
			// Calculate output sizes
			$this->output_width		= round($this->source_width / $k, 0);
			$this->output_height	= round($this->source_height / $k, 0);
		}
		return true;
	}
 
	/**
	* Clean all data
	*/
	function _clean_data () {
		$this->source_file		= null;
		$this->source_type		= null;
		$this->source_width		= null;
		$this->source_height	= null;
		$this->source_atts		= null;
		$this->limit_x			= 100;
		$this->limit_y			= 100;
		$this->output_width		= null;
		$this->output_height	= null;
		$this->output_type		= "jpeg";
		$this->tmp_img			= null;
		$this->tmp_resampled	= null;
		return true;
	}
} 
