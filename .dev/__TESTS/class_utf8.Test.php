<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_utf8_test extends PHPUnit_Framework_TestCase {
	public function test_main() {
		$str = 'Mutual Friends (пользователь должен быть у Вас в друзьях а Вы у него)';
		$this->assertEquals(111, strlen($str));
		$this->assertEquals(69, _strlen($str));
		$this->assertEquals(26, strlen(_substr($str, 0, 21)));
		$this->assertEquals(21, _strlen(_substr($str, 0, 21)));
#		$this->assertNotEquals(strtoupper($str), _strtoupper($str));
#		$this->assertNotEquals(strtolower($str), _strtolower($str));
/*
		_truncate($_string, 21)
		_truncate($_string, 21, true)
		_truncate($_string, 21, '', true)

#		$testcase = array(
#			'tHe QUIcK bRoWn' => 'QUI',
#			'frànçAIS' => 'çAI',
#			'über-åwesome' => '-åw',
#		);
#		foreach ((array)$testcase as $input => $output) {
#			$body .= "<br />_substr(\"".$input."\", 4, 3) == \""._substr($input, 4, 3)."\", must be: \"".$output."\"\n";
#		}
*/
	}
}
