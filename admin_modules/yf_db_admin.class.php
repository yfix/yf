<?php

class yf_db_admin {

	private $table_params = array(
		'pager_records_on_page' => 1000,
		'id'		=> 'name',
		'condensed'	=> 1,
		'no_total'	=> 1,
		'no_ajax'	=> 1,
		'no_records_html' => '',
	);
	private	$guarded_databases = array(
		'information_schema',
		'performance_schema',
		'mysql',
		'sys',
	);

	/***/
	function _init() {
		$this->_add_custom_navbar();
	}

	/***/
	function _db_custom_connection($db_name, $params = array()) {
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
			$instance->connect(array(
				'name'	=> $db_name,
				'prefix'=> '',
			) + (array)$params);
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
			$data[$name] = array(
				'name'	=> $name,
			);
		}
		return table($data, $this->table_params)
			->link('name', url_admin('/@object/database_show/%d/'), array(), array('class' => ' '))
			->btn_edit('Alter', url_admin('/@object/database_alter/%d/'))
			->btn_delete('Drop', url_admin('/@object/database_drop/%d/'))
			->header_add('Add database', url_admin('/@object/database_create/'))
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
		$a = array(
			'name'		=> $db_name,
			'back_link'	=> url_admin('/@object/databases_list/'),
		);
		return form((array)$_POST + $a)
			->validate(array('name' => 'trim|required|alpha_dash'))
			->on_validate_ok(function($data) use ($db_name) {
				db()->utils()->rename_database($db_name, $data['name']);
				common()->message_success('Database was successfully renamed: '.$db_name.' => '.$data['name']);
				return js_redirect(url_admin('/@object/databases_list/'));
			})
			->text('name')
			->save_and_back();
	}

	/**
	*/
	function database_create() {
		$a = array(
			'name'		=> '',
			'back_link'	=> url_admin('/@object/databases_list/'),
		);
		return form((array)$_POST + $a)
			->validate(array('name' => 'trim|required|alpha_dash'))
			->on_validate_ok(function($data) {
				db()->utils()->create_database($data['name']);
				common()->message_success('Database was successfully created: '.$data['name']);
				return js_redirect(url_admin('/@object/database_show/'.$data['name']));
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
		$a = array(
			'back_link'	=> url_admin('/@object/databases_list/'),
		);
		return form($a)
			->on_post(function($data) use ($db_name) {
				db()->utils()->drop_database($db_name);
				common()->message_success('Database was successfully dropped: '.$db_name);
				return js_redirect(url_admin('/@object/databases_list/'));
			})
			->info('Are you sure?')
			->save_and_back();
	}

	/***/
	function database_show() {
		$db_name = $this->_database_name($_GET['id']);
		if (!$db_name) {
			return _e('Wrong name');
		}
		$db = $this->_db_custom_connection($db_name);
		return _class('html')->tabs(array(
			'tables' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_tables() as $name) {
						$data[$name] = array('name'	=> $name);
					}; return $data;
				}, $this->table_params + array('feedback' => &$totals['tables']))
#				->check_box('name', array('th_desc' => '#'))
				->link('name', url_admin('/@object/table_show/'.$db_name.'.%d/'), array(), array('class' => ' '))
				->btn_edit('Indexes', url_admin('/@object/table_indexes/'.$db_name.'.%d/'))
				->btn_edit('Foreign keys', url_admin('/@object/table_foreign_keys/'.$db_name.'.%d/'))
				->btn_edit('Alter table', url_admin('/@object/table_alter/'.$db_name.'.%d/'))
				->btn_delete('Drop', url_admin('/@object/table_drop/'.$db_name.'.%d/'))
				->header_add('Create table', url_admin('/@object/table_create/'.$db_name.'/'))
			,
			'views' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_views() as $name) {
						$data[$name] = array('name'	=> $name);
					}; return $data;
				}, $this->table_params + array('feedback' => &$totals['views']))
#				->check_box('name', array('th_desc' => '#'))
				->link('name', url_admin('/@object/view_show/'.$db_name.'.%d/'), array(), array('class' => ' '))
				->btn_edit('Alter', url_admin('/@object/view_alter/'.$db_name.'.%d/'))
				->btn_delete('Drop', url_admin('/@object/view_drop/'.$db_name.'.%d/'))
				->header_add('Create view', url_admin('/@object/view_create/'.$db_name.'/', $this->btn_params))
			,
			'triggers' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_triggers() as $name) {
						$data[$name] = array('name'	=> $name);
					}; return $data;
				}, $this->table_params + array('feedback' => &$totals['triggers']))
