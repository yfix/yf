<?php

/**
* Menu's editor
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_menus_editor {

	/** @var string Path to icons */
	public $ICONS_PATH = 'uploads/icons/';

	/**
	* Constructor
	*/
	function _init () {
		$this->_boxes = array(
			'menu_type'		=> 'radio_box("type",			$this->_menu_types,			$selected, false, 2, "", false)',
			'active'		=> 'radio_box("active",			$this->_statuses,			$selected, false, 2, "", false)',
			'type_id'		=> 'select_box("type_id",		$this->_item_types,			$selected, false, 2, "", false)',
			'parent_id'		=> 'select_box("parent_id",		$this->_items_for_parent,	$selected, false, 2, "", false)',
			'item_order'	=> 'select_box("item_order",	$this->_item_orders,		$selected, false, 2, "", false)',
			'methods'		=> 'select_box("methods",		$this->_methods,			$selected, false, 2, "", false)',
			'groups'		=> 'multi_select("groups",		$this->_groups,				$selected, false, 2, " size=5 class=small_for_select ", false)',
			'site_ids'		=> 'multi_select("site_ids",	$this->_sites,				$selected, false, 2, " size=5 class=small_for_select ", false)',
			'server_ids'	=> 'multi_select("server_ids",	$this->_servers,			$selected, false, 2, " size=5 class=small_for_select ", false)',
		);
		$this->_menu_types = array(
			'user'	=> 'user',
			'admin'	=> 'admin',
		);
		$this->_statuses = array(
			'0' => '<span class="negative">NO</span>',
			'1' => '<span class="positive">YES</span>',
		);
		$this->_item_types = array(
			1 => t('Internal link'),
			2 => t('External link'),
			3 => t('Spacer'),
		);
		$this->_user_modules = main()->_execute('user_modules', '_get_modules');
		$this->_user_modules_methods = main()->_execute('user_modules', '_get_methods');
		$this->_user_methods[''] = '-- ALL --';
		foreach ((array)$this->_user_modules_methods as $module_name => $module_methods) {
			$this->_user_methods['object='.$module_name] = $module_name.' -> -- ALL --';
			foreach ((array)$module_methods as $method_name) {
				if ($method_name == $module_name) {
					continue;
				}
				$this->_user_methods['object='.$module_name.'&action='.$method_name] = _prepare_html($module_name.' -> '.$method_name);
			}
		}
		$this->_user_groups[''] = '-- ALL --';
		$Q = db()->query('SELECT id,name FROM '.db('user_groups').' WHERE active="1"');
		while ($A = db()->fetch_assoc($Q)) {
			$this->_user_groups[$A['id']] = $A['name'];
		}
		$this->_admin_modules = main()->_execute('admin_modules', '_get_modules');
		$this->_admin_modules_methods = main()->_execute('admin_modules', '_get_methods');
		$this->_admin_methods[''] = '-- ALL --';
		foreach ((array)$this->_admin_modules_methods as $module_name => $module_methods) {
			$this->_admin_methods['object='.$module_name] = $module_name.' -> -- ALL --';
			foreach ((array)$module_methods as $method_name) {
				if ($method_name == $module_name) {
					continue;
				}
				$this->_admin_methods['object='.$module_name.'&action='.$method_name] = _prepare_html($module_name.' -> '.$method_name);
			}
		}
		$this->_admin_groups[''] = '-- ALL --';
		$Q = db()->query('SELECT id,name FROM '.db('admin_groups').' WHERE active="1"');
		while ($A = db()->fetch_assoc($Q)) {
			$this->_admin_groups[$A['id']] = $A['name'];
		}
		$this->_sites = array(
			'' => '-- ALL --',
		);
		$Q = db()->query('SELECT id,name FROM '.db('sites').' WHERE active="1"');
		while ($A = db()->fetch_assoc($Q)) {
			$this->_sites[$A['id']] = $A['name'];
		}
		$this->_servers = array(
			'' => '-- ALL --',
		);
		$Q = db()->query('SELECT id,name FROM '.db('core_servers').' WHERE active="1"');
		while ($A = db()->fetch_assoc($Q)) {
			$this->_servers[$A['id']] = $A['name'];
		}
	}

	/**
	* Display menus blocks
	*/
	function show() {
		$q = db()->query('SELECT m.id, COUNT(i.id) AS num FROM '.db('menus').' AS m LEFT JOIN '.db('menu_items').' AS i ON m.id = i.menu_id GROUP BY m.id');
		while ($a = db()->fetch_assoc($q)) {
			$num_items[$a['id']] = $a['num'];
		}
		return common()->table2('SELECT * FROM '.db('menus').' ORDER BY type DESC')
			->link('name', './?object='.$_GET['object'].'&action=show_items&id=%d')
			->text('id', 'Num Items', array('data' => $num_items))
			->text('type')
			->text('stpl_name')
			->text('method_name')
			->btn('Items', './?object='.$_GET['object'].'&action=show_items&id=%d')
			->btn_edit()
			->btn_delete()
			->btn_clone('', './?object='.$_GET['object'].'&action=clone_menu&id=%d')
			->btn('Export', './?object='.$_GET['object'].'&action=export&id=%d')
			->btn_active()
			->footer_add();
	}

	/**
	* Add new menu block
	*/
	function add() {
		if ($_POST) {
			if (!common()->_error_exists()) {
				db()->INSERT('menus', array(
					'name'			=> _es($_POST['name']),
					'desc'			=> _es($_POST['desc']),
					'stpl_name'		=> _es($_POST['stpl_name']),
					'method_name'	=> _es($_POST['method_name']),
					'active'		=> (int)((bool)$_POST['active']),
					'type'			=> _es($_POST['type']),
				));
				common()->admin_wall_add(array('menu added: '.$_POST['name'].'', db()->insert_id()));
				cache()->refresh('menus');
				return js_redirect('./?object='.$_GET['object']);
			}
		}
		foreach ((array)$menu_info as $k => $v) {
			$DATA[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'],
			'name'			=> _prepare_html($DATA['name']),
			'desc'			=> _prepare_html($DATA['desc']),
			'type'			=> _prepare_html($DATA['type']),
			'stpl_name'		=> _prepare_html($DATA['stpl_name']),
			'method_name'	=> _prepare_html($DATA['method_name']),
			'active_box'	=> $this->_box('active', $DATA['active']),
			'menu_type_box'	=> $this->_box('menu_type', $DATA['type']),
			'active'		=> $DATA['active'],
			'back_link'		=> './?object='.$_GET['object'].'&action=show',
			'for_edit'		=> 0,
			'modules_link'	=> './?object='.($DATA['type'] ? $DATA['type'] : 'user').'_modules',
			'stpls_link'	=> './?object=template_editor',
		);
		return tpl()->parse($_GET['object'].'/edit_menu', $replace);
	}

	/**
	* Edit menu block
	*/
	function edit() {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e(t('No id!'));
		}
		$menu_info = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($_GET['id']));
		if (empty($menu_info['id'])) {
			return _e(t('No such menu!'));
		}
		if ($_POST) {
			if (!common()->_error_exists()) {
				db()->UPDATE('menus', array(
					'name'			=> _es($_POST['name']),
					'desc'			=> _es($_POST['desc']),
					'stpl_name'		=> _es($_POST['stpl_name']),
					'method_name'	=> _es($_POST['method_name']),
					'active'		=> (int)((bool)$_POST['active']),
				), 'id='.intval($_GET['id']));
				common()->admin_wall_add(array('menu updated: '.$_POST['name'].'', $menu_info['id']));
				cache()->refresh('menus');
				return js_redirect('./?object='.$_GET['object']);
			}
		}
		foreach ((array)$menu_info as $k => $v) {
			$DATA[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
		}
		$methods_for_select = $block_info['type'] == 'admin' ? $this->_admin_methods : $this->_user_methods;
		if (isset($methods_for_select[''])) {
			unset($methods_for_select['']);
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'name'			=> _prepare_html($DATA['name']),
			'desc'			=> _prepare_html($DATA['desc']),
			'type'			=> _prepare_html($DATA['type']),
			'stpl_name'		=> _prepare_html($DATA['stpl_name']),
			'method_name'	=> _prepare_html($DATA['method_name']),
			'active_box'	=> $this->_box('active', $DATA['active']),
			'active'		=> $DATA['active'],
			'back_link'		=> './?object='.$_GET['object'].'&action=show',
			'for_edit'		=> 1,
			'items_link'	=> './?object='.$_GET['object'].'&action=show_items&id='.$_GET['id'],
			'methods_box'	=> common()->select_box('methods', $methods_for_select, '', true, 2, 'class=small_for_select', false),
			'stpls_box'		=> common()->select_box('stpls', $this->_get_stpls($menu_info['type']), '', true, 2, 'class=small_for_select', false),
			'modules_link'	=> './?object='.$menu_info['type'].'_modules',
			'stpls_link'	=> './?object=template_editor',
		);
		return tpl()->parse($_GET['object'].'/edit_menu', $replace);
	}

	/**
	* Get array of templates for the given init type
	*/
	function _get_stpls ($type = 'user') {
		return module('template_editor')->_get_stpls_for_type($type);
	}

	/**
	* Clone menus block
	*/
	function clone_menu() {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e(t('No id!'));
		}
		$menu_info = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($_GET['id']));
		if (empty($menu_info['id'])) {
			return _e(t('No such menu!'));
		}
		$sql = $menu_info;
		unset($sql['id']);
		$sql['name'] = $sql['name'].'_clone';

		db()->INSERT('menus', $sql);
		$NEW_MENU_ID = db()->INSERT_ID();

		$old_items = $this->_recursive_get_menu_items($menu_info['id']);
		foreach ((array)$old_items as $_id => $_info) {
			unset($_info['id']);
			unset($_info['level']);
			$_info['menu_id'] = $NEW_MENU_ID;

			db()->INSERT('menu_items', $_info);
			$NEW_ITEM_ID = db()->INSERT_ID();

			$_old_to_new[$_id] = $NEW_ITEM_ID;
			$_new_to_old[$NEW_ITEM_ID] = $_id;
		}
		foreach ((array)$_new_to_old as $_new_id => $_old_id) {
			$_old_info = $old_items[$_old_id];
			$_old_parent_id = $_old_info['parent_id'];
			if (!$_old_parent_id) {
				continue;
			}
			$_new_parent_id = intval($_old_to_new[$_old_parent_id]);
			db()->UPDATE('menu_items', array('parent_id' => $_new_parent_id), 'id='.intval($_new_id));
		}
		common()->admin_wall_add(array('menu cloned: '.$menu_info['name'].'', $NEW_ITEM_ID));
		cache()->refresh(array('menus', 'menu_items'));
		return js_redirect('./?object='.$_GET['object'].'&action=edit&id='.intval($NEW_MENU_ID));
	}

	/**
	* Delete menu block and all sub items
	*/
	function delete() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$menu_info = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($menu_info['id'])) {
			db()->query('DELETE FROM '.db('menus').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			db()->query('DELETE FROM '.db('menu_items').' WHERE menu_id='.intval($_GET['id']));
			common()->admin_wall_add(array('menu deleted: '.$menu_info['name'].'', $menu_info['id']));
		}
		cache()->refresh(array('menus', 'menu_items'));
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	* Change menu block activity
	*/
	function active() {
		if (!empty($_GET['id'])) {
			$menu_info = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($menu_info)) {
			db()->UPDATE('menus', array('active' => (int)!$menu_info['active']), 'id='.intval($menu_info['id']));
			common()->admin_wall_add(array('menu: '.$menu_info['name'].' '.($menu_info['active'] ? 'inactivated' : 'activated'), $menu_info['id']));
		}
		cache()->refresh(array('menus', 'menu_items'));
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($menu_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	* Display menu items for the given block
	*/
	function show_items() {
		$menu_info = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($_GET['id']).' OR name="'.db()->es($_GET['id']).'"');
		if (empty($menu_info)) {
			return _e(t('No such menu!'));
		}
		$_GET['id'] = intval($menu_info['id']);

		$menu_items = $this->_auto_update_items_orders($menu_info['id']);
		if ($_POST) {
			$batch = array();
			foreach ((array)$menu_items as $a) {
				if (!isset($_POST['name'][$a['id']])) {
					continue;
				}
				$batch[$a['id']] = array(
					'id'		=> $a['id'],
					'name'		=> $_POST['name'][$a['id']],
					'location'	=> $_POST['location'][$a['id']],
				);
			}
			if ($batch) {
				db()->update_batch('menu_items', db()->es($batch));
				common()->admin_wall_add(array('menu items updated: '.$menu_info['name'].'', $menu_info['id']));
				cache()->refresh(array('menus', 'menu_items'));
			}
			return js_redirect('./?object='.$_GET['object'].'&action=show_items&id='.$_GET['id']);
		}
		return table($menu_items, array('pager_records_on_page' => 10000, 'condensed' => 1))
			->form()
			->icon('icon')
			->input_padded('name')
			->input('location')
			->text('type_id', 'Item type', array('data' => $this->_item_types, 'nowrap' => 1))
			->data_array('user_groups', array(
				'desc' => 'Groups',
				'data' => $menu_info['type'] == 'admin' ? $this->admin_groups : $this->user_groups
			))
			->btn_edit('', './?object='.$_GET['object'].'&action=edit_item&id=%d')
			->btn_delete('', './?object='.$_GET['object'].'&action=delete_item&id=%d')
			->btn_clone('', './?object='.$_GET['object'].'&action=clone_item&id=%d')
			->btn_active('', './?object='.$_GET['object'].'&action=activate_item&id=%d')
			->footer_add('Add item', './?object='.$_GET['object'].'&action=add_item&id='.$_GET['id'], array('copy_to_header' => 1))
			->footer_link('Drag items', './?object='.$_GET['object'].'&action=drag_items&id='.$_GET['id'], array('icon' => 'icon-move', 'copy_to_header' => 1))
			->footer_submit()
		;
	}

	/**
	*/
	function drag_items() {
		if (empty($_GET['id'])) {
			return _e('No id!');
		}
		$menu_info = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($_GET['id']).' OR name="'.db()->es($_GET['id']).'"');
		if (empty($menu_info)) {
			return _e('No such menu!');
		}
		$items = _class('graphics')->_show_menu(array(
			'force_stpl_name'	=> $_GET['object'].'/drag',
			'name'				=> $menu_info['name'],
			'return_array'		=> 1,
		));
		if ($_POST) {
			$old_info = $this->_auto_update_items_orders($menu_info['id']);
			$batch = array();
			foreach ((array)$_POST['items'] as $order_id => $info) {
				$item_id = (int)$info['item_id'];
				if (!$item_id || !isset($items[$item_id])) {
					continue;
				}
				$parent_id = (int)$info['parent_id'];
				$new_data[$item_id] = array(
					'id'		=> $item_id,
					'order'		=> intval($order_id),
					'parent_id'	=> intval($parent_id),
				);
				$old_info = $cur_items[$item_id];
				$old_data = array(
					'id'		=> $item_id,
					'order'		=> intval($old_info['order']),
					'parent_id'	=> intval($old_info['parent_id']),
				);
				if ($new_data != $old_data) {
					$batch[$item_id] = $new_data[$item_id];
				}
			}
			if ($batch) {
				db()->update_batch('menu_items', db()->es($batch));
				common()->admin_wall_add(array('menu items dragged: '.$menu_info['name'].'', $menu_info['id']));
			}
			main()->NO_GRAPHICS = true;
			return false;
		}
		foreach ((array)$items as $id => $item) {
			$item['edit_link']		= './?object='.$_GET['object'].'&action=edit_item&id='.$id;
			$item['delete_link']	= './?object='.$_GET['object'].'&action=delete_item&id='.$id;
			$item['active_link']	= './?object='.$_GET['object'].'&action=activate_item&id='.$id;
			$item['clone_link']		= './?object='.$_GET['object'].'&action=clone_item&id='.$id;
			$item['active']			= 1;
			$items[$id] = tpl()->parse($_GET['object'].'/drag_item', $item);
		}
		$replace = array(
			'items' 		=> implode(PHP_EOL, (array)$items),
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'add_link'		=> './?object='.$_GET['object'].'&action=add_item&id='.$_GET['id'],
			'back_link'		=> './?object='.$_GET['object'].'&action=show_items&id='.$_GET['id'],
		);
		return tpl()->parse($_GET['object'].'/drag_main', $replace);
	}

	/**
	*/
	function _auto_update_items_orders($menu_id) {
		if (!$menu_id) {
			return false;
		}
		$menu_items = $this->_recursive_get_menu_items($menu_id);
		$new_order = 1;
		$batch = array();
		foreach ((array)$menu_items as $item_id => $info) {
			if ($info['order'] != $new_order) {
				$batch[$item_id] = array(
					'id'	=> $item_id,
					'order' => $new_order,
				);
				$menu_items[$item_id]['order'] = $new_order;
			}
			$new_order++;
		}
		if ($batch) {
			db()->update_batch('menu_items', $batch);
		}
		return $menu_items;
	}

	/**
	* Add new menu item
	*/
	function add_item() {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e(t('No id!'));
		}
		$menu_info = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($_GET['id']));
		if (empty($menu_info['id'])) {
			return _e(t('No such menu!'));
		}
		if ($_POST) {
			db()->INSERT('menu_items', array(
				'menu_id'		=> intval($_GET['id']),
				'type_id'		=> intval($_POST['type_id']),
				'parent_id'		=> intval($_POST['parent_id']),
				'name'			=> _es($_POST['name']),
				'location'		=> _es($_POST['location']),
				'icon'			=> _es($_POST['icon']),
				'user_groups'	=> _es($this->_multi_html_to_db($_POST['groups'])),
				'site_ids'		=> _es($this->_multi_html_to_db($_POST['site_ids'])),
				'server_ids'	=> _es($this->_multi_html_to_db($_POST['server_ids'])),
				'order'			=> intval($_POST['order']),
				'active'		=> intval($_POST['active']),
			));
			common()->admin_wall_add(array('menu item added: '.$_POST['name'].'', db()->insert_id()));
			cache()->refresh(array('menus', 'menu_items'));
			return js_redirect('./?object='.$_GET['object'].'&action=show_items&id='.$menu_info['id']);
		}
		$this->_items_for_parent[0] = '-- TOP --';
		foreach ((array)$this->_recursive_get_menu_items($_GET['id']) as $cur_item_id => $cur_item_info) {
			if (empty($cur_item_id)) {
				continue;
			}
			$this->_items_for_parent[$cur_item_id] = str_repeat('&nbsp;', $cur_item_info['level'] * 6).' &#9492; '.$cur_item_info['name'];
		}
		if ($menu_info['type'] == 'admin') {
			$this->_groups	= $this->_admin_groups;
			$this->_methods = $this->_admin_methods;
		} else {
			$this->_groups	= $this->_user_groups;
			$this->_methods = $this->_user_methods;
		}
		$icon_src = '';
		if ($item_info['icon'] && file_exists(INCLUDE_PATH. $this->ICONS_PATH. $item_info['icon'])) {
			$icon_src = WEB_PATH. $this->ICONS_PATH. $item_info['icon'];
		}
		foreach (array('groups', 'methods', 'site_ids', 'server_ids') as $k) {
			$DATA[$k] = $this->_multi_db_to_html($DATA[$k]);
		}
		$replace = array(
			'form_action'		=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'menu_name'			=> _prepare_html($menu_info['name']),
			'name'				=> _prepare_html($DATA['name']),
			'location'			=> _prepare_html($DATA['location']),
			'icon'				=> _prepare_html($DATA['icon']),
			'order'				=> intval($DATA['order']),
			'type_id_box'		=> $this->_box('type_id',	''),
			'parent_id_box'		=> $this->_box('parent_id', ''),
			'groups_box'		=> $this->_box('groups',	array(''=>'-- ALL --')),
			'methods_box'		=> $this->_box('methods',	''),
			'site_ids_box'		=> $this->_box('site_ids',	''),
			'server_ids_box'	=> $this->_box('server_ids',''),
			'active_box'		=> $this->_box('active', 	$DATA['active']),
			'active'			=> $DATA['active'],
			'back_link'			=> './?object='.$_GET['object'].'&action=show_items&id='.intval($menu_info['id']),
			'for_edit'			=> 0,
			'edit_modules_link'	=> './?object='.$menu_info['type'].'_modules',
			'edit_groups_link'	=> './?object='.$menu_info['type'].'_groups',
			'icons_list_link'	=> './?object='.$_GET['object'].'&action=icons_list',
			'icons_web_path'	=> WEB_PATH. $this->ICONS_PATH,
			'icon_src'			=> $icon_src,
			'edit_menu_link'	=> './?object='.$_GET['object'].'&action=edit&id='.$menu_info['id'],
			'cond_code'			=> _prepare_html($DATA['cond_code'], 0),
			'sites_link'		=> './?object=manage_sites',
			'servers_link'		=> './?object=manage_servers',
		);
		return tpl()->parse($_GET['object'].'/edit_item_form', $replace);
	}

	/**
	* Edit menu item
	*/
	function edit_item() {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e(t('No id!'));
		}
		$item_info = db()->query_fetch('SELECT * FROM '.db('menu_items').' WHERE id='.intval($_GET['id']));
		if (empty($item_info['id'])) {
			return _e(t('No such menu item!'));
		}
		$menu_info = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($item_info['menu_id']));
		if (empty($menu_info['id'])) {
			return _e(t('No such menu!'));
		}
		if ($_POST) {
			db()->UPDATE('menu_items', array(
				'parent_id'		=> intval($_POST['parent_id']),
				'name'			=> _es($_POST['name']),
				'location'		=> _es($_POST['location']),
				'icon'			=> _es($_POST['icon']),
				'user_groups'	=> _es($this->_multi_html_to_db($_POST['groups'])),
				'site_ids'		=> _es($this->_multi_html_to_db($_POST['site_ids'])),
				'server_ids'	=> _es($this->_multi_html_to_db($_POST['server_ids'])),
				'cond_code'		=> _es($_POST['cond_code']),
				'type_id'		=> intval($_POST['type_id']),
				'order'			=> intval($_POST['order']),
				'active'		=> intval($_POST['active']),
			), 'id='.intval($item_info['id']));
			common()->admin_wall_add(array('menu item updated: '.$_POST['name'].'', $item_info['id']));
			cache()->refresh(array('menus', 'menu_items'));
			return js_redirect('./?object='.$_GET['object'].'&action=show_items&id='.$menu_info['id']);
		}
		$this->_items_for_parent[0] = '-- TOP --';
		foreach ((array)$this->_recursive_get_menu_items($menu_info['id'], $_GET['id']) as $cur_item_id => $cur_item_info) {
			if (empty($cur_item_id)) {
				continue;
			}
			$this->_items_for_parent[$cur_item_id] = str_repeat('&nbsp; &nbsp; &nbsp; ', $cur_item_info['level']).' &#9492; &nbsp; '.$cur_item_info['name'];
		}
		$item_info['user_groups']	= explode(',',str_replace(array(' ',"\t","\r","\n"), '', $item_info['user_groups']));
		foreach ((array)$item_info['user_groups'] as $v) {
			$tmp[$v] = $v;
		}
		$item_info['user_groups'] = $tmp;
		if ($menu_info['type'] == 'admin') {
			$this->_groups	= $this->_admin_groups;
			$this->_methods = $this->_admin_methods;
		} else {
			$this->_groups	= $this->_user_groups;
			$this->_methods = $this->_user_methods;
		}
		$icon_src = '';
		if ($item_info['icon'] && file_exists(INCLUDE_PATH. $this->ICONS_PATH. $item_info['icon'])) {
			$icon_src = WEB_PATH. $this->ICONS_PATH. $item_info['icon'];
		}
		foreach (array('groups', 'methods', 'site_ids', 'server_ids') as $k) {
			$item_info[$k] = $this->_multi_db_to_html($item_info[$k]);
		}
		$replace = array(
			'form_action'		=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'menu_name'			=> _prepare_html($menu_info['name']),
			'name'				=> _prepare_html($item_info['name']),
			'location'			=> _prepare_html($item_info['location']),
			'icon'				=> _prepare_html($item_info['icon']),
			'order'				=> intval($item_info['order']),
			'type_id_box'		=> $this->_box('type_id',		$item_info['type_id']),
			'parent_id_box'		=> $this->_box('parent_id', 	$item_info['parent_id']),
			'groups_box'		=> $this->_box('groups',		$item_info['user_groups']),
			'methods_box'		=> $this->_box('methods',		$item_info['methods']),
			'site_ids_box'		=> $this->_box('site_ids',		$item_info['site_ids']),
			'server_ids_box'	=> $this->_box('server_ids',	$item_info['server_ids']),
			'active_box'		=> $this->_box('active', 		$item_info['active']),
			'active'			=> $item_info['active'],
			'back_link'			=> './?object='.$_GET['object'].'&action=show_items&id='.intval($menu_info['id']),
			'for_edit'			=> 1,
			'edit_modules_link'	=> './?object='.$menu_info['type'].'_modules',
			'edit_groups_link'	=> './?object='.$menu_info['type'].'_groups',
			'icons_list_link'	=> './?object='.$_GET['object'].'&action=icons_list',
			'icons_web_path'	=> WEB_PATH. $this->ICONS_PATH,
			'icon_src'			=> $icon_src,
			'edit_menu_link'	=> './?object='.$_GET['object'].'&action=edit&id='.$menu_info['id'],
			'cond_code'			=> _prepare_html($item_info['cond_code'], 0),
			'sites_link'		=> './?object=manage_sites',
			'servers_link'		=> './?object=manage_servers',
		);
		return tpl()->parse($_GET['object'].'/edit_item_form', $replace);
	}

	/**
	* Clone menu item
	*/
	function clone_item() {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e(t('No id!'));
		}
		$item_info = db()->query_fetch('SELECT * FROM '.db('menu_items').' WHERE id='.intval($_GET['id']));
		if (empty($item_info['id'])) {
			return _e(t('No such menu item!'));
		}
		$menu_info = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($item_info['menu_id']));
		if (empty($menu_info['id'])) {
			return _e(t('No such menu!'));
		}
		$sql = $item_info;
		unset($sql['id']);
		db()->INSERT('menu_items', $sql);
		common()->admin_wall_add(array('menu item cloned: '.$item_info['name'].'', $item_info['id']));
		cache()->refresh(array('menus', 'menu_items'));
		return js_redirect('./?object='.$_GET['object'].'&action=show_items&id='.$menu_info['id']);
	}

	/**
	* Get menu items ordered array (recursively)
	*/
	function _recursive_get_menu_items($menu_id = 0, $skip_item_id = 0, $parent_id = 0, $level = 0) {
		if (!isset($this->_menu_items_from_db)) {
			$Q = db()->query(
				'SELECT * FROM '.db('menu_items').' 
				WHERE menu_id='.intval($menu_id).' 
				ORDER BY `order` ASC'
			);
			while ($A = db()->fetch_assoc($Q)) {
				$this->_menu_items_from_db[$A['id']] = $A;
			}
		}
		if (empty($this->_menu_items_from_db)) {
			return '';
		}
		$items_ids		= array();
		$items_array	= array();
		foreach ((array)$this->_menu_items_from_db as $item_info) {
			if ($item_info['parent_id'] != $parent_id) {
				continue;
			}
			if ($skip_item_id == $item_info['id']) {
				continue;
			}
			$items_array[$item_info['id']] = $item_info;
			$items_array[$item_info['id']]['level'] = $level;
			$tmp_array = $this->_recursive_get_menu_items($menu_id, $skip_item_id, $item_info['id'], $level + 1);
			foreach ((array)$tmp_array as $sub_item_info) {
				if ($sub_item_info['id'] == $item_info['id']) {
					continue;
				}
				$items_array[$sub_item_info['id']] = $sub_item_info;
			}
		}
		return $items_array;
	}

	/**
	* Change menu item activity
	*/
	function activate_item() {
		if (!empty($_GET['id'])) {
			$item_info = db()->query_fetch('SELECT * FROM '.db('menu_items').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($item_info)) {
			db()->UPDATE('menu_items', array('active' => (int)!$item_info['active']), 'id='.intval($item_info['id']));
			common()->admin_wall_add(array('menu item: '.$item_info['name'].' '.($item_info['active'] ? 'inactivated' : 'activated'), $item_info['id']));
		}
		cache()->refresh(array('menus', 'menu_items'));
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($item_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object'].'&action=show_items&id='.$item_info['menu_id']);
		}
	}

	/**
	* Delete item
	*/
	function delete_item() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$item_info = db()->query_fetch('SELECT * FROM '.db('menu_items').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($item_info)) {
			db()->query('DELETE FROM '.db('menu_items').' WHERE id='.intval($_GET['id']));
			db()->UPDATE('menu_items', array('parent_id' => 0), 'parent_id='.intval($_GET['id']));
			common()->admin_wall_add(array('menu item deleted: '.$item_info['name'].'', $item_info['id']));
		}
		cache()->refresh(array('menus', 'menu_items'));
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object'].'&action=show_items&id='.$item_info['menu_id']);
		}
	}

	/**
	* Export menu items
	*/
	function export() {
		$_GET['id'] = intval($_GET['id']);
		$menu_info = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($_GET['id']));
		$params = array(
			'single_table'	=> '',
			'tables'		=> array(db('menus'), db('menu_items')),
			'full_inserts'	=> 1,
			'ext_inserts'	=> 1,
			'export_type'	=> 'insert',
			'silent_mode'	=> true,
		);
		if ($menu_info['id']) {
			$params['where'] = array(
				db('menus')		=> 'id='.intval($menu_info['id']),
				db('menu_items')	=> 'menu_id='.intval($menu_info['id']),
			);
		}
		$EXPORTED_SQL = module('db_manager')->export($params);
		$replace = array(
			'sql_text'	=> _prepare_html($EXPORTED_SQL, 0),
			'back_link'	=> './?object='.$_GET['object'],
		);
		return tpl()->parse('db_manager/export_text_result', $replace);
	}

	/**
	* Choose icon visaul
	*/
	function icons_list() {
		main()->NO_GRAPHICS = true;

		$icons_dir = INCLUDE_PATH. $this->ICONS_PATH;

		$cut_length = 0;
		foreach ((array)_class('dir')->scan_dir($icons_dir, true, '', '/\.(svn|git)/i') as $_icon_path) {
			$_icon_path = str_replace("\\", '/', strtolower($_icon_path));
			if (empty($cut_length)) {
				$cut_length = strpos($_icon_path, str_replace("\\", '/', strtolower($this->ICONS_PATH))) + strlen($this->ICONS_PATH);
			}
			$_icon_path = substr($_icon_path, $cut_length);

			$body[$_icon_path] = $_icon_path;
		}
		if (is_array($body)) {
			ksort($body);
		}
		echo implode(PHP_EOL, $body);
	}

	/**
	*
	*/
	function _multi_html_to_db($input = array()) {
		if (is_array($input)) {
			$input = ','.implode(',', $input).',';
		}
		return (string)str_replace(array(' ',"\t","\r","\n",',,'), '', $input);
	}

	/**
	*
	*/
	function _multi_db_to_html($input = '') {
		if (!is_array($input)) {
			$input	= explode(',',str_replace(array(' ',"\t","\r","\n",',,'), '', $input));
		}
		$output = array();
		foreach ((array)$input as $v) {
			if ($v) {
				$output[$v] = $v;
			}
		}
		return (array)$output;
	}

	/**
	*
	*/
	function _multi_db_to_show($input = '', $names = array()) {
		$output = array();
		if (is_array($input)) {
			$input = ','.implode(',', $input).',';
		}
		foreach (explode(',',trim($input,',')) as $k => $v) {
			if (empty($names[$v])) {
				continue;
			}
			$output[] = $names[$v];
		}
		$output = implode('<br />'.PHP_EOL, $output);
		if (empty($output)) {
			$output	= '-- ALL --';
		}
		return $output;
	}

	/**
	*/
	function _box ($name = '', $selected = '') {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval('return common()->'.$this->_boxes[$name].';');
	}

	/**
	* Execute this before redirect
	*/
	function _on_before_redirect () {
		if (defined('ADMIN_FRAMESET_MODE')) {
			$_SESSION['_menu_js_refresh_frameset'] = true;
		}
	}

	/**
	*/
	function _hook_widget__menus ($params = array()) {
// TODO
	}

	/**
	*/
	function _hook_widget__menu_items ($params = array()) {
// TODO
	}
}
