<?php

/**
* Database structure installer core
*/
abstract class yf_db_installer {

	/** @var array */
	public $TABLES_SQL				= array();
	/** @var array */
	public $TABLES_DATA				= array();
	/** @var bool */
	public $USE_CACHE				= true;
	/** @var bool */
	public $USE_LOCKING				= false;
	/** @var int */
	public $LOCK_TIMEOUT			= 600;
	/** @var string */
	public $LOCK_FILE_NAME			= 'db_installer.lock';
	/** @var bool */
	public $RESTORE_FULLTEXT_INDEX	= true;
	/** @var bool */
	public $USE_SQL_IF_NOT_EXISTS	= true;
	/** @var bool */
	public $SHARDING_BY_YEAR		= false;
	/** @var bool */
	public $SHARDING_BY_MONTH		= false;
	/** @var bool */
	public $SHARDING_BY_DAY			= false;
	/** @var bool */
	public $SHARDING_BY_COUNTRY		= false;
	/** @var bool */
	public $SHARDING_BY_LANG		= false;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Framework constructor
	*/
	function _init () {
		$this->LOCK_FILE_NAME = PROJECT_PATH. $this->LOCK_FILE_NAME;
		$this->_load_data_files();
	}

	/**
	*/
	abstract protected function _do_alter_table ($table_name, $column_name, $table_struct, $db);

	/**
	*/
	abstract protected function _auto_repair_table($sql, $db_error, $db);

	/**
	*/
	abstract protected function _do_create_table ($full_table_name, $table_struct, $db);

	/**
	* This method can be inherited in project with custom rules inside.
	* Or use array or pattern callbacks. Example:
	*	$this->create_table_pre_callbacks = array(
	*		'^b_bets.*' => function($table, $struct, $db, $m) {
	*			return $struct;
	*		}
	*	);
	*/
	function create_table_pre_hook($full_table_name, $table_struct, $db) {
		_class('core_events')->fire('db.before_create_table', array(
			'table'		=> $full_table_name,
			'struct'	=> $table_struct,
			'db'		=> $db,
		));
		foreach ((array)$this->create_table_pre_callbacks as $regex => $func) {
			if (!preg_match('/'.$regex.'/ims', $full_table_name, $m)) {
				continue;
			}
			$table_struct = $func($full_table_name, $table_struct, $db, $m);
		}
		return $table_struct;
	}

	/**
	* This method can be inherited in project with custom rules inside.
	*/
	function create_table_post_hook($full_table_name, $table_struct, $db) {
		_class('core_events')->fire('db.after_create_table', array(
			'table'		=> $full_table_name,
			'struct'	=> $table_struct,
			'db'		=> $db,
		));
		foreach ((array)$this->create_table_post_callbacks as $regex => $func) {
			if (!preg_match('/'.$regex.'/ims', $full_table_name, $m)) {
				continue;
			}
			$results[$regex] = $func($full_table_name, $table_struct, $db, $m);
		}
		return $results;
	}

	/**
	* This method can be inherited in project with custom rules inside
	*/
	function alter_table_pre_hook($table_name, $column_name, $table_struct, $db) {
		_class('core_events')->fire('db.before_alter_table', array(
			'table'		=> $table_name,
			'column'	=> $column_name,
			'struct'	=> $table_struct,
			'db'		=> $db,
		));
		foreach ((array)$this->alter_table_pre_callbacks as $table_regex => $func) {
			if (!preg_match('/'.$regex.'/ims', $table_name, $m)) {
				continue;
			}
			$table_struct = $func($table_name, $column_name, $table_struct, $db, $m);
		}
		return $table_struct;
	}

	/**
	* This method can be inherited in project with custom rules inside
	*/
	function alter_table_post_hook($table_name, $column_name, $table_struct, $db) {
		_class('core_events')->fire('db.after_alter_table', array(
			'table'		=> $table_name,
			'column'	=> $column_name,
			'struct'	=> $table_struct,
			'db'		=> $db,
		));
		foreach ((array)$this->alter_table_post_callbacks as $table_regex => $func) {
			if (!preg_match('/'.$regex.'/ims', $table_name, $m)) {
				continue;
			}
			$results[$regex] = $func($table_name, $column_name, $table_struct, $db, $m);
		}
		return $results;
	}

