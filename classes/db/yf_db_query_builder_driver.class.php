<?php

/**
* Query builder (Active Record)
*/
abstract class yf_db_query_builder_driver {

	const REGEX_INLINE_CONDS = '~^([a-z0-9\(\)*_\.]+)[\s]*(=|!=|<>|<|>|>=|<=)[\s]*([a-z0-9_\.]+)$~ims';
	const REGEX_IS_NULL = '~^([a-z0-9\(\)*_\.]+)[\s]*(is[\s]+null|is[\s]+not[\s]+null)$~ims';
	const REGEX_AS = '~^([a-z0-9\(\)*_\.]+)[\s]+AS[\s]+([a-z0-9_]+)$~ims';
	const REGEX_ASC_DESC = '~^([a-z0-9\(\)*_\.]+)[\s]+(asc|desc)$~ims';

	/**
	* Catch missing method call
	*/
	public function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* We cleanup object properties when cloning
	*/
	public function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	* Need to avoid calling render() without params
	*/
	public function __toString() {
		return $this->render();
	}

	/**
	*/
	public function dump_json () {
		return json_encode($this->exec());
	}

	/**
	* Alias
	*/
	public function sql() {
		return $this->render();
	}

	/**
	* Create text SQL from params
	*/
	public function render() {
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
	* Return SELECT sql string
	*/
	public function _render_select() {
		return $this->_sql_part_to_array('select');
	}

	/**
	* Return FROM sql string
	*/
	public function _render_from() {
		return $this->_sql_part_to_array('from');
	}

	/**
	* Return JOINs sql string
	*/
	public function _render_joins() {
		$a = array();
		foreach (array('join','left_join','inner_join','right_join') as $name) {
			$a[$name] = $this->_sql_part_to_array($name);
			if (empty($a[$name])) {
				unset($a[$name]);
			}
		}
		return $a ? implode(' ', $a) : false;
	}

	/**
	* Return WHERE sql string
	*/
	public function _render_where() {
		$a = array();
		foreach (array('where','where_or') as $name) {
			$a[$name] = $this->_sql_part_to_array($name);
			if (empty($a[$name])) {
				unset($a[$name]);
			}
		}
		return $a ? implode(' ', $a) : false;
	}

	/**
	* Return ORDER BY sql string
	*/
	public function _render_order_by() {
		return $this->_sql_part_to_array('order_by');
	}

	/**
	* Return LIMIT sql string
	*/
	public function _render_limit() {
		return $this->_sql['limit'];
	}

	/**
	* Create overall SQL array parts in correct order
	*/
	public function _sql_to_array($return_raw = false) {
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
		// Ensuring strict order of parts of the generated SQL will be correct, no matter how functions were called
		foreach ($this->_get_sql_parts_config() as $name => $config) {
			if (empty($this->_sql[$name])) {
				continue;
			}
			$a[$name] = $this->_sql_part_to_array($name, $this->_sql[$name], $config, $return_raw);
		}
		return $a;
	}

	/**
	*/
	public function _get_sql_parts_config() {
		return array(
			'select'		=> array('separator' => ',', 'operator' => 'SELECT'),
			'from'			=> array('separator' => ',', 'operator' => 'FROM'),
			'join'			=> array('separator' => 'JOIN', 'operator' => 'JOIN'),
			'left_join'		=> array('separator' => 'LEFT JOIN', 'operator' => 'LEFT JOIN'),
			'inner_join'	=> array('separator' => 'INNER JOIN', 'operator' => 'INNER JOIN'),
			'right_join'	=> array('separator' => 'RIGHT JOIN', 'operator' => 'RIGHT JOIN'),
			'where'			=> array('separator' => 'AND', 'operator' => 'WHERE'),
			'where_or'		=> array('separator' => 'OR', 'operator' => 'OR'),
			'group_by'		=> array('separator' => ',', 'operator' => 'GROUP BY'),
			'having'		=> array('separator' => 'AND', 'operator' => 'HAVING'),
			'order_by'		=> array('separator' => ',', 'operator' => 'ORDER BY'),
			'limit'			=> array(/* 'operator' => 'LIMIT' */),
		);
	}

	/**
	*/
	public function _sql_part_to_array($part, $data = null, $config = null, $return_raw = false) {
		if (!$part) {
			return false;
		}
		$config = $config ?: $this->_get_sql_parts_config();
		$data = isset($data) ? $data : (isset($this->_sql[$part]) ? $this->_sql[$part] : null);
		if (!isset($data) || empty($data)) {
			return false;
		}
		$operator = $config['operator'];
		$out = array();
		if (is_array($data)) {
			if (!isset($config['separator'])) {
				return false;
			}
			if ($return_raw) {
				$out = array(
					'operator' => $operator,
					'separator' => $config['separator'],
					'condition' => $data,
				);
			} else {
				$out = $operator.' '.implode(' '.$config['separator'].' ', $data);
			}
		} else {
			if ($return_raw) {
				$out = array(
					'operator' => ($operator ? $operator.' ' : ''),
					'condition' => $data,
				);
			} else {
				$out = ($operator ? $operator.' ' : ''). $data;
			}
		}
		return $out;
	}

	/**
	* Execute generated query
	*/
	public function exec($as_sql = false) {
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
	* Counting number of records inside requested recordset
	*/
	public function count() {
		$this->_sql['select'] = 'COUNT(*)';
		return $this->get_one();
	}

	/**
	* Return first item from resultset
	*/
	public function first($use_cache = false) {
		if (is_object($this->get_model())) {
			return $this->order_by($this->get_key_name().' asc')->limit(1)->get($use_cache);
		} else {
			return $this->get($use_cache);
		}
	}

	/**
	* Return last item from resultset
	*/
	public function last($use_cache = false) {
		if (is_object($this->get_model())) {
			return $this->order_by($this->get_key_name().' desc')->limit(1)->get($use_cache);
		} else {
			$result = $this->get_all($use_cache);
			if (is_array($result) && count($result)) {
				return end($result);
			} else {
				return null;
			}
		}
	}

	/**
	* Render SQL and execute db->get()
	*/
	public function get($use_cache = false) {
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
	public function one($use_cache = false) {
		return $this->get_one($use_cache);
	}

	/**
	* Render SQL and execute db->get_one()
	*/
	public function get_one($use_cache = false) {
		$sql = $this->sql();
		if ($sql) {
			return $this->db->get_one($sql, $use_cache);
		}
		return false;
	}

	/**
	* Alias
	*/
	public function all($use_cache = false) {
		return $this->get_all($use_cache);
	}

	/**
	* Render SQL and execute db->get_all()
	*/
	public function get_all($use_cache = false) {
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
	public function get_2d($use_cache = false) {
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
	public function get_deep_array($levels = 1, $use_cache = false) {
		$sql = $this->sql();
		if ($sql) {
			return $this->db->get_deep_array($sql, $levels, $use_cache);
		}
		return false;
	}

	/**
	*/
	public function delete($as_sql = false) {
		$sql = false;
		if ($this->_remove_as_from_delete) {
			$table = $this->get_table();
			$this->_sql['from'] = $table ? array($this->_escape_table_name($table)) : false;
		}
		if (empty($this->_sql['from'])) {
			return false;
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
	public function insert(array $data, $params = array()) {
		if (empty($data)) {
			return false;
		}
		$a = array();
		if (empty($this->_sql['from'])) {
			return false;
		}
		$table = preg_replace('~[^a-z0-9_\s]~ims', '', $this->_sql['from'][0]);
		if (preg_match(self::REGEX_AS, $table, $m)) {
			$table = $m[1];
		}
		if (!$table) {
			return false;
		}
		$sql = $this->compile_insert($table, $data, $params);
		if (!empty($params['sql'])) {
			return $sql;
		}
		if ($sql) {
			$result = $this->db->query($sql);
			$insert_id = $result ? $this->db->insert_id() : false;
			return $insert_id ?: $result;
		}
		return false;
	}

	/**
	*/
	public function insert_into($table, array $fields = array(), $params = array()) {
// TODO: unit tests
// usage pattern: select('id, name')->from('table1')->where('age','>','30')->limit(50)->insert('table2')
// usage pattern: select('id, name')->from('table1')->where('age','>','30')->limit(50)->insert('table2', array('id' => '@id', 'name' => '@name'))
// Use for into_table here INSERT INTO ... SELECT .. FROM ...
		$data = $this->get_all();
		$first = reset($data);
		$sql = $this->compile_insert($table, $data, $params);
		if ($sql) {
			$result = $this->db->query($sql);
			$insert_id = $result ? $this->db->insert_id() : false;
			return $insert_id ?: $result;
		}
	}

	/**
	* Insert array of values into table
	*/
	public function compile_insert($table, $data, $params = array()) {
		if (!strlen($table) || !is_array($data)) {
			return false;
		}
		$replace = isset($params['replace']) ? $params['replace'] : false;
		$ignore = isset($params['ignore']) ? $params['ignore'] : false;
		$on_duplicate_key_update = isset($params['on_duplicate_key_update']) ? $params['on_duplicate_key_update'] : false;
		if (is_string($replace)) {
			$replace = false;
		}
		$escape = isset($params['escape']) ? (bool)$params['escape'] : true;
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
				if ($escape) {
					$_cur_values = $this->_escape_val($cur_values);
				} else {
					$_cur_values = $cur_values;
				}
				$values_array[] = '('.implode(', ', $_cur_values).PHP_EOL.')';
			}
		} elseif (count($data)) {
			$cols	= array_keys($data);
			$values = array_values($data);
			foreach ((array)$values as $k => $v) {
				if ($escape) {
					$_v = $this->_escape_val($v);
				} else {
					$_v = $v;
				}
				$values[$k] = $_v;
			}
			$values_array[] = '('.implode(', ', $values).PHP_EOL.')';
		}
		foreach ((array)$cols as $k => $v) {
			unset($cols[$k]);
			if ($escape) {
				$_v = $this->_escape_key($v);
			} else {
				$_v = $v;
			}
			$cols[$v] = $_v;
		}
		$sql = '';
		$primary_col = $this->get_key_name($table);
		if (count($cols) && count($values_array)) {
			$sql = ($replace ? 'REPLACE' : 'INSERT'). ($ignore ? ' IGNORE' : '')
				.' INTO '.$this->_escape_table_name($table).PHP_EOL
				.' ('.implode(', ', $cols).') VALUES '
				.PHP_EOL.implode(', ', $values_array);
			if ($on_duplicate_key_update) {
				$sql .= PHP_EOL.' ON DUPLICATE KEY UPDATE ';
				$tmp = array();
				foreach ((array)$cols as $col => $col_escaped) {
					if ($col == $primary_col) {
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
	public function update(array $data, $params = array()) {
// TODO: support for dataset params: select('id, name')->from('table1')->where('age','>','30')->limit(50)->update(array('id' => '@id', 'name' => '@name'), array('table' => 'table2'))
// TODO: where condition for update inside params
		$table = $params['table'] ?: $this->get_table();
		if (!$table) {
			return false;
		}
		if (empty($data)) {
			return false;
		}
		// 3-dimensional array detected
		if (is_array($data) && is_array(reset($data))) {
			return $this->update_batch($table);
		}
		$a = $this->_sql_to_array($return_raw = true);
		$where = '';
		if (isset($a['where'])) {
			$where = implode(' '.$a['where']['separator'].' ', $a['where']['condition']);
		}
		if (isset($a['where_or'])) {
			$where = rtrim($where).' '.$a['where_or']['operator'].' '.implode(' '.$a['where_or']['separator'].' ', $a['where_or']['condition']);
		}
		$sql = $this->compile_update($table, $data, $where, $params);
		if (!empty($params['sql'])) {
			return $sql;
		}
		if ($sql) {
			$result = $this->db->query($sql);
		}
		return $result;
	}

	/**
	* Update table with given values
	*/
	public function compile_update($table, array $data, $where, $params = array()) {
		if (empty($table) || empty($data) || empty($where)) {
			return false;
		}
		// $where contains numeric id
		if (is_numeric($where)) {
			$where = 'id='.intval($where);
		}
		$tmp_data = array();
		$escape = isset($params['escape']) ? (bool)$params['escape'] : true;
		foreach ((array)$data as $k => $v) {
			if (empty($k)) {
				continue;
			}
			if ($escape) {
				$_k = $this->_escape_key($k);
				$_v = $this->_escape_val($v);
			} else {
				$_k = $k;
				$_v = $v;
			}
			$tmp_data[$k] = $_k. ' = '. $_v;
		}
		$sql = '';
		if (count($tmp_data)) {
			$sql = 'UPDATE '.$this->_escape_table_name($table).' SET '.implode(', ', $tmp_data). (!empty($where) ? ' WHERE '.$where : '');
		}
		return $sql ?: false;
	}

	/**
	*/
	public function update_batch($table, array $data, $index = null, $only_sql = false, $params = array()) {
		if (!$index) {
			$index = $this->get_key_name($table);
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
	public function _set_update_batch_data($key, $index = '') {
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
	public function _get_update_batch_sql($table, $values, $index) {
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
	public function select() {
		$sql = '';
		$fields = func_get_args();
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
					if (preg_match(self::REGEX_AS, $v, $m)) {
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
							if (preg_match(self::REGEX_AS, $v2, $m)) {
								$a[] = $this->_escape_expr($m[1]).' AS '.$this->_escape_key($m[2]);
							} else {
								$a[] = $this->_escape_expr($k2).' AS '.$this->_escape_key($v2);
							}
						}
					}
				}
			}
			if ($a) {
// TODO: use smarter part from _process_where
				$sql = implode(', ', $a);
			}
		}
		if ($sql) {
			$this->_sql[__FUNCTION__][] = $sql;
		}
		return $this;
	}

	/**
	* Alias for "from"
	*/
	public function table() {
		return call_user_func_array(array($this, 'from'), func_get_args());
	}

	/**
	* Examples: from('users'), from(array('users' => 'u', 'suppliers' => 's'))
	*/
	public function from() {
// TODO: auto-joins if comma detected
		$sql = '';
		$tables = func_get_args();
		if (count($tables)) {
			$a = array();
			foreach ((array)$tables as $k => $v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				if (is_string($v) && strlen($v) && !empty($v)) {
					// support for syntax: from('users as u') from('users as u', 'messages as m')
					if (preg_match(self::REGEX_AS, $v, $m)) {
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
							if (preg_match(self::REGEX_AS, $v2, $m)) {
								$a[] = $this->_escape_table_name($m[1]).' AS '.$this->_escape_key($m[2]);
							} else {
								$a[] = $this->_escape_table_name($k2).' AS '.$this->_escape_key($v2);
							}
						}
					}
				}
			}
			if ($a) {
// TODO: use smarter part from _process_where
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
	public function _ids_sql_from_array(array $ids) {
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
	public function whereid($id, $pk = '') {
		!$pk && $pk = $this->get_key_name();
		$sql = '';
		if (is_array($id) && count($id) > 1) {
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
	public function where() {
		return $this->_process_where(func_get_args(), __FUNCTION__);
	}

	/**
	* Example: where_or(array('id','>','1'))
	*/
	public function where_or() {
		return $this->_process_where(func_get_args(), __FUNCTION__);
	}

	/**
	* Prepare WHERE statement
	*/
	public function _process_where(array $where, $func_name = 'where') {
		$sql = '';
		$count = count($where);
		if ($count && $this->_is_where_all_numeric($where)) {
			// where(array(1,2,3))
			if (count($where) === 1 && isset($where[0])) {
				$where = $where[0];
			}
			return $this->whereid($where);
		}
		if (($count === 3 || $count === 2) && is_string($where[0]) && (is_string($where[1]) || is_array($where[1]))) {
			$sql = $this->_process_where_cond($where[0], $where[1], $where[2]);
		} elseif ($count) {
			$a = array();
			foreach ((array)$where as $k => $v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				if (is_string($v) && strlen($v) && !empty($v)) {
					if (preg_match(self::REGEX_INLINE_CONDS, $v, $m)) {
						$a[] = $this->_process_where_cond($m[1], $m[2], $m[3]);
					} elseif (preg_match(self::REGEX_IS_NULL, $v, $m)) {
						$a[] = $this->_process_where_cond($m[1], $m[2], '');
					} else {
						$v = strtoupper(trim($v));
						if (in_array($v, array('AND','OR','XOR'))) {
							$a[] = $v;
						}
					}
				} elseif (is_array($v)) {
					$count_a = count($a);
					$need_and = false;
					if ($count_a && !in_array($a[$count_a - 1], array('AND','OR','XOR'))) {
						$need_and = true;
					}
					// array('field', 'condition', 'value'), example: array('id','>','1')
					$_count_v = count($v);
					if (($_count_v === 3 || $_count_v === 2) && isset($v[0])) {
						$tmp = $this->_process_where_cond($v[0], $v[1], $v[2]);
						if (!strlen($tmp)) {
							continue;
						}
						if ($need_and) {
							$a[] = 'AND';
						}
						$a[] = $tmp;
					// array('field1' => 'val1', 'field2' => 'val2')
					} else {
						$tmp = array();
						foreach ($v as $k2 => $v2) {
							$_tmp = $this->_process_where_cond($k2, '=', $v2);
							if ($_tmp) {
								$tmp[] = $_tmp;
							}
						}
						if (count($tmp)) {
							if ($need_and) {
								$a[] = 'AND';
							}
							foreach ($tmp as $k2 => $v2) {
								if ($k2 > 0) {
									$a[] = 'AND';
								}
								$a[] = $v2;
							}
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
		return $this;
	}

	/**
	* Detecting input consisting from numbers, think that this is "whereid" alias call like this: where(1), whereid(1,2,3)
	*/
	public function _is_where_all_numeric(&$where) {
		if (empty($where) || (!is_array($where) && !is_numeric($where))) {
			return false;
		}
		$count = count($where);
		if (!$count) {
			return false;
		}
		$self_func = __FUNCTION__;
		foreach ($where as $k => $v) {
			if (is_array($v)) {
				if (!$this->$self_func($where[$k])) {
					return false;
				}
				continue;
			}
			if (empty($v)) {
				unset($where[$k]);
			} elseif (!is_numeric($k) || !is_numeric($v)) {
				return false;
			}
		}
		return true;
	}

	/**
	*/
	public function _process_where_cond($left, $op, $right) {
		!$op && $op = '=';
		$left = trim(strtolower($left));
		if (is_string($op)) {
			$op = trim(strtolower($op));
		}
		$right_generated = '';
		// Think that we dealing with 2 arguments passing like this: where('id', 1)
		// Also this will match: where('id', array(1,2,3))
		if (strlen($left) && !empty($op) && !is_array($right) && !strlen($right)) {
			if (strlen($op) && !in_array($op, array('=','!=','<','>','<=','>=','like','not like','is null','is not null','in','not in'))) {
				$right = $op;
				$op = '=';
			} elseif (is_array($op) && $this->_is_where_all_numeric($op)) {
				$right = $op;
				$op = 'in';
			}
		}
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
	public function join($table, $on, $join_type = '') {
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
			if (preg_match(self::REGEX_AS, $table, $m)) {
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
			if (preg_match(self::REGEX_INLINE_CONDS, $on, $m)) {
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
	public function left_join($table, $on) {
		return $this->join($table, $on, 'left');
	}

	/**
	*/
	public function right_join($table, $on) {
		return $this->join($table, $on, 'right');
	}

	/**
	*/
	public function inner_join($table, $on) {
		return $this->join($table, $on, 'inner');
	}

	/**
	* Examples: group_by('user_group'), group_by(array('supplier','manufacturer'))
	*/
	public function group_by() {
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
// TODO: use smarter part from _process_where
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
	public function having() {
		$sql = '';
		$where = func_get_args();
		if (count($where)) {
			$a = array();
			foreach ((array)$where as $k => $v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				if (is_string($v) && strlen($v) && !empty($v)) {
					if (preg_match(self::REGEX_INLINE_CONDS, $v, $m)) {
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
// TODO: use smarter part from _process_where
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
	public function order_by() {
// TODO: support for order_by('field','asc')
// TODO: support for order_by(array('field1','asc'),array('field2','desc'),array('field3','asc'))
		$sql = '';
		$items = func_get_args();
		if (count($items)) {
			$a = array();
			foreach ((array)$items as $k => $v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				if (is_string($v) && strlen($v) && !empty($v)) {
					if (preg_match(self::REGEX_ASC_DESC, $v, $m)) {
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
// TODO: use smarter part from _process_where
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
	public function limit($count = 10, $offset = null) {
		if ($count) {
			$sql = $this->db->limit($count, $offset);
		}
		if ($sql) {
			$this->_sql[__FUNCTION__] = $sql;
		}
		return $this;
	}

	/**
	* Get current linked model
	*/
	public function get_model() {
		if (isset($this->_model) && is_object($this->_model) && ($this->_model instanceof yf_model)) {
			return $this->_model;
		}
		return false;
	}

	/**
	* Find primary key name
	*/
	public function get_key_name($table = '') {
		$pk = '';
		if (strlen($table)) {
			$utils = $this->db->utils();
			if ($utils->table_exists($table)) {
				$primary_index = $utils->index_info($table, 'PRIMARY');
				if (isset($primary_index['columns'])) {
					$pk = current($primary_index['columns']);
				}
			}
		} elseif ($model = $this->get_model()) {
			$pk = $model->get_key_name();
		}
		return $pk ?: 'id';
	}

	/**
	* Return first table used
	*/
	public function get_table() {
		if (empty($this->_sql['from'])) {
			return false;
		}
		$table = preg_replace('~[^a-z0-9_\s]~ims', '', $this->_sql['from'][0]);
		if (preg_match(self::REGEX_AS, $table, $m)) {
			$table = $m[1];
		}
		return $table;
	}

	/**
	*/
	public function _escape_expr($expr = '') {
		if ($expr === '*' || false !== strpos($expr, '(') || preg_match('~[^a-z0-9_\.]+~ims', $expr)) {
			return $expr;
		}
		return $this->_escape_col_name($expr);
	}

	/**
	*/
	public function _escape_col_name($name = '') {
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
	public function _escape_table_name($name = '') {
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
	public function _escape_key($key = '') {
		if ($key != '*' && false === strpos($key, '.') && false === strpos($key, '(')) {
			return $this->db->escape_key($key);
		}
		return $key;
	}

	/**
	*/
	public function _escape_val($val = '') {
// TODO: support for binding params (':field' => $val)
		return $this->db->escape_val($val);
	}
}
