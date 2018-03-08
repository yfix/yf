<?php

/**
*/
class yf_db_ddl_parser_mysql {

	/***/
	private $parser = null;
	/***/
	public $RAW_IN_RESULTS = false;

	/**
	*/
	public function _init () {
		require_php_lib('php_sql_parser');
		$this->parser = new \PHPSQLParser\PHPSQLParser();
	}

	/**
	*/
	function create (array $data, $params = []) {
		if (!strlen($data['name']) || empty($data['fields'])) {
			return false;
		}
		$table_name = $data['name'];
		foreach ((array)$data['fields'] as $name => $v) {
			$lines[] = $this->create_column_line($name, $v);
		}
		foreach ((array)$data['indexes'] as $name => $v) {
			$lines[] = $this->create_index_line($name, $v);
		}
		foreach ((array)$data['foreign_keys'] as $name => $v) {
			$lines[] = $this->create_fk_line($name, $v);
		}
		$options = [];
		foreach ((array)$data['options'] as $k => $v) {
			if ($k == 'charset') {
				$k = 'DEFAULT CHARSET';
			}
			$options[$k] = strtoupper($k).'='.$v;
		}
		return 'CREATE TABLE '.$this->escape_table_name($table_name).' ('.PHP_EOL. implode(','.PHP_EOL, $lines). PHP_EOL.')'. ($options ? ' '.implode(' ', $options) : '').';';
	}

	/**
	* Useful for ALTER TABLE
	*/
	function create_column_line ($name = '', $v = [], $params = []) {
		if (is_array($name)) {
			$v = $name;
			$name = $v['name'];
		}
		$name = strtolower($name);
		$v['type'] = strtolower($v['type']);
		if (strpos($v['type'], 'int') !== false && !$v['length']) {
			$v['length'] = $this->_get_int_def_length($v['type']);
		}
		$type_braces = (isset($v['length']) && is_numeric($v['length']) ? '('.$v['length']. (isset($v['decimals']) && is_numeric($v['decimals']) ? ','.$v['decimals'] : '').')' : '');
		if (in_array($v['type'], ['enum','set']) && is_array($v['values']) && count($v['values'])) {
			$type_braces = '(\''.implode('\',\'', $v['values']).'\')';
		}
		$def = false;
		if ($v['default'] === 'NULL') {
			$def = 'NULL';
		} elseif ($v['type'] == 'timestamp') {
			$def = $v['default'];
		} elseif (!is_null($v['default'])) {
			$def = '\''.$v['default'].'\'';
		}
		return $this->_implode_line([
			'name'		=> $this->escape_key($name),
			'type'		=> $v['type']. $type_braces,
			'unsigned'	=> $v['unsigned'] ? 'unsigned' : '',
			'charset'	=> $v['charset'] && $v['collate'] ? 'CHARACTER SET '.strtolower($v['charset']) : '',
			'collate'	=> $v['collate'] ? 'COLLATE '.strtolower($v['collate']) : '',
			'nullable'	=> !$v['nullable'] ? 'NOT NULL' : '',
			'default'	=> $def ? 'DEFAULT '.$def : '',
			'auto_inc'	=> $v['auto_inc'] ? 'AUTO_INCREMENT' : '',
			'on_update'	=> $v['on_update'] ?: '',
		]);
	}

	/**
	* Useful for ALTER INDEX
	*/
	function create_index_line ($name = '', $v = [], $params = []) {
		if (is_array($name)) {
			$v = $name;
			$name = $v['name'];
		}
		$type = 'KEY';
		$v['type'] = strtolower($v['type']);
		if ($v['type'] == 'primary') {
			$type = 'PRIMARY KEY';
			$name = 'PRIMARY';
		} elseif ($v['type'] == 'unique') {
			$type = 'UNIQUE KEY';
		} elseif ($v['type'] == 'fulltext') {
			$type = 'FULLTEXT KEY';
		} elseif ($v['type'] == 'spatial') {
			$type = 'SPATIAL KEY';
		}
		if ($name != 'PRIMARY') {
			$name = strtolower($name);
		}
		return $this->_implode_line([
			'type'		=> $type,
			'name'		=> strlen($name) && !is_numeric($name) && in_array($v['type'], ['index', 'unique', 'fulltext', 'spatial']) ? $this->escape_key($name) : '',
			'columns'	=> strtolower('('.implode(',', $this->escape_key($v['columns'])).')'),
		]);
	}

