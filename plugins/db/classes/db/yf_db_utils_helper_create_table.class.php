<?php


class yf_db_utils_helper_create_table
{
    protected $utils = null;
    protected $db_name = '';
    protected $table_name = '';
    protected $fields = [];
    protected $indexes = [];
    protected $foreign_keys = [];
    protected $table_options = [];
    protected $for_create = false;

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
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


    public function __toString()
    {
        return $this->render();
    }

    /**
     * @param mixed $params
     */
    public function _setup($params)
    {
        foreach ($params as $k => $v) {
            $this->$k = $v;
        }
        return $this;
    }

    /**
     * @param mixed $new_name
     */
    public function _get_table_name($new_name = '')
    {
        return $this->db_name . '.' . $this->utils->db->_fix_table_name($new_name ?: $this->table_name);
    }


    public function render()
    {
        $parser = _class('db_ddl_parser_mysql', 'classes/db/');
        return $parser->create([
            'name' => $this->_get_table_name(),
            'fields' => $this->fields,
            'indexes' => $this->indexes,
            'foreign_keys' => $this->foreign_keys,
            'options' => $this->table_options,
        ]);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function increments($column, $params = [])
    {
        return $this->add_column($column, (array) $params + [
            'type' => 'int',
            'length' => 10,
            'auto_inc' => true,
            'nullable' => false,
            'unsigned' => true,
        ])->primary($column);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function big_increments($column, $params = [])
    {
        return $this->increments($column, ['type' => 'bigint', 'length' => 20] + (array) $params);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function char($column, $params = [])
    {
        if (is_numeric($params)) {
            $params = ['length' => $params];
        }
        return $this->add_column($column, (array) $params + [
            'type' => 'char',
            'length' => $params['length'] ?: 255,
        ]);
    }

    /**
     * Alias.
     * @param mixed $column
     * @param mixed $params
     */
    public function string($column, $params = [])
    {
        return $this->varchar($column, $params);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function varchar($column, $params = [])
    {
        if (is_numeric($params)) {
            $params = ['length' => $params];
        }
        return $this->add_column($column, (array) $params + [
            'type' => 'varchar',
            'length' => $params['length'] ?: 255,
        ]);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function text($column, $params = [])
    {
        return $this->add_column($column, (array) $params + [
            'type' => 'text',
        ]);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function medium_text($column, $params = [])
    {
        return $this->text($column, ['type' => 'mediumtext'] + (array) $params);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function long_text($column, $params = [])
    {
        return $this->text($column, ['type' => 'longtext'] + (array) $params);
    }

    /**
     * Alias for int.
     * @param mixed $column
     * @param mixed $params
     */
    public function integer($column, $params = [])
    {
        if (is_numeric($params)) {
            $params = ['length' => $params];
        }
        return $this->int($column, $params);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function int($column, $params = [])
    {
        if (is_numeric($params)) {
            $params = ['length' => $params];
        }
        return $this->add_column($column, (array) $params + [
            'type' => 'int',
        ]);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function big_int($column, $params = [])
    {
        if (is_numeric($params)) {
            $params = ['length' => $params];
        }
        return $this->int($column, ['type' => 'bigint'] + (array) $params);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function medium_int($column, $params = [])
    {
        if (is_numeric($params)) {
            $params = ['length' => $params];
        }
        return $this->int($column, ['type' => 'mediumint'] + (array) $params);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function tiny_int($column, $params = [])
    {
        if (is_numeric($params)) {
            $params = ['length' => $params];
        }
        return $this->int($column, ['type' => 'tinyint'] + (array) $params);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function small_int($column, $params = [])
    {
        if (is_numeric($params)) {
            $params = ['length' => $params];
        }
        return $this->int($column, ['type' => 'smallint'] + (array) $params);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function unsigned_int($column, $params = [])
    {
        if (is_numeric($params)) {
            $params = ['length' => $params];
        }
        return $this->int($column, ['unsigned' => true] + (array) $params);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function unsigned_big_int($column, $params = [])
    {
        if (is_numeric($params)) {
            $params = ['length' => $params];
        }
        return $this->big_int($column, ['unsigned' => true] + (array) $params);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function float($column, $params = [])
    {
        return $this->add_column($column, (array) $params + [
            'type' => 'float',
        ]);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function double($column, $params = [])
    {
        return $this->add_column($column, (array) $params + [
            'type' => 'double',
        ]);
    }

    /**
     * @param mixed $column
     * @param mixed $length
     * @param mixed $decimals
     * @param mixed $params
     */
    public function decimal($column, $length = 8, $decimals = 2, $params = [])
    {
        return $this->add_column($column, (array) $params + [
            'type' => 'decimal',
            'length' => $length,
            'decimals' => $decimals,
        ]);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function boolean($column, $params = [])
    {
        return $this->add_column($column, (array) $params + [
            'type' => 'tinyint',
            'length' => 1,
        ]);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function binary($column, $params = [])
    {
        return $this->add_column($column, (array) $params + [
            'type' => 'binary',
        ]);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function enum($column, array $allowed, $params = [])
    {
        return $this->add_column($column, (array) $params + [
            'type' => 'enum',
            'values' => $allowed,
        ]);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function date($column, $params = [])
    {
        return $this->add_column($column, (array) $params + [
            'type' => 'date',
        ]);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function date_time($column, $params = [])
    {
        return $this->add_column($column, (array) $params + [
            'type' => 'datetime',
        ]);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function time($column, $params = [])
    {
        return $this->add_column($column, (array) $params + [
            'type' => 'time',
        ]);
    }

    /**
     * @param mixed $column
     * @param mixed $params
     */
    public function timestamp($column, $params = [])
    {
        return $this->add_column($column, (array) $params + [
            'type' => 'timestamp',
        ]);
    }

    /**
     * @param mixed $params
     */
    public function nullable_timestamps($params = [])
    {
        $this->timestamp('created_at', ['nullable' => true] + (array) $params);
        $this->timestamp('updated_at', ['nullable' => true] + (array) $params);
        return $this;
    }

    /**
     * @param mixed $params
     */
    public function timestamps($params = [])
    {
        $this->timestamp('created_at', $params);
        $this->timestamp('updated_at', $params);
        return $this;
    }

    /**
     * @param mixed $params
     */
    public function soft_deletes($params = [])
    {
        return $this->timestamp('deleted_at', ['nullable' => true] + (array) $params);
    }

    /**
     * Rename current table.
     * @param mixed $to
     */
    public function rename($to)
    {
        $this->utils->rename_table($this->_get_table_name(), $this->_get_table_name($to));
        return $this;
    }

    /**
     * @param mixed $column
     */
    public function add_column($column, array $params)
    {
        $this->fields[$column] = $params;
        return $this;
    }

    /**
     * @param mixed $columns
     */
    public function drop_column($columns)
    {
        if ( ! is_array($columns)) {
            $columns = [$columns];
        }
        foreach ($columns as $column) {
            $this->utils->drop_column($this->_get_table_name(), $column);
        }
        return $this;
    }

    /**
     * @param mixed $from
     * @param mixed $to
     */
    public function rename_column($from, $to)
    {
        $this->utils->rename_column($this->_get_table_name(), $from, $to);
        return $this;
    }


    public function drop_timestamps()
    {
        $this->drop_column('created_at');
        $this->drop_column('updated_at');
        return $this;
    }


    public function drop_soft_deletes()
    {
        $this->drop_column('deleted_at');
        return $this;
    }

    /**
     * @param mixed $columns
     * @param null|mixed $name
     * @param mixed $params
     */
    public function primary($columns, $name = null, $params = [])
    {
        if (is_string($columns)) {
            $columns = [$columns => $columns];
        }
        if ( ! $name) {
            $name = 'PRIMARY';
        }
        if ($this->for_create) {
            $this->indexes[$name] = [
                'type' => 'primary',
                'columns' => $columns,
            ];
        } else {
            $this->utils->add_index($this->_get_table_name(), $name, $columns, ['type' => 'primary'] + (array) $params);
        }
        return $this;
    }

    /**
     * @param mixed $columns
     * @param null|mixed $name
     * @param mixed $params
     */
    public function unique($columns, $name = null, $params = [])
    {
        if (is_string($columns)) {
            $columns = [$columns => $columns];
        }
        if ( ! $name) {
            $name = 'uniq_' . implode('_', $columns);
        }
        if ($this->for_create) {
            $this->indexes[$name] = [
                'type' => 'unique',
                'columns' => $columns,
            ];
        } else {
            $this->utils->add_index($this->_get_table_name(), $name, $columns, ['type' => 'unique'] + (array) $params);
        }
        return $this;
    }

    /**
     * @param mixed $columns
     * @param null|mixed $name
     * @param mixed $params
     */
    public function index($columns, $name = null, $params = [])
    {
        if (is_string($columns)) {
            $columns = [$columns => $columns];
        }
        if ( ! $name) {
            $name = implode('_', $columns);
        }
        if ($this->for_create) {
            $this->indexes[$name] = [
                'type' => 'index',
                'columns' => $columns,
            ];
        } else {
            $this->utils->add_index($this->_get_table_name(), $name, $columns, $params);
        }
        return $this;
    }

    /**
     * @param mixed $columns
     * @param mixed $ref_table
     * @param mixed $ref_columns
     * @param null|mixed $name
     * @param mixed $params
     */
    public function foreign($columns, $ref_table, $ref_columns, $name = null, $params = [])
    {
        if (is_string($columns)) {
            $columns = [$columns => $columns];
        }
        if (is_string($ref_columns)) {
            $ref_columns = [$ref_columns => $ref_columns];
        }
        if ( ! $name) {
            $name = $ref_table . '_' . implode('_', $ref_columns);
        }
        if ($this->for_create) {
            $this->foreign_keys[$name] = [
                'columns' => $columns,
                'ref_table' => $ref_table,
                'ref_columns' => $ref_columns,
            ] + (array) $params;
        } else {
            $this->utils->add_foreign_key($this->_get_table_name(), $name, $columns, $this->_get_table_name($ref_table), $ref_columns, $params);
        }
        return $this;
    }

    /**
     * @param null|mixed $name
     */
    public function drop_primary($name = null)
    {
        if ( ! $name) {
            $name = 'PRIMARY';
        }
        $this->utils->drop_index($this->_get_table_name(), $name);
        return $this;
    }

    /**
     * @param mixed $name
     */
    public function drop_unique($name)
    {
        $this->utils->drop_index($this->_get_table_name(), $name);
        return $this;
    }

    /**
     * @param mixed $name
     */
    public function drop_index($name)
    {
        $this->utils->drop_index($this->_get_table_name(), $name);
        return $this;
    }

    /**
     * @param mixed $name
     */
    public function drop_foreign($name)
    {
        $this->utils->drop_foreign_key($this->_get_table_name(), $name);
        return $this;
    }

    /**
     * @param mixed $name
     * @param mixed $value
     */
    public function option($name, $value)
    {
        $this->table_options[$name] = $value;
        return $this;
    }
}
