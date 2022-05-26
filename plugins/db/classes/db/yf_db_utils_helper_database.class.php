<?php


class yf_db_utils_helper_database
{
    protected $utils = null;
    protected $db_name = '';

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
     * @param mixed $error
     */
    public function info($extra = [], &$error = false)
    {
        return $this->utils->database_info($this->db_name, $extra, $error);
    }

    /**
     * @param mixed $extra
     * @param mixed $error
     */
    public function exists($extra = [], &$error = false)
    {
        return $this->utils->database_exists($this->db_name, $extra, $error);
    }

    /**
     * @param mixed $extra
     * @param mixed $error
     */
    public function drop($extra = [], &$error = false)
    {
        return $this->utils->drop_database($this->db_name, $extra, $error);
    }

    /**
     * @param mixed $extra
     * @param mixed $error
     */
    public function create(array $data, $extra = [], &$error = false)
    {
        return $this->utils->create_database($this->db_name, $data, $extra, $error);
    }

    /**
     * @param mixed $extra
     * @param mixed $error
     */
    public function alter(array $data, $extra = [], &$error = false)
    {
        return $this->utils->alter_database($this->db_name, $data, $extra, $error);
    }

    /**
     * @param mixed $new_name
     * @param mixed $extra
     * @param mixed $error
     */
    public function rename($new_name, $extra = [], &$error = false)
    {
        return $this->utils->rename_database($this->db_name, $new_name, $extra, $error);
    }

    /**
     * @param mixed $extra
     * @param mixed $error
     */
    public function truncate($extra = [], &$error = false)
    {
        return $this->utils->truncate_database($this->db_name, $extra, $error);
    }

    /**
     * @param mixed $extra
     * @param mixed $error
     */
    public function tables($extra = [], &$error = false)
    {
        return $this->utils->list_tables($this->db_name, $extra, $error);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     * @param mixed $error
     */
    public function table($name, $extra = [], &$error = false)
    {
        return $this->utils->table($this->db_name, $name, $extra, $error);
    }

    /**
     * @param mixed $extra
     * @param mixed $error
     */
    public function views($extra = [], &$error = false)
    {
        return $this->utils->list_views($this->db_name, $extra, $error);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     * @param mixed $error
     */
    public function view($name, $extra = [], &$error = false)
    {
        return $this->utils->view($this->db_name, $name, $extra, $error);
    }

    /**
     * @param mixed $extra
     * @param mixed $error
     */
    public function triggers($extra = [], &$error = false)
    {
        return $this->utils->list_triggers($this->db_name, $extra, $error);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     * @param mixed $error
     */
    public function trigger($name, $extra = [], &$error = false)
    {
        return $this->utils->trigger($this->db_name, $name, $extra, $error);
    }

    /**
     * @param mixed $extra
     * @param mixed $error
     */
    public function events($extra = [], &$error = false)
    {
        return $this->utils->list_events($this->db_name, $extra, $error);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     * @param mixed $error
     */
    public function event($name, $extra = [], &$error = false)
    {
        return $this->utils->event($this->db_name, $name, $extra, $error);
    }
}
