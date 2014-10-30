<?php

/**
* YF database migrations handler
*/
abstract class yf_db_migrator {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Compare and report real db structure with expected structure, stored inside sql_php, including fields, indexes, foreign keys, table options, etc
	*/
	public function compare($params = array()) {
		$installer = $this->db->installer();
		$utils = $this->db->utils();
		$db_prefix = $this->db->DB_PREFIX;

		$tables_installer_info = isset($params['tables_sql_php']) ? (array)$params['tables_sql_php'] : (array)$installer->TABLES_SQL_PHP;
		$tables_installer = array_keys($tables_installer_info);
		if ($tables_installer) {
			$tables_installer = array_combine($tables_installer, $tables_installer);
		}
		ksort($tables_installer);

		$tables_real = array();
		$plen = strlen($db_prefix);
		foreach ((array)$utils->list_tables() as $table) {
			if ($plen && substr($table, 0, $plen) === $db_prefix) {
				$table = substr($table, $plen);
			}
			$tables_real[$table] = $table;
		}
		ksort($tables_real);

		$tables_missing = array();
		$tables_changed = array();
		$tables_new = array();
		foreach ((array)$tables_installer as $table) {
			if (!isset($tables_real[$table])) {
				$tables_missing[$table] = $table;
			}
		}
		foreach ((array)$tables_real as $table) {
			if (!isset($tables_installer[$table])) {
				$tables_new[$table] = $table;
				continue;
			}
			$table_real_info = array(
				'fields'		=> $utils->list_columns($table),
				'indexes'		=> $utils->list_indexes($table),
				'foreign_keys'	=> $utils->list_foreign_keys($table),
				'options'		=> $utils->table_options($table),
			);
			$diff = $this->compare_table($tables_installer_info[$table], $table_real_info, $db_prefix);
			if ($diff) {
				$tables_changed[$table] = $diff;
			}
		}
		$out = array(
			'tables_changed'	=> $tables_changed,
			'tables_new'		=> $tables_new,
		);
		if ($params['full_info']) {
			$out['tables_real']		= $tables_real;
			$out['tables_installer']= $tables_installer;
			$out['tables_missing']	= $tables_missing;
		}
		return $out;
	}

