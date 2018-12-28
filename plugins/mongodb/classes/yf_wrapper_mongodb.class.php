<?php

/**
 * MongoDB API wrapper.
 */
class yf_wrapper_mongodb
{
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
        $this->host = getenv('MONGODB_HOST') ?: conf('MONGODB_HOST') ?: @constant('MONGODB_HOST') ?: '127.0.0.1';
        $this->port = getenv('MONGODB_PORT') ?: conf('MONGODB_PORT') ?: @constant('MONGODB_PORT') ?: 27017;
    }


    public function is_ready()
    {
        // TODO
    }

    /**
     * @param mixed $params
     */
    public function connect($params = [])
    {
        // TODO
    }

    /**
     * @param mixed $key
     */
    public function get($key)
    {
        return $this->connection->get($key);
    }

    /**
     * @param mixed $key
     * @param mixed $val
     */
    public function set($key, $val)
    {
        return $this->connection->set($key, $val);
    }
}
