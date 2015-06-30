<?php

class sample_services {

	/***/
	function _init() {
		_class('core_api')->add_syntax_highlighter();
	}

	/***/
	function _hook_side_column() {
		$items = array();
		$url = url('/@object');
		$methods = get_class_methods(_class('services'));
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
			$items[] = array(
				'name'	=> $name. (!in_array($name, $sample_methods) ? ' <sup class="text-error text-danger"><small>TODO</small></sup>' : ''),
				'link'	=> url('/@object/@action/'.$name), // '#head_'.$name,
			);
		}
		return _class('html')->navlist($items);
	}

	/***/
	function show() {
// TODO
		$docs = _class('docs');
		asset('font-awesome4');
		foreach ($this->_get_services() as $a) {
			$name = $a['name'];
			$sub = array();
			$sub[] = $docs->_github_link($a['path']);
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
}