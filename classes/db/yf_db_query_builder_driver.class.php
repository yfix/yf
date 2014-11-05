<?php

/**
* Query builder (Active Record)
*/
abstract class yf_db_query_builder_driver {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* We cleanup object properties when cloning
	*/
	function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	* Need to avoid calling render() without params
	*/
	function __toString() {
		return $this->render();
	}

	/**
	*/
	function dump_json () {
		return json_encode($this->exec());
	}

	/**
	* Alias
	*/
	function sql() {
		return $this->render();
	}

	/**
	* Create text SQL from params
	*/
	function render() {
		$sql = '';
		$a = $this->_sql_to_array();
		if ($a) {
			$sql = implode(' ', $a);
		}
		if (empty($sql)) {
			return false;
		}
		return $sql;
	}

	/**
	* Create text SQL from params
	*/
	function _sql_to_array($return_raw = false) {
		$a = array();
		// Save 1 call of select()
		if (empty($this->_sql['select']) && !empty($this->_sql['from'])) {
			$this->select();
		}
		if (empty($this->_sql['select']) || empty($this->_sql['from'])) {
			return array();
		}
		// HAVING without GROUP BY makes no sense
		if (!empty($this->_sql['having']) && empty($this->_sql['group_by'])) {
			unset($this->_sql['having']);
		}
		$opts = array(
			'select'		=> array('separator' => ',', 'operator' => 'SELECT'),
			'from'			=> array('separator' => ',', 'operator' => 'FROM'),
			'join'			=> array('separator' => 'JOIN', 'operator' => 'JOIN'),
			'left_join'		=> array('separator' => 'LEFT JOIN', 'operator' => 'LEFT JOIN'),
			'right_join'	=> array('separator' => 'RIGHT JOIN', 'operator' => 'RIGHT JOIN'),
			'inner_join'	=> array('separator' => 'INNER JOIN', 'operator' => 'INNER JOIN'),
			'where'			=> array('separator' => 'AND', 'operator' => 'WHERE'),
			'where_or'		=> array('separator' => 'OR', 'operator' => 'OR'),
			'group_by'		=> array('separator' => ',', 'operator' => 'GROUP BY'),
			'having'		=> array('separator' => 'AND', 'operator' => 'HAVING'),
			'order_by'		=> array('separator' => ',', 'operator' => 'ORDER BY'),
			'limit'			=> array(/* 'operator' => 'LIMIT' */),
		);
		// Ensuring strict order of parts of the generated SQL will be correct, no matter how functions were called
		foreach ($opts as $name => $opt) {
			if (empty($this->_sql[$name])) {
				continue;
			}
			$operator = $opt['operator'];
			if (is_array($this->_sql[$name])) {
				if (isset($opt['separator'])) {
					if ($return_raw) {
						$a[$name] = array('operator' => $operator, 'separator' => $opt['separator'], 'condition' => $this->_sql[$name]);
					} else {
						$a[$name] = $operator.' '.implode(' '.$opt['separator'].' ', $this->_sql[$name]);
					}
				}
			} else {
				if ($return_raw) {
					$a[$name] = array('operator' => ($operator ? $operator.' ' : ''), 'condition' => $this->_sql[$name]);
				} else {
					$a[$name] = ($operator ? $operator.' ' : ''). $this->_sql[$name];
				}
			}
		}
		return $a;
	}

	/**
	* Execute generated query
	*/
	function exec($as_sql = false) {
		$sql = $this->render();
		if ($as_sql) {
			return $sql;
		}
		if ($sql) {
			return $this->db->query($sql);
		}
		return false;
	}

	/**
	*/
	function delete($as_sql = false) {
		$sql = false;
		if (empty($this->_sql['from'])) {
			return false;
		}
		if ($this->_remove_as_from_delete) {
			$table = preg_replace('~[^a-z0-9_\s]~ims', '', $this->_sql['from'][0]);
			if (preg_match('~^([a-z0-9\(\)*_\.]+)[\s]+AS[\s]+([a-z0-9_]+)$~ims', $table, $m)) {
				$table = $m[1];
				$this->_sql['from'] = array($this->_escape_table_name($table));
			}
		}
		$a = $this->_sql_to_array();
		if ($a) {
			$to_leave = array('from','where','where_or','limit');
			foreach ($a as $k => $v) {
				if (!in_array($k, $to_leave)) {
					unset($a[$k]);
				}
			}
			if ($a && isset($a['from'])) {
				$a = array('delete' => 'DELETE') + $a;
				$sql = implode(' ', $a);
			}
		}
		if ($as_sql) {
			return $sql;
		}
		if ($sql) {
			return $this->db->query($sql);
		}
		return false;
	}

