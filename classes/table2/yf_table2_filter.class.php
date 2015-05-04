<?php

/**
* Table2 plugin
*/
class yf_table2_filter {

	/**
	*/
	function _init() {
		$this->supported_conds = array(
			'in'		=> function($a){ return ' IN( '._es($a['value']).')'; }, // "equal"
			'eq'		=> function($a){ return ' = "'._es($a['value']).'"'; }, // "equal"
			'ne'		=> function($a){ return ' != "'._es($a['value']).'"'; }, // "not equal"
			'gt'		=> function($a){ return ' > "'._es($a['value']).'"'; }, // "greater than",
			'gte'		=> function($a){ return ' >= "'._es($a['value']).'"'; }, // "greater or equal than",
			'lt'		=> function($a){ return ' < "'._es($a['value']).'"'; }, // "less than",
			'lte'		=> function($a){ return ' <= "'._es($a['value']).'"'; }, // "lower or equal than"
			'like'		=> function($a){ return ' LIKE "%'._es($a['value']).'%"'; }, // LIKE '%'.$value.'%'
			'rlike'		=> function($a){ return ' RLIKE "'._es($a['value']).'"'; }, // regular expression, RLIKE $value
			'dt_eq'		=> function($a){ return ' = "'._es(strtotime($a['value'])).'"'; }, // "equal"
			'dt_ne'		=> function($a){ return ' != "'._es(strtotime($a['value'])).'"'; }, // "not equal"
			'dt_gt'		=> function($a){ return ' > "'._es(strtotime($a['value'])).'"'; }, // "greater than",
			'dt_gte'	=> function($a){ return ' >= "'._es(strtotime($a['value'])).'"'; }, // "greater or equal than",
			'dt_lt'		=> function($a){ return ' < "'._es(strtotime($a['value'])).'"'; }, // "less than",
			'dt_lte'	=> function($a){ return ' <= "'._es(strtotime($a['value'])).'"'; }, // "lower or equal than"
			'between'	=> function($a){
				$from = trim($a['value']);
				$to = trim($a['and']);
				if ($from && $to) {
					if ($from > $to) {
						$tmp = $from;
						$from = $to;
						$to = $tmp;
					}
					$result = sprintf(' BETWEEN "%s" AND "%s"', $from, $to);
				} elseif ($from) {
					$result = sprintf(' >= "%s"', $from);
				} elseif ($to) {
					$result = sprintf(' <= "%s"', $to);
				}
				return $result;
			},
			'dt_between' => function($a){
				$from = trim($a['value']);
				$to = trim($a['and']);
				!empty($from) && $from = strtotime($from);
				!empty($to) && $to = strtotime($to);
				if ($from && $to) {
					if ($from > $to) {
						$tmp = $from;
						$from = $to;
						$to = $tmp;
					}
					$result = sprintf(' BETWEEN "%s" AND "%s"', $from, $to);
				} elseif ($from) {
					$result = sprintf(' >= "%s"', $from);
				} elseif ($to) {
					$result = sprintf(' <= "%s"', $to);
				}
				return $result;
			},
			'datetime_between' => function($a){
				$from = trim($a['value']);
				$to = trim($a['and']);
				$format = 'Y-m-d H-i-s';
				!empty($from) && $from = date($format, strtotime($from));
				!empty($to) && $to = date($format, strtotime($to));
				if ($from && $to) {
					if ($from > $to) {
						$tmp = $from;
						$from = $to;
						$to = $tmp;
					}
					$result = sprintf(' BETWEEN "%s" AND "%s"', $from, $to);
				} elseif ($from) {
					$result = sprintf(' >= "%s"', $from);
				} elseif ($to) {
					$result = sprintf(' <= "%s"', $to);
				}
				return $result;
			},
			'daterange_between' => function($a){
				$value = $a['value'];
				if (!$value || false === strpos($value, '-')) {
					return false;
				}
				$only_date_regex = '~^[0-9]{2,4}[\.-][0-9]{2,4}[\.-][0-9]{2,4}$~';
				list($from, $to) = explode('-', $value);
				$from = trim($from);
				if (preg_match($only_date_regex, $from)) {
					$from .= ' 00:00:00';
				}
				$to = trim($to);
				if (preg_match($only_date_regex, $to)) {
					$to .= ' 23:59:59';
				}
				!empty($from) && $from = strtotime($from);
				!empty($to) && $to = strtotime($to);
				if ($from && $to) {
					if ($from > $to) {
						$tmp = $from;
						$from = $to;
						$to = $tmp;
					}
					$result = sprintf(' BETWEEN "%s" AND "%s"', $from, $to);
				} elseif ($from) {
					$result = sprintf(' >= "%s"', $from);
				} elseif ($to) {
					$result = sprintf(' <= "%s"', $to);
				}
				return $result;
			},
			'daterange_dt_between' => function($a){
				$value = $a['value'];
				if (!$value || false === strpos($value, '-')) {
					return false;
				}
				$only_date_regex = '~^[0-9]{2,4}[\.-][0-9]{2,4}[\.-][0-9]{2,4}$~';
				list($from, $to) = explode('-', $value);
				$from = trim($from);
				if (preg_match($only_date_regex, $from)) {
					$from .= ' 00:00:00';
				}
				$to = trim($to);
				if (preg_match($only_date_regex, $to)) {
					$to .= ' 23:59:59';
				}
				$format = 'Y-m-d H-i-s';
				!empty($from) && $from = date($format, strtotime($from));
				!empty($to) && $to = date($format, strtotime($to));
				if ($from && $to) {
					if ($from > $to) {
						$tmp = $from;
						$from = $to;
						$to = $tmp;
					}
					$result = sprintf(' BETWEEN "%s" AND "%s"', $from, $to);
				} elseif ($from) {
					$result = sprintf(' >= "%s"', $from);
				} elseif ($to) {
					$result = sprintf(' <= "%s"', $to);
				}
				return $result;
			},
		);
	}

