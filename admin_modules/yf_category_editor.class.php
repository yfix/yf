<?php

/**
* Categories editor
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_category_editor {

	/** @var int */
	public $ITEMS_PER_PAGE		= 100;
	/** @var bool */
	public $PROPOSE_SHORT_URL	= 1;

	/**
	*/
	function _init () {
		$array_all = array('' => '-- ALL --');
		$this->_groups = array(
			'user'	=> $array_all + (array)db()->get_2d('SELECT id,name FROM '.db('user_groups').' WHERE active="1"'),
			'admin'	=> $array_all + (array)db()->get_2d('SELECT id,name FROM '.db('admin_groups').' WHERE active="1"'),
		);
	}

	/**
	*/
	function _purge_category_caches($cat_info = array()) {
		cache_del(array('category_sets', 'category_items', 'category_items_all'));
		if (isset($cat_info['name'])) {
			cache_del(array(
				'cats__get_items_names__'.$cat_info['name'],
				'cats__get_items_names__'.$cat_info['name'].'_0',
				'cats__get_items_names__'.$cat_info['name'].'_1',
				'cats__get_items_names__'.$cat_info['name'].'_0_0',
				'cats__get_items_names__'.$cat_info['name'].'_0_1',
				'cats__get_items_names__'.$cat_info['name'].'_1_0',
				'cats__get_items_names__'.$cat_info['name'].'_1_1',
				'cats__prepare_for_box__'.$cat_info['name'].'_0',
				'cats__prepare_for_box__'.$cat_info['name'].'_1',
				'cats__prepare_for_box__'.$cat_info['name'].'_0_0',
				'cats__prepare_for_box__'.$cat_info['name'].'_1_0',
			));
		}
		_class( '_shop_categories', 'modules/shop/' )->_refresh_cache();
	}

	/**
	*/
	function show() {
		$sql = 'SELECT * FROM '.db('categories').' ORDER BY type DESC, active ASC';
		return table($sql, array('custom_fields' => array(
				'items' => 'SELECT cat_id, COUNT(*) AS num FROM '.db('category_items').' GROUP BY cat_id',
			)))
			->link('name', './?object='.$_GET['object'].'&action=show_items&id=%d')
			->text('type')
			->text('desc')
			->text('custom_fields')
			->text('items')
			->btn_edit()
			->btn_delete()
			->btn_clone('', './?object='.$_GET['object'].'&action=clone_cat&id=%d')
			->btn('Drag', './?object='.$_GET['object'].'&action=drag_items&id=%d')
			->btn('Export', './?object='.$_GET['object'].'&action=export&id=%d')
			->btn_active()
			->footer_add();
	}

	/**
	*/
	function add() {
		$a = $_POST;
		$a['redirect_link'] = url_admin('/@object');
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name'	=> 'trim|required|is_unique[categories.name]',
				'type'	=> 'trim|required',
			))
			->db_insert_if_ok('categories', array('type','name','desc','stpl_name','method_name','custom_fields','active'), array())
			->on_after_update(function() {
				common()->admin_wall_add(array('category added: '.$_POST['name'], db()->insert_id()));
				module('category_editor')->_purge_category_caches();
			})
			->radio_box('type', array('user' => 'User', 'admin' => 'Admin'))
			->text('name')
			->text('desc', 'Description')
			->text('stpl_name')
			->text('method_name')
			->text('custom_fields')
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
		$a = db()->query_fetch('SELECT * FROM '.db('categories').' WHERE id='.intval($_GET['id']));
		$a['redirect_link'] = url_admin('/@object');
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name'	=> 'trim|required',
			))
			->db_update_if_ok('categories', array('name','desc','stpl_name','method_name','custom_fields','active'), 'id='.$id)
			->on_after_update(function() {
				common()->admin_wall_add(array('category updated: '.$a['name'], $id));
				module('category_editor')->_purge_category_caches();
			})
			->info('type')
			->text('name')
			->text('desc', 'Description')
			->text('stpl_name')
			->text('method_name')
			->text('custom_fields')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$cat_info = db()->query_fetch('SELECT * FROM '.db('categories').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($cat_info['id'])) {
			db()->query('DELETE FROM '.db('categories').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			db()->query('DELETE FROM '.db('category_items').' WHERE cat_id='.intval($_GET['id']));
			common()->admin_wall_add(array('category deleted: '.$cat_info['name'], $_GET['id']));
		}
		module('category_editor')->_purge_category_caches();
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect(url_admin('/@object'));
		}
	}

	/**
	*/
	function clone_cat() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$cat_info = db()->query_fetch('SELECT * FROM '.db('categories').' WHERE id='.intval($_GET['id']));
		}
		if (empty($cat_info['id'])) {
			return _e('No such category!');
		}
		$sql = $cat_info;
		unset($sql['id']);
		$sql['name'] = $sql['name'].'_clone';

		db()->INSERT('categories', $sql);
		$NEW_CAT_ID = db()->INSERT_ID();

		$old_items = $this->_recursive_get_cat_items($cat_info['id']);
		foreach ((array)$old_items as $_id => $_info) {
			unset($_info['id']);
			unset($_info['level']);
			$_info['cat_id'] = $NEW_CAT_ID;

			db()->INSERT('category_items', $_info);
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
			db()->UPDATE('category_items', array('parent_id' => $_new_parent_id), 'id='.intval($_new_id));
		}
		common()->admin_wall_add(array('category cloned: from '.$cat_info['name'].' into '.$sql['name'], $_GET['id']));
		module('category_editor')->_purge_category_caches();
		return js_redirect(url_admin('/@object'));
	}

	/**
	*/
	function active() {
		if (!empty($_GET['id'])) {
			$cat_info = db()->query_fetch('SELECT * FROM '.db('categories').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($cat_info)) {
			db()->UPDATE('categories', array('active' => (int)!$cat_info['active']), 'id='.intval($cat_info['id']));
			common()->admin_wall_add(array('category '.$cat_info['name'].' '.($cat_info['active'] ? 'inactivated' : 'activated'), $_GET['id']));
		}
		module('category_editor')->_purge_category_caches();
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($cat_info['active'] ? 0 : 1);
		} else {
			return js_redirect(url_admin('/@object'));
		}
	}

	/**
	*/
	function show_items() {
		$cat_info = db()->get('SELECT * FROM '.db('categories').' WHERE name="'.db()->es($_GET['id']).'" OR id='.intval($_GET['id']));
		if (!$cat_info) {
			return _e('No such category');
		}
		$_GET['id'] = $cat_info['id'];
		$cat_items = $this->_recursive_get_cat_items($_GET['id']);
		if (main()->is_post()) {
			$batch = array();
			foreach ((array)$cat_items as $a) {
				if (!isset($_POST['name'][$a['id']])) {
					continue;
				}
				$batch[$a['id']] = array(
					'id'	=> $a['id'],
					'name'	=> $_POST['name'][$a['id']],
					'url'	=> $_POST['url'][$a['id']],
				);
			}
			if ($batch) {
				db()->update_batch('category_items', db()->es($batch));
				common()->admin_wall_add(array('category items updated: '.$cat_info['name'], $cat_info['id']));
				module('category_editor')->_purge_category_caches($cat_info);
			}
			return js_redirect('./?object='.$_GET['object'].'&action=show_items&id='.$_GET['id']);
		}
		return table($cat_items, array(
				'pager_records_on_page' => $this->ITEMS_PER_PAGE,
				'condensed' => 1,
				'hide_empty' => 1,
			))
			->form()
			->input_padded('name')
			->input('url', array('propose_url_from' => $this->PROPOSE_SHORT_URL ? 'name' : false))
			->text('other_info')
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
		$cat_info = db()->get('SELECT * FROM '.db('categories').' WHERE name="'.db()->es($_GET['id']).'" OR id='.intval($_GET['id']));
		if (!$cat_info) {
			return _e('No such category');
		}
		$_GET['id'] = $cat_info['id'];
		$items = $this->_show_category_contents(array(
			'cat_info' => $cat_info,
		));
		if (main()->is_post()) {
			$cur_items = $this->_auto_update_items_orders($cat_info['id']);
			$batch = array();
			foreach ((array)json_decode((string)$_POST['items'], $assoc = true) as $order_id => $info) {
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
				db()->update_batch('category_items', db()->es($batch));
				common()->admin_wall_add(array('category items dragged and saved: '.$cat_info['name'], $cat_info['id']));
				module('category_editor')->_purge_category_caches($cat_info);
			}
			main()->NO_GRAPHICS = true;
			return false;
		}
		if (isset($items[''])) {
			unset($items['']);
		}
		return $this->_drag_tpl_main($items);
	}

	/**
	* This pure-php method needed to greatly speedup page rendering time for 100+ items
	*/
	function _drag_tpl_main(&$items) {
		$r = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'add_link'		=> './?object='.$_GET['object'].'&action=add_item&id='.$_GET['id'],
			'back_link'		=> './?object='.$_GET['object'].'&action=show_items&id='.$_GET['id'],
		);
		return '<form action="'.$r['form_action'].'" method="post" id="draggable_form">
				<div class="controls">
					<button type="submit" class="btn btn-primary btn-mini btn-xs"><i class="icon-save"></i> '.t('Save').'</button>
					<a href="'.$r['back_link'].'" class="btn btn-mini btn-xs"><i class="icon-backward"></i> '.t('Go Back').'</a>
					<a href="'.$r['add_link'].'" class="btn btn-mini btn-xs ajax_add"><i class="icon-plus-sign"></i> '.t('Add').'</a>
					<a href="javascript:void(0);" class="btn btn-mini btn-xs" id="draggable-menu-expand-all"><i class="icon-expand-alt fa-expand"></i> '.t('Expand').'</a>
				</div>
				<ul class="draggable_menu">'.implode(PHP_EOL, (array)$this->_drag_tpl_items($items)).'</ul>
			</form>'
			.tpl()->parse('draggable_menu_js');
	}

	/**
	* This pure-php method needed to greatly speedup page rendering time for 100+ items
	*/
	function _drag_tpl_items(&$items) {
		$body = array();

		$form = _class('form2');
		$replace = array(
			'edit_link'		=> './?object='.$_GET['object'].'&action=edit_item&id=%d',
			'delete_link'	=> './?object='.$_GET['object'].'&action=delete_item&id=%d',
			'clone_link'	=> './?object='.$_GET['object'].'&action=clone_item&id=%d',
		);
		$form_controls =
			$form->tpl_row('tbl_link_edit', $replace, '', '', '')
			.$form->tpl_row('tbl_link_delete', $replace, '', '', '')
			.$form->tpl_row('tbl_link_clone', $replace, '', '', '')
		;
		foreach ((array)$items as $id => $item) {
			if (!$id) {
				continue;
			}
			$expander_icon = '';
			if ($item['have_children']) {
				$expander_icon = $item['level_num'] >= 1 ? 'icon-caret-right' : 'icon-caret-down';
			}
			$content = ($item['icon_class'] ? '<i class="'.$item['icon_class'].'"></i>' : ''). $item['name'];
			if ($item['link']) {
				$content = '<a href="'.$item['link'].'">'.$content. '</a>';
			}
			if ($item['have_children']) {
				$footer = '<ul class="'.($item['level_num'] >= 1 ? 'closed' : '').'">';
			} else {
				$footer = '</li>'.str_repeat('</ul>'.PHP_EOL, $item['next_level_diff']);
			}
			$body[] = '
				<li id="item_'.$id.'">
					<div class="dropzone"></div>
					<dl>
						<a href="#" class="expander"><i class="icon '.$expander_icon.'"></i></a>&nbsp;'
						.$content
						.'&nbsp;<span class="move" title="'.t('Move').'"><i class="icon icon-move"></i></span>
						<div style="float:right;display:none;" class="controls_over">'
						.str_replace('%d', $id, $form_controls)
						.'</div>
					</dl>'
				.$footer
			;
		}
		return $body;
	}

	/**
	*/
	function _show_category_contents ($params = array()) {
		$ICONS_PATH		= 'uploads/icons/';
		$MEDIA_PATH		= WEB_PATH;
		$force_stpl_name= isset($params['force_stpl_name']) ? $params['force_stpl_name'] : false;
		$STPL_MAIN 		= !empty($force_stpl_name) ? $force_stpl_name : $_GET['object'].'/drag_main';
		$STPL_ITEM		= !empty($force_stpl_name) ? $force_stpl_name.'_item' : $_GET['object'].'/drag_item';

		$cat_info		= $params['cat_info'];
		$cat_id			= $cat_info['id'];
		if (empty($cat_id)) {
			return _e('No id');
		}
		$cat_items = $this->_auto_update_items_orders($cat_id);
		if (empty($cat_items)) {
			return false;
		}
		// Update field 'have_children'
		foreach ((array)$cat_items as $id => $info) {
			$cat_items[$id]['have_children'] = 0;
		}
		foreach ((array)$cat_items as $id => $info) {
			$cat_items[$info['parent_id']]['have_children']++;
		}

		$cat_items_to_display = array_values($cat_items);
		$num_cat_items = count($cat_items_to_display);

		$ICONS_DIR = _class('graphics')->ICONS_PATH;
		$MEDIA_PATH = _class('graphics')->MEDIA_PATH;

		$_prev_level = 0;
		$_next_level = 0;
		$item_counter = 0;
		foreach ((array)$cat_items_to_display as $i => $item_info) {
			if (!$item_info['id']) {
				continue;
			}
			$item_counter++;
			$_next_info	= isset($cat_items_to_display[$i + 1]) ? $cat_items_to_display[$i + 1] : array();
			$_next_level = isset($_next_info['level']) ? (int)$_next_info['level'] : 0;

			$icon = trim($item_info['icon']);
			$icon_path = '';
			$icon_class = '';
			if ($icon) {
				// Icon class from bootstrap icon class names
				if (preg_match('/^icon\-[a-z0-9_-]+$/i', $icon) || (strpos($icon, '.') === false)) {
					$icon_class = $icon;
				} else {
					$_icon_fs_path = PROJECT_PATH. $ICONS_DIR. $icon;
					if (file_exists($_icon_fs_path)) {
						$icon_path = $MEDIA_PATH. $ICONS_DIR. $icon;
					}
				}
			}
			$items[$item_info['id']] = array(
				'item_id'		=> intval($item_info['id']),
				'parent_id'		=> intval($item_info['parent_id']),
				'name'			=> _prepare_html(t($item_info['name'])),
				'level_num'		=> intval($item_info['level']),
				'prev_level'	=> intval($_prev_level),
				'next_level'	=> intval($_next_level),
				'icon_path'		=> $icon_path,
				'icon_class'	=> $icon_class,
				'is_first_item'	=> (int)($item_counter == 1),
				'is_last_item'	=> (int)($item_counter == $num_cat_items),
				'have_children'	=> intval((bool)$item_info['have_children']),
				'next_level_diff'=> intval(abs($item_info['level'] - $_next_level)),
				'link'			=> '',
				'active'		=> intval($item_info['active']),
				'order'			=> intval($item_info['order']),
			);
			// Save current level for the next iteration
			$_prev_level = $item_info['level'];
		}
		return $items;
	}

	/**
	*/
	function _auto_update_items_orders($cat_id) {
		if (!$cat_id) {
			return false;
		}
		$cat_items = $this->_recursive_get_cat_items($cat_id);
		$new_order = 1;
		$batch = array();
		foreach ((array)$cat_items as $item_id => $info) {
			if (!$info) {
				continue;
			}
			if ($info['order'] != $new_order) {
				$batch[$item_id] = array(
					'id'	=> $item_id,
					'order' => $new_order,
				);
				$cat_items[$item_id]['order'] = $new_order;
			}
			$new_order++;
		}
		if ($batch) {
			db()->update_batch('category_items', $batch);
		}
		return $cat_items;
	}

	/**
	*/
	function _recursive_get_cat_items($cat_id = 0, $skip_item_id = 0, $parent_id = 0) {
		if (!isset($this->_category_items_from_db)) {
			$this->_category_items_from_db = db()->get_all('SELECT * FROM '.db('category_items').' WHERE cat_id='.intval($cat_id).' ORDER BY `order` ASC');
		}
		if (empty($this->_category_items_from_db)) {
			return '';
		}
		return $this->_recursive_sort_items($this->_category_items_from_db, $skip_item_id, $parent_id);
	}

	/**
	* Get and sort items ordered array (recursively)
	*/
	function _recursive_sort_items($items = array(), $skip_item_id = 0, $parent_id = 0, $level = 0) {
		$children = array();
		foreach ((array)$items as $id => $info) {
			$parent_id = $info['parent_id'];
			if ($skip_item_id == $id) {
				continue;
			}
			$children[$parent_id][$id] = $id;
		}
		$ids = $this->_count_levels(0, $children);
		$new_items = array();
		foreach ((array)$ids as $id => $level) {
			$new_items[$id] = $items[$id] + array('level' => $level);
		}
		return $new_items;
	}

	/**
	*/
	function _count_levels($start_id = 0, &$children, $level = 0) {
		$ids = array();
		foreach ((array)$children[$start_id] as $id => $_tmp) {
			$ids[$id] = $level;
			if (isset($children[$id])) {
				foreach ((array)$this->_count_levels($id, $children, $level + 1) as $_id => $_level) {
					$ids[$_id] = $_level;
				}
			}
		}
		return $ids;
	}

	/**
	*/
	function add_item() {
		$cat_info = db()->query_fetch('SELECT * FROM '.db('categories').' WHERE id='.intval($_GET['id']));
		if (empty($cat_info['id'])) {
			return _e('No such category!');
		}
		$_GET['id'] = intval($cat_info['id']);

		if (main()->is_post()) {
			$tmp = array();
			foreach (explode(',', $cat_info['custom_fields']) as $field_name) {
				if ($field_name && $_POST['custom'][$field_name]) {
					$tmp[$field_name] = $field_name.'='.$_POST['custom'][$field_name];
				}
			}
			$_POST['other_info'] = implode(';'.PHP_EOL, $tmp);

			if (empty($_POST['url']) && $this->PROPOSE_SHORT_URL) {
				$_POST['url'] = common()->_propose_url_from_name($_POST['name']);
			}
		}

		$a = (array)$_POST;
		$a['redirect_link'] = './?object='.$_GET['object'].'&action=show_items&id='.$cat_info['id'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name'	=> 'trim|required',
			))
			->db_insert_if_ok('category_items', array('parent_id','name','desc','meta_keywords','meta_desc','url','icon','featured','active','other_info'), array('cat_id' => $cat_info['id']))
			->on_after_update(function() use ($cat_info) {
				common()->admin_wall_add(array('category item added: '.$cat_info['name'], $cat_info['id']));
				module('category_editor')->_purge_category_caches($cat_info);
			})
			->select_box('parent_id', $this->_get_parents_for_select($cat_info['id']), array('desc' => 'Parent item'))
			->text('name')
			->textarea('desc', 'Description')
			->text('url', 'Pretty url')
			->text('meta_keywords')
			->text('meta_desc')
			->icon_select_box('icon')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function edit_item() {
		$item_info = db()->query_fetch('SELECT * FROM '.db('category_items').' WHERE id='.intval($_GET['id']));
		if (!$item_info['id']) {
			return _e('No such item!');
		}
		$cat_info = db()->query_fetch('SELECT * FROM '.db('categories').' WHERE id='.intval($item_info['cat_id']));
		if (empty($cat_info['id'])) {
			return _e('No such category!');
		}
		$_GET['id'] = intval($item_info['id']);

		if (main()->is_post()) {
			$tmp = array();
			foreach (explode(',', $cat_info['custom_fields']) as $field_name) {
				if ($field_zzname && $_POST['custom'][$field_name]) {
					$tmp[$field_name] = $field_name.'='.$_POST['custom'][$field_name];
				}
			}
			$_POST['other_info'] = implode(';'.PHP_EOL, $tmp);
		} else {
			if (empty($item_info['url']) && $this->PROPOSE_SHORT_URL) {
				$item_info['url'] = common()->_propose_url_from_name($item_info['name']);
			}
		}

		$a = $item_info + (array)$_POST;
		$a['redirect_link'] = './?object='.$_GET['object'].'&action=show_items&id='.$cat_info['id'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name'	=> 'trim|required',
			))
			->db_update_if_ok('category_items', array('parent_id','name','desc','meta_keywords','meta_desc','url','icon','featured','active','other_info'), 'id='.$item_info['id'])
			->on_after_update(function() use ($cat_info) {
				common()->admin_wall_add(array('category item updated: '.$cat_info['name'], $cat_info['id']));
				module('category_editor')->_purge_category_caches($cat_info);
			})
			->select_box('parent_id', $this->_get_parents_for_select($cat_info['id']), array('desc' => 'Parent item'))
			->text('name')
			->textarea('desc', 'Description')
			->text('url', 'Pretty url')
			->text('meta_keywords')
			->text('meta_desc')
			->icon_select_box('icon')
			->yes_no_box('featured')
			->active_box()
			->custom_fields('other_info', $cat_info['custom_fields'])
			->save_and_back();
	}

	/**
	*/
	function _get_parents_for_select($cat_id, $skip_id = null) {
		$data = array(0 => '-- TOP --');
		foreach ((array)$this->_recursive_get_cat_items($cat_id, $skip_id) as $cur_item_id => $cur_item_info) {
			if (empty($cur_item_id)) {
				continue;
			}
			$data[$cur_item_id] = str_repeat('&nbsp; &nbsp; &nbsp; ', $cur_item_info['level']).' &#9492; &nbsp; '.$cur_item_info['name'];
		}
		return $data;
	}

	/**
	*/
	function activate_item() {
		if (!empty($_GET['id'])) {
			$item_info = db()->query_fetch('SELECT * FROM '.db('category_items').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($item_info)) {
			$cat_info = db()->query_fetch('SELECT * FROM '.db('categories').' WHERE id='.intval($item_info['cat_id']));
			db()->UPDATE('category_items', array('active' => (int)!$item_info['active']), 'id='.intval($item_info['id']));
			common()->admin_wall_add(array('category item '.$item_info['id'].' '.($item_info['active'] ? 'inactivated' : 'activated'), $_GET['id']));
			module('category_editor')->_purge_category_caches($cat_info);
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($item_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object'].'&action=show_items&id='.$item_info['cat_id']);
		}
	}

	/**
	*/
	function delete_item() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$item_info = db()->query_fetch('SELECT * FROM '.db('category_items').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($item_info)) {
			$cat_info = db()->query_fetch('SELECT * FROM '.db('categories').' WHERE id='.intval($item_info['cat_id']));
			db()->query('DELETE FROM '.db('category_items').' WHERE id='.intval($_GET['id']));
			common()->admin_wall_add(array('category item deleted: '.$item_info['id'], $_GET['id']));
			module('category_editor')->_purge_category_caches($cat_info);
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object'].'&action=show_items&id='.$item_info['cat_id']);
		}
	}

	/**
	*/
	function clone_item() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$item_info = db()->query_fetch('SELECT * FROM '.db('category_items').' WHERE id='.intval($_GET['id']));
		}
		if ($item_info) {
			$cat_info = db()->query_fetch('SELECT * FROM '.db('categories').' WHERE id='.intval($item_info['cat_id']));
			$sql = $item_info;
			unset($sql['id']);
			db()->INSERT('category_items', $sql);
			common()->admin_wall_add(array('category item cloned from '.$item_info['id'].' into '.$item_info['id'], $_GET['id']));
			module('category_editor')->_purge_category_caches($cat_info);
		}
		return js_redirect('./?object='.$_GET['object'].'&action=show_items&id='.$item_info['cat_id']);
	}

	/**
	*/
	function export() {
		// If no ID set - mean that simply export all categories with items
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$cat_info = db()->query_fetch('SELECT * FROM '.db('categories').' WHERE id='.intval($_GET['id']));
		}
		$params = array(
			'single_table'	=> '',
			'tables'		=> array(db('categories'), db('category_items')),
			'full_inserts'	=> 1,
			'ext_inserts'	=> 1,
			'export_type'	=> 'insert',
			'silent_mode'	=> true,
		);
		if ($cat_info['id']) {
			$params['where'] = array(
				db('categories')		=> 'id='.intval($cat_info['id']),
				db('category_items')	=> 'cat_id='.intval($cat_info['id']),
			);
		}
		$EXPORTED_SQL = module('db_manager')->export($params);

		$replace = array(
			'sql_text'	=> _prepare_html($EXPORTED_SQL, 0),
			'back_link'	=> url_admin('/@object'),
		);
		return tpl()->parse('db_manager/export_text_result', $replace);
	}

	/**
	*/
	function _hook_widget__categories ($params = array()) {
// TODO
	}

	/**
	*/
	function _hook_widget__category_items ($params = array()) {
// TODO
	}
}
