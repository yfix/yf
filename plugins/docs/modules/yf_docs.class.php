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
			return $this->misc();
		}
		$a = array();
		foreach (get_class_methods($this) as $m) {
			if ($m[0] === '_' || $m === __FUNCTION__) {
				continue;
			}
			$a[$m] = '<h2><a href="'.url('/@object/'.$m).'">'.ucfirst($m).'</a></h2>';
		}
		return implode(PHP_EOL, $a);
	}

	/***/
	public function assets() {
		asset('font-awesome4');
		foreach ($this->_get_assets() as $a) {
			$name = $a['name'];
			$sub = array();
			$sub[] = $this->_github_link($a['path']);
			$content = $a['content'];
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
			$data[$name] = array(
				'name'	=> $name,
				'link'	=> url('/@object/@action/#'.$name),
				'sub'	=> $sub,
				'id'	=> $name,
#				'class'	=> 'btn btn-default btn-small btn-sm',
			);
		}
		return html()->li($data);
	}

	/***/
	public function services() {
		asset('font-awesome4');
		foreach ($this->_get_services() as $a) {
			$name = $a['name'];
			$sub = array();
			$sub[] = $this->_github_link($a['path']);
			$content = $a['content'];
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
			$data[$name] = array(
				'name'	=> $name,
				'link'	=> url('/@object/@action/#'.$name),
				'sub'	=> $sub,
				'id'	=> $name,
#				'class'	=> 'btn btn-default btn-small btn-sm',
			);
		}
		return html()->li($data);
	}

	/***/
	public function form() {
		$id = preg_replace('~[^a-z0-9_-]+~ims', '', $_GET['id']);
		if (strlen($id)) {
			return _class($id, YF_PATH.'.dev/samples/form2/')->show();
		}
		$ext = '.class.php';
		$ext_len = strlen($ext);
		$globs = array(
			'yf_dev'	=> YF_PATH.'.dev/samples/form2/*'.$ext,
#			'app'		=> APP_PATH.'modules/*'.$ext,
#			'project'	=> PROJECT_PATH.'modules/*'.$ext,
		);
		$names = array();
		foreach ($globs as $glob) {
			foreach (glob($glob) as $cls) {
				$cls = basename($cls);
				if ($cls == __CLASS__ || false === strpos($cls, __FUNCTION__)) {
					continue;
				}
				$name = substr($cls, 0, -$ext_len);
				$names[$name] = $name;
			}
		}
		$links = array();
		foreach ($names as $name) {
			$data[$name] = array(
				'name'	=> $name,
				'link'	=> url('/@object/@action/'. $name),
			);
		}
		return html()->li($data);
	}

	/***/
	public function table() {
		$id = preg_replace('~[^a-z0-9_-]+~ims', '', $_GET['id']);
		if (strlen($id)) {
			return _class($id, YF_PATH.'.dev/samples/table2/')->show();
		}
		$ext = '.class.php';
		$ext_len = strlen($ext);
		$globs = array(
			'yf_dev'	=> YF_PATH.'.dev/samples/table2/*'.$ext,
#			'app'		=> APP_PATH.'modules/*'.$ext,
#			'project'	=> PROJECT_PATH.'modules/*'.$ext,
		);
		$names = array();
		foreach ($globs as $glob) {
			foreach (glob($glob) as $cls) {
				$cls = basename($cls);
				if ($cls == __CLASS__ || false === strpos($cls, __FUNCTION__)) {
					continue;
				}
				$name = substr($cls, 0, -$ext_len);
				$names[$name] = $name;
			}
		}
		$links = array();
		foreach ($names as $name) {
			$data[$name] = array(
				'name'	=> $name,
				'link'	=> url('/@object/@action/'. $name),
			);
		}
		return html()->li($data);
	}

	/***/
	public function html() {
		return _class('test_html', YF_PATH.'.dev/samples/classes/')->show();
	}

	/***/
#	public function db() {
// TODO
#	}

	/***/
#	public function orm() {
// TODO
#	}

	/***/
#	public function common() {
// TODO
#	}

	/***/
#	public function main() {
// TODO
#	}

	/***/
#	public function aliases() {
// TODO
#	}

	/***/
#	public function fast_init() {
// TODO
#	}

	/***/
#	public function yf() {
// TODO: console tool docs
#	}

	/***/
#	public function dir() {
// TODO
#	}

	/***/
#	public function auth() {
// TODO
#	}

	/***/
	public function misc() {
		$dir = $this->docs_dir;
		$dir_len = strlen($dir);
		$ext = '.stpl';
		$ext_len = strlen($ext);

		$name = preg_replace('~[^a-z0-9/_-]+~ims', '', $_GET['id']);
		if (strlen($name)) {
			$dev_path = YF_PATH.'.dev/samples/classes/';
			$dev_class_path = $dev_path. $name. '.class.php';
			if (file_exists($dev_class_path)) {
				return _class($name, $dev_path)->show();
			}
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
		return html()->li($data);
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

		$ext = '.class.php';
		$ext_len = strlen($ext);
		$globs = array(
			'yf_dev_form2'		=> YF_PATH.'.dev/samples/form2/*'.$ext,
			'yf_dev_table2'		=> YF_PATH.'.dev/samples/table2/*'.$ext,
			'yf_dev_classes'	=> YF_PATH.'.dev/samples/classes/*'.$ext,
#			'app'		=> APP_PATH.'modules/*'.$ext,
#			'project'	=> PROJECT_PATH.'modules/*'.$ext,
		);
		$names = array();
		foreach ($globs as $glob) {
			foreach (glob($glob) as $cls) {
				$cls = basename($cls);
				if ($cls == __CLASS__) {
					continue;
				}
				$name = substr($cls, 0, -$ext_len);
				$names[$name] = $name;
			}
		}
		$links = array();
		$links[url('/@object/assets')] = t('assets');
		$links[url('/@object/services')] = t('services');
		$links[url('/@object/misc')] = t('misc');
		foreach ($names as $name) {
			$url = '/';
			if (substr($name, 0, strlen('table2_')) === 'table2_') {
				$url = '/@object/table/'. $name;
			} elseif (substr($name, 0, strlen('form2_')) === 'form2_') {
				$url = '/@object/form/'. $name;
			} elseif (substr($name, 0, strlen('test_html')) === 'test_html') {
				$url = '/@object/html/';
			} else {
				$url = '/@object/misc/'. $name;
			}
			$links[url($url)] = t($name);
		}
		return html()->navlist($links);
	}
}
