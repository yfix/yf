<?php

/**
* This is the PHP client for memcached - a distributed memory cache daemon.
* More information is available at http://www.danga.com/memcached/
*
* Usage example:
*
* require_once 'memcached_client.class.php';
*
* $mc = new memcached_client(array(
*			  'servers' => array(
*					'127.0.0.1:10000',
*					array('192.0.0.1:10010', 2),
*					'127.0.0.1:10020'),
*			  'debug'	=> false,
*			  'compress_threshold' => 10240,
*			  'persistent' => true
*		));
*
* $mc->add('key', array('some', 'array'));
* $mc->replace('key', 'some random string');
* $val = $mc->get('key');
*
* @author  Ryan T. Dean <rtdean@cytherianage.net>
* @version 0.1.2
*/

/**
* Flag: indicates data is serialized
*/
define("MEMCACHE_SERIALIZED", 1<<0);

/**
* Flag: indicates data is compressed
*/
define("MEMCACHE_COMPRESSED", 1<<1);

/**
* Minimum savings to store data compressed
*/
define("COMPRESSION_SAVINGS", 0.20);

/**
* memcached client class implemented using (p)fsockopen()
*
* @author  Ryan T. Dean <rtdean@cytherianage.net>
* @author  Yuri Vysotskiy <yfix.dev@gmail.com>
*/
class memcached_client {

	/**
	* Command statistics
	* @var	  array
	*/
	public $stats;

	/**
	* Cached Sockets that are connected
	*
	* @var	  array
	*/
	public $_cache_sock;

	/**
	* Current debug status; 0 - none to 9 - profiling
	*
	* @var	  boolean
	*/
	public $_debug;

	/**
	* Dead hosts, assoc array, 'host'=>'unixtime when ok to check again'
	*
	* @var	  array
	*/
	public $_host_dead;

	/**
	* Is compression available?
	*
	* @var	  bool
	*/
	public $_have_zlib;

	/**
	* Do we want to use compression?
	*
	* @var	  bool
	*/
	public $_compress_enable;

	/**
	* At how many bytes should we compress?
	*
	* @var	  int
	*/
	public $_compress_threshold;

	/**
	* Are we using persistent links?
	*
	* @var	  bool
	*/
	public $_persistent;

	/**
	* If only using one server; contains ip:port to connect to
	*
	* @var	  string
	*/
	public $_single_sock;

	/**
	* Array containing ip:port or array(ip:port, weight)
	*
	* @var	  array
	*/
	public $_servers;

	/**
	* Our bit buckets
	*
	* @var	  array
	*/
	public $_buckets;

	/**
	* Total # of bit buckets we have
	*
	* @var	  int
	*/
	public $_bucketcount;

	/**
	* # of total servers we have
	*
	* @var	  int
	*/
	public $_active;

	/**
	* Stream timeout in seconds. Applies for example to fread()
	*
	* @var	  int
	*/
	public $_timeout_seconds;

	/**
	* Stream timeout in microseconds
	*
	* @var	  int
	*/
	public $_timeout_microseconds;

	/**
	* Connect timeout in seconds
	*/
	public $_connect_timeout;

	/**
	* Number of connection attempts for each server
	*/
	public $_connect_attempts;

	/**
	* Constructor (PHP 4.x)
	*
	* @access	public
	* @return	void
	*/
	function memcached_client ($args = array()) {
		return $this->__construct($args);
	}

	/**
	* Constructor (PHP 5.x)
	*
	* @return  mixed
	*/
	function __construct ($args = array()) {
		return $this->init($args);
	}

