<?php

/**
* Relock algorithm http://antirez.com/news/77
* Forked from https://github.com/ronnylt/redlock-php
*/
class yf_wrapper_redlock {

	private $retry_delay;
	private $retry_count;
	private $clock_drift_factor = 0.01;
	private $quorum;
	private $servers = [];
	private $instances = [];

	/**
	*/
	public function _init() {
		$this->setup();
	}

	/**
	*/
	public function setup(array $params = []) {
		$this->servers = $params['servers'] ?: [[
			$this->_get_conf('REDIS_HOST', '127.0.0.1', $params),
			$this->_get_conf('REDIS_PORT', 6379, $params),
			$timeout = 0.01,
		]];
		if ($params['instances']) {
			$this->instances = $params['instances'];
		}
		$this->retry_delay = $params['retry_delay'] ?: 200;
		$this->retry_count = $params['retry_count'] ?: 3;
		$this->quorum  = min(count($this->servers), (count($this->servers) / 2 + 1));
	}

	/**
	*/
	public function lock($resource, $ttl) {
		$this->init_instances();

		$token = uniqid();
		$retry = $this->retry_count;

		do {
			$n = 0;

			$start_time = microtime(true) * 1000;

			foreach ((array)$this->instances as $instance) {
				if ($this->lock_instance($instance, $resource, $token, $ttl)) {
					$n++;
				}
			}

			// Add 2 milliseconds to the drift to account for Redis expires
			// precision, which is 1 millisecond, plus 1 millisecond min drift for small TTLs.
			$drift = ($ttl * $this->clock_drift_factor) + 2;

			$validity_time = $ttl - (microtime(true) * 1000 - $start_time) - $drift;

			if ($n >= $this->quorum && $validity_time > 0) {
				return [
					'validity' => $validity_time,
					'resource' => $resource,
					'token'	=> $token,
				];
			} else {
				foreach ((array)$this->instances as $instance) {
					$this->unlock_instance($instance, $resource, $token);
				}
			}

			// Wait a random delay before to retry
			$delay = mt_rand(floor($this->retry_delay / 2), $this->retry_delay);
			usleep($delay * 1000);

			$retry--;

		} while ($retry > 0);

		return false;
	}

	/**
	*/
	public function unlock(array $lock) {
		$this->init_instances();
		$resource = $lock['resource'];
		$token = $lock['token'];
		foreach ((array)$this->instances as $instance) {
			$this->unlock_instance($instance, $resource, $token);
		}
	}

	/**
	*/
	private function init_instances() {
		if (empty($this->instances)) {
			foreach ((array)$this->servers as $server) {
				list($host, $port, $timeout) = $server;
				$redis = new \Redis();
				$redis->connect($host, $port, $timeout);
				$this->instances[] = $redis;
			}
		}
	}

	/**
	*/
	private function lock_instance($instance, $resource, $token, $ttl) {
		return $instance->set($resource, $token, ['NX', 'PX' => $ttl]);
	}

	/**
	*/
	private function unlock_instance($instance, $resource, $token) {
		$script = '
			if redis.call("GET", KEYS[1]) == ARGV[1] then
				return redis.call("DEL", KEYS[1])
			else
				return 0
			end
		';
		return $instance->eval($script, [$resource, $token], 1);
	}

	/**
	*/
	private function _get_conf($name, $default = null, array $params = []) {
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
}