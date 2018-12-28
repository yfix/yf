<?php

/**
 * Core queue wrapper.
 */
class yf_wrapper_queue
{
    public $driver = 'redis';
    public $_connection = null;

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        ! $this->_connection && $this->connect();
        // Support for driver-specific methods
        if (is_object($this->_connection) && method_exists($this->_connection, $name)) {
            return call_user_func_array([$this->_connection, $name], $args);
        }
        return main()->extend_call($this, $name, $args);
    }

    /**
     * Do connect to the low level driver.
     */
    public function connect()
    {
        if ( ! $this->_connection) {
            $this->_connection = _class('queue_driver_' . $this->driver, 'classes/queue/');
            $this->_connection->connect();
        }
        return $this->_connection;
    }

    /**
     * Check if queue system is ready for processing.
     */
    public function is_ready()
    {
        ! $this->_connection && $this->connect();
        return (bool) $this->_connection;
    }

    /**
     * Add new item into named queue.
     * @param mixed $queue
     * @param mixed $what
     */
    public function add($queue, $what)
    {
        ! $this->_connection && $this->connect();
        return $this->_connection->add($queue, $what);
    }

    /**
     * Get one item from named queue (dequeue).
     * @param mixed $queue
     */
    public function get($queue)
    {
        ! $this->_connection && $this->connect();
        return $this->_connection->get($queue);
    }

    /**
     * Delete one item from the queue.
     * @param mixed $queue
     */
    public function del($queue = false)
    {
        ! $this->_connection && $this->connect();
        return $this->_connection->del($queue);
    }

    /**
     * Return all data from the queue, but not ewmo (for debug purposes).
     * @param mixed $queue
     */
    public function all($queue)
    {
        ! $this->_connection && $this->connect();
        return $this->_connection->all($queue);
    }

    /**
     * Configure driver.
     * @param mixed $params
     */
    public function conf($params = [])
    {
        ! $this->_connection && $this->connect();
        return $this->_connection->conf($params);
    }

    /**
     * Listen to queue.
     * @param mixed $qname
     * @param mixed $callback
     * @param mixed $params
     */
    public function listen($qname, $callback, $params = [])
    {
        if ( ! $this->is_ready()) {
            return false;
        }
        while (true) {
            $data = $this->get($qname);
            if ($data) {
                $callback($data, $params);
            }
            usleep($params['usleep'] ?: 100000);
        }
    }
}
