<?php

class yf_db_admin {

	private $table_params = array(
		'pager_records_on_page' => 1000,
		'id' => 'name',
		'condensed' => 1,
	);

	/***/
	function _db_custom_connection($db_name) {
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
	function _database_name() {
		return preg_replace('~[^a-z0-9_]~ims', '', $_GET['id']);
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
			->btn_edit('', url_admin('/@object/database_edit/%d/'))
			->btn_delete('', url_admin('/@object/database_delete/%d/'))
			->header_add('Add database', url_admin('/@object/database_add/'))
		;
	}

	/***/
	function database_show() {
		$db_name = $this->_database_name();
		if (!$db_name) {
			return _e('Wrong name');
		}
		$db = $this->_db_custom_connection($db_name);
		foreach ((array)$db->utils()->list_tables() as $name) {
			$tables[$name] = array(
				'name'	=> $name,
			);
		}
		return _class('html')->tabs(array(
			'tables' => table($tables, $this->table_params)
				->link('name', './?object='.$_GET['object'].'&action=table_show&id=%d', array(), array('class' => ' '))
				->btn_edit('', './?object='.$_GET['object'].'&action=table_edit&id=%d')
				->btn_delete('', './?object='.$_GET['object'].'&action=table_delete&id=%d')
				->header_add('Add database', './?object='.$_GET['object'].'&action=table_add')
			,
			'views' => table($views, $this->table_params)
				->link('name', './?object='.$_GET['object'].'&action=view_show&id=%d', array(), array('class' => ' '))
			,
			'triggers' => table($views, $this->table_params)
				->link('name', './?object='.$_GET['object'].'&action=trigger_show&id=%d', array(), array('class' => ' '))
			,
			'events' => table($views, $this->table_params)
				->link('name', './?object='.$_GET['object'].'&action=event_show&id=%d', array(), array('class' => ' '))
		));
	}

}