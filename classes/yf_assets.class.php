<?php

class yf_assets {

	/** @array Container for added content */
	protected $content = array();
	/** @array List of pre-defined assets */
	protected $assets = array();
	/** @array All filters to apply stored here */
	protected $filters = array();
	/***/
	protected $supported_asset_types = array(
		'js', 'css', 'less', 'sass', 'coffee', 'img', 'font',
	);
	/***/
	protected $supported_out_types = array(
		'js', 'css', 'images', 'fonts',
	);

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Direct call to object as to string is not allowed, return empty string instead
	*/
	function __toString() {
		return '';
	}

	/**
	*/
	public function __clone() {
		return $this->clean_all();
	}

	/**
	*/
	public function clean_all() {
		$this->content	= array();
		$this->assets	= array();
		$this->filters	= array();
	}

	/**
	*/
	public function _init() {
		$this->load_predefined_assets();
	}

	/**
	*/
	public function load_predefined_assets() {
		$assets = array();
		$suffix = '.php';
		$dir = 'share/assets/';
		$globs = array(
			'yf_main'				=> YF_PATH. $dir. '*'. $suffix,
			'yf_plugins'			=> YF_PATH. 'plugins/*/'. $dir. '*'. $suffix,
			'project_main'			=> PROJECT_PATH. $dir. '*'. $suffix,
			'project_app'			=> APP_PATH. $dir. '*'. $suffix,
			'project_plugins'		=> PROJECT_PATH. 'plugins/*/'. $dir. '*'. $suffix,
			'project_app_plugins'	=> APP_PATH. 'plugins/*/'. $dir. '*'. $suffix,
		);
		$slen = strlen($suffix);
		foreach($globs as $gname => $glob) {
			foreach(glob($glob) as $path) {
				$name = substr(basename($path), 0, -$slen);
				$assets[$name] = include $path;
			}
		}
		$this->assets += $assets;
		return $assets;
	}

