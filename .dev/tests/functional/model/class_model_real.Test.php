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

	/***/
	public function test_load_fixtures() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

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
		$first_actor->save();
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
