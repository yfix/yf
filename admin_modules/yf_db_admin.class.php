<?php

class yf_db_admin {

	private $table_params = array(
		'pager_records_on_page' => 1000,
		'id' => 'name',
		'condensed' => 1,
		'show_total' => 0,
		'no_records_html' => '', 
	);

	/***/
	function _init() {
		if (main()->is_common_page()) {
			$this->_add_custom_navbar();
		}
	}

	/***/
	function _db_custom_connection($db_name) {
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
			));
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
				->link('name', url_admin('/@object/table_show/'.$db_name.'.%d/'), array(), array('class' => ' '))
				->btn_edit('Alter', url_admin('/@object/table_alter/'.$db_name.'.%d/'))
				->btn_delete('Drop', url_admin('/@object/table_drop/'.$db_name.'.%d/'))
				->header_add('Create database', url_admin('/@object/table_create/'.$db_name.'/'))
			,
			'views' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_views() as $name) {
						$data[$name] = array('name'	=> $name);
					}; return $data;
				}, $this->table_params + array('feedback' => &$totals['views']))
				->link('name', url_admin('/@object/view_show/'.$db_name.'.%d/'), array(), array('class' => ' '))
				->header_add('Create view', url_admin('/@object/view_create/'.$db_name.'/'))
			,
			'triggers' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_triggers() as $name) {
						$data[$name] = array('name'	=> $name);
					}; return $data;
				}, $this->table_params + array('feedback' => &$totals['triggers']))
				->link('name', url_admin('/@object/trigger_show/'.$db_name.'.%d/'), array(), array('class' => ' '))
				->header_add('Create trigger', url_admin('/@object/trigger_create/'.$db_name.'/'))
			,
			'procedures' => table(
				function() use ($db) {
#					foreach ((array)$db->utils()->list_procedures() as $name) {
#						$data[$name] = array('name'	=> $name);
#					}; return $data;
				}, $this->table_params + array('feedback' => &$totals['procedures']))
				->link('name', url_admin('/@object/procedure_show/'.$db_name.'.%d/'), array(), array('class' => ' '))
				->header_add('Create procedure', url_admin('/@object/procedure_create/'.$db_name.'/'))
			,
			'functions' => table(
				function() use ($db) {
#					foreach ((array)$db->utils()->list_functions() as $name) {
#						$data[$name] = array('name'	=> $name);
#					}; return $data;
				}, $this->table_params + array('feedback' => &$totals['functions']))
				->link('name', url_admin('/@object/function_show/'.$db_name.'.%d/'), array(), array('class' => ' '))
				->header_add('Create function', url_admin('/@object/function_create/'.$db_name.'/'))
			,
			'events' => table(
				function() use ($db) {
					foreach ((array)$db->utils()->list_events() as $name) {
						$data[$name] = array('name'	=> $name);
					}; return $data;
				}, $this->table_params + array('feedback' => &$totals['events']))
				->link('name', url_admin('/@object/event_show/'.$db_name.'.%d/'), array(), array('class' => ' '))
				->header_add('Create event', url_admin('/@object/event_create/'.$db_name.'/'))
			,
		), array('hide_empty' => 0, 'totals' => $totals));
	}

	/**
	*/
	function database_alter() {
// TODO
	}

	/**
	*/
	function database_create() {
// TODO
	}

	/**
	*/
	function database_drop() {
// TODO
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
// TODO
	}

	/**
	*/
	function table_create() {
// TODO
	}

	/**
	*/
	function table_drop() {
// TODO
	}

	/**
	*/
	function view_show() {
// TODO
	}

	/**
	*/
	function view_alter() {
// TODO
	}

	/**
	*/
	function view_create() {
// TODO
	}

	/**
	*/
	function view_drop() {
// TODO
	}

	/**
	*/
	function trigger_show() {
// TODO
	}

	/**
	*/
	function trigger_alter() {
// TODO
	}

	/**
	*/
	function trigger_create() {
// TODO
	}

	/**
	*/
	function trigger_drop() {
// TODO
	}

	/**
	*/
	function procedure_show() {
// TODO
	}

	/**
	*/
	function procedure_alter() {
// TODO
	}

	/**
	*/
	function procedure_create() {
// TODO
	}

	/**
	*/
	function procedure_drop() {
// TODO
	}

	/**
	*/
	function function_show() {
// TODO
	}

	/**
	*/
	function function_alter() {
// TODO
	}

	/**
	*/
	function function_create() {
// TODO
	}

	/**
	*/
	function function_drop() {
// TODO
	}

	/**
	*/
	function event_show() {
// TODO
	}

	/**
	*/
	function event_alter() {
// TODO
	}

	/**
	*/
	function event_create() {
// TODO
	}

	/**
	*/
	function event_drop() {
// TODO
	}

	/**
	*/
	function _add_custom_navbar() {
		_class('core_events')->listen('block.prepend[center_area]', function(){
			$a = array(
				array('link' => url('/home_page'), 'name' => 'Home'),
				array('link' => url('/@object'), 'name' => 'Db admin'),
			);
// TODO: add custom navbar items depending on current page
			return _class('html')->breadcrumbs($a);
		});
	}
}