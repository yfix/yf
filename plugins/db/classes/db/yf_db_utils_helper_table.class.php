<?php


class yf_db_utils_helper_table
{
    protected $utils = null;
    protected $db_name = '';
    protected $table = '';

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
        return $this->utils->table_exists($this->db_name . '.' . $this->table, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function info($extra = [], &$error = false)
    {
        return $this->utils->table_info($this->db_name . '.' . $this->table, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function drop($extra = [], &$error = false)
    {
        return $this->utils->drop_table($this->db_name . '.' . $this->table, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function create(array $data, $extra = [], &$error = false)
    {
        return $this->utils->create_table($this->db_name . '.' . $this->table, $data, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function alter(array $data, $extra = [], &$error = false)
    {
        return $this->utils->alter_table($this->db_name . '.' . $this->table, $data, $extra, $error);
    }

    /**
     * @param mixed $new_name
     * @param mixed $extra
     */
    public function rename($new_name, $extra = [], &$error = false)
    {
        return $this->utils->rename_table($this->db_name . '.' . $this->table, $new_name, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function truncate($extra = [], &$error = false)
    {
        return $this->utils->truncate_table($this->db_name . '.' . $this->table, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function check($extra = [], &$error = false)
    {
        return $this->utils->check_table($this->db_name . '.' . $this->table, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function repair($extra = [], &$error = false)
    {
        return $this->utils->repair_table($this->db_name . '.' . $this->table, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function optimize($extra = [], &$error = false)
    {
        return $this->utils->optimize_table($this->db_name . '.' . $this->table, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function columns($extra = [], &$error = false)
    {
        return $this->utils->list_columns($this->db_name . '.' . $this->table, $extra, $error);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function column($name, $extra = [], &$error = false)
    {
        return $this->utils->column($this->db_name, $this->table, $name, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function indexes($extra = [], &$error = false)
    {
        return $this->utils->list_indexes($this->db_name . '.' . $this->table, $extra, $error);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function index($name, $extra = [], &$error = false)
    {
        return $this->utils->index($this->db_name, $this->table, $name, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function foreign_keys($extra = [], &$error = false)
    {
        return $this->utils->list_foreign_keys($this->db_name . '.' . $this->table, $extra, $error);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function foreign_key($name, $extra = [], &$error = false)
    {
        return $this->utils->foreign_key($this->db_name, $this->table, $name, $extra, $error);
    }
}
