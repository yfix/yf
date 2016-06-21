<?php

/**
* Database administration tool
*/
class yf_db_admin {

	/***/
	private	$guarded_databases = [
		'information_schema',
		'performance_schema',
		'mysql',
		'sys',
	];
	/***/
	private $table_params = [
		'pager_records_on_page' => 1000,
		'id'		=> 'name',
		'condensed'	=> 1,
		'no_total'	=> 1,
		'no_ajax'	=> 1,
		'no_records_html' => '',
	];

	/***/
	function _init() {
		$this->_add_custom_navbar();
	}

	/***/
	function _db_custom_connection($db_name, $params = []) {
		if (!$db_name) {
			return false;
		}
		if (isset($this->_connections[$db_name])) {
			return $this->_connections[$db_name];
		}
		$instance = null;
		$db_class = load_db_class();
		if ($db_class) {
			$instance = new $db_class('mysql5');
			$instance->connect([
				'name'	=> $db_name,
				'prefix'=> '',
			] + (array)$params);
		}
		$this->_connections[$db_name] = $instance;
		return $instance;
	}

	/***/
	function _database_name($db_name) {
		return preg_replace('~[^a-z0-9_]~ims', '', $db_name);
	}

	/**
	*/
	function _table_name($table) {
		return preg_replace('/[^a-z0-9_]+/ims', '', $table);
	}

	/***/
	function show() {
		return $this->databases_list();
	}

	/***/
	function databases_list() {
		foreach ((array)db()->utils()->list_databases() as $name) {
			$data[$name] = [
				'name'	=> $name,
			];
		}
		return table($data, $this->table_params)
			->link('name', url('/@object/database_show/%d/'), [], ['class' => ' '])
			->btn_edit('Alter', url('/@object/database_alter/%d/'))
			->btn_delete('Drop', url('/@object/database_drop/%d/'))
			->header_add('Add database', url('/@object/database_create/'))
		;
	}

	/**
	*/
	function database_alter() {
		$db_name = $this->_database_name($_GET['id']);
		if (!$db_name) {
			return _e('Wrong name');
		}
		$all_databases = db()->utils()->list_databases();
		$all_databases = array_combine($all_databases, $all_databases);
		if (!isset($all_databases[$db_name])) {
			return _e('Database not exists');
		}
		if (in_array($db_name, $this->guarded_databases)) {
			return _e('Database is read-only');
		}
		$a = [
			'name'		=> $db_name,
			'back_link'	=> url('/@object/databases_list/'),
		];
		return form((array)$_POST + $a)
			->validate(['name' => 'trim|required|alpha_dash'])
			->on_validate_ok(function($data) use ($db_name) {
				db()->utils()->rename_database($db_name, $data['name']);
				common()->message_success('Database was successfully renamed: '.$db_name.' => '.$data['name']);
				return js_redirect(url('/@object/databases_list/'));
			})
			->text('name')
			->save_and_back();
	}

	/**
	*/
	function database_create() {
		$a = [
			'name'		=> '',
			'back_link'	=> url('/@object/databases_list/'),
		];
		return form((array)$_POST + $a)
			->validate(['name' => 'trim|required|alpha_dash'])
			->on_validate_ok(function($data) {
				db()->utils()->create_database($data['name']);
				common()->message_success('Database was successfully created: '.$data['name']);
				return js_redirect(url('/@object/database_show/'.$data['name']));
			})
			->text('name')
			->save_and_back();
	}

	/**
	*/
	function database_drop() {
		$db_name = $this->_database_name($_GET['id']);
		if (!$db_name) {
			return _e('Wrong name');
		}
		$a = [
			'back_link'	=> url('/@object/databases_list/'),
		];
		return form($a)
			->on_post(function($data) use ($db_name) {
				db()->utils()->drop_database($db_name);
				common()->message_success('Database was successfully dropped: '.$db_name);
				return js_redirect(url('/@object/databases_list/'));
			})
			->info('Are you sure?')
			->save_and_back();
	}

	/***/
	function database_show_ajax() {
		no_graphics(true);
		$db_name = $this->_database_name($_GET['id']);
		if (!$db_name) {
			return false;
		}
		$db = $this->_db_custom_connection($db_name);
		$data = [
			'indexes'		=> (array)$db->utils()->list_all_indexes($db_name),
			'foreign_keys'	=> (array)$db->utils()->list_all_foreign_keys($db_name),
			'triggers'		=> (array)$db->utils()->list_all_triggers($db_name),
		];
		foreach ((array)$data as $k => $v) {
			foreach ((array)$v as $table => $info) {
				$data[$k][$table] = count($info);
			}
			if (empty($data[$k])) {
				unset($data[$k]);
			}
		}
		header('Content-type: text/json', $replace = true);
		print json_encode($data);
		exit();
	}

