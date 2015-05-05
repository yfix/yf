<?php

/**
*/
class yf_manage_revisions {

	/** @var bool Track content revisions */
	public $ENABLED = true;
	/** @var array Restrict logged revisions to specific content objects */
	public $ALLOWED_OBJECTS = array();

	/**
	* Should be used from admin modules.
	* Examples:
	*	module_safe('manage_revisions')->add($table, $id, 'add');
	*	module_safe('manage_revisions')->add($table, array(1,2,3), 'delete');
	*	module_safe('manage_revisions')->add(array(
	*		'object_id' => $a['id'],
	*		'old'		=> $a,
	*		'new'		=> $_POST,
	*		'action'	=> 'update',
	*	));
	*/
	function add ($object_name, $ids = array(), $action = null, $extra = array()) {
		if (!$this->ENABLED) {
			return false;
		}
		if (is_array($object_name)) {
			$extra = (array)$extra + $object_name;
			$object_name = '';
		}
		$object_name = $extra['object_name'] ?: ($object_name ?: $_GET['object']);
		if (($allowed_objects = $this->ALLOWED_OBJECTS)) {
			if (!in_array($object_name, $allowed_objects)) {
				return false;
			}
		}
		$ids = $extra['object_id'] ?: ($extra['ids'] ?: $ids);
		if ($ids && !is_array($ids)) {
			$ids = array($ids);
		}
		$items = array();
		if (is_array($extra['items'])) {
			$items = $extra['items'];
		} elseif (is_array($ids) && !empty($ids)) {
			$records = (array)db()->from($object_name)->whereid($ids)->get_all();
			foreach ((array)$ids as $id) {
				$a = $records[$id];
				if ($a) {
					$items[$id] = array(
						'new'		=> $a,
						'locale'	=> $a['locale'],
					);
				}
			}
		}
		if (!$items) {
			return false;
		}
		$action = $extra['action'] ?: $action;
		if (!$action) {
			$action = 'update';
		}
		$to_insert = array(
			'action'		=> $action,
			'object_name'	=> $object_name,
			'date'			=> date('Y-m-d H:i:s'),
			'user_id'		=> main()->ADMIN_ID,
			'site_id'		=> conf('SITE_ID'),
			'server_id'		=> conf('SERVER_ID'),
			'ip'			=> common()->get_ip(),
			'url'			=> (main()->is_https() ? 'https://' : 'http://'). $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'],
			'user_agent'	=> $_SERVER['HTTP_USER_AGENT'],
		);
		foreach ((array)$items as $object_id => $data) {
			if (!$object_id) {
				continue;
			}
			$data_old = $data['old'] ?: ($extra['data_old'] ?: $extra['old']);
			$data_new = $data['new'] ?: ($extra['data_new'] ?: $extra['new']);
#			$data_stump_json = json_encode($data_stump);
#			$check_equal_data = db()->get_one('SELECT data FROM '.db('revisions').' WHERE item_id='.$id.' ORDER BY id DESC');
#			if ($data_stump_json == $check_equal_data) {
#				continue;
#			}
			if ($data_old && $data_old == $data_new) {
// TODO: do not save same data as new revision
#				continue;
			}
			$sql = db()->insert_safe('sys_revisions', $to_insert + array(
				'object_id'		=> $object_id,
				'locale'		=> $data['locale'] ?: $extra['locale'],
				'data_old'		=> is_array($data_old) ? 'json:'.json_encode($data_old) : (string)$data_old,
				'data_new'		=> is_array($data_new) ? 'json:'.json_encode($data_new) : (string)$data_new,
				'comment'		=> $data['comment'] ?: $extra['comment'],
			), $only_sql = true);
			db()->_add_shutdown_query($sql);
		}
		return true;
	}

	/**
	*/
	function show() {
/*
		return table('SELECT * FROM '.db('revisions'), array(
				'filter' => true,
				'filter_params' => array(
					'user_id'	=> array('eq','user_id'),
					'add_date'	=> array('dt_between','add_date'),
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
			->btn_view('', url('/@object/details/%d'))
		;
*/
	}

	/**
	*/
	function check_revision($function, $id, $db_table) {
/*
		if (empty($function) || empty($id) || empty($db_table)) {
			return false;
		}
		if (!is_array($id) && intval($id)) {
			$ids = array(intval($id));
		}
		$check_ids = db()->get_2d('SELECT id, item_id FROM '.db('revisions').' WHERE item_id IN ('.implode(',',$ids).') AND action=\''.$function.'\'');
		$ids = array_diff($ids, (array)$check_ids);
		return $this->new_revision($function, $ids, $db_table);
*/
	}

