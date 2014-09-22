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
		$this->assertEquals(self::_get_driver_name(), self::_cache()->DRIVER);
	}
	public function test_is_ready() {
		$this->assertTrue(self::_cache()->_driver_ok);
		$this->assertNotEmpty(self::_cache()->_driver);
		$this->assertTrue(self::_cache()->_driver->is_ready());
	}
	public function test_get() {
		self::_cache()->flush();
		$this->assertNull(@self::_cache()->get());
		$this->assertNull(self::_cache()->get('k1'));

		self::_cache()->set('k1', 'val1');
		$this->assertEquals('val1', self::_cache()->get('k1'));

		self::_cache()->set('k11', 0);
		$this->assertEquals(0, self::_cache()->get('k11'));
	}
	public function test_set() {
		self::_cache()->flush();
		$this->assertNotEmpty(self::_cache()->set('k2', 'some_data'));
		$this->assertEquals('some_data', self::_cache()->get('k2'));
		$this->assertNotEmpty(self::_cache()->set('k2_', array()));
		$this->assertEquals(array(), self::_cache()->get('k2_'));
	}
	public function test_del() {
		self::_cache()->flush();
		self::_cache()->set('k3', 'val3');
		$this->assertEquals('val3', self::_cache()->get('k3'));
		$this->assertTrue(self::_cache()->del('k3'));
		$this->assertNull(self::_cache()->get('k3'));
	}
	public function test_flush() {
		self::_cache()->flush();
		self::_cache()->set('k4', 'val4');
		$this->assertTrue(self::_cache()->flush());
		$this->assertNull(self::_cache()->get('k4'));
		$list_keys_result = self::_cache()->list_keys();
		if ($list_keys_result !== false && $list_keys_result !== null) {
			$this->assertEquals(array(), self::_cache()->list_keys());
		}
	}
	public function test_list_keys() {
		if (!self::_cache()->_driver->implemented['list_keys']) {
			return ;
		}
		$this->assertTrue(self::_cache()->flush());
		self::_cache()->set('k1', 'v1');
		self::_cache()->set('k2', 'v2');
		$list_keys_result = self::_cache()->list_keys();
		if ($list_keys_result !== false && $list_keys_result !== null) {
			$this->assertEquals(array('k1', 'k2'), self::_cache()->list_keys());
			self::_cache()->set('k3', 'v3');
			$this->assertEquals(array('k1', 'k2', 'k3'), self::_cache()->list_keys());
		}
	}
	public function test_multi_get() {
		$this->assertTrue(self::_cache()->flush());
		self::_cache()->set('k17', 'v1');
		self::_cache()->set('k27', 'v2');
		$this->assertEquals(array('k17' => 'v1', 'k27' => 'v2'), self::_cache()->multi_get(array('k17', 'k27')));
	}
	public function test_multi_set() {
		$this->assertTrue(self::_cache()->flush());
		$this->assertEquals(array(), self::_cache()->multi_get(array('k1', 'k2')));
		$this->assertEquals(true, self::_cache()->multi_set(array('k111' => 'v1', 'k222' => 'v2')));
		$this->assertEquals(array('k111' => 'v1', 'k222' => 'v2'), self::_cache()->multi_get(array('k111', 'k222')));
	}
	public function test_multi_del() {
		$this->assertTrue(self::_cache()->flush());
		$this->assertEquals(array(), self::_cache()->multi_get(array('k1', 'k2')));
		$this->assertEquals(true, self::_cache()->multi_set(array('k1' => 'v1', 'k2' => 'v2')));
		$this->assertTrue(self::_cache()->set('k333', 'v3'));
		self::_cache()->multi_del(array('k133', 'k233'));
		$this->assertEquals(array(), self::_cache()->multi_get(array('k133', 'k233')));
		$this->assertEquals('v3', self::_cache()->get('k333'));
	}
	public function test_del_by_prefix() {
		if (!self::_cache()->_driver->implemented['list_keys']) {
			return ;
		}
		$this->assertTrue(self::_cache()->flush());
		self::_cache()->multi_set(array('k118' => 'v11', 'k218' => 'v21', 'k138' => 'v13'));
		$this->assertEquals(array('k118' => 'v11', 'k218' => 'v21', 'k138' => 'v13'), self::_cache()->multi_get(array('k118', 'k218', 'k138')));
		self::_cache()->del_by_prefix('k1');
		$list_keys_result = self::_cache()->list_keys();
		if ($list_keys_result !== false && $list_keys_result !== null) {
			$this->assertEquals('v21', self::_cache()->get('k218'));
			$this->assertNull(self::_cache()->get('k138'));
		}
	}
}
