<?php

load('assets', 'framework', 'classes/');
class yf_core_js extends yf_assets {

// TODO: auto-caching into web-accessible dir with locking (to avoid duplicate cache entry attempts)

	public $content = array();
	/** @array List of pre-defined assets. See share/assets.php */
	public $assets = array();

	/**
	*/
	public function _init() {
		$all_assets = require YF_PATH.'share/assets.php';
		$this->assets = $all_assets['js'];
		// Main JS from theme stpl
		$main_script_js = trim(tpl()->parse_if_exists('script_js'));
		// single string = automatically generated by compass
		if (strpos($main_script_js, "\n") === false && strlen($main_script_js) && preg_match('~^js/script.js\?[0-9]{10}$~ims', $main_script_js)) {
			$this->add_url(WEB_PATH. tpl()->TPL_PATH. $main_script_js);
		} else {
			$this->add_raw($main_script_js);
		}
	}

	/**
	* Main method to display overall JS. Can be called from main
	* like this: {execute_shutdown(core_js,show)} or {execute_shutdown(graphics,show_js)}
	*/
	public function show($params = array()) {
		// JS from current module
		$module_js_path = $this->_find_module_js($_GET['object']);
		if ($module_js_path) {
			$this->add_file($module_js_path);
		}
		if ($params['packed']) {
			$packed = $this->_show_packed_content($params);
			// Degrade gracefully
			if (strlen($packed)) {
				return $packed;
			}
		}
		$prepend = _class('core_events')->fire('show_js.prepend');
		$out = array();
		// Process previously added content, depending on its type
		foreach ((array)$this->content as $md5 => $v) {
			$type = $v['type'];
			$text = $v['text'];
			$_params = (array)$v['params'] + (array)$params;
			$css_class = $_params['class'] ? ' class="'.$_params['class'].'"' : '';
			if ($type == 'url') {
				if ($params['min'] && !DEBUG_MODE && strpos($text, '.min.') === false) {
					$text = substr($text, 0, -strlen('.js')).'.min.js';
				}
// TODO: add optional _prepare_html() for $url
				$out[$md5] = '<script src="'.$text.'" type="text/javascript"'.$css_class.'></script>';
			} elseif ($type == 'file') {
				$out[$md5] = '<script type="text/javascript"'.$css_class.'>'. PHP_EOL. file_get_contents($text). PHP_EOL. '</script>';
			} elseif ($type == 'inline') {
				$text = $this->_strip_script_tags($text);
				$out[$md5] = '<script type="text/javascript"'.$css_class.'>'. PHP_EOL. $text. PHP_EOL. '</script>';
			} elseif ($type == 'raw') {
				$out[$md5] = $text;
			}
		}
		$append = _class('core_events')->fire('show_js.append', array('out' => &$out));
		$this->content = array();
		return implode(PHP_EOL, $prepend). implode(PHP_EOL, $out). implode(PHP_EOL, $append);
	}

	/**
	*/
	public function _show_packed_content($params = array()) {
		$packed_file = $this->_pack_content($params);
		if (!$packed_file || !file_exists($packed_file)) {
			return false;
		}
		$css_class = $params['class'] ? ' class="'.$params['class'].'"' : '';
		return '<script type="text/javascript" src="'.$packed_file.'"'.$css_class.'></script>';
	}

	/**
	*/
	public function _pack_content($params = array()) {
// TODO
		$packed_file = INCLUDE_PATH. 'uploads/js/packed_'.md5($_SERVER['HTTP_HOST']).'.js';
		if (file_exists($packed_file) && filemtime($packed_file) > (time() - 3600)) {
			return $packed_file;
		}
		$packed_dir = dirname($packed_file);
		if (!file_exists($packed_dir)) {
			mkdir($packed_dir, 0755, true);
		}
		_class('core_errors')->fire('js.before_pack', array(
			'fiie'		=> $packed_file,
			'content'	=> $this->content,
			'params'	=> $params,
		));
		$out = array();
		foreach ((array)$this->content as $md5 => $v) {
			$type = $v['type'];
			$text = $v['text'];
			if ($type == 'url') {
				$out[$md5] = file_get_contents($text);
			} elseif ($type == 'file') {
				$out[$md5] = file_get_contents($text);
			} elseif ($type == 'inline') {
				$text = $this->_strip_script_tags($text);
				$out[$md5] = $text;
			} elseif ($type == 'raw') {
				$out[$md5] = $text;
			}
		}
// TODO: in DEBUG_MODE add comments into generated file and change its name to not overlap with production one
		file_put_contents($packed_file, implode(PHP_EOL, $out));

		_class('core_errors')->fire('js.after_pack', array(
			'fiie'		=> $packed_file,
			'content'	=> $out,
			'params'	=> $params,
		));
		return $packed_file;
	}

	/**
	* Alias
	*/
	public function show_js($params = array()) {
		return $this->show($params);
	}

	/**
	* Special code for jquery on document ready
	*/
	function jquery($content, $params = array()) {
		if (!$this->_jquery_requried) {
			$this->add_asset('jquery');
			$this->_jquery_requried = true;
		}
		return $this->add('$(function(){'.PHP_EOL. $content. PHP_EOL.'})', 'inline', $params);
	}

	/**
	* Search for JS for current module in several places, where it can be stored.
	*/
	public function _find_module_js($module = '') {
		if (!$module) {
			$module = $_GET['object'];
		}
		$js_path = $module.'/'.$module.'.js';
		$paths = array(
			MAIN_TYPE_ADMIN ? YF_PATH. 'templates/admin/'.$js_path : '',
			YF_PATH. 'templates/user/'.$js_path,
			MAIN_TYPE_ADMIN ? YF_PATH. 'plugins/'.$module.'/templates/admin/'.$js_path : '',
			YF_PATH. 'plugins/'.$module.'/templates/user/'.$js_path,
			MAIN_TYPE_ADMIN ? PROJECT_PATH. 'templates/admin/'.$js_path : '',
			PROJECT_PATH. 'templates/user/'.$js_path,
			SITE_PATH != PROJECT_PATH ? SITE_PATH. 'templates/user/'.$js_path : '',
		);
		$found = '';
		foreach (array_reverse($paths, true) as $path) {
			if (!strlen($path)) {
				continue;
			}
			if (file_exists($path)) {
				$found = $path;
				break;
			}
		}
		return $found;
	}

	/**
	*/
	public function _detect_content($content = '') {
		$content = trim($content);
		$type = false;
		if (isset($this->assets[$content])) {
			$type = 'asset';
		} elseif (preg_match('~^(http://|https://|//)[a-z0-9]+~ims', $content)) {
			$type = 'url';
		} elseif (preg_match('~^/[a-z0-9\./_-]+\.js$~ims', $content) && file_exists($content)) {
			$type = 'file';
		} elseif (preg_match('~^(<script|[$;#\*/])~ims', $content) || strpos($content, PHP_EOL) !== false) {
			$type = 'inline';
		}
		return $type;
	}

	/**
	*/
	public function _strip_script_tags ($text) {
		for ($i = 0; $i < 10; $i++) {
			if (strpos($text, 'script') === false) {
				break;
			}
			$text = preg_replace('~^<script[^>]*?>~ims', '', $text);
			$text = preg_replace('~</script>$~ims', '', $text);
		}
		return $text;
	}
}
