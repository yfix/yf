<?php

load('cache_driver', '', 'classes/cache/');
class yf_cache_driver_memcache extends yf_cache_driver
{
    /** @var object internal @conf_skip */
    public $_connection = null;

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
        $this->_connection = memcached();
        $this->_connection->connect();
    }


    public function is_ready()
    {
        return $this->_connection && $this->_connection->is_ready();
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
        return $this->_connection->get($name, $ttl, $params);
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
        return $this->_connection->set($name, $data, $ttl);
    }

    /**
     * @param mixed $name
     */
    public function del($name)
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        return $this->_connection->del($name) ?: null;
    }


    public function flush()
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        return $this->_connection->flush() ?: null;
    }


    public function stats()
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        return $this->_connection->stats() ?: null;
    }
}