	/**
	* Constructor (PHP 5.x)
	*
	* @return  mixed
	*/
	function init ($args = array()) {
		if (empty($args)) {
			$args = array();
		}

		$this->stats		= array();
		$this->_cache_sock	= array();
		$this->_host_dead	= array();

		$this->_timeout_seconds			= 1;
		$this->_timeout_microseconds	= 0;
		$this->_connect_timeout			= 0.01;
		$this->_connect_attempts		= 3;
		$this->_compress_enable			= true;

		$this->set_servers(@$args['servers']);

		$this->_debug				= @$args['debug'];
		$this->_compress_threshold	= @$args['compress_threshold'];
		$this->_persistent			= array_key_exists('persistent', $args) ? (@$args['persistent']) : false;
		$this->_have_zlib			= function_exists("gzcompress");
	}

	/**
	*/
	function addServer ($host, $port = 11211, $persistent = false, $weight = null) {
		$this->set_servers(array($host.":".$port, $weight));
/*
		$this->_servers[] = array($host.":".$port, $weight);
		$this->_active = count($this->_servers);
		$this->_buckets = null;
		$this->_bucketcount = 0;

		$this->_single_sock = null;
		if ($this->_active == 1) {
			$this->_single_sock = $this->_servers[0];
		}
*/
		return true;
	}

	/**
	* Adds a key/value to the memcache server if one isn't already set with
	* that key
	*
	* @param	string	$key	  Key to set with data
	* @param	mixed	 $val	  Value to store
	* @param	interger $exp	  (optional) Time to expire data at
	*
	* @return  boolean
	*/
	function add ($key, $val, $exp = 0) {
		return $this->_set('add', $key, $val, $exp);
	}

	/**
	* Decrement a value stored on the memcache server
	*
	* @param	string	$key	  Key to decrement
	* @param	interger $amt	  (optional) Amount to decrement
	*
	* @return  mixed	 FALSE on failure, value on success
	*/
	function decr ($key, $amt = 1) {
		return $this->_incrdecr('decr', $key, $amt);
	}

	/**
	* Deletes a key from the server, optionally after $time
	*
	* @param	string	$key	  Key to delete
	* @param	interger $time	 (optional) How long to wait before deleting
	*
	* @return  boolean  TRUE on success, FALSE on failure
	*/
	function delete ($key, $time = 0) {
		if (!$this->_active)
			return false;

		$sock = $this->get_sock($key);
		if (!is_resource($sock))
			return false;

		$key = is_array($key) ? $key[1] : $key;

		@$this->stats['delete']++;
		$cmd = "delete $key $time\r\n";

		if (!$this->_safe_fwrite($sock, $cmd, strlen($cmd)))	{
			$this->_dead_sock($sock);
			return false;
		}
		$res = trim(fgets($sock));

		if ($this->_debug)
			$this->_debugprint(sprintf("MemCache: delete %s (%s)\n", $key, $res));

		if ($res == "DELETED")
			return true;
		return false;
	}

	/**
	* Disconnects all connected sockets
	*/
	function disconnect_all () {
		foreach ((array)$this->_cache_sock as $sock)
			fclose($sock);

		$this->_cache_sock = array();
	}

	/**
	* Enable / Disable compression
	*
	* @param	boolean  $enable  TRUE to enable, FALSE to disable
	*/
	function enable_compress ($enable) {
		$this->_compress_enable = $enable;
	}

	/**
	* Forget about all of the dead hosts
	*/
	function forget_dead_hosts () {
		$this->_host_dead = array();
	}

	/**
	* Retrieves the value associated with the key from the memcache server
	*
	* @param  string	$key	  Key to retrieve
	*
	* @return  mixed
	*/
	function get ($key)	{
		if (!$this->_active) {
			return false;
		}

		$sock = $this->get_sock($key);

		if (!is_resource($sock)) {
			return false;
		}

		@$this->stats['get']++;

		$cmd = "get $key\r\n";
		if (!$this->_safe_fwrite($sock, $cmd, strlen($cmd))) {
			$this->_dead_sock($sock);
			return false;
		}

		$val = array();
		$this->_load_items($sock, $val);

		if ($this->_debug) {
			foreach ((array)$val as $k => $v) {
				$this->_debugprint(@sprintf("MemCache: sock %s got %s => %s\r\n", serialize($sock), $k, $v));
			}
		}

		return @$val[$key];
	}