	/**
	* Useful for ALTER FOREIGN KEY
	*/
	function create_fk_line ($name = '', $v = [], $params = []) {
		if (is_array($name)) {
			$v = $name;
			$name = $v['name'];
		}
		return $this->_implode_line([
			'begin'			=> 'CONSTRAINT',
			'name'			=> $this->escape_key(strtolower($name)),
			'fk'			=> 'FOREIGN KEY',
			'columns'		=> strtolower('('.implode(',', $this->escape_key($v['columns'])).')'),
			'ref'			=> 'REFERENCES',
			'ref_table'		=> '`'.$v['ref_table'].'`',
			'ref_columns'	=> strtolower('('.implode(',', $this->escape_key($v['ref_columns'])).')'),
			'on_delete'		=> $v['on_delete'] ? 'ON DELETE '.strtoupper(str_replace('_', ' ', $v['on_delete'])) : '',
			'on_update'		=> $v['on_update'] ? 'ON UPDATE '.strtoupper(str_replace('_', ' ', $v['on_update'])) : '',
		]);
	}

	/**
	*/
	function parse ($sql) {
		$parsed = $this->parser->parse($sql);

		$table_name = $parsed['TABLE']['no_quotes']['parts'][0] ?: '';
		$tmp_create_def = $parsed['TABLE']['create-def']['sub_tree'] ?: [];
		$tmp_options = $parsed['TABLE']['options'] ?: [];

		$struct = [
			'name'	=> $table_name,
			'fields' => [],
			'indexes' => [],
			'foreign_keys' => [],
			'options' => [],
		];

		foreach ((array)$tmp_create_def as $v) {
			if ($v['expr_type'] == 'column-def') {
				$name = null;
				$type = null;
				$length = null;
				$unsigned = null;
				$nullable = false;
				$charset = null;
				$collate = null;
				$default = null;
				$auto_inc = null;
				$primary = false;
				$unique = false;
				$decimals = null;
				$values = null; // ENUM and SET
				$on_update = null; // TIMESTAMP and DATETIME
				foreach ((array)$v['sub_tree'] as $v2) {
					if ($v2['expr_type'] == 'colref') {
						$name = $v2['no_quotes']['parts'][0];
					} elseif ($v2['expr_type'] == 'column-type') {
						foreach ((array)$v2['sub_tree'] as $k3 => $v3) {
							if (isset($v3['unsigned'])) {
								$unsigned = $v3['unsigned'];
							}
							if ($v3['expr_type'] == 'data-type') {
								$type = $v3['base_expr'];
								$length = $v3['length'];
								$decimals = $v3['decimals'];
							} elseif ($v3['expr_type'] == 'default-value') {
								$default = trim($v3['base_expr'], '"\'');
							} elseif ($v3['expr_type'] == 'reserved' && in_array($v3['base_expr'], ['enum', 'set'])) {
								$type = $v3['base_expr'];
								if ($v3['sub_tree']['expr_type'] != 'bracket_expression') {
									continue;
								}
								$values = [];
								foreach ((array)$v3['sub_tree']['sub_tree'] as $v4) {
									if ($v4['expr_type'] == 'const') {
										$_val = trim($v4['base_expr'], '"\'');
										$values[$_val] = $_val;
									}
								}
							} elseif ($v3['expr_type'] == 'reserved' && strtoupper($v3['base_expr']) === 'DEFAULT') {
								$next1 = $v2['sub_tree'][$k3 + 1];
								$next2 = $v2['sub_tree'][$k3 + 2];
								if ($next1['expr_type'] == 'reserved') {
									$default = strval($next1['base_expr']);
									if ($next2['expr_type'] == 'reserved') {
										$default .= ' '.strval($next2['base_expr']);
									}
								}
							}
						}
						// http://dev.mysql.com/doc/refman/5.6/en/timestamp-initialization.html
						// As of MySQL 5.6.5, TIMESTAMP and DATETIME columns can be automatically initializated and updated to the current date and time (that is, the current timestamp). 
						// Before 5.6.5, this is true only for TIMESTAMP, and for at most one TIMESTAMP column per table
						if (in_array($type, ['timestamp','datetime'])) {
							$try = 'ON UPDATE CURRENT_TIMESTAMP';
							if (strpos($v2['base_expr'], $try) !== false) {
								$on_update = $try;
							}
						}
						$nullable = $v2['nullable'];
						$auto_inc = $v2['auto_inc'];
						$primary = $v2['primary'];
						$unique = $v2['unique'];
						$charset = $v2['charset'];
						$collate = $v2['collate'];
					}
				}
				if ($auto_inc) {
					$primary = true;
				}
				$name = strtolower($name);
				$type = strtolower($type);
				if (strpos($type, 'int') !== false && !$length) {
					$length = $this->_get_int_def_length($type);
				}
				$struct['fields'][$name] = [
					'name'		=> $name,
					'type'		=> $type,
					'length'	=> $length ? intval($length) : null,
					'decimals'	=> $decimals,
					'unsigned'	=> $unsigned,
					'nullable'	=> $nullable,
					'default'	=> $default,
					'charset'	=> $charset ? strtolower($charset) : null,
					'collate'	=> $collate ? strtolower($collate) : null,
					'auto_inc'	=> $auto_inc,
					'primary'	=> $primary,
					'unique'	=> $unique,
					'values'	=> !empty($values) ? $values : null,
				];
				if ($on_update) {
					$struct['fields'][$name]['on_update'] = $on_update;
				}
				if ($this->RAW_IN_RESULTS) {
					$struct['fields'][$name]['raw'] = $v['base_expr'];
				}
			} elseif ($v['expr_type'] == 'primary-key') {
				$name = 'PRIMARY';
				$type = 'primary';
				$columns = [];
				foreach ((array)$v['sub_tree'] as $v2) {
					if ($v2['expr_type'] == 'column-list') {
						foreach ((array)$v2['sub_tree'] as $v3) {
							if ($v3['expr_type'] == 'index-column') {
								$index_col_name = $v3['no_quotes']['parts'][0];
								$columns[$index_col_name] = $index_col_name;
							}
						}
					}
				}
				$struct['indexes'][$name] = [
					'name'		=> $name,
					'type'		=> strtolower($type),
					'columns'	=> $columns,
				];
				if ($this->RAW_IN_RESULTS) {
					$struct['indexes'][$name]['raw'] = $v['base_expr'];
				}
			} elseif ($v['expr_type'] == 'index' || $v['expr_type'] == 'fulltext-index' || $v['expr_type'] == 'spatial-index') {
				$name = null;
				$type = 'index';
				$base = strtoupper(trim($v['base_expr']));
				if (substr($base, 0, strlen('UNIQUE')) == 'UNIQUE') {
					$type = 'unique';
				} elseif (substr($base, 0, strlen('FULLTEXT')) == 'FULLTEXT' || $v['expr_type'] == 'fulltext-index') {
					$type = 'fulltext';
				} elseif (substr($base, 0, strlen('SPATIAL')) == 'SPATIAL' || $v['expr_type'] == 'spatial-index') {
					$type = 'spatial';
				}
				$columns = [];
				foreach ((array)$v['sub_tree'] as $v2) {
					if ($v2['expr_type'] == 'const') {
						$name = trim($v2['base_expr'], '"\'`');
					} elseif ($v2['expr_type'] == 'column-list') {
						foreach ((array)$v2['sub_tree'] as $v3) {
							if ($v3['expr_type'] == 'index-column') {
								$index_col_name = strtolower($v3['no_quotes']['parts'][0]);
								$columns[$index_col_name] = $index_col_name;
							}
						}
					} elseif ($v2['expr_type'] == 'colref') {
						$index_col_name = strtolower($v2['no_quotes']['parts'][0]);
						$columns[$index_col_name] = $index_col_name;
					}
				}
				if (!$name) {
					$name = 'idx_'.(count($struct['indexes']) + 1);
				}
				if ($name != 'PRIMARY') {
					$name = strtolower($name);
				}
				$struct['indexes'][$name] = [
					'name'		=> $name,
					'type'		=> strtolower($type),
					'columns'	=> $columns,
				];
				if ($this->RAW_IN_RESULTS) {
					$struct['indexes'][$name]['raw'] = $v['base_expr'];
				}
			} elseif ($v['expr_type'] == 'foreign-key') {
				$name = null;
				$columns = [];
				$ref_table = null;
				$ref_columns = [];
				$on_update = null;
				$on_delete = null;
				foreach ((array)$v['sub_tree'] as $v2) {
					if ($v2['expr_type'] == 'constraint') {
						$name = trim($v2['sub_tree']['base_expr'], '"\'`');
					} elseif ($v2['expr_type'] == 'column-list') {
						foreach ((array)$v2['sub_tree'] as $v3) {
							if ($v3['expr_type'] == 'index-column') {
								$index_col_name = strtolower($v3['no_quotes']['parts'][0]);
								$columns[$index_col_name] = $index_col_name;
							}
						}
					} elseif ($v2['expr_type'] == 'foreign-ref') {
						foreach ((array)$v2['sub_tree'] as $v3) {
							if ($v3['expr_type'] == 'table') {
								$ref_table = $v3['no_quotes']['parts'][0];
							} elseif ($v3['expr_type'] == 'column-list') {
								foreach ((array)$v3['sub_tree'] as $v4) {
									if ($v4['expr_type'] == 'index-column') {
										$ref_col_name = strtolower($v4['no_quotes']['parts'][0]);
										$ref_columns[$ref_col_name] = $ref_col_name;
									}
								}
							}
						}
						$on_update = $v2['on_update'];
						$on_delete = $v2['on_delete'];
					}
				}
				if (!$name) {
					$name = 'fk_'.(count($struct['foreign_keys']) + 1);
				}
				$name = strtolower($name);
				$struct['foreign_keys'][$name] = [
					'name'			=> $name,
					'columns'		=> $columns,
					'ref_table'		=> $ref_table,
					'ref_columns'	=> $ref_columns,
					'on_update'		=> $on_update ? strtoupper($on_update) : null,
					'on_delete'		=> $on_delete ? strtoupper($on_delete) : null,
				];
				if ($this->RAW_IN_RESULTS) {
					$struct['foreign_keys'][$name]['raw'] = $v['base_expr'];
				}
			}
		}
		foreach ((array)$tmp_options as $v) {
			$name = [];
			$val = '';
			foreach ((array)$v['sub_tree'] as $v2) {
				if ($v2['expr_type'] == 'reserved') {
					$name[] = $v2['base_expr'];
				} elseif ($v2['expr_type'] == 'const') {
					$val = $v2['base_expr'];
				}
			}
			$name = strtolower(implode(' ', $name));
			if (in_array($name, ['default charset', 'default character set', 'charset', 'character set'])) {
				$name = 'charset';
			} elseif (in_array($name, ['engine'])) {
				$name = 'engine';
			}
			$struct['options'][$name] = $val;
		}
		// Final fixes for compatibility with db utils:
		// If some column exists inside unique key def -> set its attribute "unique = true"
		foreach ((array)$struct['indexes'] as $idx) {
			if ($idx['type'] === 'unique') {
				foreach ((array)$idx['columns'] as $fname) {
					if (isset($struct['fields'][$fname])) {
						$struct['fields'][$fname]['unique'] = true;
					}
				}
			}
		}
		// If some column exists inside primary key def -> set its attribute "primary = true"
		if (isset($struct['indexes']['PRIMARY'])) {
			foreach ((array)$struct['indexes']['PRIMARY']['columns'] as $fname) {
				if (isset($struct['fields'][$fname])) {
					$struct['fields'][$fname]['primary'] = true;
				}
			}
		}
		return $struct;
	}

