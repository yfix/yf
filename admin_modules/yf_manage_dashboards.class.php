<?php

/**
* Dashboards management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_dashboards {

	/**
	* Framework constructor
	*/
	function _init () {
		$this->_admin_modules = module("admin_modules")->_get_modules();
	}

	/**
	* Designed to be used by other modules to show configured dashboard
	*/
	function display() {
// TODO
	}

	/**
	* Similar to "display", but for usage inside this module (action links and more)
	*/
	function view() {
// TODO
	}

	/**
	*/
	function show() {
		return table2('SELECT * FROM '.db('dashboards'))
			->text('name')
			->btn_view()
			->btn_edit('', '', array('no_ajax' => 1))
			->btn_delete()
			->footer_add()
		;
	}

	/**
	*/
	function add () {
// TODO
	}

	/**
	*/
	function edit () {
		$dashboard = $this->_get_dashboard_data($_GET['id']);
		if (!$dashboard['id']) {
			return _e('No such record');
		}
		if ($_POST) {
// TODO: carefully validate POST data, ensuring it is in correct format
			if (!_ee()) {
				db()->update('dashboards', db()->es(array(
					'name'	=> 'admin_home',
					'data'	=> json_encode($_POST['ds_data']),
				)), 'id='.intval($dashboard['id']));
				return js_redirect('./?object='.$_GET['object'].'&action='.$_GET['object']);
			}
		}
		$replace = array();
		foreach ((array)$dashboard['data'] as $column_id => $name_ids) {
			$replace['items_'.$column_id] = $this->_show_widget_items($name_ids);
		}
		return tpl()->parse(__CLASS__.'/edit_main', $replace);
	}

	/**
	*/
	function _hook_side_column () {
		if ($_GET['object'] != 'manage_dashboards' || !in_array($_GET['action'], array('edit','add'))) {
			return false;
		}
		$dashboard = $this->_get_dashboard_data();
		$avail_hooks = $this->_get_available_widgets_hooks();
		foreach ((array)$dashboard['data'] as $column_id => $name_ids) {
			foreach ((array)$name_ids as $auto_id) {
				unset($avail_hooks[$auto_id]);
			}
		}
		$replace = array(
			'items' 		=> $this->_show_widget_items(array_keys($avail_hooks)),
			'save_link'		=> './?object='.$_GET['object'].'&action=edit',
		);
		return tpl()->parse(__CLASS__.'/edit_side', $replace);
	}

	/**
	*/
	function _get_dashboard_data ($id = "") {
		if (isset($this->_dashboard_data[$id])) {
			return $this->_dashboard_data[$id];
		}
		if (!$id) {
			$id = $_GET['id'];
		}
		if (!$id) {
			return false;
		}
		$dashboard = db()->get('SELECT * FROM '.db('dashboards').' WHERE name="'.db()->es($name).'" OR id='.intval($id));
		if ($dashboard) {
			$dashboard['data'] = (array)json_decode($dashboard['data']);
		}
		$this->_dashboard_data[$id] = $dashboard;
		return $dashboard;
	}

	/**
	*/
	function _show_widget_items ($name_ids = array()) {
		$list_of_hooks = $this->_get_available_widgets_hooks();
		foreach ((array)$name_ids as $name_id) {
			$info = $list_of_hooks[$name_id];
			if (!$info) {
				continue;
			}
			$items[$info['auto_id']] = array(
				'id'		=> $info['auto_id'].'_'.$info['auto_id'],
				'name'		=> _prepare_html($info['name']),
				'desc'		=> _prepare_html($info['desc']),
				'has_config'=> $info['configurable'] ? 1 : 0,
				'config'	=> json_encode($info['configurable']),
			);
		}
		return $items;
	}

	/**
	*/
	function _get_available_widgets_hooks () {
		if (isset($this->_avail_widgets)) {
			return $this->_avail_widgets;
		}
		$method_prefix = "_hook_widget_";
		$r = array(
			'_hook_widget__' => '',
			'_' => '',
			':' => '',
		);
		foreach ((array)module("admin_modules")->_get_methods(array("private" => "1")) as $module_name => $module_methods) {
			foreach ((array)$module_methods as $method_name) {
				if (substr($method_name, 0, strlen($method_prefix)) != $method_prefix) {
					continue;
				}
				$full_name = $module_name."::".$method_name;
				$auto_id = str_replace(array_keys($r), array_values($r), $full_name);
				$widgets[$auto_id] = module($module_name)->$method_name(array('describe_self' => true));
				$widgets[$auto_id]['full_name'] = $full_name;
				$widgets[$auto_id]['auto_id'] = $auto_id;
			}
		}
		ksort($widgets);
		$this->_avail_widgets = $widgets;
		return $widgets;
	}
}
