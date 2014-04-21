<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class func_todecimal_test extends PHPUnit_Framework_TestCase {
	public function test_main() {
		$this->assertEquals('0', todecimal(''));
		$this->assertEquals('1', todecimal(1));
		$this->assertEquals('-1', todecimal(-1));
		$this->assertEquals('1', todecimal('1'));
		$this->assertEquals('-1', todecimal('-1'));
		$this->assertEquals('1.1', todecimal('1.1'));
		$this->assertEquals('-1.1', todecimal('-1.1'));
		$this->assertEquals('1.1', todecimal('1,1'));
		$this->assertEquals('-1.1', todecimal('-1,1'));
		$this->assertEquals('0', todecimal(''));
		$this->assertEquals('0', todecimal('-'));
		$this->assertEquals('1999.37', todecimal('1.999,369€'));
		$this->assertEquals('-1999.37', todecimal('-1.999,369€'));
		$this->assertEquals('126564789.33', todecimal('126,564,789.33 m²'));
		$this->assertEquals('-126564789.33', todecimal('-126,564,789.33 m²'));
		$this->assertEquals('126564789.33', todecimal(126564789.33));
		$this->assertEquals('-126564789.33', todecimal(-126564789.33));
		$this->assertEquals('122.34', todecimal('122.34343The'));
		$this->assertEquals('-122.34', todecimal('-122.34343The'));
		$this->assertEquals('122.34', todecimal(' 122.34343 The '));
		$this->assertEquals('-122.34', todecimal(' -122.34343 The '));
		$this->assertEquals('122.34', todecimal('The122.34343'));
		$this->assertEquals('-122.34', todecimal('The-122.34343'));
		$this->assertEquals('0', todecimal('some string not containing numbers'));
		$this->assertEquals('1234', todecimal('01234'));
		$this->assertEquals('-1234', todecimal('-01234'));
		$this->assertEquals(array('12.34', '56.78'), todecimal(array(12.341, '56,7811111')));
		$this->assertEquals(array('12.34', '-56.78'), todecimal(array(12.341, '-56,7811111')));
		$this->assertEquals(array('-12.34', '-56.78'), todecimal(array(-12.341, '-56,7811111')));
		$this->assertEquals(array('k1' => '12.34', 'k2' => '56.78'), todecimal(array('k1' => 12.341, 'k2' => '56,7811111')));
		$this->assertEquals(array('k1' => '-12.34', 'k2' => '-56.78'), todecimal(array('k1' => -12.341, 'k2' => '-56,7811111')));
		$this->assertEquals(array('k1' => '12.34', 'k2' => array(array('56.78'))), todecimal(array('k1' => 12.341, 'k2' => array(array('56,7811111')))));
		$this->assertEquals(array('k1' => '12.34', 'k2' => array(array('-56.78'))), todecimal(array('k1' => 12.341, 'k2' => array(array('-56,7811111')))));

		$this->assertEquals('56.7811', todecimal('56,7811111', $digits = 4) );
#		$this->assertEquals(array('12.341', '56.7811'), todecimal(array(12.341, '56,7811111'), $digits = 4) );
	}
}