	/***/
	function database_show() {
		$db_name = $this->_database_name($_GET['id']);
		if (!$db_name) {
			return _e('Wrong name');
		}
		$db = $this->_db_custom_connection($db_name);

		return _class('html')->tabs([
			'tables' => table(
				function() use ($db, $db_name) {
					foreach ((array)$db->utils()->list_tables_details() as $name => $a) {
						$data[$name] = [
							'name'			=> $name,
							'engine'		=> $a['engine'],
							'collation'		=> $a['collation'],
							'rows'			=> $a['rows'],
							'data_size'		=> $a['data_size'],
							'indexes'		=> count($all_indexes[$name]),
							'foreign_keys'	=> count($all_foreign[$name]),
							'triggers'		=> count($all_triggers[$name]),
						];
					}; return $data;
				}, $this->table_params + [
					'feedback' => &$totals['tables'],
					'data-postload-url' => url('/@object/database_show_ajax/'.$db_name.'/'),
					'tr' => function($row, $_id) {
						return ' id="tr_'.$_id.'"';
					},
#					'th' => function($_id) {
#						return ' id="th_'.$_id.'"';
#					},
				])
				->check_box('name', ['th_desc' => '#'])
				->link('name', url('/@object/table_show/'.$db_name.'.%d/'))
				->text('engine')
				->text('collation')
				->text('rows')
				->text('data_size')
				->link('indexes', url('/@object/indexes/'.$db_name.'.%d/'), [], ['link_field_name' => 'name', 'link_title' => 'Alter indexes', 'th_id' => 'th_indexes'])
				->link('foreign_keys', url('/@object/foreign_keys/'.$db_name.'.%d/'), [], ['link_field_name' => 'name', 'link_title' => 'Alter foreign keys', 'th_id' => 'th_foreign_keys'])
				->link('triggers', url('/@object/triggers/'.$db_name.'.%d/'), [], ['link_field_name' => 'name', 'link_title' => 'Alter triggers', 'th_id' => 'th_triggers'])
				->btn_edit('Alter table', url('/@object/table_alter/'.$db_name.'.%d/'), ['btn_no_text' => 1])
				->btn_delete('Drop', url('/@object/table_drop/'.$db_name.'.%d/'), ['btn_no_text' => 1])
				->header_add('Create table', url('/@object/table_create/'.$db_name.'/'))
			,
			'views' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_views() as $name) {
						$data[$name] = ['name'	=> $name];
					}; return $data;
				}, $this->table_params + ['feedback' => &$totals['views']])
				->link('name', url('/@object/view_show/'.$db_name.'.%d/'), [], ['class' => ' '])
				->btn_edit('Alter', url('/@object/view_alter/'.$db_name.'.%d/'))
				->btn_delete('Drop', url('/@object/view_drop/'.$db_name.'.%d/'))
				->header_add('Create view', url('/@object/view_create/'.$db_name.'/', $this->btn_params))
			,
			'procedures' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_procedures() as $name => $info) {
						$data[$name] = ['name'	=> $name];
					}; return $data;
				}, $this->table_params + ['feedback' => &$totals['procedures']])
				->link('name', url('/@object/procedure_show/'.$db_name.'.%d/'), [], ['class' => ' '])
				->btn_edit('Alter', url('/@object/procedure_alter/'.$db_name.'.%d/'))
				->btn_delete('Drop', url('/@object/procedure_drop/'.$db_name.'.%d/'))
				->header_add('Create procedure', url('/@object/procedure_create/'.$db_name.'/'))
			,
			'functions' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_functions() as $name => $info) {
						$data[$name] = ['name'	=> $name];
					}; return $data;
				}, $this->table_params + ['feedback' => &$totals['functions']])
				->link('name', url('/@object/function_show/'.$db_name.'.%d/'), [], ['class' => ' '])
				->btn_edit('Alter', url('/@object/function_alter/'.$db_name.'.%d/'))
				->btn_delete('Drop', url('/@object/function_drop/'.$db_name.'.%d/'))
				->header_add('Create function', url('/@object/function_create/'.$db_name.'/'))
			,
			'events' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_events() as $name) {
						$data[$name] = ['name'	=> $name];
					}; return $data;
				}, $this->table_params + ['feedback' => &$totals['events']])
				->link('name', url('/@object/event_show/'.$db_name.'.%d/'), [], ['class' => ' '])
				->btn_edit('Alter', url('/@object/event_alter/'.$db_name.'.%d/'))
				->btn_delete('Drop', url('/@object/event_drop/'.$db_name.'.%d/'))
				->header_add('Create event', url('/@object/event_create/'.$db_name.'/'))
			,
		], ['hide_empty' => 0, 'totals' => $totals]);
	}

	/**
	*/
	function table_show() {
		list($db_name, $table) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
		return table2('SELECT * FROM '.$table, [
				'auto_no_buttons' => 1,
				'db' => $db,
			])
			->btn_edit('', url('/@object/table_edit/'.$db_name.'.'.$table.'.%d'))
			->btn_delete('', url('/@object/table_delete/'.$db_name.'.'.$table.'.%d'))
			->footer_add('Insert', url('/@object/table_add/'.$db_name.'.'.$table))
			->auto();
	}

	/**
	*/
	function table_edit() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$id = intval($id);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
		return _class('admin_methods')->edit([
			'table' 		=> $table,
			'back_link'		=> url('/@object/table_show/'.$db_name.'.'.$table),
			'db'			=> $db,
			'return_form'	=> true,
			'input_id'		=> $id,
		]);
	}

	/**
	*/
	function table_add() {
		list($db_name, $table) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
		return _class('admin_methods')->add([
			'table' 		=> $table,
			'back_link'		=> url('/@object/table_show/'.$db_name.'.'.$table),
			'db'			=> $db,
			'return_form'	=> true,
		]);
	}

	/**
	*/
	function table_delete() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$id = intval($id);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
		return _class('admin_methods')->delete([
			'table'		=> $table,
			'back_link'	=> url('/@object/table_show/'.$db_name.'.'.$table),
			'db'		=> $db,
			'input_id'	=> $id,
		]);
	}

	/**
	*/
	function table_alter() {
		list($db_name, $table) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table) {
			return _e('Wrong params');
		}
