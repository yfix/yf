<?php

/**
 * CouchBase API wrapper.
 */
class yf_wrapper_couchbase
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
        $this->host = getenv('COUCHBASE_HOST') ?: conf('COUCHBASE_HOST') ?: @constant('COUCHBASE_HOST') ?: '127.0.0.1';
        $this->port = getenv('COUCHBASE_PORT') ?: conf('COUCHBASE_PORT') ?: @constant('COUCHBASE_PORT') ?: 8092;
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
