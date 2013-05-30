<?php

/**
* Test sub-class
*/
class profy_test_unicode {

	/**
	* Profy module constructor
	*/
	function _init () {
		$this->TEST_OBJ = module("test");
	}

	/**
	*/
	function run_test () {
		$_string = "Mutual Friends (пользователь должен быть у Вас в друзьях а Вы у него)";
		$body .= "<b>NOTE: You need to set UTF-8 encoding in your browser to see correct results</b><br /><br />";
		$body .= "String: \""._prepare_html($_string)."\"<br /><br />";
		$body .= "Strlen (raw): ".strlen($_string)." symbols<br />";
		$body .= "Strlen (utf-8): "._strlen($_string)." symbols<br />";
		$body .= "Substr 21 symbols (raw): ".substr($_string, 0, 21)."<br />";
		$body .= "Utf_Substr 21 symbols (utf-8): "._substr($_string, 0, 21)."<br />";
		$body .= "Strtoupper (raw): ".strtoupper($_string)."<br />";
		$body .= "Utf_Strtoupper (utf-8): "._strtoupper($_string)."<br />";
		$body .= "Utf_Strtolower (utf-8): "._strtolower($_string)."<br />";
		$body .= "Truncate utf-8 21 symbols (utf-8): "._truncate($_string, 21)."<br />";
		$body .= "Truncate utf-8 21 symbols (utf-8) & parametr wordsafe = true: "._truncate($_string, 21, true)."<br />";
		$body .= "Truncate utf-8 21 symbols (utf-8) & parametr dots = true: "._truncate($_string, 21, '', true)."<br />";
		$body .= "<br />Available functions:<br /><b> _truncate<br /> _strlen<br /> _strtoupper<br /> _strtolower<br /> _ucfirst<br />_ucwords<br />  _substr<br />  _wordwrap</b><br /><br />";

		$testcase = array(
			"tHe QUIcK bRoWn" => "QUI",
			"frànçAIS" => "çAI",
			"über-åwesome" => "-åw",
		);
		foreach ((array)$testcase as $input => $output) {
			$body .= "<br />_substr(\"".$input."\", 4, 3) == \""._substr($input, 4, 3)."\", must be: \"".$output."\"\n";
		}
		$body .= "<br />\n";
		$body .= "<br />\n";


		$_start_time = microtime(true);
		for ($i = 0; $i <= 10000; $i++) {
			$_length = strlen($_string);
		}
		$_duration_1 = common()->_format_time_value(microtime(true) - $_start_time);
		$body .= "strlen internal 10000 times: ".$_duration_1." sec<br />";
		$_start_time = microtime(true);

		// Compare fastest method to use utf8-safe strlen
		$u = _class("unicode_funcs", "classes/");
		for ($i = 0; $i <= 10000; $i++) {
			$_length = $u->strlen($_string);
#			$_length = _strlen($_string);
#			$_length = _class("unicode_funcs", "classes/")->strlen($_string);
		}
		$_duration_2 = common()->_format_time_value(microtime(true) - $_start_time);
		$body .= "strlen utf-8 10000 times: ".$_duration_2." sec<br />";
		return $body;
	}
}
