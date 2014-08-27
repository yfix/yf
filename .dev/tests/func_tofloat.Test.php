<?php

require __DIR__.'/yf_unit_tests_setup.php';

class func_tofloat_test extends PHPUnit_Framework_TestCase {
	public function test_main() {
		$this->assertEquals(0, tofloat(''));
		$this->assertEquals(1, tofloat(1));
		$this->assertEquals(-1, tofloat(-1));
		$this->assertEquals(1, tofloat('1'));
		$this->assertEquals(-1, tofloat('-1'));
		$this->assertEquals(1.1, tofloat('1.1'));
		$this->assertEquals(-1.1, tofloat('-1.1'));
		$this->assertEquals(1.1, tofloat('1,1'));
		$this->assertEquals(-1.1, tofloat('-1,1'));
		$this->assertEquals(0, tofloat(''));
		$this->assertEquals(0, tofloat('-'));
		$this->assertEquals(1999.369, tofloat('1.999,369€'));
		$this->assertEquals(-1999.369, tofloat('-1.999,369€'));
		$this->assertEquals(126564789.33, tofloat('126,564,789.33 m²'));
		$this->assertEquals(-126564789.33, tofloat('-126,564,789.33 m²'));
		$this->assertEquals(126564789.33, tofloat(126564789.33));
		$this->assertEquals(-126564789.33, tofloat(-126564789.33));
		$this->assertEquals(122.34343, tofloat('122.34343The'));
		$this->assertEquals(-122.34343, tofloat('-122.34343The'));
		$this->assertEquals(122.34343, tofloat(' 122.34343 The '));
		$this->assertEquals(122.34343, tofloat('The122.34343'));
		$this->assertEquals(-122.34343, tofloat('The-122.34343'));
		$this->assertEquals(0, tofloat('some string not containing numbers'));
		$this->assertEquals(1234, tofloat('01234'));
		$this->assertEquals(-1234, tofloat('-01234'));
		$this->assertEquals(array(12.341, 56.7811111), tofloat(array(12.341, '56,7811111')));
		$this->assertEquals(array(12.341, -56.7811111), tofloat(array(12.341, '-56,7811111')));
		$this->assertEquals(array(-12.341, -56.7811111), tofloat(array(-12.341, '-56,7811111')));
		$this->assertEquals(array('k1' => 12.341, 'k2' => 56.7811111), tofloat(array('k1' => 12.341, 'k2' => '56,7811111')));
		$this->assertEquals(array('k1' => 12.341, 'k2' => array(array(56.7811111))), tofloat(array('k1' => 12.341, 'k2' => array(array('56,7811111')))));
		$this->assertEquals(array('k1' => 12.341, 'k2' => array(array(-56.7811111))), tofloat(array('k1' => 12.341, 'k2' => array(array('-56,7811111')))));
	}
}