	/**
	*/
	function _load_data_files() {
		$data = array();
		// Load install data from external files
		$globs_sql = array(
			'yf_main'			=> YF_PATH.'share/db_installer/sql/*.sql.php',
			'yf_plugins'		=> YF_PATH.'plugins/*/share/db_installer/sql/*.sql.php',
			'project_config'	=> CONFIG_PATH.'share/db_installer/sql/*.sql.php',
			'project_main'		=> PROJECT_PATH.'share/db_installer/sql/*.sql.php',
			'project_plugins'	=> PROJECT_PATH.'plugins/*/share/db_installer/sql/*.sql.php',
		);
		foreach ($globs_sql as $glob) {
			foreach (glob($glob) as $f) {
				$t_name = substr(basename($f), 0, -strlen('.sql.php'));
				require_once $f; // $data should be loaded from file
				$this->TABLES_SQL[$t_name] = $data;
			}
		}
		$globs_data = array(
			'yf_main'			=> YF_PATH.'share/db_installer/data/*.data.php',
			'yf_plugins'		=> YF_PATH.'plugins/*/share/db_installer/data/*.data.php',
			'project_config'	=> CONFIG_PATH.'share/db_installer/data/*.data.php',
			'project_main'		=> PROJECT_PATH.'share/db_installer/data/*.data.php',
			'project_plugins'	=> PROJECT_PATH.'plugins/*/share/db_installer/data/*.data.php',
		);
		foreach ($globs_data as $glob) {
			foreach (glob($glob) as $f) {
				$t_name = substr(basename($f), 0, -strlen('.data.php'));
				require_once $f; // $data should be loaded from file
				$this->TABLES_DATA[$t_name] = $data;
			}
		}
		// Project has higher priority than framework (allow to change anything in project)
		// Try to load db structure from project file
		// Sample contents part: 	$project_data['OTHER_TABLES_STRUCTS'] = my_array_merge((array)$project_data['OTHER_TABLES_STRUCTS'], array(
		$structure_file = PROJECT_PATH. 'project_db_structure.php';
		if (file_exists($structure_file)) {
			include_once ($structure_file);
		}
		foreach((array)$project_data as $cur_array_name => $_cur_data) {
			$this->$cur_array_name = my_array_merge((array)$this->$cur_array_name, (array)$_cur_data);
		}
		// Compatibility with old codebase
		foreach ((array)$this->SYS_TABLES_STRUCTS as $k => $v) {
			$this->TABLES_SQL[$k] = $v;
		}
		foreach ((array)$this->OTHER_TABLES_STRUCTS as $k => $v) {
			$this->TABLES_SQL[$k] = $v;
		}
		foreach ((array)$this->SYS_TABLES_DATAS as $k => $v) {
			$this->TABLES_DATA[$k] = $v;
		}
		foreach ((array)$this->OTHER_TABLES_DATAS as $k => $v) {
			$this->TABLES_DATA[$k] = $v;
		}
	}

	/**
	* Do create table
	*/
	function create_table ($table_name, $db) {
		$table_found = false;
		if (empty($table_name)) {
			return false;
		}
		if (!$this->_get_lock()) {
			return false;
		}
		if (isset($this->TABLES_SQL[$table_name])) {
			$table_found = true;
		} elseif (isset($this->TABLES_SQL['sys_'.$table_name])) {
			$table_name	= 'sys_'.$table_name;
			$table_found = true;
		}
		if ($table_found) {
			$table_struct = $this->TABLES_SQL[$table_name];
			$table_data	= $this->TABLES_DATA[$table_name];
			$full_table_name = $db->DB_PREFIX. $table_name;
		} else {
			// Try if sharded table
			$shard = $this->_shard_table_struct($table_name, $data, $db);
			if ($shard) {
				$table_struct = $shard['struct'];
				$table_data	= $shard['data'];
				$full_table_name = $shard['name'];
			}
		}
		if (empty($table_struct)) {
			return false;
		}
		$table_struct = $this->create_table_pre_hook($full_table_name, $table_struct, $db);
		$result = $this->_do_create_table($full_table_name, $table_struct, $db);
		if (!$result) {
			return false;
		}
		$this->create_table_post_hook($full_table_name, $table_struct, $db);
		// Check if we also need to insert some data into new system table
		if ($table_data && is_array($table_data)) {
			$result = $db->insert_safe($full_table_name, $table_data);
		}
		$this->_release_lock();
		return $result;
	}