	/**
	*/
	function insert(array $data, $params = array()) {
// usage pattern: select('id, name')->from('table1')->where('age','>','30')->limit(50)->insert('table2')
// usage pattern: select('id, name')->from('table1')->where('age','>','30')->limit(50)->insert('table2', array('id' => '@id', 'name' => '@name'))
// Use for into_table here INSERT INTO ... SELECT .. FROM ...
//		if ($params['into_table']) { };
		if (empty($data)) {
			return false;
		}
		$a = array();
		if (empty($this->_sql['from'])) {
			return false;
		}
		$table = preg_replace('~[^a-z0-9_\s]~ims', '', $this->_sql['from'][0]);
		if (preg_match('~^([a-z0-9\(\)*_\.]+)[\s]+AS[\s]+([a-z0-9_]+)$~ims', $table, $m)) {
			$table = $m[1];
		}
		if (!$table) {
			return false;
		}
		$sql = $this->compile_insert($table, $data, $params);
		if ($sql) {
			$result = $this->db->query($sql);
			$insert_id = $result ? $this->db->insert_id() : false;
			return $insert_id ?: $result;
		}
		return false;
	}

	/**
	* Insert array of values into table
	*/
	function compile_insert($table, $data, $params = array()) {
		if (!strlen($table) || !is_array($data)) {
			return false;
		}
		$replace = isset($params['replace']) ? $params['replace'] : false;
		$ignore = isset($params['ignore']) ? $params['ignore'] : false;
		$on_duplicate_key_update = isset($params['on_duplicate_key_update']) ? $params['on_duplicate_key_update'] : false;
		if (is_string($replace)) {
			$replace = false;
		}
		$values_array = array();
		// Try to check if array is two-dimensional
		foreach ((array)$data as $cur_row) {
			$is_multiple = is_array($cur_row) ? 1 : 0;
			break;
		}
		$cols = array();
		if ($is_multiple) {
			foreach ((array)$data as $cur_row) {
				if (empty($cols)) {
					$cols = array_keys($cur_row);
				}
				// This method ensures that SQL will consist of same key=value pairs, even if in some sub-array they will be missing
				foreach ((array)$cols as $col) {
					$cur_values[$col] = $cur_row[$col];
				}
				$values_array[] = '('.implode(', ', $this->_escape_val($cur_values)).PHP_EOL.')';
			}
		} elseif (count($data)) {
			$cols	= array_keys($data);
			$values = array_values($data);
			foreach ((array)$values as $k => $v) {
				$values[$k] = $this->_escape_val($v);
			}
			$values_array[] = '('.implode(', ', $values).PHP_EOL.')';
		}
		foreach ((array)$cols as $k => $v) {
			unset($cols[$k]);
			$cols[$v] = $this->_escape_key($v);
		}
		$sql = '';
		if (count($cols) && count($values_array)) {
			$sql = ($replace ? 'REPLACE' : 'INSERT'). ($ignore ? ' IGNORE' : '')
				.' INTO '.$this->_escape_table_name($table).PHP_EOL
				.' ('.implode(', ', $cols).') VALUES '
				.PHP_EOL.implode(', ', $values_array);
			if ($on_duplicate_key_update) {
				$sql .= PHP_EOL.' ON DUPLICATE KEY UPDATE ';
				$tmp = array();
				foreach ((array)$cols as $col => $col_escaped) {
					if ($col == 'id') {
						continue;
					}
					$tmp[] = $col_escaped.' = VALUES('.$col_escaped.')';
				}
				$sql .= implode(', ', $tmp);
			}
		}
		return $sql ?: false;
	}

