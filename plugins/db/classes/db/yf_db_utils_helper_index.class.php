<?php


class yf_db_utils_helper_index
{
    protected $utils = null;
    protected $db_name = '';
    protected $table = '';
    protected $index = '';

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
        return $this->utils->index_exists($this->db_name . '.' . $this->table, $this->index, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function info($extra = [], &$error = false)
    {
        return $this->utils->index_info($this->db_name . '.' . $this->table, $this->index, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function drop($extra = [], &$error = false)
    {
        return $this->utils->drop_index($this->db_name . '.' . $this->table, $this->index, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function add(array $data, $extra = [], &$error = false)
    {
        return $this->utils->add_index($this->db_name . '.' . $this->table, $this->index, $data, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function update(array $data, $extra = [], &$error = false)
    {
        return $this->utils->update_index($this->db_name . '.' . $this->table, $this->index, $data, $extra, $error);
    }
}
