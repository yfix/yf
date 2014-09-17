<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_mongodb extends yf_cache_driver {

	const DATA_FIELD = 'd';
	const EXPIRATION_FIELD = 'e';

	/** @var object internal @conf_skip */
	public $_connection = null;
	/** @var boo; internal @conf_skip */
	public $_connected_ok = false;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		// Support for driver-specific methods
		if (is_object($this->_connected) && method_exists($this->_connected, $name)) {
			return call_user_func_array(array($this->_connected, $name), $args);
		}
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function _init() {
// TODO
	}

	/**
	*/
	function is_ready() {
		return false;
// TODO
	}

	/**
	*/
	function get($name, $ttl = 0, $params = array()) {
		if (!$this->is_ready()) {
			return null;
		}
		$document = $this->_connection->findOne(array('_id' => $name), array(self::DATA_FIELD, self::EXPIRATION_FIELD));
		if ($document === null) {
			return false;
		}
		if ($this->is_expired($document)) {
			$this->del($name);
			return false;
		}
		return unserialize($document[self::DATA_FIELD]->bin);
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
		$result = $this->_connection->update(
			array('_id' => $name),
			array('$set' => array(
				self::EXPIRATION_FIELD => ($ttl > 0 ? new MongoDate(time() + $ttl) : null),
				self::DATA_FIELD => new MongoBinData(serialize($data), MongoBinData::BYTE_ARRAY),
			)),
			array('upsert' => true, 'multiple' => false)
		);
		return isset($result['ok']) ? $result['ok'] == 1 : true;
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		$result = $this->_connection->remove(array('_id' => $name));
		return isset($result['n']) ? $result['n'] == 1 : true;
	}

	/**
	*/
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
		// Use remove() in lieu of drop() to maintain any collection indexes
		$result = $this->_connection->remove();
		return isset($result['ok']) ? $result['ok'] == 1 : true;
	}

	/**
	*/
	protected function stats() {
		if (!$this->is_ready()) {
			return null;
		}
		$serverStatus = $this->_connection->db->command(array(
			'serverStatus' => 1,
			'locks' => 0,
			'metrics' => 0,
			'recordStats' => 0,
			'repl' => 0,
		));
		$collStats = $this->_connection->db->command(array('collStats' => 1));
		return array(
			'hits' 		=> null,
			'misses'	=> null,
			'uptime'	=> (isset($serverStatus['uptime']) ? (integer) $serverStatus['uptime'] : null),
			'mem_usage' => (isset($collStats['size']) ? (integer) $collStats['size'] : null),
			'mem_avail'	=> null,
		);
	}

	/**
	 * Check if the document is expired.
	 *
	 * @param array $document
	 * @return boolean
	 */
	private function is_expired(array $document) {
		return isset($document[self::EXPIRATION_FIELD]) &&
			$document[self::EXPIRATION_FIELD] instanceof MongoDate &&
			$document[self::EXPIRATION_FIELD]->sec < time();
	}
}
