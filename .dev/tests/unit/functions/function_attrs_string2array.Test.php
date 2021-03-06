<?php

require_once dirname(__DIR__) . '/yf_unit_tests_setup.php';

class function_attrs_string2array_test extends yf\tests\wrapper
{
    public function test_negative()
    {
        $this->assertEquals([], _attrs_string2array());
        $this->assertEquals([], _attrs_string2array(null));
        $this->assertEquals([], _attrs_string2array(''));
        $this->assertEquals([], _attrs_string2array([]));
        $this->assertEquals([], _attrs_string2array(new stdClass()));
        $this->assertEquals([], _attrs_string2array(function () {
        }));
    }
    public function test_simple()
    {
        $a = ['k1' => 'v1'];
        $this->assertEquals($a, _attrs_string2array('k1=v1'));
        $this->assertEquals($a, _attrs_string2array(' k1=v1'));
        $this->assertEquals($a, _attrs_string2array('k1=v1 '));
        $this->assertEquals($a, _attrs_string2array(' k1=v1 '));
        $this->assertEquals($a, _attrs_string2array(' k1 = v1 '));
        $this->assertEquals($a, _attrs_string2array(' k1 = v1; '));
        $this->assertEquals($a, _attrs_string2array('   ;,k1   =   v1,;   '));
    }
    public function test_two()
    {
        $a = ['k1' => 'v1', 'k2' => 'v2'];
        $this->assertEquals($a, _attrs_string2array('k1=v1;k2=v2'));
        $this->assertEquals($a, _attrs_string2array(' k1 = v1 ; k2 = v2 '));
        $this->assertEquals($a, _attrs_string2array('  k1  =  v1  ,  k2  =  v2  '));
        $this->assertEquals($a, _attrs_string2array(',,,,,,  k1   =    v1  ,,,,,  k2    =   v2 ,,,,, '));
    }
    public function test_many()
    {
        $a = ['k1' => 'v1', 'k2' => 'v2', 'k3' => 'v3', 'k4' => 'v4'];
        $this->assertEquals($a, _attrs_string2array('k1=v1;k2=v2;k3=v3;k4=v4'));
        $this->assertEquals($a, _attrs_string2array(' k1 = v1; k2 = v2; k3 = v3; k4 = v4'));
        $this->assertEquals($a, _attrs_string2array(' k1 = 0; k2 = v2; k3 = v3; k4 = v4; k1 = v1'));
    }
    public function test_special_symbols()
    {
        $a = ['k1' => '!@#$%^&&*(()_-+1234567890.'];
        $this->assertEquals($a, _attrs_string2array('k1=!@#$%^&&*(()_-+1234567890.'));
        $this->assertEquals($a, _attrs_string2array(' k1 = !@#$%^&&*(()_-+1234567890. '));
        $this->assertEquals(['k1' => 'test"test'], _attrs_string2array(' k1 = test"test '));
        $this->assertEquals(['k1["test"]' => '["something"]'], _attrs_string2array(' k1["test"] = ["something"] '));
    }
    public function test_quotes()
    {
        $this->assertEquals(['k1' => 'v1'], _attrs_string2array('k1="v1"', $strip_quotes = true));
        $this->assertEquals(['k1' => '"v1"'], _attrs_string2array('k1="v1"', $strip_quotes = false));
        $this->assertEquals(['k1' => '" v1 "'], _attrs_string2array(' k1 = " v1 " ', $strip_quotes = false));
        $this->assertEquals(['k1' => 'v1'], _attrs_string2array(' k1 = " v1 " ', $strip_quotes = true));
    }
    public function test_empty()
    {
        $this->assertEquals(['k1' => ''], _attrs_string2array('k1='));
        $this->assertEquals(['k1' => ''], _attrs_string2array('k1'));
        $this->assertEquals(['k1' => ''], _attrs_string2array(' k1 = '));
    }
}
