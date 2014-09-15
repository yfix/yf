<?php

/*
use Riak\Bucket;
use Riak\Connection;
use Riak\Input;
use Riak\Exception;
use Riak\Object;
*/

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_riak extends yf_cache_driver {

	const EXPIRES_HEADER = 'X-Riak-Meta-Expires';

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
// TODO
		return false;
	}

	/**
	*/
	function get($name, $ttl = 0, $params = array()) {
		if (!$this->is_ready()) {
			return null;
		}
		try {
			$response = $this->bucket->get($name);
			// No objects found
			if ( ! $response->hasObject()) {
				return false;
			}
			// Check for attempted siblings
			$object = ($response->hasSiblings())
				? $this->resolve_conflict($name, $response->getVClock(), $response->getObjectList())
				: $response->getFirstObject();
			// Check for expired object
			if ($this->is_expired($object)) {
				$this->bucket->delete($object);
				return false;
			}
			return unserialize($object->getContent());
		} catch (Exception\RiakException $e) {
			// Covers:
			// - Riak\ConnectionException
			// - Riak\CommunicationException
			// - Riak\UnexpectedResponseException
			// - Riak\NotFoundException
		}
		return false;
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
		try {
			$object = new Object($name);
			$object->setContent(serialize($data));
			if ($ttl > 0) {
				$object->addMetadata(self::EXPIRES_HEADER, (string) (time() + $ttl));
			}
			$this->bucket->put($object);
			return true;
		} catch (Exception\RiakException $e) {
			// Do nothing
		}
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		try {
			$this->bucket->delete($name);
			return true;
		} catch (Exception\BadArgumentsException $e) {
			// Key did not exist on cluster already
		} catch (Exception\RiakException $e) {
			// Covers:
			// - Riak\Exception\ConnectionException
			// - Riak\Exception\CommunicationException
			// - Riak\Exception\UnexpectedResponseException
		}
		return false;
	}

	/**
	*/
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
		try {
			$keyList = $this->bucket->getKeyList();
			foreach ($keyList as $key) {
				$this->bucket->delete($key);
			}
			return true;
		} catch (Exception\RiakException $e) {
			// Do nothing
		}
		return false;
	}

	/**
	*/
	protected function stats() {
		// Only exposed through HTTP stats API, not Protocol Buffers API
		return null;
	}

	/**
	 * Check if a given Riak Object have expired.
	 *
	 * @param \Riak\Object $object
	 *
	 * @return boolean
	 */
	private function is_expired(Object $object) {
		$metadataMap = $object->getMetadataMap();
		return isset($metadataMap[self::EXPIRES_HEADER]) && $metadataMap[self::EXPIRES_HEADER] < time();
	}

	/**
	 * On-read conflict resolution. Applied approach here is last write wins.
	 * Specific needs may override this method to apply alternate conflict resolutions.
	 *
	 * {@internal Riak does not attempt to resolve a write conflict, and store
	 * it as sibling of conflicted one. By following this approach, it is up to
	 * the next read to resolve the conflict. When this happens, your fetched
	 * object will have a list of siblings (read as a list of objects).
	 * In our specific case, we do not care about the intermediate ones since
	 * they are all the same read from storage, and we do apply a last sibling
	 * (last write) wins logic.
	 * If by any means our resolution generates another conflict, it'll up to
	 * next read to properly solve it.}
	 *
	 * @param string $id
	 * @param string $vClock
	 * @param array  $objectList
	 *
	 * @return \Riak\Object
	 */
	protected function resolveConflict($id, $vClock, array $objectList) {
		// Our approach here is last-write wins
		$winner = $objectList[count($objectList)];

		$putInput = new Input\PutInput();
		$putInput->setVClock($vClock);

		$mergedObject = new Object($id);
		$mergedObject->setContent($winner->getContent());

		$this->bucket->put($mergedObject, $putInput);

		return $mergedObject;
	}
}
