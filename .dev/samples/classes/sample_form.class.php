<?php

class sample_form {

	/***/
	function _init() {
		_class('core_api')->add_syntax_highlighter();
	}

	/***/
	function _hook_side_column() {
		$items = [];
		$url = url('/@object');
		$methods = get_class_methods(_class('form2'));
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
				'link'	=> '#head_'.$name,
			];
		}
		return _class('html')->navlist($items);
	}

	/***/
	function show() {
		$id = preg_replace('~[^a-z0-9_-]+~ims', '', $_GET['id']);
		$method = preg_replace('~[^a-z0-9_-]+~ims', '', $_GET['page']);
		if (strlen($id)) {
			if (substr($id, 0, strlen('form2_')) !== 'form2_') {
				return _class('docs')->_show_for($this);
			}
			$obj = _class($id, YF_PATH.'.dev/samples/form2/');
			if ($method) {
				return $obj->$method();
			}
			foreach (get_class_methods($obj) as $name) {
				if (substr($name, 0, 1) === '_' || $name === 'show') {
					continue;
				}
				$names[$name] = $name;
			}
			if ($obj && !$names) {
				return $obj->show();
			}
			foreach ($names as $name) {
				$data[$name] = [
					'name'	=> $name,
					'link'	=> url('/@object/@action/@id/'. $name),
				];
			}
			return html()->li($data);
		}
		$ext = '.class.php';
		$ext_len = strlen($ext);
		$globs = [
			'yf_dev'	=> YF_PATH.'.dev/samples/form2/*'.$ext,
#			'app'		=> APP_PATH.'modules/*'.$ext,
#			'project'	=> PROJECT_PATH.'modules/*'.$ext,
		];
		$names = [];
		foreach ($globs as $glob) {
			foreach (glob($glob) as $cls) {
				$cls = basename($cls);
				if ($cls == __CLASS__ || false === strpos($cls, __FUNCTION__)) {
#					continue;
				}
				$name = substr($cls, 0, -$ext_len);
				$names[$name] = $name;
			}
		}
		$links = [];
		foreach ($names as $name) {
			$data[$name] = [
				'name'	=> $name,
				'link'	=> url('/@object/@action/'. $name),
			];
		}
		return html()->li($data);
	}
}