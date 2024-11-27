<?php

use PHPUnit\Framework\Attributes\Depends;

require_once dirname(__DIR__) . '/db_real_abstract.php';

/**
 */
class class_model_bears_test extends db_real_abstract
{
    public static function db_name()
    {
        return self::$DB_NAME;
    }
    public static function table_name($name)
    {
        return $name;
    }
    public static function setUpBeforeClass(): void
    {
        self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
        self::$DB_DRIVER = 'mysqli';
        self::_connect();
        self::utils()->truncate_database(self::db_name());
        self::$_bak['ERROR_AUTO_REPAIR'] = self::db()->ERROR_AUTO_REPAIR;
        self::db()->ERROR_AUTO_REPAIR = true;
        $GLOBALS['db'] = self::db();

        // unit_tests == name of the custom storage used here
        // Ensure unit_tests will be on top of the storages list
        main()->_custom_class_storages['*_model'] = ['unit_tests' => [__DIR__ . '/fixtures/']] + (array) main()->_custom_class_storages['*_model'];
    }
    public static function tearDownAfterClass(): void
    {
        self::utils()->truncate_database(self::db_name());
        self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
        self::db()->ERROR_AUTO_REPAIR = self::$_bak['ERROR_AUTO_REPAIR'];
    }

    /**
     * Create sample tables.
     */
    public function create_tables()
    {
        try {
            self::utils()->create_table('bears', function ($t) {
                $t->increments('id')
                    ->string('name')
                    ->string('type')
                    ->int('danger_level')
                    ->timestamps();
            });
            self::utils()->create_table('fish', function ($t) {
                $t->increments('id')
                    ->int('weight')
                    ->int('bear_id')
                    ->timestamps();
            });
            self::utils()->create_table('trees', function ($t) {
                $t->increments('id')
                    ->string('type')
                    ->int('age')
                    ->int('bear_id')
                    ->timestamps();
            });
            self::utils()->create_table('picnics', function ($t) {
                $t->increments('id')
                    ->string('name')
                    ->int('taste_level')
                    ->timestamps();
            });
            self::utils()->create_table('bears_picnics', function ($t) {
                $t->increments('id')
                    ->int('bear_id')
                    ->int('picnic_id')
                    ->timestamps();
            });

            $this->assertTrue(self::utils()->table_exists('bears'));
            $this->assertTrue(self::utils()->table_exists('fish'));
            $this->assertTrue(self::utils()->table_exists('trees'));
            $this->assertTrue(self::utils()->table_exists('picnics'));
            $this->assertTrue(self::utils()->table_exists('bears_picnics'));
        } catch (Exception $e) {
            echo self::_pretty_show_exception($e);
        }
    }

    /**
     * Create models.
     */
    public function create_models()
    {
        try {
            $this->assertFalse(class_exists('bears'));
            $this->assertFalse(class_exists('fish'));
            $this->assertFalse(class_exists('trees'));
            $this->assertFalse(class_exists('picnics'));
            $this->assertFalse(class_exists('bears_picnics'));

            // Eval nowdoc string syntax
            eval(<<<'ND'
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
ND);

            $this->assertTrue(class_exists('bear'));
            $this->assertTrue(class_exists('fish'));
            $this->assertTrue(class_exists('tree'));
            $this->assertTrue(class_exists('picnic'));
        } catch (Exception $e) {
            echo self::_pretty_show_exception($e);
        }
    }

