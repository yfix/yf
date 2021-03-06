<?php

load('pubsub_driver', '', 'classes/pubsub/');
class yf_pubsub_driver_rabbitmq extends yf_pubsub_driver
{
    private $_is_connection = null;
    private $_connection = null;


    public function _init()
    {
        $this->connect();
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

    /**
     * @param mixed $params
     */
    public function conf($params = [])
    {
        ! $this->_is_connection && $this->connect();
        $this->_connection->conf($params);
        return $this;
    }

    /**
     * @param mixed $params
     */
    public function connect($params = [])
    {
        if ( ! $this->_is_connection) {
            $override = [
                'RABBITMQ_HOST' => $this->_get_conf('RABBITMQ_PUBSUB_HOST'),
                'RABBITMQ_PORT' => $this->_get_conf('RABBITMQ_PUBSUB_PORT'),
                'RABBITMQ_PREFIX' => $this->_get_conf('RABBITMQ_PUBSUB_PREFIX'),
                'RABBITMQ_VHOST' => $this->_get_conf('RABBITMQ_PUBSUB_VHOST'),
                'RABBITMQ_USER' => $this->_get_conf('RABBITMQ_PUBSUB_USER'),
                'RABBITMQ_PASSWORD' => $this->_get_conf('RABBITMQ_PUBSUB_PASSWORD'),
            ];
            $this->_connection = rabbitmq($params);
            $this->_connection->connect($override);
        }
        return $this->_is_connection;
    }


    public function is_ready()
    {
        ! $this->_is_connection && $this->connect();
        return (bool) $this->_connection;
    }

    /**
     * @param mixed $channel
     * @param mixed $what
     */
    public function pub($channel, $what)
    {
        return $this->_connection->pub($channel, $what);
    }

    /**
     * @param mixed $channels
     * @param mixed $callback
     */
    public function sub($channels, $callback)
    {
        return $this->_connection->sub($channels, $callback);
    }
}
