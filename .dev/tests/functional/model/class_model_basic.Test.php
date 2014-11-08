<?php

require_once dirname(__DIR__).'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class class_model_basic_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'mysql5';
		self::_connect();
		self::utils()->truncate_database(self::db_name());
		self::$_bak['ERROR_AUTO_REPAIR'] = self::db()->ERROR_AUTO_REPAIR;
		self::db()->ERROR_AUTO_REPAIR = true;
		$GLOBALS['db'] = self::db();

		// unit_tests == name of the custom storage used here
		// Ensure unit_tests will be on top of the storages list
		main()->_custom_class_storages['*_model'] = array('unit_tests' => array(__DIR__.'/fixtures/')) + (array)main()->_custom_class_storages['*_model'];
	}
	public static function tearDownAfterClass() {
		self::utils()->truncate_database(self::db_name());
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
		self::db()->ERROR_AUTO_REPAIR = self::$_bak['ERROR_AUTO_REPAIR'];
	}
	public static function db_name() {
		return self::$DB_NAME;
	}
	public static function table_name($name) {
		return $name;
	}

	/***/
	public function test_basic() {
		$model_base = _class('model');
		$this->assertTrue( is_object($model_base) );
		$this->assertTrue( is_a($model_base, 'yf_model') );
		$this->assertSame( $model_base, _class('yf_model') );

		$model_exists = main()->_class_exists('film_model');
		if (!$model_exists) {
			$this->assertTrue( main()->_class_exists('film_model') );
		}

		$film_model = _class('film_model');
		$this->assertTrue( is_object($film_model) );
		$this->assertTrue( is_a($film_model, 'film_model') );
		$this->assertTrue( is_a($film_model, 'yf_model') );

		$film_model2 = model('film');
		$this->assertNotSame( $film_model2, $film_model );
		$this->assertTrue( is_object($film_model2) );
		$this->assertTrue( is_a($film_model2, 'film_model') );
		$this->assertTrue( is_a($film_model2, 'yf_model') );

		$film_model3 = model('film');
		$this->assertNotSame( $film_model2, $film_model3 );
		$this->assertTrue( is_object($film_model2) );
		$this->assertTrue( is_a($film_model2, 'film_model') );
		$this->assertTrue( is_a($film_model2, 'yf_model') );
	}

	/***/
	public function test_short_name_autoload() {
		$model_base = _class('model');
		$m = __FUNCTION__.'_model';
		eval('class '.$m.' extends yf_model {}');
		self::utils()->create_table(__FUNCTION__, function($t) {
			$t->increments('id');
		});
		$m::create(array('id' => 1));
		$m::find(1);
		$m_short = __FUNCTION__;
#		$m_short::find(1);
	}

	/***/
	public function test_where() {
		$model_base = _class('model');
		$m = __FUNCTION__.'_model';
		eval('class '.$m.' extends yf_model {}');
		self::utils()->create_table(__FUNCTION__, function($t) {
			$t->increments('id')
			->string('name')
			->string('gender')
			->int('popularity');
		});
		$m::create(array('name' => 'Susan', 'gender' => 'w', 'popularity' => 8));
		$m::create(array('name' => 'Michael', 'gender' => 'm', 'popularity' => 12));

#		$m::where_popular('>','10');
#		$m::where_gender('w');
#		$m::where_name($wildcard);
	}

	/***/
	public function test_scopes() {
		$model_base = _class('model');
		eval(
<<<'ND'
			class test_scopes_model extends yf_model {
				public function scope_popular($query) {
					return $query->where('popular','>','10');
#					return $query->where_popular('>','10');
				}
				public function scope_women($query) {
					return $query->where('gender','w');
#					return $query->where_gender('w');
				}
				public function scope_name($query, $wildcard) {
					return $query->where('name',$wildcard);
#					return $query->where_name($wildcard);
				}
			}
ND
		);
		self::utils()->create_table(__FUNCTION__, function($t) {
			$t->increments('id')
			->string('name')
			->string('gender')
			->int('popularity');
		});
		$m = __FUNCTION__.'_model';
#		test_scopes::create(array('name' => 'Susan', 'gender' => 'w', 'popularity' => 8));
		$m::create(array('name' => 'Susan', 'gender' => 'w', 'popularity' => 8));
		$m::create(array('name' => 'Michael', 'gender' => 'm', 'popularity' => 12));
		$m::create(array('name' => 'Marilyn', 'gender' => 'w', 'popularity' => 11));
		$m::create(array('name' => 'Brigitte', 'gender' => 'w', 'popularity' => 11));

#		$m::popular()->order_by('name')->get();
#		$m::popular()->women()->order_by('name', 'desc')->get();
#		$m::popular()->women()->name('mary*')->select('name')->one();
	}

	/***/
	public function test_accessors_and_mutators() {
		$model_base = _class('model');
		eval(
<<<'ND'
			class test_accessors_and_mutators_model extends yf_model {
				public function get_attr_name($value) {
					return ucfirst($value);
				}
				public function set_attr_name($value) {
					return strtolower($value);
				}
				public function get_attr_popularity($value) {
					return 'Popularity: '.$value;
				}
				public function set_attr_popularity($value) {
					return intval($value);
				}
			}
ND
		);
		self::utils()->create_table(__FUNCTION__, function($t) {
			$t->increments('id')
			->string('name')
			->string('gender')
			->int('popularity');
		});
		$m = __FUNCTION__.'_model';
		$m::create(array('name' => 'Susan', 'gender' => 'w', 'popularity' => 8));

#		$m1 = $m::find(1);
#		$m1->popularity;

#		$m1->popularity = '15';
#		$m1->save();

#		$m1->set('popularity', '15')->save();

#		$m1->name;
#		$m1->set('name', '15')->save();
	}
}
