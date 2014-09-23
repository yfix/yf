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
	function _init () {
		$libs_root = YF_PATH.'libs';
		require_once $libs_root.'/symfony_class_loader/UniversalClassLoader.php';
		$loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
		$loader->registerNamespaces(array(
			'PHPSQLParser' => $libs_root.'/php_sql_parser/src',
		));
		$loader->register();
		$this->parser = new \PHPSQLParser\PHPSQLParser();
	}

	/**
	*/
	function create (array $data, $params = array()) {
		if (!strlen($data['name']) || empty($data['fields'])) {
			return false;
		}
		$implode_line = function($a) {
			foreach ($a as $k => $v) {
				$v = trim($v);
				if (!strlen($v)) {
					unset($a[$k]);
				}
			}
			return '  '.implode(' ', $a);
		};

		$table_name = $data['name'];
		foreach ((array)$data['fields'] as $name => $v) {
			$type_braces = (isset($v['length']) ? '('.$v['length']. (isset($v['decimals']) ? ','.$v['decimals'] : '').')' : '');
			if (in_array($v['type'], array('enum','set'))) {
				$type_braces = '(\''.implode('\',\'', $v['values']).'\')';
			}
			$def = false;
			if ($v['default'] === 'NULL') {
				$def = 'NULL';
			} elseif ($v['type'] == 'timestamp') {
				$def = $v['default'];
			} elseif (strlen($v['default'])) {
				$def = '\''.$v['default'].'\'';
			}
			$lines[] = $implode_line(array(
				'name'		=> '`'.$name.'`',
				'type'		=> $v['type']. $type_braces,
				'unsigned'	=> $v['unsigned'] ? 'unsigned' : '',
				'nullable'	=> !$v['nullable'] ? 'NOT NULL' : '',
				'charset'	=> $v['charset'] ? 'CHARACTER SET '.$v['charset'] : '',
				'collate'	=> $v['collate'] ? 'COLLATE '.$v['collate'] : '',
				'default'	=> $def ? 'DEFAULT '.$def : '',
				'auto_inc'	=> $v['auto_inc'] ? 'AUTO_INCREMENT' : '',
				'on_update'	=> $v['on_update'] ?: '',
			));
		}
		foreach ((array)$data['indexes'] as $name => $v) {
			$type = 'KEY';
			if ($v['type'] == 'primary') {
				$type = 'PRIMARY KEY';
			} elseif ($v['type'] == 'unique') {
				$type = 'UNIQUE KEY';
			} elseif ($v['type'] == 'fulltext') {
				$type = 'FULLTEXT KEY';
			} elseif ($v['type'] == 'spatial') {
				$type = 'SPATIAL KEY';
			}
			$lines[] = $implode_line(array(
				'type'		=> $type,
				'name'		=> strlen($name) && !is_numeric($name) && in_array($v['type'], array('index', 'unique', 'fulltext', 'spatial')) ? '`'.$name.'`' : '',
				'columns'	=> '(`'.implode('`,`', $v['columns']).'`)',
			));
		}
		foreach ((array)$data['foreign_keys'] as $name => $v) {
			$lines[] = $implode_line(array(
				'begin'			=> 'CONSTRAINT',
				'name'			=> '`'.$name.'`',
				'fk'			=> 'FOREIGN KEY',
				'columns'		=> '(`'.implode('`,`', $v['columns']).'`)',
				'ref'			=> 'REFERENCES',
				'ref_table'		=> '`'.$v['ref_table'].'`',
				'ref_columns'	=> '(`'.implode('`,`', $v['ref_columns']).'`)',
				'on_delete'		=> $v['on_delete'] ? 'ON DELETE '.$v['on_delete'] : '',
				'on_update'		=> $v['on_update'] ? 'ON UPDATE '.$v['on_update'] : '',
			));
		}
		$options = array();
		foreach ((array)$data['options'] as $k => $v) {
			if ($k == 'charset') {
				$k = 'DEFAULT CHARSET';
			}
			$options[$k] = strtoupper($k).'='.$v;
		}
		return 'CREATE TABLE `'.$table_name.'` ('.PHP_EOL. implode(','.PHP_EOL, $lines). PHP_EOL.')'. ($options ? ' '.implode(' ', $options) : '').';';
	}

	/**
	*/
	function parse ($sql) {
		$parsed = $this->parser->parse($sql);

		$table_name = $parsed['TABLE']['no_quotes']['parts'][0] ?: '';
		$tmp_create_def = $parsed['TABLE']['create-def']['sub_tree'] ?: array();
		$tmp_options = $parsed['TABLE']['options'] ?: array();

		$struct = array(
			'name'	=> $table_name,
			'fields' => array(),
			'indexes' => array(),
			'foreign_keys' => array(),
			'options' => array(),
		);

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
							} elseif ($v3['expr_type'] == 'reserved' && in_array($v3['base_expr'], array('enum', 'set'))) {
								$type = $v3['base_expr'];
								if ($v3['sub_tree']['expr_type'] != 'bracket_expression') {
									continue;
								}
								$values = array();
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
						if (in_array($type, array('timestamp','datetime'))) {
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
				$struct['fields'][$name] = array(
					'name'		=> $name,
					'type'		=> $type,
					'length'	=> $length,
					'decimals'	=> $decimals,
					'unsigned'	=> $unsigned,
					'nullable'	=> $nullable,
					'default'	=> $default,
					'charset'	=> $charset,
					'collate'	=> $collate,
					'auto_inc'	=> $auto_inc,
					'primary'	=> $primary,
					'unique'	=> $unique,
					'values'	=> !empty($values) ? $values : null,
				);
				if ($on_update) {
					$struct['fields'][$name]['on_update'] = $on_update;
				}
				if ($this->RAW_IN_RESULTS) {
					$struct['fields'][$name]['raw'] = $v['base_expr'];
				}
			} elseif ($v['expr_type'] == 'primary-key') {
				$name = 'PRIMARY';
				$type = 'primary';
				$columns = array();
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
				$struct['indexes'][$name] = array(
					'name'		=> $name,
					'type'		=> $type,
					'columns'	=> $columns,
				);
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
				$columns = array();
				foreach ((array)$v['sub_tree'] as $v2) {
					if ($v2['expr_type'] == 'const') {
						$name = trim($v2['base_expr'], '"\'`');
					} elseif ($v2['expr_type'] == 'column-list') {
						foreach ((array)$v2['sub_tree'] as $v3) {
							if ($v3['expr_type'] == 'index-column') {
								$index_col_name = $v3['no_quotes']['parts'][0];
								$columns[$index_col_name] = $index_col_name;
							}
						}
					} elseif ($v2['expr_type'] == 'colref') {
						$index_col_name = $v2['no_quotes']['parts'][0];
						$columns[$index_col_name] = $index_col_name;
					}
				}
				if (!$name) {
					$name = 'idx_'.(count($struct['indexes']) + 1);
				}
				$struct['indexes'][$name] = array(
					'name'		=> $name,
					'type'		=> $type,
					'columns'	=> $columns,
				);
				if ($this->RAW_IN_RESULTS) {
					$struct['indexes'][$name]['raw'] = $v['base_expr'];
				}
			} elseif ($v['expr_type'] == 'foreign-key') {
				$name = null;
				$columns = array();
				$ref_table = null;
				$ref_columns = array();
				$on_update = null;
				$on_delete = null;
				foreach ((array)$v['sub_tree'] as $v2) {
					if ($v2['expr_type'] == 'constraint') {
						$name = trim($v2['sub_tree']['base_expr'], '"\'`');
					} elseif ($v2['expr_type'] == 'column-list') {
						foreach ((array)$v2['sub_tree'] as $v3) {
							if ($v3['expr_type'] == 'index-column') {
								$index_col_name = $v3['no_quotes']['parts'][0];
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
										$ref_col_name = $v4['no_quotes']['parts'][0];
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
				$struct['foreign_keys'][$name] = array(
					'name'			=> $name,
					'columns'		=> $columns,
					'ref_table'		=> $ref_table,
					'ref_columns'	=> $ref_columns,
					'on_update'		=> $on_update,
					'on_delete'		=> $on_delete,
				);
				if ($this->RAW_IN_RESULTS) {
					$struct['foreign_keys'][$name]['raw'] = $v['base_expr'];
				}
			}
		}
		foreach ((array)$tmp_options as $v) {
			$name = array();
			$val = '';
			foreach ((array)$v['sub_tree'] as $v2) {
				if ($v2['expr_type'] == 'reserved') {
					$name[] = $v2['base_expr'];
				} elseif ($v2['expr_type'] == 'const') {
					$val = $v2['base_expr'];
				}
			}
			$name = strtolower(implode(' ', $name));
			if (in_array($name, array('default charset', 'default character set', 'charset', 'character set'))) {
				$name = 'charset';
			} elseif (in_array($name, array('engine'))) {
				$name = 'engine';
			}
			$struct['options'][$name] = $val;
		}
		return $struct;
	}
}
