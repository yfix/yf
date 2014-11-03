<?php

require_once dirname(__DIR__).'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class class_model_real_test extends db_real_abstract {
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
#		self::utils()->truncate_database(self::db_name());
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
	public function _fix_sql_php($sql_php) {
		$innodb_has_fulltext = self::_innodb_has_fulltext();
		if ( ! $innodb_has_fulltext) {
			// Remove fulltext indexes from db structure before creating table
			foreach ((array)$sql_php['indexes'] as $iname => $idx) {
				if ($idx['type'] == 'fulltext') {
					unset($sql_php['indexes'][$iname]);
				}
			}
		}
		foreach ((array)$sql_php['fields'] as $fname => $f) {
			unset($sql_php['fields'][$fname]['raw']);
			unset($sql_php['fields'][$fname]['collate']);
			unset($sql_php['fields'][$fname]['charset']);
			if ($f['default'] === 'NULL') {
				$sql_php['fields'][$fname]['default'] = null;
			}
		}
		foreach ((array)$sql_php['indexes'] as $fname => $f) {
			unset($sql_php['indexes'][$fname]['raw']);
		}
		foreach ((array)$sql_php['foreign_keys'] as $fname => $fk) {
			unset($sql_php['foreign_keys'][$fname]['raw']);
			if (is_null($fk['on_update'])) {
				$sql_php['foreign_keys'][$fname]['on_update'] = 'RESTRICT';
			}
			if (is_null($fk['on_delete'])) {
				$sql_php['foreign_keys'][$fname]['on_delete'] = 'RESTRICT';
			}
		}
		return $sql_php;
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

	/**
	*/
	public function test_basic_relations() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$model_base = _class('model'); // Need this to load basic model class
		$prefix = self::utils()->db->DB_PREFIX;

		// ------- Create sample tables ---------

		self::utils()->create_table($prefix.'bears', function($t) {
			$t->increments('id')
			->string('name')
			->string('type')
			->int('danger_level')
			->timestamps();
		});
		self::utils()->create_table($prefix.'fish', function($t) {
			$t->increments('id')
			->int('weight')
			->int('bear_id')
			->timestamps();
		});
		self::utils()->create_table($prefix.'trees', function($t) {
			$t->increments('id')
			->string('type')
			->int('age')
			->int('bear_id')
			->timestamps();
		});
		self::utils()->create_table($prefix.'picnics', function($t) {
			$t->increments('id')
			->string('name')
			->int('taste_level')
			->timestamps();
		});
		self::utils()->create_table($prefix.'bears_picnics', function($t) {
			$t->increments('id')
			->int('bear_id')
			->int('picnic_id')
			->timestamps();
		});

		$this->assertTrue(self::utils()->table_exists($prefix.'bears'));
		$this->assertTrue(self::utils()->table_exists($prefix.'fish'));
		$this->assertTrue(self::utils()->table_exists($prefix.'trees'));
		$this->assertTrue(self::utils()->table_exists($prefix.'picnics'));
		$this->assertTrue(self::utils()->table_exists($prefix.'bears_picnics'));

		// ----------- Create models -------------

		$this->assertFalse(class_exists('bears'));
		$this->assertFalse(class_exists('fish'));
		$this->assertFalse(class_exists('trees'));
		$this->assertFalse(class_exists('picnics'));
		$this->assertFalse(class_exists('bears_picnics'));

		// Eval nowdoc string syntax
		eval(
<<<'ND'
			class bear extends yf_model {
				// link this model to db table
				protected $_table = 'bears';
				// define which attributes are mass assignable (for security)
				protected $_fillable = array('name', 'type', 'danger_level');
				// each bear HAS one fish to eat
				public function fish() {
					return $this->has_one('fish');
				}
				// each bear climbs many trees
				public function trees() {
					return $this->has_many('tree');
				}
				// each bear BELONGS to many picnic. define our pivot table also
				public function picnics() {
					return $this->belongs_to_many('picnic', 'bears_picnics', 'bear_id', 'picnic_id');
				}
			}
			class fish extends yf_model {
				// link this model to db table
				protected $_table = 'fish';
				// define which attributes are mass assignable (for security)
				protected $_fillable = array('weight', 'bear_id');
				// relationships
				public function bear() {
					return $this->belongs_to('bear');
				}
			}
			class tree extends yf_model {
				// link this model to db table
				protected $_table = 'trees';
				// define which attributes are mass assignable (for security)
				protected $_fillable = array('type', 'age', 'bear_id');
				// relationships
				public function bear() {
					return $this->belongs_to('bear');
				}
			}
			class picnic extends yf_model {
				// link this model to db table
				protected $_table = 'picnics';
				// define which attributes are mass assignable (for security)
				protected $_fillable = array('name', 'taste_level');
				// define a many to many relationship. also call the linking table
				public function bears() {
					return $this->belongs_to_many('bear', 'bears_picnics', 'picnic_id', 'bear_id');
				}
			}
ND
		);

		$this->assertTrue(class_exists('bear'));
		$this->assertTrue(class_exists('fish'));
		$this->assertTrue(class_exists('tree'));
		$this->assertTrue(class_exists('picnic'));

		// --------- seed data --------------
		// bear 1 is named Lawly. She is extremely dangerous. Especially when hungry.
		$bear_lawly = bear::create(array(
			'name'         => 'Lawly',
			'type'         => 'Grizzly',
			'danger_level' => 8,
		));
		// bear 2 is named Cerms. He has a loud growl but is pretty much harmless.
		$bear_cerms = bear::create(array(
			'name'         => 'Cerms',
			'type'         => 'Black',
			'danger_level' => 4
		));
		// bear 3 is named Adobot. He is a polar bear.
		$bear_adobot = bear::create(array(
			'name'         => 'Adobot',
			'type'         => 'Polar',
			'danger_level' => 3
		));

		$this->assertInternalType('object', $bear_lawly);
		$this->assertInstanceOf('yf_model_result', $bear_lawly);
		$this->assertInstanceOf('yf_model', $bear_lawly->_get_model());
		$this->assertInstanceOf('bear', $bear_lawly->_get_model());
		$this->assertObjectHasAttribute('id', $bear_lawly);
		$this->assertObjectHasAttribute('name', $bear_lawly);
		$this->assertObjectHasAttribute('type', $bear_lawly);
		$this->assertObjectHasAttribute('danger_level', $bear_lawly);
		$this->assertSame('Lawly', $bear_lawly->name);
		$this->assertSame('Grizzly', $bear_lawly->type);
		$this->assertEquals('8', $bear_lawly->danger_level);

		$this->assertInternalType('object', $bear_cerms);
		$this->assertInstanceOf('yf_model_result', $bear_cerms);
		$this->assertInstanceOf('yf_model', $bear_cerms->_get_model());
		$this->assertInstanceOf('bear', $bear_cerms->_get_model());
		$this->assertObjectHasAttribute('id', $bear_cerms);
		$this->assertObjectHasAttribute('name', $bear_cerms);
		$this->assertObjectHasAttribute('type', $bear_cerms);
		$this->assertObjectHasAttribute('danger_level', $bear_cerms);
		$this->assertSame('Cerms', $bear_cerms->name);
		$this->assertSame('Black', $bear_cerms->type);
		$this->assertEquals('4', $bear_cerms->danger_level);

		$this->assertInternalType('object', $bear_adobot);
		$this->assertInstanceOf('yf_model_result', $bear_adobot);
		$this->assertInstanceOf('yf_model', $bear_adobot->_get_model());
		$this->assertInstanceOf('bear', $bear_adobot->_get_model());
		$this->assertObjectHasAttribute('id', $bear_adobot);
		$this->assertObjectHasAttribute('name', $bear_adobot);
		$this->assertObjectHasAttribute('type', $bear_adobot);
		$this->assertObjectHasAttribute('danger_level', $bear_adobot);
		$this->assertSame('Adobot', $bear_adobot->name);
		$this->assertSame('Polar', $bear_adobot->type);
		$this->assertEquals('3', $bear_adobot->danger_level);

		// seed our fish table. our fish wont have names... because theyre going to be eaten
		// we will use the variables we used to create the bears to get their id
		$fish1 = fish::create(array(
			'weight'  => '5',
			'bear_id' => $bear_lawly->id
		));
		$fish2 = fish::create(array(
			'weight'  => '12',
			'bear_id' => $bear_cerms->id
		));
		$fish3 = fish::create(array(
			'weight'  => '4',
			'bear_id' => $bear_adobot->id
		));

		$this->assertInternalType('object', $fish1);
		$this->assertInstanceOf('yf_model_result', $fish1);
		$this->assertInstanceOf('yf_model', $fish1->_get_model());
		$this->assertInstanceOf('fish', $fish1->_get_model());
		$this->assertObjectHasAttribute('id', $fish1);
		$this->assertObjectHasAttribute('weight', $fish1);
		$this->assertObjectHasAttribute('bear_id', $fish1);
		$this->assertEquals('5', $fish1->weight);
		$this->assertSame($bear_lawly->id, $fish1->bear_id);

		$this->assertInternalType('object', $fish2);
		$this->assertInstanceOf('yf_model_result', $fish2);
		$this->assertInstanceOf('yf_model', $fish2->_get_model());
		$this->assertInstanceOf('fish', $fish2->_get_model());
		$this->assertObjectHasAttribute('id', $fish2);
		$this->assertObjectHasAttribute('weight', $fish2);
		$this->assertObjectHasAttribute('bear_id', $fish2);
		$this->assertEquals('12', $fish2->weight);
		$this->assertSame($bear_cerms->id, $fish2->bear_id);

		$this->assertInternalType('object', $fish3);
		$this->assertInstanceOf('yf_model_result', $fish3);
		$this->assertInstanceOf('yf_model', $fish3->_get_model());
		$this->assertInstanceOf('fish', $fish3->_get_model());
		$this->assertObjectHasAttribute('id', $fish3);
		$this->assertObjectHasAttribute('weight', $fish3);
		$this->assertObjectHasAttribute('bear_id', $fish3);
		$this->assertEquals('4', $fish3->weight);
		$this->assertSame($bear_adobot->id, $fish3->bear_id);

		// seed our trees table
		$tree1 = tree::create(array(
			'type'    => 'Redwood',
			'age'     => '500',
			'bear_id' => $bear_lawly->id
		));
		$tree2 = tree::create(array(
			'type'    => 'Oak',
			'age'     => '400',
			'bear_id' => $bear_lawly->id
		));

		$this->assertInternalType('object', $tree1);
		$this->assertInstanceOf('yf_model_result', $tree1);
		$this->assertInstanceOf('yf_model', $tree1->_get_model());
		$this->assertInstanceOf('tree', $tree1->_get_model());
		$this->assertObjectHasAttribute('id', $tree1);
		$this->assertObjectHasAttribute('type', $tree1);
		$this->assertObjectHasAttribute('age', $tree1);
		$this->assertObjectHasAttribute('bear_id', $tree1);
		$this->assertSame('Redwood', $tree1->type);
		$this->assertEquals('500', $tree1->age);
		$this->assertSame($bear_lawly->id, $tree1->bear_id);

		$this->assertInternalType('object', $tree2);
		$this->assertInstanceOf('yf_model_result', $tree2);
		$this->assertInstanceOf('yf_model', $tree2->_get_model());
		$this->assertInstanceOf('tree', $tree2->_get_model());
		$this->assertObjectHasAttribute('id', $tree2);
		$this->assertObjectHasAttribute('type', $tree2);
		$this->assertObjectHasAttribute('age', $tree2);
		$this->assertObjectHasAttribute('bear_id', $tree2);
		$this->assertSame('Oak', $tree2->type);
		$this->assertEquals('400', $tree2->age);
		$this->assertSame($bear_lawly->id, $tree2->bear_id);

		// we will create one picnic and apply all bears to this one picnic
		$picnic_yellowstone = picnic::create(array(
			'name'        => 'Yellowstone',
			'taste_level' => '6'
		));
		$picnic_grand_canyon = picnic::create(array(
			'name'        => 'Grand Canyon',
			'taste_level' => '5'
		));

		// link our bears to picnics
		// for our purposes we'll just add all bears to both picnics for our many to many relationship
		$bear_lawly->picnics()->attach($picnic_yellowstone->id);
		$bear_lawly->picnics()->attach($picnic_grand_canyon->id);

		$bear_cerms->picnics()->attach($picnic_yellowstone->id);
		$bear_cerms->picnics()->attach($picnic_grand_canyon->id);

		$bear_adobot->picnics()->attach($picnic_yellowstone->id);
		$bear_adobot->picnics()->attach($picnic_grand_canyon->id);

		$this->assertInternalType('object', $picnic_yellowstone);
		$this->assertInstanceOf('yf_model_result', $picnic_yellowstone);
		$this->assertInstanceOf('yf_model', $picnic_yellowstone->_get_model());
		$this->assertInstanceOf('picnic', $picnic_yellowstone->_get_model());
		$this->assertObjectHasAttribute('id', $picnic_yellowstone);
		$this->assertObjectHasAttribute('name', $picnic_yellowstone);
		$this->assertObjectHasAttribute('taste_level', $picnic_yellowstone);
		$this->assertSame('Yellowstone', $picnic_yellowstone->name);
		$this->assertEquals('6', $picnic_yellowstone->taste_level);
#		$this->assertObjectHasAttribute('bear_id', $picnic_yellowstone);
#		$this->assertSame($bear_lawly->id, $picnic_yellowstone->bear_id);

		$this->assertInternalType('object', $picnic_grand_canyon);
		$this->assertInstanceOf('yf_model_result', $picnic_grand_canyon);
		$this->assertInstanceOf('yf_model', $picnic_grand_canyon->_get_model());
		$this->assertInstanceOf('picnic', $picnic_grand_canyon->_get_model());
		$this->assertObjectHasAttribute('id', $picnic_grand_canyon);
		$this->assertObjectHasAttribute('name', $picnic_grand_canyon);
		$this->assertObjectHasAttribute('taste_level', $picnic_grand_canyon);
		$this->assertSame('Grand Canyon', $picnic_grand_canyon->name);
		$this->assertEquals('5', $picnic_grand_canyon->taste_level);
#		$this->assertObjectHasAttribute('bear_id', $picnic_grand_canyon);
#		$this->assertSame($bear_lawly->id, $picnic_grand_canyon->bear_id);

		// ----------- alternate creating models -----------

		$bear_cool1 = bear::create(array(
			'name'         => 'Super Cool1',
			'type'         => 'Black',
			'danger_level' => 1
		));

		// alternatively you can create an object, assign values, then save
		$bear_cool2               = new bear;
		$bear_cool2->name         = 'Super Cool2';
		$bear_cool2->type         = 'Black';
		$bear_cool2->danger_level = 1;
		$bear_cool2_saved = $bear_cool2->save();

		$this->assertInternalType('object', $bear_cool1);
		$this->assertInstanceOf('yf_model_result', $bear_cool1);
		$this->assertInstanceOf('yf_model', $bear_cool1->_get_model());
		$this->assertInstanceOf('bear', $bear_cool1->_get_model());
		$this->assertObjectHasAttribute('id', $bear_cool1);
		$this->assertObjectHasAttribute('name', $bear_cool1);
		$this->assertObjectHasAttribute('type', $bear_cool1);
		$this->assertObjectHasAttribute('danger_level', $bear_cool1);
		$this->assertSame('Super Cool1', $bear_cool1->name);
		$this->assertSame('Black', $bear_cool1->type);
		$this->assertEquals('1', $bear_cool1->danger_level);

		$this->assertTrue((bool)$bear_cool2_saved);
		$this->assertInternalType('object', $bear_cool2);
		$this->assertInstanceOf('yf_model', $bear_cool2);
		$this->assertInstanceOf('bear', $bear_cool2);
		$this->assertObjectHasAttribute('id', $bear_cool2);
		$this->assertObjectHasAttribute('name', $bear_cool2);
		$this->assertObjectHasAttribute('type', $bear_cool2);
		$this->assertObjectHasAttribute('danger_level', $bear_cool2);
		$this->assertSame('Super Cool2', $bear_cool2->name);
		$this->assertSame('Black', $bear_cool2->type);
		$this->assertEquals('1', $bear_cool2->danger_level);

		// ----------- querying models -----------

		// find the bear or create it into the database
		$bear_first1 = bear::first_or_create(array('name' => 'Lawly'));
		$bear_first2 = bear::first_or_create(array('name' => 'Lawly2'));
		// find the bear or instantiate a new instance into the object we want
		$bear_new1 = bear::first_or_new(array('name' => 'Cerms'));
		$bear_new2 = bear::first_or_new(array('name' => 'Cerms2'));
		// get all the bears
		$bears = bear::all();
		// find a specific bear by id
		$bear_id2 = bear::find(2);
		// find a bear by a specific attribute
		$bear_cerms_first = bear::where('name', '=', 'Cerms')->first();
		// find a bear with danger level greater than 2
		$dangerous_bears = bear::all('danger_level', '>', 2);

		$this->assertInternalType('object', $bear_first1);
		$this->assertInstanceOf('yf_model_result', $bear_first1);
		$this->assertInstanceOf('yf_model', $bear_first1->_get_model());
		$this->assertInstanceOf('bear', $bear_first1->_get_model());
		$this->assertObjectHasAttribute('id', $bear_first1);
		$this->assertSame('Lawly', $bear_first1->name);
		$this->assertNotEmpty($bear_lawly->type);
		$this->assertSame($bear_lawly->type, $bear_first1->type);
		$this->assertEquals($bear_lawly->danger_level, $bear_first1->danger_level);

		$this->assertNotSame($bear_first1, $bear_first2);
		$this->assertInternalType('object', $bear_first2);
		$this->assertInstanceOf('yf_model_result', $bear_first2);
		$this->assertInstanceOf('yf_model', $bear_first2->_get_model());
		$this->assertInstanceOf('bear', $bear_first2->_get_model());
		$this->assertObjectHasAttribute('id', $bear_first2);
		$this->assertSame('Lawly2', $bear_first2->name);
		$this->assertSame('', $bear_first2->type);
		$this->assertEquals('0', $bear_first2->danger_level);

		$this->assertInternalType('object', $bear_new1);
		$this->assertInstanceOf('yf_model_result', $bear_new1);
		$this->assertInstanceOf('yf_model', $bear_new1->_get_model());
		$this->assertInstanceOf('bear', $bear_new1->_get_model());
		$this->assertObjectHasAttribute('id', $bear_new1);
		$this->assertEquals($bear_cerms->id, $bear_new1->id);
		$this->assertEquals($bear_cerms->name, $bear_new1->name);
		$this->assertEquals($bear_cerms, $bear_new1);

		$this->assertNotSame($bear_new1, $bear_new2);
		$this->assertInternalType('object', $bear_new2);
		$this->assertInstanceOf('yf_model', $bear_new2);
		$this->assertInstanceOf('bear', $bear_new2);
		$this->assertNull($bear_new2->id);
		$this->assertEquals('Cerms2', $bear_new2->name);

		$this->assertInternalType('array', $bears);
		$this->assertNotEmpty($bears);
		$bears_array = array();
		foreach ((array)$bears as $k => $v) {
			$bears_array[$k] = $v->get_data();
			unset($bears_array[$k]['created_at']);
			unset($bears_array[$k]['updated_at']);
		}
		$expected = array(
			1 => array('id' => '1', 'name' => 'Lawly', 'type' => 'Grizzly', 'danger_level' => '8'),
			2 => array('id' => '2', 'name' => 'Cerms', 'type' => 'Black', 'danger_level' => '4'),
			3 => array('id' => '3', 'name' => 'Adobot', 'type' => 'Polar', 'danger_level' => '3'),
			4 => array('id' => '4', 'name' => 'Super Cool1', 'type' => 'Black', 'danger_level' => '1'),
			5 => array('id' => '5', 'name' => 'Super Cool2', 'type' => 'Black', 'danger_level' => '1'),
			6 => array('id' => '6', 'name' => 'Lawly2', 'type' => '', 'danger_level' => '0'),
		);
		$this->assertEquals($expected, $bears_array);
		$this->assertEquals($expected[2]['id'], $bear_id2->id);
		$this->assertEquals($expected[2]['name'], $bear_id2->name);
		$this->assertEquals($expected[2]['type'], $bear_id2->type);

		$this->assertInternalType('object', $bear_cerms_first);
		$this->assertNotSame($bear_cerms, $bear_cerms_first);
		$this->assertEquals($bear_cerms->id, $bear_cerms_first->id);
		$this->assertEquals($bear_cerms->name, $bear_cerms_first->name);
		$this->assertEquals($bear_cerms->type, $bear_cerms_first->type);

		$this->assertInternalType('array', $dangerous_bears);
		$this->assertEquals('3', count($dangerous_bears));

		// ----------- changing models -----------

		// let's change the danger level of Lawly to level 10
		$lawly = bear::where('name', '=', 'Lawly')->first();
		$this->assertEquals($bear_lawly->danger_level, $lawly->danger_level);
		$this->assertNotEquals('10', $lawly->danger_level);
		$lawly->danger_level = 10;
		$lawly->save();

		$this->assertEquals('10', $lawly->danger_level);

		// ------ query one-to-one relationships ------

		// find a bear named Adobot
		$adobot = bear::where('name', '=', 'Adobot')->first();
		// get the fish that Adobot has
		$fish = $adobot->fish;
		// get the weight of the fish Adobot is going to eat
		$weight1 = $fish->weight;
		// alternatively you could go straight to the weight attribute
		$weight2 = $adobot->fish->weight;
/*
		// ------ query one-to-many relationships ------

		// find the trees lawly climbs
		$lawly = bear::where('name', '=', 'Lawly')->first();
		foreach ($lawly->trees as $tree) {
			echo $tree->type . ' ' . $tree->age;
		}

		// ------ query many-to-many relationships ------

		// get the picnics that Cerms goes to ------------------------
		$cerms = bear::where('name', '=', 'Cerms')->first();
		// get the picnics and their names and taste levels
		foreach ($cerms->picnics as $picnic) {
			echo $picnic->name . ' ' . $picnic->taste_level;
		}
		// get the bears that go to the Grand Canyon picnic -------------
		$grand_canyon = picnic::where('name', '=', 'Grand Canyon')->first();
		// show the bears
		foreach ($grand_canyon->bears as $bear)
			echo $bear->name . ' ' . $bear->type . ' ' . $bear->danger_level;
		}
*/
		// ------ deleting models ------
/*
		// find and delete a record
		$bear = bear::find(1);
		$bear->delete();
		// delete a record 
		bear::destroy(1);
		// delete multiple records 
		bear::destroy(1, 2, 3);
		// find and delete all bears with a danger level over 5
		bear::where('danger_level', '>', 5)->delete();
*/
	}

	/***/
	public function test_load_fixtures() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		self::utils()->truncate_database(self::db_name());

		$db_prefix = self::db()->DB_PREFIX;
		$plen = strlen($db_prefix);
		$innodb_has_fulltext = self::_innodb_has_fulltext();

		$this->assertEquals( array(), self::utils()->list_tables(self::db_name()) );

		$parser = _class('db_ddl_parser_mysql', 'classes/db/');
		$parser->RAW_IN_RESULTS = false;

		$tables_php = array();
		$ext = '.sql_php.php';
		$globs_php = array(
			'fixtures'	=> __DIR__.'/fixtures/*'.$ext,
		);
		foreach ($globs_php as $glob) {
			foreach (glob($glob) as $f) {
				$t_name = substr(basename($f), 0, -strlen($ext));
				$tables_php[$t_name] = include $f; // $data should be loaded from file
			}
		}
		$tables_data = array();
		$ext = '.data.php';
		$globs_data = array(
			'fixtures'	=> __DIR__.'/fixtures/*'.$ext,
		);
		foreach ($globs_data as $glob) {
			foreach (glob($glob) as $f) {
				$t_name = substr(basename($f), 0, -strlen($ext));
				$tables_data[$t_name] = include $f; // $data should be loaded from file
			}
		}
		$this->assertNotEmpty($tables_php);
		$this->assertTrue( (bool)self::db()->query('SET foreign_key_checks = 0;') );
		foreach ((array)$tables_php as $name => $sql_php) {
			$sql_php = $this->_fix_sql_php($sql_php);
			$this->assertTrue( is_array($sql_php) && count($sql_php) && $sql_php );
			$this->assertTrue( (bool)self::utils()->create_table($name, $sql_php), 'creating table: '.$db_prefix.$name );
			$this->assertTrue( (bool)self::utils()->table_exists(self::table_name($db_prefix.$name)) );

			$columns = self::utils()->list_columns(self::table_name($db_prefix.$name));
			foreach ((array)$columns as $fname => $f) {
				unset($columns[$fname]['type_raw']);
				unset($columns[$fname]['collate']);
				unset($columns[$fname]['charset']);
			}
			$this->assertEquals( $sql_php['fields'], $columns, 'Compare columns with expected sql_php for table: '.$name );
			$indexes = self::utils()->list_indexes(self::table_name($db_prefix.$name));
			$this->assertEquals( $sql_php['indexes'], $indexes, 'Compare indexes with expected sql_php for table: '.$name );
			$fks = self::utils()->list_foreign_keys(self::table_name($db_prefix.$name));
			if ($plen) {
				foreach ((array)$fks as $fname => $finfo) {
					$fks[$fname]['ref_table'] = substr($finfo['ref_table'], $plen);
				}
			}
			$this->assertEquals( $sql_php['foreign_keys'], $fks, 'Compare indexes with expected sql_php for table: '.$name );

			$table_data = $tables_data[$name];
			if ($table_data) {
				$this->assertTrue( (bool)self::db()->insert_safe($name, $table_data) );
			}
			$real_data = self::db()->from($name)->get_all();
			$this->assertEquals($table_data, $real_data);
if ($i++ > 3) {
	break;
}
#break;
		}

		$this->assertTrue( (bool)self::db()->query('SET foreign_key_checks = 1;') );
	}

	/**
	* @depends test_load_fixtures
	*/
	public function test_sakila_basic() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$actors_data = include __DIR__.'/fixtures/actor.data.php';
		$actors_data_objects = array();
		foreach ($actors_data as $arr) {
			$actors_data_objects[] = (object)$arr;
		}