#				->check_box('name', array('th_desc' => '#'))
				->link('name', url_admin('/@object/trigger_show/'.$db_name.'.%d/'), array(), array('class' => ' '))
				->btn_edit('Alter', url_admin('/@object/trigger_alter/'.$db_name.'.%d/'))
				->btn_delete('Drop', url_admin('/@object/trigger_drop/'.$db_name.'.%d/'))
				->header_add('Create trigger', url_admin('/@object/trigger_create/'.$db_name.'/'))
			,
			'procedures' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_procedures() as $name => $info) {
						$data[$name] = array('name'	=> $name);
					}; return $data;
				}, $this->table_params + array('feedback' => &$totals['procedures']))
#				->check_box('name', array('th_desc' => '#'))
				->link('name', url_admin('/@object/procedure_show/'.$db_name.'.%d/'), array(), array('class' => ' '))
				->btn_edit('Alter', url_admin('/@object/procedure_alter/'.$db_name.'.%d/'))
				->btn_delete('Drop', url_admin('/@object/procedure_drop/'.$db_name.'.%d/'))
				->header_add('Create procedure', url_admin('/@object/procedure_create/'.$db_name.'/'))
			,
			'functions' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_functions() as $name => $info) {
						$data[$name] = array('name'	=> $name);
					}; return $data;
				}, $this->table_params + array('feedback' => &$totals['functions']))
#				->check_box('name', array('th_desc' => '#'))
				->link('name', url_admin('/@object/function_show/'.$db_name.'.%d/'), array(), array('class' => ' '))
				->btn_edit('Alter', url_admin('/@object/function_alter/'.$db_name.'.%d/'))
				->btn_delete('Drop', url_admin('/@object/function_drop/'.$db_name.'.%d/'))
				->header_add('Create function', url_admin('/@object/function_create/'.$db_name.'/'))
			,
			'events' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_events() as $name) {
						$data[$name] = array('name'	=> $name);
					}; return $data;
				}, $this->table_params + array('feedback' => &$totals['events']))