	/**
	* Get multiple keys from the server(s)
	*
	* @param	array	 $keys	 Keys to retrieve
	*
	* @return  array
	*/
	function get_multi ($keys) {
		if (!$this->_active)
			return false;

		$this->stats['get_multi']++;

		foreach ((array)$keys as $key)	{
			$sock = $this->get_sock($key);
			if (!is_resource($sock)) continue;
			$key = is_array($key) ? $key[1] : $key;
			if (!isset($sock_keys[$sock])) {
				$sock_keys[$sock] = array();
				$socks[] = $sock;
			}
			$sock_keys[$sock][] = $key;
		}

		// Send out the requests
		foreach ((array)$socks as $sock) {
			$cmd = "get";
			foreach ((array)$sock_keys[$sock] as $key) {
				$cmd .= " ". $key;
			}
			$cmd .= "\r\n";

			if ($this->_safe_fwrite($sock, $cmd, strlen($cmd)))	{
				$gather[] = $sock;
			} else {
				$this->_dead_sock($sock);
			}
		}

		// Parse responses
		$val = array();
		foreach ((array)$gather as $sock) {
			$this->_load_items($sock, $val);
		}

		if ($this->_debug)
			foreach ((array)$val as $k => $v)
				$this->_debugprint(sprintf("MemCache: got %s => %s\r\n", $k, $v));

		return $val;
	}

	/**
	* Increments $key (optionally) by $amt
	*
	* @param	string	$key	  Key to increment
	* @param	interger $amt	  (optional) amount to increment
	*
	* @return  interger New key value?
	*/
	function incr ($key, $amt=1) {
		return $this->_incrdecr('incr', $key, $amt);
	}

	/**
	* Overwrites an existing value for key; only works if key is already set
	*
	* @param	string	$key	  Key to set value as
	* @param	mixed	 $value	Value to store
	* @param	interger $exp	  (optional) Experiation time
	*
	* @return  boolean
	*/
	function replace ($key, $value, $exp=0)	{
		return $this->_set('replace', $key, $value, $exp);
	}

	/**
	* Passes through $cmd to the memcache server connected by $sock; returns
	* output as an array (null array if no output)
	*
	* NOTE: due to a possible bug in how PHP reads while using fgets(), each
	*		 line may not be terminated by a \r\n.  More specifically, my testing
	*		 has shown that, on FreeBSD at least, each line is terminated only
	*		 with a \n.  This is with the PHP flag auto_detect_line_endings set
	*		 to false (the default).
	*
	* @param	resource $sock	 Socket to send command on
	* @param	string	$cmd	  Command to run
	*
	* @return  array	 Output array
	*/
	function run_command ($sock, $cmd) {
		if (!is_resource($sock))
			return array();

		if (!$this->_safe_fwrite($sock, $cmd, strlen($cmd)))
			return array();

		while (true)
		{
			$res = fgets($sock);
			$ret[] = $res;
			if (preg_match('/^END/', $res))
				break;
			if (strlen($res) == 0)
				break;
		}
		return $ret;
	}

	/**
	* Unconditionally sets a key to a given value in the memcache.  Returns true
	* if set successfully.
	*
	* @param	string	$key	  Key to set value as
	* @param	mixed	 $value	Value to set
	* @param	interger $exp	  (optional) Experiation time
	*
	* @return  boolean  TRUE on success
	*/
	function set ($key, $value, $flags = 0, $exp = 0) {
		return $this->_set('set', $key, $value, $exp, $flags);
	}

	/**
	* Sets the compression threshold
	*
	* @param	interger $thresh  Threshold to compress if larger than
	*/
	function set_compress_threshold ($thresh) {
		$this->_compress_threshold = $thresh;
	}

	/**
	* Sets the debug flag
	*
	* @param	boolean  $dbg	  TRUE for debugging, FALSE otherwise
	*/
	function set_debug ($dbg) {
		$this->_debug = $dbg;
	}