	/**
	*/
	public function compare_table($t1, $t2, $db_prefix) {
		$prefix_len = strlen($db_prefix);
		$columns = array();
		$indexes = array();
		$foreign_keys = array();
		$options_changed = array();
		foreach ((array)$t1['fields'] as $name => $info) {
			if (!isset($t2['fields'][$name])) {
				$info = $this->_cleanup_column_sql_php($info);
				$columns['missing'][$name] = $info;
			} else {
				$diff = $this->compare_column($info, $t2['fields'][$name]);
				if (isset($diff['default'])) {
					// Fix for default value null when null not allowed
					if (!$info['nullable'] && is_null($diff['default']['actual'])) {
						unset($diff['default']);
					}
				}
				if (!$diff) {
					continue;
				}
				$columns['changed'][$name] = $diff;
			}
		}
		foreach ((array)$t2['fields'] as $name => $info) {
			if (isset($t1['fields'][$name])) {
				continue;
			}
			$info = $this->_cleanup_column_sql_php($info);
			$columns['new'][$name] = $info;
		}
		foreach ((array)$t1['indexes'] as $name => $info) {
			if (!isset($t2['indexes'][$name])) {
				$indexes['missing'][$name] = $info;
			} else {
				$diff = $this->compare_index($info, $t2['indexes'][$name]);
				if (!$diff) {
					continue;
				}
				$indexes['changed'][$name] = $diff;
			}
		}
		foreach ((array)$t2['indexes'] as $name => $info) {
			if (isset($t1['indexes'][$name])) {
				continue;
			}
			// Check that current index not used in db with different name
			foreach ((array)$indexes['missing'] as $m_name => $m_info) {
				if ($info['type'] == $m_info['type'] && $info['columns'] == $m_info['columns']) {
					unset($indexes['missing'][$m_name]);
					continue 2;
				}
			}
			$indexes['new'][$name] = $info;
		}
		foreach ((array)$t1['foreign_keys'] as $name => $info) {
			// remove DB_PREFIX from ref_table
			if ($prefix_len && $info['ref_table'] && substr($info['ref_table'], 0, $prefix_len) === $db_prefix) {
				$info['ref_table'] = substr($info['ref_table'], $prefix_len);
			}
			if (!isset($t2['foreign_keys'][$name])) {
				$foreign_keys['missing'][$name] = $info;
			} else {
				$info2 = $t2['foreign_keys'][$name];
				if ($prefix_len && $info2['ref_table'] && substr($info2['ref_table'], 0, $prefix_len) === $db_prefix) {
					$info2['ref_table'] = substr($info2['ref_table'], $prefix_len);
				}
				$diff = $this->compare_foreign_key($info, $info2);
				if (!$diff) {
					continue;
				}
				$foreign_keys['changed'][$name] = $diff;
			}
		}
		foreach ((array)$t2['foreign_keys'] as $name => $info) {
			if (isset($t1['foreign_keys'][$name])) {
				continue;
			}
			// remove DB_PREFIX from ref_table
			if ($prefix_len && $info['ref_table'] && substr($info['ref_table'], 0, $prefix_len) === $db_prefix) {
				$info['ref_table'] = substr($info['ref_table'], $prefix_len);
			}
			// Check that current foreign key not used in db with different name
			foreach ((array)$foreign_keys['missing'] as $m_name => $m_info) {
				if ($info['columns'] == $m_info['columns'] && $info['ref_columns'] == $m_info['ref_columns'] && $info['ref_table'] == $m_info['ref_table']) {
					unset($foreign_keys['missing'][$m_name]);
					continue 2;
				}
			}
			$foreign_keys['new'][$name] = $info;
		}
		$compare_options = array(
			'engine',
			'charset',
		);
		foreach ((array)$compare_options as $name) {
			if (!isset($t1['options'][$name])) {
				continue;
			}
			$o1 = $t1['options'][$name];
			$o2 = $t2['options'][$name];
			if (strtolower($o1) !== strtolower($o2)) {
				$options_changed[$name] = array(
					'expected'	=> $o1,
					'actual'	=> $o2,
				);
			}
		}
		$result = array(
			'columns_missing'		=> $columns['missing'],
			'columns_new'			=> $columns['new'],
			'columns_changed'		=> $columns['changed'],
			'indexes_missing'		=> $indexes['missing'],
			'indexes_new'			=> $indexes['new'],
			'indexes_changed'		=> $indexes['changed'],
			'foreign_keys_missing'	=> $foreign_keys['missing'],
			'foreign_keys_new'		=> $foreign_keys['new'],
			'foreign_keys_changed'	=> $foreign_keys['changed'],
			'options_changed'		=> $options_changed,
		);
		foreach ($result as $k => $v) {
			if (empty($v)) {
				unset($result[$k]);
			}
		}
		return $result;
	}

	/**
	*/
	public function compare_column($c1, $c2) {
		$changes = array();
		$skip = array(
			'charset',
			'collate',
		);
		foreach ((array)$c1 as $k => $v) {
			if (in_array($k, $skip)) {
				continue;
			}
			if ($k === 'default') {
				if ($c2[$k] == 'NULL') {
					$c2[$k] = null;
				}
				if ($v == 'NULL') {
					$v = null;
				}
			} elseif ($k === 'unsigned') {
				$c2[$k] = (bool)$c2[$k];
				$v = (bool)$v;
			} elseif ($k === 'length' || $k === 'decimals') {
				$c2[$k] = (int)$c2[$k];
				$v = (int)$v;
			}
			if ($c2[$k] !== $v) {
				$changes[$k] = array(
					'expected'	=> $v,
					'actual'	=> $c2[$k],
				);
			}
		}
		return $changes;
	}

