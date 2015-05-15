<?php

/**
*/
class yf_db_revisions {

	/**
	*/
	function show() {
		return table('SELECT * FROM '.db('db_revisions'), array(
				'filter' => true,
				'filter_params' => array(
					'user_id'	=> array('eq','user_id'),
					'add_date'	=> array('like','date'),
					'query_method' 	=> array('eq','query_method'),
					'query_table' 	=> array('eq','query_table'),
					'ip'		=> array('like', 'ip'),
				),
				'hide_empty' => 1,
			))
			->text('date')
			->admin('user_id', array('desc' => 'admin'))
			->text('ip')	
			->text('query_method')
			->text('query_table')
			->btn_view('', url('/@object/details/%d'))
			;
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
		_class('admin_methods')->filter_save();
	}

	/**
	*/
	function _show_filter() {
		$filters = array(
			'show'	=> function($filter_name, $replace) {
				$fields = array('id','date','query_method','query_table','ip', 'user_id');
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				$methods = db()->get_2d('SELECT DISTINCT query_method FROM '.db('db_revisions'));
				$method_fields = array_combine($methods, $methods);
				$tables = db()->get_2d('SELECT DISTINCT query_table FROM '.db('db_revisions'));
				$table_fields = array_combine($tables, $tables);
				return form($replace, array(
						'filter' => true,
					))
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
				->order_box()
				->save_and_clear();
		}
		return false;
	}
	
}