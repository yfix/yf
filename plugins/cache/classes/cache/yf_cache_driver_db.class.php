<?php

load('cache_driver', '', 'classes/cache/');
class yf_cache_driver_db extends yf_cache_driver
{
    public $table = 'cache';

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        // Support for driver-specific methods
        if (is_object($this->_connection) && method_exists($this->_connection, $name)) {
            return call_user_func_array([$this->_connection, $name], $args);
        }
        return main()->extend_call($this, $name, $args);
    }


    public function _init()
    {
        if ( ! $this->is_ready()) {
            main()->init_db();
            if (is_object(db()) && ! db()->_connected) {
                db()->connect();
            }
        }
    }


    public function is_ready()
    {
        return is_object(db()) && db()->_connected;
    }

    /**
     * @param mixed $name
     * @param mixed $ttl
     * @param mixed $params
     */
    public function get($name, $ttl = 0, $params = [])
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        $ttl = (int) ($ttl ?: $this->_parent->TTL);
        $data = db()->from($this->table)->where('key', '=', $name)->get();
        if ( ! $data || $data['time'] < (time() - $ttl)) {
            return null;
        }
        $val = $data['value'];
        if ($val[0] == '[' || $val[0] == '{') {
            $val = json_decode($val, true);
        }
        return $val;
    }

    /**
     * @param mixed $name
     * @param mixed $data
     * @param mixed $ttl
     */
    public function set($name, $data, $ttl = 0)
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        return db()->replace($this->table, db()->es([
            'key' => $name,
            'value' => is_array($data) || is_object($data) ? json_encode($data) : $data,
            'time' => time(),
        ]));
    }

    /**
     * @param mixed $name
     */
    public function del($name)
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        return db()->query('DELETE FROM ' . db($this->table) . ' WHERE `key`="' . db()->es($name) . '"');
    }


    public function flush()
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        return db()->query('TRUNCATE ' . db($this->table));
    }


    public function list_keys()
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        $data = db()->from($this->table)->get_2d();
        if ( ! $data) {
            return null;
        }
        foreach ($data as &$v) {
            $v = json_decode($v, true);
        }
        return $data;
    }


    public function stats()
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        // TODO: make this database-abstract, not bind hard into mysql SQL
        $stats = db()->get_2d('SHOW GLOBAL STATUS');
        return [
            'hits' => null,
            'misses' => null,
            'uptime' => $stats['Uptime'],
            'mem_usage' => null,
            'mem_avail' => null,
        ];
    }
}