	/**
	*/
	public function compare_index($i1, $i2) {
		$changes = array();
		foreach ((array)$i1 as $k => $v) {
			if ($i2[$k] !== $v) {
				$changes[$k] = array(
					'expected'	=> $v,
					'actual'	=> $i2[$k],
				);
			}
		}
		return $changes;
	}

	/**
	*/
	public function compare_foreign_key($f1, $f2) {
		$changes = array();
		foreach ((array)$f1 as $k => $v) {
			if ($f2[$k] !== $v) {
				$changes[$k] = array(
					'expected'	=> $v,
					'actual'	=> $f2[$k],
				);
			}
		}
		return $changes;
	}

	/**
	* Alias
	*/
	public function dump_db_installer_sql($params = array()) {
		$params['dump_only_sql'] = true;
		return $this->dump($params);
	}

	/**
	* Alias
	*/
	public function dump_sql_php($params = array()) {
		return $this->dump($params);
	}

	/**
	* Dump current database structure into sql and sql_php files
	*/
	public function dump($params = array()) {
		$utils = $this->db->utils();
		$installer = $this->db->installer();
		$db_prefix = $this->db->DB_PREFIX;

		$in_old_place = array();
		$existing_sql_php = array();
		$existing_sql_php_files = array();
		if (!$params['no_load_default']) {
			list($in_old_place, $existing_sql_php, $existing_sql_php_files) = $this->_load_tables_sql_php_from_files();
		}

		$compared = $this->compare($params);
		$tables_to_dump = array();
		foreach ((array)$in_old_place as $table) {
			$tables_to_dump[$table] = $table;
		}
		foreach ((array)$compared['tables_new'] as $table) {
			$tables_to_dump[$table] = $table;
		}
		foreach ((array)$compared['tables_changed'] as $table => $changed) {
			$tables_to_dump[$table] = $table;
		}
		$dumped = array();
		foreach ((array)$tables_to_dump as $table) {
			$sql_php = $this->get_real_table_sql_php($table);
			$sql_php = $this->_cleanup_table_sql_php($sql_php, $db_prefix);
			$sql = $this->_convert_sql_php_into_sql($sql_php);
			$dumped_sql_path = $this->_write_dump_sql_file($table, $sql);
			$dumped_sql_php_path = $this->_write_dump_sql_php_file($table, $sql_php);
			if ($dumped_sql_path) {
				$dumped['sql:'.$table] = $dumped_sql_path;
			}
			if ($dumped_sql_php_path) {
				$dumped['sql_php:'.$table] = $dumped_sql_php_path;
			}
		}
		return $dumped;
	}

	/**
	*/
	public function _convert_sql_php_into_sql(array $sql_php, $params = array()) {
		$tmp_name = 'tmp_name_not_exists';
		$sql = _class('db_ddl_parser_mysql', 'classes/db/')->create(array('name' => $tmp_name) + $sql_php);

		$sql_a = explode(PHP_EOL, trim($sql));
		$last_index = count($sql_a) - 1;
		$last_item = $sql_a[$last_index];
		unset($sql_a[0]);
		unset($sql_a[$last_index]);

		// Add commented table attributes
		$options = array();
		foreach ((array)$sql_php['options'] as $k => $v) {
			if ($k == 'charset') {
				$k = 'DEFAULT CHARSET';
			}
			$options[$k] = strtoupper($k).'='.$v;
		}
		$sql_a[] = $options ? '  /** '.implode(' ', $options).' **/' : '';
		$sql = '  '.trim(implode(PHP_EOL, $sql_a));
		return $sql;
	}