	/**
	*/
	function update(array $data, $params = array()) {
// usage pattern: select('id, name')->from('table1')->where('age','>','30')->limit(50)->update(array('last_activity' => time()))
// usage pattern: select('id, name')->from('table1')->where('age','>','30')->limit(50)->update(array('id' => '@id', 'name' => '@name'), array('table' => 'table2'))
// TODO: be able to specify other table in params
// TODO: where condition for update inside params
#		if ($is_3d_array) {
#			$this->update_batch();
#		} else {
#			$sql = $this->compile_update();
#			$this->db->query($sql);
#		}
		if (empty($data)) {
			return false;
		}
		$a = array();
		if (empty($this->_sql['from'])) {
			return false;
		}
		$table = preg_replace('~[^a-z0-9_\s]~ims', '', $this->_sql['from'][0]);
		if (preg_match('~^([a-z0-9\(\)*_\.]+)[\s]+AS[\s]+([a-z0-9_]+)$~ims', $table, $m)) {
			$table = $m[1];
		}
		if (!$table) {
			return false;
		}
		$a = $this->_sql_to_array($return_raw = true);
		$where = '';
		if (isset($a['where'])) {
			$where = implode(' '.$a['where']['separator'].' ', $a['where']['condition']);
		}
		if (isset($a['where_or'])) {
			$where = rtrim($where).' '.$a['where_or']['operator'].' '.implode(' '.$a['where_or']['separator'].' ', $a['where_or']['condition']);
		}
		$sql = $this->compile_update($table, $data, $where);
		if ($sql) {
			$result = $this->db->query($sql);
		}
		return false;
	}

	/**
	* Update table with given values
	*/
	function compile_update($table, array $data, $where) {
		if (empty($table) || empty($data) || empty($where)) {
			return false;
		}
		// $where contains numeric id
		if (is_numeric($where)) {
			$where = 'id='.intval($where);
		}
		$tmp_data = array();
		foreach ((array)$data as $k => $v) {
			if (empty($k)) {
				continue;
			}
			$tmp_data[$k] = $this->_escape_key($k).' = '.$this->_escape_val($v);
		}
		$sql = '';
		if (count($tmp_data)) {
			$sql = 'UPDATE '.$this->_escape_table_name($table).' SET '.implode(', ', $tmp_data). (!empty($where) ? ' WHERE '.$where : '');
		}
		return $sql ?: false;
	}

	/**
	*/
	function update_batch($table, array $data, $index = null, $only_sql = false, $params = array()) {
		if (!$index) {
			$index = 'id';
		}
		if (!strlen($table) || !$data || !is_array($data) || !$index) {
			return false;
		}
		$this->_set_update_batch_data($data, $index);
		if (count($this->_qb_set) === 0) {
			return false;
		}
		$affected_rows = 0;
		$records_at_once = 100;
		$out = '';
		for ($i = 0, $total = count($this->_qb_set); $i < $total; $i += $records_at_once) {
			$_data = array_slice($this->_qb_set, $i, $records_at_once);
			$sql = $this->_get_update_batch_sql($table, $_data, $index);
			if (is_callable($params['split_callback'])) {
				$callback = $params['split_callback'];
				$callback($_data);
			}
			if ($only_sql) {
				$out .= $sql.';'.PHP_EOL;
			} else {
				$this->db->query($sql);
				$affected_rows += $this->db->affected_rows();
			}
		}
		$this->_qb_set = array();
		if ( ! $only_sql) {
			$out = $affected_rows;
		}
		return $out;
	}

	/**
	*/
	function _set_update_batch_data($key, $index = '') {
		if (!is_array($key)) {
			return false;
		}
		foreach ((array)$key as $k => $v) {
			$index_set = FALSE;
			$clean = array();
			foreach ((array)$v as $k2 => $v2) {
				if ($k2 === $index)	{
					$index_set = TRUE;
				}
				$clean[$this->_escape_key($k2)] = $this->_escape_val($v2);
			}
			if ($index_set === FALSE) {
				throw new Exception('db_batch_missing_index');
				return false;
			}
			$this->_qb_set[] = $clean;
		}
	}

