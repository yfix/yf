<?php

/**
* Static/HTML pages content editor
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_static_pages {

	/**
	*/
	function show() {
		$filter_name = $_GET['object'].'__'.$_GET['action'];

		$sql = 'SELECT * FROM '.db('static_pages');
		return table($sql, array(
				'filter' => $_SESSION[$filter_name],
				'filter_params' => array(
					'name'	=> 'like',
				),
			))
			->text('name')
			->btn_edit()
			->btn_delete()
			->btn('View', './?object='.$_GET['object'].'&action=view&id=%d')
			->btn_active()
			->footer_link('Add', './?object='.$_GET['object'].'&action=add');
	}

	/**
	*/
	function add() {
		if (empty($_POST['name'])) {
			return form(array('back_link' => './?object='.$_GET['object']))
				->text('name')
				->save_and_back();
		}
		$name = preg_replace('/[^a-z0-9\_\-]/i', '_', _strtolower($_POST['name']));
		$name = str_replace(array('__', '___'), '_', $name);
		if (strlen($name)) {
			db()->insert('static_pages', _es(array('name' => $name)));
			$page_id = db()->insert_id();
			common()->admin_wall_add(array('statis page added: '.$name, $page_id));
		}
		cache_del('static_pages_names');
		if (!empty($page_id)) {
			return js_redirect('./?object='.$_GET['object'].'&action=edit&id='.$page_id);
		} else {
			return _e('Cannot insert record!');
		}
	}

	/**
	*/
	function edit() {
		if (!isset($_GET['id'])) {
			return _e('No id');
		}
		$page_info = db()->get('SELECT * FROM '.db('static_pages').' WHERE name="'._es(_strtolower(urldecode($_GET['id']))).'" OR id='.intval($_GET['id']).' LIMIT 1');
		if (!$page_info) {
			return _e('No page info');
		}
		if (main()->is_post()) {
			if (isset($_POST['name'])) {
				$_POST['name'] = preg_replace('/[^a-z0-9\_\-]/i', '_', _strtolower($_POST['name']));
				$_POST['name'] = str_replace(array('__', '___'), '_', $_POST['name']);
			}
			$sql = array();
			$fields = array(
				'name',
				'text',
				'page_title',
				'page_heading',
				'meta_keywords',
				'meta_desc',
				'active',
			);
			foreach ((array)$fields as $field) {
				if (isset($_POST[$field])) {
					$sql[$field] = $_POST[$field];
				}
			}
			if ($sql['text']) {
				db()->update('static_pages', db()->es($sql), 'id='.intval($page_info['id']));
				common()->admin_wall_add(array('statis page updated: '.$page_info['name'], $page_info['id']));
			}
			cache_del('static_pages_names');
			return js_redirect(url_admin('/@object'));
		}
		$DATA = $page_info;
		foreach ((array)$_POST as $k => $v) {
			$DATA[$k] = $v;
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$page_info['id'],
			'name'			=> $DATA['name'],
			'text'			=> $DATA['text'],
			'page_title'	=> $DATA['page_title'],
			'page_heading'	=> $DATA['page_heading'],
			'meta_keywords'	=> $DATA['meta_keywords'],
			'meta_desc'		=> $DATA['meta_desc'],
			'active'		=> $DATA['active'],
			'back_url'		=> url_admin('/@object'),
		);
		return form($replace)
			->text('name')
			->textarea('text','',array('class' => 'span4','rows' => '10','ckeditor' => true, 'id' => 'text'))
			->text('page_title')
			->text('page_heading')
			->text('meta_keywords')
			->text('meta_desc')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		if (isset($_GET['id'])) {
			db()->query('DELETE FROM '.db('static_pages').' WHERE name="'._es(urldecode($_GET['id'])).'" OR id='.intval($_GET['id']));
			common()->admin_wall_add(array('static page deleted: '.$_GET['id'], $_GET['id']));
		}
		cache_del('static_pages_names');
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $page_name;
		} else {
			return js_redirect(url_admin('/@object'));
		}
	}

	/**
	*/
	function active () {
		if (isset($_GET['id'])) {
			$page_info = db()->query_fetch('SELECT * FROM '.db('static_pages').' WHERE name="'._es(_strtolower(urldecode($_GET['id']))).'" OR id='.intval($_GET['id']));
		}
		if (!empty($page_info['id'])) {
			db()->UPDATE('static_pages', array('active' => (int)!$page_info['active']), 'id='.intval($page_info['id']));
			common()->admin_wall_add(array('static page: '.$page_info['name'].' '.($page_info['active'] ? 'inactivated' : 'activated'), $page_info['id']));
			cache_del('static_pages_names');
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($page_info['active'] ? 0 : 1);
		} else {
			return js_redirect(url_admin('/@object'));
		}
	}

	/**
	*/
	function view() {
		if (!empty($_GET['id'])) {
			$page_info = db()->query_fetch('SELECT * FROM '.db('static_pages').' WHERE name="'._es(_strtolower(urldecode($_GET['id']))).'" OR id='.intval($_GET['id']));
		}
		if (empty($page_info)) {
			return _e('No such page!');
		}
		$body = stripslashes($page_info['text']);
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=edit&id='.$page_info['id'],
			'back_link'		=> url_admin('/@object'),
			'body'			=> $body,
		);
		return form($replace)
			->container($body, '', array(
				'id'	=> 'content_editable',
				'wide'	=> 1,
				'ckeditor' => array(
					'hidden_id'	=> 'text',
				),
			))
			->hidden('text')
			->save_and_back();
	}

	/**
	*/
	function _show_header() {
		$pheader = t('Static pages');
		$subheader = _ucwords(str_replace('_', ' ', $_GET['action']));
		$cases = array (
			//$_GET['action'] => {string to replace}
			'show'			=> '',
			'edit'			=> '',
		);			 		
		if (isset($cases[$_GET['action']])) {
			$subheader = $cases[$_GET['action']];
		}
		return array(
			'header'	=> $pheader,
			'subheader'	=> $subheader ? _prepare_html($subheader) : '',
		);
	}

	/**
	*/
	function filter_save() {
		return _class('admin_methods')->filter_save();
	}

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], array('show'))) {
			return false;
		}
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$r = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		);
		$order_fields = array(
			'name'		=> 'name',
			'active'	=> 'active',
		);
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
				'class' => 'form-vertical',
			))
			->text('name')
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'), array('horizontal' => true))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__static_pages_list ($params = array()) {
		$meta = array(
			'name' => 'Static pages quick access',
			'desc' => 'List of static pages with quick links to edit/preview',
			'configurable' => array(
				'order_by'	=> array('id','name','active'),
			),
		);
		if ($params['describe_self']) {
			return $meta;
		}
		$config = $params;
		$avail_orders = $meta['configurable']['order_by'];
		if (isset($avail_orders[$config['order_by']])) {
			$order_by_sql = ' ORDER BY '.db()->es($avail_orders[$config['order_by']].'');
		}
		$avail_limits = $meta['configurable']['limit'];
		if (isset($avail_limits[$config['limit']])) {
			$limit_records = (int)$avail_limits[$config['limit']];
		}
		$sql = 'SELECT * FROM '.db('static_pages'). $order_by_sql;
		return table($sql, array('no_header' => 1, 'btn_no_text' => 1))
			->link('name', './?object='.$_GET['object'].'&action=view&id=%d', '', array('width' => '100%'))
			->btn_edit()
		;
	}

}
