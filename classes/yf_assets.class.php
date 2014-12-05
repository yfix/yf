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
		'js', 'css', 'less', 'sass', 'coffee', 'img', 'font', 'bundle',
	);
	/***/
	protected $supported_out_types = array(
		'js', 'css', 'images', 'fonts',
	);
	/** @bool Needed to ensure smooth transition of existing codebase. If enabled - then each add() call will immediately return generated content */
	public $ADD_IS_DIRECT_OUT = false;

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
		$this->filters	= array();
	}

	/**
	*/
	public function _init() {
		$this->load_predefined_assets();
	}

	/**
	*/
	function _autoload_libs() {
		if (isset($this->_autoload_registered)) {
			return true;
		}
		$paths = array(
			'app'	=> APP_PATH.'libs/vendor/autoload.php',
			'yf'	=> YF_PATH.'libs/vendor/autoload.php',
			'server'=> '/usr/local/share/composer/vendor/autoload.php',
		);
		$path_loaded = '';
		foreach ($paths as $name => $path) {
			if (file_exists($path)) {
				$path_loaded = $name;
				require_once $path;
				break;
			}
		}
		if (!$path_loaded) {
			throw new Exception('Assets: filter libs not loaded as composer autoload not found on these paths: '.implode(', ', $paths).'.'
				. PHP_EOL. 'You need to install composer dependencies by running this script from console: %YF_PATH%/.dev/scripts/assets/install_global.sh');
		}
		$this->_autoload_registered = $paths[$path_loaded];
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
		if (!isset($this->already_required[$asset_type][$lib_name])) {
			$this->add_asset($lib_name, $asset_type, $this->ADD_IS_DIRECT_OUT ? array('direct_out' => false) : array());
			$this->already_required[$asset_type][$lib_name] = true;
		}
		return $this->add($content, $asset_type, 'inline', $params);
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
		if (!is_string($asset_data) && is_callable($asset_data)) {
			$asset_data = $asset_data();
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
	* Add asset item into current workflow
	*
	* $content: string/array
	* $asset_type: = bundle|js|css|img|less|sass|font
	* $content_type_hint: = auto|asset|url|file|inline|raw
	*/
	public function add($content, $asset_type = 'bundle', $content_type_hint = 'auto', $params = array()) {
		if (DEBUG_MODE) {
			$trace = main()->trace_string();
		}
		if (!$asset_type) {
			$asset_type = 'bundle';
		}
		if (!in_array($asset_type, $this->supported_asset_types)) {
			throw new Exception('Assets add(): unsupported asset type: '.$asset_type);
			return $this;
		}
		$DIRECT_OUT = isset($params['direct_out']) ? $params['direct_out'] : $this->ADD_IS_DIRECT_OUT;
		if (empty($content)) {
			return $DIRECT_OUT ? $this->show($asset_type) : $this;
		}
		if (!is_array($content)) {
			$content = array($content);
		}
		if (!$content_type_hint) {
			$content_type_hint = 'auto';
		}
		if (is_array($content_type_hint)) {
			$params = (array)$params + $content_type_hint;
			$content_type_hint = '';
		}
		foreach ($content as $_content) {
			if (!is_string($_content) && is_callable($_content)) {
				$_content = $_content();
			}
			$_content = trim($_content);
			$_params = $params;
			if ($asset_type === 'bundle') {
				$bundle_details = $this->get_asset_details($_content);
				if (!is_string($bundle_details) && is_callable($bundle_details)) {
					$bundle_details = $bundle_details();
				}
				foreach ($this->supported_asset_types as $atype) {
					$arequire = $bundle_details['require'][$atype];
					if ($arequire) {
						$this->add($arequire, $atype, 'auto', $DIRECT_OUT ? array('direct_out' => false) : array());
					}
					$adata = $this->get_asset($_content, $atype);
					if ($adata) {
						$this->add($adata, $atype, 'auto', $DIRECT_OUT ? array('direct_out' => false) : array());
					}
				}
				continue;
			}
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
			$asset_data = array();
			if ($content_type === 'asset') {
				$info = $this->get_asset($_content, $asset_type);
				if ($info) {
					$asset_data = $this->get_asset_details($_content);
					if (!is_string($asset_data) && is_callable($asset_data)) {
						$asset_data = $asset_data();
					}
					if (isset($asset_data['config'])) {
						$_params['config'] = $asset_data['config'];
					}
					if (isset($asset_data['require'][$asset_type])) {
						$this->add($asset_data['require'][$asset_type], $asset_type, 'asset', (array)$_params + ($DIRECT_OUT ? array('direct_out' => false) : array()));
					}
					if (!is_array($info)) {
						$info = array($info);
					}
					foreach ($info as $_info) {
						$this->add($_info, $asset_type, 'auto', (array)$_params + ($DIRECT_OUT ? array('direct_out' => false) : array()));
					}
				}
			} elseif ($content_type === 'url') {
				$this->set_content($asset_type, $md5, 'url', $_content, $_params);
			} elseif ($content_type === 'file') {
				if (file_exists($_content)) {
					$str = file_get_contents($_content);
					if (strlen($str)) {
						$this->set_content($asset_type, $md5, 'file', $_content, $_params);
					}
				}
			} elseif ($content_type === 'inline') {
				$this->set_content($asset_type, $md5, 'inline', $_content, $_params);
			} elseif ($content_type === 'raw') {
				$this->set_content($asset_type, $md5, 'raw', $_content, $_params);
			}
			if (DEBUG_MODE) {
				debug('assets_add[]', array(
					'asset_type'	=> $asset_type,
					'content_type'	=> $content_type,
					'md5'			=> $md5,
					'content'		=> $_content,
					'is_added'		=> !is_null($this->get_content($asset_type, $md5)),
					'preview'		=> '',
					'params'		=> $_params,
					'trace'			=> $trace,
				));
			}
		}
		return $DIRECT_OUT ? $this->show_css().$this->show_js() : $this;
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
		if (!$this->ADD_IS_DIRECT_OUT) {
			$this->already_required[$asset_type] = array();
		}
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
	*/
	public function _get_all_content_for_out($out_type, $params = array()) {
		// Move down inlined content
		$all_content = $this->get_content($out_type);
		$top = array();
		$bottom = array();
		foreach ((array)$all_content as $md5 => $v) {
			$content_type = $v['content_type'];
			if (in_array($content_type, array('inline', 'raw'))) {
				$top[$md5] = $v;
			} else {
				$bottom[$md5] = $v;
			}
		}
		return $bottom + $top;
	}

	/**
	* Main method to display overall content by out type (js, css, images, fonts).
	* Can be called from main template like this: {exec_last(assets,show_js)} {exec_last(assets,show_css)}
	*/
// TODO: decide with virtual formats like sass, less, coffeescript
	public function show($out_type, $params = array()) {
		if (!$out_type || !in_array($out_type, $this->supported_out_types)) {
			throw new Exception('Assets: unsupported out content type: '.$out_type);
			return null;
		}
		if (!is_array($params)) {
			$params = !empty($params) ? array($params) : array();
		}
		$ext = '.'.$out_type;
		// Assets from current module
		$module_assets_path = $this->find_asset_type_for_module($out_type, $_GET['object']);
		if ($module_assets_path) {
			$this->add_file($module_assets_path, $out_type);
		}
		if ($params['combined']) {
			$combined = $this->show_combined_content($out_type, $params);
			// Degrade gracefully, also display raw content in case when combining queue is in progress
			if (strlen($combined)) {
				return $combined;
			}
		}
		$prepend = _class('core_events')->fire('assets.prepend');

		// Process previously added content, depending on its type
		$out = array();
		foreach ((array)$this->_get_all_content_for_out($out_type) as $md5 => $v) {
			$content_type = $v['content_type'];
			$str = $v['content'];
			$_params = (array)$v['params'] + (array)$params;
			if ($_params['min'] && $content_type === 'url' && !DEBUG_MODE) {
				if (strpos($str, '.min.') === false) {
					$str = substr($str, 0, -strlen($ext)).'.min'.$ext;
				}
			}
			$before = $_params['config']['before'];
			$after = $_params['config']['after'];
			if (DEBUG_MODE) {
				$debug = array();
				foreach ((array)debug('assets_add') as $d) {
					if ($d['md5'] === $md5) {
						$debug = $d;
						break;
					}
				}
				$dname = $out_type.'_'.$md5;
				$trace_short = str_replace(array('<','>'), array('&lt;','&gt;'), implode('; ', array_slice(explode(PHP_EOL, $debug['trace']), 2, 2, true)));
				$ctype = $debug['content_type'];
				if ($ctype === 'asset') {
					$ctype .= ':'.$debug['content'];
				}
				$before = PHP_EOL. '<!-- asset start: '.$dname.' | '.$ctype.' | '.$trace_short.' -->'. PHP_EOL. $before;
				$after = $after. PHP_EOL. '<!-- asset end: '.$dname.' -->'. PHP_EOL;
				debug('assets_out[]', array(
					'out_type'		=> $out_type,
					'md5'			=> $md5,
					'content_type'	=> $content_type,
					'content'		=> $str,
					'preview'		=> '',
					'params'		=> $_params,
					'trace'			=> $debug['trace'],
				));
			}
			$out[$md5] = $before. $this->html_out($out_type, $content_type, $str, $_params). $after;
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
	public function show_css($params = array()) {
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
	public function combine($asset_type, $params = array()) {
		$ext = '.'.$asset_type;
		$combined_file = $params['out_file'] ?: PROJECT_PATH. 'templates/'.$asset_type.'/'.date('YmdHis').'_'.substr(md5($_SERVER['HTTP_HOST']), 0, 8). $ext;
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
			$content_type = $v['content_type'];
			$_content = $v['content'];
			if ($content_type === 'url') {
				$try_prefix = '//';
				if (substr($_content, 0, strlen($try_prefix)) === $try_prefix) {
					$_content = 'http:'.$_content;
				}
				$out[$md5] = file_get_contents($_content, false, stream_context_create(array('http' => array('timeout' => 5))));
			} elseif ($content_type === 'file' && file_exists($_content)) {
				$out[$md5] = file_get_contents($_content);
			} elseif ($content_type === 'inline') {
				if ($asset_type === 'css') {
					$_content = $this->_strip_css_input($_content);
				} elseif ($asset_type === 'js') {
					$_content = $this->_strip_js_input($_content);
				}
				$out[$md5] = $_content;
			} elseif ($content_type === 'raw') {
				$out[$md5] = $_content;
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
			$divider = PHP_EOL;
			if ($asset_type === 'js') {
				$divider = PHP_EOL.';'.PHP_EOL;
			}
			file_put_contents($combined_file, implode($divider, $out));
		}
		return $combined_file;
	}

	/**
	* Shortcut
	*/
	public function combine_js($params = array()) {
		return $this->combine('js', $params);
	}

	/**
	* Shortcut
	*/
	public function combine_css($params = array()) {
		return $this->combine('css', $params);
	}

	/**
	*/
// TODO: replace links with WEB_PATH or MEDIA_PATH, as $combined_file is filesystem path
// TODO: support for multiple media servers
	public function show_combined_content($out_type, $params = array()) {
		$combined_file = $this->combine($out_type, $params);
		if (!$combined_file || !file_exists($combined_file)) {
			return false;
		}
		return $this->html_out($out_type, 'url', str_replace(PROJECT_PATH, WEB_PATH, $combined_file), $params);
	}

	/**
	* Generate html output for desired asset out type and content type
	*/
// TODO: add optional _prepare_html() for $url
	public function html_out($out_type, $content_type, $str, $params = array()) {
		if (!$out_type || !$content_type || !strlen($str)) {
			return false;
		}
		$out = '';
		if ($out_type === 'js') {
			$params['type'] = 'text/javascript';
			if ($content_type === 'url') {
				$params['src'] = $str;
				$out = '<script'._attrs($params, array('src', 'type', 'class', 'id')).'></script>';
			} elseif ($content_type === 'file') {
// TODO: try to find web path for file and show it as url
				$out = '<script'._attrs($params, array('type', 'class', 'id')).'>'. PHP_EOL. file_get_contents($str). PHP_EOL. '</script>';
			} elseif ($content_type === 'inline') {
				$str = $this->_strip_js_input($str);
				$out = '<script'._attrs($params, array('type', 'class', 'id')).'>'. PHP_EOL. $str. PHP_EOL. '</script>';
			} elseif ($content_type === 'raw') {
				$out = $str;
			}
		} elseif ($out_type === 'css') {
			$params['type'] = 'text/css';
			if ($content_type === 'url') {
				$params['rel'] = 'stylesheet';
				$params['href'] = $str;
				$out = '<link'._attrs($params, array('href', 'rel', 'class', 'id')).' />';
			} elseif ($content_type === 'file') {
// TODO: try to find web path for file and show it as url
				$out = '<style'._attrs($params, array('type', 'class', 'id')).'>'. PHP_EOL. file_get_contents($str). PHP_EOL. '</style>';
			} elseif ($content_type === 'inline') {
				$str = $this->_strip_css_input($str);
				$out = '<style'._attrs($params, array('type', 'class', 'id')).'>'. PHP_EOL. $str. PHP_EOL. '</style>';
			} elseif ($content_type === 'raw') {
				$out = $str;
			}
		}
		return $out;
	}

	/**
	* Auto-detection on content type
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
	* Cleanup for CSS strings
	*/
	public function _strip_css_input($str) {
		// Extracting url from <link rel="stylesheet" href="path.to/style.css">
		$str = preg_replace_callback('~<link[\s]+[^>]*href=["\']([^"\']+?)["\'][^>]*>~ims', function($m) { return $m[1]; }, $str);

		for ($i = 0; $i < 10; $i++) {
			if (strpos($str, 'style') === false) {
				break;
			}
			$str = preg_replace('~^<style[^>]*?>~ims', '', $str);
			$str = preg_replace('~</style>$~ims', '', $str);
		}
		return $str;
	}

	/**
	* Cleanup for JS strings
	*/
	public function _strip_js_input($str) {
		// Extracting url from <script src="path.to/scripts.js"></script>
		$str = preg_replace_callback('~<script[\s]+[^>]*src=["\']([^"\']+?)["\'][^>]*>~ims', function($m) { return $m[1]; }, $str);

		for ($i = 0; $i < 10; $i++) {
			if (strpos($str, 'script') === false) {
				break;
			}
			$str = preg_replace('~^<script[^>]*?>~ims', '', $str);
			$str = preg_replace('~</script>$~ims', '', $str);
		}
		return $str;
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
	* Clean list of current filters to apply automatically to output
	*/
	public function filters_clean($asset_type = '') {
		if ($asset_type) {
			$this->filters[$asset_type] = array();
		} else {
			$this->filters = array();
		}
	}

	/**
	* Apply filters from names array to input string
	*/
	public function filters_apply($str, $filters = array(), $params = array()) {
		$out = $str;
		$methods = $this->get_class_methods($this);
		$methods = array_combine($methods, $methods);
		foreach ($filters as $name => $filter) {
			if (is_callable($filter)) {
				$str = $filter($str, $params);
			} elseif (isset($methods['filter_'.$name])) {
				$func = 'filter_'.$name;
				$str = $this->$func($str, $params);
			}
		}
		return $str;
	}

	/**
	*/
	public function filters_process_css_urls() {
// TODO
	}

	/**
	* Content filter
	*/
	public function filter_jsmin($in) {
		$this->_autoload_libs();
		if (!class_exists('\JSMin')) {
			throw new Exception('Assets: class \JSMin not found');
			return $in;
		}
		return \JSMin::minify($in);
	}

	/**
	* Content filter
	*/
	public function filter_jsminplus($in) {
		$this->_autoload_libs();
		if (!class_exists('\JSMinPlus')) {
			throw new Exception('Assets: class \JSMinPlus not found');
			return $in;
		}
		return \JSMinPlus::minify($in);
	}

	/**
	* Content filter
	*/
	public function filter_cssmin($in) {
		$this->_autoload_libs();
		if (!class_exists('\CssMin')) {
			throw new Exception('Assets: class \CssMin not found');
			return $in;
		}
		return \CssMin::minify($in);
	}

	/**
	*/
	public function upload_to() {
// TODO: upload to S3, FTP
	}
}