	/**
	*/
	function _get_update_batch_sql($table, $values, $index) {
		$index = $this->_escape_key($index);
		$ids = array();
		foreach ((array)$values as $key => $val) {
			$ids[] = $val[$index];
			foreach (array_keys($val) as $field) {
				if ($field !== $index) {
					$final[$field][] = 'WHEN '.$index.' = '.$val[$index].' THEN '.$val[$field];
				}
			}
		}
		$cases = '';
		foreach ((array)$final as $k => $v) {
			$cases .= $k.' = CASE '.PHP_EOL. implode(PHP_EOL, $v). PHP_EOL. 'ELSE '.$k.' END, ';
		}
		return 'UPDATE '.$this->_escape_table_name($table).' SET '.substr($cases, 0, -2). ' WHERE '.$index.' IN('.implode(',', $ids).')';
	}

	/**
	* Counting number of records inside requested recordset
	*/
	function count() {
		$this->_sql['select'] = 'COUNT(*)';
		return $this->get_one();
	}

	/**
	*/
	function first($use_cache = false) {
// TODO order_by PK asc limit 1
		return $this->get($use_cache);
	}

	/**
	*/
	function last($use_cache = false) {
// TODO order_by PK desc limit 1
		return $this->get($use_cache);
	}

	/**
	* Render SQL and execute db->get()
	*/
	function get($use_cache = false) {
		$sql = $this->sql();
		if ($sql) {
			$result = $this->db->get($sql, $use_cache);
			if ($result && is_callable($this->_result_wrapper)) {
				return call_user_func($this->_result_wrapper, $result);
			}
			return $result;
		}
		return false;
	}

	/**
	* Alias
	*/
	function one($use_cache = false) {
		return $this->get_one($use_cache);
	}

	/**
	* Render SQL and execute db->get_one()
	*/
	function get_one($use_cache = false) {
		$sql = $this->sql();
		if ($sql) {
			return $this->db->get_one($sql, $use_cache);
		}
		return false;
	}

	/**
	* Alias
	*/
	function all($use_cache = false) {
		return $this->get_all($use_cache);
	}

	/**
	* Render SQL and execute db->get_all()
	*/
	function get_all($use_cache = false) {
		$sql = $this->sql();
		if ($sql) {
			$result = $this->db->get_all($sql, $key_name, $use_cache);
			if ($result && is_callable($this->_result_wrapper)) {
				foreach ((array)$result as $k => $v) {
					$result[$k] = call_user_func($this->_result_wrapper, $v);
				}
			}
			return $result;
		}
		return false;
	}

	/**
	* Render SQL and execute db->get_2d()
	*/
	function get_2d($use_cache = false) {
		$sql = $this->sql();
		if ($sql) {
			$result = $this->db->get_2d($sql, $use_cache);
			if (is_callable($this->_result_wrapper)) {
				return call_user_func($this->_result_wrapper, $result);
			}
			return $result;
		}
		return false;
	}

	/**
	* Render SQL and execute db->get_deep_array()
	*/
	function get_deep_array($levels = 1, $use_cache = false) {
		$sql = $this->sql();
		if ($sql) {
			return $this->db->get_deep_array($sql, $levels, $use_cache);
		}
		return false;
	}

// TODO: optionally check available fields and tables with db_installer sql data
	/**
	* Examples:
	*	db()
	*	->select('id','name')
	*	->from('users','u')
	*	->inner_join('groups','g',array('u.group_id'=>'g.id'))
	*	->order_by('add_date')
	*	->group_by('id')
	*	->limit(10)
	*/
	function select() {
		$sql = '';
		$fields = func_get_args();
		if (isset($fields[0]) && is_array($fields[0]) && isset($fields[0]['__args__'])) {
			$fields = $fields[0]['__args__'];
		}
		if (!count($fields) || $fields === array(array())) {
			$sql = '*';
		} else {
			$a = array();
			foreach ((array)$fields as $k => $v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				if (is_string($v) && strlen($v) && !empty($v)) {
					// support for syntax: select('a.id as aid')
					if (preg_match('~^([a-z0-9\(\)*_\.]+)[\s]+AS[\s]+([a-z0-9_]+)$~ims', $v, $m)) {
						$a[] = $this->_escape_expr($m[1]).' AS '.$this->_escape_key($m[2]);
					} else {
						$a[] = $this->_escape_expr($v);
					}
				} elseif (is_callable($v)) {
					$a[] = $v($fields, $this);
				} elseif (is_array($v)) {
					foreach ((array)$v as $k2 => $v2) {
						$k2 = trim($k2);
						$v2 = trim($v2);
						if (strlen($k2) && strlen($v2)) {
							// support for syntax: select('a.id as aid')
							if (preg_match('~^([a-z0-9\(\)*_\.]+)[\s]+AS[\s]+([a-z0-9_]+)$~ims', $v2, $m)) {
								$a[] = $this->_escape_expr($m[1]).' AS '.$this->_escape_key($m[2]);
							} else {
								$a[] = $this->_escape_expr($k2).' AS '.$this->_escape_key($v2);
							}
						}
					}
				}
			}
			if ($a) {
				$sql = implode(', ', $a);
			}
		}
		if ($sql) {
			$this->_sql[__FUNCTION__][] = $sql;
		}
		return $this;
	}