    /**
     * Seed test data.
     */
    public function create_data()
    {
        try {
            // bear 1 is named Lawly. She is extremely dangerous. Especially when hungry.
            $bear_lawly = bear::create([
                'name' => 'Lawly',
                'type' => 'Grizzly',
                'danger_level' => 8,
            ]);
            // bear 2 is named Cerms. He has a loud growl but is pretty much harmless.
            $bear_cerms = bear::create([
                'name' => 'Cerms',
                'type' => 'Black',
                'danger_level' => 4,
            ]);
            // bear 3 is named Adobot. He is a polar bear.
            $bear_adobot = bear::create([
                'name' => 'Adobot',
                'type' => 'Polar',
                'danger_level' => 3,
            ]);

            $this->assertIsObject($bear_lawly);
            $this->assertInstanceOf('yf_model_result', $bear_lawly);
            $this->assertInstanceOf('yf_model', $bear_lawly->_get_model());
            $this->assertInstanceOf('bear', $bear_lawly->_get_model());
            $this->assertObjectHasProperty('id', $bear_lawly);
            $this->assertObjectHasProperty('name', $bear_lawly);
            $this->assertObjectHasProperty('type', $bear_lawly);
            $this->assertObjectHasProperty('danger_level', $bear_lawly);
            $this->assertSame('Lawly', $bear_lawly->name);
            $this->assertSame('Grizzly', $bear_lawly->type);
            $this->assertEquals('8', $bear_lawly->danger_level);

            $this->assertIsObject($bear_cerms);
            $this->assertInstanceOf('yf_model_result', $bear_cerms);
            $this->assertInstanceOf('yf_model', $bear_cerms->_get_model());
            $this->assertInstanceOf('bear', $bear_cerms->_get_model());
            $this->assertObjectHasProperty('id', $bear_cerms);
            $this->assertObjectHasProperty('name', $bear_cerms);
            $this->assertObjectHasProperty('type', $bear_cerms);
            $this->assertObjectHasProperty('danger_level', $bear_cerms);
            $this->assertSame('Cerms', $bear_cerms->name);
            $this->assertSame('Black', $bear_cerms->type);
            $this->assertEquals('4', $bear_cerms->danger_level);

            $this->assertIsObject($bear_adobot);
            $this->assertInstanceOf('yf_model_result', $bear_adobot);
            $this->assertInstanceOf('yf_model', $bear_adobot->_get_model());
            $this->assertInstanceOf('bear', $bear_adobot->_get_model());
            $this->assertObjectHasProperty('id', $bear_adobot);
            $this->assertObjectHasProperty('name', $bear_adobot);
            $this->assertObjectHasProperty('type', $bear_adobot);
            $this->assertObjectHasProperty('danger_level', $bear_adobot);
            $this->assertSame('Adobot', $bear_adobot->name);
            $this->assertSame('Polar', $bear_adobot->type);
            $this->assertEquals('3', $bear_adobot->danger_level);

            // seed our fish table. our fish wont have names... because theyre going to be eaten
            // we will use the variables we used to create the bears to get their id
            $fish1 = fish::create([
                'weight' => '5',
                'bear_id' => $bear_lawly->id,
            ]);
            $fish2 = fish::create([
                'weight' => '12',
                'bear_id' => $bear_cerms->id,
            ]);
            $fish3 = fish::create([
                'weight' => '4',
                'bear_id' => $bear_adobot->id,
            ]);

            $this->assertIsObject($fish1);
            $this->assertInstanceOf('yf_model_result', $fish1);
            $this->assertInstanceOf('yf_model', $fish1->_get_model());
            $this->assertInstanceOf('fish', $fish1->_get_model());
            $this->assertObjectHasProperty('id', $fish1);
            $this->assertObjectHasProperty('weight', $fish1);
            $this->assertObjectHasProperty('bear_id', $fish1);
            $this->assertEquals('5', $fish1->weight);
            $this->assertSame($bear_lawly->id, $fish1->bear_id);

            $this->assertIsObject($fish2);
            $this->assertInstanceOf('yf_model_result', $fish2);
            $this->assertInstanceOf('yf_model', $fish2->_get_model());
            $this->assertInstanceOf('fish', $fish2->_get_model());
            $this->assertObjectHasProperty('id', $fish2);
            $this->assertObjectHasProperty('weight', $fish2);
            $this->assertObjectHasProperty('bear_id', $fish2);
            $this->assertEquals('12', $fish2->weight);
            $this->assertSame($bear_cerms->id, $fish2->bear_id);

            $this->assertIsObject($fish3);
            $this->assertInstanceOf('yf_model_result', $fish3);
            $this->assertInstanceOf('yf_model', $fish3->_get_model());
            $this->assertInstanceOf('fish', $fish3->_get_model());
            $this->assertObjectHasProperty('id', $fish3);
            $this->assertObjectHasProperty('weight', $fish3);
            $this->assertObjectHasProperty('bear_id', $fish3);
            $this->assertEquals('4', $fish3->weight);
            $this->assertSame($bear_adobot->id, $fish3->bear_id);

            // seed our trees table
            $tree1 = tree::create([
                'type' => 'Redwood',
                'age' => '500',
                'bear_id' => $bear_lawly->id,
            ]);
            $tree2 = tree::create([
                'type' => 'Oak',
                'age' => '400',
                'bear_id' => $bear_lawly->id,
            ]);

            $this->assertIsObject($tree1);
            $this->assertInstanceOf('yf_model_result', $tree1);
            $this->assertInstanceOf('yf_model', $tree1->_get_model());
            $this->assertInstanceOf('tree', $tree1->_get_model());
            $this->assertObjectHasProperty('id', $tree1);
            $this->assertObjectHasProperty('type', $tree1);
            $this->assertObjectHasProperty('age', $tree1);
            $this->assertObjectHasProperty('bear_id', $tree1);
            $this->assertSame('Redwood', $tree1->type);
            $this->assertEquals('500', $tree1->age);
            $this->assertSame($bear_lawly->id, $tree1->bear_id);

            $this->assertIsObject($tree2);
            $this->assertInstanceOf('yf_model_result', $tree2);
            $this->assertInstanceOf('yf_model', $tree2->_get_model());
            $this->assertInstanceOf('tree', $tree2->_get_model());
            $this->assertObjectHasProperty('id', $tree2);
            $this->assertObjectHasProperty('type', $tree2);
            $this->assertObjectHasProperty('age', $tree2);
            $this->assertObjectHasProperty('bear_id', $tree2);
            $this->assertSame('Oak', $tree2->type);
            $this->assertEquals('400', $tree2->age);
            $this->assertSame($bear_lawly->id, $tree2->bear_id);

            // we will create one picnic and apply all bears to this one picnic
            $picnic_yellowstone = picnic::create([
                'name' => 'Yellowstone',
                'taste_level' => '6',
            ]);
            $picnic_grand_canyon = picnic::create([
                'name' => 'Grand Canyon',
                'taste_level' => '5',
            ]);

            $this->assertIsObject($picnic_yellowstone);
            $this->assertInstanceOf('yf_model_result', $picnic_yellowstone);
            $this->assertInstanceOf('yf_model', $picnic_yellowstone->_get_model());
            $this->assertInstanceOf('picnic', $picnic_yellowstone->_get_model());
            $this->assertObjectHasProperty('id', $picnic_yellowstone);
            $this->assertObjectHasProperty('name', $picnic_yellowstone);
            $this->assertObjectHasProperty('taste_level', $picnic_yellowstone);
            $this->assertSame('Yellowstone', $picnic_yellowstone->name);
            $this->assertEquals('6', $picnic_yellowstone->taste_level);

            $this->assertIsObject($picnic_grand_canyon);
            $this->assertInstanceOf('yf_model_result', $picnic_grand_canyon);
            $this->assertInstanceOf('yf_model', $picnic_grand_canyon->_get_model());
            $this->assertInstanceOf('picnic', $picnic_grand_canyon->_get_model());
            $this->assertObjectHasProperty('id', $picnic_grand_canyon);
            $this->assertObjectHasProperty('name', $picnic_grand_canyon);
            $this->assertObjectHasProperty('taste_level', $picnic_grand_canyon);
            $this->assertSame('Grand Canyon', $picnic_grand_canyon->name);
            $this->assertEquals('5', $picnic_grand_canyon->taste_level);

            // ---------- link our bears to picnics -------------

            // for our purposes we'll just add all bears to both picnics for our many to many relationship
            $bear_lawly->picnics()->attach($picnic_yellowstone->id);
            $bear_lawly->picnics()->attach($picnic_grand_canyon->id);

            $bear_cerms->picnics()->attach($picnic_yellowstone->id);
            $bear_cerms->picnics()->attach($picnic_grand_canyon->id);

            $bear_adobot->picnics()->attach($picnic_yellowstone->id);
            $bear_adobot->picnics()->attach($picnic_grand_canyon->id);

            $this->assertEquals(
                ['bear_id' => $bear_lawly->id, 'picnic_id' => $picnic_yellowstone->id],
                self::db()->select('bear_id, picnic_id')->from('bears_picnics')->where('bear_id', $bear_lawly->id)->where('picnic_id', $picnic_yellowstone->id)->get()
            );
            $this->assertEquals(
                ['bear_id' => $bear_lawly->id, 'picnic_id' => $picnic_grand_canyon->id],
                self::db()->select('bear_id, picnic_id')->from('bears_picnics')->where('bear_id', $bear_lawly->id)->where('picnic_id', $picnic_grand_canyon->id)->get()
            );

            $this->assertEquals(
                ['bear_id' => $bear_cerms->id, 'picnic_id' => $picnic_yellowstone->id],
                self::db()->select('bear_id, picnic_id')->from('bears_picnics')->where('bear_id', $bear_cerms->id)->where('picnic_id', $picnic_yellowstone->id)->get()
            );
            $this->assertEquals(
                ['bear_id' => $bear_cerms->id, 'picnic_id' => $picnic_grand_canyon->id],
                self::db()->select('bear_id, picnic_id')->from('bears_picnics')->where('bear_id', $bear_cerms->id)->where('picnic_id', $picnic_grand_canyon->id)->get()
            );

            $this->assertEquals(
                ['bear_id' => $bear_adobot->id, 'picnic_id' => $picnic_yellowstone->id],
                self::db()->select('bear_id, picnic_id')->from('bears_picnics')->where('bear_id', $bear_adobot->id)->where('picnic_id', $picnic_yellowstone->id)->get()
            );
            $this->assertEquals(
                ['bear_id' => $bear_adobot->id, 'picnic_id' => $picnic_grand_canyon->id],
                self::db()->select('bear_id, picnic_id')->from('bears_picnics')->where('bear_id', $bear_adobot->id)->where('picnic_id', $picnic_grand_canyon->id)->get()
            );
        } catch (Exception $e) {
            echo self::_pretty_show_exception($e);
        }
    }


