<?php

/**
* Core CSS methods
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_graphics {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Show CSS
	*/
	function show_css () {
		$body = '';
		// Reset style
		$_reset_css = '';
		if (_class('graphics')->CSS_ADD_RESET) {
			$_reset_css = $this->_load_css_file('reset');
			$body .= $this->_show_css_file($_reset_css, 'reset');
		}
		// Base CSS style (positioning of main elements only)
		$_base_css = $this->_load_css_file('base');
		$body .= $this->_show_css_file($_base_css, 'base');
		// Main style
		$_main_css = $this->_load_css_file('style');
		$body .= $this->_show_css_file($_main_css, 'style');
		// Custom module style (eg. place: 'forum/forum.css')
		$_user_modules = main()->get_data('user_modules');
		if ($_user_modules && is_array($_user_modules)) {
			foreach ((array)main()->modules as $_name => $_obj) {
				if ($_name == $_GET['object'] || !isset($_user_modules[$_name])) {
					continue;
				}
				$_module_css = $this->_load_css_file($_name.'/'.$_name);
				$body .= $this->_show_css_file($_module_css, $_name);
			}
		}
		$_user_custom_css = '';
		$_user_custom_css_ie = '';
		$_color_theme_css = '';
		$_ie_fixes_css = '';
		// Current module CSS
		$_module_css = $this->_load_css_file($_GET['object'].'/'.$_GET['object']);
		$body .= $this->_show_css_file($_module_css, $_GET['object']);
		// Custom user layout
		if (MAIN_TYPE_USER) {
			$user_layout = isset($_COOKIE['layout']) ? $_COOKIE['layout'] : '';
			// Override with default color theme (if exists one)
			if (!isset($user_layout['color_theme']) && defined('DEFAULT_COLOR_THEME')) {
				$user_layout['color_theme'] = DEFAULT_COLOR_THEME;
			}
			// Use selected color theme
			if (isset($user_layout['color_theme'])) {
				$_color_theme_label = 'color_theme_'.$user_layout['color_theme'];
				$_color_theme_css = $this->_load_css_file($_color_theme_label);
				if ($_color_theme_css) {
					conf('color_theme', $user_layout['color_theme']);
				}
			}
			// If nothing custom found - then try to use default one
			if (empty($_color_theme_css)) {
				$_color_theme_label = 'color_theme_default';
				$_color_theme_css = $this->_load_css_file($_color_theme_label);
			}
			$body .= $this->_show_css_file($_color_theme_css, $_color_theme_label);
			// Custom font size
			if (isset($user_layout['font_size'])) {
				$_user_custom_css		.= 'html{font-size:'.$user_layout['font_size'].'%;}';
				$_user_custom_css_ie	.= "\n".'html{font-size:'.$user_layout['font_size'].'%;}';
			}
			// Custom page width
			if (isset($user_layout['max_page_width'])) {
				$_user_custom_css		.= 'body{max-width:'.$user_layout['max_page_width'].'px;}';
				$_user_custom_css_ie	.= "\n".'body{width:expression((documentElement.offsetWidth || document.body.offsetWidth) > '.$user_layout['max_page_width'].' ? "'.$user_layout['max_page_width'].'px" : "auto");}';
			}
			$body .= $this->_show_css_file($_user_custom_css, 'user_custom');
		}
		// Custom module color style (eg. place: 'forum/color_theme_black.css')
		if (MAIN_TYPE_USER && $_module_css && $_color_theme_label) {
			$_color_module_css = $this->_load_css_file($_GET['object'].'/'.$_color_theme_label);
			$body .= $this->_show_css_file($_color_module_css, $_GET['object'].'_'.$_color_theme_label);
		}
		// Load IE specific code
		if (_class('graphics')->CSS_FIXES_FOR_IE || MAIN_TYPE_ADMIN) {
			if (!$_ie_fixes_css) {
				$_ie_fixes_css	= $this->_load_css_file('ie_only');
			}
		}
		if ($_ie_fixes_css || $_user_custom_css_ie) {
			$body .= $this->_show_css_file($_ie_fixes_css. $_user_custom_css_ie, 'ie_only', 'ie');
		}
		$iepngfix_url = $this->_load_css_file('iepngfix.htc');
		$body .= $iepngfix_url && strlen($iepngfix_url) < 256 ? '<!--[if lt IE 7]><style type="text/css">img{behavior: url("'.$iepngfix_url.'");}</style><![endif]-->' : '';
		return $body;
	}

	/**
	* Try to load CSS file with inheritance
	*/
	function _show_css_file ($css = '', $keyword = '', $option = '') {
		if (!$css) {
			return false;
		}
		// Try to use 'link' tag if applicable
		if (_class('graphics')->CSS_USE_LINK_TAG && _class('graphics')->_css_loaded_from[$keyword] == 'project' && !_class('graphics')->EMBED_CSS) {
			$body = '<link rel="stylesheet" type="text/css" href="'._prepare_html(substr($css, strlen('@import url('), -strlen('");'))).'" />';
		} else {
			$body = '<style type="text/css">'.($keyword ? '/*'.$keyword.'*/' : '')."\n".$css."\n".'</style>';
		}
		if ($option == 'ie') {
			$body = '<!--[if lt IE 8]>'.$body.'<![endif]-->';
		}
		$body = "\n".$body."\n";
		return $body;
	}

	/**
	* Try to load CSS file with inheritance
	*/
	function _load_css_file ($name = '') {
		$CACHE_CSS = _class('graphics')->CACHE_CSS;
		$EMBED_CSS = _class('graphics')->EMBED_CSS;

		$css_name	= $name.'.css';
		$_name_for_cache = str_replace(array('.css', '/'), array('', '__'), $name). '__cached.css';
		if ($name == 'iepngfix.htc') {
			$css_name = $name;
			$_name_for_cache = 'iepngfix__cached.htc';
			$CACHE_CSS = true; // Force not embedding .htc file
			$EMBED_CSS = false;
		}
		$TPL_PATH	= tpl()->TPL_PATH;
		$FS_PATH	= PROJECT_PATH. $TPL_PATH. $css_name;

		$_exists_in_proj = file_exists($FS_PATH);
		// Try inherited skin
		if (!$_exists_in_proj && conf('INHERIT_SKIN')) {
			$TPL_PATH = 'templates/'. conf('INHERIT_SKIN'). '/';
			$FS_PATH = PROJECT_PATH. $TPL_PATH. $css_name;
			$_exists_in_proj = file_exists($FS_PATH);
		}
		if (!$_exists_in_proj && conf('INHERIT_SKIN2')) {
			$TPL_PATH = 'templates/'. conf('INHERIT_SKIN2'). '/';
			$FS_PATH = PROJECT_PATH. $TPL_PATH. $css_name;
			$_exists_in_proj = file_exists($FS_PATH);
		}
		// Use cached file (only if no such file found in project)
		if ($CACHE_CSS && !$EMBED_CSS && !$_exists_in_proj) {
			// Try to use cached file
			$CACHED_CSS = PROJECT_PATH. $TPL_PATH. $_name_for_cache;
			$_cache_refresh = false;
			if (!file_exists($CACHED_CSS)) {
				$_cache_refresh = true;
			} elseif (filemtime($CACHED_CSS) < (time() - _class('graphics')->CACHE_CSS_TTL)) {
				$_cache_refresh = true;
			}
			$FS_PATH = YF_PATH. 'templates/'.MAIN_TYPE.'/'. $css_name;
			if (file_exists($FS_PATH) && $_cache_refresh) {
				_mkdir_m(dirname($CACHED_CSS));
				if (is_writable($CACHED_CSS)) {
					file_put_contents($CACHED_CSS, file_get_contents($FS_PATH));
				}
			}
			if (file_exists($CACHED_CSS)) {
				_class('graphics')->_css_loaded_from[$name] = 'cache';
				$web_path = _class('graphics')->MEDIA_PATH. $TPL_PATH. $_name_for_cache;
				if ($name == 'iepngfix.htc') {
					return $web_path;
				} else {
					return '@import url("'.$web_path.'");'."\n";
				}
			}
		}
		// Common way
		$FS_PATH = PROJECT_PATH. $TPL_PATH. $css_name;
		if ($_exists_in_proj) {
			_class('graphics')->_css_loaded_from[$name] = 'project';
			// Force embedding CSS
			if ($EMBED_CSS) {
				return file_get_contents($FS_PATH);
			} else {
				$web_path = _class('graphics')->MEDIA_PATH. $TPL_PATH. $css_name;
				if ($name == 'iepngfix.htc') {
					return $web_path;
				} else {
					return '@import url("'.$web_path.'");';
				}
			}
		}
		// Check if main CSS exists in project
		// If true - stop trying to load other CSS files from framework
		if (isset(_class('graphics')->_css_loaded_from['style']) && _class('graphics')->_css_loaded_from['style'] == 'project' && !$EMBED_CSS) {
			return false;
		}
		// Try to load from admin section
		if (MAIN_TYPE_ADMIN) {
			$FS_PATH = YF_PATH. 'templates/admin/'. $css_name;
			if (file_exists($FS_PATH)) {
				_class('graphics')->_css_loaded_from[$name] = 'framework_admin';
				return file_get_contents($FS_PATH);
			}
		}
		// Try framework user section
		$FS_PATH = YF_PATH. 'templates/user/'. $css_name;
		if (file_exists($FS_PATH)) {
			_class('graphics')->_css_loaded_from[$name] = 'framework_user';
			return file_get_contents($FS_PATH);
		}
		// Nothing found
		return false;
	}

}

