<?php

require_once dirname(__DIR__) . '/yf_unit_tests_setup.php';

class function_debug_test extends yf\tests\wrapper
{
    public static $_bak = [];
    public static function setUpBeforeClass() : void
    {
        self::$_bak = $GLOBALS['DEBUG'];
        $GLOBALS['DEBUG'] = [];
    }
    public static function tearDownAfterClass() : void
    {
        $GLOBALS['DEBUG'] = self::$_bak;
    }
    protected function setUp() : void
    {
        $GLOBALS['DEBUG'] = [];
    }
    public function test_22()
    {
        $GLOBALS['DEBUG']['test'] = '55';
        $this->assertEquals(debug('test'), '55');
    }
    public function test_23()
    {
        $GLOBALS['DEBUG']['test']['sub'] = 'sub1';
        $this->assertEquals(debug('test::sub'), 'sub1');
    }
    public function test_24()
    {
        debug([
            'key1' => 'val1',
            'key2' => 'val2',
            'key3' => 'val3',
        ]);
        $should_be = [
            'key1' => 'val1',
            'key2' => 'val2',
            'key3' => 'val3',
        ];
        $this->assertEquals($GLOBALS['DEBUG'], $should_be);
    }
    public function test_25()
    {
        debug([
            'key1' => 'val1',
            'key2::sub1' => 'val21',
            'key2::sub2' => 'val22',
            'key2::sub3::ss1' => 'val231',
            'key2::sub3::ss2' => 'val232',
            'key2::sub4::ss1::sss1' => 'val2411',
            'key2::sub4::ss1::sss2' => 'val2412',
        ]);
        $should_be = [
            'key1' => 'val1',
            'key2' => [
                'sub1' => 'val21',
                'sub2' => 'val22',
                'sub3' => [
                    'ss1' => 'val231',
                    'ss2' => 'val232',
                ],
                'sub4' => [
                    'ss1' => [
                        'sss1' => 'val2411',
                        'sss2' => 'val2412',
                    ],
                ],
            ],
        ];
        $this->assertEquals($GLOBALS['DEBUG'], $should_be);
    }
    public function test_26()
    {
        $GLOBALS['DEBUG'] = [
            'key2' => [
                'sub4' => [
                    'ss1' => [
                        'sss2' => 'val2412',
                    ],
                ],
            ],
        ];
        $this->assertEquals(debug('key2::sub4::ss1::sss2'), 'val2412');
    }
    public function test_31()
    {
        debug('key', ['v0', 'v1', 'v2']);
        debug('key[]', 'v3');
        $should_be = [
            'key' => [
                0 => 'v0',
                1 => 'v1',
                2 => 'v2',
                3 => 'v3',
            ],
        ];
        $this->assertEquals($should_be, $GLOBALS['DEBUG']);
    }
    public function test_32()
    {
        debug([
            'k1' => 'v1',
            'k2[]' => 'v20',
        ]);
        $should_be = [
            'k1' => 'v1',
            'k2' => [
                0 => 'v20',
            ],
        ];
        $this->assertEquals($should_be, $GLOBALS['DEBUG']);
    }
}
