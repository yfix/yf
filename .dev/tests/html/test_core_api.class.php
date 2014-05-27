<?php

class test_core_api {

	/**
	*/
	function _hook_side_column() {
		$methods = array();
		foreach (get_class_methods($this) as $name) {
			if ($name[0] == '_' || $name == 'show') {
				continue;
			}
			$methods[$name] = array(
				'name'	=> $name,
#				'link'	=> url('/'.__CLASS__.'/'.$name),
				'link'	=> './?object='.__CLASS__.'&action='.$name,
			);
		}
		return _class('html')->navlist($methods);
	}

	/**
	*/
	function show() {
		return $this->get_all_classes();
	}

	/**
	*/
	function get_all_classes($section = 'all') {
		$data = array();
		foreach (_class('core_api')->get_classes($section) as $_section => $modules) {
			$i++;
			$section_id = $i;
			$data[$section_id] = array(
				'name'	=> $_section,
			);
			foreach ((array)$modules as $module) {
				$i++;
				$module_id = $i;
				$data[$module_id] = array(
					'name'		=> $module,
					'link'		=> './?object='.__CLASS__.'&action=get_methods&id='.$_section.'.'.$module,
					'parent_id'	=> $section_id,
				);
			}
		}
		return _class('html')->tree($data, array(
			'opened_levels'	=> 1,
			'draggable'		=> false,
		));
	}

	/**
	*/
	function get_all_methods($section = 'all') {
		$data = array();
		foreach (_class('core_api')->get_methods($section) as $module => $methods) {
			$i++;
			$module_id = $i;
			$data[$module_id] = array(
				'name'	=> $module,
				'link'	=> './?object='.__CLASS__.'&action=get_methods&id='.$section.'.'.$module,
			);
			foreach ((array)$methods as $method) {
				$i++;
				$method_id = $i;
				$data[$method_id] = array(
					'name'		=> $method,
					'link'		=> './?object='.__CLASS__.'&action=get_method_source&id='.$section.'.'.$module.'.'.$method,
					'parent_id'	=> $module_id,
				);
			}
		}
		return _class('html')->tree($data, array(
			'opened_levels'	=> 0,
			'draggable'		=> false,
		));
	}

	/**
	*/
	function get_methods() {
		list($section, $module) = explode('.', $_GET['id']);
		$section = preg_replace('~[^a-z0-9_]~ims', '', $section);
		$module = preg_replace('~[^a-z0-9_]~ims', '', $module);
		if (!$section || !$module) {
			return js_redirect('./?object='.__CLASS__.'&action=get_all_methods');
		}
		$all_methods = _class('core_api')->get_methods($section);
		$data = array();
		foreach ((array)$all_methods[$module] as $method) {
			$data[] = array(
				'name'	=> $method,
				'link'	=> './?object='.__CLASS__.'&action=get_method_source&id='.$section.'.'.$module.'.'.$method,
			);
		}
		return _class('html')->tree($data, array(
			'opened_levels'	=> 0,
			'draggable'		=> false,
		));
	}

	/**
	*/
	function get_all_submodules($section = 'all') {
		$data = array();
		$submodules = (array)_class('core_api')->get_submodules($section);
		foreach ($submodules as $_section => $modules) {
			$i++;
			$section_id = $i;
			$data[$section_id] = array(
				'name'	=> $_section,
			);
			foreach ((array)$modules as $module => $submodules) {
				$i++;
				$module_id = $i;
				$data[$module_id] = array(
					'name'		=> $module,
					'link'		=> './?object='.__CLASS__.'&action=get_methods&id='.$_section.'.'.$module,
					'parent_id'	=> $section_id,
				);
				foreach ((array)$submodules as $submodule) {
					$i++;
					$submodule_id = $i;
					$data[$submodule_id] = array(
						'name'		=> $submodule,
						'link'		=> './?object='.__CLASS__.'&action=get_submodule_methods&id='.$_section.'.'.$module.'.'.$submodule,
						'parent_id'	=> $module_id,
					);
				}
			}
		}
		return _class('html')->tree($data, array(
			'opened_levels'	=> 1,
			'draggable'		=> false,
		));
	}

	/**
	*/
	function get_available_hooks() {
		$data = array();
		foreach (_class('core_api')->get_available_hooks() as $name => $hooks) {
			$i++;
			$hook_id = $i;
			$data[$hook_id] = array(
				'name'	=> $name,
				'link'	=> './?object='.__CLASS__.'&action=get_methods&id=all.'.$module,
			);
			foreach ((array)$hooks as $module => $method) {
				$i++;
				$method_id = $i;
				$data[$method_id] = array(
					'name'		=> $module.'.'.$method,
					'link'		=> './?object='.__CLASS__.'&action=get_method_source&id=all.'.$module.'.'.$method,
					'parent_id'	=> $hook_id,
				);
			}
		}
		return _class('html')->tree($data, array(
			'opened_levels'	=> 1,
			'draggable'		=> false,
		));
	}

	/**
	*/
	function get_hooks() {
		$data = array();
		foreach (_class('core_api')->get_all_hooks() as $module => $hooks) {
			$i++;
			$module_id = $i;
			$data[$module_id] = array(
				'name'	=> $module,
				'link'	=> './?object='.__CLASS__.'&action=get_methods&id=all.'.$module,
			);
			foreach ((array)$hooks as $name => $method) {
				$i++;
				$method_id = $i;
				$data[$method_id] = array(
					'name'		=> $method,
					'link'		=> './?object='.__CLASS__.'&action=get_method_source&id=all.'.$module.'.'.$method,
					'parent_id'	=> $module_id,
				);
			}
		}
		return _class('html')->tree($data, array(
			'opened_levels'	=> 0,
			'draggable'		=> false,
		));
	}

	/**
	*/
	function get_functions() {
		$data = array();
		foreach (_class('core_api')->get_functions() as $name) {
			$data[] = array(
				'name'	=> $name,
				'link'	=> './?object='.__CLASS__.'&action=get_function_source&id='.$name,
			);
		}
		return _class('html')->tree($data, array(
			'opened_levels'	=> 0,
			'draggable'		=> false,
		));
	}

	/**
	*/
	function get_function_source() {
		$name = preg_replace('~[^a-z0-9_]~ims', '', $_GET['id']);
		if (!$name) {
			return js_redirect('./?object='.__CLASS__.'&action=get_functions');
		}
		$info = _class('core_api')->get_function_source($name);
		return _var_dump($info);
	}

	/**
	*/
	function get_submodule_methods() {
		list($section, $module, $submodule) = explode('.', $_GET['id']);
		$section = preg_replace('~[^a-z0-9_]~ims', '', $section);
		$module = preg_replace('~[^a-z0-9_]~ims', '', $module);
		$submodule = preg_replace('~[^a-z0-9_]~ims', '', $submodule);
		if (!$section || !$module || !$submodule) {
			return js_redirect('./?object='.__CLASS__.'&action=get_submodules');
		}
// TODO
	}

	/**
	*/
	function get_method_source() {
		list($section, $module, $method) = explode('.', $_GET['id']);
		$section = preg_replace('~[^a-z0-9_]~ims', '', $section);
		$module = preg_replace('~[^a-z0-9_]~ims', '', $module);
		$method = preg_replace('~[^a-z0-9_]~ims', '', $method);
		if (!$section || !$module || !$method) {
			return js_redirect('./?object='.__CLASS__.'&action=get_all_methods');
		}
// TODO
	}
}