	/**
	* Sets the server list to distribute key gets and puts between
	*
	* @param	array	 $list	 Array of servers to connect to
	*/
	function set_servers ($list) {
		$this->_servers = $list;
		$this->_active = count($list);
		$this->_buckets = null;
		$this->_bucketcount = 0;

		$this->_single_sock = null;
		if ($this->_active == 1)
			$this->_single_sock = $this->_servers[0];
	}

	/**
	* Sets the timeout for new connections
	*
	* @param	integer  $seconds Number of seconds
	* @param	integer  $microseconds  Number of microseconds
	*/
	function set_timeout ($seconds, $microseconds) {
		$this->_timeout_seconds = $seconds;
		$this->_timeout_microseconds = $microseconds;
	}

	/**
	* Close the specified socket
	*
	* @param	string	$sock	 Socket to close
	*/
	function _close_sock ($sock) {
		$host = array_search($sock, $this->_cache_sock);
		fclose($this->_cache_sock[$host]);
		unset($this->_cache_sock[$host]);
	}

	/**
	* Connects $sock to $host, timing out after $timeout
	*
	* @param	interger $sock	 Socket to connect
	* @param	string	$host	 Host:IP to connect to
	*
	* @return  boolean
	*/
	function _connect_sock (&$sock, $host) {
		list ($ip, $port) = explode(":", $host);
		$sock = false;
		$timeout = $this->_connect_timeout;
		for ($i = 0; !$sock && $i < $this->_connect_attempts; $i++) {
			if ($i > 0) {
				# Sleep until the timeout, in case it failed fast
				$elapsed = microtime(true) - $t;
				if ( $elapsed < $timeout ) {
					usleep(($timeout - $elapsed) * 1e6);
				}
				$timeout *= 2;
			}
			$t = microtime(true);
			if ($this->_persistent == 1) {
				$sock = @pfsockopen($ip, $port, $errno, $errstr, $timeout);
			} else {
				$sock = fsockopen($ip, $port, $errno, $errstr, $timeout);
			}
		}
		if (!$sock) {
			if ($this->_debug)
				$this->_debugprint( "Error connecting to $host: $errstr\n" );
			return false;
		}

		// Initialise timeout
		stream_set_timeout($sock, $this->_timeout_seconds, $this->_timeout_microseconds);

		return true;
	}

	/**
	* Marks a host as dead until 30-40 seconds in the future
	*
	* @param	string	$sock	 Socket to mark as dead
	*/
	function _dead_sock ($sock)	{
		$host = array_search($sock, $this->_cache_sock);
		@list ($ip, $port) = explode(":", $host);
		$this->_host_dead[$ip] = time() + 30 + intval(rand(0, 10));
		$this->_host_dead[$host] = $this->_host_dead[$ip];
		unset($this->_cache_sock[$host]);
	}

	/**
	* get_sock
	*
	* @param	string	$key	  Key to retrieve value for;
	*
	* @return  mixed	 resource on success, false on failure
	*/
	function get_sock ($key) {
		if (!$this->_active)
			return false;

		if ($this->_single_sock !== null) {
			$this->_flush_read_buffer($this->_single_sock);
			return $this->sock_to_host($this->_single_sock);
		}

		$hv = is_array($key) ? intval($key[0]) : $this->_hashfunc($key);

		if ($this->_buckets === null)
		{
			foreach ((array)$this->_servers as $v)
			{
				if (is_array($v))
				{
					for ($i=0; $i<$v[1]; $i++)
						$bu[] = $v[0];
				} else
				{
					$bu[] = $v;
				}
			}
			$this->_buckets = $bu;
			$this->_bucketcount = count($bu);
		}

		$realkey = is_array($key) ? $key[1] : $key;
		for ($tries = 0; $tries<20; $tries++)
		{
			$host = $this->_buckets[$hv % $this->_bucketcount];
			$sock = $this->sock_to_host($host);
			if (is_resource($sock)) {
				$this->_flush_read_buffer($sock);
				return $sock;
			}
			$hv += $this->_hashfunc($tries . $realkey);
		}

		return false;
	}

