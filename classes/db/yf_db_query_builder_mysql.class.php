<?php

/**
* Query builder (Active Record) for mysql
*/
load('db_query_builder_driver', 'framework', 'classes/db/');
class yf_db_query_builder_mysql extends yf_db_query_builder_driver {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
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
	function dump_sql () {
// TODO
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
		$a = array();
		// Save 1 call of select()
		if (empty($this->_sql['select']) && !empty($this->_sql['from'])) {
			$this->select();
		}
		if (empty($this->_sql['select']) || empty($this->_sql['from'])) {
			return false;
		}
		// HAVING without GROUP BY makes no sense
		if (!empty($this->_sql['having']) && empty($this->_sql['group_by'])) {
			unset($this->_sql['having']);
		}
		$separators = array(
			'select'		=> ',',
			'from'			=> ',',
			'join'			=> '',
			'where'			=> 'AND',
			'where_or'		=> 'OR',
			'group_by'		=> ',',
			'having'		=> ',',
			'order_by'		=> ',',
		);
		// Ensuring strict order of parts of the generated SQL will be correct, no matter how functions were called
		foreach (array('select','from','join','where','where_or','group_by','having','order_by','limit') as $name) {
			if (empty($this->_sql[$name])) {
				continue;
			}
			if (is_array($this->_sql[$name])) {
				if (isset($separators[$name])) {
					$a[] = implode(' '.$separators[$name].' ', $this->_sql[$name]);
				}
			} else {
				$a[] = $this->_sql[$name];
			}
		}
		if ($a) {
			$sql = implode(' ', $a);
		}
		if (empty($sql)) {
			return false;
		}
		return $sql;
	}

	/**
	* Execute generated query
	*/
	function exec($as_sql = true) {
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
	* Render SQL and execute db->get()
	*/
	function get($use_cache = true) {
		$sql = $this->sql();
		if ($sql) {
			return $this->db->get($sql, $use_cache);
		}
		return false;
	}

	/**
	*/
	function delete() {
// TODO
	}

	/**
	*/
	function insert($data) {
// TODO
	}

	/**
	*/
	function update($data) {
// TODO
	}

	/**
	* Render SQL and execute db->get_one()
	*/
	function get_one($use_cache = true) {
		$sql = $this->sql();
		if ($sql) {
			return $this->db->get_one($sql, $use_cache);
		}
		return false;
	}

	/**
	* Render SQL and execute db->get_all()
	*/
	function get_all($use_cache = true) {
		$sql = $this->sql();
		if ($sql) {
			return $this->db->get_all($sql, $use_cache);
		}
		return false;
	}

	/**
	* Render SQL and execute db->get_2d()
	*/
	function get_2d($use_cache = true) {
		$sql = $this->sql();
		if ($sql) {
			return $this->db->get_2d($sql, $use_cache);
		}
		return false;
	}

	/**
	* Render SQL and execute db->get_deep_array()
	*/
	function get_deep_array($levels = 1, $use_cache = true) {
		$sql = $this->sql();
		if ($sql) {
			return $this->db->get_deep_array($sql, $levels, $use_cache);
		}
		return false;
	}

// TODO: correct fields escaping
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
// TODO: add support for syntax: select('a.id')  select('a.id as aid')
	function select() {
		$sql = '';
		$fields = func_get_args();
		if (isset($fields[0]) && is_array($fields[0]) && isset($fields[0]['__args__'])) {
			$fields = $fields[0]['__args__'];
		}
		if (!count($fields) || $fields === array(array())) {
			$sql = 'SELECT *';
		} else {
			$a = array();
			foreach ((array)$fields as $k => $v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				if (is_string($v) && strlen($v) && !empty($v)) {
					$v = trim($v);
					$a[] = $this->_escape_key($v);
				} elseif (is_callable($v)) {
					$a[] = $v($fields, $this);
				} elseif (is_array($v)) {
					foreach ((array)$v as $k2 => $v2) {
						if (!is_string($k2) || !is_string($v2)) {
							continue;
						}
						$k2 = trim($k2);
						$v2 = trim($v2);
						if (strlen($k2) && strlen($v2)) {
							$a[] = $this->_escape_key($k2).' AS '.$this->_escape_key($v2);
						}
					}
				}
			}
			if ($a) {
				$sql = 'SELECT '.implode(', ', $a);
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
// TODO: add support for syntax: from('users as u') from('users as u, messages as m')
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
					$v = trim($v);
					$a[] = $this->_escape_key($this->db->_real_name($v));
				} elseif (is_callable($v)) {
					$a[] = $v($tables, $this);
				} elseif (is_array($v)) {
					foreach ((array)$v as $k2 => $v2) {
						if (!is_string($k2) || !is_string($v2)) {
							continue;
						}
						$k2 = trim($k2);
						$v2 = trim($v2);
						if (strlen($k2) && strlen($v2)) {
							$a[] = $this->_escape_key($this->db->_real_name($k2)).' AS '.$this->_escape_key($v2);
						}
					}
				}
			}
			if ($a) {
				$sql = 'FROM '.implode(', ', $a);
			}
		}
		if ($sql) {
			$this->_sql[__FUNCTION__][] = $sql;
		}
		return $this;
	}

