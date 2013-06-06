<?php

/**
* Layout settings manager
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_layout_settings {

	/** @var string */
	var $_cookie_name = "layout";
	/** @var string */
	var $COOKIE_PATH	= "/";
	/** @var int */
	var $COOKIE_TTL		= 31536000; // 86400 * 365
	/** @var int */
	var $MIN_FSIZE		= 75;	//%
	/** @var int */
	var $MAX_FSIZE		= 150; 	//%
	/** @var int */
	var $STEP_FSIZE		= 5; 	//%
	/** @var int */
	var $MIN_WIDTH		= 700;	//px
	/** @var int */
	var $MAX_WIDTH		= 1600;	//px
	/** @var int */
	var $STEP_WIDTH		= 50; 	//px

	/**
	* YF module constructor
	*/
	function _init () {
		// Available font sizes
		$A = $this->MIN_FSIZE;
		while ($A <= $this->MAX_FSIZE) {
			$this->_font_sizes[$A] = $A."%";
			$A += $this->STEP_FSIZE;
		}
		// Available page width
		$A = $this->MIN_WIDTH;
		while ($A <= $this->MAX_WIDTH) {
			$this->_layout_width[$A] = $A."px";
			$A += $this->STEP_WIDTH;
		}

		$_prefix = "color_theme_";
		$DIR_OBJ = main()->init_class("dir", "classes/");
		$path_to_color_themes = INCLUDE_PATH. tpl()->TPL_PATH;
		// Get available color themes
		foreach ((array)$DIR_OBJ->scan_dir($path_to_color_themes, 1, array("", "/".$_prefix.".*\.css\$/i"), "/(svn|git)/i") as $_path) {
			// Skip cached styles
			if (false !== strpos($_path, "__cached")) {
				continue;
			}
			// Skip subdirs
			if (preg_match("#[\\\/]+#i", substr($_path, strlen($path_to_color_themes)))) {
				continue;
			}
			$_cur_name = substr($_path, strlen($path_to_color_themes) + strlen($_prefix), -strlen(".css"));
			$this->_css_themes[$_cur_name] = ucwords(str_replace("_", " ", $_cur_name));
		}
		// If no custom themes in project - then use ones from framework
		if (empty($this->_css_themes)) {
			$path_to_color_themes = PF_PATH. "templates/user/";
			// Get available color themes
			foreach ((array)$DIR_OBJ->scan_dir($path_to_color_themes, 1, array("", "/".$_prefix.".*\.css\$/i"), "/(svn|git)/i") as $_path) {
				// Skip cached styles
				if (false !== strpos($_path, "__cached")) {
					continue;
				}
				// Skip subdirs
				if (preg_match("#[\\\/]+#i", substr($_path, strlen($path_to_color_themes)))) {
					continue;
				}
				$_cur_name = substr($_path, strlen($path_to_color_themes) + strlen($_prefix), -strlen(".css"));
				$this->_css_themes[$_cur_name] = _ucwords(str_replace("_", " ", $_cur_name));
			}
		}
		// Current selected color theme
		$this->_selected_theme 	= $_COOKIE[$this->_cookie_name]["color_theme"];
		// Current selected font size
		$this->_selected_size 	= $_COOKIE[$this->_cookie_name]["font_size"];
		// Current selected page width
		$this->_selected_width 	= $_COOKIE[$this->_cookie_name]["max_page_width"];
		if ($this->_selected_width % 50) {
			$this->_selected_width = round($_COOKIE[$this->_cookie_name]["max_page_width"], -2);
		}
	}

	/**
	* Default method
	*/
	function show () {
		if (!empty($_POST)) {
			// Save settings
			setcookie($this->_cookie_name."[color_theme]", 		$_POST["color_theme"], 		time() + $this->COOKIE_TTL, $this->COOKIE_PATH);
			setcookie($this->_cookie_name."[font_size]", 		$_POST["font_size"], 		time() + $this->COOKIE_TTL, $this->COOKIE_PATH);
			setcookie($this->_cookie_name."[max_page_width]", 	$_POST["max_page_width"],	time() + $this->COOKIE_TTL, $this->COOKIE_PATH);
			// Go back
			return js_redirect("./?object=".$_GET["object"]);
		}
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"],
			"reset_link"		=> "./?object=".$_GET["object"]."&action=reset",
			"themes_box" 		=> common()->select_box("color_theme", 	$this->_css_themes, 	$this->_selected_theme, 1, 0, "", false),
			"font_size_box"		=> common()->select_box("font_size", 		$this->_font_sizes, 	$this->_selected_size, 	1, 0, "", false),
			"max_page_width_box"=> common()->select_box("max_page_width",	$this->_layout_width, 	$this->_selected_width, 1, 0, "", false),
			"dynamic_css_link"	=> process_url("./?object=dynamic&action=css&id="),
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Reset to defaults
	*/
	function reset () {
		// Delete cookies
		setcookie($this->_cookie_name."[color_theme]", "", time() + $this->COOKIE_TTL, $this->COOKIE_PATH);
		setcookie($this->_cookie_name."[font_size]", "", time() + $this->COOKIE_TTL, $this->COOKIE_PATH);
		setcookie($this->_cookie_name."[max_page_width]", "", time() + $this->COOKIE_TTL, $this->COOKIE_PATH);
		setcookie($this->_cookie_name, false, time() + $this->COOKIE_TTL, $this->COOKIE_PATH);
		// Go back
		return js_redirect("./?object=".$_GET["object"]);
	}
}
