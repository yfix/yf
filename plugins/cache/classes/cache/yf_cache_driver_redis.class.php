<?php

load('cache_driver', '', 'classes/cache/');
class yf_cache_driver_redis extends yf_cache_driver
{
    /** @var object internal @conf_skip */
    public $_conf = null;
    public $_connection = null;
    /** @var int */
    public $DEFAULT_TTL = 3600;

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        // Support for driver-specific methods
        if (is_object($this->_connection) && method_exists($this->_connection, $name)) {
            return call_user_func_array([$this->_connection, $name], $args);
        }
        return main()->extend_call($this, $name, $args);
    }

    /**
     * @param mixed $name
     * @param null|mixed $default
     */
    public function _get_conf($name, $default = null, array $params = [])
    {
        if (isset($params[$name]) && $val = $params[$name]) {
            return $val;
        }
        if ($val = getenv($name)) {
            return $val;
        }
        if ($val = conf($name)) {
            return $val;
        }
        if (defined($name) && ($val = constant($name)) != $name) {
            return $val;
        }
        return $default;
    }


    public function reconnect()
    {
        $this->_connection && $this->_connection->reconnect();
    }

    public function connect($options = [])
    {
        if ( ! $this->_connection) {
            if ( ! $options) {
                $options = [
                    'REDIS_HOST' => $this->_get_conf('REDIS_CACHE_HOST'),
                    'REDIS_PORT' => $this->_get_conf('REDIS_CACHE_PORT'),
                    'REDIS_PREFIX' => $this->_get_conf('REDIS_CACHE_PREFIX'),
                ];
            }
            $this->_connection = redis()->factory($options);
            $this->_connection->connect();
        }
        if ( ! $this->_connection->is_connection()) {
            $this->reconnect();
        }
        return  $this->_connection;
    }


    public function is_ready()
    {
        ! $this->_connection && $this->connect();
        return (bool) $this->_connection;
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
        $res = $this->_connection->get($name);
        //		return $res === false || $res === null ? null : $res;
        return $res ? json_decode($res, true) : null;
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
        $data = json_encode($data, JSON_PRETTY_PRINT);
        return $this->_connection->setex($name, $ttl ?: $this->DEFAULT_TTL, $data) ?: null;
    }

    /**
     * @param mixed $name
     */
    public function del($name)
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        return $this->_connection->del($name) > 0 ? true : null;
    }


    public function flush()
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        return $this->_connection->flushDB() ?: null;
    }


    public function stats()
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        $info = $this->_connection->info();
        return [
            'hits' => false,
            'misses' => false,
            'uptime' => $info['uptime_in_seconds'],
            'mem_usage' => $info['used_memory'],
            'mem_avail' => false,
        ];
    }
}
