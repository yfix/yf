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
		$globs = array(
		);
// TODO
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
$ext = '.sql_php.php';
$globs_php = array(
	'yf_main'		=> YF_PATH.'share/db_installer/sql_php/*'.$ext,
	'yf_plugins'	=> YF_PATH.'plugins/*/share/db_installer/sql_php/*'.$ext,
);

$ext = '.sql.php';
$globs_sql = array(
	'yf_main'		=> YF_PATH.'share/db_installer/sql/*'.$ext,
	'yf_plugins'	=> YF_PATH.'plugins/*/share/db_installer/sql/*'.$ext,
);
// TODO
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
