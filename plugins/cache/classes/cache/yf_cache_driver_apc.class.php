<?php

load('cache_driver', '', 'classes/cache/');
class yf_cache_driver_apc extends yf_cache_driver
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


    public function is_ready()
    {
        return function_exists('apt_fetch');
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
        $result = apc_fetch($name);
        if (is_string($result)) {
            $try_unpack = unserialize($result);
            if ($try_unpack || substr($result, 0, 2) == 'a:') {
                $result = $try_unpack;
            }
            if ($result === 'false') {
                $result = false;
            }
        }
        return $result;
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
        if ($data === false) {
            $data = 'false';
        }
        return apc_store($name, $data, $ttl);
    }

    /**
     * @param mixed $name
     */
    public function del($name)
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        return apc_delete($name);
    }


    public function flush()
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        return apc_clear_cache() && apc_clear_cache('user');
    }


    public function stats()
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        $info = apc_cache_info();
        $sma = apc_sma_info();
        return [
            'hits' => isset($info['num_hits']) ? $info['num_hits'] : $info['nhits'],
            'misses' => isset($info['num_misses']) ? $info['num_misses'] : $info['nmisses'],
            'uptime' => isset($info['start_time']) ? $info['start_time'] : $info['stime'],
            'mem_usage' => $info['mem_size'],
            'mem_avail' => $sma['avail_mem'],
        ];
    }
}
