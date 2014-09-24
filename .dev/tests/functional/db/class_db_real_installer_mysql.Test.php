<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class class_db_real_installer_mysql_test extends db_real_abstract {

	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'mysql5';
		self::_connect();
		self::utils()->truncate_database(self::db_name());
	}
	public static function tearDownAfterClass() {
#		self::utils()->truncate_database(self::db_name());
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public static function db_name() {
		return self::$DB_NAME;
	}
	public static function table_name($name) {
		return self::db_name().'.'.$name;
	}

	/***/
	public function test_sakila() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$db_prefix = self::db()->DB_PREFIX;

		$this->assertEquals( array(), self::utils()->list_tables(self::db_name()) );

		$parser = _class('db_ddl_parser_mysql', 'classes/db/');
		$parser->RAW_IN_RESULTS = true;

		$this->assertTrue( (bool)self::db()->query('SET foreign_key_checks = 0;') );

		$fixtures_path = __DIR__.'/fixtures/';
		$tables = array();
		foreach (glob($fixtures_path.'*.sql') as $path) {
			$name = substr(basename($path), strlen('class_db_ddl_parser_mysql_test_tbl_'), -strlen('.sql'));
			$tables[$db_prefix.$name] = $db_prefix.$name;
			$sql = file_get_contents($path);

			$this->assertTrue( (bool)self::utils()->drop_table(self::table_name($db_prefix.$name)) );
			$this->assertFalse( (bool)self::utils()->table_exists(self::table_name($db_prefix.$name)) );

			$sql = str_replace('CREATE TABLE `', 'CREATE TABLE `'.$db_prefix, $sql);
			$this->assertTrue( (bool)self::db()->query($sql) );

			$this->assertTrue( (bool)self::utils()->table_exists(self::table_name($db_prefix.$name)) );

			$php_path = substr($path, 0, -strlen('.sql')). '.php';
			if (!file_exists($php_path)) {
				continue;
			}
			$sql_php = include $php_path;
			$this->assertTrue( is_array($sql_php) && count($sql_php) && $sql_php );
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
			$this->assertEquals( $sql_php['foreign_keys'], $fks, 'Compare indexes with expected sql_php for table: '.$name );
		}
		$this->assertTrue( (bool)self::db()->query('SET foreign_key_checks = 1;') );

		$this->assertEquals( $tables, self::utils()->list_tables(self::db_name()) );
	}

	/***/
	public function test_yf_db_installer_basic() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$db_prefix = self::db()->DB_PREFIX;

		self::utils()->truncate_database(self::db_name());
		$this->assertEquals( array(), self::utils()->list_tables(self::db_name()) );

		$db_installer = _class('db_installer_mysql', 'classes/db/');

		$parser = _class('db_ddl_parser_mysql', 'classes/db/');
		$parser->RAW_IN_RESULTS = false;

		$tables_sql = array();
		$tables_php = array();

		// Load install data from external files
		$globs_sql = array(
			'yf_main'		=> YF_PATH.'share/db_installer/sql/*.sql.php',
			'yf_plugins'	=> YF_PATH.'plugins/*/share/db_installer/sql/*.sql.php',
		);
		foreach ($globs_sql as $glob) {
			foreach (glob($glob) as $f) {
				$t_name = substr(basename($f), 0, -strlen('.sql.php'));
				$tables_sql[$t_name] = include $f; // $data should be loaded from file
			}
		}
		$globs_php = array(
			'yf_main'		=> YF_PATH.'share/db_installer/sql_php/*.sql_php.php',
			'yf_plugins'	=> YF_PATH.'plugins/*/share/db_installer/sql_php/*.sql_php.php',
		);
		foreach ($globs_php as $glob) {
			foreach (glob($glob) as $f) {
				$t_name = substr(basename($f), 0, -strlen('.sql_php.php'));
				$tables_php[$t_name] = include $f; // $data should be loaded from file
			}
		}

		$this->assertNotEmpty($tables_sql);
		$this->assertNotEmpty($tables_php);
		$this->assertEquals(array_keys($tables_sql), array_keys($tables_php));

		$this->assertTrue( (bool)self::db()->query('SET foreign_key_checks = 0;') );
		
		$tables = array();
		foreach ((array)$tables_php as $name => $php_sql) {
			$tables[$db_prefix.$name] = $db_prefix.$name;

			$this->assertTrue( (bool)self::utils()->drop_table(self::table_name($db_prefix.$name)) );
			$this->assertFalse( (bool)self::utils()->table_exists(self::table_name($db_prefix.$name)) );

			$php_sql['name'] = $db_prefix.$name;
			$sql = $parser->create($php_sql);

			$this->assertTrue( (bool)self::db()->query($sql) );
			$this->assertTrue( (bool)self::utils()->table_exists(self::table_name($db_prefix.$name)) );
		}
		$this->assertTrue( (bool)self::db()->query('SET foreign_key_checks = 1;') );

		$this->assertEquals( $tables, self::utils()->list_tables(self::db_name()) );
	}

	/***/
	public function test_yf_db_installer_create_missing_table() {
// TODO: check how db installer table creating working when selecting missing column in db, but have it in structure
	}

	/***/
	public function test_yf_db_installer_alter_table() {
// TODO: check how db installer table altering working when selecting missing column in db, but have it in structure
	}

	/***/
	public function test_yf_db_installer_fix_table_indexes() {
// TODO
	}

	/***/
	public function test_yf_db_installer_fix_table_foreign_keys() {
// TODO
	}
// TODO: db installer events before/after create/alter table tests
// TODO: db installer extending tests
}