	/**
	* Do alter table structure
	*/
	function alter_table ($table_name, $column_name, $db) {
		if (!$this->_get_lock()) {
			return false;
		}
		if (substr($table_name, 0, strlen($db->DB_PREFIX)) == $db->DB_PREFIX) {
			$table_name = substr($table_name, strlen($db->DB_PREFIX));
		}
		$avail_tables = $db->meta_tables();
		if (!in_array($db->DB_PREFIX. $table_name, $avail_tables)) {
			return false;
		}
		$cache_name = __CLASS__.'__'.__FUNCTION__.'__'.$table_name;
		$data = array();
		if ($this->USE_CACHE) {
			$data = cache_get($cache_name);
		}
		if (!$data) {
			$data = $this->_db_table_struct_into_array($this->TABLES_SQL[$table_name]);
			cache_set($cache_name, $data);
		}
		if (!isset($data)) {
			return false;
		}
		$table_struct = $data['fields'];
		// Try if sharded table
		if (empty($table_struct)) {
			$shard = $this->_shard_table_struct($table_name, $data, $db);
			if ($shard) {
				$table_struct = $shard['struct'];
			}
		}
		if (!isset($table_struct[$column_name])) {
			return false;
		}
		$table_struct = $this->alter_table_pre_hook($table_name, $column_name, $table_struct, $db);
		$result = $this->_do_alter_table($table_name, $column_name, $table_struct, $db);
		$this->alter_table_post_hook($table_name, $column_name, $table_struct, $db);
		$this->_release_lock();
		return $result;
	}

	/**
	* Try to find table structure with sharding in mind
	*/
	function _shard_table_struct ($table_name, array $data, $db) {
		$table_struct = array();
		$shard_table_name = '';
		// Try sharding by year/month/day (example: db('currency_pairs_log_2013_07_01') from db('currency_pairs_log'))
		if (!$shard_table_name && $this->SHARDING_BY_DAY) {
			$name = $table_name;
			$shard_day = (int)substr($name, -strlen('01'));
			$shard_month = (int)substr($name, -strlen('07_01'), strlen('07'));
			$shard_year	= (int)substr($name, -strlen('2013_07_01'), strlen('2013'));
			$has_divider = (substr($name, -strlen('_2013_07_01'), 1) === '_');
			if ($has_divider && $shard_year >= 1970 && $shard_year <= 2050 && $shard_month >= 1 && $shard_month <= 12 && $shard_day >= 1 && $shard_day <= 31) {
				$shard_table_name = substr($name, 0, -strlen('_2013_07_01'));
			}
		}
		// Try sharding by year/month (example: db('stats_cars_2009_08'), db('stats_cars_2009_07'), db('stats_cars_2009_06') from db('stats_cars'))
		if (!$shard_table_name && $this->SHARDING_BY_MONTH) {
			$name = $table_name;
			$shard_month = (int)substr($name, -strlen('07'));
			$shard_year	= (int)substr($name, -strlen('2013_08'), strlen('2013'));
			$has_divider = (substr($name, -strlen('_2013_08'), 1) === '_');
			if ($has_divider && $shard_year >= 1970 && $shard_year <= 2050 && $shard_month >= 1 && $shard_month <= 12) {
				$shard_table_name = substr($name, 0, -8);
			}
		}
		// Try sharding by year (example: db('stats_cars_2009'), from db('stats_cars'))
		if (!$shard_table_name && $this->SHARDING_BY_YEAR) {
			$name = $table_name;
			$shard_year	= (int)substr($name, -strlen('2013'));
			$has_divider = (substr($name, -strlen('_2013'), 1) === '_');
			if ($has_divider && $shard_year >= 1970 && $shard_year <= 2050) {
				$shard_table_name = substr($name, 0, -strlen('_2009'));
			}
		}
		// Try sharding by lang (example: db('some_data_en'), db('some_data_ru') from db('some_data'))
		if (!$shard_table_name && $this->SHARDING_BY_LANG) {
			$name = $table_name;
			$shard_lang = substr($name, -strlen('ru'));
			$has_divider = (substr($name, -strlen('_ru'), 1) === '_');
			if ($has_divider && preg_match('/[a-z]{2}/', $shard_lang)) {
				$shard_table_name = substr($name, 0, -strlen('_ru'));
			}
		}
		// Try sharding by country (example: db('cars_es'), db('cars_uk'), db('cars_de') from db('cars'))
		if (!$shard_table_name && $this->SHARDING_BY_COUNTRY) {
			$name = $table_name;
			$shard_country = substr($name, -strlen('es'));
			$has_divider = (substr($name, -strlen('_es'), 1) === '_');
			if ($has_divider && preg_match('/[a-z]{2}/', $shard_country)) {
				$shard_table_name = substr($name, 0, -strlen('_es'));
			}
		}
		if ($shard_table_name) {
			if (isset($this->TABLES_SQL[$shard_table_name])) {
				$table_found = true;
			} elseif (isset($this->TABLES_SQL['sys_'.$shard_table_name])) {
				$table_found = true;
				$shard_table_name = 'sys_'.$shard_table_name;
				$table_name	= 'sys_'.$table_name;
			}
			if ($table_found) {
				$table_struct = $this->TABLES_SQL[$shard_table_name];
				$table_data	= $this->TABLES_DATA[$table_name]; // No error in name!
				$full_table_name = $db->DB_PREFIX. $table_name;
			}
		}
		return $table_struct ? array(
			'name'	=> $full_table_name,
			'struct'=> $table_struct,
			'data'	=> $table_data,
		) : false;
	}