	/**
	* Examples: from('users'), from(array('users' => 'u', 'suppliers' => 's'))
	*/
	function from() {
		$sql = '';
		$tables = func_get_args();
		if (isset($tables[0]) && is_array($tables[0]) && isset($tables[0]['__args__'])) {
			$tables = $tables[0]['__args__'];
		}
		if (count($tables)) {
			$a = array();
			foreach ((array)$tables as $k => $v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				if (is_string($v) && strlen($v) && !empty($v)) {
					// support for syntax: from('users as u') from('users as u', 'messages as m')
					if (preg_match('~^([a-z0-9\(\)*_\.]+)[\s]+AS[\s]+([a-z0-9_]+)$~ims', $v, $m)) {
						$a[] = $this->_escape_table_name($m[1]).' AS '.$this->_escape_key($m[2]);
					} else {
						$a[] = $this->_escape_table_name($v);
					}
				} elseif (is_callable($v)) {
					$a[] = $v($tables, $this);
				} elseif (is_array($v)) {
					foreach ((array)$v as $k2 => $v2) {
						$k2 = trim($k2);
						$v2 = trim($v2);
						if (strlen($k2) && strlen($v2)) {
							// support for syntax: from('users as u') from('users as u', 'messages as m')
							if (preg_match('~^([a-z0-9\(\)*_\.]+)[\s]+AS[\s]+([a-z0-9_]+)$~ims', $v2, $m)) {
								$a[] = $this->_escape_table_name($m[1]).' AS '.$this->_escape_key($m[2]);
							} else {
								$a[] = $this->_escape_table_name($k2).' AS '.$this->_escape_key($v2);
							}
						}
					}
				}
			}
			if ($a) {
				$sql = implode(', ', $a);
			}
		}
		if ($sql) {
			$this->_sql[__FUNCTION__][] = $sql;
		}
		return $this;
	}

	/**
	*/
	function _ids_sql_from_array(array $ids) {
		foreach ((array)$ids as $v) {
			if (!is_int($v)) {
				$v = (string)$v;
				if (!strlen($v)) {
					continue;
				}
				$v = $this->_escape_val($v);
			}
			$out[$v] = $v;
		}
		return implode(',', $out);
	}

	/**
	* Example: whereid(1)
	*/
	function whereid($id, $pk = '') {
		!$pk && $pk = 'id';
		$sql = '';
		if (is_array($id)) {
			$ids = $this->_ids_sql_from_array($id);
			if ($ids) {
				$sql = $this->_escape_col_name($pk).' IN('.$ids.')';
			}
		} elseif (is_callable($id)) {
			$sql = $id();
		} else {
			$sql = $this->_process_where_cond($pk, '=', intval($id));
		}
		if ($sql) {
			$this->_sql['where'][] = $sql;
		}
		return $this;
	}

	/**
	* Example: where(array('id','>','1'),'and',array('name','!=','peter'))
	*/
	function where() {
		$this->_process_where(func_get_args(), __FUNCTION__);
		return $this;
	}

	/**
	* Example: where_or(array('id','>','1'))
	*/
	function where_or() {
		$this->_process_where(func_get_args(), __FUNCTION__);
		return $this;
	}

