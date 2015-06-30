<?php

class sample_misc {

	/***/
	function _init() {
		_class('core_api')->add_syntax_highlighter();
	}

	/***/
	function _hook_side_column() {
/*
		$items = array();
		$url = url('/@object');
		$methods = get_class_methods(_class('utils'));
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
				'link'	=> url('/@object/@action/'.$name),
			);
		}
		return _class('html')->navlist($items);
*/
	}

	/***/
	function show() {
		$docs = _class('docs');
		$dir = $docs->docs_dir;
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
}