	/**
	* Creates a hash interger based on the $key
	*
	* @param	string	$key	  Key to hash
	*
	* @return  interger Hash value
	*/
	function _hashfunc ($key) {
		# Hash function must on [0,0x7ffffff]
		# We take the first 31 bits of the MD5 hash, which unlike the hash
		# function used in a previous version of this client, works
		return hexdec(substr(md5($key),0,8)) & 0x7fffffff;
	}

	/**
	* Perform increment/decrement on $key
	*
	* @param	string	$cmd	  Command to perform
	* @param	string	$key	  Key to perform it on
	* @param	interger $amt	  Amount to adjust
	*
	* @return  interger	 New value of $key
	*/
	function _incrdecr ($cmd, $key, $amt=1) {
		if (!$this->_active)
			return null;

		$sock = $this->get_sock($key);
		if (!is_resource($sock))
			return null;

		$key = is_array($key) ? $key[1] : $key;
		@$this->stats[$cmd]++;
		if (!$this->_safe_fwrite($sock, "$cmd $key $amt\r\n"))
			return $this->_dead_sock($sock);

		stream_set_timeout($sock, 1, 0);
		$line = fgets($sock);
		if (!preg_match('/^(\d+)/', $line, $match))
			return null;
		return $match[1];
	}

	/**
	* Load items into $ret from $sock
	*
	* @param	resource $sock	 Socket to read from
	* @param	array	 $ret	  Returned values
	*/
	function _load_items ($sock, &$ret)	{
		while (1)
		{
			$decl = fgets($sock);
			if ($decl == "END\r\n")
			{
				return true;
			} elseif (preg_match('/^VALUE (\S+) (\d+) (\d+)\r\n$/', $decl, $match))
			{
				list($rkey, $flags, $len) = array($match[1], $match[2], $match[3]);
				$bneed = $len+2;
				$offset = 0;

				while ($bneed > 0)
				{
					$data = fread($sock, $bneed);
					$n = strlen($data);
					if ($n == 0)
						break;
					$offset += $n;
					$bneed -= $n;
					@$ret[$rkey] .= $data;
				}

				if ($offset != $len+2)
				{
					// Something is borked!
					if ($this->_debug)
						$this->_debugprint(sprintf("Something is borked!  key %s expecting %d got %d length\n", $rkey, $len+2, $offset));

					unset($ret[$rkey]);
					$this->_close_sock($sock);
					return false;
				}

				if ($this->_have_zlib && $flags & MEMCACHE_COMPRESSED)
					$ret[$rkey] = gzuncompress($ret[$rkey]);

				$ret[$rkey] = rtrim($ret[$rkey]);

				if ($flags & MEMCACHE_SERIALIZED)
					$ret[$rkey] = unserialize($ret[$rkey]);

			} else
			{
				$this->_debugprint("Error parsing memcached response\n");
				return 0;
			}
		}
	}

