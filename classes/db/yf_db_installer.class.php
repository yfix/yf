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
	public $USE_SQL_IF_NOT_EXISTS	= true;
	/** @var array @conf_skip Required patterns */
	public $_patterns	= array(
		'table'		=> "/^CREATE[\s\t]*TABLE[\s\t]*[\`]{0,1}([^\s\t\`]+)[\`]{0,1}[\s\t]*\((.*)\)([^\(]*)\$/ims",
		'split'		=> "/[\n]+,?/",
		'field'		=> "/[\`]{0,1}([^\s\t\`]+)[\`]{0,1}[\s\t]+?([^\s\t]+)(.*)/ims",
// TODO: key could contain several fields
		'key'		=> "/(PRIMARY|UNIQUE){0,1}[\s\t]*?KEY[\s\t]*?[\`]{0,1}([a-z\_]*)[\`]{0,1}[\s\t]*?\(([^\)]+)\)/ims",
// TODO: character_set with collate
		'collate'	=> "/collate[\s\t][\"\'][a-z\_][\"\']/i",
		'default'	=> '/(,|unsigned|not null|null|zerofill|auto_increment|default)/i',
		'type'		=> '/([a-z]+)[\(]*([^\)]*)[\)]*/ims',
		'comment'	=> '#\/\*\*([^\*\/]+)\*\*\/$#i',
// TODO: support for foreign keys
// TODO: support for partitions
	);
	/** @var int Lifetime for caches */
	public $CACHE_TTL				= 86400; // 1*3600*24 = 1 day
	/** @var bool */
	public $USE_LOCKING				= false;
	/** @var int */
	public $LOCK_TIMEOUT			= 600;
	/** @var string */
	public $LOCK_FILE_NAME			= 'db_installer.lock';
	/** @var bool */
	public $RESTORE_FULLTEXT_INDEX	= true;
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
			$shard = $this->_shard_table_struct($table_name, $data, $db);
			if ($shard) {
				$table_found = $shard['found'];
				$table_struct = $shard['struct'];
				$table_data	= $shard['data'];
				$full_table_name = $shard['name'];
			}
		}
		// Stop here if we do not know about given table name
		if (!$table_found || empty($table_struct)) {
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
		$data = cache_get($cache_name);
		if (!$data) {
			$data = $this->_db_table_struct_into_array($this->TABLES_SQL[$table_name]);
			cache_set($cache_name, $data);
		}
		if (!isset($data)) {
			return false;
		}
		$table_struct = $data['fields'];
		// Possibly this is sharded table
		if (empty($table_struct)) {
			$shard = $this->_shard_table_struct($table_name, $data, $db);
			if ($shard) {
				$table_struct = $shard['struct'];
			}
		}
		// Check if we have such field in the current table structure
		// (then probably we have a simple mistake)
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
		$table_found = false;
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
		return $table_found ? array(
			'found'	=> $table_found,
			'name'	=> $full_table_name,
			'struct'=> $table_struct,
			'data'	=> $table_data,
		) : false;
	}

	/**
	*/
	function _db_table_struct_into_array ($raw_data) {
// TODO: bug with parsed default values
// TODO: write unit tests on parsing table structures
		$struct_array	= array();
		// Check if we have full table definition or cutted one
		if (preg_match($this->_patterns['table'], $raw_data, $m9)) {
			$table_raw_data = $raw_data;
			$table_name		= $m9[1];
			$raw_data		= $m9[2];
		}
		// Cut off comments with params
		if (preg_match($this->_patterns['comment'], trim($raw_data), $m)) {
			$raw_data = str_replace($m[0], '', $raw_data);
		}
		// Cleanup raw first
		$cur_raw_lines = preg_split($this->_patterns['split'], trim(str_replace("\t", ' ', $raw_data)));
		foreach ((array)$cur_raw_lines as $cur_line) {
			$m			= array();
			$m_t		= array();
			$def_value	= '';
			// First we check if current line contains key or regular field
			$IS_KEY = preg_match($this->_patterns['key'], $cur_line);
			// Do parse
			$res = preg_match($IS_KEY ? $this->_patterns['key'] : $this->_patterns['field'], $cur_line, $m);
			if (empty($res)) {
				continue;
			}
			// Switch between processing key and regular field
			if ($IS_KEY) {
				// Prepare key params
				$key_fields = explode(',', str_replace(array("`","'","\""), '', $m[3]));
				$key_name	= !empty($m[2]) ? $m[2] : implode('_', $key_fields);
				$key_type	= !empty($m[1]) ? strtolower($m[1]) : 'key';
				// Prepare index definition array
				$struct_array['keys'][$key_name] = array(
					'fields'	=> $key_fields,
					'type'		=> $key_type,
				);
			} else {
				// Cut off collate string (if exists)
				$m[3] = preg_replace($this->_patterns['collate'], '', $m[3]);
				// Prepare field type
				preg_match($this->_patterns['type'], $m[2], $m_t);
				// Prepare field params
				$field_name		= $m[1];
				$field_length	= $m_t[2];
				$field_arrtib	= (false !== strpos(strtolower($m[3]), 'unsigned')) ? 'unsigned' : '';
				$field_not_null	= (false !== strpos(strtolower($m[3]), 'not null')) ? 1 : 0;
				$field_auto_inc	= (false !== strpos(strtolower($m[3]), 'auto_increment')) ? 1 : 0;
				$field_default	= trim(str_replace(array('"', '\''), '', preg_replace($this->_patterns['default'], '', $m[3])));
				$field_type		= preg_replace('/[^a-z]/i', '', strtolower($m_t[1]));
				// Fix default value
				if (!strlen($field_default) && (false !== strpos($field_type, 'int') || in_array($field_type, array('float','double')))) {
					$field_default = '0';
				}
				$struct_array['fields'][$field_name] = array(
					'type'		=> $field_type,
					'length'	=> $field_length,
					'attrib'	=> $field_attrib,
					'not_null'	=> $field_not_null,
					'default'	=> $field_default,
					'auto_inc'	=> $field_auto_inc,
				);
			}
		}
		return $struct_array;
	}

	/**
	*/
	function _get_table_struct_array_by_name ($table_name, $db) {
		$data2 = $db->query_fetch('SHOW CREATE TABLE `'.$table_name.'`');
		$table_struct = $data2['Create Table'];
		return $this->_db_table_struct_into_array($table_struct);
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
