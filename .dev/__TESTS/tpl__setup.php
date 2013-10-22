<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

if (!function_exists('_tpl')) {
function _tpl($stpl_text = '', $replace = array(), $name = '', $params = array()) {
	return tpl()->parse_string($stpl_text, $replace, $name, $params);
}
}