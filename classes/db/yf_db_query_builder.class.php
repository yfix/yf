<?php

/**
 */
class yf_db_query_builder {

	/**
	*/
	function _init () {
		$this->db = _class('db');
	}

	/**
	* Part of query-generation chain
	* Examples:
	*	db()
	*	->select(array('id','name'))
	*	->from('users','u')
	*	->inner_join('groups','g',array('u.group_id'=>'g.id'))
	*	->order_by('add_date')
	*	->group_by('id')
	*	->limit(10)
	*/
	function select($fields = array()) {
		if (is_array($fields)) {
			$sql = 'SELECT '.implode(',', $fields);
		} elseif (empty($fields) || $fields == '*') {
			$sql = 'SELECT *';
		}
		$this->_sql[__FUNCTION__] = $sql;
		return $this;
	}

	/**
	* Part of query-generation chain
	* Examples: from('users'), from('users', 'u'), from(array('users' => 'u', 'suppliers' => 's'))
	*/
	function from($table, $as = '') {
		$tt = array();
		if (is_array($table)) {
			foreach ((array)$table as $t => $_as) {
				$tt[] = $this->_real_name($t). ($_as ? ' AS '.$this->db->enclose_field_name($_as) : '');
			}
		} else {
			$tt[] = $this->_real_name($table). ($as ? ' AS '.$this->db->enclose_field_name($as) : '');
		}
		$sql = 'FROM '.implode(',', $tt);
		$this->_sql[__FUNCTION__] = $sql;
		return $this;
	}

	/**
	* Part of query-generation chain
	* Examples: join('suppliers', array('u.supplier_id' => 's.id'))
	*/
	function join($table, $as, $items, $join_type = 'JOIN') {
		$on = array();
		foreach ((array)$items as $k => $v) {
			list($t1_as, $t1_field) = explode('.', $k);
			list($t2_as, $t2_field) = explode('.', $v);
			$on[] = $this->db->enclose_field_name($t1_as).'.'.$this->db->enclose_field_name($t1_field).' = '.$this->db->enclose_field_name($t2_as).'.'.$this->db->enclose_field_name($t2_field);
		}
		$sql = $join_type.' '.$this->_real_name($table).' AS '.$this->db->enclose_field_name($as).' ON '.implode(',', $on);
		$this->_sql[__FUNCTION__] = $sql;
		return $this;
	}

	/**
	* Part of query-generation chain
	*/
	function left_join($table, $as, $items) {
		return $this->join($table, $as, $items, 'LEFT JOIN');
	}

	/**
	* Part of query-generation chain
	*/
	function right_join($table, $as, $items) {
		return $this->join($table, $as, $items, 'RIGHT JOIN');
	}

	/**
	* Part of query-generation chain
	*/
	function inner_join($table, $as, $items) {
		return $this->join($table, $as, $items, 'INNER JOIN');
	}

	/**
	* Part of query-generation chain
	* Example: where(array('id','>','1'),array('name','!=','peter'))
	*/
	function where($items) {
		$where = array();
		foreach ((array)$items as $v) {
			$where[] = $this->db->enclose_field_name($v[0]). $v[1]. $this->db->enclose_field_value($v[2]);
		}
		$sql = 'WHERE '.implode(' AND ', $where);
		$this->_sql[__FUNCTION__] = $sql;
		return $this;
	}

	/**
	* Part of query-generation chain
	* Examples: group_by('user_group'), group_by(array('supplier','manufacturer'))
	*/
	function group_by($items) {
		if (is_array($items)) {
			$by = array();
			foreach ((array)$items as $v) {
				$by[] = $this->db->enclose_field_name($v);
			}
		} else {
			$by = array($this->db->enclose_field_name($items));
		}
		$sql = 'GROUP BY '.implode(',', $by);
		$this->_sql[__FUNCTION__] = $sql;
		return $this;
	}

	/**
	* Part of query-generation chain
	* Examples: order_by('user_group'), order_by(array('supplier','manufacturer'))
	*/
	function order_by($items) {
		if (is_array($items)) {
			$by = array();
			foreach ((array)$items as $v) {
				$by[] = $this->db->enclose_field_name($v);
			}
		} else {
			$by = array($this->db->enclose_field_name($items));
		}
		$sql = 'ORDER BY '.implode(',', $by);
		$this->_sql[__FUNCTION__] = $sql;
		return $this;
	}

	/**
	* Part of query-generation chain
	* Examples: having(array('COUNT(*)','>','1'))
	*/
	function having($items) {
		$where = array();
		foreach ((array)$items as $v) {
			$where[] = $this->db->enclose_field_name($v[0]). $v[1]. $this->db->enclose_field_value($v[2]);
		}
		$sql = 'HAVING '.implode(' AND ', $where);
		$this->_sql[__FUNCTION__] = $sql;
		return $this;
	}

	/**
	* Part of query-generation chain
	* Examples: limit(10), limit(10,100)
	*/
	function limit($count, $offset = null) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		if (!is_object($this->db)) {
			return false;
		}
		$sql = $this->db->limit($count, $offset);
		$this->_sql[__FUNCTION__] = $sql;
		return $this;
	}

	/**
	* Execute generated query
	*/
	function exec($as_sql = true) {
		$a = array();
		// Ensuring strict order of parts of the generated SQL will be correct, no matter how functions were called
		foreach (array('select','from','join','left_join','right_join','inner_join','where','group_by','having','order_by','limit') as $name) {
			if ($this->_sql[$name]) {
				$a[] = $this->_sql[$name];
			}
		}
		$sql = implode(' ', $a);
		if (empty($sql)) {
			return false;
		}
		if ($as_sql) {
			return $sql;
		}
#		return $this->db->query($sql);
	}
}
