<?php

class yf_docs {

	/**
	* Catch all methods calls
	*/
	public function _module_action_handler($name) {
		if (method_exists($this, $name)) {
			return $this->$name();
		} else {
			$_GET['id'] = $name;
			return $this->view();
		}
	}

	/**
	*/
	public function _init() {
		_class('core_api')->add_syntax_highlighter();

		$this->docs_dir = YF_PATH.'.dev/docs/en/';

		tpl()->add_function_callback('github', function($m, $r, $name, $_this) {
			return _class('core_api')->get_github_link($m[1]);
		});
	}

	/***/
	public function show() {
		if ($_GET['id']) {
			return $this->view();
		}
		return ''
			. '<h1>Docs</h1>' . $this->_show_docs()
#			. '<h1>Assets</h1>' . $this->assets()
		;
	}

	/***/
	public function view() {
		$name = preg_replace('~[^a-z0-9_-]+~ims', '', $_GET['id']);
		if ($name) {
			$f = $this->docs_dir. $name. '.stpl';
			if (file_exists($f)) {
				return '<section class="page-contents">'.tpl()->parse_string(file_get_contents($f), $replace, 'doc_'.$name).'</section>';
			}
		}
		return _e('Not found');
	}

	/***/
	public function _show_docs() {
		foreach (glob($this->docs_dir.'*.stpl') as $path) {
			$f = basename($path);
			$name = substr($f, 0, -strlen('.stpl'));
			$data[++$i] = array(
				'name'	=> $name,
				'link'	=> url('/@object/show/'.$name),
			);
		}
		return _class('html')->tree($data, array('draggable' => false));
	}

	/***/
	public function assets() {
		$yf_len = strlen(realpath(YF_PATH));
		foreach ($this->_load_predefined_assets() as $asset) {
			$data[++$i] = array(
				'name'	=> $asset['name'],
#				'link'	=> url('/@object/show/'.$name),
				'link'	=> 'https://github.com/yfix/yf/tree/master/'.ltrim(substr(realpath($asset['path']), $yf_len), '/'),
			);
		}
		return _class('html')->navlist($data);
	}

	/***/
	public function _load_predefined_assets() {
		$assets = array();
		$suffix = '.php';
		$dir = 'share/assets/';
		$pattern = $dir. '*'. $suffix;
		$globs = array(
			'yf_main'				=> YF_PATH. $pattern,
			'yf_plugins'			=> YF_PATH. 'plugins/*/'. $pattern,
#			'project_main'			=> PROJECT_PATH. $pattern,
#			'project_app'			=> APP_PATH. $pattern,
#			'project_plugins'		=> PROJECT_PATH. 'plugins/*/'. $pattern,
#			'project_app_plugins'	=> APP_PATH. 'plugins/*/'. $pattern,
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
			$assets[$name] = array(
				'name'		=> $name,
				'path'		=> $path,
				'content'	=> include $path,
			);
		}
		return $assets;
	}

	/***/
	public function _hook_side_column() {
		$url = url('/@object');
		$names = array();
		foreach (array_merge((array)glob(APP_PATH.'modules/*.class.php'),(array)glob(PROJECT_PATH.'modules/*.class.php')) as $cls) {
			$cls = basename($cls);
			if ($cls == __CLASS__) {
				continue;
			}
			$name = substr($cls, 0, -strlen('.class.php'));
			$names[$name] = $name;
		}
		$links = array();
		foreach ($names as $name) {
			$links[url('/'.$name)] = t($name);
		}
		return html()->navlist($links);
	}
}