	/**
	* Examples: join('suppliers', array('u.supplier_id' => 's.id'))
	*/
// TODO: add support for syntax: join('users as u')
// TODO: add support for simpler join type: left|right|inner
	function join($table, $on, $join_type = 'JOIN') {
		if (!$join_type) {
			$join_type = 'JOIN';
		}
		$_on = array();
		if (is_array($on)) {
			foreach ((array)$on as $k => $v) {
				list($t1_as, $t1_field) = explode('.', $k);
				list($t2_as, $t2_field) = explode('.', $v);
				$_on[] = $this->_escape_key($t1_as).'.'.$this->_escape_key($t1_field).' = '.$this->_escape_key($t2_as).'.'.$this->_escape_key($t2_field);
			}
		} elseif (is_callable($on)) {
			$_on = $on($table, $this);
		}
		$as = '';
		if (is_array($table)) {
			$as = current($table);
			$table = key($table);
		}
		$sql = '';
		if (is_string($table) && !empty($_on)) {
			$sql = strtoupper($join_type).' '.$this->_escape_key($this->db->_real_name($table)). ($as ? ' AS '.$this->_escape_key($as) : '').' ON '.implode(',', $_on);
		}
		if ($sql) {
			$this->_sql[__FUNCTION__][] = $sql;
		}
		return $this;
	}

	/**
	*/
	function left_join($table, $on) {
		return $this->join($table, $on, 'LEFT JOIN');
	}

	/**
	*/
	function right_join($table, $on) {
		return $this->join($table, $on, 'RIGHT JOIN');
	}

	/**
	*/
	function inner_join($table, $on) {
		return $this->join($table, $on, 'INNER JOIN');
	}

	/**
	* Example: whereid(1)
	*/
// TODO: unit tests for it
	function whereid($id) {
		$sql = '';
		if (is_array($id)) {
			$ids = array();
			foreach ((array)$id as $v) {
				$v = intval($v);
				$v && $ids[$v] = $v;
			}
			if ($ids) {
				$sql = 'WHERE id IN('.implode(',', $ids).')';
			}
		} elseif (is_callable($id)) {
			$sql = 'WHERE '.$id();
		} else {
			$sql = 'WHERE id = '.intval($id);
		}
		if ($sql) {
			$this->_sql['where'][] = $sql;
		}
		return $this;
	}

	/**
	* Example: where(array('id','>','1'),'and',array('name','!=','peter'))
	*/
// TODO: add support for syntax: where('u.id', 1)  where('u.id = 1')  where('u.id > 1')
// TODO: support for binding params (':field' => $val)
	function where() {
		$this->_process_where(func_get_args(), __FUNCTION__);
		return $this;
	}

