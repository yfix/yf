<?php

/**
*/
class yf_dynamic_image {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function __construct() {
		$this->_parent = module('dynamic');
	}
	
	/**
	* Display image with error text inside
	*/
	function _show_error_image () {
		@header('Content-Type: image/gif', $force = true);
		print base64_decode('R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
		exit();
	}

	/**
	* Display 'dynamic' image (block hotlinking)
	*/
	function image () {
		no_graphics(true);
		if (empty($_SERVER['HTTP_REFERER']) || !defined('WEB_PATH') || substr($_SERVER['HTTP_REFERER'], 0, strlen(WEB_PATH)) !== WEB_PATH) {
			return $this->_show_error_image();
		}
		$img = trim($_GET['id'], '/');
		$path = PROJECT_PATH. $img;
		if (!strlen($img) || false !== strpos($img, '..')) {
			return $this->_show_error_image();
		}
		if (!file_exists($path) || !filesize($path)) {
			return $this->_show_error_image();
		}
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$allowed_exts = [
			'jpg'	=> 'image/jpeg',
			'jpeg'	=> 'image/jpeg',
			'gif'	=> 'image/gif',
			'png'	=> 'image/png',
		];
		if (!$ext || !isset($allowed_exts[$ext])) {
			return $this->_show_error_image();
		}
		@header('Content-Type: '.$allowed_exts[$ext], $force = true);
		readfile($path);
		exit();
	}

	/**
	*/
	function captcha_image() {
		return _class('captcha')->show_image();
	}

	/**
	* Output sample placeholder image, useful for designing wireframes and prototypes
	*/
	function placeholder() {
		no_graphics(true);

		list($id, $ext) = explode('.', $_GET['id']);
		list($w, $h) = explode('x', $id);
		$w = (int)$w ?: 100;
		$h = (int)$h ?: 100;
		$params['color_bg'] = $_GET['page'] ? preg_replace('[^a-z0-9]', '', $_GET['page']) : '';

		require_once YF_PATH.'share/functions/yf_placeholder_img.php';
		echo yf_placeholder_img($w, $h, $params);

		exit;
	}

	/**
	* Helper to output placeholder image, by default output is data/image
	*/
	function placeholder_img($extra = []) {
		if (!is_array($extra)) {
			$extra = [];
		}
		$w = (int)$extra['width'];
		$h = (int)$extra['height'];
		if ($extra['as_url']) {
			$extra['src'] = url('/dynamic/placeholder/'.$w.'x'.$h);
		} else {
			require_once YF_PATH.'share/functions/yf_placeholder_img.php';
			$img_data = yf_placeholder_img($w, $h, ['no_out' => true] + (array)$extra);
			$extra['src'] = 'data:image/png;base64,'.base64_encode($img_data);
		}
		return '<img'._attrs($extra, ['src', 'type', 'class', 'id']).' />';
	}
}