	/**
	*/
	function _process_where(array $where, $func_name = 'where') {
// TODO: auto-detect and apply whereid: where(1)
// TODO: auto-detect and apply whereid with several numbers where(1,2,3) === whereid(1,2,3)
		$sql = '';
		if (isset($where[0]) && is_array($where[0]) && isset($where[0]['__args__'])) {
			$where = $where[0]['__args__'];
		}
		$count = count($where);
		if (($count === 3 || $count === 2) && is_string($where[0]) && is_string($where[1])) {
			$sql = $this->_process_where_cond($where[0], $where[1], $where[2]);
		} elseif ($count) {
			$a = array();
			foreach ((array)$where as $k => $v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				if (is_string($v) && strlen($v) && !empty($v)) {
					if (preg_match('~^([a-z0-9\(\)*_\.]+)[\s]*(=|!=|<>|<|>|>=|<=)[\s]*([a-z0-9_\.]+)$~ims', $v, $m)) {
						$a[] = $this->_process_where_cond($m[1], $m[2], $m[3]);
					} elseif (preg_match('~^([a-z0-9\(\)*_\.]+)[\s]*(is[\s]+null|is[\s]+not[\s]+null)$~ims', $v, $m)) {
						$a[] = $this->_process_where_cond($m[1], $m[2], '');
					} else {
						$v = strtoupper(trim($v));
						if (in_array($v, array('AND','OR','XOR'))) {
							$a[] = $v;
						}
					}
				} elseif (is_array($v)) {
					// array('field', 'condition', 'value'), example: array('id','>','1')
					$_count_v = count($v);
					if (($_count_v === 3 || $_count_v === 2) && isset($v[0])) {
						$a[] = $this->_process_where_cond($v[0], $v[1], $v[2]);
					// array('field1' => 'val1', 'field2' => 'val2')
					} else {
						$tmp = array();
						foreach ($v as $k2 => $v2) {
							$_tmp = $this->_process_where_cond($k2, '=', $v2);
							if ($_tmp) {
								$tmp[] = $_tmp;
							}
						}
						if ($tmp) {
							$a[] = implode(' AND ', $tmp);
						}
					}
				} elseif (is_callable($v)) {
					$a[] = $v($where, $this);
				}
			}
			if ($a) {
				$sql = implode(' ', $a);
			}
		}
		if ($sql) {
			$this->_sql[$func_name][] = $sql;
		}
	}

	/**
	*/
	function _process_where_cond($left, $op, $right) {
		!$op && $op = '=';
		$left = trim(strtolower($left));
		$op = trim(strtolower($op));
		$right_generated = '';
		if (is_string($right) && (false !== strpos($right, '%') || false !== strpos($right, '*'))) {
			if ($op == '=') {
				$op = 'like';
			} elseif ($op == '!=') {
				$op = 'not like';
			}
		}
		if ($op === 'like' || $op === 'not like') {
			$right = str_replace('*', '%', $right);
		} elseif ($op === 'is null' || $op === 'is not null') {
			$right = '';
			$right_generated = '__dummy_for_null__';
		} elseif ($op === 'in' || $op === 'not in') {
			$right_generated = $this->_ids_sql_from_array((array)$right);
			if (strlen($right_generated)) {
				$right_generated = '('.$right_generated.')';
			}
		// Think that we dealing with 2 arguments passing like this: where('id',1)
		} elseif (strlen($left) && (strlen($op) && !in_array($op, array('=','!=','<','>','<=','>='))) && !strlen($right)) {
			$right = $op;
			$op = '=';
		}
		if ((empty($right) || !strlen(is_array($right) ? '' : $right)) && !strlen($right_generated)) {
			return '';
		}
		if ($right_generated === '__dummy_for_null__') {
			return $this->_escape_expr($left). ' '. strtoupper($op);
		}
		return $this->_escape_expr($left). ' '. strtoupper($op). ($right_generated ?: ' '.$this->_escape_val($right));
	}

