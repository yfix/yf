<?php

class sample_assets {

	/***/
	function _init() {
		_class('core_api')->add_syntax_highlighter();
	}

	/***/
	function _hook_side_column() {
		$items = [];
		$url = url('/@object');
		$methods = get_class_methods(_class('assets'));
		$sample_methods = get_class_methods($this);
		sort($methods);
		foreach ((array)$sample_methods as $name) {
			if (in_array($name, $methods)) {
				continue;
			}
			$methods[] = $name;
		}
		foreach ((array)$methods as $name) {
			if ($name == 'show' || substr($name, 0, 1) == '_') {
				continue;
			}
			$items[] = [
				'name'	=> $name. (!in_array($name, $sample_methods) ? ' <sup class="text-error text-danger"><small>TODO</small></sup>' : ''),
				'link'	=> url('/@object/@action/'.$name), // '#head_'.$name,
			];
		}
		return _class('html')->navlist($items);
	}

	/***/
	function demo() {
		$name = preg_replace('~[^a-z0-9_-]+~ims', '', trim($_GET['page'] ?: $_GET['id']));
		if (!$name) {
			return _404();
		}
		$cls_assets = _class($assets);
		$all = $this->_get_assets();
		if (!isset($all[$name])) {
			return _404();
		}
		$content = $all[$name]['content'];
		if (is_callable($content)) {
			$content = $content($cls_assets);
		}
		asset($name);
		if (isset($content['demo'])) {
			$demo = $content['demo'];
			if (is_callable($demo)) {
				$demo = $demo();
			}
			$out[] = $demo;
		}
		$out[] = '<pre><code>'._prepare_html(var_export($content, true)).'</code></pre>';
		return implode('<br>'.PHP_EOL, $out);
	}

	/***/
	function show() {
		if ($_GET['id']) {
			return _class('docs')->_show_for($this);
		}
		$docs = _class('docs');
		asset('font-awesome4');
		foreach ($this->_get_assets() as $a) {
			$name = $a['name'];
			$sub = [];
			$sub[] = $docs->_github_link($a['path']);
			$content = $a['content'];
			$info = is_array($content) ? $content['info'] : [];
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
			$data[$name] = [
				'name'	=> $name,
				'link'	=> url('/@object/@action/demo/'.$name),
				'sub'	=> $sub,
				'id'	=> $name,
#				'class'	=> 'btn btn-default btn-small btn-sm',
			];
		}
		return html()->li($data);
	}

	/***/
	public function _get_assets() {
		$assets = [];
		$suffix = '.php';
		$slen = strlen($suffix);
		$pattern  = '{,plugins/*/}{assets/*,share/assets/*}'. $suffix;
		$globs = [
			'framework' => YF_PATH. $pattern,
#			'project'	=> PROJECT_PATH. $pattern,
#			'app'		=> APP_PATH. $pattern,
		];
		$names = [];
		foreach($globs as $gname => $glob) {
			foreach(glob($glob, GLOB_BRACE) as $path) {
				$name = substr(basename($path), 0, -$slen);
				$names[$name] = $path;
			}
		}
		foreach($names as $name => $path) {
			$assets[$name] = [
				'name'		=> $name,
				'path'		=> $path,
				'content'	=> include $path,
				'raw'		=> file_get_contents($path),
			];
		}
		return $assets;
	}
}