	/**
	*/
	function _filter_sql_prepare($filter_data = array(), $filter_params = array(), $__sql = '', $table = null) {
		if (!$filter_data) {
			return '';
		}
		$db = is_object($table->_params['db']) ? $table->_params['db'] : db();
		$special_fields = array(
			'order_by',
			'order_direction',
		);
		$supported_conds = &$this->supported_conds;
		foreach((array)$filter_data as $k => $v) {
			if (!strlen($k)) {
				continue;
			}
			if (in_array($k, $special_fields)) {
				continue;
			}
			// Special field for BETWEEN second value
			if (substr($k, -strlen('__and')) == '__and') {
				$k = substr($k, 0, -strlen('__and'));
				$from = $filter_data[ $k ];
				if( $from ) { continue; }
				$v = ' ';
			}
			$field = $k;
			$left_part = '';
			$part_on_the_right = '';
			// Here we support complex filtering conditions, examples:
			// 'price' => array('gt', 'value' => '100')
			// 'price' => array('between', 'value' => '1', 'and' => '10')
			// 'name' => array('like', 'value' => 'john')
			if (is_array($v)) {
				$cond = isset($v[0]) ? $v[0] : $v['cond'];
				if (!$cond) {
					continue;
				}
				if (!isset($supported_conds[$cond])) {
					continue;
				}
				if (!isset($v['and'])) {
					$v['and'] == $filter_data[$k.'__and'];
				}
				if ($v['field']) {
					$field = $v['field'];
				}
				$part_on_the_right = $supported_conds[$cond]($v);
			} else {
				if (!strlen($v)) {
					continue;
				}
				$cond = 'eq';
				$func = null;
				// Here we can override default 'eq' condition with custom one by passing filter $params like this: table($sql, array('filter_params' => $filter_params)).
				$field_params = $filter_params[$k];
				if ($field_params) {
					// Fully replacing left and right parts with callback function
					// Example: table($sql, array('filter_params' => array('my_field' => function($a){ return ' v.value LIKE "%'._es($a['value']).'%" '; } )))
					if (is_callable($field_params)) {
						$left_part = ' ';
						$func = $field_params;
					// Ways of passing array of params: 1) long and 2) short
					// Example: table($sql, array('filter_params' => array('value'	=> array('cond' => 'like', 'field' => 'v.value'))))
					// Example: table($sql, array('filter_params' => array('translation' => array('like', 't.value'))))
					} elseif (is_array($field_params)) {
						$cond = isset($field_params['cond']) ? $field_params['cond'] : $field_params[0];
						if (!$cond) {
							$cond = 'eq';
						}
						$func = $supported_conds[$cond];
						if ($field_params['field']) {
							$field = $field_params['field'];
						} elseif (isset($field_params[1])) {
							$field = $field_params[1];
						}
					// Predefined condition found (gt, between, like, etc..)
					// Example: table($sql, array('filter_params' => array('name' => 'gt', 'price' => 'between')))
					} elseif (isset($supported_conds[$field_params])) {
						$cond = $field_params;
						$func = $supported_conds[$cond];
					}
				} else {
					$func = $supported_conds[$cond];
				}
				// Field with __and on the end of its name is special one for 'between' condition
				if ($func) {
					$part_on_the_right = $func(array('value' => $v, 'and' => $filter_data[$k.'__and']), $filter_data);
				}
			}
			if (!strlen($left_part)) {
				$left_part = '`'.str_replace('.', '`.`', $db->es($field)).'`';
			}
			if ($part_on_the_right) {
				$sql[] = trim($left_part). ' '. trim($part_on_the_right);
			}
		}
		if ($sql) {
			$filter_sql = ' AND '.implode(' AND ', $sql);
		}
		if (isset($filter_data['order_by']) && $filter_data['order_by'] && strpos(strtoupper($__sql), 'ORDER BY') === false) {
			$order_by_field = $filter_data['order_by'];
			if (is_array($filter_params[$order_by_field])) {
				$field_params = $filter_params[$order_by_field];
				if ($field_params['field']) {
					$order_by_field = $field_params['field'];
				} elseif (isset($field_params[1])) {
					$order_by_field = $field_params[1];
				}
			}
			$order_sql = ' ORDER BY `'.str_replace('.', '`.`', $db->es($order_by_field)).'` ';
			if ($filter_data['order_direction']) {
				$direction = strtoupper($filter_data['order_direction']);
			}
			if ($direction && in_array($direction, array('ASC','DESC'))) {
				$order_sql .= ' '.$direction;
			}
		}
		return array($filter_sql, $order_sql);
	}

