<?php

load('queue_driver', '', 'classes/queue/');
class yf_queue_driver_redis extends yf_queue_driver
{
    public $_connection = null;

    /**
     * @param mixed $name
     * @param null|mixed $default
     */
    public function _get_conf($name, $default = null, array $params = [])
    {
        if (isset($params[$name])) {
            return $params[$name];
        }
        $from_env = getenv($name);
        if ($from_env !== false) {
            return $from_env;
        }
        global $CONF;
        if (isset($CONF[$name])) {
            $from_conf = $CONF[$name];
            return $from_conf;
        }
        if (defined($name) && ($val = constant($name)) != $name) {
            return $val;
        }
        return $default;
    }

    /**
     * @param mixed $params
     */
    public function conf($params = [])
    {
        ! $this->_connection && $this->connect();
        $this->_connection->conf($params);
        return $this;
    }


    public function reconnect()
    {
        $this->_connection && $this->_connection->reconnect();
    }

    /**
     * @param mixed $options
     */
    public function connect($options = [])
    {
        if ( ! $this->_connection) {
            if ( ! $options) {
                $options = [
                    'REDIS_HOST' => $this->_get_conf('REDIS_QUEUE_HOST'),
                    'REDIS_PORT' => $this->_get_conf('REDIS_QUEUE_PORT'),
                    'REDIS_PREFIX' => $this->_get_conf('REDIS_QUEUE_PREFIX'),
                ];
            }
            $this->_connection = redis()->factory($options);
            $this->_connection->connect();
        }
        if ( ! $this->_connection->is_connection()) {
            $this->reconnect();
        }
        return $this->_connection;
    }


    public function is_ready()
    {
        ! $this->_connection && $this->connect();
        return (bool) $this->_connection;
    }

    /**
     * @param mixed $queue
     * @param mixed $what
     */
    public function add($queue, $what)
    {
        ! $this->_connection && $this->connect();
        return $this->_connection->lpush($queue, $what);
    }

    /**
     * @param mixed $queue
     */
    public function get($queue)
    {
        ! $this->_connection && $this->connect();
        return $this->_connection->rpop($queue);
    }

    /**
     * @param mixed $queue
     */
    public function del($queue)
    {
        ! $this->_connection && $this->connect();
        return $this->_connection->lrem($queue, 1);
    }

    /**
     * @param mixed $queue
     */
    public function all($queue)
    {
        ! $this->_connection && $this->connect();
        return $this->_connection->lrange($queue, 0, -1);
    }
}