	/**
	* Examples: join('suppliers', array('u.supplier_id' => 's.id'))
	*/
	function join($table, $on, $join_type = '') {
		$join_types = array(
			'left',
			'right',
			'inner',
		);
		if (!in_array($join_type, $join_types)) {
			$join_type = '';
		}
		$as = '';
		if (is_array($table)) {
			$as = current($table);
			$table = key($table);
		} elseif (is_string($table)) {
			// support for syntax: join('users as u', 'u.id = s.id')
			if (preg_match('~^([a-z0-9\(\)*_\.]+)[\s]+AS[\s]+([a-z0-9_]+)$~ims', $table, $m)) {
				$table = $m[1];
				$as = $m[2];
			}
		}
		$_on = array();
		if (is_array($on)) {
			foreach ((array)$on as $k => $v) {
				$_on[] = $this->_escape_col_name($k).' = '.$this->_escape_col_name($v);
			}
		} elseif (is_callable($on)) {
			$_on = $on($table, $this);
		} elseif (is_string($on)) {
			if (preg_match('~^([a-z0-9\(\)*_\.]+)[\s]*(=|!=|<>|<|>|>=|<=)[\s]*([a-z0-9_\.]+)$~ims', $on, $m)) {
				$_on[] = $this->_escape_col_name($m[1]). ' '. $m[2]. ' '. $this->_escape_col_name($m[3]);
			}
		}
		$sql = '';
		if (is_string($table) && !empty($_on)) {
			$sql = $this->_escape_table_name($table). ($as ? ' AS '.$this->_escape_key($as) : '').' ON '.implode(',', $_on);
		}
		if ($sql) {
			$this->_sql[($join_type ? $join_type.'_' : '').__FUNCTION__][] = $sql;
		}
		return $this;
	}

	/**
	*/
	function left_join($table, $on) {
		return $this->join($table, $on, 'left');
	}

	/**
	*/
	function right_join($table, $on) {
		return $this->join($table, $on, 'right');
	}

	/**
	*/
	function inner_join($table, $on) {
		return $this->join($table, $on, 'inner');
	}

	/**
	* Examples: group_by('user_group'), group_by(array('supplier','manufacturer'))
	*/
	function group_by() {
		$sql = '';
		$items = func_get_args();
		if (count($items)) {
			$a = array();
			foreach ((array)$items as $k => $v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				if (is_string($v) && strlen($v) && !empty($v)) {
					$a[] = $this->_escape_expr($v);
				} elseif (is_array($v)) {
					foreach ((array)$v as $v2) {
						if (!is_string($v2)) {
							continue;
						}
						$v2 = trim($v2);
						if ($v2) {
							$a[] = $this->_escape_expr($v2);
						}
					}
				} elseif (is_callable($v)) {
					$a[] = $v($items, $this);
				}
			}
			if ($a) {
				$sql = implode(', ', $a);
			}
		}
		if ($sql) {
			$this->_sql[__FUNCTION__][] = $sql;
		}
		return $this;
	}

	/**
	* Examples: having(array('COUNT(*)','>','1'))
	*/
	function having() {
		$sql = '';
		$where = func_get_args();
		if (count($where)) {
			$a = array();
			foreach ((array)$where as $k => $v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				if (is_string($v) && strlen($v) && !empty($v)) {
					if (preg_match('~^([a-z0-9\(\)*_\.]+)[\s]*(=|!=|<>|<|>|>=|<=)[\s]*([a-z0-9_\.]+)$~ims', $v, $m)) {
						$a[] = $this->_process_where_cond($m[1], $m[2], $m[3]);
					} else {
						$v = strtoupper(trim($v));
						if (in_array($v, array('AND','OR','XOR'))) {
							$a[] = $v;
						}
					}
				} elseif (is_array($v)) {
					// array('field', 'condition', 'value'), example: array('id','>','1')
					if (count($v) == 3 && isset($v[0])) {
						$a[] = $this->_process_where_cond($v[0], $v[1], $v[2]);
					// array('field1' => 'val1', 'field2' => 'val2')
					} else {
						$tmp = array();
						foreach ($v as $k2 => $v2) {
							$_tmp = $this->_process_where_cond($k2, '=', $v2);
							if ($_tmp) {
								$tmp[] = $_tmp;
							}
						}
						if ($tmp) {
							$a[] = implode(' AND ', $tmp);
						}
					}
				} elseif (is_callable($v)) {
					$a[] = $v($where, $this);
				}
			}
			if ($a) {
				$sql = implode(' AND ', $a);
			}
		}
		if ($sql) {
			$this->_sql[__FUNCTION__][] = $sql;
		}
		return $this;
	}

