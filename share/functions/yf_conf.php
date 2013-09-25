<?php

if (!function_exists('my_array_merge')) {
	function my_array_merge($a1, $a2) {
		foreach ((array)$a2 as $k => $v) { if (isset($a1[$k]) && is_array($a1[$k])) { if (is_array($a2[$k])) {
			foreach ((array)$a2[$k] as $k2 => $v2) { if (isset($a1[$k][$k1]) && is_array($a1[$k][$k1])) { $a1[$k][$k2] += $v2; } else { $a1[$k][$k2] = $v2; }
		} } else { $a1[$k] += $v; } } else { $a1[$k] = $v; } }
		return $a1;
	}
}

// Abstraction layer for any configuration, that was previously set in GLOBALS arrays. 
// Examples:
// conf('key1'); => get conf data
// conf('key1', 'value1'); => set conf data
// conf('key2', array('k2' => 'v2')); => set conf data array
// conf('key2::k2'); => get conf data subarray item
// conf('key2::k2', 'v2'); => set conf data subarray item
// conf(array('key2' => 'v2','key3' => 'v3')); => set conf data array
if (!function_exists('conf')) {
	function conf ($name = null, $new_value = null) {
		$value = null;
		// If no first params passed - we return whole structure
		if (!isset($name)) {
			return $GLOBALS['CONF'];
		}
		// SET $name as array to merge as key-val
		if (is_array($name)) {
			$V = &$GLOBALS['CONF'];
			foreach ((array)$name as $_key => $_new_val) {
				if (!isset($_new_val)) {
					continue;
				}
				$a = false;
				if (false !== strpos($_key, '::')) {
					$a = explode('::', $_key);
				}
				if (is_array($a) && !empty($a)) {
					$_key = $a[0];
					$c = count($a);
					if ($c == 2) { $V[$_key][$a[1]] = $_new_val; }
					elseif ($c == 3) { $V[$_key][$a[1]][$a[2]] = $_new_val; }
					elseif ($c == 4) { $V[$_key][$a[1]][$a[2]][$a[3]] = $_new_val; }
				} else {
					$V[$_key] = $_new_val;
				}
			}
			return true;
		}
		$a = false;
		if (false !== strpos($name, '::')) {
			$a = explode('::', $name);
		}
		if (is_array($a) && !empty($a)) {
			$name = $a[0];
			$V = &$GLOBALS['CONF'][$name];
			$c = count($a);
			if ($c == 2 && isset($V[$a[1]])) { $value = $V[$a[1]]; }
			elseif ($c == 3 && isset($V[$a[1]][$a[2]])) { $value = $V[$a[1]][$a[2]]; }
			elseif ($c == 4 && isset($V[$a[1]][$a[2]][$a[3]])) { $value = $V[$a[1]][$a[2]][$a[3]]; }
			if (isset($new_value)) {
				if ($c == 2) { $V[$a[1]] = $new_value; }
				elseif ($c == 3) { $V[$a[1]][$a[2]] = $new_value; }
				elseif ($c == 4) { $V[$a[1]][$a[2]][$a[3]] = $new_value; }
			}
		} else {
			$V = &$GLOBALS['CONF'][$name];
			if (isset($V)) {
				$value = $V;
			}
			if (isset($new_value)) {
				$V = $new_value;
			}
		}
		if (isset($new_value)) {
			$value = $new_value;
		}
		return $value;
	}
}

