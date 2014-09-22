<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class class_cache_test extends PHPUnit_Framework_TestCase {
	public static $_cache = array();
	public static function _cache_init() {
		main()->modules['cache'] = null;
		self::$_cache = clone _class('cache');
		self::$_cache->_init(array('driver' => self::_get_driver_name()));
		self::$_cache->_driver = clone self::$_cache->_driver;
		if (self::_get_driver_name() == 'memcache') {
			if (false !== strpos(strtolower(get_called_class()), '_memcached')) {
				self::$_cache->_driver->FORCE_EXT = 'memcached';
			} else {
				self::$_cache->_driver->FORCE_EXT = 'memcache';
			}
		}
		method_exists(self::$_cache->_driver, '_init') && self::$_cache->_driver->_init();
		self::$_cache->NO_CACHE = false;
		self::$_cache->CACHE_NS = 'unit_tests_';
		self::$_cache->FORCE_REBUILD_CACHE = false;
	}
	public static function setUpBeforeClass() {
		if (!defined('HHVM_VERSION') || self::_get_driver_name() != 'memcache') {
			self::_cache_init();
		}
	}
	protected function setUp() {
		if (defined('HHVM_VERSION') && self::_get_driver_name() == 'memcache') {
			self::_cache_init();
		}
	}
	public static function tearDownAfterClass() {
		$driver = self::_get_driver_name();
		if ($driver == 'files') {
			$cache_dir = self::_cache()->_driver->CACHE_DIR;
			if ($cache_dir && file_exists($cache_dir)) {
				_class('dir')->delete_dir($cache_dir, $delete_start_dir = true);
			}
		}
	}
	public static function _get_driver_name() {
		$called = strtolower(get_called_class());
		if (false !== strpos($called, '_files')) {
			return 'files';
		} elseif (false !== strpos($called, '_memcache')) {
			return 'memcache';
		} elseif (false !== strpos($called, '_xcache')) {
			return 'xcache';
		} elseif (false !== strpos($called, '_apc')) {
			return 'apc';
		} else {
			return 'tmp';
		}
	}
	public static function _cache() {
		return self::$_cache;
	}
	public function test_driver() {
		$this->assertSame(self::_get_driver_name(), self::_cache()->DRIVER);
	}
	public function test_is_ready() {
		$this->assertTrue(self::_cache()->_driver_ok);
		$this->assertNotEmpty(self::_cache()->_driver);
		$this->assertTrue(self::_cache()->_driver->is_ready());
	}
	public function test_get() {
		$this->assertTrue(self::_cache()->flush());
		$this->assertNull(@self::_cache()->get());
		$this->assertNull(self::_cache()->get('k1'));

		$this->assertTrue(self::_cache()->set('k1', 'val1'));
		$this->assertSame('val1', self::_cache()->get('k1'));

		$this->assertTrue(self::_cache()->set('k11', 0));
		$this->assertSame(0, self::_cache()->get('k11'));

		$this->assertTrue(self::_cache()->set('k11_', false));
		$this->assertFalse(self::_cache()->get('k11_'));
	}
	public function test_set() {
		$this->assertTrue(self::_cache()->flush());
		$this->assertTrue(self::_cache()->set('k2', 'some_data'));
		$this->assertSame('some_data', self::_cache()->get('k2'));
		$this->assertTrue(self::_cache()->set('k2_', array()));
		$this->assertSame(array(), self::_cache()->get('k2_'));
		$this->assertTrue(self::_cache()->set('k3_', false));
		$this->assertFalse(self::_cache()->get('k3_'));
	}
	public function test_del() {
		$this->assertTrue(self::_cache()->flush());
		$this->assertTrue(self::_cache()->set('k3', 'val3'));
		$this->assertSame('val3', self::_cache()->get('k3'));
		$this->assertTrue(self::_cache()->del('k3'));
		$this->assertNull(self::_cache()->get('k3'));
	}
	public function test_flush() {
		$this->assertTrue(self::_cache()->flush());
		$this->assertTrue(self::_cache()->set('k4', 'val4'));
		$this->assertTrue(self::_cache()->flush());
		$this->assertNull(self::_cache()->get('k4'));
		$list_keys_result = self::_cache()->list_keys();
		if ($list_keys_result !== false && $list_keys_result !== null) {
			$this->assertSame(array(), self::_cache()->list_keys());
		}
	}
	public function test_list_keys() {
		if (!self::_cache()->_driver->implemented['list_keys']) {
			return ;
		}
		$this->assertTrue(self::_cache()->flush());
		$this->assertTrue(self::_cache()->set('k1', 'v1'));
		$this->assertTrue(self::_cache()->set('k2', 'v2'));
		$list_keys_result = self::_cache()->list_keys();
		if ($list_keys_result !== false && $list_keys_result !== null) {
			$this->assertSame(array('k1', 'k2'), self::_cache()->list_keys());
			$this->assertTrue(self::_cache()->set('k3', 'v3'));
			$this->assertSame(array('k1', 'k2', 'k3'), self::_cache()->list_keys());
		}
	}
	public function test_multi_get() {
		$this->assertTrue(self::_cache()->flush());
		$this->assertTrue(self::_cache()->set('k17', 'v1'));
		$this->assertTrue(self::_cache()->set('k27', 'v2'));
		$this->assertSame(array('k17' => 'v1', 'k27' => 'v2'), self::_cache()->multi_get(array('k17', 'k27')));
		$this->assertSame('v1', self::_cache()->get('k17'));
		$this->assertSame('v2', self::_cache()->get('k27'));

		$this->assertTrue(self::_cache()->flush());
		$this->assertTrue(self::_cache()->set('k18', 'v1'));
		$this->assertTrue(self::_cache()->set('k28', false));
		$this->assertSame(array('k18' => 'v1', 'k28' => false), self::_cache()->multi_get(array('k18', 'k28')));
		$this->assertSame('v1', self::_cache()->get('k18'));
		$this->assertFalse(self::_cache()->get('k28'));
	}
	public function test_multi_set() {
		$this->assertTrue(self::_cache()->flush());
		$this->assertSame(array(), self::_cache()->multi_get(array('k111', 'k222')));
		$this->assertTrue(self::_cache()->multi_set(array('k111' => 'v1', 'k222' => 'v2')));
		$this->assertSame(array('k111' => 'v1', 'k222' => 'v2'), self::_cache()->multi_get(array('k111', 'k222')));

		$this->assertTrue(self::_cache()->flush());
		$this->assertSame(array(), self::_cache()->multi_get(array('k113', 'k223')));
		$this->assertTrue(self::_cache()->multi_set(array('k113' => 'v1', 'k223' => false)));
		$this->assertSame(array('k113' => 'v1', 'k223' => false), self::_cache()->multi_get(array('k113', 'k223')));
	}
	public function test_multi_del() {
		$this->assertTrue(self::_cache()->flush());
		$this->assertSame(array(), self::_cache()->multi_get(array('k133', 'k233')));

		$this->assertTrue(self::_cache()->multi_set(array('k133' => 'v1', 'k233' => 'v2')));
		$this->assertSame(array('k133' => 'v1', 'k233' => 'v2'), self::_cache()->multi_get(array('k133', 'k233')));

		$this->assertTrue(self::_cache()->set('k333', 'v3'));
		$this->assertTrue(self::_cache()->set('k444', false));
		$this->assertSame(array('k333' => 'v3', 'k444' => false), self::_cache()->multi_get(array('k333', 'k444')));

		$this->assertTrue(self::_cache()->multi_del(array('k133', 'k233')));
		$this->assertSame(array(), self::_cache()->multi_get(array('k133', 'k233')));
		$this->assertSame('v3', self::_cache()->get('k333'));
		$this->assertFalse(self::_cache()->get('k444'));
	}
	public function test_del_by_prefix() {
		if (!self::_cache()->_driver->implemented['list_keys']) {
			return ;
		}
		$this->assertTrue(self::_cache()->flush());
		$this->assertTrue(self::_cache()->multi_set(array('k118' => 'v11', 'k218' => 'v21', 'k138' => 'v13')));
		$this->assertSame(array('k118' => 'v11', 'k218' => 'v21', 'k138' => 'v13'), self::_cache()->multi_get(array('k118', 'k218', 'k138')));
		$this->assertTrue(self::_cache()->del_by_prefix('k1'));
		$list_keys_result = self::_cache()->list_keys();
		if ($list_keys_result !== false && $list_keys_result !== null) {
			$this->assertSame('v21', self::_cache()->get('k218'));
			$this->assertNull(self::_cache()->get('k138'));
		}
	}
}
