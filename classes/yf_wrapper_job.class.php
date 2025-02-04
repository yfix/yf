<?php

/**
 * Core jobs wrapper.
 */
class yf_wrapper_job
{
    public $driver = 'redis';
    public $_connection = null;

    public $statuses = [
        'waiting',  // Job is still queued
        'running',  // Job is currently running
        'failed',   // Job has failed
        'complete', // Job is complete
    ];

    // TODO

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

    /**
     * Do connect to the low level driver.
     */
    public function connect()
    {
        if ( ! $this->_connection) {
            $this->_connection = queue()->connect();
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
     * @param mixed $text
     * @param mixed $queue
     */
    public function add($text = false, $queue = false)
    {
        ! $this->_connection && $this->connect();
        return $this->_connection->add($text, $queue);
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
     * Get job status.
     * @param mixed $queue
     * @param mixed $job
     */
    public function status($queue = false, $job = null)
    {
        ! $this->_connection && $this->connect();
        return $this->_connection->del($queue);
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
}
