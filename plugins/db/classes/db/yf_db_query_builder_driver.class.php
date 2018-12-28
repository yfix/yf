<?php

/**
 * Database query builder (Active Record).
 *
 * Examples:
 *	db()->from('users')->get()
 *	db()->from('users')->get_all()
 *	db()->select('id,name')->from('users')->get_2d()
 *	db()->select('id,name')->from('users as u')->inner_join('groups as g', 'u.group_id = g.id')->order_by('add_date')->group_by('id')->limit(10)
 */
abstract class yf_db_query_builder_driver
{
    const REGEX_INLINE_CONDS = '~^([a-z0-9\(\)*_\.]+)[\s]*(=|!=|<>|<|>|>=|<=)[\s]*([a-z0-9_\.]+)$~ims';
    const REGEX_IS_NULL = '~^([a-z0-9\(\)*_\.]+)[\s]*(is[\s]+null|is[\s]+not[\s]+null)$~ims';
    const REGEX_AS = '~^([a-z0-9\(\)*_\.]+)[\s]+AS[\s]+([a-z0-9_]+)$~ims';
    const REGEX_ASC_DESC = '~^([a-z0-9\(\)*_\.]+)[\s]+(asc|desc)$~ims';
    const REGEX_TABLE_NAME_CLEANUP = '~[^a-z0-9_\.\s]~ims';

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        // keep compatibility with php5.6, where word "clone" is reserved in class methods
        if ($name == 'clone') {
            return call_user_func_array([$this, '_clone'], $args);
        }
        return main()->extend_call($this, $name, $args);
    }

    /**
     * We cleanup object properties when cloning.
     */
    public function __clone()
    {
        foreach ((array) get_object_vars($this) as $k => $v) {
            $this->$k = null;
        }
    }

    /**
     * Need to avoid calling render() without params.
     */
    public function __toString()
    {
        try {
            return (string) $this->render();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Clone this object while keeping all vars.
     */
    public function _clone()
    {
        $bak = get_object_vars($this);
        $new = clone $this;
        foreach ((array) $bak as $k => $v) {
            $new->$k = $v;
        }
        return $new;
    }


    public function dump_json()
    {
        return json_encode($this->exec());
    }

    /**
     * Alias.
     */
    public function sql()
    {
        return $this->render();
    }

    /**
     * Create text SQL from params.
     */
    public function render()
    {
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
     * Return SELECT sql string.
     */
    public function _render_select()
    {
        return $this->_sql_part_to_array('select');
    }

    /**
     * Return FROM sql string.
     */
    public function _render_from()
    {
        return $this->_sql_part_to_array('from');
    }

    /**
     * Return JOINs sql string.
     */
    public function _render_joins()
    {
        $a = [];
        foreach (['join', 'left_join', 'inner_join', 'right_join'] as $name) {
            $a[$name] = $this->_sql_part_to_array($name);
            if (empty($a[$name])) {
                unset($a[$name]);
            }
        }
        return $a ? implode(' ', $a) : false;
    }

    /**
     * Return WHERE sql string.
     */
    public function _render_where()
    {
        $a = [];
        foreach (['where', 'where_or'] as $name) {
            $a[$name] = $this->_sql_part_to_array($name);
            if (empty($a[$name])) {
                unset($a[$name]);
            }
        }
        if (isset($a['where_or']) && ! isset($a['where'])) {
            $a = [
                'where' => 'WHERE',
                'where_or' => trim(substr($a['where_or'], 0, strlen('OR '))),
            ];
        }
        return $a ? implode(' ', $a) : false;
    }

    /**
     * Return ORDER BY sql string.
     */
    public function _render_order_by()
    {
        return $this->_sql_part_to_array('order_by');
    }

    /**
     * Return LIMIT sql string.
     */
    public function _render_limit()
    {
        return $this->_sql['limit'];
    }

    /**
     * Create overall SQL array parts in correct order.
     * @param mixed $return_raw
     * @param null|mixed $sql_data
     */
    public function _sql_to_array($return_raw = false, $sql_data = null)
    {
        if ( ! isset($sql_data)) {
            $sql_data = &$this->_sql;
        }
        $a = [];
        // Save 1 call of select()
        if (empty($sql_data['select']) && ! empty($sql_data['from'])) {
            $this->select();
        }
        if (empty($sql_data['select']) || empty($sql_data['from'])) {
            return [];
        }
        // HAVING without GROUP BY makes no sense
        if ( ! empty($sql_data['having']) && empty($sql_data['group_by'])) {
            unset($sql_data['having']);
        }
        // Ensuring strict order of parts of the generated SQL will be correct, no matter how functions were called
        $parts_config = $this->_get_sql_parts_config();
        foreach ($parts_config as $name => $config) {
            if (empty($sql_data[$name])) {
                continue;
            }
            if ($name === 'where_or' && ! isset($a['where'])) {
                $a['where'] = $parts_config['where']['operator'];
                $config['operator'] = '';
            }
            $a[$name] = $this->_sql_part_to_array($name, $sql_data[$name], $config, $return_raw);
        }
        $unions = [];
        foreach ((array) $sql_data['union'] as $query) {
            $subquery = $this->subquery($query);
            if ($subquery) {
                $unions[] = 'UNION ' . $subquery;
            }
        }
        foreach ((array) $sql_data['union_all'] as $query) {
            $subquery = $this->subquery($query);
            if ($subquery) {
                $unions[] = 'UNION ALL ' . $subquery;
            }
        }
        if ($unions) {
            $a['union'] = implode(PHP_EOL, $unions);
        }
        $lock = $sql_data['lock'];
        if (in_array($lock, ['lock_for_update', 'shared_lock'])) {
            $a['lock'] = ($lock === 'lock_for_update' ? 'FOR UPDATE' : 'LOCK IN SHARE MODE');
        }
        return $a;
    }

    /**
     * @param null|mixed $part
     */
    public function _get_sql_parts_config($part = null)
    {
        $config = [
            'select' => ['separator' => ',', 'operator' => 'SELECT'],
            'from' => ['separator' => ',', 'operator' => 'FROM'],
            'join' => ['separator' => 'JOIN', 'operator' => 'JOIN'],
            'left_join' => ['separator' => 'LEFT JOIN', 'operator' => 'LEFT JOIN'],
            'inner_join' => ['separator' => 'INNER JOIN', 'operator' => 'INNER JOIN'],
            'right_join' => ['separator' => 'RIGHT JOIN', 'operator' => 'RIGHT JOIN'],
            'where' => ['separator' => 'AND', 'operator' => 'WHERE'],
            'where_or' => ['separator' => 'OR', 'operator' => 'OR'],
            'group_by' => ['separator' => ',', 'operator' => 'GROUP BY'],
            'having' => ['separator' => 'AND', 'operator' => 'HAVING'],
            'order_by' => ['separator' => ',', 'operator' => 'ORDER BY'],
            'limit' => [],
        ];
        return isset($part) ? $config[$part] : $config;
    }

    /**
     * @param mixed $part
     * @param null|mixed $data
     * @param null|mixed $config
     * @param mixed $return_raw
     */
    public function _sql_part_to_array($part, $data = null, $config = null, $return_raw = false)
    {
        if ( ! $part) {
            return false;
        }
        $config = $config ?: $this->_get_sql_parts_config($part);
        if ( ! isset($data) && isset($this->_sql[$part])) {
            $data = $this->_sql[$part];
        }
        if ( ! isset($data) || empty($data)) {
            return false;
        }
        $operator = $config['operator'];
        $out = [];
        if (is_array($data)) {
            if ( ! isset($config['separator'])) {
                return false;
            }
            if ($return_raw) {
                $out = [
                    'operator' => $operator,
                    'separator' => $config['separator'],
                    'condition' => $data,
                ];
            } else {
                $out = ($operator ? $operator . ' ' : '') . implode(' ' . $config['separator'] . ' ', $data);
            }
        } else {
            if ($return_raw) {
                $out = [
                    'operator' => ($operator ? $operator . ' ' : ''),
                    'condition' => $data,
                ];
            } else {
                $out = ($operator ? $operator . ' ' : '') . $data;
            }
        }
        return $out;
    }

    /**
     * Execute generated query.
     * @param mixed $as_sql
     */
    public function exec($as_sql = false)
    {
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
     * Counting number of records inside requested recordset.
     * @param mixed $id
     * @param mixed $as_sql
     */
    public function count($id = '*', $as_sql = false)
    {
        unset($this->_sql['select']);
        $query = $this->select('COUNT(' . $this->_escape_col_name($id ?: '*') . ')');
        return $as_sql ? $query->sql() : $query->get_one();
    }

    /**
     * SQL aggregate MAX().
     * @param null|mixed $pk
     * @param mixed $as_sql
     */
    public function max($pk = null, $as_sql = false)
    {
        $query = $this->select('MAX(' . $this->_escape_col_name($pk ?: $this->get_key_name()) . ')');
        return $as_sql ? $query->sql() : $query->get_one();
    }

    /**
     * SQL aggregate MIN().
     * @param null|mixed $pk
     * @param mixed $as_sql
     */
    public function min($pk = null, $as_sql = false)
    {
        $query = $this->select('MIN(' . $this->_escape_col_name($pk ?: $this->get_key_name()) . ')');
        return $as_sql ? $query->sql() : $query->get_one();
    }

    /**
     * SQL aggregate AVG().
     * @param null|mixed $pk
     * @param mixed $as_sql
     */
    public function avg($pk = null, $as_sql = false)
    {
        $query = $this->select('AVG(' . $this->_escape_col_name($pk ?: $this->get_key_name()) . ')');
        return $as_sql ? $query->sql() : $query->get_one();
    }

    /**
     * SQL aggregate SUM().
     * @param null|mixed $pk
     * @param mixed $as_sql
     */
    public function sum($pk = null, $as_sql = false)
    {
        $query = $this->select('SUM(' . $this->_escape_col_name($pk ?: $this->get_key_name()) . ')');
        return $as_sql ? $query->sql() : $query->get_one();
    }

    /**
     * Increments value of a column with query condition, similar to update().
     * @param mixed $field
     * @param mixed $step
     * @param mixed $as_sql
     */
    public function increment($field, $step = 1, $as_sql = false)
    {
        if ( ! $field) {
            return false;
        }
        $step = (int) $step;
        if ( ! $step) {
            $step = 1;
        }
        $from = $this->_render_from();
        if (empty($from)) {
            return false;
        }
        $table = preg_replace(self::REGEX_TABLE_NAME_CLEANUP, '', $this->_sql['from'][0]);
        if (preg_match(self::REGEX_AS, $table, $m)) {
            $table = $m[1];
        }
        if ( ! $table) {
            return false;
        }
        $where = [
            $this->_render_where(),
            $this->_render_limit(),
        ];
        // Implode only non-empty array items
        $where = implode(' ', array_filter($where, 'strlen'));
        $key_escaped = $this->_escape_col_name($field);
        $sql = 'UPDATE ' . $this->_escape_table_name($table) . ' SET ' . $key_escaped . ' = ' . $key_escaped . ' ' . ($step < 0 ? '-' : '+') . ' ' . abs((int) $step) . ( ! empty($where) ? ' ' . $where : '');
        if (MAIN_TYPE_ADMIN && $this->db->QUERY_REVISIONS) {
            $this->db->_save_query_revision(__FUNCTION__, $table, ['data' => $sql, 'where' => $where]);
        }
        return $as_sql ? $sql : $this->db->query($sql);
    }

    /**
     * Decrements value of a column with query condition, similar to update().
     * @param mixed $field
     * @param mixed $step
     * @param mixed $as_sql
     */
    public function decrement($field, $step = 1, $as_sql = false)
    {
        return $this->increment($field, ($step ?: 1) * -1, $as_sql);
    }

    /**
     * Return first item from resultset.
     * @param null|mixed $select
     * @param mixed $use_cache
     */
    public function first($select = null, $use_cache = false)
    {
        if (is_object($this->get_model())) {
            return $this->order_by($this->get_key_name() . ' asc')->limit(1)->get($select, $use_cache);
        }
        $this->limit(1);
        return $this->get($select, $use_cache);
    }

    /**
     * Return last item from resultset.
     * @param null|mixed $select
     * @param mixed $use_cache
     */
    public function last($select = null, $use_cache = false)
    {
        if (is_object($this->get_model())) {
            return $this->order_by($this->get_key_name() . ' desc')->limit(1)->get($select, $use_cache);
        }
        $this->limit(1);
        $result = $this->get_all($select, $use_cache);
        if (is_array($result) && count((array) $result)) {
            return end($result);
        }
        return null;
    }

    /**
     * Render SQL and execute db->get().
     * @param null|mixed $select
     * @param mixed $use_cache
     */
    public function get($select = null, $use_cache = false)
    {
        if (isset($select)) {
            $this->select($select);
        }
        $this->limit(1);
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
     * Alias for get_one().
     * @param mixed $field
     * @param mixed $use_cache
     */
    public function one($field = '', $use_cache = false)
    {
        return $this->get_one($field, $use_cache);
    }

    /**
     * Render SQL and execute db->get_one().
     * @param mixed $field
     * @param mixed $use_cache
     */
    public function get_one($field = '', $use_cache = false)
    {
        if (strlen($field)) {
            $this->select($field);
        }
        $this->limit(1);
        $sql = $this->sql();
        if ($sql) {
            return $this->db->get_one($sql, $use_cache);
        }
        return false;
    }

    /**
     * Split big resultset into parts, by executing callback on each chunk.
     * Examples:
     *	db()->from('big_table')->limit(10000)->chunk(200, function($data) {
     *		foreach ((array)$data as $a) { print $a['name'].PHP_EOL; }
     *	}).
     * @param mixed $num
     * @param mixed $callback
     */
    public function chunk($num = 100, $callback)
    {
        $sql = $this->sql();
        if ( ! $sql) {
            return false;
        }
        $q = $this->db->query($sql);
        if ( ! $q) {
            return false;
        }
        $buffer = [];
        while ($a = $this->db->fetch_assoc($q)) {
            $buffer[] = $a;
            if (count((array) $buffer) >= $num) {
                $callback($buffer);
                $buffer = [];
            }
        }
        if (count((array) $buffer)) {
            $callback($buffer);
        }
        return $this;
    }

    /**
     * Alias for get_all().
     * @param null|mixed $select
     * @param mixed $use_cache
     * @param null|mixed $key_name
     */
    public function all($select = null, $use_cache = false, $key_name = null)
    {
        return $this->get_all($select, $use_cache, $key_name);
    }

    /**
     * Get all records with generated SQL.
     * @param null|mixed $select
     * @param mixed $use_cache
     * @param null|mixed $key_name
     */
    public function get_all($select = null, $use_cache = false, $key_name = null)
    {
        if (isset($select)) {
            $this->select($select);
        }
        $sql = $this->sql();
        if ($sql) {
            $result = $this->db->get_all($sql, $key_name, $use_cache);
            if ($result && is_callable($this->_result_wrapper)) {
                foreach ((array) $result as $k => $v) {
                    $result[$k] = call_user_func($this->_result_wrapper, $v);
                }
            }
            return $result;
        }
        return false;
    }

    /**
     * Render SQL and execute db->get_2d().
     * @param null|mixed $select
     * @param mixed $use_cache
     */
    public function get_2d($select = null, $use_cache = false)
    {
        if (isset($select)) {
            $this->select($select);
        }
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
     * Render SQL and execute db->get_deep_array().
     * @param mixed $levels
     * @param null|mixed $select
     * @param mixed $use_cache
     */
    public function get_deep_array($levels = 1, $select = null, $use_cache = false)
    {
        if (isset($select)) {
            $this->select($select);
        }
        $sql = $this->sql();
        if ($sql) {
            return $this->db->get_deep_array($sql, $levels, $use_cache);
        }
        return false;
    }

    /**
     * Delete records matching query params.
     * @param null|mixed $where
     * @param mixed $as_sql
     */
    public function delete($where = null, $as_sql = false)
    {
        if (isset($where)) {
            $this->where($where);
        }
        $sql = false;
        $remove_as_from_delete = true;
        if ($remove_as_from_delete) {
            $table = $this->get_table();
            $this->_sql['from'] = $table ? [$this->_escape_table_name($table)] : false;
        }
        $from = $this->_render_from();
        if (empty($from)) {
            return false;
        }
        $where = $this->_render_where();
        $sql = [
            'DELETE',
            $from,
            $where,
            $this->_render_limit(),
        ];
        // Implode only non-empty array items
        $sql = implode(' ', array_filter($sql, 'strlen'));
        if ($as_sql) {
            return $sql;
        }
        if ($sql) {
            if (MAIN_TYPE_ADMIN && $this->db->QUERY_REVISIONS) {
                $this->db->_save_query_revision(__FUNCTION__, $table, ['data' => $where]);
            }
            return $this->db->query($sql);
        }
        return false;
    }

    /**
     * Insert data into table from query params.
     * @param mixed $params
     */
    public function insert(array $data, $params = [])
    {
        if (empty($data)) {
            return false;
        }
        $a = [];
        $table = $params['table'] ?: $this->get_table();
        if ( ! $table) {
            return false;
        }
        $sql = $this->compile_insert($table, $data, $params);
        if ( ! empty($params['sql'])) {
            return $sql;
        }
        if ($sql) {
            if (MAIN_TYPE_ADMIN && $this->db->QUERY_REVISIONS) {
                $this->db->_save_query_revision(__FUNCTION__, $table, ['data' => $data]);
            }
            $result = $this->db->query($sql);
            $insert_id = $result ? $this->db->insert_id() : false;
            return $insert_id ?: $result;
        }
        return false;
    }

    /**
     * Insert data from query params SQL into other table.
     * @param mixed $table
     * @param mixed $params
     */
    public function insert_into($table, $params = [])
    {
        if ( ! $table) {
            return false;
        }
        $replace = isset($params['replace']) ? $params['replace'] : false;
        $ignore = isset($params['ignore']) ? $params['ignore'] : false;
        $select_sql = $this->sql();
        if ( ! $select_sql) {
            return false;
        }
        $fields_escaped = substr($this->_render_select(), strlen('SELECT '));
        if ($fields_escaped === '*') {
            $fields_escaped = '';
        }
        $sql = ($replace ? 'REPLACE' : 'INSERT') . ($ignore ? ' IGNORE' : '')
            . ' INTO ' . $this->_escape_table_name($table)
            . ($fields_escaped ? ' (' . $fields_escaped . ')' : '')
            . ' ' . PHP_EOL . $select_sql;
        if (MAIN_TYPE_ADMIN && $this->db->QUERY_REVISIONS) {
            $this->db->_save_query_revision(__FUNCTION__, $table, ['data' => $sql]);
        }
        return $params['sql'] ? $sql : $this->db->query($sql);
    }

    /**
     * Return SQL string with INSERT/REPLACE statement for given dataset.
     * @param mixed $table
     * @param mixed $data
     * @param mixed $params
     */
    public function compile_insert($table, $data, $params = [])
    {
        if ( ! strlen($table) || ! is_array($data)) {
            return false;
        }
        $replace = isset($params['replace']) ? $params['replace'] : false;
        $ignore = isset($params['ignore']) ? $params['ignore'] : false;
        $on_duplicate_key_update = isset($params['on_duplicate_key_update']) ? $params['on_duplicate_key_update'] : false;
        $delayed = isset($params['delayed']) ? $params['delayed'] : false;
        if (is_string($replace)) {
            $replace = false;
        }
        $escape = isset($params['escape']) ? (bool) $params['escape'] : true;
        $values_array = [];
        // Try to check if array is two-dimensional
        foreach ((array) $data as $cur_row) {
            $is_multiple = is_array($cur_row) ? 1 : 0;
            break;
        }
        $cols = [];
        if ($is_multiple) {
            foreach ((array) $data as $cur_row) {
                if (empty($cols)) {
                    $cols = array_keys($cur_row);
                }
                // This method ensures that SQL will consist of same key=value pairs, even if in some sub-array they will be missing
                foreach ((array) $cols as $col) {
                    $cur_values[$col] = $cur_row[$col];
                }
                if ($escape) {
                    $_cur_values = $this->_escape_val($cur_values);
                } else {
                    $_cur_values = $cur_values;
                }
                $values_array[] = '(' . implode(', ', $_cur_values) . PHP_EOL . ')';
            }
        } elseif (count((array) $data)) {
            $cols = array_keys($data);
            $values = array_values($data);
            foreach ((array) $values as $k => $v) {
                if ($escape) {
                    $_v = $this->_escape_val($v);
                } else {
                    $_v = $v;
                }
                $values[$k] = $_v;
            }
            $values_array[] = '(' . implode(', ', $values) . PHP_EOL . ')';
        }
        foreach ((array) $cols as $k => $v) {
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
        if (count((array) $cols) && count((array) $values_array)) {
            $sql = ($replace ? 'REPLACE' : 'INSERT')
                . ($delayed ? ' DELAYED' : '')
                . ($ignore ? ' IGNORE' : '')
                . ' INTO ' . $this->_escape_table_name($table) . PHP_EOL
                . ' (' . implode(', ', $cols) . ') VALUES ' . PHP_EOL
                . implode(', ', $values_array);
            if ($on_duplicate_key_update) {
                $sql .= PHP_EOL . ' ON DUPLICATE KEY UPDATE ';
                $tmp = [];
                foreach ((array) $cols as $col => $col_escaped) {
                    if ($col == $primary_col) {
                        continue;
                    }
                    $tmp[] = $col_escaped . ' = VALUES(' . $col_escaped . ')';
                }
                $sql .= implode(', ', $tmp);
            }
        }
        return $sql ?: false;
    }

    /**
     * Update current dataset, mathcing query params.
     * @param mixed $params
     */
    public function update(array $data, $params = [])
    {
        $table = $params['table'] ?: $this->get_table();
        if ( ! $table) {
            return false;
        }
        if (empty($data)) {
            return false;
        }
        // 3-dimensional array detected
        if (is_array($data) && is_array(reset($data))) {
            $index = $params['id'] ?: $this->get_key_name();
            return $this->update_batch($table, $data, $index, $params['sql'], $params);
        }
        $a = $this->_sql_to_array($return_raw = true);
        $where = '';
        if (isset($a['where'])) {
            $where = implode(' ' . $a['where']['separator'] . ' ', $a['where']['condition']);
        }
        if (isset($a['where_or'])) {
            $where = rtrim($where) . ' ' . $a['where_or']['operator'] . ' ' . implode(' ' . $a['where_or']['separator'] . ' ', $a['where_or']['condition']);
        }
        if ( ! strlen($where)) {
            $where = '1=1';
        }
        $sql = $this->compile_update($table, $data, $where, $params);
        if (isset($a['limit'])) {
            $sql .= ' ' . $this->_render_limit();
        }
        if ( ! empty($params['sql'])) {
            return $sql;
        }
        if ($sql) {
            if (MAIN_TYPE_ADMIN && $this->db->QUERY_REVISIONS) {
                $this->db->_save_query_revision(__FUNCTION__, $table, ['data' => $sql]);
            }
            $result = $this->db->query($sql);
        }
        return $result;
    }

    /**
     * Return SQL string with UPDATE statement for given dataset.
     * @param mixed $table
     * @param mixed $where
     * @param mixed $params
     */
    public function compile_update($table, array $data, $where, $params = [])
    {
        if (empty($table) || empty($data) || empty($where)) {
            return false;
        }
        // $where contains numeric id
        if (is_numeric($where)) {
            $where = 'id=' . (int) $where;
        }
        $tmp_data = [];
        $escape = isset($params['escape']) ? (bool) $params['escape'] : true;
        foreach ((array) $data as $k => $v) {
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
            $tmp_data[$k] = $_k . ' = ' . $_v;
        }
        $sql = '';
        if (count((array) $tmp_data)) {
            $sql = 'UPDATE '
                . ($params['ignore'] ? 'IGNORE ' : '')
                . $this->_escape_table_name($table)
                . ' SET ' . implode(', ', $tmp_data)
                . ($where ? ' WHERE ' . $where : '')
                . ($params['order_by'] ? ' ORDER BY ' . $params['order_by'] : '')
                . ($params['limit'] ? ' LIMIT ' . (int) $params['limit'] : '');
        }
        return $sql ?: false;
    }

    /**
     * Update multiple database records at once.
     * Examples:
     *	update_batch('user', $data, 'id')
     *	update_batch('user', $data, ['id', 'cat_id']).
     * @param mixed $table
     * @param null|mixed $index
     * @param mixed $only_sql
     * @param mixed $params
     */
    public function update_batch($table, array $data, $index = null, $only_sql = false, $params = [])
    {
        if ( ! $index) {
            $index = $this->get_key_name($table);
        }
        if ( ! strlen($table) || ! $data || ! is_array($data) || ! $index) {
            return false;
        }
        $this->_set_update_batch_data($data, $index);
        if (count((array) $this->_qb_set) === 0) {
            return false;
        }
        $affected_rows = 0;
        $records_at_once = 100;
        $out = '';
        for ($i = 0, $total = count((array) $this->_qb_set); $i < $total; $i += $records_at_once) {
            $_data = array_slice($this->_qb_set, $i, $records_at_once);
            $sql = $this->_get_update_batch_sql($table, $_data, $index);
            if (is_callable($params['split_callback'])) {
                $callback = $params['split_callback'];
                $callback($_data);
            }
            if ($only_sql) {
                $out .= $sql . ';' . PHP_EOL;
            } else {
                if (MAIN_TYPE_ADMIN && $this->db->QUERY_REVISIONS) {
                    $this->db->_save_query_revision(__FUNCTION__, $table, ['data' => $sql]);
                }
                $this->db->query($sql);
                $affected_rows += $this->db->affected_rows();
            }
        }
        $this->_qb_set = [];
        if ( ! $only_sql) {
            $out = $affected_rows;
        }
        return $out;
    }

    /**
     * Related to update_batch().
     * @param mixed $key
     * @param mixed $index
     */
    public function _set_update_batch_data($key, $index = '')
    {
        if ( ! is_array($key)) {
            return false;
        }
        $index_is_array = is_array($index);
        foreach ((array) $key as $k => $v) {
            $index_set = false;
            $clean = [];
            foreach ((array) $v as $k2 => $v2) {
                if ($index_is_array && in_array($k2, $index)) {
                    $index_set = true;
                } elseif ($k2 === $index) {
                    $index_set = true;
                }
                $clean[$this->_escape_key($k2)] = $this->_escape_val($v2);
            }
            if ($index_set === false) {
                throw new Exception('db_batch_missing_index');
                return false;
            }
            $this->_qb_set[] = $clean;
        }
    }

    /**
     * Related to update_batch().
     * @param mixed $table
     * @param mixed $values
     * @param mixed $index
     */
    public function _get_update_batch_sql($table, $values, $index)
    {
        $ids = [];
        $final = [];
        $where = [];
        $index_is_array = is_array($index);
        if ($index_is_array) {
            foreach ($index as $ik => $idx_col) {
                $index[$ik] = $this->_escape_key($idx_col);
            }
            foreach ((array) $values as $key => $val) {
                foreach ($index as $idx_col) {
                    $ids[$idx_col][] = $val[$idx_col];
                }
                foreach (array_keys($val) as $field) {
                    if (in_array($field, $index)) {
                        continue;
                    }
                    $when = [];
                    foreach ($index as $idx_col) {
                        $when[] = $idx_col . ' = ' . $val[$idx_col];
                    }
                    $final[$field][] = 'WHEN ' . implode(' AND ', $when) . ' THEN ' . $val[$field];
                }
            }
            foreach ($index as $ik => $idx_col) {
                $where[] = $idx_col . ' IN(' . implode(',', $ids[$idx_col]) . ')';
            }
        } else {
            $index = $this->_escape_key($index);
            foreach ((array) $values as $key => $val) {
                $ids[] = $val[$index];
                foreach (array_keys($val) as $field) {
                    if ($field !== $index) {
                        $final[$field][] = 'WHEN ' . $index . ' = ' . $val[$index] . ' THEN ' . $val[$field];
                    }
                }
            }
            $where[] = $index . ' IN(' . implode(',', $ids) . ')';
        }
        $cases = '';
        foreach ((array) $final as $k => $v) {
            $cases .= $k . ' = CASE ' . PHP_EOL . implode(PHP_EOL, $v) . PHP_EOL . 'ELSE ' . $k . ' END, ';
        }
        return 'UPDATE ' . $this->_escape_table_name($table) . ' SET ' . substr($cases, 0, -2) . ' WHERE ' . implode(' AND ', $where);
    }

    /**
     * Examples:
     *	select('id, name, age')
     *	select('id', 'name', 'age')
     *	select(['id', 'name', 'age'])
     *	select('u.id as user_id, u.name as user_name')
     *	select('DISTINCT user_id')
     *	select(['COUNT(u.id)' => 'num']);.
     */
    public function select()
    {
        $sql = '';
        $fields = func_get_args();
        if ( ! count((array) $fields) || $fields === []) {
            $sql = '*';
        } else {
            $a = [];
            $fields = $this->_split_by_comma($fields);
            foreach ((array) $fields as $k => $v) {
                if (is_string($v)) {
                    $v = trim($v);
                }
                if (is_string($v) && strlen($v) && ! empty($v)) {
                    // support for syntax: select('a.id as aid')
                    if (preg_match(self::REGEX_AS, $v, $m)) {
                        $a[] = $this->_escape_expr($m[1]) . ' AS ' . $this->_escape_key($m[2]);
                    } else {
                        $a[] = $this->_escape_expr($v);
                    }
                } elseif (is_array($v)) {
                    foreach ((array) $v as $k2 => $v2) {
                        $k2 = trim($k2);
                        $v2 = trim($v2);
                        if (strlen($k2) && strlen($v2)) {
                            // support for syntax: select('a.id as aid')
                            if (preg_match(self::REGEX_AS, $v2, $m)) {
                                $a[] = $this->_escape_expr($m[1]) . ' AS ' . $this->_escape_key($m[2]);
                            } elseif ( ! is_numeric($k2)) {
                                $a[] = $this->_escape_expr($k2) . ' AS ' . $this->_escape_key($v2);
                            } else {
                                $a[] = $this->_escape_expr($v2);
                            }
                        }
                    }
                } elseif (is_object($v) && $v instanceof self) {
                    $a[] = $this->subquery($v);
                } elseif (is_callable($v)) {
                    $a[] = $v($fields, $this);
                }
            }
            if ($a) {
                $sql = implode(' , ', $a);
            }
        }
        if ($sql) {
            $this->_sql[__FUNCTION__][] = $sql;
        }
        return $this;
    }

    /**
     * Alias for "from".
     */
    public function table()
    {
        return call_user_func_array([$this, 'from'], func_get_args());
    }

    /**
     * Examples:
     *	from('users')
     *	from('users as u')
     *	from('users as u', 'products as p')
     *	from(['users' => 'u'])
     *	from(['users' => 'u', 'suppliers' => 's'])
     *	from(['users' => 'u'), array('suppliers' => 's']).
     */
    public function from()
    {
        $sql = '';
        $tables = func_get_args();
        if ( ! count((array) $tables)) {
            return $this;
        }
        $a = [];
        $tables = $this->_split_by_comma($tables);
        foreach ((array) $tables as $k => $v) {
            if (is_string($v)) {
                $v = trim($v);
            }
            if (is_string($v) && strlen($v) && ! empty($v)) {
                // support for syntax: from('users as u') from('users as u', 'messages as m')
                if (preg_match(self::REGEX_AS, $v, $m)) {
                    $a[] = $this->_escape_table_name($m[1]) . ' AS ' . $this->_escape_key($m[2]);
                } else {
                    $a[] = $this->_escape_table_name($v);
                }
            } elseif (is_array($v)) {
                foreach ((array) $v as $k2 => $v2) {
                    $k2 = trim($k2);
                    $v2 = trim($v2);
                    if (strlen($k2) && strlen($v2)) {
                        // support for syntax: from('users as u') from('users as u', 'messages as m')
                        if (preg_match(self::REGEX_AS, $v2, $m)) {
                            $a[] = $this->_escape_table_name($m[1]) . ' AS ' . $this->_escape_key($m[2]);
                        } else {
                            $a[] = $this->_escape_table_name($k2) . ' AS ' . $this->_escape_key($v2);
                        }
                    }
                }
            } elseif (is_object($v) && $v instanceof self) {
                $a[] = $this->subquery($v);
            } elseif (is_callable($v)) {
                $a[] = $v($tables, $this);
            }
        }
        if ($a) {
            $sql = implode(' , ', $a);
        }
        if ($sql) {
            $this->_sql[__FUNCTION__][] = $sql;
        }
        return $this;
    }

    /**
     * Examples:
     *	where('id', '1')
     *	where('id > 5')
     *	where(['id' => '5'])
     *	where(['id','>','1'],'and', ['name','!=','peter'])
     *	where(1)
     *	where(1,2,3)
     *	where('id', [1,2,3])
     *	where(['id' => [1,2,3]]).
     */
    public function where()
    {
        return $this->_process_where(func_get_args(), __FUNCTION__);
    }

    /**
     * Examples:
     *	where_or('id', '1')
     *	where_or('id > 5')
     *	where_or(['id' => '5']).
     *
     *	Note: for more examples see "where()"
     */
    public function where_or()
    {
        return $this->_process_where(func_get_args(), __FUNCTION__);
    }

    /**
     * Alias.
     */
    public function or_where()
    {
        return $this->_process_where(func_get_args(), 'where_or');
    }

    /**
     * Examples:
     *	whereid(1)
     *	whereid(1, 'id')
     *	whereid(1, 'user_id')
     *	whereid([1,2,3])
     *	whereid([1,2,3], 'user_id')
     *	whereid(1,2,3)
     *	whereid(1,2,3,'user_id').
     */
    public function whereid()
    {
        $id = func_get_args();
        $pk = '';
        if (count((array) $id) > 1) {
            $last = array_pop($id);
            if ( ! empty($last) && ! is_numeric($last)) {
                $pk = $last;
            } else {
                $id[] = $last;
            }
        }
        if (is_array($id) && count((array) $id) === 1) {
            $id = reset($id);
        }
        if (is_array($id) && count((array) $id) === 1) {
            $key = key($id);
            if ( ! is_numeric($key)) {
                $pk = $key;
            }
            $id = reset($id);
        }
        if ( ! $pk) {
            $pk = $this->get_key_name();
        }
        $sql = '';
        if (is_array($id) && count((array) $id) > 1) {
            $ids = $this->_ids_sql_from_array($id);
            if (count((array) $ids) > 1) {
                $sql = $this->_escape_col_name($pk) . ' IN(' . implode(',', $ids) . ')';
            } else {
                $sql = $this->_process_where_cond($pk, '=', reset($ids));
            }
        } elseif (is_object($id) && $id instanceof self) {
            $sql = $this->subquery($id);
        } elseif (is_callable($id)) {
            $sql = $id();
        } else {
            $sql = $this->_process_where_cond($pk, '=', (int) $id);
        }
        if ($sql) {
            $this->_sql['where'][] = $sql;
        }
        return $this;
    }

    /**
     * Alias for whereid().
     */
    public function where_in()
    {
        return call_user_func_array([$this, 'whereid'], func_get_args());
    }

    /**
     * Add raw WHERE part. Be careful with it, no escaping or wrapping here!
     *	where_raw('id BETWEEN 1 AND 4').
     */
    public function where_raw()
    {
        foreach ((array) func_get_args() as $arg) {
            $this->_sql['where'][] = $arg;
        }
        return $this;
    }

    /**
     * Shortcut for IS NULL checking for field(s)
     *	where_null('pid')		=> `pid` IS NULL
     *	where_null('pid','uid','gid')		=> `pid` IS NULL AND `uid` IS NULL AND `gid` IS NULL
     *	where_null(['pid','uid','gid'])		=> `pid` IS NULL AND `uid` IS NULL AND `gid` IS NULL.
     */
    public function where_null()
    {
        $func = __FUNCTION__;
        foreach ((array) func_get_args() as $arg) {
            if (is_array($arg)) {
                $this->$func($arg);
            } else {
                $this->where($arg, 'is null');
            }
        }
        return $this;
    }

    /**
     * where_null with "not".
     */
    public function where_not_null()
    {
        $func = __FUNCTION__;
        foreach ((array) func_get_args() as $arg) {
            if (is_array($arg)) {
                $this->$func($arg);
            } else {
                $this->where($arg, 'is not null');
            }
        }
        return $this;
    }

    /**
     * Shortcut for BETWEEN min AND max
     *	where_between('pid', 1, 10)		=> `pid` BETWEEN 1 AND 10.
     * @param mixed $field
     * @param mixed $min
     * @param mixed $max
     */
    public function where_between($field, $min, $max)
    {
        return $this->where_raw($this->_escape_col_name($field) . ' BETWEEN ' . $this->_escape_val($min) . ' AND ' . $this->_escape_val($max));
    }

    /**
     * SQL statement ANY() for subqueries.
     * @param mixed $key
     * @param mixed $op
     * @param mixed $query
     */
    public function where_any($key, $op = '=', $query)
    {
        return $this->where_raw(
            $this->_escape_col_name($key) . ' ' . (in_array($op, ['=', '>', '<', '>=', '<=', '!=', '<>']) ? $op : '=') . ' ANY ' . $this->subquery($query)
        );
    }

    /**
     * SQL statement ALL() for subqueries.
     * @param mixed $key
     * @param mixed $op
     * @param mixed $query
     */
    public function where_all($key, $op = '=', $query)
    {
        return $this->where_raw(
            $this->_escape_col_name($key) . ' ' . (in_array($op, ['=', '>', '<', '>=', '<=', '!=', '<>']) ? $op : '=') . ' ALL ' . $this->subquery($query)
        );
    }

    /**
     * SQL statement EXISTS for subqueries.
     * @param mixed $query
     */
    public function where_exists($query)
    {
        return $this->where_raw('EXISTS ' . $this->subquery($query));
    }

    /**
     * SQL statement NOT EXISTS for subqueries.
     * @param mixed $query
     */
    public function where_not_exists($query)
    {
        return $this->where_raw('NOT EXISTS ' . $this->subquery($query));
    }

    /**
     * Prepare WHERE statement.
     * @param mixed $func_name
     * @param mixed $return_array
     */
    public function _process_where(array $where, $func_name = 'where', $return_array = false)
    {
        $sql = '';
        if ($count = count((array) $where)) {
            $all_numeric = $this->_is_where_all_numeric($where);
            if ($all_numeric) {
                // where([1,2,3])
                if (count((array) $where) === 1 && isset($where[0])) {
                    $where = $where[0];
                }
                return $this->whereid($where);
            }
        }
        $where = $this->_split_by_comma($where);
        $count = count((array) $where);
        if (($count === 3 || $count === 2) && is_string($where[0]) && (is_string($where[1]) || is_numeric($where[1]) || is_array($where[1]))) {
            if ( ! preg_match(self::REGEX_INLINE_CONDS, $where[0]) && ! preg_match(self::REGEX_INLINE_CONDS, $where[1])) {
                $sql = $this->_process_where_cond($where[0], $where[1], $where[2]);
            }
        }
        $avail_imploders = ['AND', 'OR', 'XOR'];
        $imploder = 'AND';
        if ($func_name === 'where_or') {
            $imploder = 'OR';
        }
        if ( ! $sql && $count) {
            $a = [];
            foreach ((array) $where as $k => $v) {
                if (is_string($v)) {
                    $v = trim($v);
                }
                $count_a = count((array) $a);
                $need_imploder = false;
                if ($count_a && ! in_array($a[$count_a - 1], $avail_imploders)) {
                    $need_imploder = true;
                }
                if (is_string($v) && strlen($v) && ! empty($v)) {
                    if (preg_match(self::REGEX_INLINE_CONDS, $v, $m)) {
                        if ($need_imploder) {
                            $a[] = $imploder;
                        }
                        $a[] = $this->_process_where_cond($m[1], $m[2], $m[3]);
                    } elseif (preg_match(self::REGEX_IS_NULL, $v, $m)) {
                        if ($need_imploder) {
                            $a[] = $imploder;
                        }
                        $a[] = $this->_process_where_cond($m[1], $m[2], '');
                    } else {
                        $v = strtoupper(trim($v));
                        if (in_array($v, $avail_imploders)) {
                            $a[] = $v;
                        }
                    }
                } elseif (is_array($v)) {
                    // ['field', 'condition', 'value'], example: ['id','>','1']
                    $_count_v = count((array) $v);
                    if (($_count_v === 3 || $_count_v === 2) && isset($v[0]) && ! preg_match(self::REGEX_INLINE_CONDS, $v[0])) {
                        $tmp = $this->_process_where_cond($v[0], $v[1], $v[2]);
                        if ( ! strlen($tmp)) {
                            continue;
                        }
                        if ($need_imploder) {
                            $a[] = $imploder;
                        }
                        $a[] = $tmp;
                    // ['field1' => 'val1', 'field2' => 'val2']
                    } else {
                        $tmp = [];
                        foreach ($v as $k2 => $v2) {
                            if (is_string($v2)) {
                                $v2 = trim($v2);
                            }
                            if (is_string($v2) && preg_match(self::REGEX_INLINE_CONDS, $v2, $m)) {
                                $_tmp = $this->_process_where_cond($m[1], $m[2], $m[3]);
                            } else {
                                $_tmp = $this->_process_where_cond($k2, '=', $v2);
                            }
                            if ($_tmp) {
                                $tmp[] = $_tmp;
                            }
                        }
                        if (count((array) $tmp)) {
                            if ($need_imploder) {
                                $a[] = $imploder;
                            }
                            foreach ($tmp as $k2 => $v2) {
                                if ($k2 > 0) {
                                    $a[] = $imploder;
                                }
                                $a[] = $v2;
                            }
                        }
                    }
                } elseif (is_object($v) && $v instanceof self) {
                    $a[] = $this->subquery($v);
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
     * @param mixed $left
     * @param mixed $op
     * @param mixed $right
     */
    public function _process_where_cond($left, $op, $right)
    {
        ! $op && $op = '=';
        $left = trim(strtolower($left));
        if (is_string($op)) {
            $op = trim(strtolower($op));
        }
        $right_generated = '';
        // Think that we dealing with 2 arguments passing like this: where('id', 1)
        // Also this will match: where('id', [1,2,3])
        if (strlen($left) && ! empty($op) && ! is_array($right) && ! strlen($right)) {
            if (strlen($op) && ! in_array($op, ['=', '!=', '<', '>', '<=', '>=', 'like', 'not like', 'is null', 'is not null', 'in', 'not in'])) {
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
            $right_generated = $this->_ids_sql_from_array((array) $right);
            if ($right_generated) {
                $right_generated = '(' . implode(',', $right_generated) . ')';
            }
        }
        if ((empty($right) || ! strlen(is_array($right) ? '' : $right)) && ! strlen($right_generated)) {
            return '';
        }
        if ($right_generated === '__dummy_for_null__') {
            return $this->_escape_expr($left) . ' ' . strtoupper($op);
        }
        return $this->_escape_expr($left) . ' ' . strtoupper($op) . ($right_generated ?: ' ' . $this->_escape_val($right));
    }

    /**
     * Examples:
     *	join('suppliers', 'products.supplier_id = 'suppliers.id'))
     *	join('suppliers as s', 's.supplier_id = 'u.id')
     *	join(['suppliers' => 's', 's.supplier_id = 'u.id'])
     *	join(['suppliers' => 's', 's.supplier_id = 'u.id'], 'inner')
     *	inner_join('suppliers as s', ['s.supplier_id' => 'u.id'])
     *	left_join('suppliers as s', ['s.supplier_id' => 'u.id', 's.other_id' => 'u.other_id']).
     * @param mixed $table
     * @param mixed $on
     * @param mixed $join_type
     * @param mixed $is_select
     */
    public function join($table, $on, $join_type = '', $is_select = false)
    {
        $join_types = [
            'left',
            'right',
            'inner',
        ];
        if ( ! in_array($join_type, $join_types)) {
            $join_type = '';
        }
        $as = '';
        if (is_array($table)) {
            $as = current($table);
            $table = key($table);
        } elseif (is_string($table)) {
            $table = trim($table);
            // support for syntax: join('users as u', 'u.id = s.id')
            if (preg_match(self::REGEX_AS, $table, $m)) {
                $table = $m[1];
                $as = $m[2];
            }
        }
        $_on = [];
        if (is_array($on)) {
            foreach ((array) $on as $k => $v) {
                if (is_numeric($k)) {
                    $tmp = trim($v);
                    if (preg_match(self::REGEX_INLINE_CONDS, $tmp, $m)) {
                        $_on[] = $this->_escape_col_name(trim($m[1])) . ' ' . trim($m[2]) . ' ' . $this->_escape_col_name(trim($m[3]));
                    }
                } else {
                    $_on[] = $this->_escape_col_name(trim($k)) . ' = ' . $this->_escape_col_name(trim($v));
                }
            }
        } elseif (is_string($on)) {
            foreach (preg_split('~\s+(and)\s+~ims', $on) as $on_part) {
                $on_part = trim($on_part);
                if (preg_match(self::REGEX_INLINE_CONDS, $on_part, $m)) {
                    $_on[] = $this->_escape_col_name(trim($m[1])) . ' ' . trim($m[2]) . ' ' . $this->_escape_col_name(trim($m[3]));
                }
            }
        } elseif (is_callable($on)) {
            $table = trim($table);
            $_on = $on($table, $this);
        }
        $sql = '';
        if (is_string($table) && ! empty($_on)) {
            if ($is_select) {
                $table_name = $table;
            } else {
                $table_name = $this->_escape_table_name(trim($table));
            }
            $sql = $table_name . ($as ? ' AS ' . $this->_escape_key(trim($as)) : '') . ' ON ' . implode(' AND ', $_on);
        }
        if ($sql) {
            $this->_sql[($join_type ? $join_type . '_' : '') . __FUNCTION__][] = $sql;
        }
        return $this;
    }

    /**
     * @param mixed $table
     * @param mixed $on
     * @param mixed $is_select
     */
    public function left_join($table, $on, $is_select = false)
    {
        return $this->join($table, $on, 'left', $is_select);
    }

    /**
     * @param mixed $table
     * @param mixed $on
     * @param mixed $is_select
     */
    public function right_join($table, $on, $is_select = false)
    {
        return $this->join($table, $on, 'right', $is_select);
    }

    /**
     * @param mixed $table
     * @param mixed $on
     * @param mixed $is_select
     */
    public function inner_join($table, $on, $is_select = false)
    {
        return $this->join($table, $on, 'inner', $is_select);
    }

    /**
     * Add raw joint part. Be careful with it, no escaping or wrapping here!
     *	join_raw(left join table as t on t.id = t1.id).
     * @param mixed $join_type
     * @param mixed $sql
     */
    public function join_raw($join_type, $sql)
    {
        $join_types = [
            'left',
            'right',
            'inner',
        ];
        if ( ! in_array($join_type, $join_types)) {
            $join_type = '';
        }
        $this->_sql[($join_type ? $join_type . '_' : '') . 'join'][] = $sql;
        return $this;
    }

    /**
     * @param mixed $sql
     */
    public function left_join_raw($sql)
    {
        return $this->join_raw('left', $sql);
    }

    /**
     * @param mixed $sql
     */
    public function right_join_raw($sql)
    {
        return $this->join_raw('right', $sql);
    }

    /**
     * @param mixed $sql
     */
    public function inner_join_raw($sql)
    {
        return $this->join_raw('inner', $sql);
    }

    /**
     * Examples:
     *	group_by('user_group')
     *	group_by(['supplier','manufacturer']).
     */
    public function group_by()
    {
        $sql = '';
        $items = func_get_args();
        if ( ! count((array) $items)) {
            return $this;
        }
        $a = [];
        $items = $this->_split_by_comma($items);
        foreach ((array) $items as $k => $v) {
            if (is_string($v)) {
                $v = trim($v);
            }
            if (is_string($v) && strlen($v) && ! empty($v)) {
                $a[] = $this->_escape_expr($v);
            } elseif (is_array($v)) {
                foreach ((array) $v as $v2) {
                    if ( ! is_string($v2)) {
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
            $sql = implode(' , ', $a);
        }
        if ($sql) {
            $this->_sql[__FUNCTION__][] = $sql;
        }
        return $this;
    }

    /**
     * Examples:
     *	having('COUNT(*)','>','1')
     *	having(['COUNT(*)','>','1'])
     *	having(['COUNT(id)','>','1'], ['COUNT(gid)','>','2']).
     */
    public function having()
    {
        $sql = '';
        $where = func_get_args();
        if ( ! count((array) $where)) {
            return $this;
        }
        $a = [];
        $where = $this->_split_by_comma($where);
        foreach ((array) $where as $k => $v) {
            if (is_string($v)) {
                $v = trim($v);
            }
            if (is_string($v) && strlen($v) && ! empty($v)) {
                if (preg_match(self::REGEX_INLINE_CONDS, $v, $m)) {
                    $a[] = $this->_process_where_cond($m[1], $m[2], $m[3]);
                } else {
                    $v = strtoupper(trim($v));
                    if (in_array($v, ['AND', 'OR', 'XOR'])) {
                        $a[] = $v;
                    }
                }
            } elseif (is_array($v)) {
                // ['field', 'condition', 'value'], example: ['id','>','1']
                if (count((array) $v) == 3 && isset($v[0])) {
                    $a[] = $this->_process_where_cond($v[0], $v[1], $v[2]);
                // ['field1' => 'val1', 'field2' => 'val2']
                } else {
                    $tmp = [];
                    foreach ($v as $k2 => $v2) {
                        $v2 = trim($v2);
                        $_tmp = '';
                        if (preg_match(self::REGEX_INLINE_CONDS, $v2, $m)) {
                            $_tmp = $this->_process_where_cond($m[1], $m[2], $m[3]);
                        } else {
                            $_tmp = $this->_process_where_cond($k2, '=', $v2);
                        }
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
        if ($sql) {
            $this->_sql[__FUNCTION__][] = $sql;
        }
        return $this;
    }

    /**
     * Examples:
     *	order_by('user_group')
     * 	order_by('field','asc')
     *	order_by(['supplier' => 'desc', 'manufacturer' => 'asc']),
     *	order_by(['field1','asc'], ['field2','desc'], ['field3','asc']).
     */
    public function order_by()
    {
        $sql = '';
        $items = func_get_args();
        $count = count((array) $items);
        if ( ! $count) {
            unset($this->_sql[__FUNCTION__]);
            return $this;
        }
        if ($count === 2 && ! empty($items[0]) && in_array(trim(strtoupper($items[1])), ['ASC', 'DESC'])) {
            $items = [[$items[0] => $items[1]]];
        }
        $a = [];
        $items = $this->_split_by_comma($items);
        foreach ((array) $items as $k => $v) {
            if (is_string($v)) {
                $v = trim($v);
            }
            if (is_string($v) && strlen($v) && ! empty($v)) {
                if (preg_match(self::REGEX_ASC_DESC, $v, $m)) {
                    $a[] = $this->_escape_expr($m[1]) . ' ' . strtoupper($m[2]);
                } else {
                    $a[] = $this->_escape_expr($v) . ' ASC';
                }
            } elseif (is_array($v)) {
                if (count((array) $v) === 2 && ! empty($v[0]) && in_array(trim(strtoupper($v[1])), ['ASC', 'DESC'])) {
                    $v = [$v[0] => $v[1]];
                }
                foreach ((array) $v as $k2 => $v2) {
                    if ( ! is_string($v2)) {
                        continue;
                    }
                    $v2 = trim($v2);
                    if (preg_match(self::REGEX_ASC_DESC, $v2, $m)) {
                        $a[] = $this->_escape_expr($m[1]) . ' ' . strtoupper($m[2]);
                    } else {
                        $direction = 'ASC';
                        $v2 = trim($v2);
                        if (is_string($k2) && in_array(strtoupper($v2), ['ASC', 'DESC'])) {
                            $direction = $v2;
                            $v2 = trim($k2);
                        }
                        if ($v2) {
                            $a[] = $this->_escape_expr($v2) . ' ' . strtoupper($direction);
                        }
                    }
                }
            } elseif (is_callable($items)) {
                $a[] = $v($items, $this);
            }
        }
        if ($a) {
            $sql = implode(' , ', $a);
        }
        if ($sql) {
            $this->_sql[__FUNCTION__][] = $sql;
        }
        return $this;
    }

    /**
     * Examples:
     *	limit(10)
     *	limit(10,100).
     * @param mixed $count
     * @param null|mixed $offset
     */
    public function limit($count = 10, $offset = null)
    {
        if ($count) {
            $sql = $this->db->limit($count, $offset);
            $this->_sql[__FUNCTION__] = $sql;
        } else {
            unset($this->_sql[__FUNCTION__]);
        }
        return $this;
    }

    /**
     * SQL subquery wrapper.
     * @param mixed $query
     */
    public function subquery($query)
    {
        $sql = (is_object($query) && $query instanceof self) ? $query->sql() : $query;
        return '(' . PHP_EOL . $sql . PHP_EOL . ')';
    }

    /**
     * UNION sql wrapper.
     * @param mixed $query
     */
    public function union($query)
    {
        $this->_sql[__FUNCTION__][] = $query;
        return $this;
    }

    /**
     * UNION ALL sql wrapper.
     * @param mixed $query
     */
    public function union_all($query)
    {
        $this->_sql[__FUNCTION__][] = $query;
        return $this;
    }

    /**
     * Pessimistic locking.
     */
    public function shared_lock()
    {
        $this->_sql['lock'] = __FUNCTION__;
        return $this;
    }

    /**
     * Lock records for update on select statement.
     */
    public function lock_for_update()
    {
        $this->_sql['lock'] = __FUNCTION__;
        return $this;
    }

    /**
     * Get current linked model.
     */
    public function get_model()
    {
        if (isset($this->_model) && is_object($this->_model) && ($this->_model instanceof yf_model)) {
            return $this->_model;
        }
        return false;
    }

    /**
     * Find primary key name.
     * @param mixed $table
     */
    public function get_key_name($table = '')
    {
        $pk = '';
        if (strlen($table)) {
            if (main()->USE_SYSTEM_CACHE) {
                $cache_name = get_class($this) . '|' . __FUNCTION__ . '|' . $table . '|' . $this->db->DB_HOST . '|' . $this->db->DB_PORT . '|' . $this->db->DB_NAME . '|' . $this->db->DB_PREFIX;
            }
            $cache_name && $pk = cache()->get($cache_name);
            if ( ! $pk) {
                $utils = $this->db->utils();
                if ($utils->table_exists($table)) {
                    $primary_index = $utils->index_info($table, 'PRIMARY');
                    if (isset($primary_index['columns'])) {
                        $pk = current($primary_index['columns']);
                    }
                }
                $cache_name && cache()->set($cache_name, $pk);
            }
        } elseif ($model = $this->get_model()) {
            $pk = $model->get_key_name();
        }
        return $pk ?: 'id';
    }

    /**
     * Return first table used.
     */
    public function get_table()
    {
        if (empty($this->_sql['from'])) {
            return false;
        }
        $table = preg_replace(self::REGEX_TABLE_NAME_CLEANUP, '', $this->_sql['from'][0]);
        if (preg_match(self::REGEX_AS, $table, $m)) {
            $table = $m[1];
        }
        return $table;
    }

    /**
     * Return array of params splitted by comma from strings or subarray strings.
     */
    public function _split_by_comma(array $items)
    {
        // Pre-split items by comma
        foreach ($items as $k => $v) {
            if (is_string($v)) {
                if (strpos($v, ',') !== false) {
                    $items[$k] = explode(',', $v);
                }
            } elseif (is_array($v) && is_numeric($k)) {
                foreach ((array) $v as $k2 => $v2) {
                    if ( ! is_string($v2)) {
                        continue;
                    }
                    if (strpos($v2, ',') !== false) {
                        // Replace parent array with splitted values
                        $items[$k] = explode(',', $v2);
                    }
                }
            }
        }
        return $items;
    }

    /**
     * Detecting input consisting from numbers, think that this is "whereid" alias call like this: where(1), whereid(1,2,3).
     */
    public function _is_where_all_numeric(&$where)
    {
        if (empty($where) || ( ! is_array($where) && ! is_numeric($where))) {
            return false;
        }
        $count = count((array) $where);
        if ( ! $count) {
            return false;
        }
        $self_func = __FUNCTION__;
        foreach ($where as $k => $v) {
            if (is_array($v)) {
                if ( ! $this->$self_func($where[$k])) {
                    return false;
                }
                continue;
            }
            if (empty($v)) {
                unset($where[$k]);
            } elseif ( ! is_numeric($k) || ! is_numeric($v)) {
                return false;
            }
        }
        return true;
    }


    public function _ids_sql_from_array(array $ids)
    {
        $out = [];
        foreach ((array) $ids as $v) {
            if (is_array($v)) {
                $func = __FUNCTION__;
                foreach ($this->$func($v) as $v2) {
                    $out[$v2] = $v2;
                }
            } elseif ( ! is_int($v)) {
                $v = (string) $v;
                if ( ! strlen($v)) {
                    continue;
                }
                $v = $this->_escape_val($v);
                $out[$v] = $v;
            } elseif (is_numeric($v)) {
                $v = (int) $v;
                $out[$v] = $v;
            }
        }
        return $out;
    }

    /**
     * @param mixed $expr
     */
    public function _escape_expr($expr = '')
    {
        if ($expr === '*' || false !== strpos($expr, '(') || preg_match('~[^a-z0-9_\.]+~ims', $expr)) {
            return $expr;
        }
        return $this->_escape_col_name($expr);
    }

    /**
     * @param mixed $name
     */
    public function _escape_col_name($name = '')
    {
        $name = trim($name);
        if ( ! strlen($name)) {
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
        if ( ! strlen($col)) {
            return false;
        }
        return (strlen($db) ? $this->_escape_key($db) . '.' : '')
            . (strlen($table) ? $this->_escape_key($table) . '.' : '')
            . $this->_escape_key($col);
    }

    /**
     * @param mixed $name
     */
    public function _escape_table_name($name = '')
    {
        $name = trim($name);
        if ( ! strlen($name)) {
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
        if ( ! strlen($table)) {
            return false;
        }
        if ( ! strlen($db) || $db == $this->db->DB_NAME) {
            $table = $this->db->_real_name($table);
        }
        return (strlen($db) ? $this->_escape_key($db) . '.' : '') . $this->_escape_key($table);
    }

    /**
     * @param mixed $key
     */
    public function _escape_key($key = '')
    {
        if ($key != '*' && false === strpos($key, '.') && false === strpos($key, '(')) {
            return $this->db->escape_key($key);
        }
        return $key;
    }

    /**
     * @param mixed $val
     */
    public function _escape_val($val = '')
    {
        // TODO: support for binding params (':field' => $val)
        // TODO: support for binding params ('id > ?', $val)
        return $this->db->escape_val($val);
    }
}
