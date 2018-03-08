<?php

/**
*/
class yf_db_revisions {

	const table = 'db_revisions';

	/**
	*/
	function show() {
		return table('SELECT * FROM '.db(self::table), [
				'filter' => true,
				'filter_params' => [
					'user_id'	=> ['eq','user_id'],
					'add_date'	=> ['like','date'],
					'query_method' 	=> ['eq','query_method'],
					'query_table' 	=> ['eq','query_table'],
					'ip'		=> ['like', 'ip'],
				],
				'hide_empty' => 1,
			])
			->text('date')
			->admin('user_id', ['desc' => 'admin'])
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
		$sql = 'SELECT * FROM '.db(self::table).' WHERE id='.intval($_GET['id']);
		$a = db()->get($sql);
		if (empty($a['id'])) {
			return _e('Revision not found');
		}
		return form($a, [
				'dd_mode' => 1,
			])
			->admin_info('user_id')
			->info_date('date', ['format' => 'full'])
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
		$filters = [
			'show'	=> function($filter_name, $replace) {
				$fields = ['id','date','query_method','query_table','ip', 'user_id'];
				foreach ((array)$fields as $v) {
					$order_fields[$v] = $v;
				}
				$methods = db()->get_2d('SELECT DISTINCT query_method FROM '.db(self::table));
				$method_fields = array_combine($methods, $methods);
				$tables = db()->get_2d('SELECT DISTINCT query_table FROM '.db(self::table));
				$table_fields = array_combine($tables, $tables);
				return form($replace, [
						'filter' => true,
					])
					->text('add_date')
					->text('user_id')
					->text('ip')
					->select_box('query_method', $method_fields, ['no_translate' => 1, 'show_text' => 1])
					->select_box('query_table', $table_fields, ['no_translate' => 1, 'show_text' => 1])
					->select_box('order_by', $order_fields, ['show_text' => 1]);
			},
		];
		$action = $_GET['action'];
		if (isset($filters[$action])) {
			return $filters[$action]($filter_name, $replace)
				->order_box()
				->save_and_clear();
		}
		return false;
	}
	
}