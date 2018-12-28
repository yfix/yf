<?php

load('pubsub_driver', '', 'classes/pubsub/');
class yf_pubsub_driver_redis extends yf_pubsub_driver
{
    public $_is_connection = null;
    public $_connection_pub = null;
    public $_connection_sub = null;

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
        ! $this->_is_connection && $this->connect();
        $this->_connection_pub->conf($params);
        $this->_connection_sub->conf($params);
        return $this;
    }

    /**
     * @param mixed $params
     */
    public function pub_conf($params = [])
    {
        ! $this->_is_connection && $this->connect();
        $this->_connection_pub->conf($params);
        return $this;
    }

    /**
     * @param mixed $params
     */
    public function sub_conf($params = [])
    {
        ! $this->_is_connection && $this->connect();
        $this->_connection_sub->conf($params);
        return $this;
    }


    public function reconnect_pub()
    {
        $this->_connection_pub->reconnect();
    }


    public function reconnect_sub()
    {
        $this->_connection_sub->reconnect();
    }

    /**
     * @param mixed $options
     */
    public function connect($options = [])
    {
        if ( ! $this->_is_connection) {
            if ( ! $options) {
                $options = [
                    'REDIS_HOST' => $this->_get_conf('REDIS_PUBSUB_HOST'),
                    'REDIS_PORT' => $this->_get_conf('REDIS_PUBSUB_PORT'),
                    'REDIS_PREFIX' => $this->_get_conf('REDIS_PUBSUB_PREFIX'),
                ];
            }
            $this->_connection_pub = redis()->factory($options);
            $options['is_new'] = true;
            $this->_connection_sub = redis()->factory($options);
            $this->_is_connection = true;
        }
        if ( ! $this->_connection_pub->is_connection()) {
            $this->reconnect_pub();
        }
        if ( ! $this->_connection_sub->is_connection()) {
            $this->reconnect_sub();
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
        ! $this->_is_connection && $this->connect();
        return $this->_connection_pub->pub($channel, $what);
    }

    /**
     * @param mixed $channels
     * @param mixed $callback
     */
    public function sub($channels, $callback)
    {
        ! $this->_is_connection && $this->connect();
        return $this->_connection_sub->sub($channels, $callback);
    }
}
