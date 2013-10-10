<?php

/**
* Core JS methods
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
	* Common javascript loader
	*/
	function show_javascript () {
		if (conf('no_js')) {
			return false;
		}
		$replace = array(
			'js_console_allow'	=> intval((bool)_class('graphics')->JS_CONSOLE_ALLOW),
		);
		$body = tpl()->parse('system/main_js', $replace);

		$ie6_js_path = tpl()->TPL_PATH. 'css/ie6.js';
		if (file_exists(PROJECT_PATH. $ie6_js_path)) {
			$body .= "\n".'<!--[if IE 6]><script type="text/javascript" src="'._class('graphics')->MEDIA_PATH. $ie6_js_path.'"></script><![endif]-->'."\n";
		}
		// Connect Firbug Lite
		if (DEBUG_MODE && _class('graphics')->USE_FIREBUG_LITE) {
			$body .= "\n".'<script type="text/javascript" src="'._class('graphics')->MEDIA_PATH.'js/firebug-lite-compressed.js"></script>'."\n";
		}
		if (_class('graphics')->CACHE_JAVASCRIPT) {
			$cache_file_name = 'site.js';
			// Try to use cached file
			$CACHED_JS		= 'js/__cache/'.
				str_replace(array('templates/', '/'), array('', '_'), tpl()->TPL_PATH).
				preg_replace('#[^0-9a-z]#', '', $_SERVER['HTTP_HOST']).
				$cache_file_name;
			$CACHED_FS_JS	= PROJECT_PATH. $CACHED_JS;
			$CACHED_WEB_JS	= _class('graphics')->MEDIA_PATH. $CACHED_JS;
			$_cache_refresh = false;
			if (!file_exists($CACHED_FS_JS)) {
				$_cache_refresh = true;
			} elseif (filemtime($CACHED_FS_JS) < (time() - _class('graphics')->CACHE_JS_TTL)) {
				$_cache_refresh = true;
			}
			$urls = array();

			$p = "#<script [^>]*src=[\"']{1}?(.*?)[\"']{1}?[^>]*>[^<]*?</script>#ims";
			if (preg_match_all($p, $body, $m)) {
				$web_path_len = strlen(_class('graphics')->MEDIA_PATH);
				foreach((array)$m[1] as $id => $_src) {
					if (false === strpos($m[0][$id], 'yf:cacheable')) {
						unset($m[0][$id]);
						continue;
					}
					$_src = trim($_src);
					// Check for the current domain
					if (substr($_src, 0, $web_path_len) != _class('graphics')->MEDIA_PATH) {
				//		continue;
					}
					$urls[$_src] = $_src;
				}
				$to_replace = $m[0];
			}
			if ($urls) {
				if ($_cache_refresh) {
					foreach ((array)common()->multi_request($urls) as $_src => $text) {
						$new_contents .= "\n/** source: ".$_src." */\n".$text;
					}
					if (!empty($new_contents)) {
						$new_contents = "/** cached time: ".date("YmdHis")." */\n".$new_contents;
						if (!file_exists(dirname($CACHED_FS_JS))) {
							_mkdir_m(dirname($CACHED_FS_JS));
						}
						file_put_contents($CACHED_FS_JS, $new_contents);
					}
				}
				if (file_exists($CACHED_FS_JS)) {
					$body = trim(str_replace($to_replace, "", $body));
					$body = '<script type="text/javascript" src="'.$CACHED_WEB_JS.'"></script>'."\n". $body;
				}
			}
		}
		return $body;
	}
}