	/**
	*/
	function _db_table_struct_into_array ($sql) {
		if (false !== strpos(strtoupper($sql), 'CREATE TABLE')) {
			$tmp_name = 'tmp_name_not_exists';
			$sql = 'CREATE TABLE `'.$tmp_name.'` ('.$sql.')';
		}
// TODO: support for commented params like this: /*Engine=InnoDB*/

		// Get table options from table structure
		// Example: /** ENGINE=MEMORY **/
/*
	public $_KNOWN_TABLE_OPTIONS = array(
		'ENGINE',
		'TYPE',
		'AUTO_INCREMENT',
		'AVG_ROW_LENGTH',
		'CHARACTER SET',
		'DEFAULT CHARACTER SET',
		'CHECKSUM',
		'COLLATE',
		'DEFAULT COLLATE',
		'COMMENT',
		'CONNECTION',
		'DATA DIRECTORY',
		'DELAY_KEY_WRITE',
		'INDEX DIRECTORY',
		'INSERT_METHOD',
		'MAX_ROWS',
		'MIN_ROWS',
		'PACK_KEYS',
		'PASSWORD',
		'ROW_FORMAT',
		'UNION',
	);
		if (preg_match('#\/\*\*([^\*\/]+)\*\*\/$#i', trim($table_struct), $m)) {
			// Cut comment with options from source table structure
			// to prevent misunderstanding
			$table_struct = str_replace($m[0], '', $table_struct);

			$_raw_options = str_replace(array("\r","\n","\t"), array('','',' '), trim($m[1]));

			$_pattern = '/('.implode('|', $this->_KNOWN_TABLE_OPTIONS).")[\s]{0,}=[\s]{0,}([\']{0,1}[^\'\,]+[\']{0,1})/ims";
			if (preg_match_all($_pattern, $_raw_options, $m)) {
				foreach ((array)$m[0] as $_id => $v) {
					$_option_key = strtoupper(trim($m[1][$_id]));
					$_option_val = trim($m[2][$_id]);
					if (!in_array($_option_key, $this->_KNOWN_TABLE_OPTIONS)) {
						continue;
					}
					$_options_to_merge[$_option_key] = $_option_val;
				}
			}
		}
*/

		$result = _class('db_ddl_parser_mysql', 'classes/db/')->parse($sql);
		if ($result && $tmp_name) {
			$result['name'] = $tmp_name;
		}
		return $result;
	}

	/**
	*/
	function _get_table_struct_array_by_name ($table_name, $db) {
		return $this->_db_table_struct_into_array( $db->get_one('SHOW CREATE TABLE `'.$table_name.'`') );
	}

	/**
	*/
	function _get_all_struct_array ($only_what, $db) {
		$structs_array = array();
		foreach ((array)$db->meta_tables() as $full_table_name) {
			$structs_array[substr($full_table_name, strlen($db->DB_PREFIX))] = $this->_get_table_struct_array_by_name($full_table_name, $db);
		}
		return $structs_array;
	}

	/**
	*/
	function _get_lock () {
		if (!$this->USE_LOCKING) {
			return true;
		}
		if (file_exists($this->LOCK_FILE_NAME)) {
			if ((time() - filemtime($this->LOCK_FILE_NAME)) > $this->LOCK_TIMEOUT) {
				unlink($this->LOCK_FILE_NAME);
			} else {
				return false;
			}
		}
		return file_put_contents($this->LOCK_FILE_NAME, time());
	}

	/**
	*/
	function _release_lock () {
		if (!$this->USE_LOCKING) {
			return true;
		}
		unlink($this->LOCK_FILE_NAME);
		return true;
	}
}