	/**
	*/
	function details() {
/*
		$sql = 'SELECT * FROM '.db('revisions').' WHERE id='.intval($_GET['id']);
		$a = db()->get($sql);
		if (empty($a)) {
			return _e('Revision not found');
		}
		return form($a, array(
				'dd_mode' => 1,
			))
			->link('Revisions list', url('/@object'))
			->admin_info('user_id')
			->info_date('add_date', array('format' => 'full'))
			->info('action')
			->link('Activate new version', url('/object/rollback_revision/%id'))
			->tab_start('View_difference')
				->func('data', function($extra, $r, $_this) {
					$origin = json_decode($r[$extra['name']], true);
					$before = db()->get('SELECT * FROM '.db('revisions').' WHERE id<'.$r['id'].' AND item_id='.$r['item_id'].' ORDER BY id DESC' );
					$before = json_decode($before[$extra['name']], true);
					$origin = var_export($origin, true);
					$before = var_export($before, true);
					return common()->get_diff($before, $origin);
				})
			->tab_end()
			->tab_start('New_version')
				->func('data', function($extra, $r, $_this) {
					return '<pre>'.var_export(json_decode($r[$extra['name']], true), 1).'</pre>';
				})
			->tab_end()
		;
*/
	}

	/**
	*/
	function filter_save() {
/*
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
		return js_redirect('/@object/'.$action);
*/
	}

	/**
	*/
	function _show_filter() {
/*
		$filters = array(
			'show'	=> function($filter_name, $replace) {
				$fields = array('id','add_date','action','item_id','ip', 'user_id');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				$action = db()->get_2d('SELECT DISTINCT action FROM '.db('revisions'));
				$action = array_combine($action, $action );
				return form($replace, array(
						'filter' => true,
					))
					->datetime_select('add_date',      null, array( 'with_time' => 1 ) )
					->datetime_select('add_date__and', null, array( 'with_time' => 1 ) )
					->text('user_id', 'Админ')
					->text('ip')
					->select_box('action', $action, array('no_translate' => 1, 'show_text' => 1))
					->select_box('order_by', $order_fields, array('no_translate' => 1,'show_text' => 1));
			},
		);
		$action = $_GET['action'];
		if (isset($filters[$action])) {
			return $filters[$action]($filter_name, $replace)
				->order_box()
				->save_and_clear();
		}
		return false;
*/
	}

	/**
	*/
	function _hook_side_column () {
/*
		$rev = db()->get('SELECT * FROM '.db('revisions').' WHERE id='.intval($_GET['id']));
		if (!$rev) {
			return false;
		}
		$sql = 'SELECT * FROM '.db('revisions').' WHERE item_id='.intval($rev['item_id']).' AND action !=\'\' ORDER BY id DESC';
		return table($sql, array(
				'caption' => t('Product revisions'),
				'no_records_html' => '',
				'tr' => array(
					$rev['id'] => array('class' => 'success'),
				),
				'pager_records_on_page' => 10,
				'btn_no_text'	=> 1,
				'no_header'	=> 1,
			))
			->date('add_date', array('format' => '%d/%m/%Y', 'nowrap' => 1))
			->admin('user_id', array('desc' => 'admin'))
			->text('action')
			->btn_view('', url('/@object/details/%id/@page'))
		;
*/
	}

	/**
	*/
	function rollback_revision() {
/*
		$_GET['id'] = intval($_GET['id']);
		$revision_data = db()->get('SELECT * FROM '.db('revisions').' WHERE id='.$_GET['id']);
		if (empty($revision_data)) {
			return _e('Revision not found');
		}
		if(empty($revision_data['table'])){
			return _e('Revision failed');
		}
		$data_stamp = json_decode($revision_data['data'], true);
		$check_db_row = db()->get('SELECT * FROM '.db($revision_data['table']).' WHERE id='.$revision_data['item_id']);
		db()->begin();
		if($data_stamp){
			if($check_db_row){
				db()->update_safe(db($revision_data['table']), $data_stamp, 'id ='.$revision_data['item_id']);
			}else{
				db()->insert_safe(db($revision_data['table']), $data_stamp);
			}
		}else{
			db()->query('DELETE FROM '.db($revision_data['table']).' WHERE id ='.$revision_data['item_id']);
		}
		$this->new_revision($revision_data['action'], $revision_data['item_id'], $revision_data['table']);
		db()->commit();
		common()->message_success('Revision retrieved');
		common()->admin_wall_add(array('Rollback common revision: '.$_GET['id'], $_GET['id']));
		return js_redirect('/@object/details/@id');
*/
	}
}