    public function create_models_alternate_methods()
    {
        try {
            // Method 2
            $bear_cool1 = model('bear')->create([
                'name' => 'Super Cool1',
                'type' => 'Black',
                'danger_level' => 1,
            ]);

            // Method 3
            // alternatively you can create an object, assign values, then save
            $bear_cool2 = new bear();
            $bear_cool2->name = 'Super Cool2';
            $bear_cool2->type = 'Black';
            $bear_cool2->danger_level = 1;
            $bear_cool2_saved = $bear_cool2->save();

            $this->assertIsObject($bear_cool1);
            $this->assertInstanceOf('yf_model_result', $bear_cool1);
            $this->assertInstanceOf('yf_model', $bear_cool1->_get_model());
            $this->assertInstanceOf('bear', $bear_cool1->_get_model());
            $this->assertObjectHasProperty('id', $bear_cool1);
            $this->assertObjectHasProperty('name', $bear_cool1);
            $this->assertObjectHasProperty('type', $bear_cool1);
            $this->assertObjectHasProperty('danger_level', $bear_cool1);
            $this->assertSame('Super Cool1', $bear_cool1->name);
            $this->assertSame('Black', $bear_cool1->type);
            $this->assertEquals('1', $bear_cool1->danger_level);

            $this->assertTrue((bool) $bear_cool2_saved);
            $this->assertIsObject($bear_cool2);
            $this->assertInstanceOf('yf_model', $bear_cool2);
            $this->assertInstanceOf('bear', $bear_cool2);
            $this->assertObjectHasProperty('id', $bear_cool2);
            $this->assertObjectHasProperty('name', $bear_cool2);
            $this->assertObjectHasProperty('type', $bear_cool2);
            $this->assertObjectHasProperty('danger_level', $bear_cool2);
            $this->assertSame('Super Cool2', $bear_cool2->name);
            $this->assertSame('Black', $bear_cool2->type);
            $this->assertEquals('1', $bear_cool2->danger_level);
        } catch (Exception $e) {
            echo self::_pretty_show_exception($e);
        }
    }

