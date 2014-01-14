<?php

/**
* Comments management module
*/
class yf_manage_comments {

	public $TEXT_PREVIEW_LENGTH	= 0;

	public $_comments_actions	= array(
		'articles'		=> 'view',
		'blog'			=> 'show_single_post',
		'faq'			=> 'view',
		'gallery'		=> 'show_medium_size',
		'help'			=> 'view_answers',
		'news'			=> 'full_news',
		'que'			=> 'view',
		'reviews'		=> 'view_details',
		'user_profile'	=> 'show',
	);

	/**
	*/
	function show () {
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$default_filter = array(
			'order_by' => 'add_date',
			'order_direction' => 'desc',
		);
		$sql = 'SELECT * FROM '.db('comments');
		return table($sql, array(
				'filter' => (array)$_SESSION[$filter_name] + $default_filter,
				'filter_params' => array(
					'text'	=> 'like',
				),
			))
#			->check_box('id')
			->date('add_date', array('format' => 'full', 'nowrap' => 1))
			->text('object_name', array('link' => './?object='.$_GET['object'].'&action=redirect_view&id=%d', 'link_field_name' => 'id'))
			->text('object_id', 'oid')
			->text('text', array('max_length' => $this->TEXT_PREVIEW_LENGTH))
			->user('user_id')
			->text('ip', array('link' => './?object='.$_GET['object'].'&action=filter_save&page=clear&filter=ip:%d'))
			->btn_active()
			->btn_edit()
			->btn_delete()
		;
	}

	/**
	*/
	function redirect_view() {
		$_GET['id'] = intval($_GET['id']);
		$a = db()->get('SELECT * FROM '.db('comments').' WHERE id='.intval($_GET['id']));
		if (empty($a) || !$a['object_name'] || !$a['object_id']) {
			return js_redirect('./?object='.$_GET['object']);
		}
		return js_redirect('./?object='.$a['object_name'].'&action='.($this->_comments_actions[$a['object_name']] ?: 'edit').'&id='.$a['object_id']);
	}

	/**
	*/
	function edit () {
		$_GET['id'] = intval($_GET['id']);
		$a = db()->query_fetch('SELECT * FROM '.db('comments').' WHERE id='.intval($_GET['id']));
		if (empty($a)) {
			return _e('No such record');
		}
		if (main()->is_post()) {
			if (empty($_POST['text'])) {
				_re('Comment text required');
			}
			if (!common()->_error_exists()) {
				db()->update_safe('comments', array('text' => $_POST['text']), 'id='.intval($a['id']));
				return js_redirect('');
			}
		}
		return form($a + array(
				'back_link'	=> './?object='.$_GET['object'],
			))
			->info_date('add_date', 'full')
			->info('object_name')
			->info('object_id')
			->user_info('user_id')
			->textarea('text')
			->save_and_back()
		;
	}

	/**
	*/
	function delete () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			db()->query('DELETE FROM '.db('comments').' WHERE id='.intval($_GET['id']).' LIMIT 1');
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
	function active () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$a = db()->query_fetch('SELECT * FROM '.db('comments').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($a)) {
			db()->update('comments', array('active' => (int)!$a['active']), 'id='.intval($_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($a['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
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
		$order_fields = array();
		foreach (explode('|', 'user_id|add_date|ip|object_name|object_id|user_name|user_email|active') as $f) {
			$order_fields[$f] = $f;
		}
		foreach ((array)$this->_comments_actions as $k => $v) {
			$object_names[$k] = $k;
		}
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
			))
			->number('user_id')
			->text('ip')
			->select_box('object_name', $object_names, array('show_text' => 1))
			->number('object_id')
			->text('text')
			->active_box()
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__comments_stats ($params = array()) {
// TODO
	}

	/**
	*/
	function _hook_widget__comments_latest ($params = array()) {
// TODO
	}
}
