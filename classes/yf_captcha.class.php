<?php

/**
* Class to handle CAPTCHA images (to prevent auto-registering, flooding etc)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_captcha {

	/** @var string Secret key (will be added to hash) */
	public $secret_key		= '';
	/** @var bool Use cookies or session vars */
	public $use_cookies		= false;
	/** @var string Cookie var name */
	public $var_name		= 'image_hash';
	/** @var int Cookie time-to-live (in seconds) */
	public $cookie_ttl		= 86400; // @var 24 * 3600 == 1 day
	/** @var string Path to the True Type Font to use (could be array) */
	public $ttf_font_path	= '';
	/** @var int Result image width (in pixels) */
	public $image_width		= 110;
	/** @var int Result image height (in pixels) */
	public $image_height	= 30;
	/** @var array Allowed symbols to use in randomizer */
	var	$symbols_array	= array();
	/** @var int Number of symbols to generate */
	var	$num_symbols	= 5;
	/** @var int Middle value (will be bounced randomly with +2 and -2) */
	var	$font_height	= 16;
	/** @var int Number of random rectangles to add */
	var	$add_rects		= 15;
	/** @var int Number of random lines to add */
	var	$add_lines		= 15;
	/** @var int Number of random ellipses to add */
	var	$add_ellipses	= 10;
	/** @var int Number of random pixels to add */
	var	$add_pixels		= 500;
	/** @var int @conf_skip Image background color */
	public $bg_color		= 0x00ffffff; // @var 0x AA RR GG BB (alpha, red, green, blue)
	/** @var array @conf_skip Colors arrays */
	public $text_colors	= array(
		0x162A7C8F,
		0x1628508C,
		0x16A12F9B,
		0x1619621D,
		0x16622A19,
	);
	/** @var array @conf_skip */
	public $rect_colors	= array(
		0x702A7C8F,
		0x7028508C,
		0x70A12F9B,
		0x7019621D,
		0x70622A19,
	);
	/** @var array @conf_skip */
	public $line_colors	= array(
		0x202A7C8F,
		0x2028508C,
	);
	/** @var array @conf_skip */
	public $ellipse_colors	= array(
		0x702A7C8F,
		0x7028508C,
		0x70A12F9B,
		0x7019621D,
		0x70622A19,
	);
	/** @var array @conf_skip */
	public $pixel_colors	= array(
		0x202A7C8F,
		0x2028508C,
	);
	/** @var CAPTCHA enabled */
	public $ENABLED = true;

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Framework constructor
	*/
	function _init () {
		$this->_is_enabled_hook();
		if (!$this->ENABLED) {
			return false;
		}
		$this->set_secret_key();
		$lib_path	= PROJECT_PATH. 'fonts/';
		$fwork_path	= YF_PATH. 'share/captcha_fonts/';
		$path = file_exists($lib_path) ? $lib_path : $fwork_path;
		$this->set_font_path(array($path.'pioneer.ttf', $path.'banco.ttf', $path.'glast.ttf'));
		$this->set_symbols_array(1);
	}

	/**
	* Set secret key
	*/
	function set_secret_key($input = '') {
		if (empty($input)) {
			$this->secret_key = substr(md5(REAL_PATH), 8, -8);
		} else {
			$this->secret_key = $input;
		}
	}

	/**
	* Set font path ($input could be array or string)
	*/
	function set_font_path($input = '') {
		if (!empty($input)) {
			$this->ttf_font_path = $input;
		}
	}

	/**
	* Set symbols array contetns
	*/
	function set_symbols_array($input = array()) {
		if (empty($input)) {
			return false;
		}
		// Try to assign predefined arrays
		if (is_numeric($input)) {
			if ($input == 1)	 $this->symbols_array = range(0, 9);
			elseif ($input == 2) $this->symbols_array = array_flip(range('A', 'Z'));
			elseif ($input == 3) $this->symbols_array = array_merge(array_flip(range(0, 9)), array_flip(range('a', 'z')));
		// Try to set custom array
		} elseif (is_array($input)) {
			$this->symbols_array = $input;
		}
	}

	/**
	* Set colors
	*/
	function set_colors($name = '', $input = '') {
		if (!empty($input) && in_array('text','rect','line','ellipse','pixel')) {
			$this->{$name.'_colors'} = $input;
		}
	}

	/**
	* Set image size
	*/
	function set_image_size($width = '', $height = '') {
		if (!empty($width) && !empty($height)) {
			$this->image_width	= $width;
			$this->image_height	= $height;
		}
	}

	/**
	* Set new var name to use in session or in cookie
	*/
	function set_var_name($new_name = '') {
		if (!empty($new_name)) {
			$this->var_name = $new_name;
		}
	}

	/**
	* Show HTML code for the CAPTCHA image
	*/
	function show_html($location = '', $add_style = ' border="1" ') {
		if (!$this->ENABLED) {
			return false;
		}
		if (empty($location)) {
			$location = process_url('./?object='.__CLASS__.'&action=show_image');
		}
		return '<img src="'.$location.'" '.$add_style.' />';
	}

	/**
	* Show HTML block for the CAPTCHA image (complete, with input and it's validation)
	*/
	function show_block($location = '', $stpl_name = '') {
		if (!$this->ENABLED) {
			return false;
		}
		if (empty($location)) {
			$location = './?object='.$_GET['object'].'&action=show_image';
		}
		$uid = '__captcha_id__';
		if (false === strpos($location, $uid)) {
			$location .= '&id='.$uid;
		}
		if (empty($stpl_name)) {
			$stpl_name = 'system/captcha_block';
		}
		$replace = array(
			'img_src'		=> process_url($location),
			'num_symbols'	=> intval($this->num_symbols),
		);
		return tpl()->parse($stpl_name, $replace);
	}

	/**
	* Show image with text
	*/
	function check($field_in_form = 'image_numbers') {
		if (!$this->ENABLED) {
			return true;
		}
		$VALID_CODE = false;

		if (empty($_POST[$field_in_form])) {
			_re('Please enter code');
		} else {
			$hash = md5($this->secret_key. $_POST[$field_in_form]);
			if ($this->use_cookies) {
				if ($hash != $_COOKIE[$this->var_name]) {
					$code_incorrect = true;
				}
			} else {
				if ($hash != $_SESSION[$this->var_name]) {
					$code_incorrect = true;
				}
			}
			if ($code_incorrect) {
				_re('Code you entered is incorrect');
			} else {
				$VALID_CODE = true;
			}
		}
		if ($this->use_cookies) {
			setcookie($this->var_name, '', time());
		} else {
			unset($_SESSION[$this->var_name]);
		}
		return $VALID_CODE;
	}

	/**
	* Show image with text
	*/
	function show_image() {
		if (function_exists('main')) {
			main()->NO_GRAPHICS = true;
		}
		if (!$this->ENABLED) {
			return false;
		}
		// Create image
		$image = imagecreatetruecolor($this->image_width, $this->image_height);
		// Calculate average width for the one font symbol
		$font_width = $this->image_width / $this->num_symbols;
		// Set font path
		$ttf_font = is_array($this->ttf_font_path) ? array_rand(array_flip($this->ttf_font_path)) : $this->ttf_font_path;
		// Set image background color
		imagefilledrectangle($image, 0, 0, $this->image_width, $this->image_height, $this->bg_color);
		// Draw text
		for ($i = 0; $i < $this->num_symbols; $i++) {
			$random_string .= array_rand($this->symbols_array);
			imagettftext($image, round(rand($this->font_height - 2, $this->font_height + 2), 0), rand(-30, 30), $i * $font_width + 5 + rand(-2, 2), $this->image_height / 2 + 5 + rand(-4, 4), array_rand(array_flip($this->text_colors)), $ttf_font, $random_string[$i]);
		}
		// Draw random rectangles
		for ($i = 0; $i < $this->add_rects; $i++) {
			imagefilledrectangle($image, rand(-$this->image_width, $this->image_width), rand(-$this->image_height, $this->image_height), rand(-$this->image_width, $this->image_width), rand(-$this->image_height, $this->image_height), array_rand(array_flip($this->rect_colors)));
		}
		// Draw random lines
		for ($i = 0; $i < $this->add_lines; $i++) {
			imageline($image, rand(-$this->image_width, $this->image_width), rand(-$this->image_height, $this->image_height), rand(-$this->image_width, $this->image_width), rand(-$this->image_height, $this->image_height), array_rand(array_flip($this->line_colors)));
		}
		// Draw random ellipses
		for ($i = 0; $i < $this->add_ellipses; $i++) {
			imagefilledellipse($image, rand(-$this->image_height, $this->image_width), rand(-$this->image_height, $this->image_height), rand(20, $this->image_width), rand(10, $this->image_width), array_rand(array_flip($this->ellipse_colors)));
		}
		// Draw random pixels
		for ($i = 0; $i < $this->add_pixels; $i++) {
			imagesetpixel($image, rand(0, $this->image_width), rand(0, $this->image_height), array_rand(array_flip($this->pixel_colors)));
		}
		// Calculate hash
		$hash = md5($this->secret_key. $random_string);
		// Store secure data
		if ($this->use_cookies)	{
			setcookie($this->var_name, $hash, time() + $this->cookie_ttl);
		} else {
			$_SESSION[$this->var_name] = $hash;
		}
		// Throw image to the user
		header('Content-type: image/png');
		imagepng($image);
		// Cleanup
		imagedestroy($image);
	}

	/**
	* Allows you to override $this->ENABLED option for some cases
	*/
	function _is_enabled_hook() {
		//$this->ENABLED = false;
	}
}
