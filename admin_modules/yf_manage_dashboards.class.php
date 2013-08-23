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
// TODO: add ability to use user module dashboards also
// TODO: implement auto-sizing grid if one of columns is empty
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
	* Designed to be used by other modules to show configured dashboard
	*/
	function display($params = array()) {
		if (is_string($params)) {
			$name = $params;
		}
		if (!is_array($params)) {
			$params = array();
		}
		if (!$params['name'] && $name) {
			$params['name'] = $name;
		}
		if (!$params['name']) {
			return _e('Empty dashboard name');
		}
		$this->_name = $params['name'];
		return $this->view($params);
	}

	/**
	* Similar to "display", but for usage inside this module (action links and more)
	*/
	function view($params = array()) {
		if (!is_array($params)) {
			$params = array();
		}
		$ds_name = isset($params['name']) ? $params['name'] : ($this->_name ? $this->_name : $_GET['id']);
		$dashboard = $this->_get_dashboard_data($ds_name);
		if (!$dashboard['id']) {
			return _e('No such record');
		}
		$replace = array(
			'edit_link'	=> './?object=manage_dashboards&action=edit&id='.$dashboard['id'],
		);
		$items_configs = $dashboard['data']['items_configs'];
		// Fill empty vars first
		foreach (range(1,10) as $n) {
			$replace['items_'.$n] = "";
		}
		foreach ((array)$dashboard['data']['columns'] as $column_id => $column_items) {
			$replace['items_'.$column_id] = $this->_view_widget_items($column_items, $items_configs);
		}
		return tpl()->parse(__CLASS__.'/view_main', $replace);
	}

	/**
	*/
	function _view_widget_items ($name_ids = array(), $items_configs = array()) {
		$list_of_hooks = $this->_get_available_widgets_hooks();

		$_orig_object = $_GET['object'];
		$_orig_action = $_GET['action'];

		foreach ((array)$name_ids as $name_id) {
			$info = $list_of_hooks[$name_id];
			if (!$info) {
				continue;
			}
			list($module_name, $method_name) = explode('::', $info['full_name']);

			$saved_config = $items_configs[$name_id."_".$name_id];

			// This is needed to correctly execute widget (maybe not nicest method, I know...)
			$_GET['object'] = $module_name;
			$_GET['action'] = $module_name;
			$content = module($module_name)->$method_name($saved_config);
			$_GET['object'] = $_orig_object;
			$_GET['action'] = $_orig_action;

			$items[$info['auto_id']] = tpl()->parse(__CLASS__.'/view_item', array(
				'id'		=> $info['auto_id'].'_'.$info['auto_id'],
				'name'		=> _prepare_html($info['name']),
				'desc'		=> $content,
				'has_config'=> $info['configurable'] ? 1 : 0,
				'css_class'	=> $saved_config['color'],
			));
		}
		if (!$items) {
			return '';
		}
		return implode("\n", $items);
	}

	/**
	* This will be showed in side (left) area, catched by hooks functionality
	*/
	function _hook_side_column () {
		if ($_GET['object'] != 'manage_dashboards' || !in_array($_GET['action'], array('edit','add'))) {
			return false;
		}
		$dashboard = $this->_get_dashboard_data();
		if (!$dashboard) {
			return false;
		}
		$avail_hooks = $this->_get_available_widgets_hooks();
		foreach ((array)$dashboard['data']['columns'] as $column_id => $column_items) {
			foreach ((array)$column_items as $auto_id) {
				if (isset($avail_hooks[$auto_id])) {
					unset($avail_hooks[$auto_id]);
				}
			}
		}
		$replace = array(
			'items' 		=> $this->_show_edit_widget_items(array_keys($avail_hooks)),
			'save_link'		=> './?object='.$_GET['object'].'&action=edit&id='.$dashboard['id'],
			'view_link'		=> './?object='.$_GET['object'].'&action=view&id='.$dashboard['id'],
		);
		return tpl()->parse(__CLASS__.'/edit_side', $replace);
	}

	/**
	*/
	function add () {
		if ($_POST) {
			if (!_ee()) {
				db()->insert('dashboards', db()->es(array(
					'name'	=> $_POST['name'],
				)));
				$new_id = db()->insert_id();
				common()->admin_wall_add(array('dashboard added: '.$_POST['name'], $new_id));
				return js_redirect('./?object='.$_GET['object'].'&action=edit&id='.$new_id);
			}
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'],
			'back_link'		=> './?object='.$_GET['object'],
		);
		return form2($replace)
			->text('name')
			->save_and_back();
	}

	/**
	*/
	function edit () {
		$dashboard = $this->_get_dashboard_data($_GET['id']);
		if (!$dashboard['id']) {
			return _e('No such record');
		}
		if ($_POST) {
			if (!_ee()) {
				db()->update('dashboards', db()->es(array(
					'data'	=> json_encode($_POST['ds_data']),
				)), 'id='.intval($dashboard['id']));
				common()->admin_wall_add(array('dashboard updated: '.$dashboard['name'], $_GET['id']));
				return js_redirect('./?object='.$_GET['object'].'&action='.$_GET['object']);
			}
		}
		$replace = array(
			'save_link'	=> './?object='.$_GET['object'].'&action=edit&id='.$dashboard['id'],
		);
		$items_configs = $dashboard['data']['items_configs'];
		// Fill empty vars first
		foreach (range(1,10) as $n) {
			$replace['items_'.$n] = "";
		}
		foreach ((array)$dashboard['data']['columns'] as $column_id => $column_items) {
			$replace['items_'.$column_id] = $this->_show_edit_widget_items($column_items, $items_configs);
		}
		return tpl()->parse(__CLASS__.'/edit_main', $replace);
	}

	/**
	*/
	function _show_edit_widget_items ($column_items = array(), $items_configs = array()) {
		$list_of_hooks = $this->_get_available_widgets_hooks();

		foreach ((array)$column_items as $name_id) {
			$info = $list_of_hooks[$name_id];
			if (!$info) {
				continue;
			}
			$saved_config = $items_configs[$name_id."_".$name_id];
			$items[$info['auto_id']] = tpl()->parse(__CLASS__.'/edit_item', array(
				'id'				=> _prepare_html($info['auto_id'].'_'.$info['auto_id']),
				'name'				=> _prepare_html($info['name']),
				'desc'				=> _prepare_html($info['desc']),
				'has_config'		=> $info['configurable'] ? 1 : 0,
				'css_class'			=> $saved_config['color'],
				'options_container'	=> $this->_options_container($info, $saved_config),
			));
		}
		if (!$items) {
			return '';
		}
		return implode("\n", $items);
	}

	/**
	*/
	function _options_container($info = array(), $saved = array()) {
		$form = form();
		foreach ((array)$info['configurable'] as $k => $v) {
			$form->select_box($k, $v, array('selected' => $saved[$k]));
		}
		return tpl()->parse(__CLASS__.'/ds_options', array(
			'form_items'	=> $form,
			'color'			=> $saved['color'],
			'item_id'		=> _prepare_html($info['auto_id']),
		));
	}

	/**
	*/
	function _get_dashboard_data ($id = "") {
		if (!$id) {
			$id = isset($params['name']) ? $params['name'] : ($this->_name ? $this->_name : $_GET['id']);
		}
		if (!$id) {
			return false;
		}
		if (isset($this->_dashboard_data[$id])) {
			return $this->_dashboard_data[$id];
		}
		$dashboard = db()->get('SELECT * FROM '.db('dashboards').' WHERE name="'.db()->es($id).'" OR id='.intval($id));
		if ($dashboard) {
			$dashboard['data'] = object_to_array(json_decode($dashboard['data']));
		}
		$this->_dashboard_data[$id] = $dashboard;
		return $dashboard;
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
// TODO: add ability to use user module dashboards also
		foreach ((array)module("admin_modules")->_get_methods(array("private" => "1")) as $module_name => $module_methods) {
			foreach ((array)$module_methods as $method_name) {
				if (substr($method_name, 0, strlen($method_prefix)) != $method_prefix) {
					continue;
				}
				$full_name = $module_name.'::'.$method_name;
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
