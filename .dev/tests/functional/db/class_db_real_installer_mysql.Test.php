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

		$this->assertNotEmpty( self::utils()->truncate_database(self::db_name()) );
#		$this->assertEquals( array(), self::utils()->list_tables(self::db_name()) );

		$parser = _class('db_ddl_parser_mysql', 'classes/db/');
		$parser->RAW_IN_RESULTS = true;

		self::db()->query('SET foreign_key_checks = 0;');

		$fixtures_path = __DIR__.'/fixtures/';
		foreach (glob($fixtures_path.'*.sql') as $path) {
			$name = substr(basename($path), strlen('class_db_ddl_parser_mysql_test_tbl_'), -strlen('.sql'));

			$sql = file_get_contents($path);

#			$this->assertNotEmpty( self::utils()->drop_table(self::table_name($name)) );
			self::db()->query('DROP TABLE IF EXISTS `'.$name.'`');
			self::db()->query($sql);

			$php_path = substr($path, 0, -strlen('.sql')). '.php';
			if (!file_exists($php_path)) {
				continue;
			}
			$sql_php = include $php_path;
/*
			$response = $parser->parse($sql);

			$this->assertSame($expected, $response);

			// Check that without SQL newlines or pretty formatting code works the same
			$response = $parser->parse(str_replace(array("\r","\n"), ' ', $sql));
			$this->assertSame($expected, $response);
*/
		}
		self::db()->query('SET foreign_key_checks = 1;');

		print_r( self::utils()->list_tables(self::db_name()) );
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