#				->check_box('name', array('th_desc' => '#'))
				->link('name', url_admin('/@object/event_show/'.$db_name.'.%d/'), array(), array('class' => ' '))
				->btn_edit('Alter', url_admin('/@object/event_alter/'.$db_name.'.%d/'))
				->btn_delete('Drop', url_admin('/@object/event_drop/'.$db_name.'.%d/'))
				->header_add('Create event', url_admin('/@object/event_create/'.$db_name.'/'))
			,
		), array('hide_empty' => 0, 'totals' => $totals));
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
		return table2('SELECT * FROM '.$table, array(
				'auto_no_buttons' => 1,
				'db' => $db,
			))
			->btn_edit('', url_admin('/@object/table_edit/'.$db_name.'.'.$table.'.%d'))
			->btn_delete('', url_admin('/@object/table_delete/'.$db_name.'.'.$table.'.%d'))
			->footer_add('Insert', url_admin('/@object/table_add/'.$db_name.'.'.$table))
			->auto();
	}

	/**
	*/
	function table_indexes() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$id = intval($id);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function table_index_create() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$id = intval($id);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function table_index_alter() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$id = intval($id);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function table_index_drop() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$id = intval($id);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function table_foreign_keys() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$id = intval($id);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function table_foreign_key_create() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$id = intval($id);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function table_foreign_key_alter() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$id = intval($id);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
	}

	/**
	*/
	function table_foreign_key_drop() {
		list($db_name, $table, $id) = explode('.', $_GET['id']);
		$id = intval($id);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
		if (!$db_name || !$table || !$id) {
			return _e('Wrong params');
		}
		$db = $this->_db_custom_connection($db_name);
// TODO
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
		return _class('admin_methods')->edit(array(
			'table' 		=> $table,
			'back_link'		=> url_admin('/@object/table_show/'.$db_name.'.'.$table),
			'db'			=> $db,
			'return_form'	=> true,
			'input_id'		=> $id,
		));
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
		return _class('admin_methods')->add(array(
			'table' 		=> $table,
			'back_link'		=> url_admin('/@object/table_show/'.$db_name.'.'.$table),
			'db'			=> $db,
			'return_form'	=> true,
		));
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
		return _class('admin_methods')->delete(array(
			'table'		=> $table,
			'back_link'	=> url_admin('/@object/table_show/'.$db_name.'.'.$table),
			'db'		=> $db,
			'input_id'	=> $id,
		));
	}

	/**
	*/
	function table_alter() {
		list($db_name, $table) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
// TODO
	}

	/**
	*/
	function table_create() {
		list($db_name, $table) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
// TODO
	}

	/**
	*/
	function table_drop() {
		list($db_name, $table) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$table = $this->_table_name($table);
// TODO
	}

	/**
	*/
	function view_show() {
		list($db_name, $db_view) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_view = $this->_table_name($db_view);
// TODO
	}

	/**
	*/
	function view_alter() {
		list($db_name, $db_view) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_view = $this->_table_name($db_view);
// TODO
	}

	/**
	*/
	function view_create() {
		list($db_name, $db_view) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_view = $this->_table_name($db_view);
// TODO
	}

	/**
	*/
	function view_drop() {
		list($db_name, $db_view) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_view = $this->_table_name($db_view);
// TODO
	}

	/**
	*/
	function trigger_show() {
		list($db_name, $db_trigger) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_trigger = $this->_table_name($db_trigger);
// TODO
	}

	/**
	*/
	function trigger_alter() {
		list($db_name, $db_trigger) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_trigger = $this->_table_name($db_trigger);
// TODO
	}

	/**
	*/
	function trigger_create() {
		list($db_name, $db_trigger) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_trigger = $this->_table_name($db_trigger);
// TODO
	}

	/**
	*/
	function trigger_drop() {
		list($db_name, $db_trigger) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_trigger = $this->_table_name($db_trigger);
// TODO
	}

	/**
	*/
	function procedure_show() {
		list($db_name, $db_procedure) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_procedure = $this->_table_name($db_procedure);
// TODO
	}

	/**
	*/
	function procedure_alter() {
		list($db_name, $db_procedure) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_procedure = $this->_table_name($db_procedure);
// TODO
	}

	/**
	*/
	function procedure_create() {
		list($db_name, $db_procedure) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_procedure = $this->_table_name($db_procedure);
// TODO
	}

	/**
	*/
	function procedure_drop() {
		list($db_name, $db_procedure) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_procedure = $this->_table_name($db_procedure);
// TODO
	}

	/**
	*/
	function function_show() {
		list($db_name, $db_function) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_function = $this->_table_name($db_function);
// TODO
	}

	/**
	*/
	function function_alter() {
		list($db_name, $db_function) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_function = $this->_table_name($db_function);
// TODO
	}

	/**
	*/
	function function_create() {
		list($db_name, $db_function) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_function = $this->_table_name($db_function);
// TODO
	}

	/**
	*/
	function function_drop() {
		list($db_name, $db_function) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_function = $this->_table_name($db_function);
// TODO
	}

	/**
	*/
	function event_show() {
		list($db_name, $db_event) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_event = $this->_table_name($db_event);
// TODO
	}

	/**
	*/
	function event_alter() {
		list($db_name, $db_event) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_event = $this->_table_name($db_event);
// TODO
	}

	/**
	*/
	function event_create() {
		list($db_name, $db_event) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_event = $this->_table_name($db_event);
// TODO
	}

	/**
	*/
	function event_drop() {
		list($db_name, $db_event) = explode('.', $_GET['id']);
		$db_name = $this->_database_name($db_name);
		$db_event = $this->_table_name($db_event);
// TODO
	}

	/**
	*/
	function _add_custom_navbar() {
		if (main()->is_redirect() || main()->is_console() || main()->is_ajax()) {
			return false;
		}
		_class('core_events')->listen('block.prepend[center_area]', function(){
			$a = array(
				array('link' => url_admin('/home_page/'), 'name' => 'Home'),
				array('link' => url_admin('/@object/'), 'name' => 'Db admin'),
#				array('link' => url_admin('/@object/'), 'name' => 'Database'),
			);
/*
		$db_name = $this->_database_name($_GET['id']);
			$map = array(
				'database_show' => array('name' => $this->_database_name($_GET['id'])),
				'database_*' => array('link' => url_admin('/@object/'), 'name' => 'Home'),
			);
*/
// TODO: add custom navbar items depending on current page
			return _class('html')->breadcrumbs($a);
		});
	}
}