	/**
	* Example: where_or(array('id','>','1'))
	*/
// TODO: add support for syntax: where_or('u.id', 1)  where_or('u.id = 1')  where_or('u.id > 1')
// TODO: support for binding params (':field' => $val)
// TODO: unit tests for it
	function where_or() {
		$this->_process_where(func_get_args(), __FUNCTION__);
		return $this;
	}

	/**
	*/
	function _process_where(array $where, $func_name = 'where') {
// TODO: add support for syntax: where('u.id', 1)  where('u.id = 1')  where('u.id > 1')
// TODO: support for binding params (':field' => $val)
		$sql = '';
		if (isset($where[0]) && is_array($where[0]) && isset($where[0]['__args__'])) {
			$where = $where[0]['__args__'];
		}
		if (count($where) == 3 && is_string($where[0]) && is_string($where[1])) {
			$sql = 'WHERE '.$this->_escape_key($where[0]). $where[1]. $this->_escape_val($where[2]);
		} elseif (count($where)) {
			$a = array();
			foreach ((array)$where as $k => $v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				if (is_string($v) && strlen($v) && !empty($v)) {
					$v = strtoupper(trim($v));
					if (in_array($v, array('AND','OR','XOR'))) {
						$a[] = $v;
					}
				// array('field', 'condition', 'value'), example: array('id','>','1')
				} elseif (is_array($v) && count($v) == 3) {
					$a[] = $this->_escape_key($v[0]). $v[1]. $this->_escape_val($v[2]);
				} elseif (is_callable($v)) {
					$a[] = $v($where, $this);
				}
			}
			if ($a) {
				$sql = 'WHERE '.implode(' ', $a);
			}
		}
		if ($sql) {
			$this->_sql[$func_name][] = $sql;
		}
	}

	/**
	* Examples: group_by('user_group'), group_by(array('supplier','manufacturer'))
	*/
// TODO: add support for syntax: group_by('user_group')  group_by('u.id', 'm.id')
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
					$a[] = $this->_escape_key($v);
				} elseif (is_array($v)) {
					foreach ((array)$v as $v2) {
						if (!is_string($v2)) {
							continue;
						}
						$v2 = trim($v2);
						if ($v2) {
							$a[] = $this->_escape_key($v2);
						}
					}
				} elseif (is_callable($v)) {
					$a[] = $v($items, $this);
				}
			}
			if ($a) {
				$sql = 'GROUP BY '.implode(', ', $a);
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
// TODO: add support for syntax: having('count(*) > 1') having('count(id) > 1', 'count(num) > 2')
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
					$v = strtoupper(trim($v));
					if (in_array($v, array('AND','OR','XOR'))) {
						$a[] = $v;
					}
				// array('field', 'condition', 'value'), example: array('id','>','1')
				} elseif (is_array($v) && count($v) == 3) {
					$a[] = $this->_escape_key($v[0]). $v[1]. $this->_escape_val($v[2]);
				} elseif (is_callable($v)) {
					$a[] = $v($where, $this);
				}
			}
			if ($a) {
				$sql = 'HAVING '.implode(' ', $a);
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
// TODO: add support for syntax: order_by('user_group') order_by('user_group asc') order_by('user_group asc', 'u.id desc')
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
					$a[] = $this->_escape_key($v).' ASC';
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
							$a[] = $this->_escape_key($v2).' '.strtoupper($direction);
						}
					}
				} elseif (is_callable($items)) {
					$a[] = $v($items, $this);
				}
			}
			if ($a) {
				$sql = 'ORDER BY '.implode(', ', $a);
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
	*/
	function _escape_key($key = '') {
		$out = '';
		if ($key != '*' && false === strpos($key, '.') && false === strpos($key, '(')) {
			$out = $this->db->escape_key($key);
		} else {
// TODO: split by "." and escape each value
			$out = $key;
		}
		return $out;
	}

	/**
	*/
	function _escape_val($val = '') {
		return $this->db->escape_val($val);
	}
}