/*
		$all_actors = model('actor')->all();
#		$this->assertTrue( is_array($all_actors) );
#		$this->assertTrue( (count($all_actors) > 0) );
#		$this->assertEquals( $actors_data_objects, $all_actors );
		$this->assertEquals( $actors_data, $all_actors );
*/

		$raw_first_id = $actors_data[0]['actor_id'];
		$this->assertNotEmpty( $raw_first_id );
		$first_actor = model('actor')->find($raw_first_id);
		$this->assertNotEmpty( $actors_data_objects[0] );

		foreach ($actors_data_objects[0] as $k => $v) {
			$this->assertEquals( $v, $first_actor->$k );
		}
#		$same = true;
#		foreach ($actors_data_objects[0] as $k => $v) {
#			if ($v != $first_actor->$k) {
#				$same = false;
#				break;
#			}
#		}
#		$this->assertTrue( $same, 'These objects should be same: '. print_r($actors_data_objects[0], 1). PHP_EOL. print_r($first_actor, 1) );

#		$this->assertEquals( $actors_data_objects[0], $first_actor );
		$this->assertEquals( $raw_first_id, $first_actor->actor_id );

		$raw_second_id = $actors_data[1]['actor_id'];
		$this->assertNotEmpty( $raw_second_id );

/*
		$second_actor = model('actor')->find($raw_second_id);
		$this->assertNotEmpty( $actors_data_objects[1] );
		$this->assertEquals( $actors_data_objects[1], $second_actor );
		$this->assertEquals( $raw_second_id, $second_actor->actor_id );

		$raw_some_actors = array();
		foreach ($actors_data_objects as $i => $a) {
			if ($a->actor_id < 10) {
				$raw_some_actors[$i] = $a;
			}
		}
		$this->assertNotEmpty( $raw_some_actors );

		$some_actors = model('actor')->all('actor_id < 10');
		$this->assertEquals( $raw_some_actors, $some_actors );
		unset($some_actors);

		$some_actors = model('actor')->where('actor_id < 10')->all();
		$this->assertEquals( $raw_some_actors, $some_actors );
		unset($some_actors);

		$some_actors = model('actor')->where('actor_id < 10')->limit(1)->all();
		$this->assertEquals( array_slice($raw_some_actors, 0, 1, true), $some_actors );
		unset($some_actors);

		$some_actors = model('actor')->where('actor_id < 10')->limit(1,1)->all();
		$this->assertEquals( current(array_slice($raw_some_actors, 1, 1, true)), current($some_actors) );
		$this->assertNotNull( $some_actors[0]->first_name );
		unset($some_actors);

		$some_actors = model('actor')->where('actor_id < 10')->select('actor_id')->limit(0,1)->all();
		$this->assertEquals( $raw_some_actors[0]->actor_id, $some_actors[0]->actor_id );
		$this->assertNull( $some_actors[0]->first_name );
		unset($some_actors);

		$some_actors = model('actor')->all(1);
		$this->assertEquals( $raw_some_actors[0], $some_actors[0] );
		unset($some_actors);

		$some_actors = model('actor')->get(1);
		$this->assertEquals( $raw_some_actors[0], $some_actors );
		unset($some_actors);

#		$some_actors = model('actor')->whereid(1)->all();
#		$this->assertEquals( $raw_some_actors[0], $some_actors[0] );
#		unset($some_actors);

#		$some_actors = model('actor')->whereid(1)->get();
#		$this->assertEquals( $raw_some_actors[0], $some_actors );
#		unset($some_actors);

		$some_actors = model('actor')->where('actor_id < 10')->order_by('actor_id desc')->all();
		$this->assertEquals( array_reverse($raw_some_actors), $some_actors );
		unset($some_actors);

#		$some_actors = model('actor')->all(array('where' => 'actor_id < 10', 'order_by' => 'actor_id desc'));
#		$this->assertEquals( array_reverse($raw_some_actors), $some_actors );
#		unset($some_actors);
*/
	}

	/**
	* @depends test_load_fixtures
	*/
	public function test_sakila_save() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$actors_data = include __DIR__.'/fixtures/actor.data.php';
		$actors_data_objects = array();
		foreach ($actors_data as $arr) {
			$actors_data_objects[] = (object)$arr;
		}

		$raw_first_id = $actors_data[0]['actor_id'];
		$this->assertNotEmpty( $raw_first_id );
		$first_actor = model('actor')->find($raw_first_id);
		$new_name = 'some new name';
		$this->assertNotEmpty( $first_actor->first_name );
		$this->assertNotEquals( $first_actor->first_name, $new_name );
		$first_actor->first_name = $new_name;
