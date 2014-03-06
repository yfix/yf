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
	* Bootstrap CSS classes used to create configurable grid
	*/
	private $_col_classes = array(
		1 => 'span12 col-md-12 column',
		2 => 'span6 col-md-6 column',
		3 => 'span4 col-md-4 column',
		4 => 'span3 col-md-3 column',
		6 => 'span2 col-md-2 column',
		12 => 'span1 col-md-1 column',
	);

// TODO: add ability to use user module dashboards also

	/**
	*/
	function _init () {
		$this->_auto_info['php_item'] = array(
			'id'			=> 'php_item',
			'name'			=> 'CLONEABLE: php item name',
			'desc'			=> 'CLONEABLE: php item desc',
			'configurable'	=> array(),
			'cloneable'		=> 1,
			'auto_type'		=> 'php_item',
		);
		$this->_auto_info['block_item'] = array(
			'id'			=> 'block_item',
			'name'			=> 'CLONEABLE: block item name',
			'desc'			=> 'CLONEABLE: block item desc',
			'configurable'	=> array(),
			'cloneable'		=> 1,
			'auto_type'		=> 'block_item',
		);
		$this->_auto_info['stpl_item'] = array(
			'id'			=> 'stpl_item',
			'name'			=> 'CLONEABLE: stpl item name',
			'desc'			=> 'CLONEABLE: stpl item desc',
			'configurable'	=> array(),
			'cloneable'		=> 1,
			'auto_type'		=> 'stpl_item',
		);
	}

	/**
	*/
	function show() {
		return table('SELECT * FROM '.db('dashboards'))
			->text('name')
			->text('type')
			->btn_view()
			->btn_edit(array('no_ajax' => 1))
			->btn_clone()
			->btn_delete()
			->btn_active()
			->footer_add()
		;
	}

	/**
	*/
	function delete () {
		$id = $_GET['id'];
		$ds_info = db()->get('SELECT * FROM '.db('dashboards').' WHERE name="'.db()->es($id).'" OR id='.intval($id));
		if (!$ds_info['id']) {
			return _e('No such record');
		}
		$_GET['id'] = $ds_info['id'];
		if (!empty($ds_info['id'])) {
			db()->query('DELETE FROM '.db('dashboards').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			common()->admin_wall_add(array('dashboard deleted: '.$ds_info['name'].'', $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function clone_item() {
		$id = $_GET['id'];
		$ds_info = db()->get('SELECT * FROM '.db('dashboards').' WHERE name="'.db()->es($id).'" OR id='.intval($id));
		if (!$ds_info['id']) {
			return _e('No such record');
		}
		$_GET['id'] = $ds_info['id'];
		$sql = $ds_info;
		unset($sql['id']);
		$sql['name'] = $sql['name'].'_clone';
		db()->insert('dashboards', $sql);
		common()->admin_wall_add( array('dashboard cloned: '.$ds_info['name'], db()->insert_id() ));
		return js_redirect('./?object='.$_GET['object']);
	}

	/**
	*/
	function active () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$ds_info = db()->get('SELECT * FROM '.db('dashboards').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($ds_info['id'])) {
			db()->update('dashboards', array('active' => (int)!$ds_info['active']), 'id='.intval($_GET['id']));
			common()->admin_wall_add(array('dashboard '.$ds_info['name'].' '.($ds_info['active'] ? 'inactivated' : 'activated'), $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($ds_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function add () {
		if (main()->is_post()) {
			if (!_ee()) {
				db()->insert('dashboards', db()->es(array(
					'name'		=> $_POST['name'],
					'type'		=> $_POST['type'],
					'active'	=> $_POST['active'],
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
			->radio_box('type', array('admin' => 'admin', 'user' => 'user'))
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function edit () {
		$ds = $this->_get_dashboard_data($_GET['id']);
		if (!$ds['id']) {
			return _e('No such record');
		}
		if (main()->is_post()) {
			if (!_ee()) {
				db()->update('dashboards', db()->es(array(
					'data'	=> json_encode($_POST['ds_data']),
				)), 'id='.intval($ds['id']));
				common()->admin_wall_add(array('dashboard updated: '.$ds['name'], $_GET['id']));
				return js_redirect('./?object='.$_GET['object'].'&action='.$_GET['object']);
			}
		}
		$items_configs = $ds['data']['items_configs'];
		$ds_settings = $ds['data']['settings'];
		$num_columns = isset($this->_col_classes[$ds_settings['columns']]) ? $ds_settings['columns'] : 3;
		foreach ((array)$ds['data']['columns'] as $column_id => $column_items) {
			$columns[$column_id] = array(
				'num'	=> $column_id,
				'class'	=> $this->_col_classes[$num_columns],
				'items'	=> $this->_show_edit_widget_items($column_items, $ds),
			);
		}
		// Fix empty drag places
		foreach (range(1, $num_columns) as $num) {
			if (!$columns[$num]) {
				$columns[$num] = array(
					'num'	=> $num,
					'class'	=> $this->_col_classes[$num_columns],
					'items'	=> '',
				);
			}
		}
		$replace = array(
			'save_link'	=> './?object='.$_GET['object'].'&action=edit&id='.$ds['id'],
			'columns'	=> $columns,
		);
		return tpl()->parse(__CLASS__.'/edit_main', $replace);
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
	* Similar to 'display', but for usage inside this module (action links and more)
	*/
	function view($params = array()) {
		if (!is_array($params)) {
			$params = array();
		}
		$ds_name = isset($params['name']) ? $params['name'] : ($this->_name ? $this->_name : $_GET['id']);
		$ds = $this->_get_dashboard_data($ds_name);
		if (!$ds['id']) {
			return _e('No such record');
		}
		$items_configs = $ds['data']['items_configs'];
		$ds_settings = $ds['data']['settings'];
		$num_columns = isset($this->_col_classes[$ds_settings['columns']]) ? $ds_settings['columns'] : 3;
		if ($ds_settings['full_width']) {
			$filled_columns = 0;
			foreach ((array)$ds['data']['columns'] as $column_id => $column_items) {
				$empty_items = true;
				foreach ((array)$column_items as $name_id) {
					if ($name_id) {
						$empty_items = false;
						break;
					}
				}
				if (!$empty_items) {
					$filled_columns++;
				}
			}
			$num_columns = $filled_columns;
		}
		foreach ((array)$ds['data']['columns'] as $column_id => $column_items) {
			$columns[$column_id] = array(
				'num'	=> $column_id,
				'class'	=> $this->_col_classes[$num_columns],
				'items'	=> $this->_view_widget_items($column_items, $items_configs, $ds_settings),
			);
		}
		$replace = array(
			'edit_link'	=> './?object=manage_dashboards&action=edit&id='.$ds['id'],
			'columns'	=> $columns,
		);
		return tpl()->parse(__CLASS__.'/view_main', $replace);
	}

	/**
	*/
	function _view_widget_items ($name_ids = array(), $items_configs = array()) {
		$list_of_hooks = $this->_get_available_widgets_hooks();

		$_orig_object = $_GET['object'];
		$_orig_action = $_GET['action'];

		foreach ((array)$name_ids as $name_id) {
			$saved_config = $items_configs[$name_id.'_'.$name_id];
			$info = $list_of_hooks[$name_id];

			$is_cloneable_item = (substr($name_id, 0, strlen('autoid')) == 'autoid');
			if ($is_cloneable_item) {
				$auto_type = $saved_config['auto_type'];
				$info = $this->_auto_info[$auto_type];
				// Merge default settings with saved override
				foreach ((array)$saved_config as $k => $v) {
					if (strlen($v)) {
						$info[$k] = $v;
					}
				}
				$info['auto_id'] = $name_id;
				$info['auto_type'] = $auto_type;
			}
			if (!$info) {
				continue;
			}
			$module_name = '';
			$method_name = '';
			$content = '';
			if ($is_cloneable_item) {
				if ($auto_type == 'php_item') {
					if (strlen($info['code'])) {
						$content = eval('<?'.'php '.$info['code']);
					} elseif ($info['method_name']) {
						list($module_name, $method_name) = explode('.', $info['method_name']);
					}
				} elseif ($auto_type == 'block_item') {
					$content = _class('core_blocks')->show_block(array('name' => $info['block_name']));
				} elseif ($auto_type == 'stpl_item') {
					if (strlen($info['code'])) {
						$content = tpl()->parse_string($info['code']);
					} elseif ($info['stpl_name']) {
						$content = tpl()->parse($info['stpl_name']);
					}
				}
			} else {
				list($module_name, $method_name) = explode('::', $info['full_name']);
			}
			if ($module_name && $method_name) {
				// This is needed to correctly execute widget (maybe not nicest method, I know...)
				$_GET['object'] = $module_name; $_GET['action'] = $module_name;
				$content = module($module_name)->$method_name($saved_config);
				$_GET['object'] = $_orig_object; $_GET['action'] = $_orig_action;
			}

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
		return implode(PHP_EOL, $items);
	}

	/**
	* This will be showed in side (left) area, catched by hooks functionality
	*/
	function _hook_side_column () {
		if ($_GET['object'] != 'manage_dashboards' || !in_array($_GET['action'], array('edit','add'))) {
			return false;
		}
		$ds = $this->_get_dashboard_data();
		if (!$ds) {
			return false;
		}
		$avail_hooks = $this->_get_available_widgets_hooks();
		foreach ((array)$ds['data']['columns'] as $column_id => $column_items) {
			foreach ((array)$column_items as $auto_id) {
				if (isset($avail_hooks[$auto_id])) {
					unset($avail_hooks[$auto_id]);
				}
			}
		}
		$ds_settings = $ds['data']['settings'];
		$auto_items = array();
		foreach((array)$this->_auto_info as $name => $info) {
			$auto_items[$name] = tpl()->parse(__CLASS__.'/edit_item', array(
				'id'				=> _prepare_html($info['id']),
				'name'				=> _prepare_html($info['name']),
				'desc'				=> _prepare_html($info['desc']),
				'has_config'		=> $info['configurable'] ? 1 : 0,
				'css_class'			=> 'drag-clone-needed custom_widget_template_'.$name,
				'options_container'	=> $this->_options_container($info, $auto_saved_config[$name], $ds),
			));
		}
		$replace = array(
			'items' 		=> $this->_show_edit_widget_items(array_keys($avail_hooks)),
			'save_link'		=> './?object='.$_GET['object'].'&action=edit&id='.$ds['id'],
			'view_link'		=> './?object='.$_GET['object'].'&action=view&id='.$ds['id'],
			'settings_items'=> $this->_show_ds_settings_items($ds),
			'auto_items'	=> $auto_items,
		);
		return tpl()->parse(__CLASS__.'/edit_side', $replace);
	}

	/**
	*/
	function _show_ds_settings_items($ds = array()) {
		$settings = $ds['data']['settings'];
		$columns_values = array_combine(array_keys($this->_col_classes), array_keys($this->_col_classes));
		return form()
			->select_box('columns', $columns_values, array('class' => 'no-chosen', 'style'=>'width:auto;', 'selected' => (int)$settings['columns']))
			->yes_no_box('full_width', '', array('selected' => (int)$settings['full_width']))
		;
	}

	/**
	*/
	function _show_edit_widget_items ($column_items = array(), $ds = array()) {
		$items_configs = $ds['data']['items_configs'];
		$ds_settings = $ds['data']['settings'];

		$list_of_hooks = $this->_get_available_widgets_hooks();

		foreach ((array)$column_items as $name_id) {
			$saved_config = $items_configs[$name_id.'_'.$name_id];
			$info = $list_of_hooks[$name_id];

			$is_cloneable_item = (substr($name_id, 0, strlen('autoid')) == 'autoid');
			if ($is_cloneable_item) {
				$auto_type = $saved_config['auto_type'];
				$info = $this->_auto_info[$auto_type];
				// Merge default settings with saved override
				foreach ((array)$saved_config as $k => $v) {
					if (strlen($v)) {
						$info[$k] = $v;
					}
				}
				$info['auto_id'] = $name_id;
				$info['auto_type'] = $auto_type;
			}
			if (!$info) {
				continue;
			}
			$items[$info['auto_id']] = tpl()->parse(__CLASS__.'/edit_item', array(
				'id'				=> _prepare_html($info['auto_id'].'_'.$info['auto_id']),
				'name'				=> _prepare_html($info['name']),
				'desc'				=> _prepare_html($info['desc']),
				'has_config'		=> $info['configurable'] ? 1 : 0,
				'css_class'			=> $saved_config['color'],
				'options_container'	=> $this->_options_container($info, $saved_config, $ds),
			));
		}
		if (!$items) {
			return '';
		}
		return implode(PHP_EOL, $items);
	}

	/**
	*/
	function _options_container($info = array(), $saved = array(), $ds = array()) {
		$for_section = $ds['type'] == 'user' ? 'user' : 'admin';

		$a = array();
		if ($info['cloneable']) {
			$a[] = array('text', 'name', array('class' => 'input-medium'));
			$a[] = array('text', 'desc', 'Description', array('class' => 'input-medium'));
			if ($info['auto_type'] == 'php_item') {
				$a[] = array('text', 'method_name', 'Custom class method');
			} elseif ($info['auto_type'] == 'block_item') {
				$a[] = array('select_box', 'block_name', main()->get_data('blocks_names_'.$for_section));
			} elseif ($info['auto_type'] == 'stpl_item') {
				$a[] = array('text', 'stpl_name', 'Custom template');
			}
#			$a[] = array('text', 'html_id', array('class' => 'input-medium'));
#			$a[] = array('textarea', 'code');
		}
		$a[] = array('check_box', 'hide_header', '1', array('no_label' => 1));
		$a[] = array('check_box', 'hide_border', '1', array('no_label' => 1));
		$a[] = array('text', 'grid_class', array('class' => 'input-small'));
		$a[] = array('text', 'offset_class', array('class' => 'input-small'));
		foreach ((array)$info['configurable'] as $k => $v) {
			$a[] = array('select_box', $k, $v);
		}
		return tpl()->parse(__CLASS__.'/ds_options', array(
			'form_items'	=> form($saved, array('class' => 'form-horizontal form-condensed'))->array_to_form($a),
			'color'			=> $saved['color'],
			'item_id'		=> _prepare_html($info['auto_id']),
			'auto_type'		=> $info['cloneable'] ? $info['auto_type'] : '',
		));
	}

	/**
	*/
	function _get_dashboard_data ($id = '') {
		if (!$id) {
			$id = isset($params['name']) ? $params['name'] : ($this->_name ? $this->_name : $_GET['id']);
		}
		if (!$id) {
			return false;
		}
		if (isset($this->_dashboard_data[$id])) {
			return $this->_dashboard_data[$id];
		}
		$ds = db()->get('SELECT * FROM '.db('dashboards').' WHERE name="'.db()->es($id).'" OR id='.intval($id));
		if ($ds) {
			$ds['data'] = object_to_array(json_decode($ds['data']));
		}
		$this->_dashboard_data[$id] = $ds;
		return $ds;
	}

	/**
	*/
	function _get_available_widgets_hooks_user () {
		return $this->_get_available_widgets_hooks('user');
	}

	/**
	*/
	function _get_available_widgets_hooks_admin () {
		return $this->_get_available_widgets_hooks('admin');
	}

	/**
	*/
	function _get_available_widgets_hooks ($for_section = 'admin') {
		if (!in_array($for_section, array('user', 'admin'))) {
			$for_section = 'admin';
		}
		if (isset($this->_avail_widgets[$for_section])) {
			return $this->_avail_widgets[$for_section];
		}
		$method_prefix = '_hook_widget_';
		$r = array(
			'_hook_widget__' => '',
			'_' => '',
			':' => '',
		);
		$_widgets = array();
		if ($for_section == 'admin') {
			$methods = module('admin_modules')->_get_methods(array('private' => '1'));
		} else {
			$methods = module('user_modules')->_get_methods(array('private' => '1'));
		}
		foreach ((array)$methods as $module_name => $module_methods) {
			foreach ((array)$module_methods as $method_name) {
				if (substr($method_name, 0, strlen($method_prefix)) != $method_prefix) {
					continue;
				}
				$full_name = $module_name.'::'.$method_name;
				$_widgets[$module_name][$method_name] = $full_name;
			}
		}
		foreach ((array)$_widgets as $module_name => $module_widgets) {
			foreach ((array)$module_widgets as $method_name => $full_name) {
				$auto_id = str_replace(array_keys($r), array_values($r), $full_name);
				$widgets[$auto_id] = module_safe($module_name)->$method_name(array('describe_self' => true));
				if (!$widgets[$auto_id]['name']) {
unset($widgets[$auto_id]);
continue;
//					$widgets[$auto_id]['name'] = 'TODO: '.str_replace('_', ' ', substr($method_name, strlen($method_prefix)));
					$widgets[$auto_id]['name'] = str_replace('_', ' ', substr($method_name, strlen($method_prefix)));
				}
				if (!$widgets[$auto_id]['desc']) {
//					$widgets[$auto_id]['name'] = $module_name.':'.str_replace('_', ' ', substr($method_name, strlen($method_prefix)));
					$widgets[$auto_id]['name'] = 'TODO: '.str_replace('_', ' ', substr($method_name, strlen($method_prefix)));
				}
				$widgets[$auto_id]['full_name'] = $full_name;
				$widgets[$auto_id]['auto_id'] = $auto_id;
			}
		}
		ksort($widgets);
		$this->_avail_widgets[$for_section] = $widgets;
		return $widgets;
	}

	/**
	*/
	function _hook_widget__dashboards_stats ($params = array()) {
// TODO
	}

	/**
	*/
	function _hook_widget__dashboards_list ($params = array()) {
// TODO
	}

	/**
	*/
	function _hook_settings(&$selected = array()) {
/*
		return array(
			array('yes_no_box', 'admin_home__DISPLAY_STATS'),
		);
*/
	}
}
