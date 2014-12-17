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
		'js', 'jquery', 'css', 'less', 'sass', 'coffee', 'img', 'font', 'bundle',
	);
	/***/
	protected $supported_content_types = array(
		'asset', 'url', 'file', 'inline',
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
	* Main JS from theme stpl
	*/
	public function init_js() {
		if ($this->_init_js_done) {
			return false;
		}
		$this->_init_js_done = true;
		$main_script_js = trim(tpl()->parse_if_exists('script_js'));
		// single string = automatically generated by compass
		if (strpos($main_script_js, "\n") === false && strlen($main_script_js) && preg_match('~^js/script.js\?[0-9]{10}$~ims', $main_script_js)) {
			$this->add(WEB_PATH. tpl()->TPL_PATH. $main_script_js, 'js', 'url');
		} else {
			$this->add($main_script_js, 'js', 'inline');
		}
	}

	/**
	* Main CSS from theme stpl
	*/
	public function init_css() {
		if ($this->_init_css_done) {
			return false;
		}
		$this->_init_css_done = true;
		$main_style_css = trim(tpl()->parse_if_exists('style_css'));
		// single string = automatically generated by compass
		if (strpos($main_style_css, "\n") === false && strlen($main_style_css) && preg_match('~^css/style.css\?[0-9]{10}$~ims', $main_style_css)) {
			$this->add(WEB_PATH. tpl()->TPL_PATH. $main_style_css, 'css', 'inline');
		} else {
			$this->add($main_style_css, 'css', 'inline');
		}
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
		$pattern = $dir. '*'. $suffix;
		$globs = array(
			'yf_main'				=> YF_PATH. $pattern,
			'yf_plugins'			=> YF_PATH. 'plugins/*/'. $pattern,
			'project_main'			=> PROJECT_PATH. $pattern,
			'project_app'			=> APP_PATH. $pattern,
			'project_plugins'		=> PROJECT_PATH. 'plugins/*/'. $pattern,
			'project_app_plugins'	=> APP_PATH. 'plugins/*/'. $pattern,
		);
		$slen = strlen($suffix);
		$names = array();
		foreach($globs as $gname => $glob) {
			foreach(glob($glob) as $path) {
				$name = substr(basename($path), 0, -$slen);
				$names[$name] = $path;
			}
		}
		// This double iterating code ensures we can inherit/replace assets with same name inside project
		foreach($names as $name => $path) {
			$assets[$name] = include $path;
		}
		$this->assets += $assets;
// TODO: debug info
		return $assets;
	}

	/**
	* Get list of built-in filters
	*/
	public function filters_get_avail() {
		if (isset($this->_avail_filters)) {
			return $this->_avail_filters;
		}
		$names = array();
		$suffix = '.class.php';
		$prefix = 'assets_filter_';
		$prefix2 = YF_PREFIX;
		$dir = 'classes/assets/';
		$pattern = $dir. '*'. $prefix. '*'. $suffix;
		$globs = array(
			'yf_main'				=> YF_PATH. $pattern,
			'yf_plugins'			=> YF_PATH. 'plugins/*/'. $pattern,
			'project_main'			=> PROJECT_PATH. $pattern,
			'project_app'			=> APP_PATH. $pattern,
			'project_plugins'		=> PROJECT_PATH. 'plugins/*/'. $pattern,
			'project_app_plugins'	=> APP_PATH. 'plugins/*/'. $pattern,
		);
		$slen = strlen($suffix);
		$plen = strlen($prefix);
		$plen2 = strlen($prefix2);
		$names = array();
		foreach($globs as $gname => $glob) {
			foreach(glob($glob) as $path) {
				$name = substr(basename($path), 0, -$slen);
				if (substr($name, 0, $plen2) === $prefix2) {
					$name = substr($name, $plen2);
				}
				if (substr($name, 0, $plen) === $prefix) {
					$name = substr($name, $plen);
				}
				$names[$name] = $path;
			}
		}
		$this->_avail_filters = $names;
		return $names;
	}

	/**
	* Register new bundle or replace existing by name on-the-fly
	*/
	public function bundle_register($name, array $config) {
		$this->assets[$name] = $config;
		return $this;
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
/*
TODO: idea from  https://getcomposer.org/doc/01-basic-usage.md#package-versions
Package Versions#

In the previous example we were requiring version 1.0.* of monolog. This means any version in the 1.0 development branch. It would match 1.0.0, 1.0.2 or 1.0.20.

Version constraints can be specified in a few different ways.

Name	Example	Description
Exact version	1.0.2	You can specify the exact version of a package.
Range	>=1.0 >=1.0,<2.0 >=1.0,<1.1 | >=1.2	By using comparison operators you can specify ranges of valid versions. Valid operators are >, >=, <, <=, !=. 
You can define multiple ranges. Ranges separated by a comma (,) will be treated as a logical AND. A pipe (|) will be treated as a logical OR. AND has higher precedence than OR.
Wildcard	1.0.*	You can specify a pattern with a * wildcard. 1.0.* is the equivalent of >=1.0,<1.1.
Tilde Operator	~1.2	Very useful for projects that follow semantic versioning. ~1.2 is equivalent to >=1.2,<2.0. For more details, read the next section below.
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
	* $asset_type: = bundle|js|jquery|css|img|less|sass|font
	* $content_type_hint: = auto|asset|url|file|inline
	*/
	public function add($content, $asset_type = 'bundle', $content_type_hint = 'auto', $params = array()) {
		if (DEBUG_MODE) {
			$trace = main()->trace_string();
		}
		if ($asset_type === 'jquery') {
			return $this->jquery($content, $params);
		}
		if ($asset_type === 'js') {
			$this->init_js();
		} elseif ($asset_type === 'css') {
			$this->init_css();
		}
		if (is_array($content_type_hint)) {
			$params = (array)$params + $content_type_hint;
			$content_type_hint = $params['type'];
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
		if (is_array($content) && isset($content['content'])) {
			if (is_array($content['params'])) {
				$params += $content['params'];
			}
			$content = $content['content'];
		}
		if (!is_array($content)) {
			$content = array($content);
		}
		if (!$content_type_hint) {
			$content_type_hint = 'auto';
		}
		if (is_array($content_type_hint)) {
			$params = (array)$params + $content_type_hint;
			$content_type_hint = $params['type'];
		}
		foreach ($content as $_content) {
			if (!is_string($_content) && is_callable($_content)) {
				$_content = $_content();
			}
			$_params = $params;
			if (is_array($_content) && isset($_content['content'])) {
				if (is_array($_content['params'])) {
					$_params += $_content['params'];
				}
				$_content = $_content['content'];
			}
			if (is_array($_content)) {
				$this->add($_content, $asset_type, $content_type_hint, $_params);
				continue;
			}
			$_content = trim($_content);
			if (!$_content) {
				continue;
			}
			if ($asset_type === 'bundle') {
				$this->_add_bundle($_content, $_params);
				continue;
			}
			$content_type = '';
			if (in_array($content_type_hint, $this->supported_content_types)) {
				$content_type = $content_type_hint;
			} else {
				$content_type = $this->detect_content_type($asset_type, $_content);
			}
			$md5 = md5($_content);
			$asset_data = array();
			if ($content_type === 'asset') {
				$this->_add_asset($_content, $asset_type, $_params);
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
	*/
	public function _add_bundle($_content, $params = array()) {
		if (!$_content) {
			return false;
		}
		$bundle_details = $this->get_asset_details($_content);
		if (!$bundle_details) {
			return false;
		}
		if (!is_string($bundle_details) && is_callable($bundle_details)) {
			$bundle_details = $bundle_details();
		}
		if (!$bundle_details) {
			return false;
		}
		if (isset($bundle_details['config'])) {
			$_params['config'] = $bundle_details['config'];
		}
		$DIRECT_OUT = isset($params['direct_out']) ? $params['direct_out'] : $this->ADD_IS_DIRECT_OUT;
		$sub_params = (array)$_params + ($DIRECT_OUT ? array('direct_out' => false) : array());
		foreach ($this->supported_asset_types as $atype) {
			$this->_sub_add($bundle_details['require'][$atype], $atype, $sub_params);
			$this->_sub_add($this->get_asset($_content, $atype), $atype, $sub_params);
			$this->_sub_add($bundle_details['add'][$atype], $atype, $sub_params);
		}
	}

	/**
	*/
	public function _add_asset($_content, $asset_type, $params = array()) {
		if (!$_content) {
			return false;
		}
		$asset_data = $this->get_asset_details($_content);
		if (!$asset_data) {
			return false;
		}
		if (!is_string($asset_data) && is_callable($asset_data)) {
			$asset_data = $asset_data();
		}
		if (!$asset_data) {
			return false;
		}
		if (isset($asset_data['config'])) {
			$_params['config'] = $asset_data['config'];
		}
		$DIRECT_OUT = isset($params['direct_out']) ? $params['direct_out'] : $this->ADD_IS_DIRECT_OUT;
		$sub_params = (array)$_params + ($DIRECT_OUT ? array('direct_out' => false) : array());
		$this->_sub_add($asset_data['require'][$asset_type], $asset_type, $sub_params);
		$this->_sub_add($this->get_asset($_content, $asset_type), $asset_type, $sub_params);
		$this->_sub_add($asset_data['add'][$asset_type], $asset_type, $sub_params);
	}

	/**
	*/
	public function _sub_add($info, $asset_type, $sub_params = array()) {
		if (!$info) {
			return false;
		}
		if (!is_string($info) && is_callable($info)) {
			$info = $info();
		}
		if (!$info) {
			return false;
		}
		if (!is_array($info)) {
			$info = array($info);
		}
		if (is_array($info) && isset($info['content'])) {
			if (is_array($info['params'])) {
				$sub_params += $info['params'];
			}
			$info = $info['content'];
			if (!$info) {
				return false;
			}
			if (!is_array($info)) {
				$info = array($info);
			}
		}
		if (!$info) {
			return false;
		}
		foreach ((array)$info as $_info) {
			if ($_info) {
				$this->add($_info, $asset_type, 'auto', $sub_params);
			}
		}
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
		return $this->add($content, 'js', $content_type_hint, $params);
	}

	/**
	* Shortcut
	*/
	public function add_css($content, $content_type_hint = 'auto', $params = array()) {
		return $this->add($content, 'css', $content_type_hint, $params);
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
			if (in_array($content_type, array('inline'))) {
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
// TODO: Fallback to local: window.Foundation || document.write('<script src="/js/vendor/foundation.min.js"><\/script>')
	public function show($out_type, $params = array()) {
		if (!$out_type || !in_array($out_type, $this->supported_out_types)) {
			throw new Exception('Assets: unsupported out content type: '.$out_type);
			return null;
		}
		if ($out_type === 'js') {
			$this->init_js();
		} elseif ($out_type === 'css') {
			$this->init_css();
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
			if ($_params['config']['class']) {
				$_params['class'] = $_params['config']['class'];
			}
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
			}
		}
		return $out;
	}

	/**
	*/
	public function upload_to() {
// TODO: upload to S3, FTP
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
			} else {
				$type = 'inline';
			}
		} elseif ($asset_type === 'css') {
			if (preg_match('~^(http://|https://|//)[a-z0-9]+~ims', $content)) {
				$type = 'url';
			} elseif (preg_match('~^/[a-z0-9\./_-]+\.css$~ims', $content) && file_exists($content)) {
				$type = 'file';
			} else {
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
	* Shortcut for filters_add with js asset
	*/
	public function filters_add_js($callback, $params = array()) {
		return $this->filters_add('js', $callback, $params);
	}

	/**
	* Shortcut for filters_add with css asset
	*/
	public function filters_add_css($callback, $params = array()) {
		return $this->filters_add('css', $callback, $params);
	}

	/**
	* Add filters to processing chain, both custom and built-in supported
	*/
	public function filters_add($asset_type, $callback, $params = array()) {
		if (!$asset_type) {
			throw new Exception('Assets: '.__FUNCTION__.' missing asset_type');
			return $this;
		}
		if (is_array($callback)) {
			$func = __FUNCTION__;
			foreach ($callback as $k => $v) {
				$this->$func($asset_type, $v, $params);
			}
			return $this;
		}
		$this->filters[$asset_type][] = array(
			'callback'	=> $callback,
			'params'	=> $params,
		);
		return $this;
	}

	/**
	* Get list of filters, added to procesing chain, both custom and built-in
	*/
	public function filters_get_added($asset_type) {
		if (!$asset_type) {
			return false;
		}
		return $this->filters[$asset_type];
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
	public function filters_process_input($in, $filters = array(), $params = array()) {
		if (is_array($in)) {
			$out = array();
			$func = __FUNCTION__;
			foreach ($in as $k => $v) {
				$out[$k] = $this->$func($v, $filters, $params);
			}
			return $out;
		}
		$this->_autoload_libs();

		if (!is_array($filters)) {
			$filters = array($filters);
		}
		$out = $in;
		$avail_filters = $this->filters_get_avail();
		foreach ($filters as $filter) {
			$_params = array();
			if (is_array($filter)) {
				$_params = $filter['params'];
				$filter = $filter['callback'];
			}
			if (!$filter) {
				continue;
			}
			if (is_callable($filter)) {
				$out = $filter($out, $params);
			} elseif (is_string($filter) && isset($avail_filters[$filter])) {
				$out = _class('assets_filter_'.$filter, 'classes/assets/')->apply($out, $params);
			}
		}
		return $out;
	}

	/**
	* Shortcut for filters_content_process with js asset
	*/
	public function filters_process_js($params = array()) {
		return $this->filters_process_added('js', $params);
	}

	/**
	* Shortcut for filters_content_process with css asset
	*/
	public function filters_process_css($params = array()) {
		return $this->filters_process_added('css', $params);
	}

	/**
	* Apply added filters to gathered content of the given asset type
	*/
	public function filters_process_added($asset_type, $params = array()) {
		if (!$asset_type) {
			return false;
		}
		$filters = $this->filters_get_added($asset_type);
		if (!$filters) {
			return $this;
		}
		$content = $this->get_content($asset_type);
		if (!$content) {
			return $this;
		}
		foreach ($content as $md5 => $info) {
// TODO: support for other content types
			if ($info['content_type'] !== 'inline') {
				continue;
			}
			$_content = $info['content'];
			$processed = $this->filters_process_input($_content, $filters, $params);
			if ($_content !== $processed) {
				$this->content[$asset_type][$md5]['content'] = $processed;
			}
		}
		return $this;
	}
}