#		$first_actor->save();
		$this->assertEquals( $first_actor->first_name, $new_name );

		$first_actor_copy = model('actor')->find($raw_first_id);
		$this->assertNotEquals( $first_actor, $first_actor_copy );
#		$this->assertEquals( $first_actor_copy->first_name, $new_name );
	}

	/**
	* @depends test_load_fixtures
	*/
	public function test_sakila_relations() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$actors_data = include __DIR__.'/fixtures/actor.data.php';
		$actors_data_objects = array();
		foreach ($actors_data as $arr) {
			$actors_data_objects[] = (object)$arr;
		}

		// One-one relation test
#		print_r(
#			self::utils()->table_info('actor')
#		);


#		$all_actors_having_films = model('actor')->has('films')->all();
#print_r($all_actors_having_films);
#		$this->assertEquals( array_reverse($raw_some_actors), $some_actors );
#		unset($some_actors);

#		$all_film_titles_with_actor1 = foreach (model('actor')->find(1)->films() as $film) { echo $film->title; }
#		$film1_titles_with_actor1 = model('actor')->find(1)->films()->first()->title;
#		$film1_titles_with_actor1 = foreach (model('actor')->with('films') as $actor) { echo $actor->films()->first()->title; }
	}

	/**
	* @depends test_load_fixtures
	*/
	public function test_sakila_all_models() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
