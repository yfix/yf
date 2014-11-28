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
		'js','css','less','sass','img','font',
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
	*/
	public function get_asset_details($name) {
		return $this->assets[$name];
	}

	/**
	*/
	public function get_asset($name, $asset_type) {
		$asset_data = $this->get_asset_details($name);
		// Get last version
		if ($asset_data && is_array($asset_data['versions'])) {
			$version_arr = array_slice($asset_data['versions'], -1, 1, true);
			$version_number = key($version_arr);
			$version_info = current($version_arr);
			return $version_info[$asset_type];
		}
		return null;
	}

	/**
	* Add asset item into current workflow
	*
	* $content: string/array
	* $asset_type: = bundle|js|css|img|less|sass|font
	* $content_type_hint: = auto|asset|url|file|inline|raw
	*/
	public function add($content, $asset_type = '', $content_type_hint = 'auto', $params = array()) {
		if (DEBUG_MODE) {
			$trace = main()->trace_string();
		}
		if (!$asset_type) {
// TODO
			$asset_type = 'bundle';
			return false;
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
				$info = null;
				$version_number = null;
				$version_info = null;
				$asset_data = $this->get_asset_details($_content);
// TODO: support for asset config
// TODO: support for asset inherit (for example needed for bootstrap)
				if ($asset_data && is_array($asset_data['versions'])) {
					// Get last version
					$version_arr = array_slice($asset_data['versions'], -1, 1, true);
					$version_number = key($version_arr);
					$version_info = current($version_arr);
					$info = $version_info[$asset_type];
				}
				if ($info) {
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
	* Shortcut
	*/
	public function add_less($content, $content_type_hint = 'auto', $params = array()) {
		return $this->add('less', $content, $content_type_hint, $params);
	}

	/**
	* Shortcut
	*/
	public function add_sass($content, $content_type_hint = 'auto', $params = array()) {
		return $this->add('sass', $content, $content_type_hint, $params);
	}

	/**
	* Shortcut
	*/
	public function add_img($content, $content_type_hint = 'auto', $params = array()) {
// TODO
#		return $this->add('img', $content, $content_type_hint, $params);
	}

	/**
	* Shortcut
	*/
	public function add_font($content, $content_type_hint = 'auto', $params = array()) {
// TODO
#		return $this->add('font', $content, $content_type_hint, $params);
	}

	/**
	* General output/embed method
	*/
	public function show($out_type, $params = array()) {
// TODO
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
	* Shortcut
	*/
	public function show_images() {
		return $this->show('images', $params);
	}

	/**
	* Shortcut
	*/
	public function show_fonts() {
		return $this->show('fonts', $params);
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
	* Search for assets for current module in several places, where it can be stored.
	*/
	public function find_asset_type_for_module($asset_type, $module = '') {
		if (!$module) {
			$module = $_GET['object'];
		}
		$ext = '.'.$asset_type;
		$path = $module. '/'. $module. $ext;
		$paths = array(
			MAIN_TYPE_ADMIN ? YF_PATH. 'templates/admin/'.$path : '',
			YF_PATH. 'templates/user/'.$path,
			MAIN_TYPE_ADMIN ? YF_PATH. 'plugins/'.$module.'/templates/admin/'.$path : '',
			YF_PATH. 'plugins/'.$module.'/templates/user/'.$path,
			MAIN_TYPE_ADMIN ? PROJECT_PATH. 'templates/admin/'.$path : '',
			PROJECT_PATH. 'templates/user/'.$path,
			SITE_PATH != PROJECT_PATH ? SITE_PATH. 'templates/user/'.$path : '',
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
	public function save_to() {
// TODO
	}

	/**
	*/
	public function upload_to() {
// TODO
	}

	/**
	*/
	public function combine() {
// TODO
	}
}