// TODO
	}

	/**
	*/
	function table_create() {
		list($db_name, $table) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name) {
			return _e('Wrong params');
		}
// TODO
	}

	/**
	*/
	function table_drop() {
		list($db_name, $table) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table) {
			return _e('Wrong params');
		}
		$a = [
			'back_link'	=> url('/@object/databases_list/'),
		];
		return form($a)
			->on_post(function($data) use ($db_name, $table) {
				db()->utils()->drop_table($db_name, $table);
				common()->message_success('Table was successfully dropped: '.$db_name.'.'.$table);
				return js_redirect(url('/@object/database_show/'.$db_name.'/'));
			})
			->info('Are you sure?')
			->save_and_back();
	}

	/**
	*/
	function indexes() {
		list($db_name, $table) = explode('.', $_GET['id']);
		$id = intval($id);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
		$data = [];
		foreach ((array)db()->utils()->list_indexes($table) as $name => $info) {
			$info['columns'] = implode(',', $info['columns']);
			$data[$name] = $info;
		}
		return table2($data, $this->table_params + [
				'auto_no_buttons' => 1,
				'db' => $db,
				'id' => 'name',
			])
			->btn_edit('', url('/@object/index_alter/'.$db_name.'.'.$table.'.%d'))
			->btn_delete('', url('/@object/index_drop/'.$db_name.'.'.$table.'.%d'))
			->footer_add('Add index', url('/@object/index_create/'.$db_name.'.'.$table))
			->auto();
	}

	/**
	*/
	function index_create() {
		list($db_name, $table) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function index_alter() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		$id = $this->_table_name($id);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function index_drop() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		$id = $this->_table_name($id);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function foreign_keys() {
		list($db_name, $table) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
		$data = [];
		foreach ((array)db()->utils()->list_foreign_keys($table) as $name => $info) {
			$info['columns'] = implode(',', $info['columns']);
			$data[$name] = $info;
		}
		return table2($data, $this->table_params + [
				'auto_no_buttons' => 1,
				'db' => $db,
			])
			->btn_edit('', url('/@object/foreign_key_alter/'.$db_name.'.'.$table.'.%d'))
			->btn_delete('', url('/@object/foreign_key_drop/'.$db_name.'.'.$table.'.%d'))
			->footer_add('Add foreign key', url('/@object/foreign_key_create/'.$db_name.'.'.$table))
			->auto();
	}

	/**
	*/
	function foreign_key_create() {
		list($db_name, $table) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function foreign_key_alter() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		$id = $this->_table_name($id);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function foreign_key_drop() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		$id = $this->_table_name($id);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function triggers() {
		list($db_name, $table) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		$db = $this->_db_custom_connection($db_name);
// TODO
/*
// TODO: triggers need to be shown for table, not for database
			'triggers' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_triggers() as $name) {
						$data[$name] = array('name'	=> $name);
					}; return $data;
				}, $this->table_params + array('feedback' => &$totals['triggers']))
				->link('name', url('/@object/triggers/'.$db_name.'.%d/'), array(), array('class' => ' '))
				->btn_edit('Alter', url('/@object/trigger_alter/'.$db_name.'.%d/'))
				->btn_delete('Drop', url('/@object/trigger_drop/'.$db_name.'.%d/'))
				->header_add('Create trigger', url('/@object/trigger_create/'.$db_name.'/'))
			,
*/
	}

	/**
	*/
	function trigger_alter() {
		list($db_name, $db_trigger) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_trigger = $this->_table_name($db_trigger);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function trigger_create() {
		list($db_name, $db_trigger) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_trigger = $this->_table_name($db_trigger);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function trigger_drop() {
		list($db_name, $db_trigger) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_trigger = $this->_table_name($db_trigger);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function view_show() {
		list($db_name, $db_view) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_view = $this->_table_name($db_view);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function view_alter() {
		list($db_name, $db_view) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_view = $this->_table_name($db_view);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function view_create() {
		list($db_name, $db_view) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_view = $this->_table_name($db_view);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function view_drop() {
		list($db_name, $db_view) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_view = $this->_table_name($db_view);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function procedure_show() {
		list($db_name, $db_procedure) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_procedure = $this->_table_name($db_procedure);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function procedure_alter() {
		list($db_name, $db_procedure) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_procedure = $this->_table_name($db_procedure);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function procedure_create() {
		list($db_name, $db_procedure) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_procedure = $this->_table_name($db_procedure);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function procedure_drop() {
		list($db_name, $db_procedure) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_procedure = $this->_table_name($db_procedure);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function function_show() {
		list($db_name, $db_function) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_function = $this->_table_name($db_function);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function function_alter() {
		list($db_name, $db_function) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_function = $this->_table_name($db_function);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function function_create() {
		list($db_name, $db_function) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_function = $this->_table_name($db_function);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function function_drop() {
		list($db_name, $db_function) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_function = $this->_table_name($db_function);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function event_show() {
		list($db_name, $db_event) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_event = $this->_table_name($db_event);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function event_alter() {
		list($db_name, $db_event) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_event = $this->_table_name($db_event);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function event_create() {
		list($db_name, $db_event) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_event = $this->_table_name($db_event);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function event_drop() {
		list($db_name, $db_event) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_event = $this->_table_name($db_event);
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function _add_custom_navbar() {
		if (main()->is_redirect() || main()->is_console() || main()->is_ajax()) {
			return false;
		}
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$patterns = [
			'^/@object/(show|list_databases)' => 'Databases',
			'^/@object/database_([a-z0-9_]+)' => function($m) use($db_name) {
				$name = ucwords(str_replace('_', ' ', $m[1]));
				return [
					['Database: '.$db_name, url('/@object/database_show/'.$db_name)],
					[$name],
				];
			},
			'^/@object/table_([a-z0-9_]+)' => function($m) use($db_name, $table) {
				$name = ucwords(str_replace('_', ' ', $m[1]));
				return [
					['Database: '.$db_name, url('/@object/database_show/'.$db_name)],
					['Table: '.$table, url('/@object/table_show/'.$db_name.'.'.$table)],
					[$name],
				];
			},
			'^/@object/((?P<name2>indexes|triggers|foreign_keys)|(index|trigger|foreign_key)_(?P<name>[a-z0-9_]+))' => function($m) use($db_name, $table) {
				$name = ucwords(str_replace('_', ' ', $m['name'] ?: $m[1]));
				$name2 = $m['name2'] ? ucwords(str_replace('_', ' ', $m['name2'])) : '';
				return [
					['Database: '.$db_name, url('/@object/database_show/'.$db_name)],
					['Table: '.$table, url('/@object/table_show/'.$db_name.'.'.$table)],
					$name2 ? [$name2, url('/@object/'.$m['name2'].'/'.$db_name.'.'.$table)] : '',
					[$name],
				];
			},
		];
		_class('core_events')->listen('block.prepend[center_area]', function() use ($patterns) {
			$a = [
				[
					'name' => 'Home',
					'link' => url('/home_page/'),
				],
				[
					'name' => 'Db admin',
					'link' => url('/@object/'),
				],
			];
			$cur_url = '/@object/'.$_GET['action'].'/'.$_GET['id'];
			foreach ($patterns as $pattern => $val) {
				if (!preg_match('~'.$pattern.'~is', $cur_url, $m)) {
					continue;
				}
				if (is_array($val)) {
					foreach ($val as $item) {
						$a[] = [
							'name' => $item[0] ?: $item['name'],
							'link' => $item[1] ?: $item['link'],
						];
					}
				} elseif (is_callable($val)) {
					foreach ($val($m) as $item) {
						$a[] = [
							'name' => $item[0] ?: $item['name'],
							'link' => $item[1] ?: $item['link'],
						];
					}
				} elseif (is_string($val)) {
					$a[] = ['name' => $val];
				}
			}
			return _class('html')->breadcrumbs($a);
		});
	}
}