	/**
	* Examples: order_by('user_group'), order_by(array('supplier' => 'DESC','manufacturer' => ASC))
	*/
	function order_by() {
		$sql = '';
		$items = func_get_args();
		if (count($items)) {
			$a = array();
			foreach ((array)$items as $k => $v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				if (is_string($v) && strlen($v) && !empty($v)) {
					if (preg_match('~^([a-z0-9\(\)*_\.]+)[\s]+(asc|desc)$~ims', $v, $m)) {
						$a[] = $this->_escape_expr($m[1]).' '.strtoupper($m[2]);
					} else {
						$a[] = $this->_escape_expr($v).' ASC';
					}
				} elseif (is_array($v)) {
					foreach ((array)$v as $k2 => $v2) {
						if (!is_string($v2)) {
							continue;
						}
						$direction = 'ASC';
						$v2 = trim($v2);
						if (is_string($k2) && in_array(strtoupper($v2), array('ASC','DESC'))) {
							$direction = $v2;
							$v2 = trim($k2);
						}
						if ($v2) {
							$a[] = $this->_escape_expr($v2).' '.strtoupper($direction);
						}
					}
				} elseif (is_callable($items)) {
					$a[] = $v($items, $this);
				}
			}
			if ($a) {
				$sql = implode(', ', $a);
			}
		}
		if ($sql) {
			$this->_sql[__FUNCTION__][] = $sql;
		}
		return $this;
	}

	/**
	* Examples: limit(10), limit(10,100)
	*/
	function limit($count = 10, $offset = null) {
		if ($count) {
			$sql = $this->db->limit($count, $offset);
		}
		if ($sql) {
			$this->_sql[__FUNCTION__] = $sql;
		}
		return $this;
	}

	/**
	* Find primary key name
	*/
	public function _get_primary_key_column($table) {
		$primary = $this->db->utils()->index_info($table, 'PRIMARY');
		if ($primary) {
			return current($primary['columns']);
		}
		return false;
	}

	/**
	*/
	function _escape_expr($expr = '') {
		if ($expr === '*' || false !== strpos($expr, '(') || preg_match('~[^a-z0-9_\.]+~ims', $expr)) {
			return $expr;
		}
		return $this->_escape_col_name($expr);
	}

	/**
	*/
	function _escape_col_name($name = '') {
		$name = trim($name);
		if (!strlen($name)) {
			return false;
		}
		$db = '';
		$table = '';
		$col = '';
		if (strpos($name, '.') !== false) {
			$parts = explode('.', $name);
			$num_dots = substr_count($name, '.');
			if ($num_dots >= 2) {
				$db = trim($parts[0]);
				$table = trim($parts[1]);
				$col = trim($name[2]);
			} else {
				$table = trim($parts[0]);
				$col = trim($parts[1]);
			}
		} else {
			$col = $name;
		}
		if (!strlen($col)) {
			return false;
		}
		if (strlen($table) && (!strlen($db) || $db == $this->db->DB_NAME)) {
#			$table = $this->db->_real_name($table);
		}
		return (strlen($db) ? $this->_escape_key($db).'.' : '')
			. (strlen($table) ? $this->_escape_key($table).'.' : '')
			. $this->_escape_key($col)
		;
	}

	/**
	*/
	function _escape_table_name($name = '') {
		$name = trim($name);
		if (!strlen($name)) {
			return false;
		}
		$db = '';
		$table = '';
		if (strpos($name, '.') !== false) {
			$parts = explode('.', $name);
			$db = trim($parts[0]);
			$table = trim($parts[1]);
		} else {
			$table = $name;
		}
		if (!strlen($table)) {
			return false;
		}
		if (!strlen($db) || $db == $this->db->DB_NAME) {
			$table = $this->db->_real_name($table);
		}
		return (strlen($db) ? $this->_escape_key($db).'.' : ''). $this->_escape_key($table);
	}

	/**
	*/
	function _escape_key($key = '') {
		if ($key != '*' && false === strpos($key, '.') && false === strpos($key, '(')) {
			return $this->db->escape_key($key);
		}
		return $key;
	}

	/**
	*/
	function _escape_val($val = '') {
// TODO: support for binding params (':field' => $val)
		return $this->db->escape_val($val);
	}
}