	/**
	*/
	public function _load_tables_sql_php_from_files($params = array()) {
		$existing_files_sql_php = array();
		$existing_sql_php = array();
		// Preload db installer PHP array of CREATE TABLE DDL statements
		$ext = '.sql_php.php';
		$dir = 'share/db/sql_php/*'.$ext;
		$globs_sql_php = array(
			'yf_main'				=> YF_PATH. $dir,
			'yf_plugins'			=> YF_PATH. 'plugins/*/'. $dir,
			'project_app'			=> APP_PATH. $dir,
			'project_main'			=> PROJECT_PATH. $dir,
			'project_plugins'		=> PROJECT_PATH. 'plugins/*/'. $dir,
			'project_plugins_app'	=> APP_PATH. 'plugins/*/'. $dir,
		);
		foreach ($globs_sql_php as $gname => $glob) {
			foreach (glob($glob) as $f) {
				$t_name = substr(basename($f), 0, -strlen($ext));
				$existing_files_sql_php[$t_name][$gname] = $f;
				$existing_sql_php[$t_name] = include $f;
			}
		}
		// Project has higher priority than framework (allow to change anything in project)
		// Try to load db structure from project file
		// Sample contents part: 	$project_data['OTHER_TABLES_STRUCTS'] = my_array_merge((array)$project_data['OTHER_TABLES_STRUCTS'], array(
		$structure_file = PROJECT_PATH. 'project_db_structure.php';
		$project_data = array(); // Should be loaded with this name from file
		if (file_exists($structure_file)) {
			include_once ($structure_file);
		}
		$in_old_place = array();
		foreach((array)$project_data as $cur_array_name => $tables) {
			$prefix = '';
			if ($cur_array_name == 'SYS_TABLES_STRUCTS') {
				$prefix = 'sys_';
			} elseif ($cur_array_name == 'OTHER_TABLES_STRUCTS') {
				$prefix = '';
			} else {
				continue;
			}
			foreach ((array)$tables as $table => $sql) {
				if (strlen($prefix)) {
					$table = $prefix. $table;
				}
				$in_old_place[$table] = $table;
				$existing_sql_php[$table] = $installer-create_table_sql_to_php($sql);
			}
		}
		return array($in_old_place, $existing_sql_php, $existing_sql_php_files);
	}

	/**
	*/
	public function _write_dump_sql_file($table, $sql) {
		$file_sql = APP_PATH. 'share/db/sql/'.$table.'.sql.php';
		$dir_sql = dirname($file_sql);
		if (!file_exists($dir_sql)) {
			mkdir($dir_sql, 0755, true);
		}
		$body_sql = '<?'.'php'.PHP_EOL.'return \''. PHP_EOL. addslashes($sql). PHP_EOL. '\';'.PHP_EOL;
		if (!file_exists($file_sql) || md5($body_sql) != md5(file_get_contents($file_sql))) {
			file_put_contents($file_sql, $body_sql);
			return $file_sql;
		}
		return false;
	}

	/**
	*/
	public function _write_dump_sql_php_file($table, array $sql_php) {
		$file_php = APP_PATH. 'share/db/sql_php/'.$table.'.sql_php.php';
		$dir_php = dirname($file_php);
		if (!file_exists($dir_php)) {
			mkdir($dir_php, 0755, true);
		}
		$body_php = '<?'.'php'.PHP_EOL.'return '._var_export($sql_php).';'.PHP_EOL;
		if (!file_exists($file_php) || md5($body_php) != md5(file_get_contents($file_php))) {
			file_put_contents($file_php, $body_php);
			return $file_php;
		}
		return false;
	}

	/**
	* Alias
	*/
	public function sync_sql_php($params = array()) {
		return $this->sync($params);
	}

	/**
	* Ensure all sql, sql_php are in sync with each other and current db structure
	*/
	public function sync($params = array()) {
		$params['dump_all'] = true;
		return $this->dump($params);
	}

	/**
	* Alias
	*/
	public function generate_migration($params = array()) {
		return $this->generate($params);
	}

	/**
	* Generate migration file, based on compare() report. Need to make current database structure looks like desired from sql_php files.
	*/
	public function generate($params = array()) {
		$report = $this->compare($params);
		return array(
			'up'	=> $this->generate_up($report, $params),
			'down'	=> $this->generate_down($report, $params),
		);
	}

