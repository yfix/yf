<?php


class yf_db_utils_helper_column
{
    protected $utils = null;
    protected $db_name = '';
    protected $table = '';
    protected $col = '';

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
     * @param mixed $extra
     */
    public function exists($extra = [], &$error = false)
    {
        return $this->utils->column_exists($this->db_name . '.' . $this->table, $this->col, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function info($extra = [], &$error = false)
    {
        return $this->utils->column_info($this->db_name . '.' . $this->table, $this->col, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function drop($extra = [], &$error = false)
    {
        return $this->utils->drop_column($this->db_name . '.' . $this->table, $this->col, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function add(array $data, $extra = [], &$error = false)
    {
        return $this->utils->add_column($this->db_name . '.' . $this->table, $this->col, $data, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function alter(array $data, $extra = [], &$error = false)
    {
        return $this->utils->alter_column($this->db_name . '.' . $this->table, $this->col, $data, $extra, $error);
    }

    /**
     * @param mixed $new_name
     * @param mixed $extra
     */
    public function rename($new_name, $extra = [], &$error = false)
    {
        return $this->utils->rename_column($this->db_name . '.' . $this->table, $this->col, $new_name, $extra, $error);
    }
}
