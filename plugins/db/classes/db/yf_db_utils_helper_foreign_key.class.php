<?php


class yf_db_utils_helper_foreign_key
{
    protected $utils = null;
    protected $db_name = '';
    protected $table = '';
    protected $foreign_key = '';

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
        return $this->utils->foreign_key_exists($this->db_name . '.' . $this->table, $this->foreign_key, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function info($extra = [], &$error = false)
    {
        return $this->utils->foreign_key_info($this->db_name . '.' . $this->table, $this->foreign_key, $extra, $error);
    }

    /**
     * @param mixed $extra
     */
    public function drop($extra = [], &$error = false)
    {
        return $this->utils->drop_foreign_key($this->db_name . '.' . $this->table, $this->foreign_key, $extra, $error);
    }

    /**
     * @param mixed $ref_table
     * @param mixed $extra
     */
    public function add(array $fields, $ref_table, array $ref_fields, $extra = [], &$error = false)
    {
        return $this->utils->add_foreign_key($this->db_name . '.' . $this->table, $this->foreign_key, $fields, $ref_table, $ref_fields, $extra, $error);
    }

    /**
     * @param mixed $ref_table
     * @param mixed $extra
     */
    public function update(array $fields, $ref_table, array $ref_fields, $extra = [], &$error = false)
    {
        return $this->utils->update_foreign_key($this->db_name . '.' . $this->table, $this->foreign_key, $fields, $ref_table, $ref_fields, $extra, $error);
    }
}