	/**
	*/
	public function generate_up($params = array()) {
		if (!isset($report)) {
			$report = $this->compare($params);
		}
		$db_prefix = $this->db->DB_PREFIX;
		// Safe mode here means that we do not generate danger statements like drop something
		$safe_mode = isset($params['safe_mode']) ? $params['safe_mode'] : true;

		$out = array();
		foreach ((array)$report['tables_changed'] as $table => $diff) {
			$table_real_info = $this->get_real_table_sql_php($table);
			if (!$table_real_info) {
				continue;
			}
			$table_real_info = $this->_cleanup_table_sql_php($table_real_info, $db_prefix);

			foreach ((array)$diff['columns_new'] as $name => $info) {
				$info = $this->_cleanup_column_sql_php($info);
				$out[] = array('cmd' => 'add_column', 'table' => $table, 'info' => $info);
			}
			foreach ((array)$diff['columns_changed'] as $name => $info) {
				$new_info = $table_real_info['fields'][$name];
				if ($new_info) {
					$new_info = $this->_cleanup_column_sql_php($new_info);
					$out[] = array('cmd' => 'alter_column', 'table' => $table, 'column' => $name, 'info' => $new_info);
				}
			}
			if (!$safe_mode) {
				foreach ((array)$diff['columns_missing'] as $name => $info) {
					$out[] = array('cmd' => 'drop_column', 'table' => $table, 'column' => $name);
				}
			}
			foreach ((array)$diff['indexes_new'] as $name => $info) {
				$out[] = array('cmd' => 'add_index', 'table' => $table, 'info' => $info);
			}
			if (!$safe_mode) {
				foreach ((array)$diff['indexes_missing'] as $name => $info) {
					$out[] = array('cmd' => 'drop_index', 'table' => $table, 'index' => $name);
				}
			}
			foreach ((array)$diff['indexes_changed'] as $name => $info) {
				$new_info = $table_real_info['indexes'][$name];
				if ($new_info) {
					$out[] = array('cmd' => 'drop_index', 'table' => $table, 'index' => $name);
					$out[] = array('cmd' => 'add_index', 'table' => $table, 'info' => $new_info);
				}
			}
			foreach ((array)$diff['foreign_keys_new'] as $name => $info) {
				$out[] = array('cmd' => 'add_foreign_key', 'table' => $table, 'info' => $info);
			}
			if (!$safe_mode) {
				foreach ((array)$diff['foreign_keys_missing'] as $name => $info) {
					$out[] = array('cmd' => 'drop_foreign_key', 'table' => $table, 'fk' => $name);
				}
			}
			foreach ((array)$diff['foreign_keys_changed'] as $name => $info) {
				$new_info = $table_real_info['foreign_keys'][$name];
				if ($new_info) {
					$out[] = array('cmd' => 'drop_foreign_key', 'table' => $table, 'fk' => $name);
					$out[] = array('cmd' => 'add_foreign_key', 'table' => $table, 'info' => $new_info);
				}
			}
			foreach ((array)$diff['options_changed'] as $name => $info) {
				$new_info = $table_real_info['options'];
				if ($new_info) {
					$out[] = array('cmd' => 'alter_table', 'table' => $table, 'info' => $new_info);
				}
			}
		}
		foreach ((array)$report['tables_new'] as $table => $diff) {
			$new_info = $this->get_real_table_sql_php($table);
			if ($new_info) {
				$new_info = $this->_cleanup_table_sql_php($new_info, $db_prefix);
				$out[] = array('cmd' => 'create_table', 'table' => $table, 'info' => $new_info);
			}
		}
		if (!$safe_mode) {
			foreach ((array)$report['tables_missing'] as $table => $diff) {
				$out[] = array('cmd' => 'drop_table', 'table' => $table);
			}
		}
		return $out;
	}

