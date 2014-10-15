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
			// unit_tests == name of the custom storage used here
			main()->_custom_class_storages = array(
				'film_model' => array('unit_tests' => array(__DIR__.'/fixtures/')),
			);
			$this->assertTrue( main()->_class_exists('film_model') );
		}

		$film_model = _class('film_model');
		$this->assertTrue( is_object($film_model) );
		$this->assertTrue( is_a($film_model, 'film_model') );
		$this->assertTrue( is_a($film_model, 'yf_model') );
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
break;
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
		}
#print_r(self::utils()->list_tables(self::db_name()) );

		$this->assertTrue( (bool)self::db()->query('SET foreign_key_checks = 1;') );
	}
/*
	public function test_dump_sakila_data() {
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
	}
*/
}
