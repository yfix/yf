<?php

class yf_db_static {

	static private $connection_params = array();
	static private $links = array();

	static function set_params($params) {
		$c = get_called_class();
		self::$connection_params[$c] = $params;
	}
	static function get_params() {
		$c = get_called_class();
		return self::$connection_params[$c];
	}
	static function connect() {
		$c = get_called_class();
		$params = self::$connection_params[$c];
		$link = &self::$links[$c];
		$link = mysql_connect($params["HOST"], $params["USER"], $params["PSWD"], true);
		if ($link) {
			mysql_select_db($params["NAME"], $link);
		}
	}
	static function escape($string) {
		$c = get_called_class();
		$link = self::$links[$c];
		return mysql_real_escape_string($string, $link);
	}
	static function get($sql, $keys = 1) {
		$c = get_called_class();
		$link = &self::$links[$c];
		if (is_null($link)) {
			self::connect();
		}
		if (!$link) {
			return false;
		}
	    $a = array();
		$n = array();
	    $query = mysql_query($sql, $link);
		if (!$query) {
			echo mysql_error($link);
			return false;
		}
	    while ($r = mysql_fetch_assoc($query)) {
			if (!$n) {
				$n = array_keys($r);
			}
	      	if ($keys == 1) {
        		$a[$r[$n[0]]] = $r;
	      	}
	    	if($keys == 2) {
    	    	$a[$r[$n[0]]][$r[$n[1]]] = $r;
		    }
			if ($keys == 3) {
    	    	$a[$r[$n[0]]][$r[$n[1]]][$r[$n[2]]] = $r;
			}
		}
		mysql_free_result($query);
		return $a;
	}
	static function query($sql) {
		$c = get_called_class();
		$link = self::$links[$c];
		$result = mysql_query($sql, $link);
	}
}
