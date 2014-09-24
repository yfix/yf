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
		self::utils()->truncate_database(self::db_name());
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
			}
			foreach ((array)$sql_php['indexes'] as $fname => $f) {
				unset($sql_php['indexes'][$fname]['raw']);
			}
			foreach ((array)$sql_php['foreign_keys'] as $fname => $f) {
				unset($sql_php['foreign_keys'][$fname]['raw']);
			}

			$columns = self::utils()->list_columns(self::table_name($db_prefix.$name));
			foreach ((array)$columns as $fname => $f) {
				unset($columns[$fname]['type_raw']);
				unset($columns[$fname]['collate']);
			}
			$this->assertEquals( $sql_php['fields'], $columns, 'Compare columns with expected sql_php for table: '.$name );

			$indexes = self::utils()->list_indexes(self::table_name($db_prefix.$name));
			$this->assertEquals( $sql_php['indexes'], $indexes, 'Compare indexes with expected sql_php for table: '.$name );

#			$fks = self::utils()->list_foreign_keys(self::table_name($db_prefix.$name));
#			$this->assertEquals( $sql_php['foreign_keys'], $fks, 'Compare indexes with expected sql_php for table: '.$name );
#break;
		}
		$this->assertTrue( (bool)self::db()->query('SET foreign_key_checks = 1;') );

		$this->assertEquals( $tables, self::utils()->list_tables(self::db_name()) );
	}

	/***/
	public function test_yf_db_installer() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

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
/*
		$this->assertNotEmpty($tables_sql);
		$this->assertNotEmpty($tables_php);
		$this->assertEquals(array_keys($tables_sql), array_keys($tables_php));
		
		foreach ((array)$tables_sql as $name => $sql) {
			$orig_sql = $sql;

			$expected = $tables_php[$name];
			$this->assertNotEmpty($expected);

			$response = $parser->parse($sql);
			unset($expected['name']);
			unset($response['name']);
			$this->assertEquals($expected, $response, 'Parse create table raw: '.$name);

			$response2 = $db_installer->create_table_sql_to_php($sql);
			unset($response2['name']);
			$this->assertEquals($expected, $response2, 'Parse create table with db_installer create_table_sql_to_php: '.$name);
		}
*/
	}
}