	/**
	*/
	public function generate_down($report = null, $params = array()) {
		if (!isset($report)) {
			$report = $this->compare($params);
		}
		$db_prefix = $this->db->DB_PREFIX;
		$tables_installer_info = $this->db->installer()->TABLES_SQL_PHP;

		// Safe mode here means that we do not generate danger statements like drop something
		$safe_mode = isset($params['safe_mode']) ? $params['safe_mode'] : true;

		$out = array();
		foreach ((array)$report['tables_changed'] as $table => $diff) {
			foreach ((array)$diff['columns_missing'] as $name => $info) {
				$info = $this->_cleanup_column_sql_php($info);
				$out[] = array('cmd' => 'add_column', 'table' => $table, 'info' => $info);
			}
			if (!$safe_mode) {
				foreach ((array)$diff['columns_new'] as $name => $info) {
					$out[] = array('cmd' => 'drop_column', 'table' => $table, 'column' => $name);
				}
			}
			foreach ((array)$diff['columns_changed'] as $name => $info) {
				$new_info = $tables_installer_info[$table]['fields'][$name];
				if ($new_info) {
					$new_info = $this->_cleanup_column_sql_php($new_info);
					$out[] = array('cmd' => 'alter_column', 'table' => $table, 'column' => $name, 'info' => $new_info);
				}
			}
			foreach ((array)$diff['indexes_missing'] as $name => $info) {
				$out[] = array('cmd' => 'add_index', 'table' => $table, 'info' => $info);
			}
			if (!$safe_mode) {
				foreach ((array)$diff['indexes_new'] as $name => $info) {
					$out[] = array('cmd' => 'drop_index', 'table' => $table, 'index' => $name);
				}
			}
			foreach ((array)$diff['indexes_changed'] as $name => $info) {
				$new_info = $tables_installer_info[$table]['indexes'][$name];
				if ($new_info) {
					$out[] = array('cmd' => 'drop_index', 'table' => $table, 'index' => $name);
					$out[] = array('cmd' => 'add_index', 'table' => $table, 'info' => $new_info);
				}
			}
			foreach ((array)$diff['foreign_keys_missing'] as $name => $info) {
				$out[] = array('cmd' => 'add_foreign_key', 'table' => $table, 'info' => $info);
			}
			if (!$safe_mode) {
				foreach ((array)$diff['foreign_keys_new'] as $name => $info) {
					$out[] = array('cmd' => 'drop_foreign_key', 'table' => $table, 'fk' => $name);
				}
			}
			foreach ((array)$diff['foreign_keys_changed'] as $name => $info) {
				$new_info = $tables_installer_info[$table]['foreign_keys'][$name];
				if ($new_info) {
					$out[] = array('cmd' => 'drop_foreign_key', 'table' => $table, 'fk' => $name);
					$out[] = array('cmd' => 'add_foreign_key', 'table' => $table, 'info' => $new_info);
				}
			}
			foreach ((array)$diff['options_changed'] as $name => $info) {
				$new_info = $tables_installer_info[$table]['options'];
				if ($new_info) {
					$out[] = array('cmd' => 'alter_table', 'table' => $table, 'info' => $new_info);
				}
			}
		}
		foreach ((array)$report['tables_missing'] as $table => $diff) {
			$new_info = $tables_installer_info[$table];
			if ($new_info) {
				$out[] = array('cmd' => 'create_table', 'table' => $table, 'info' => $new_info);
			}
		}
		if (!$safe_mode) {
			foreach ((array)$report['tables_new'] as $table => $diff) {
				$out[] = array('cmd' => 'drop_table', 'table' => $table);
			}
		}
		return $out;
	}

	/**
	* Alias
	*/
	public function create_migration($params = array()) {
		return $this->create($params);
	}

	/**
	* Create migration file from current database comparing to stored structure. Need to store current database changes to apply in other places.
	*/
	public function create($params = array()) {
		$name = date('YmdHis');
		$report = $this->compare($params);
		$up = (array)$this->generate_up($report, $params);
		$down = (array)$this->generate_down($report, $params);
		$body = $this->_create_migration_body($name, $up, $down);
		$file_path = $this->_write_new_migration_file($name, $body);
		return $file_path;
	}

