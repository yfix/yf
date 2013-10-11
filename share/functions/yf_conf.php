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
// conf('key2[]', 'v20'); => set conf data with auto-increment
if (!function_exists('conf')) {
	function conf ($name = null, $new_value = null) {
		$ARR = &$GLOBALS['CONF'];
		$value = null;
		// If no first params passed - we return whole structure
		if (!isset($name)) {
			return $ARR;
		}
		// SET $name as array to merge as key-val
		if (is_array($name)) {
			$v = &$ARR;
			foreach ((array)$name as $_key => $_new_val) {
				if (!isset($_new_val)) {
					continue;
				}
				$add_auto_index = false;
				if (substr($_key, -2) == '[]') {
					$_key = substr($_key, 0, -2);
					$add_auto_index = true;
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

					if ($c == 2) { $base = &$v[$_key]; }
					elseif ($c == 3) { $base = &$v[$_key][$a[1]]; }
					elseif ($c == 4) { $base = &$v[$_key][$a[1]][$a[2]]; }
					elseif ($c == 5) { $base = &$v[$_key][$a[1]][$a[2]][$a[3]]; }

					if ($add_auto_index) {
						$base[] = $_new_val;
					} else {
						$base[$last_key] = $_new_val;
					}
					unset($base);
				} else {
					if ($add_auto_index) {
						$v[$_key][] = $_new_val;
					} else {
						$v[$_key] = $_new_val;
					}
				}
			}
			return true;
		}
		$add_auto_index = false;
		if (substr($name, -2) == '[]') {
			$name = substr($name, 0, -2);
			$add_auto_index = true;
		}
		$a = false;
		if (false !== strpos($name, '::')) {
			$a = explode('::', $name);
		}
		if (is_array($a) && !empty($a)) {
			$name = $a[0];
			$v = &$ARR[$name];
			$c = count($a);
			$last_key = $a[$c - 1];
			$base = null;

			if ($c == 2) { $base = &$v; }
			elseif ($c == 3) { $base = &$v[$a[1]]; }
			elseif ($c == 4) { $base = &$v[$a[1]][$a[2]]; }
			elseif ($c == 5) { $base = &$v[$a[1]][$a[2]][$a[3]]; }

			if (isset($base[$last_key])) {
				$value = $base[$last_key];
			}
			if (isset($new_value)) {
				if ($add_auto_index) {
					$base[] = $new_value;
				} else {
					$base[$last_key] = $new_value;
				}
			}
		} else {
			$v = &$ARR[$name];
			if (isset($v)) {
				$value = $v;
			}
			if (isset($new_value)) {
				if ($add_auto_index) {
					$v[] = $new_value;
				} else {
					$v = $new_value;
				}
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
		$v = &$GLOBALS['PROJECT_CONF'][$module];
		if (!$name) {
			return $v;
		}
		// SET $name as array to merge as key-val
		if (is_array($name)) {
			foreach ((array)$name as $_key => $_new_val) {
				if (!isset($_new_val)) {
					continue;
				}
				$add_auto_index = false;
				if (substr($_key, -2) == '[]') {
					$_key = substr($_key, 0, -2);
					$add_auto_index = true;
				}
				$a = false;
				if (false !== strpos($_key, '::')) {
					$a = explode('::', $_key);
				}
				if (is_array($a) && !empty($a)) {
					$_key = $a[0];
					$c = count($a);
					if ($c == 2) { $v[$_key][$a[1]] = $_new_val; }
					elseif ($c == 3) { $v[$_key][$a[1]][$a[2]] = $_new_val; }
					elseif ($c == 4) { $v[$_key][$a[1]][$a[2]][$a[3]] = $_new_val; }
				} else {
					$v[$_key] = $_new_val;
				}
			}
			return true;
		}
		$add_auto_index = false;
		if (substr($name, -2) == '[]') {
			$name = substr($name, 0, -2);
			$add_auto_index = true;
		}
		$a = false;
		if (false !== strpos($name, '::')) {
			$a = explode('::', $name);
		}
		if (is_array($a) && !empty($a)) {
			$name = $a[0];
			$c = count($a);
			if ($c == 2 && isset($v[$name][$a[1]])) { $value = $v[$name][$a[1]]; }
			elseif ($c == 3 && isset($v[$name][$a[1]][$a[2]])) { $value = $v[$name][$a[1]][$a[2]]; }
			elseif ($c == 4 && isset($v[$name][$a[1]][$a[2]][$a[3]])) { $value = $v[$name][$a[1]][$a[2]][$a[3]]; }
			if (isset($new_value)) {
				if ($c == 2) { $v[$name][$a[1]] = $new_value; }
				elseif ($c == 3) { $v[$name][$a[1]][$a[2]] = $new_value; }
				elseif ($c == 4) { $v[$name][$a[1]][$a[2]][$a[3]] = $new_value; }
			}
		} else {
			if (isset($v[$name])) {
				$value = $v[$name];
			}
			if (isset($new_value)) {
				$v[$name] = $new_value;
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
// debug('key2[]', 'v20'); => set debug data with auto-increment
if (!function_exists('debug')) {
	function debug ($name = null, $new_value = null) {
		$ARR = &$GLOBALS['DEBUG'];
		$value = null;
		// If no first params passed - we return whole structure
		if (!isset($name)) {
			return $ARR;
		}
		// SET $name as array to merge as key-val
		if (is_array($name)) {
			$v = &$ARR;
			foreach ((array)$name as $_key => $_new_val) {
				if (!isset($_new_val)) {
					continue;
				}
				$add_auto_index = false;
				if (substr($_key, -2) == '[]') {
					$_key = substr($_key, 0, -2);
					$add_auto_index = true;
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

					if ($c == 2) { $base = &$v[$_key]; }
					elseif ($c == 3) { $base = &$v[$_key][$a[1]]; }
					elseif ($c == 4) { $base = &$v[$_key][$a[1]][$a[2]]; }
					elseif ($c == 5) { $base = &$v[$_key][$a[1]][$a[2]][$a[3]]; }

					if ($add_auto_index) {
						$base[] = $_new_val;
					} else {
						$base[$last_key] = $_new_val;
					}
					unset($base);
				} else {
					if ($add_auto_index) {
						$v[$_key][] = $_new_val;
					} else {
						$v[$_key] = $_new_val;
					}
				}
			}
			return true;
		}
		$add_auto_index = false;
		if (substr($name, -2) == '[]') {
			$name = substr($name, 0, -2);
			$add_auto_index = true;
		}
		$a = false;
		if (false !== strpos($name, '::')) {
			$a = explode('::', $name);
		}
		if (is_array($a) && !empty($a)) {
			$name = $a[0];
			$v = &$ARR[$name];
			$c = count($a);
			$last_key = $a[$c - 1];
			$base = null;

			if ($c == 2) { $base = &$v; }
			elseif ($c == 3) { $base = &$v[$a[1]]; }
			elseif ($c == 4) { $base = &$v[$a[1]][$a[2]]; }
			elseif ($c == 5) { $base = &$v[$a[1]][$a[2]][$a[3]]; }

			if (isset($base[$last_key])) {
				$value = $base[$last_key];
			}
			if (isset($new_value)) {
				if ($add_auto_index) {
					$base[] = $new_value;
				} else {
					$base[$last_key] = $new_value;
				}
			}
		} else {
			$v = &$ARR[$name];
			if (isset($v)) {
				$value = $v;
			}
			if (isset($new_value)) {
				if ($add_auto_index) {
					$v[] = $new_value;
				} else {
					$v = $new_value;
				}
			}
		}
		if (isset($new_value)) {
			$value = $new_value;
		}
		return $value;
	}
}