    /**
     * Idea for tests got from here: http://scotch.io/tutorials/php/a-guide-to-using-eloquent-orm-in-laravel.
     */
    public function test_main()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $model_base = _class('model'); // Need this to load basic model class
        try {
            $this->create_tables();
            $this->create_models();
            $this->create_data();
            $this->create_models_alternate_methods();
        } catch (Exception $e) {
            echo self::_pretty_show_exception($e);
        }
    }

    /**
     */
    #[Depends('test_main')]
    public function test_query_models()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        try {
            $bear_lawly = bear::where('name', '=', 'Lawly')->first();
            $bear_cerms = bear::where('name', '=', 'Cerms')->first();
            $bear_adobot = bear::where('name', '=', 'Adobot')->first();

            // ----------- querying models -----------

            // find the bear or create it into the database
            $bear_first1 = bear::first_or_create(['name' => 'Lawly']);
            $bear_first2 = bear::first_or_create(['name' => 'Lawly2']);
            // find the bear or instantiate a new instance into the object we want
            $bear_new1 = bear::first_or_new(['name' => 'Cerms']);
            $bear_new2 = bear::first_or_new(['name' => 'Cerms2']);
            // get all the bears
            $bears = bear::all();
            // find a specific bear by id
            $bear_id2 = bear::find(2);
            // find a bear by a specific attribute
            $bear_cerms_first = bear::where('name', '=', 'Cerms')->first();
            // find a bear with danger level greater than 2
            $dangerous_bears = bear::all('danger_level', '>', 2);

            $this->assertIsObject($bear_first1);
            $this->assertInstanceOf('yf_model_result', $bear_first1);
            $this->assertInstanceOf('yf_model', $bear_first1->_get_model());
            $this->assertInstanceOf('bear', $bear_first1->_get_model());
            $this->assertObjectHasProperty('id', $bear_first1);
            $this->assertSame('Lawly', $bear_first1->name);
            $this->assertNotEmpty($bear_lawly->type);
            $this->assertSame($bear_lawly->type, $bear_first1->type);
            $this->assertEquals($bear_lawly->danger_level, $bear_first1->danger_level);

            $this->assertNotSame($bear_first1, $bear_first2);
            $this->assertIsObject($bear_first2);
            $this->assertInstanceOf('yf_model_result', $bear_first2);
            $this->assertInstanceOf('yf_model', $bear_first2->_get_model());
            $this->assertInstanceOf('bear', $bear_first2->_get_model());
            $this->assertObjectHasProperty('id', $bear_first2);
            $this->assertSame('Lawly2', $bear_first2->name);
            $this->assertSame('', $bear_first2->type);
            $this->assertEquals('0', $bear_first2->danger_level);

            $this->assertIsObject($bear_new1);
            $this->assertInstanceOf('yf_model_result', $bear_new1);
            $this->assertInstanceOf('yf_model', $bear_new1->_get_model());
            $this->assertInstanceOf('bear', $bear_new1->_get_model());
            $this->assertObjectHasProperty('id', $bear_new1);
            $this->assertEquals($bear_cerms->id, $bear_new1->id);
            $this->assertEquals($bear_cerms->name, $bear_new1->name);
            //		$this->assertEquals($bear_cerms, $bear_new1);

            $this->assertNotSame($bear_new1, $bear_new2);
            $this->assertIsObject($bear_new2);
            $this->assertInstanceOf('yf_model', $bear_new2);
            $this->assertInstanceOf('bear', $bear_new2);
            $this->assertNull($bear_new2->id);
            $this->assertEquals('Cerms2', $bear_new2->name);

            $this->assertIsArray($bears);
            $this->assertNotEmpty($bears);
            $bears_array = [];
            foreach ((array) $bears as $k => $v) {
                $bears_array[$k] = $v->get_data();
                unset($bears_array[$k]['created_at']);
                unset($bears_array[$k]['updated_at']);
            }
            $expected = [
                1 => ['id' => '1', 'name' => 'Lawly', 'type' => 'Grizzly', 'danger_level' => '8'],
                2 => ['id' => '2', 'name' => 'Cerms', 'type' => 'Black', 'danger_level' => '4'],
                3 => ['id' => '3', 'name' => 'Adobot', 'type' => 'Polar', 'danger_level' => '3'],
                4 => ['id' => '4', 'name' => 'Super Cool1', 'type' => 'Black', 'danger_level' => '1'],
                5 => ['id' => '5', 'name' => 'Super Cool2', 'type' => 'Black', 'danger_level' => '1'],
                6 => ['id' => '6', 'name' => 'Lawly2', 'type' => '', 'danger_level' => '0'],
            ];
            $this->assertEquals($expected, $bears_array);
            $this->assertEquals($expected[2]['id'], $bear_id2->id);
            $this->assertEquals($expected[2]['name'], $bear_id2->name);
            $this->assertEquals($expected[2]['type'], $bear_id2->type);

            $this->assertIsObject($bear_cerms_first);
            $this->assertNotSame($bear_cerms, $bear_cerms_first);
            $this->assertEquals($bear_cerms->id, $bear_cerms_first->id);
            $this->assertEquals($bear_cerms->name, $bear_cerms_first->name);
            $this->assertEquals($bear_cerms->type, $bear_cerms_first->type);

            $this->assertIsArray($dangerous_bears);
            $this->assertEquals('3', count((array) $dangerous_bears));
        } catch (Exception $e) {
            echo self::_pretty_show_exception($e);
        }
    }

    /**
     */
    #[Depends('test_main')]
    public function test_change_models()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        try {
            // let's change the danger level of Lawly to level 10
            $lawly = bear::where('name', '=', 'Lawly')->first();

            $this->assertEquals(8, $lawly->danger_level);
            $this->assertNotEquals('10', $lawly->danger_level);

            $lawly->danger_level = 10;
            $lawly->save();

            $this->assertEquals('10', $lawly->danger_level);
        } catch (Exception $e) {
            echo self::_pretty_show_exception($e);
        }
    }

    /**
     * query one-to-one relationships
     */
    #[Depends('test_main')]
    public function test_one_to_one()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        try {
            $bear_lawly = bear::where('name', '=', 'Lawly')->first();
            $bear_adobot = bear::where('name', '=', 'Adobot')->first();

            // find a bear named Adobot
            $adobot = bear::where('name', '=', 'Adobot')->first();
            // get the fish that Adobot has
            $fish = $adobot->fish;
            // get the weight of the fish Adobot is going to eat
            $weight1 = $fish->weight;
            // alternatively you could go straight to the weight attribute
            $weight2 = $adobot->fish->weight;

            $this->assertIsObject($adobot);
            $this->assertNotSame($bear_adobot, $adobot);
            $this->assertEquals($bear_adobot->id, $adobot->id);
            $this->assertEquals($bear_adobot->name, $adobot->name);
            $this->assertIsObject($adobot->fish);
            $this->assertIsObject($fish);
            $this->assertNotSame($fish, $adobot->fish);
            // $this->assertEquals($fish, $adobot->fish);
            $this->assertInstanceOf('yf_model_result', $adobot->fish);
            $this->assertInstanceOf('yf_model_result', $fish);
            $this->assertInstanceOf('yf_model', $fish->_get_model());
            $this->assertInstanceOf('fish', $fish->_get_model());

            // Find bear that holds the fish1
            $fish_first = fish::find(1);
            $bear_fish1 = $fish_first->bear;

            $this->assertIsObject($fish_first);
            $this->assertInstanceOf('yf_model_result', $fish_first);
            $this->assertInstanceOf('yf_model', $fish_first->_get_model());
            $this->assertInstanceOf('fish', $fish_first->_get_model());
            $this->assertNotSame($fish, $fish_first);

            $this->assertIsObject($bear_fish1);
            $this->assertInstanceOf('yf_model_result', $bear_fish1);
            $this->assertInstanceOf('yf_model', $bear_fish1->_get_model());
            $this->assertInstanceOf('bear', $bear_fish1->_get_model());
            $this->assertEquals($bear_lawly->id, $bear_fish1->id);
            $this->assertEquals($bear_lawly->name, $bear_fish1->name);
            $this->assertEquals($bear_lawly->name, $fish_first->bear->name);
        } catch (Exception $e) {
            echo self::_pretty_show_exception($e);
        }
    }

    /**
     * query one-to-many relationships
     */
    #[Depends('test_main')]
    public function test_one_to_many()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        try {
            $trees = [];
            // find the trees lawly climbs
            $lawly = bear::where('name', '=', 'Lawly')->first();
            foreach ($lawly->trees as $tree) {
                $trees[$tree->type] = $tree->age;
            }

            $expected = [
                'Redwood' => '500',
                'Oak' => '400',
            ];
            $this->assertEquals($expected, $trees);
        } catch (Exception $e) {
            echo self::_pretty_show_exception($e);
        }
    }

    /**
     * query many-to-many relationships
     */
    #[Depends('test_main')]
    public function test_many_to_many()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        try {
            // get the picnics that Cerms goes to
            $cerms = bear::where('name', '=', 'Cerms')->first();
            // get the picnics and their names and taste levels
            $taste_levels = [];
            $cerms_picnics = $cerms->picnics;
            foreach ($cerms_picnics as $picnic) {
                $taste_levels[$picnic->name] = $picnic->taste_level;
            }

            $this->assertIsObject($cerms);
            $this->assertNotSame($bear_cerms, $cerms);
            $this->assertInstanceOf('yf_model_result', $cerms);
            $this->assertInstanceOf('bear', $cerms->_get_model());
            $this->assertIsArray($cerms_picnics);
            $first_object = reset($cerms_picnics);
            $this->assertIsObject($first_object);
            $this->assertInstanceOf('yf_model_result', $first_object);
            $this->assertInstanceOf('yf_model', $first_object->_get_model());
            $this->assertInstanceOf('picnic', $first_object->_get_model());
            $expected = [
                'Yellowstone' => 6,
                'Grand Canyon' => 5,
            ];
            $this->assertEquals($expected, $taste_levels);

            // get the bears that go to the Grand Canyon picnic
            $grand_canyon = picnic::where('name', '=', 'Grand Canyon')->first();
            // show the bears
            $bears_in_grand_canyon = [];
            $grand_canyon_bears = $grand_canyon->bears;
            foreach ($grand_canyon_bears as $bear) {
                $bears_in_grand_canyon[$bear->name] = $bear->type . ', danger: ' . $bear->danger_level;
            }

            $this->assertIsObject($grand_canyon);
            $this->assertNotSame($picnic_grand_canyon, $grand_canyon);
            $this->assertInstanceOf('yf_model_result', $grand_canyon);
            $this->assertInstanceOf('picnic', $grand_canyon->_get_model());
            $this->assertIsArray($grand_canyon_bears);
            $first_object = reset($grand_canyon_bears);
            $this->assertIsObject($first_object);
            $this->assertInstanceOf('yf_model_result', $first_object);
            $this->assertInstanceOf('yf_model', $first_object->_get_model());
            $this->assertInstanceOf('bear', $first_object->_get_model());
            $expected = [
                'Lawly' => 'Grizzly, danger: 10',
                'Cerms' => 'Black, danger: 4',
                'Adobot' => 'Polar, danger: 3',
            ];
            $this->assertEquals($expected, $bears_in_grand_canyon);
        } catch (Exception $e) {
            echo self::_pretty_show_exception($e);
        }
    }

    #[Depends('test_main')]
    public function test_delete_models()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        try {
            // find and delete a record
            $bear = bear::find(1);
            $this->assertNotEmpty($bear->id);
            $this->assertEquals($bear->id, self::db()->from($bear->get_table())->whereid($bear->id)->get_one());
            $this->assertInstanceOf('yf_model_result', $bear);
            $bear->delete();
            $this->assertFalse((bool) self::db()->from($bear->get_table())->whereid($bear->id)->get_one());

            // delete a record
            $this->assertTrue((bool) self::db()->from('bears')->whereid(6)->get_one());
            bear::destroy(6);
            $this->assertFalse((bool) self::db()->from('bears')->whereid(6)->get_one());

            // delete multiple records
            $this->assertEquals(2, (int) self::db()->from('bears')->whereid([2, 3])->count());
            bear::destroy(2, 3);
            $this->assertEquals(0, (int) self::db()->from('bears')->whereid([2, 3])->count());
            $this->assertEquals(4, self::db()->from('bears')->whereid(4)->get_one());
            $this->assertEquals(5, self::db()->from('bears')->whereid(5)->get_one());

            // find and delete all bears with a danger level less 5
            $this->assertEquals(2, (int) self::db()->from('bears')->count());
            bear::where('danger_level', '<', 5)->delete();
            $this->assertEquals(0, (int) self::db()->from('bears')->count());
        } catch (Exception $e) {
            echo self::_pretty_show_exception($e);
        }
    }
}