	/**
	* Search for assets for current module in several places, where it can be stored.
	*/
	public function find_asset_type_for_module($asset_type, $module = '') {
		if (!$module) {
			$module = $_GET['object'];
		}
		$ext = '.'.$asset_type;
		$path = $module. '/'. $module. $ext;
		$paths = array(
			'yf_admin'			=> MAIN_TYPE_ADMIN ? YF_PATH. 'templates/admin/'.$path : '',
			'yf_user'			=> YF_PATH. 'templates/user/'.$path,
			'yf_plugins_admin'	=> MAIN_TYPE_ADMIN ? YF_PATH. 'plugins/'.$module.'/templates/admin/'.$path : '',
			'yf_plugins_user'	=> YF_PATH. 'plugins/'.$module.'/templates/user/'.$path,
			'project_admin'		=> MAIN_TYPE_ADMIN ? PROJECT_PATH. 'templates/admin/'.$path : '',
			'project_user'		=> PROJECT_PATH. 'templates/user/'.$path,
			'site_user'			=> SITE_PATH != PROJECT_PATH ? SITE_PATH. 'templates/user/'.$path : '',
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
	public function get_asset_details($name) {
		return $this->assets[$name];
	}

	/**
	* Return named asset, also can return specific version
	*/
	public function get_asset($name, $asset_type, $version = '') {
		$asset_data = $this->get_asset_details($name);
		// Get last version
		if (!$asset_data) {
			return null;
		}
		if (isset($asset_data['inherit'])) {
			$func = __FUNCTION__;
			return $this->$func($asset_data['inherit'][$asset_type], $asset_type, $version);
		}
		if (!is_array($asset_data['versions'])) {
			return null;
		}
		if ($version) {
			return $asset_data['versions'][$version][$asset_type];
		} else {
			$version_arr = array_slice($asset_data['versions'], -1, 1, true);
			$version_number = key($version_arr);
			$version_info = current($version_arr);
			return $version_info[$asset_type];
		}
	}

	/**
	* Helper for jquery on document ready
	*/
	function jquery($content, $params = array()) {
		return $this->helper_js_library(__FUNCTION__, '$(function(){'.PHP_EOL. $content. PHP_EOL.'})', $params);
	}

	/**
	* Helper
	*/
	function angularjs($content, $params = array()) {
		return $this->helper_js_library(__FUNCTION__, $content, $params);
	}

	/**
	* Helper
	*/
	function backbonejs($content, $params = array()) {
		return $this->helper_js_library(__FUNCTION__, $content, $params);
	}

	/**
	* Helper
	*/
	function reactjs($content, $params = array()) {
		return $this->helper_js_library(__FUNCTION__, $content, $params);
	}

	/**
	* Helper
	*/
	function emberjs($content, $params = array()) {
		return $this->helper_js_library(__FUNCTION__, $content, $params);
	}

	/**
	* Helper for JS library code
	*/
	function helper_js_library($lib_name, $content, $params = array()) {
		$asset_type = 'js';
		if (empty($this->already_required[$asset_type][$lib_name])) {
			$this->add_asset($lib_name, $asset_type);
			$this->already_required[$asset_type][$lib_name] = true;
		}
		return $this->add($content, $asset_type, 'inline', $params);
	}

	/**
	* Add asset item into current workflow
	*
	* $content: string/array
	* $asset_type: = bundle|js|css|img|less|sass|font
	* $content_type_hint: = auto|asset|url|file|inline|raw
	*/
	public function add($content, $asset_type, $content_type_hint = 'auto', $params = array()) {
		if (DEBUG_MODE) {
			$trace = main()->trace_string();
		}
		if (!$asset_type || !in_array($asset_type, $this->supported_asset_types)) {
			throw new Exception('Assets: unsupported asset type: '.$asset_type);
			return null;
		}
		if (!is_array($content)) {
			$content = array($content);
		}
		if (is_array($content_type_hint)) {
			$params = (array)$params + $content_type_hint;
			$content_type_hint = '';
		}
		foreach ($content as $_content) {
			$_content = trim($_content);
			if (!strlen($_content)) {
				continue;
			}
			$content_type = '';
			if (in_array($content_type_hint, array('url','file','inline','raw','asset'))) {
				$content_type = $content_type_hint;
			} else {
				$content_type = $this->detect_content_type($asset_type, $_content);
			}
			$md5 = md5($_content);
			if ($content_type == 'url') {
				$this->set_content($asset_type, $md5, 'url', $_content, $params);
			} elseif ($content_type == 'file') {
				if (file_exists($_content)) {
					$text = file_get_contents($_content);
					if (strlen($text)) {
						$this->set_content($asset_type, $md5, 'file', $_content, $params);
					}
				}
			} elseif ($content_type == 'inline') {
				$this->set_content($asset_type, $md5, 'inline', $_content, $params);
			} elseif ($content_type == 'raw') {
				$this->set_content($asset_type, $md5, 'raw', $_content, $params);
			} elseif ($content_type == 'asset') {
				$info = $this->get_asset($_content, $asset_type);
				if ($info) {
					$asset_data = $this->get_asset_details($_content);
					if (isset($asset_data['require'][$asset_type])) {
						$this->add($asset_data['require'][$asset_type], $asset_type, 'asset');
					}
					if (is_array($info)) {
						$url = $info['url'];
					} else {
						$url = $info;
					}
					$md5 = md5($url);
					$this->set_content($asset_type, $md5, 'url', $url, $params);
				}
			}
			if (DEBUG_MODE) {
				debug('assets[]', array(
					'asset_type'	=> $asset_type,
					'content_type'	=> $content_type,
					'md5'			=> $md5,
					'content'		=> $_content,
					'is_added'		=> !is_null($this->get_content($asset_type, $md5)),
					'params'		=> $params,
					'trace'			=> $trace,
				));
			}
		}
		return $this; // Chaining
	}

	/**
	* Shortcut
	*/
	public function add_url($content, $asset_type, $params = array()) {
		return $this->add($content, $asset_type, 'url', $params);
	}

	/**
	* Shortcut
	*/
	public function add_file($content, $asset_type, $params = array()) {
		return $this->add($content, $asset_type, 'file', $params);
	}

	/**
	* Shortcut
	*/
	public function add_inline($content, $asset_type, $params = array()) {
		return $this->add($content, $asset_type, 'inline', $params);
	}

	/**
	* Shortcut
	*/
	public function add_raw($content, $asset_type, $params = array()) {
		return $this->add($content, $asset_type, 'raw', $params);
	}

	/**
	* Shortcut
	*/
	public function add_asset($content, $asset_type, $params = array()) {
		return $this->add($content, $asset_type, 'asset', $params);
	}

	/**
	* Return content for given asset type, optionally only for md5 of it
	*/
	public function get_content($asset_type, $params = array()) {
		$md5 = (is_string($params) && strlen($params) === 32) ? $params : $params['md5'];
		return $md5 ? $this->content[$asset_type][$md5] : $this->content[$asset_type];
	}

	/**
	* Set unique content entry for given asset type
	*/
	public function set_content($asset_type, $md5, $content_type, $content, $params = array()) {
		return $this->content[$asset_type][$md5] = array(
			'content_type'	=> $content_type,
			'content'		=> $content,
			'params'		=> $params,
		);
	}
	/**
	* Clean content for given asset type
	*/
	public function clean_content($asset_type) {
		$this->already_required[$asset_type] = array();
		return $this->content[$asset_type] = array();
	}

	/**
	* Shortcut
	*/
	public function add_js($content, $content_type_hint = 'auto', $params = array()) {
		return $this->add('js', $content, $content_type_hint, $params);
	}

	/**
	* Shortcut
	*/
	public function add_css($content, $content_type_hint = 'auto', $params = array()) {
		return $this->add('css', $content, $content_type_hint, $params);
	}

	/**
	* Main method to display overall content by out type (js, css, images, fonts).
	* Can be called from main template like this: {exec_last(assets,show_js)} {exec_last(assets,show_css)}
	*/
// TODO: decide with virtual formats like sass, less, coffeescript
// TODO: add optional _prepare_html() for $url
	public function show($out_type, $params = array()) {
		if (!$out_type || !in_array($out_type, $this->supported_out_types)) {
			throw new Exception('Assets: unsupported out content type: '.$out_type);
			return null;
		}
		$ext = '.'.$out_type;
		// Assets from current module
		$module_assets_path = $this->find_asset_type_for_module($out_type, $_GET['object']);
		if ($module_assets_path) {
			$this->add_file($module_assets_path, $out_type);
		}
		if ($params['combined']) {
			$combined = $this->_show_combined_content($out_type, $params);
			// Degrade gracefully, also display raw content in case when combining queue is in progress
			if (strlen($combined)) {
				return $combined;
			}
		}
		$prepend = _class('core_events')->fire('assets.prepend');
		$out = array();
		// Process previously added content, depending on its type
		foreach ((array)$this->get_content($out_type) as $md5 => $v) {
			$type = $v['content_type'];
			$text = $v['content'];
			$_params = (array)$v['params'] + (array)$params;
			$css_class = $_params['class'] ? ' class="'.$_params['class'].'"' : '';
			if ($type == 'url') {
				if ($params['min'] && !DEBUG_MODE && strpos($text, '.min.') === false) {
					$text = substr($text, 0, -strlen($ext)).'.min'.$ext;
				}
			}
			if ($out_type === 'js') {
				if ($type == 'url') {
					$out[$md5] = '<script src="'.$text.'" type="text/javascript"'.$css_class.'></script>';
				} elseif ($type == 'file') {
					$out[$md5] = '<script type="text/javascript"'.$css_class.'>'. PHP_EOL. file_get_contents($text). PHP_EOL. '</script>';
				} elseif ($type == 'inline') {
					$text = $this->_strip_script_tags($text);
					$out[$md5] = '<script type="text/javascript"'.$css_class.'>'. PHP_EOL. $text. PHP_EOL. '</script>';
				} elseif ($type == 'raw') {
					$out[$md5] = $text;
				}
			} elseif ($out_type === 'css') {
				if ($type == 'url') {
					$out[$md5] = '<link href="'.$text.'" rel="stylesheet"'.$css_class.' />';
				} elseif ($type == 'file') {
					$out[$md5] = '<style type="text/css"'.$css_class.'>'. PHP_EOL. file_get_contents($text). PHP_EOL. '</style>';
				} elseif ($type == 'inline') {
					$text = $this->_strip_style_tags($text);
					$out[$md5] = '<style type="text/css"'.$css_class.'>'. PHP_EOL. $text. PHP_EOL. '</style>';
				} elseif ($type == 'raw') {
					$out[$md5] = $text;
				}
			}
		}
		$append = _class('core_events')->fire('assets.append', array('out' => &$out));
		$this->clean_content($out_type);
		return implode(PHP_EOL, $prepend). implode(PHP_EOL, $out). implode(PHP_EOL, $append);
	}

	/**
	* Shortcut
	*/
	public function show_js($params = array()) {
		return $this->show('js', $params);
	}

	/**
	* Shortcut
	*/
	public function show_css() {
		return $this->show('css', $params);
	}

	/**
	* Combine added content according to rules, optionally applying different filters
	*/
// TODO: add tpl for auto-generated hash file name, using: %host, %project, %include_path, %yf_path, %date, %is_user, %is_admin ...
// TODO: add ability to pass callback for auto-generated hash file name
// TODO: support for .min, using some of console minifier (yahoo, google, jsmin ...)
// TODO: locking atomic to prevent doing same job in several threads
// TODO: move to web accessible folder only after completion to ensure atomicity
// TODO: unify get_url_contents()
// TODO: in DEBUG_MODE add comments into generated file and change its name to not overlap with production one
// TODO: decide with images: jpeg, png, gif, sprites
// TODO: decide with fonts: different formats
	public function combine_by_type($asset_type, $params = array()) {
		$ext = '.'.$asset_type;
		$combined_file = PROJECT_PATH. 'templates/'.$asset_type.'/'.date('YmdHis').'_'.md5($_SERVER['HTTP_HOST']). $ext;
		if (file_exists($combined_file) && filemtime($combined_file) > (time() - 3600)) {
			return $combined_file;
		}
		$combined_dir = dirname($combined_file);
		if (!file_exists($combined_dir)) {
			mkdir($combined_dir, 0755, true);
		}
		$content = $this->get_content($asset_type);
		_class('core_events')->fire('assets.before_combine', array(
			'asset_type'=> $asset_type,
			'file'		=> $combined_file,
			'content'	=> &$content,
			'params'	=> $params,
		));
		$out = array();
		$content = $this->get_content($asset_type);
		foreach ((array)$content as $md5 => $v) {
			$type = $v['content_type'];
			$text = $v['content'];
			if ($type == 'url') {
				$out[$md5] = file_get_contents($text, false, stream_context_create(array('http' => array('timeout' => 5))));
			} elseif ($type == 'file') {
				$out[$md5] = file_get_contents($text);
			} elseif ($type == 'inline') {
				if ($asset_type === 'css') {
					$text = $this->_strip_style_tags($text);
				} elseif ($asset_type === 'js') {
					$text = $this->_strip_script_tags($text);
				}
				$out[$md5] = $text;
			} elseif ($type == 'raw') {
				$out[$md5] = $text;
			}
		}
		_class('core_events')->fire('assets.after_combine', array(
			'asset_type'=> $asset_type,
			'file'		=> $combined_file,
			'content'	=> &$content,
			'out'		=> &$out,
			'params'	=> $params,
		));
		if (!empty($out)) {
			file_put_contents($combined_file, implode(PHP_EOL, $out));
		}
		return $combined_file;
	}

	/**
	*/
	public function show_combined_content($out_type, $params = array()) {
		$combined_file = $this->combine_by_type($out_type, $params);
		if (!$combined_file || !file_exists($combined_file)) {
			return false;
		}
		if ($out_type === 'js') {
			$params['type'] = 'text/javascript';
			$params['src'] = $combined_file;
			return '<script'._attrs($params, array('type', 'src', 'class', 'id')).'></script>';
		} elseif ($out_type === 'css') {
			$params['rel'] = 'stylesheet';
			$params['href'] = $combined_file;
			return '<link'._attrs($params, array('href', 'rel', 'class', 'id')).' />';
		}
	}

	/**
	*/
	public function detect_content_type($asset_type, $content = '') {
		$content = trim($content);
		$type = false;
		if (isset($this->assets[$content])) {
			$type = 'asset';
		} elseif ($asset_type === 'js') {
			if (preg_match('~^(http://|https://|//)[a-z0-9]+~ims', $content)) {
				$type = 'url';
			} elseif (preg_match('~^/[a-z0-9\./_-]+\.js$~ims', $content) && file_exists($content)) {
				$type = 'file';
			} elseif (preg_match('~^(<script|[$;#\*/])~ims', $content) || strpos($content, PHP_EOL) !== false) {
				$type = 'inline';
			}
		} elseif ($asset_type === 'css') {
			if (preg_match('~^(http://|https://|//)[a-z0-9]+~ims', $content)) {
				$type = 'url';
			} elseif (preg_match('~^/[a-z0-9\./_-]+\.css$~ims', $content) && file_exists($content)) {
				$type = 'file';
			} elseif (preg_match('~^(<style|[$;#\.@/\*])~ims', $content) || strpos($content, PHP_EOL) !== false) {
				$type = 'inline';
			}
		}
		return $type;
	}

	/**
	*/
	public function _strip_style_tags ($text) {
/*
// TODO: add support for extracting url from <link rel="stylesheet" href="path.to/style.css">
//		preg_replace_callback('~<link[\s\t]+rel="stylesheet"[\s\t]+href="([^"]+)"[\s\t]*[/]?>~ims', function($m) use (&$text) {
//			return $m[1];
//		});
*/
		for ($i = 0; $i < 10; $i++) {
			if (strpos($text, 'style') === false) {
				break;
			}
			$text = preg_replace('~^<style[^>]*?>~ims', '', $text);
			$text = preg_replace('~</style>$~ims', '', $text);
		}
		return $text;
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

	/**
	* Add custom filter callback
	*/
	public function filter_add($asset_type, $callback, $params = array()) {
		$this->filters[$asset_type][] = array(
			'callback'	=> $callback,
			'params'	=> $params,
		);
	}

	/**
	*/
	public function filters_apply($asset_type) {
// TODO
	}

	/**
	*/
	public function filters_process_css_urls() {
// TODO
	}

	/**
	*/
	public function upload_to() {
// TODO: upload to S3, FTP
	}
}