	/**
	*/
	public function _migration_commands_into_string($cmds = array(), $num_tabs = 2) {
		$TAB = "\t";
		$prefix = str_repeat($TAB, $num_tabs). '$utils->';
		$a = array();
		$a[] = str_repeat($TAB, $num_tabs).'$utils = $this->db->utils();';
		foreach ((array)$cmds as $c) {
			$name = $c['cmd'];
			unset($c['cmd']);
			$body = array();
			foreach ($c as $k => $v) {
				if (is_array($v)) {
					$body[] = str_replace(PHP_EOL, PHP_EOL.str_repeat($TAB, $num_tabs), _var_export($v));
				} else {
					$body[] = '\''.addslashes($v).'\'';
				}
			}
			$a[] = $prefix. $name. '('.implode(', ', $body).');';
		}
		return implode(PHP_EOL, $a);
	}

	/**
	*/
	public function _create_migration_body($name, array $up, array $down) {
		$TAB = "\t";
		$fhead = PHP_EOL. $TAB.'/'.'**'.PHP_EOL.$TAB.'*'.'/';
		return '<?'.'php'.PHP_EOL.PHP_EOL
			. 'load(\'db_migrator\', \'framework\', \'classes/db/\');'.PHP_EOL
			. 'class db_migration_'.$name.' extends yf_db_migrator {'.PHP_EOL
			. $fhead. PHP_EOL. $TAB. 'protected function up() {'.PHP_EOL. $this->_migration_commands_into_string($up). PHP_EOL. $TAB. '}'.PHP_EOL
			. $fhead .PHP_EOL. $TAB. 'protected function down() {'.PHP_EOL. $this->_migration_commands_into_string($down). PHP_EOL. $TAB. '}'.PHP_EOL
			. '}';
	}

	/**
	*/
	public function _write_new_migration_file($name, $body) {
		$file = APP_PATH. 'share/db/migrations/db_migration_'.$name.'.class.php';
		if (!file_exists($file)) {
			$dir = dirname($file);
			if (!file_exists($dir)) {
				mkdir($dir, 0755, true);
			}
			file_put_contents($file, $body);
		}
		return $file;
	}

	/**
	*/
	public function _cleanup_column_sql_php($field_info = array()) {
		foreach ((array)$field_info as $k => $v) {
			$need_unset = false;
			if (is_null($v) || in_array($k, array('raw', 'type_raw', 'primary', 'unique'))) {
				$need_unset = true;
			} elseif ($k === 'auto_inc' && !$v) {
				$need_unset = true;
			}
			if ($need_unset) {
				unset($field_info[$k]);
			}
		}
		return $field_info;
	}

	/**
	*/
	public function _cleanup_table_sql_php($sql_php = array(), $db_prefix = '') {
		$prefix_len = strlen($db_prefix);
		foreach ((array)$sql_php['fields'] as $field_name => $field_info) {
			$sql_php['fields'][$field_name] = $this->_cleanup_column_sql_php($field_info);
		}
		if ($prefix_len && $sql_php['foreign_keys']) {
			foreach ((array)$sql_php['foreign_keys'] as $fk_name => $fk_info) {
				// remove db_prefix from ref_table
				if (substr($fk_info['ref_table'], 0, $prefix_len) === $db_prefix) {
					$sql_php['foreign_keys'][$fk_name]['ref_table'] = substr($fk_info['ref_table'], $prefix_len);
				}
			}
		}
		foreach ((array)$sql_php['options'] as $k => $v) {
			if (is_null($v)) {
				unset($sql_php['options'][$k]);
			}
		}
		foreach ((array)$sql_php as $k => $v) {
			if (empty($v)) {
				unset($sql_php[$k]);
			}
		}
		return $sql_php;
	}

