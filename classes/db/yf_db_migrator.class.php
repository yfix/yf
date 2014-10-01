<?php

// TODO: implement migrations like in ROR, based on these methods

/**
*/
abstract class yf_db_migrator {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	public function _init() {
	}

	/**
	* Compare and report real db structure with expected structure, stored inside sql_php, including fields, indexes, foreign keys, table options, etc
	*/
	public function compare($params = array()) {
		$installer = $this->db->installer();
		$utils = $this->db->utils();
		$db_prefix = $this->db->DB_PREFIX;

		$tables_installer_info = $installer->TABLES_SQL_PHP;
		$tables_installer = array_keys($tables_installer_info);
		$tables_installer = array_combine($tables_installer, $tables_installer);
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
			$diff = $this->compare_table($tables_installer_info[$table], $table_real_info);
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
	public function compare_table($t1, $t2) {
		$columns = array();
		$indexes = array();
		$foreign_keys = array();
		$options_changed = array();
		foreach ((array)$t1['fields'] as $name => $info) {
			if (!isset($t2['fields'][$name])) {
				foreach ($info as $k => $v) {
					if (is_null($v)) { unset($info[$k]); }
				}
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
			foreach ($info as $k => $v) {
				if (is_null($v)) { unset($info[$k]); }
			}
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
			if (!isset($t2['foreign_keys'][$name])) {
				$foreign_keys['missing'][$name] = $info;
			} else {
				$diff = $this->compare_foreign_key($info, $t2['foreign_keys'][$name]);
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
// TODO: remove DB_PREFIX from ref_table
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
	public function generate_migration($params = array()) {
		return $this->generate($params);
	}

	/**
	* Generate migration file, based on compare() report
	*/
	public function generate($params = array()) {
		$tables_installer_info = $installer->TABLES_SQL_PHP;

		// Safe mode here means that we do not generate danger statements like drop something
		$safe_mode = isset($params['safe_mode']) ? $params['safe_mode'] : true;

		$report = $this->compare();
		foreach ((array)$report['tables_changed'] as $table => $diff) {
			foreach ((array)$diff['columns_missing'] as $name => $info) {
				foreach ($info as $k => $v) {
					if (is_null($v)) { unset($info[$k]); }
				}
				// preg_replace('~[\n\t]+~ims', ' ', _var_export($info))
				$out[] = 'db()->utils()->add_column(\''.$table.'\', \''.$name.'\', '._var_export($info).');';
			}
			if (!$safe_mode) {
				foreach ((array)$diff['columns_new'] as $name => $info) {
					$out[] = 'db()->utils()->drop_column(\''.$table.'\', \''.$name.'\');';
				}
			}
			foreach ((array)$diff['columns_changed'] as $name => $info) {
				$new_info = $tables_installer_info[$table]['fields'][$name];
				if ($new_info) {
					foreach ((array)$new_info as $k => $v) {
						if (is_null($v)) { unset($new_info[$k]); }
					}
					$out[] = 'db()->utils()->alter_column(\''.$table.'\', \''.$name.'\', '._var_export($new_info).');';
				}
			}
			foreach ((array)$diff['indexes_missing'] as $name => $info) {
				$out[] = 'db()->utils()->add_index(\''.$table.'\', \''.$name.'\', '._var_export($info).');';
			}
			if (!$safe_mode) {
				foreach ((array)$diff['indexes_new'] as $name => $info) {
					$out[] = 'db()->utils()->drop_index(\''.$table.'\', \''.$name.'\');';
				}
			}
			foreach ((array)$diff['indexes_changed'] as $name => $info) {
				$new_info = $tables_installer_info[$table]['indexes'][$name];
				if ($new_info) {
					$out[] = 'db()->utils()->drop_index(\''.$table.'\', \''.$name.'\');';
					$out[] = 'db()->utils()->add_index(\''.$table.'\', \''.$name.'\', '._var_export($new_info).');';
				}
			}
			foreach ((array)$diff['foreign_keys_missing'] as $name => $info) {
				$out[] = 'db()->utils()->add_foreign_key(\''.$table.'\', \''.$name.'\', '._var_export($info).');';
			}
			if (!$safe_mode) {
				foreach ((array)$diff['foreign_keys_new'] as $name => $info) {
					$out[] = 'db()->utils()->drop_foreign_key(\''.$table.'\', \''.$name.'\');';
				}
			}
			foreach ((array)$diff['foreign_keys_changed'] as $name => $info) {
				$new_info = $tables_installer_info[$table]['foreign_keys'][$name];
				if ($new_info) {
					$out[] = 'db()->utils()->drop_foreign_key(\''.$table.'\', \''.$name.'\');';
					$out[] = 'db()->utils()->add_foreign_key(\''.$table.'\', \''.$name.'\', '._var_export($new_info).');';
				}
			}
			foreach ((array)$diff['options_changed'] as $name => $info) {
				$new_info = $tables_installer_info[$table]['options'];
				if ($new_info) {
					$out[] = 'db()->utils()->alter_table(\''.$table.'\', '._var_export($new_info).');';
				}
			}
		}
		foreach ((array)$report['tables_missing'] as $table => $diff) {
			$new_info = $tables_installer_info[$table];
			if ($new_info) {
				$out[] = 'db()->utils()->create_table(\''.$table.'\', '._var_export($new_info).');';
			}
		}
		if (!$safe_mode) {
			foreach ((array)$report['tables_new'] as $table => $diff) {
				$out[] = 'db()->utils()->drop_table(\''.$table.'\');';
			}
		}
		return implode(PHP_EOL, $out);
	}

	/**
	* Alias
	*/
	public function create_migration($params = array()) {
		return $this->create($params);
	}

	/**
	* Create migration file from current database comparing to stored structure
	*/
	public function create($params = array()) {
// TODO
	}

	/**
	* Alias
	*/
	public function apply_migration($params = array()) {
		return $this->apply($params);
	}

	/**
	* Apply selected migration file to current database
	*/
	public function apply($params = array()) {
// TODO
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
		$ext = '.migration.php';
		$dir = 'share/db_installer/migrations/*'.$ext;
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
			foreach (glob($glob) as $f) {
				$name = substr(basename($f), 0, -strlen($ext));
				$migrations[$name][$gname] = $f;
			}
		}
		return $migrations;
	}

	/**
	* Alias
	*/
	public function dump_db_installer_sql($params = array()) {
		$params['only_sql'] = true;
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
		$existing_files_sql_php = array();
		$existing_sql_php = array();
		// Preload db installer PHP array of CREATE TABLE DDL statements
		$ext = '.sql_php.php';
		$dir = 'share/db_installer/sql_php/*'.$ext;
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
				$existing_sql_php[$table] = $this->create_table_sql_to_php($sql);
			}
		}
		$compared = $this->compare();
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
		$tmp_name = 'tmp_name_not_exists';
		$skip_options = array(
			'auto_increment',
			'collate',
		);

		$utils = $this->db->utils();
		$db_prefix = $this->db->DB_PREFIX;

		foreach ((array)$tables_to_dump as $table) {
#			list(, $raw_sql) = array_values($this->db->get('SHOW CREATE TABLE '.$this->db->escape_key($this->db->_real_name($table))));
#			$sql_php = $this->create_table_sql_to_php($raw_sql);

			$real_table_name = $this->db->_real_name($table);
			$sql_php = array(
				'fields'		=> $utils->list_columns($real_table_name),
				'indexes'		=> $utils->list_indexes($real_table_name),
// TODO: remove DB_PREFIX from ref_table
				'foreign_keys'	=> $utils->list_foreign_keys($real_table_name),
				'options'		=> $utils->table_options($real_table_name),
			);
			foreach ((array)$sql_php['fields'] as $fname => $finfo) {
				if ($finfo['collate'] === 'utf8_general_ci') {
					$sql_php['fields'][$fname]['collate'] = null;
				}
			}
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

			$file_sql = APP_PATH. 'share/db_installer/sql/'.$table.'.sql.php';
			$dir_sql = dirname($file_sql);
			if (!file_exists($dir_sql)) {
				mkdir($dir_sql, 0755, true);
			}
			$body_sql = '<?'.'php'.PHP_EOL.'return \''. PHP_EOL. addslashes($sql). PHP_EOL. '\';'.PHP_EOL;
			if (!file_exists($file_sql) || md5($body_sql) != md5(file_get_contents($file_sql))) {
				$dumped['sql:'.$table] = $file_sql;
				file_put_contents($file_sql, $body_sql);
			}

			$file_php = APP_PATH. 'share/db_installer/sql_php/'.$table.'.sql_php.php';
			$dir_php = dirname($file_php);
			if (!file_exists($dir_php)) {
				mkdir($dir_php, 0755, true);
			}
			$body_php = '<?'.'php'.PHP_EOL.'return '._var_export($sql_php).';'.PHP_EOL;
			if (!file_exists($file_php) || md5($body_php) != md5(file_get_contents($file_php))) {
				$dumped['sql_php:'.$table] = $file_php;
				file_put_contents($file_php, $body_php);
			}
		}
		return $dumped;
	}

	/**
	*/
	public function create_table_php_to_sql ($data) {
		return _class('db_ddl_parser_mysql', 'classes/db/')->create($data);
	}

	/**
	*/
	public function create_table_sql_to_php ($sql) {
		$options = '';
		// Get table options from table structure. Example: /** ENGINE=MEMORY **/
		if (preg_match('#\/\*\*(?P<raw_options>[^\*\/]+)\*\*\/#i', trim($sql), $m)) {
			// Cut comment with options from source table structure to prevent misunderstanding
			$sql = str_replace($m[0], '', $sql);
			$options = $m['raw_options'];
		}
		$tmp_name = '';
		if (false === strpos(strtoupper($sql), 'CREATE TABLE')) {
			$tmp_name = 'tmp_name_not_exists';
			$sql = 'CREATE TABLE `'.$tmp_name.'` ('.$sql.')';
		}
		// Place them into the end of the DDL
		if ($options) {
			$sql = rtrim(rtrim(rtrim($sql), ';')).' '.$options;
		}
		$result = _class('db_ddl_parser_mysql', 'classes/db/')->parse($sql);
		if ($result && $tmp_name) {
			$result['name'] = '';
		}
		return $result;
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
// TODO
	}
}
