<?php

/**
 * Redis API wrapper.
 */
class yf_wrapper_redis
{
    public $name = 'REDIS';    // instance name
    public $driver = 'phpredis'; // predis|phpredis

    public $client = null;

    public $is_clone = false;

    public $config_default = [
        'database' => 0,
        'host' => '127.0.0.1',
        'port' => 6379,
        'prefix' => '',
        'timeout' => 0,
        'retry_interval' => 100,
        'read_timeout' => -1,
    ];
    public $config = [];
    public $is_config = false;

    public $call_try = 3;
    public $call_delay = 1000000; // msec

    public $log = [];
    public $LOG_LIMIT = 1000;

    public $read_timeout = null;

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        if (DEBUG_MODE) {
            $time_start = microtime(true);
        }
        ! $this->is_connection() && $this->reconnect();
        // Support for driver-specific methods
        if (is_object($this->client) && method_exists($this->client, $name)) {
            $result = $this->call_try($name, $args);
        } else {
            $result = main()->extend_call($this, $name, $args);
        }
        if (DEBUG_MODE) {
            $this->_query_log($name, $args, $result, microtime(true) - $time_start);
        }
        return $result;
    }


    public function __clone()
    {
        $this->is_clone = true;
        $this->client = null;
        $this->config = [];
        $this->is_config = false;
    }

    public function _init()
    {
        $this->load_config();
    }

    public function call_try($name, $args)
    {
        $result = null;
        $call_try = $this->call_try;
        while ($call_try > 0) {
            try {
                $result = call_user_func_array([$this->client, $name], $args);
                $is_retry = false;
            } catch (RedisException $e) {
                // read timeout
                if ($e->getCode() === 0) {
                    $is_retry = false;
                }
            } catch (Exception $e) {
                $is_retry = true;
            }
            if ( ! $is_retry) {
                break;
            }
            --$call_try;
            usleep($this->call_delay);
            $this->reconnect();
        }
        return $result;
    }


    public function new_client()
    {
        $client = null;
        if ($this->driver == 'phpredis') {
            $client = new Redis();
        } elseif ($this->driver == 'predis') {
            require_php_lib('predis');
            $_config = &$this->config;
            $config = [
                'scheme' => 'tcp',
                'host' => $_config['host'],
                'port' => (int) $_config['port'],
                'timeout' => (float) $_config['timeout'],
                'database' => (int) $_config['database'],
            ];
            $_config['prefix'] && $config['prefix'] = $_config['prefix'];
            $_config['read_timeout'] && $config['read_write_timeout'] = (float) $_config['read_timeout'];
            $client = new Predis\Client($config);
        }
        $this->client = &$client;
        return  $client;
    }


    public function is_connection()
    {
        $client = &$this->client;
        $result = is_object($client);
        if ( ! $result) {
            return  $result;
        }
        if ($this->driver == 'phpredis') {
            $result = $client->isConnected();
        } elseif ($this->driver == 'predis') {
            $result = $client->isConnected();
        }
        return  $result;
    }


    public function is_ready()
    {
        return (bool) $this->client;
    }


    public function reconnect()
    {
        $this->disconnect();
        $this->connect();
    }

    public function disconnect()
    {
        if ( ! $this->is_connection()) {
            return  null;
        }
        $client = &$this->client;
        if ($this->driver == 'phpredis') {
            $client->close();
        } elseif ($this->driver == 'predis') {
            $client->disconnect();
        }
        return  true;
    }

    /**
     * @param null|mixed $name
     */
    public function _get_conf_key($name = null)
    {
        $result = $name;
        if ( ! $name || ! $this->name) {
            return  $result;
        }
        $result = strtoupper(implode('_', [$this->name, $name]));
        return  $result;
    }

    /**
     * @param null|mixed $key
     */
    public function _get_conf($key = null, array $options = [])
    {
        // lower
        $k = strtolower($key);
        if ( ! isset($this->config_default[$k])) {
            return  null;
        }
        $default = $this->config_default[$k];
        if (isset($options[$k])) {
            return $options[$k];
        }
        // upper
        $k = strtoupper($key);
        if (isset($options[$k])) {
            return $options[$k];
        }
        // external
        $name = $this->_get_conf_key($key);
        if (isset($options[$name])) {
            return $options[$name];
        }
        // env
        $from_env = getenv($name);
        if ($from_env !== false) {
            return $from_env;
        }
        // conf
        global $CONF;
        if (isset($CONF[$name])) {
            $from_conf = $CONF[$name];
            return $from_conf;
        }
        // constant
        if (defined($name) && ($val = constant($name)) != $name) {
            return $val;
        }
        return $default;
    }


    public function load_config()
    {
        $default = &$this->config_default;
        foreach ($default as $key => $value) {
            $default[$key] = $this->_get_conf($key);
        }
    }

    /**
     * @param mixed $options
     */
    public function set_config($options = [])
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if (@$_name) {
            $this->name = $_name;
        }
        // config
        $config = &$this->config;
        $config['database'] = $this->_get_conf('database', $options);
        $config['host'] = $this->_get_conf('host', $options);
        $config['port'] = $this->_get_conf('port', $options);
        $config['prefix'] = $this->_get_conf('prefix', $options);
        $config['prefix'] = $config['prefix'] ? $config['prefix'] . ':' : '';
        $config['timeout'] = $this->_get_conf('timeout', $options); // float, sec
        $config['retry_interval'] = $this->_get_conf('retry_interval', $options); // int,   msec
        $config['read_timeout'] = $this->_get_conf('read_timeout', $options); // float, sec, for subscribe
    }

    /**
     * @param mixed $options
     */
    public function diff_config($options = [])
    {
        if ( ! $options || ! is_array($options)) {
            return  null;
        }
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if (@$_is_new || @$_is_force) {
            return  true;
        }
        if (@$_name && $_name != $this->name) {
            return  true;
        }
        $config = &$this->config;
        foreach ($this->config_default as $key => $default) {
            $value = null;
            if (isset($options[$key])) {
                $value = $options[$key];
            }
            $name = $this->_get_conf_key($key);
            if (isset($options[$name])) {
                $value = $options[$name];
            }
            if ( ! $value) {
                continue;
            }
            $_value = @$config[$key] ?: $default;
            if ( ! $_value) {
                continue;
            }
            if ($_value !== $value) {
                return  true;
            }
        }
        return  false;
    }

    /**
     * @param mixed $options
     */
    public function factory($options = [])
    {
        if ( ! $options || ! is_array($options)) {
            return  $this;
        }
        $is_diff = $this->diff_config($options);
        if ( ! $is_diff) {
            return  $this;
        }
        if ($this->is_clone) {
            $self = &$this;
        } else {
            $self = clone $this;
        }
        if ( ! $self->is_config) {
            $self->is_config = true;
            $self->set_config($options);
        }
        return  $self;
    }


    public function _connect()
    {
        $redis = &$this->client;
        if ($this->is_connection()) {
            return $redis;
        }
        $config = &$this->config;
        if ($this->driver == 'phpredis') {
            // connect:
            //   host             : string
            //   port             : int,
            //   timeout          : float, value in seconds (optional, default: 0 - unlimited)
            //   reserved         : NULL
            //   retry_interval   : int, value in milliseconds (optional)
            // ? read_timeout     : float, value in seconds (optional, default: 0 - unlimited)
            $redis->connect(
                $config['host'],
                (int) $config['port'],
                (float) $config['timeout'],
                null,
                (int) $config['retry_interval']
            );
            $redis->select((int) $config['database']);
            // after connect, only
            $config['prefix'] && $redis->setOption(Redis::OPT_PREFIX, $config['prefix']);
            $config['read_timeout'] && $redis->setOption(Redis::OPT_READ_TIMEOUT, (float) $config['read_timeout']); // float, sec
        } elseif ($this->driver == 'predis') {
            $redis->connect();
        }
        return $redis;
    }

    /**
     * @param mixed $options
     */
    public function connect($options = [])
    {
        if ( ! $this->client) {
            $self = $this->factory($options);
            $self->new_client();
        } else {
            $self = &$this;
        }
        if ( ! $self->is_config) {
            $self->is_config = true;
            $self->set_config($options);
        }
        return  $self->_connect();
    }

    /**
     * @param mixed $opt
     */
    public function conf($opt = [])
    {
        ! $this->is_connection() && $this->reconnect();
        foreach ((array) $opt as $k => $v) {
            if ($this->driver == 'phpredis') {
                switch ($k) {
                    case Redis::OPT_READ_TIMEOUT:
                        $this->read_timeout = $v;
                        break;
                }
            }
            $this->client->setOption($k, $v);
        }
    }

    /**
     * @param mixed $channel
     * @param mixed $what
     */
    public function pub($channel, $what)
    {
        ! $this->is_connection() && $this->reconnect();
        $result = $this->call_try('publish', [$channel, $what]);
        return  $result;
    }

    /**
     * @param mixed $channels
     * @param mixed $callback
     */
    public function sub($channels, $callback)
    {
        ! $this->is_connection() && $this->reconnect();
        $result = $this->call_try('subscribe', [$channels, $callback]);
        return  $result;
    }

    /**
     * @param mixed $func
     * @param mixed $args
     * @param null|mixed $result
     * @param mixed $exec_time
     */
    public function _query_log($func, $args = [], $result = null, $exec_time = 0.0)
    {
        // Save memory on high number of query log entries
        if ($this->LOG_LIMIT && count((array) $this->log) >= $this->LOG_LIMIT) {
            return false;
        }
        $this->log[] = [
            'func' => $func,
            'args' => $args,
            'result' => $result,
            'exec_time' => round($exec_time, 5),
            'trace' => $this->_trace_string(2),
        ];
        return count((array) $this->log) - 1;
    }

    /**
     * Print nice.
     * @param mixed $from
     * @param mixed $to
     */
    public function _trace_string($from = 1, $to = 1)
    {
        $e = new Exception();
        return implode(PHP_EOL, array_slice(explode(PHP_EOL, $e->getTraceAsString()), $from, -$to));
    }
}
