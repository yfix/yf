<?php

/**
*/
class yf_manage_revisions {

	/**
	*/
	function show() {
		return table('SELECT * FROM '.db('shop_revisions'), array(
				'filter' => $_SESSION[$_GET['object'].'__show'],
				'filter_params' => array(
					'user_id'	=> array('eq','user_id'),
					'add_date'	=> array('like','date'),
					'action' 	=> array('eq','action'),
					'item_id' 	=> array('eq','item_id'),
					'ip'		=> array('like', 'ip'),
				),
				'hide_empty' => 1,
			))
			->date('add_date', array('format' => '%d-%m-%Y', 'nowrap' => 1))
			->admin('user_id', array('desc' => 'admin'))
			->text('ip')	
			->text('action')
			->text('item_id')
			->btn_view('', './?object=manage_revisions&action=details&id=%d')
			;
	}
	
	/**
	*/	
	function new_revision($function, $ids, $db_table){
		if(empty($function) || empty($ids) || empty($db_table))
			return false;
		if (!is_array($ids) && intval($ids)) {
			$ids = array(intval($ids));
		}
		$user_id = intval(main()->ADMIN_ID)?:intval($_GET['admin_id']);
		$add_rev_date = time();
		foreach((array)$ids as $id){
			$data_stump = db()->query_fetch('SELECT * FROM '.db($db_table).' WHERE id='.$id);
			$data_stump_json = json_encode($data_stump);
			$check_equal_data = db()->get_one('SELECT data FROM '.db('shop_revisions').' WHERE item_id='.$id.' ORDER BY id DESC');
			if($data_stump_json == $check_equal_data)
				continue;
			$insert = array(
				'user_id'  => $user_id,
				'add_date' => $add_rev_date,
				'action'   => $function,
				'item_id'  => $id,
				'ip'	   => common()->get_ip(),
				'data'     => $data_stump_json ? : '',
			);
			db()->insert_safe('shop_revisions', $insert);
			$revisions_ids[] = db()->insert_id();
		}
		return $revision_ids;
	}

	/**
	*/	
	function check_revision($function, $id, $db_table){
		if(empty($function) || empty($id) || empty($db_table))
			return false;
		if (!is_array($id) && intval($id)) {
			$ids = array(intval($id));
		}
		$check_ids = db()->get_2d('SELECT id, item_id FROM '.db('shop_revisions').' WHERE item_id IN ('.implode(',',$ids).') AND action=\''.$function.'\'');
		$ids = array_diff($ids, (array)$check_ids);
		return $this->new_revision($function, $ids, $db_table);
	}

	/**
	*/
	function details(){
		if (empty($_GET['id'])) {
			return _e('Empty revision id');
		}
		$sql = 'SELECT * FROM '.db('db_revisions').' WHERE id='.intval($_GET['id']);
		$a = db()->get($sql);
		if (empty($a['id'])) {
			return _e('Revision not found');
		}
		return form($a, array(
				'dd_mode' => 1,
			))
			->admin_info('user_id')
			->info_date('date', array('format' => 'full'))
			->info('query_table')
			->info('query_method')
			->info('ip')
			->info('url')
			->tab_start('new data')
				->func('data_new', function($extra, $r, $_this) {
					return '<pre>'.var_export(json_decode($r['data_new'], true), 1).'</pre>';
				})
			->tab_end()
			->tab_start('trace')
				->func('extra', function($extra, $r, $_this) {
					return '<pre>'.var_export(json_decode($r['extra'], true), 1).'</pre>';
				})
			->tab_end()
		;

	}

	/**
	*/
	function filter_save() {
		$_GET['id'] = preg_replace('~[^0-9a-z_]+~ims', '', $_GET['id']);
		if ($_GET['id'] && false !== strpos($_GET['id'], $_GET['object'].'__')) {
			$filter_name = $_GET['id'];
			list(,$action) = explode('__', $filter_name);
		}
		if ($_GET['page'] == 'clear') {
			$_SESSION[$filter_name] = array();
		} else {
			$_SESSION[$filter_name] = $_POST;
			foreach (explode('|', 'clear_url|form_id|submit') as $f) {
				if (isset($_SESSION[$filter_name][$f])) {
					unset($_SESSION[$filter_name][$f]);
				}
			}
		}
		return js_redirect('./?object='.$_GET['object'].'&action='.$action);
	}

	/**
	*/
	function _show_filter() {
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		);
		$filters = array(
			'show'	=> function($filter_name, $replace) {
				$fields = array('id','data','query_method','query_table','ip', 'user_id');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				$methods = db()->get_2d('SELECT DISTINCT query_method FROM '.db('db_revisions'));
				$method_fields = array_combine($methods, $methods);
				$tables = db()->get_2d('SELECT DISTINCT query_table FROM '.db('db_revisions'));
				$table_fields = array_combine($tables, $tables);
				return form($replace, array('selected' => $_SESSION[$filter_name], 'class' => 'form-horizontal form-condensed'))
					->text('add_date')
					->text('user_id')
					->text('ip')
					->select_box('query_method', $method_fields, array('no_translate' => 1, 'show_text' => 1))
					->select_box('query_table', $table_fields, array('no_translate' => 1, 'show_text' => 1))
					->select_box('order_by', $order_fields, array('show_text' => 1));
			},
		);
		$action = $_GET['action'];
		if (isset($filters[$action])) {
			return $filters[$action]($filter_name, $replace)
				->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'), array('horizontal' => 1))
				->save_and_clear();
		}
		return false;
	}
	
}