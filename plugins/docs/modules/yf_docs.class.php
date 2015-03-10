<?php

class yf_docs {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

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

		$this->docs_dir = YF_PATH.'.dev/docs/';

		tpl()->add_function_callback('github', function($m, $r, $name, $_this) {
			return _class('core_api')->get_github_link($m[1]);
		});
	}

	/***/
	public function show() {
		if ($_GET['id']) {
			return $this->view();
		}
		return implode(PHP_EOL, array(
			'<h2><a href="'.url('/@object/assets').'">Assets</a></h2>',
			'<h2><a href="'.url('/@object/services').'">Services</a></h2>',
			'<h2><a href="'.url('/@object/form').'">Form</a></h2>',
			'<h2><a href="'.url('/@object/table').'">Table</a></h2>',
			'<h2><a href="'.url('/@object/html').'">Html</a></h2>',
#			'<h2><a href="'.url('/@object/db').'">Database</a></h2>',
			'<h2><a href="'.url('/@object/misc').'">Miscellaneous</a></h2>',
		));
	}

	/***/
	public function assets() {
		asset('font-awesome4');
		foreach ($this->_get_assets() as $asset) {
			$name = $asset['name'];
			$sub = array();
			$sub[] = $this->_github_link($asset['path']);
			$content = $asset['content'];
			$info = is_array($content) ? $content['info'] : array();
			if ($info['name']) {
				$sub[] = '<b>'.t('name').'</b>: '.$info['name'];
			}
			if ($info['desc']) {
				$sub[] = '<b>'.t('desc').'</b>: '.$info['desc'];
			}
			if ($info['url']) {
				$sub[] = '<b>'.t('url').'</b>: <a href="'._prepare_html($info['url']).'">'._prepare_html($info['url']).'</a>';
			}
			if ($info['git']) {
				$sub[] = '<b>'.t('git').'</b>: <a href="'.$info['git'].'">'.$info['git'].'</a>';
			}
			$data[++$i] = array(
				'name'	=> $name,
				'link'	=> url('/@object/@action/#'.$name),
				'sub'	=> $sub,
				'id'	=> $name,
#				'class'	=> 'btn btn-default btn-small btn-sm',
			);
		}
		return _class('html')->li($data);
	}

	/***/
	public function services() {
		asset('font-awesome4');
		foreach ($this->_get_services() as $service) {
			$name = $service['name'];
			$sub = array();
			$sub[] = $this->_github_link($service['path']);
			$content = $service['content'];
			$info = is_array($content) ? $content['info'] : array();
			if ($info['name']) {
				$sub[] = '<b>'.t('name').'</b>: '.$info['name'];
			}
			if ($info['desc']) {
				$sub[] = '<b>'.t('desc').'</b>: '.$info['desc'];
			}
			if ($info['url']) {
				$sub[] = '<b>'.t('url').'</b>: <a href="'._prepare_html($info['url']).'">'._prepare_html($info['url']).'</a>';
			}
			if ($info['git']) {
				$sub[] = '<b>'.t('git').'</b>: <a href="'.$info['git'].'">'.$info['git'].'</a>';
			}
			$data[++$i] = array(
				'name'	=> $name,
				'link'	=> url('/@object/@action/#'.$name),
				'sub'	=> $sub,
				'id'	=> $name,
#				'class'	=> 'btn btn-default btn-small btn-sm',
			);
		}
		return _class('html')->li($data);
	}

	/***/
	public function form() {
// TODO
	}

	/***/
	public function table() {
// TODO
	}

	/***/
	public function html() {
// TODO
	}

	/***/
	public function db() {
// TODO
	}

	/***/
	public function orm() {
// TODO
	}

	/***/
	public function common() {
// TODO
	}

	/***/
	public function main() {
// TODO
	}

	/***/
	public function aliases() {
// TODO
	}

	/***/
	public function fast_init() {
// TODO
	}

	/***/
	public function yf() {
// TODO: console tool docs
	}

	/***/
	public function dir() {
// TODO
	}

	/***/
	public function auth() {
// TODO
	}

	/***/
	public function misc() {
		$dir = $this->docs_dir;
		$dir_len = strlen($dir);
		$ext = '.stpl';
		$ext_len = strlen($ext);

		$name = preg_replace('~[^a-z0-9/_-]+~ims', '', $_GET['id']);
		if (strlen($name)) {
			$f = $dir. $name. '.stpl';
			if (!file_exists($f)) {
				return _404('Not found');
			}
			return '<section class="page-contents">'.tpl()->parse_string(file_get_contents($f), $replace, 'doc_'.$name).'</section>';
		}
		$url = rtrim(url('/@object/@action/')).'/';
		$data = array();
		foreach ((array)_class('dir')->scan($dir) as $path) {
			if (substr($path, -$ext_len) !== $ext) {
				continue;
			}
			$name = substr($path, $dir_len, -$ext_len);
			$data[$name] = array(
				'name'	=> $name,
				'link'	=> $url. urlencode($name),
			);
		}
		ksort($data);
		return _class('html')->li($data);
	}

	/***/
	public function _get_assets() {
		$assets = array();
		$suffix = '.php';
		$dir = 'share/assets/';
		$pattern = $dir. '*'. $suffix;
		$globs = array(
			'yf_main'		=> YF_PATH. $pattern,
			'yf_plugins'	=> YF_PATH. 'plugins/*/'. $pattern,
		);
		$slen = strlen($suffix);
		$names = array();
		foreach($globs as $gname => $glob) {
			foreach(glob($glob) as $path) {
				$name = substr(basename($path), 0, -$slen);
				$names[$name] = $path;
			}
		}
		foreach($names as $name => $path) {
			$assets[$name] = array(
				'name'		=> $name,
				'path'		=> $path,
				'content'	=> include $path,
				'raw'		=> file_get_contents($path),
			);
		}
		return $assets;
	}

	/***/
	public function _get_services() {
		$services = array();
		$suffix = '.php';
		$dir = 'share/services/';
		$pattern = $dir. '*'. $suffix;
		$globs = array(
			'yf_main'		=> YF_PATH. $pattern,
			'yf_plugins'	=> YF_PATH. 'plugins/*/'. $pattern,
		);
		$slen = strlen($suffix);
		$names = array();
		foreach($globs as $gname => $glob) {
			foreach(glob($glob) as $path) {
				$name = substr(basename($path), 0, -$slen);
				$names[$name] = $path;
			}
		}
		foreach($names as $name => $path) {
			if (substr($name, 0, 1) === '_') {
				continue;
			}
			$services[$name] = array(
				'name'	=> $name,
				'path'	=> $path,
				'raw'	=> file_get_contents($path),
			);
		}
		return $services;
	}

	/***/
	public function _github_link($path = '') {
		if (!strlen($path)) {
			return '';
		}
		$path = realpath($path);
		$yf_path = realpath(YF_PATH);
		$yf_len = strlen($yf_path);
		if (substr($path, 0, $yf_len) === $yf_path) {
			$path = substr($path, $yf_len);
		}
		$link = 'https://github.com/yfix/yf/tree/master/'. trim($path, '/');
		return '<i class="fa fa-github fa-lg"></i> <a href="'.$link.'">'.$link.'</a>';
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
