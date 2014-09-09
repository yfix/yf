<?php

class test_core_api {

	/**
	*/
	function _init() {
		_class('core_api')->SOURCE_ONLY_FRAMEWORK = true;
		_class('core_api')->add_syntax_highlighter();
	}

	/**
	*/
	function _hook_side_column() {
		$skip_list = array(
			'get_methods',
			'get_function_source',
			'get_method_source',
			'get_submodule_methods',
		);
		$methods = array();
		foreach (get_class_methods($this) as $name) {
			if ($name[0] == '_' || $name == 'show' || in_array($name, $skip_list)) {
				continue;
			}
			$methods[$name] = array(
				'name'	=> $name,
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
					'link'		=> './?object='.__CLASS__.'&action=get_methods&id='.$_section.'-'.$module,
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
	function get_all_methods($section = 'all', $privacy = '') {
		$data = array();
		$func = 'get_methods';
		if ($privacy == 'public') {
			$func = 'get_public_methods';
		} elseif ($privacy == 'private') {
			$func = 'get_private_methods';
		}
		foreach (_class('core_api')->$func($section) as $module => $methods) {
			$i++;
			$module_id = $i;
			$data[$module_id] = array(
				'name'	=> $module,
				'link'	=> './?object='.__CLASS__.'&action=get_methods&id='.$section.'-'.$module,
			);
			foreach ((array)$methods as $method) {
				$i++;
				$method_id = $i;
				$data[$method_id] = array(
					'name'		=> $method,
					'link'		=> './?object='.__CLASS__.'&action=get_method_source&id='.$section.'-'.$module.'-'.$method,
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
	function get_all_public_methods($section = 'all') {
		return $this->get_all_methods($section, 'public');
	}

	/**
	*/
	function get_all_private_methods($section = 'all') {
		return $this->get_all_methods($section, 'private');
	}

	/**
	*/
	function get_all_properties($section = 'all') {
		$data = array();
		foreach (_class('core_api')->get_properties($section) as $module => $props) {
			$i++;
			$module_id = $i;
			$data[$module_id] = array(
				'name'	=> $module,
#				'link'	=> './?object='.__CLASS__.'&action=get_properties&id='.$section.'-'.$module,
			);
			foreach ((array)$props as $key => $val) {
				$i++;
				$prop_id = $i;
				if (is_object($val) || is_callable($val) || is_resource($val)) {
					$val = '['.gettype($val).']';
				}
				$data[$prop_id] = array(
					'name'		=> $key/*.' = '.$val*/,
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
		list($section, $module) = explode('-', $_GET['id']);
		$section = preg_replace('~[^a-z0-9_]~ims', '', $section);
		$module = preg_replace('~[^a-z0-9_]~ims', '', $module);
		if (!$section || !$module) {
			return _e('Missing required params');
		}
		$all_methods = _class('core_api')->get_methods($section);
		$data = array();
		foreach ((array)$all_methods[$module] as $method) {
			$data[++$i] = array(
				'name'	=> $method,
				'link'	=> './?object='.__CLASS__.'&action=get_method_source&id='.$section.'-'.$module.'-'.$method,
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
					'link'		=> './?object='.__CLASS__.'&action=get_methods&id='.$_section.'-'.$module,
					'parent_id'	=> $section_id,
				);
				foreach ((array)$submodules as $submodule) {
					$i++;
					$submodule_id = $i;
					$data[$submodule_id] = array(
						'name'		=> $submodule,
						'link'		=> './?object='.__CLASS__.'&action=get_submodule_methods&id='.$_section.'-'.$module.'-'.$submodule,
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
	function get_submodule_methods() {
		list($section, $module, $submodule) = explode('-', $_GET['id']);
		$section = preg_replace('~[^a-z0-9_]~ims', '', $section);
		$module = preg_replace('~[^a-z0-9_]~ims', '', $module);
		$submodule = preg_replace('~[^a-z0-9_]~ims', '', $submodule);
		if (!$section || !$module || !$submodule) {
			return _e('Missing required params');
		}
		$methods = _class('core_api')->get_submodule_methods($module, $submodule, $section);
		$data = array();
		foreach ($methods as $name => $info) {
			$data[++$i] = array(
				'name'	=> $name,
				'link'	=> './?object='.__CLASS__.'&action=get_sub_method_source&id=all.'.$module.'-'.$submodule.'-'.$name,
			);
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
					'name'		=> $module.'-'.$method,
					'link'		=> './?object='.__CLASS__.'&action=get_method_source&id=all.'.$module.'-'.$method,
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
					'link'		=> './?object='.__CLASS__.'&action=get_method_source&id=all.'.$module.'-'.$method,
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
	function get_widgets() {
		$data = array();
		foreach (_class('core_api')->get_widgets() as $module => $hooks) {
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
					'link'		=> './?object='.__CLASS__.'&action=get_method_source&id=all.'.$module.'-'.$method,
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
			$data[++$i] = array(
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
			return _e('Missing required params');
		}
		$info = _class('core_api')->get_function_source($name);
		$info['is_func'] = true;
		return _class('core_api')->show_docs($info);
	}

	/**
	*/
	function get_method_source() {
		list($section, $module, $method) = explode('-', $_GET['id']);
		$section = preg_replace('~[^a-z0-9_]~ims', '', $section);
		$module = preg_replace('~[^a-z0-9_]~ims', '', $module);
		$method = preg_replace('~[^a-z0-9_]~ims', '', $method);
		if (!$section || !$module || !$method) {
			return _e('Missing required params');
		}
		$info = _class('core_api')->get_method_source($module, $method, $section);
		$info['is_module'] = $module.'-'.$method;
		return _class('core_api')->show_docs($info);
	}

	/**
	*/
	function get_plugins() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_events() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_callbacks() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_data_handlers() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_tables_fields() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_fast_init() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_libs() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_sites() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_servers() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_langs() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_user_groups() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_admin_groups() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_cron_jobs() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_models() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function get_migrations() {
		return $this->_api_call(__FUNCTION__);
	}

	/**
	*/
	function _api_call($func) {
		$data = array();
		foreach (_class('core_api')->$func() as $name) {
			$data[++$i] = array(
				'name'	=> is_array($name) ? print_r($name, 1) : $name,
			);
		}
		if (!$data) {
			common()->message_info('Empty data');
			return false;
		}
		return _class('html')->tree($data, array(
			'opened_levels'	=> 0,
			'draggable'		=> false,
		));
	}
}
