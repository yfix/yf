<?php

require __DIR__.'/yf_unit_tests_setup.php';

/**
 * @requires extension mbstring
 */
class class_utf8_test extends PHPUnit_Framework_TestCase {
	public function test_main() {
		$str = 'Mutual Friends (пользователь должен быть у Вас в друзьях а Вы у него)';
		$this->assertEquals(111, strlen($str));
		$this->assertEquals(69, _strlen($str));
		$this->assertEquals(26, strlen(_substr($str, 0, 21)));
		$this->assertEquals(21, _strlen(_substr($str, 0, 21)));
	}
	public function test_cases() {
		$sentence = 'пользователь должен быть у Вас в друзьях а Вы у него';
		$this->assertEquals('ПОЛЬЗОВАТЕЛЬ ДОЛЖЕН БЫТЬ У ВАС В ДРУЗЬЯХ А ВЫ У НЕГО', trim(_strtoupper($sentence)));
		$this->assertEquals('пользователь должен быть у вас в друзьях а вы у него', trim(_strtolower($sentence)));
		$this->assertNotEquals(strtoupper($sentence), _strtoupper($sentence));
		$this->assertNotEquals(strtolower($sentence), _strtolower($sentence));
		$this->assertNotEquals(ucfirst($sentence), _ucfirst($sentence));
		$this->assertNotEquals(ucwords($sentence), _ucwords($sentence));
	}
	public function test_strings() {
		$sentence = 'пользователь должен быть у Вас в друзьях а Вы у него';
		$testcase = array(
			'tHe QUIcK bRoWn' => 'QUI',
			'frànçAIS' => 'çAI',
			'über-åwesome' => '-åw',
		);
		foreach ((array)$testcase as $input => $output) {
			$this->assertEquals(_substr($input, 4, 3), $output);
		}
		$this->assertEquals(_truncate($sentence, 10), 'пользовате');
		$this->assertEquals(_truncate($sentence, 15, true), 'пользователь');
		$this->assertEquals(_truncate($sentence, 15, true, true), 'пользовател...');
	}
}
