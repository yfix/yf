<?php

load('cache_driver', '', 'classes/cache/');
class yf_cache_driver_tmp extends yf_cache_driver
{
    public $storage = [];
    protected $hits = 0;
    protected $misses = 0;

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        trigger_error(__CLASS__ . ': No method ' . $name, E_USER_WARNING);
        return false;
    }


    public function __clone()
    {
        $this->storage = [];
    }


    public function is_ready()
    {
        return true;
    }

    /**
     * @param mixed $name
     * @param mixed $ttl
     * @param mixed $params
     */
    public function get($name, $ttl = 0, $params = [])
    {
        if (isset($this->storage[$name])) {
            $this->_hits++;
        } else {
            $this->_misses++;
        }
        return $this->storage[$name];
    }

    /**
     * @param mixed $name
     * @param mixed $data
     * @param mixed $ttl
     */
    public function set($name, $data, $ttl = 0)
    {
        $this->storage[$name] = $data;
        return true;
    }

    /**
     * @param mixed $name
     */
    public function del($name)
    {
        unset($this->storage[$name]);
        return true;
    }


    public function flush()
    {
        $this->storage = [];
        return true;
    }


    public function list_keys()
    {
        return array_keys($this->storage);
    }


    public function stats()
    {
        return [
            'hits' => $this->hits,
            'misses' => $this->misses,
            'uptime' => null,
            'mem_usage' => memory_get_usage(),
            'mem_avail' => null,
        ];
    }
}