	/**
	* Simple filtering of the given array. Need to support table() raw array data with filtering
	*/
	function _filter_array(&$data, $filter = array(), $filter_params = array(), $table = null) {
		if (!$data || !$filter) {
			return false;
		}
		foreach ((array)$data as $_id => $_data) {
			foreach ((array)$filter as $fk => $fv) {
				if (isset($_data[$fk]) && strlen($fv)) {
					if (is_array($_data[$fk])) {
						if (isset($filter_params[$fk])) {
							$fp = $filter_params[$fk];
							foreach ((array)$_data[$fk] as $k2 => $v2) {
								if ($fp == 'like') {
									if (false === strpos($_data[$fk][$k2], $fv)) {
										unset($data[$_id]);
										continue 3;
									}
								} elseif ($fp == 'eq') {
									if ($_data[$fk][$k2] != $fv) {
										unset($data[$_id]);
										continue 3;
									}
								} elseif ($fp == 'ne') {
									if ($_data[$fk][$k2] == $fv) {
										unset($data[$_id]);
										continue 3;
									}
								} elseif ($fp == 'gt') {
									if ($_data[$fk][$k2] <= $fv) {
										unset($data[$_id]);
										continue 3;
									}
								} elseif ($fp == 'gte') {
									if ($_data[$fk][$k2] < $fv) {
										unset($data[$_id]);
										continue 3;
									}
								} elseif ($fp == 'lt') {
									if ($_data[$fk][$k2] >= $fv) {
										unset($data[$_id]);
										continue 3;
									}
								} elseif ($fp == 'lte') {
									if ($_data[$fk][$k2] > $fv) {
										unset($data[$_id]);
										continue 3;
									}
								} elseif ($fp == 'rlike') {
// TODO
								} elseif ($fp == 'between') {
// TODO
								}
							}
						} elseif (!isset($_data[$fk][$fv])) {
							unset($data[$_id]);
							continue 2;
						}
					} else {
						if (isset($filter_params[$fk])) {
							$fp = $filter_params[$fk];
							if ($fp == 'like') {
								if (false === strpos($_data[$fk], $fv)) {
									unset($data[$_id]);
									continue 2;
								}
							} elseif ($fp == 'eq') {
								if ($_data[$fk] != $fv) {
									unset($data[$_id]);
									continue 2;
								}
							} elseif ($fp == 'ne') {
								if ($_data[$fk] == $fv) {
									unset($data[$_id]);
									continue 2;
								}
							} elseif ($fp == 'gt') {
								if ($_data[$fk] <= $fv) {
									unset($data[$_id]);
									continue 2;
								}
							} elseif ($fp == 'gte') {
								if ($_data[$fk] < $fv) {
									unset($data[$_id]);
									continue 2;
								}
							} elseif ($fp == 'lt') {
								if ($_data[$fk] >= $fv) {
									unset($data[$_id]);
									continue 2;
								}
							} elseif ($fp == 'lte') {
								if ($_data[$fk] > $fv) {
									unset($data[$_id]);
									continue 2;
								}
							} elseif ($fp == 'rlike') {
// TODO
							} elseif ($fp == 'between') {
// TODO
							}
						} elseif ($_data[$fk] != $fv) {
							unset($data[$_id]);
							continue 2;
						}
					}
				}
			}
		}
	}
}
