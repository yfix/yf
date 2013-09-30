<?php

/**
* System-wide menus editor
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_menus_editor {

	/** @var string Path to icons */
	public $ICONS_PATH = 'uploads/icons/';

	/**
	*/
	function _init () {
		$array_all = array('' => '-- ALL --');
		$this->_groups['user'] = $array_all + (array)db()->get_2d('SELECT id,name FROM '.db('user_groups').' WHERE active="1"');
		$this->_groups['admin'] = $array_all + (array)db()->get_2d('SELECT id,name FROM '.db('admin_groups').' WHERE active="1"');
		$this->_sites = $array_all + (array)db()->get_2d('SELECT id,name FROM '.db('sites').' WHERE active="1"');
		$this->_servers = $array_all + (array)db()->get_2d('SELECT id,name FROM '.db('core_servers').' WHERE active="1"');
		$this->_menu_types = array(
			'user'	=> 'user',
			'admin'	=> 'admin',
		);
		$this->_item_types = array(
			1 => 'Internal link',
			2 => 'External link',
			3 => 'Spacer',
		);
	}

	/**
	* Display menus
	*/
	function show() {
		$q = db()->query('SELECT m.id, COUNT(i.id) AS num FROM '.db('menus').' AS m LEFT JOIN '.db('menu_items').' AS i ON m.id = i.menu_id GROUP BY m.id');
		while ($a = db()->fetch_assoc($q)) {
			$num_items[$a['id']] = $a['num'];
		}
		return table('SELECT * FROM '.db('menus').' ORDER BY type DESC')
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
	*/
	function add() {
		$a = $_POST;
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name'	=> 'trim|required|is_unique[menus.name]',
				'type'	=> 'trim|required',
			))
			->db_insert_if_ok('menus', array('type','name','desc','stpl_name','method_name','active'), array(), array('on_after_update' => function() {
				common()->admin_wall_add(array('menu added: '.$_POST['name'].'', db()->insert_id()));
				cache()->refresh('menus');
			}))
			->radio_box('type', array('user' => 'User', 'admin' => 'Admin'))
			->text('name')
			->text('desc', 'Description')
			->template_select_box('stpl_name')
			->method_select_box('method_name')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function edit() {
		$id = intval($_GET['id']);
		if (!$id) {
			return _e('No id');
		}
		$a = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($_GET['id']));
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name'	=> 'trim|required',
			))
			->db_update_if_ok('menus', array('name','desc','stpl_name','method_name','active'), 'id='.$id, array('on_after_update' => function() {
				common()->admin_wall_add(array('menu updated: '.$_POST['name'].'', $menu_info['id']));
				cache()->refresh('menus');
			}))
			->info('type')
			->text('name')
			->text('desc', 'Description')
			->template_select_box('stpl_name')
			->method_select_box('method_name')
			->active_box()
			->save_and_back();
	}

	/**
	* Get array of templates for the given init type
	*/
	function _get_stpls ($type = 'user') {
		return module('template_editor')->_get_stpls_for_type($type);
	}

	/**
	* Clone menus
	*/
	function clone_menu() {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e('No id!');
		}
		$menu_info = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($_GET['id']));
		if (empty($menu_info['id'])) {
			return _e('No such menu!');
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
	* Delete menu and all sub items
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
	* Change menu activity
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
	* Display menu items for the given
	*/
	function show_items() {
		$menu_info = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($_GET['id']).' OR name="'.db()->es($_GET['id']).'"');
		if (empty($menu_info)) {
			return _e('No such menu!');
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
		$groups = $this->{'_'.$menu_info['type'].'_groups'};
		return table($menu_items, array('pager_records_on_page' => 10000, 'condensed' => 1))
			->form()
			->icon('icon')
			->input_padded('name')
			->input('location')
			->text('type_id', 'Item type', array('data' => $this->_item_types, 'nowrap' => 1))
			->data('user_groups', $groups, array('desc' => 'Groups'))
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
				$new_data = array(
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
					$batch[$item_id] = $new_data;
				}
			}
			if ($batch) {
				db()->update_batch('menu_items', db()->es($batch));
				common()->admin_wall_add(array('menu items dragged: '.$menu_info['name'].'', $menu_info['id']));
				cache()->refresh(array('menus', 'menu_items'));
			}
			main()->NO_GRAPHICS = true;
			return false;
		}
		if (isset($items[''])) {
			unset($items['']);
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
			if (!$info) {
				continue;
			}
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
	*/
	function add_item() {
		$menu_info = db()->get('SELECT * FROM '.db('menus').' WHERE id='.intval($_GET['id']));
		if (empty($menu_info['id'])) {
			return _e('No such menu!');
		}
		$_GET['id'] = intval($menu_info['id']);

		$multi_selects = array('user_groups','site_ids','server_ids');
		if ($_POST) {
			foreach ($multi_selects as $k) {
				$_POST[$k] = $this->_multi_html_to_db($_POST[$k]);
			}
		} else {
			foreach ($multi_selects as $k) {
				$a[$k] = $this->_multi_db_to_html($a[$k]);
			}
		}
		$a = $_POST;
		$a['redirect_link'] = './?object='.$_GET['object'].'&action=show_items&id='.$menu_info['id'];
		return form($a)
			->validate(array(
				'name'	=> 'trim|required',
			))
			->db_insert_if_ok('menu_items', array(
				'type_id','parent_id','name','location','icon','user_groups','site_ids','server_ids','active'
			), array('menu_id' => $menu_info['id']), array(
				'on_after_update' => function() {
					common()->admin_wall_add(array('menu item added: '.$_POST['name'].'', db()->insert_id()));
					cache()->refresh(array('menus', 'menu_items'));
				}
			))
			->select_box('type_id', $this->_item_types)
			->select_box('parent_id', $this->_get_parents_for_select($menu_info['id']), array('desc' => 'Parent item'))
			->text('name')
			->location_select_box('location')
			->multi_select_box('user_groups', $this->_groups[$menu_info['type']], array('edit_link' => './?object='.$menu_info['type'].'_groups', 'desc' => 'Groups'))
			->multi_select_box('site_ids', $this->_sites, array('edit_link' => './?object=manage_sites', 'desc' => 'Sites'))
			->multi_select_box('server_ids', $this->_servers, array('edit_link' => './?object=manage_servers', 'desc' => 'Servers'))
			->icon_select_box('icon')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function edit_item() {
		$item_info = db()->query_fetch('SELECT * FROM '.db('menu_items').' WHERE id='.intval($_GET['id']));
		if (empty($item_info['id'])) {
			return _e('No such menu item!');
		}
		$menu_info = db()->get('SELECT * FROM '.db('menus').' WHERE id='.intval($item_info['menu_id']));
		if (empty($menu_info['id'])) {
			return _e('No such menu!');
		}
		$_GET['id'] = intval($item_info['id']);

		$multi_selects = array('user_groups','site_ids','server_ids');
		if ($_POST) {
			foreach ($multi_selects as $k) {
				$_POST[$k] = $this->_multi_html_to_db($_POST[$k]);
			}
		} else {
			foreach ($multi_selects as $k) {
				$a[$k] = $this->_multi_db_to_html($a[$k]);
			}
		}
		$a = $item_info;
		$a['redirect_link'] = './?object='.$_GET['object'].'&action=show_items&id='.$menu_info['id'];
		return form($a)
			->validate(array(
				'name'	=> 'trim|required',
			))
			->db_update_if_ok('menu_items', array(
				'type_id','parent_id','name','location','icon','user_groups','site_ids','server_ids','active'
			), 'id='.$item_info['id'], array(
				'on_after_update' => function() {
					common()->admin_wall_add(array('menu item updated: '.$_POST['name'].'', $item_info['id']));
					cache()->refresh(array('menus', 'menu_items'));
				}
			))
			->select_box('type_id', $this->_item_types)
			->select_box('parent_id', $this->_get_parents_for_select($menu_info['id']), array('desc' => 'Parent item'))
			->text('name')
			->location_select_box('location')
			->multi_select_box('user_groups', $this->_groups[$menu_info['type']], array('edit_link' => './?object='.$menu_info['type'].'_groups', 'desc' => 'Groups'))
			->multi_select_box('site_ids', $this->_sites, array('edit_link' => './?object=manage_sites', 'desc' => 'Sites'))
			->multi_select_box('server_ids', $this->_servers, array('edit_link' => './?object=manage_servers', 'desc' => 'Servers'))
			->icon_select_box('icon')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function _get_parents_for_select($menu_id) {
		$data = array(0 => '-- TOP --');
		foreach ((array)$this->_recursive_get_menu_items($menu_id, $_GET['id']) as $cur_item_id => $cur_item_info) {
			if (empty($cur_item_id)) {
				continue;
			}
			$data[$cur_item_id] = str_repeat('&nbsp; &nbsp; &nbsp; ', $cur_item_info['level']).' &#9492; &nbsp; '.$cur_item_info['name'];
		}
		return $data;
	}

	/**
	* Clone menu item
	*/
	function clone_item() {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e('No id!');
		}
		$item_info = db()->query_fetch('SELECT * FROM '.db('menu_items').' WHERE id='.intval($_GET['id']));
		if (empty($item_info['id'])) {
			return _e('No such menu item!');
		}
		$menu_info = db()->query_fetch('SELECT * FROM '.db('menus').' WHERE id='.intval($item_info['menu_id']));
		if (empty($menu_info['id'])) {
			return _e('No such menu!');
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