if (!function_exists('conf_add')) {
	function conf_add($name = null, $new_value = null) {
		if (!is_null($new_value)) {
			$actual_error = conf($name) ? conf($name) . ';' : '';
		}
		return conf($name,$actual_error . $new_value);
	}		
}		
// Useful short function to call PROJECT_CONF. 
// Examples:
// module_conf('home_page', 'SETTING1');
// module_conf('home_page', 'SETTING1', 'new_value');
// module_conf('home_page', 'key2::k2'); => get module conf data subarray item
// module_conf('home_page', 'key2::k2', 'v2'); => set module conf data subarray item
// module_conf('home_page', array('k1' => 'v1', 'k2' => 'v2')); => set module conf data subarray item
if (!function_exists('module_conf')) {
	function module_conf ($module = '', $name = '', $new_value = null) {
// TODO: add/merge real value of module($module)->$property (maybe be slow due to module init);
		$value = null;
		if (!$module && !$name) {
			return $GLOBALS['PROJECT_CONF'];
		}
		if ($module && !$name) {
			return $GLOBALS['PROJECT_CONF'][$module];
		}
		$V = &$GLOBALS['PROJECT_CONF'][$module];
		if (!$name) {
			return $V;
		}
		// SET $name as array to merge as key-val
		if (is_array($name)) {
			foreach ((array)$name as $_key => $_new_val) {
				if (!isset($_new_val)) {
					continue;
				}
				$a = false;
				if (false !== strpos($_key, '::')) {
					$a = explode('::', $_key);
				}
				if (is_array($a) && !empty($a)) {
					$_key = $a[0];
					$c = count($a);
					if ($c == 2) { $V[$_key][$a[1]] = $_new_val; }
					elseif ($c == 3) { $V[$_key][$a[1]][$a[2]] = $_new_val; }
					elseif ($c == 4) { $V[$_key][$a[1]][$a[2]][$a[3]] = $_new_val; }
				} else {
					$V[$_key] = $_new_val;
				}
			}
			return true;
		}
		$a = false;
		if (false !== strpos($name, '::')) {
			$a = explode('::', $name);
		}
		if (is_array($a) && !empty($a)) {
			$name = $a[0];
			$c = count($a);
			if ($c == 2 && isset($V[$name][$a[1]])) { $value = $V[$name][$a[1]]; }
			elseif ($c == 3 && isset($V[$name][$a[1]][$a[2]])) { $value = $V[$name][$a[1]][$a[2]]; }
			elseif ($c == 4 && isset($V[$name][$a[1]][$a[2]][$a[3]])) { $value = $V[$name][$a[1]][$a[2]][$a[3]]; }
			if (isset($new_value)) {
				if ($c == 2) { $V[$name][$a[1]] = $new_value; }
				elseif ($c == 3) { $V[$name][$a[1]][$a[2]] = $new_value; }
				elseif ($c == 4) { $V[$name][$a[1]][$a[2]][$a[3]] = $new_value; }
			}
		} else {
			if (isset($V[$name])) {
				$value = $V[$name];
			}
			if (isset($new_value)) {
				$V[$name] = $new_value;
			}
		}
		if (isset($new_value)) {
			$value = $new_value;
		}
		return $value;
	}
}

// Abstraction layer for debug logging, that was previously set in different GLOBALS arrays. 
// Examples:
// debug('key1'); => get debug data
// debug('key1', 'value1'); => set debug data
// debug('key2', array('k2' => 'v2')); => set debug data array
// debug('key2::k2'); => get debug data subarray item
// debug('key2::k2', 'v2'); => set debug data subarray item
// debug(array('key2' => 'v2','key3' => 'v3')); => set debug data array
if (!function_exists('debug')) {
	function debug ($name = null, $new_value = null) {
		$value = null;
		// If no first params passed - we return whole structure
		if (!isset($name)) {
			return $GLOBALS['DEBUG'];
		}
		// SET $name as array to merge as key-val
		if (is_array($name)) {
			$V = &$GLOBALS['DEBUG'];
			foreach ((array)$name as $_key => $_new_val) {
				if (!isset($_new_val)) {
					continue;
				}
				$a = false;
				if (false !== strpos($_key, '::')) {
					$a = explode('::', $_key);
				}
				if (is_array($a) && !empty($a)) {
					$_key = $a[0];
					$c = count($a);
					$last_key = $a[$c - 1];
					$base = null;

					if ($c == 2) { $base = &$V[$_key]; }
					elseif ($c == 3) { $base = &$V[$_key][$a[1]]; }
					elseif ($c == 4) { $base = &$V[$_key][$a[1]][$a[2]]; }
					elseif ($c == 5) { $base = &$V[$_key][$a[1]][$a[2]][$a[3]]; }

					$base[$last_key] = $_new_val;
					unset($base);
				} else {
					$V[$_key] = $_new_val;
				}
			}
			return true;
		}
		$a = false;
		if (false !== strpos($name, '::')) {
			$a = explode('::', $name);
		}
		if (is_array($a) && !empty($a)) {
			$name = $a[0];
			$V = &$GLOBALS['DEBUG'][$name];
			$c = count($a);
			$last_key = $a[$c - 1];
			$base = null;

			if ($c == 2) { $base = &$V; }
			elseif ($c == 3) { $base = &$V[$a[1]]; }
			elseif ($c == 4) { $base = &$V[$a[1]][$a[2]]; }
			elseif ($c == 5) { $base = &$V[$a[1]][$a[2]][$a[3]]; }

			if (isset($base[$last_key])) {
				$value = $base[$last_key];
			}
			if (isset($new_value)) {
				$base[$last_key] = $new_value;
			}
		} else {
			$V = &$GLOBALS['DEBUG'][$name];
			if (isset($V)) {
				$value = $V;
			}
			if (isset($new_value)) {
				$V = $new_value;
			}
		}
		if (isset($new_value)) {
			$value = $new_value;
		}
		return $value;
	}
}