	/**
	*/
	function _get_int_def_length ($type) {
		$a = [
			'tinyint'	=> 3,
			'smallint'	=> 5,
			'mediumint'	=> 8,
			'int'		=> 11,
			'bigint'	=> 20,
		];
		return $a[$type] ?: 11;
	}

	/**
	* Useful for ALTER TABLE
	*/
	private function _implode_line($a) {
		foreach ($a as $k => $v) {
			$v = trim($v);
			if (!strlen($v)) {
				unset($a[$k]);
			}
		}
		return '  '.implode(' ', $a);
	}

	/**
	*/
	function escape_table_name($name = '') {
		$name = trim($name);
		if (!strlen($name)) {
			return false;
		}
		$db = '';
		$table = '';
		if (strpos($name, '.') !== false) {
			list($db, $table) = explode('.', $name);
			$db = trim($db);
			$table = trim($table);
		} else {
			$table = $name;
		}
		if (!strlen($table)) {
			return false;
		}
		return (strlen($db) ? $this->escape_key($db).'.' : ''). $this->escape_key($table);
	}

	/**
	*/
	function escape_key($data) {
		if (is_array($data)) {
			$func = __FUNCTION__;
			foreach ((array)$data as $k => $v) {
				$data[$k] = $this->$func($v);
			}
			return $data;
		}
		return '`'.$data.'`';
	}
}