	/**
	* Performs the requested storage operation to the memcache server
	*
	* @param	string	$cmd	  Command to perform
	* @param	string	$key	  Key to act on
	* @param	mixed	 $val	  What we need to store
	* @param	interger $exp	  When it should expire
	*
	* @return  boolean
	*/
	function _set ($cmd, $key, $val, $exp, $flags) {
		if (!$this->_active)
			return false;

		$sock = $this->get_sock($key);
		if (!is_resource($sock))
			return false;

		@$this->stats[$cmd]++;

		$flags = 0;

		if (!is_scalar($val))
		{
			$val = serialize($val);
			$flags |= MEMCACHE_SERIALIZED;
			if ($this->_debug)
				$this->_debugprint(sprintf("client: serializing data as it is not scalar\n"));
		}

		$len = strlen($val);

		if ($this->_have_zlib && $this->_compress_enable &&
			 $this->_compress_threshold && $len >= $this->_compress_threshold)
		{
			$c_val = gzcompress($val, 9);
			$c_len = strlen($c_val);

			if ($c_len < $len*(1 - COMPRESSION_SAVINGS))
			{
				if ($this->_debug)
					$this->_debugprint(sprintf("client: compressing data; was %d bytes is now %d bytes\n", $len, $c_len));
				$val = $c_val;
				$len = $c_len;
				$flags |= MEMCACHE_COMPRESSED;
			}
		}
		if (!$this->_safe_fwrite($sock, "$cmd $key $flags $exp $len\r\n$val\r\n"))
			return $this->_dead_sock($sock);

		$line = trim(fgets($sock));

		if ($this->_debug)
		{
			if ($flags & MEMCACHE_COMPRESSED)
				$val = 'compressed data';
			$this->_debugprint(sprintf("MemCache: %s %s => %s (%s)\n", $cmd, $key, $val, $line));
		}
		if ($line == "STORED")
			return true;
		return false;
	}

	/**
	* Returns the socket for the host
	*
	* @param	string	$host	 Host:IP to get socket for
	* @return  mixed	 IO Stream or false
	*/
	function sock_to_host ($host) {
		if (isset($this->_cache_sock[$host]))
			return $this->_cache_sock[$host];

		$now = time();
		list ($ip, $port) = explode (":", $host);
		if (isset($this->_host_dead[$host]) && $this->_host_dead[$host] > $now ||
			 isset($this->_host_dead[$ip]) && $this->_host_dead[$ip] > $now)
			return null;

		if (!$this->_connect_sock($sock, $host))
			return $this->_dead_sock($host);

		// Do not buffer writes
		stream_set_write_buffer($sock, 0);

		$this->_cache_sock[$host] = $sock;

		return $this->_cache_sock[$host];
	}

	/**
	* Original behaviour
	*/
	function _safe_fwrite($f, $buf, $len = false) {
		if ($len === false) {
			$bytesWritten = fwrite($f, $buf);
		} else {
			$bytesWritten = fwrite($f, $buf, $len);
		}
		return $bytesWritten;
	}

	/**
	* Flush the read buffer of a stream
	*/
	function _flush_read_buffer($f) {
		if (!is_resource($f)) {
			return;
		}
		$n = stream_select($r=array($f), $w = NULL, $e = NULL, 0, 0);
		while ($n == 1 && !feof($f)) {
			fread($f, 1024);
			$n = stream_select($r=array($f), $w = NULL, $e = NULL, 0, 0);
		}
	}

	/**
	* Get memcache stats
	*/
	function getExtendedStats() {
		if (!$this->_active) {
			return false;
		}
		$sock = $this->get_sock($key);
		if (!is_resource($sock)) {
			return false;
		}
		$cmd = "stats\r\n";
		if (!$this->_safe_fwrite($sock, $cmd, strlen($cmd))) {
			$this->_dead_sock($sock);
			return false;
		}
		$val = array();
		// Do process response;
		while (1) {
			$decl = fgets($sock);
			if ($decl == "END\r\n")	{
				break;
			} elseif (preg_match('/^STAT ([a-z0-9\_]+) ([a-z0-9\_\.]+)\r\n$/', $decl, $match)) {
				list($rkey, $rvalue) = array($match[1], $match[2]);
				$val[$rkey] = trim($rvalue);
			} else {
				$this->_debugprint("Error parsing memcached response\n");
				break;
			}
		}
		if ($this->_debug) {
			foreach ((array)$val as $k => $v) {
				$this->_debugprint(@sprintf("MemCache: sock %s got %s => %s\r\n", serialize($sock), $k, $v));
			}
		}
		return array("127.0.0.1:11211" => @$val);
	}

	/**
	* Placeholder for debug logger
	*/
	function _debugprint($str) {
		print($str);
	}
}
