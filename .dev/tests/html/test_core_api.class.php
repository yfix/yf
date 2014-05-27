<?php

class test_core_api {
	function show() { }
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
	function get_all_classes($section = 'all') {
		$data = array();
		foreach (_class('core_api')->get_classes($section) as $section => $modules) {
			$i++;
			$section_id = $i;
			$data[$section_id] = array(
				'name'	=> $section,
			);
			foreach ((array)$modules as $module) {
				$i++;
				$module_id = $i;
				$data[$module_id] = array(
					'name'		=> $module,
					'parent_id'	=> $section_id,
				);
			}
		}
		return _class('html')->tree($data, array(
			'opened_levels'	=> 1,
			'draggable'		=> false,
		));
	}
	function get_core_classes() {
		return $this->get_all_classes('core');
	}
	function get_modules_user() {
		return $this->get_all_classes('user');
	}
	function get_modules_admin() {
		return $this->get_all_classes('admin');
	}
	function get_all_methods($section = 'all') {
		$data = array();
		foreach (_class('core_api')->get_methods($section) as $module => $methods) {
			$i++;
			$module_id = $i;
			$data[$module_id] = array(
				'name'	=> $module,
			);
			foreach ((array)$methods as $method) {
				$i++;
				$method_id = $i;
				$data[$method_id] = array(
					'name'		=> $method,
					'parent_id'	=> $module_id,
				);
			}
		}
		return _class('html')->tree($data, array(
			'opened_levels'	=> 0,
			'draggable'		=> false,
		));
	}
	function get_core_methods() {
		return $this->get_all_methods('core');
	}
	function get_user_methods() {
		return $this->get_all_methods('user');
	}
	function get_admin_methods() {
		return $this->get_all_methods('admin');
	}
	function get_all_submodules($section = 'all') {
		$data = array();
		$submodules = (array)_class('core_api')->get_submodules($section);
		foreach ($submodules as $section => $modules) {
			$i++;
			$section_id = $i;
			$data[$section_id] = array(
				'name'	=> $section,
			);
			foreach ((array)$modules as $module => $submodules) {
				$i++;
				$module_id = $i;
				$data[$module_id] = array(
					'name'		=> $module,
					'parent_id'	=> $section_id,
				);
				foreach ((array)$submodules as $submodule) {
					$i++;
					$submodule_id = $i;
					$data[$submodule_id] = array(
						'name'		=> $submodule,
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
	function get_submodules_core() {
		return $this->get_all_submodules('core');
	}
	function get_submodules_user() {
		return $this->get_all_submodules('user');
	}
	function get_submodules_admin() {
		return $this->get_all_submodules('admin');
	}
}