	/**
	*/
	public function get_real_table_sql_php($table) {
		$utils = $this->db->utils();
		$db_prefix = $this->db->DB_PREFIX;

		$real_table_name = $this->db->_real_name($table);

		$sql_php = array(
			'fields'		=> $utils->list_columns($real_table_name),
			'indexes'		=> $utils->list_indexes($real_table_name),
			'foreign_keys'	=> $utils->list_foreign_keys($real_table_name),
			'options'		=> $utils->table_options($real_table_name),
		);
		foreach ((array)$sql_php['fields'] as $fname => $finfo) {
			if ($finfo['collate'] === 'utf8_general_ci') {
				$sql_php['fields'][$fname]['collate'] = null;
			}
			if (isset($finfo['type_raw'])) {
				unset($sql_php['fields'][$fname]['type_raw']);
			}
		}
		$skip_options = array(
			'auto_increment',
			'collate',
		);
		foreach ($skip_options as $skip_option) {
			if (isset($sql_php['options'][$skip_option])) {
				unset($sql_php['options'][$skip_option]);
			}
		}
		foreach ((array)$sql_php['options'] as $k => $v) {
			if (!strlen($v)) {
				unset($sql_php['options'][$k]);
			}
		}
		return $sql_php;
	}

	/**
	* Alias
	*/
	public function list_migrations($params = array()) {
		return $this->_list($params);
	}

	/**
	* List of available migrations to apply
	*/
	public function _list($params = array()) {
		$prefix = 'db_migration_';
		$ext = '.class.php';
		$dir = 'share/db/migrations/'.$prefix.'*'.$ext;
		$globs = array(
			'yf_main'				=> YF_PATH. $dir,
			'yf_plugins'			=> YF_PATH. 'plugins/*/'. $dir,
			'project_app'			=> APP_PATH. $dir,
			'project_main'			=> PROJECT_PATH. $dir,
			'project_plugins'		=> PROJECT_PATH. 'plugins/*/'. $dir,
			'project_plugins_app'	=> APP_PATH. 'plugins/*/'. $dir,
		);
		$migratons = array();
		foreach ($globs as $gname => $glob) {
			if ($params['only_from_project'] && substr($gname, 0, strlen('project_')) !== 'project_') {
				continue;
			}
			foreach (glob($glob) as $f) {
				$name = substr(basename($f), strlen($prefix), -strlen($ext));
				$migrations[$name] = $f;
			}
		}
		return $migrations;
	}

	/**
	* Alias
	*/
	public function apply_migration($name, $params = array()) {
		return $this->apply($name, $params);
	}

	/**
	* Apply selected migration file to current database
	*/
	public function apply($name, $params = array()) {
		if (is_array($name)) {
			$params = (array)$params + $name;
			$name = '';
		}
		$name = isset($params['name']) ? $params['name'] : $name;
		if (!$name) {
			return 'Error: empty migration name';
		}
		$avail = $this->_list();
		$path = $avail[$name];
		if (!$path || !file_exists($path)) {
			return 'Error: cannot find migration with name: '.$name;
		}
		$mclass = 'db_migration_'.$name;
		require_once $path;
		$migration = new $mclass();
		$migration->db = $this->db;

#$migration->db->LOG_ALL_QUERIES = true;

		try {
			$this->db->begin();
			$this->db->query('SET foreign_key_checks = 0;');
			$migration->up();
			$this->db->query('SET foreign_key_checks = 1;');
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollback();
			$this->db->query('SET foreign_key_checks = 1;');
#			$migration->down();
			return 'Error';
		}

#$migration->db->LOG_ALL_QUERIES = false;
#print_r($migration->db->_LOG);

		return 'Success';
	}

	/**
	*/
	public function rollback() {
// TODO: rollback selected migration(s)
	}

	/**
	*/
	public function reset() {
// TODO: rollback all applied migrations
	}

	/**
	*/
	public function _create_migrations_table() {
// TODO: create internal migrations table, where we will store metadata
	}

	/**
	*/
	public function _get_migration_model() {
// TODO:
	}
}