/*
		$model_base = _class('model');
		$this->assertTrue( is_object($model_base) );
		$this->assertTrue( is_a($model_base, 'yf_model') );
		$this->assertSame( $model_base, _class('yf_model') );

		$base_methods = get_class_methods($model_base);
#		$base_vars = get_object_vars($model_base);

		$db_prefix = self::db()->DB_PREFIX;
		$plen = strlen($db_prefix);

		foreach ((array)self::utils()->list_tables(self::db_name()) as $table) {
			$table = substr($table, $plen);
			$model = self::$db->model($table);
			$methods = get_class_methods($model);
			$model_specific_methods = array_diff($methods, $base_methods);
#echo $table.PHP_EOL;
#print_r($model_specific_methods);
			foreach ($model_specific_methods as $_method) {
				if (substr($_method, 0, 1) === '_') {
					continue;
				}
#echo $_method.PHP_EOL;
				$result = $model->$_method()->get();
#var_dump($result);
			}
#			$vars = get_object_vars($model);
#print_r(array_diff($vars, $base_vars));
		}
*/
	}

	/**
	* Just for tests development
	*/
	public function test_dump_sakila_data() {
/*
		$db_name = 'sakila';
		foreach((array)self::utils()->list_tables($db_name) as $table) {
			$file = __DIR__.'/fixtures/'.$table.'.data.php';
			if (file_exists($file)) {
				continue;
			}
			$data = self::db()->get_all('SELECT * FROM '.$db_name.'.'.$table);
			if (empty($data)) {
				continue;
			}
			echo 'Saved data ('.count($data).'): '.$file. PHP_EOL;
			file_put_contents($file, '<?'.'php'.PHP_EOL.'return '._var_export($data, 1).';');
		}
*/
	}
}
