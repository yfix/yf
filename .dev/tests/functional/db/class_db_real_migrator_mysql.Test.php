<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class class_db_real_migrator_mysql_test extends db_real_abstract {
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
		return $name;
	}
	public static function migrator() {
		return self::$db->migrator();
	}
	protected function prepare_sample_data($fname) {
		self::utils()->truncate_database(self::db_name());
		$table1 = self::utils()->db->DB_PREFIX. $fname.'_1';
		$table2 = self::utils()->db->DB_PREFIX. $fname.'_2';
		$data = array(
			'fields' => array(
				'id'	=> array('name' => 'id', 'type' => 'int', 'length' => 10),
			),
			'indexes' => array(
				'PRIMARY' => array('name' => 'PRIMARY', 'type' => 'primary', 'columns' => array('id' => 'id')),
			),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$expected = array(
			'name' => $fkey,
			'columns' => array('id' => 'id'),
			'ref_table' => $table2,
			'ref_columns' => array('id' => 'id'),
			'on_update' => 'RESTRICT',
			'on_delete' => 'RESTRICT'
		);
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $expected) );
		$this->assertEquals( $expected, self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
	}
	public function _load_fixtures_list($name) {
		$out = array();
		$dir = __DIR__.'/migrator_fixtures/';
		$ext = '.sql_php.php';
		foreach (glob($dir. $name. '*'. $ext) as $path) {
			$_name = substr(basename($path), 0, -strlen($ext));
			$out[$_name] = $path;
		}
		return $out;
	}
	public function _load_fixture($name) {
		$path = __DIR__.'/migrator_fixtures/'.$name.'.fixture.php';
		if (!file_exists($path)) {
			return array();
		}
		return include $path;
	}
	public function _load_expected($name) {
		$path = __DIR__.'/migrator_fixtures/'.$name.'.expected.php';
		if (!file_exists($path)) {
			return array();
		}
		return include $path;
	}
	public function test_cleanup_table_sql_php() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::prepare_sample_data(__FUNCTION__);
		$name = __FUNCTION__.'_1';
		$sample = $this->_load_fixture($name);
		$this->assertNotEmpty( $sample );
		$expected = $this->_load_expected($name);
		$this->assertNotEmpty( $expected );
		$result = self::migrator()->_cleanup_table_sql_php($sample, self::utils()->db->DB_PREFIX);
		$this->assertEquals( $expected, $result );
	}
	public function test_get_real_table_sql_php() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::prepare_sample_data(__FUNCTION__);
		$name = __FUNCTION__.'_1';
		$expected = $this->_load_expected($name);
		$this->assertNotEmpty( $expected );
		$result = self::migrator()->get_real_table_sql_php($name);
		$this->assertEquals( $expected, $result );
	}
	public function test_compare() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::prepare_sample_data(__FUNCTION__);
		$table1 = __FUNCTION__.'_1';
		$table2 = __FUNCTION__.'_2';

		$result = self::migrator()->compare(array('tables_sql_php' => array()));
		$expected = array(
			'tables_changed' => array(),
			'tables_new' => array($table1 => $table1, $table2 => $table2),
		);
		$this->assertEquals( $expected, $result );

		foreach ((array)$this->_load_fixtures_list(__FUNCTION__) as $name => $path) {
			$result = self::migrator()->compare(array('tables_sql_php' => $this->_load_fixture($name)));
			$expected = $this->_load_expected($name);
			$this->assertEquals( $expected, $result );
		}
	}
	public function test_generate() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::prepare_sample_data(__FUNCTION__);
		$table1 = __FUNCTION__.'_1';
		$table2 = __FUNCTION__.'_2';

		$result = self::migrator()->generate(array('tables_sql_php' => array(), 'safe_mode' => true));
		$expected = $this->_load_expected($table1.'_safe');
		$this->assertEquals( $expected, $result );

		$result = self::migrator()->generate(array('tables_sql_php' => array(), 'safe_mode' => false));
		$expected = $this->_load_expected($table1.'_full');
		$this->assertEquals( $expected, $result );
	}
	public function test_dump() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::prepare_sample_data(__FUNCTION__);
		$table1 = __FUNCTION__.'_1';
		$table2 = __FUNCTION__.'_2';

		$dir_sql = 'share/db/sql/';
		$ext_sql = '.sql.php';
		$dir_sql_php = 'share/db/sql_php/';
		$ext_sql_php = '.sql_php.php';
		$expected = array(
			'sql:'.$table1		=> APP_PATH. $dir_sql. $table1. $ext_sql,
			'sql_php:'.$table1	=> APP_PATH. $dir_sql_php. $table1. $ext_sql_php,
			'sql:'.$table2		=> APP_PATH. $dir_sql. $table2. $ext_sql,
			'sql_php:'.$table2	=> APP_PATH. $dir_sql_php. $table2. $ext_sql_php,
		);
		// Cleanup
		foreach ($expected as $k => $file) {
			if (file_exists($file)) {
				unlink($file);
			}
			$this->assertFileNotExists( $file );
		}

		$result = self::migrator()->dump(array('no_load_default' => true));
		$this->assertEquals( $expected, $result );
		foreach ($expected as $k => $file) {
			$this->assertFileExists( $file );
		}
		$this->assertEquals( trim($this->_load_expected($table1.'_sql')), trim(include $result['sql:'.$table1]) );
		$this->assertEquals( trim($this->_load_expected($table1.'_sql_php')), trim(include $result['sql_php:'.$table1]) );
		$this->assertEquals( trim($this->_load_expected($table2.'_sql')), trim(include $result['sql:'.$table2]) );
		$this->assertEquals( trim($this->_load_expected($table2.'_sql_php')), trim(include $result['sql_php:'.$table2]) );

		// Cleanup
		foreach ($expected as $k => $file) {
			if (file_exists($file)) {
				unlink($file);
			}
			$this->assertFileNotExists( $file );
		}
	}
	public function test_create() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		self::prepare_sample_data(__FUNCTION__);
#		$result = self::migrator()->create();
// TODO
#		$this->assertEquals( $expected, $result );
	}
	public function test_list() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		self::prepare_sample_data(__FUNCTION__);
#		$result = self::migrator()->list();
// TODO
#		$this->assertEquals( $expected, $result );
	}
	public function test_apply() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		self::prepare_sample_data(__FUNCTION__);
#		$result = self::migrator()->apply();
// TODO
#		$this->assertEquals( $expected, $result );
	}
	public function test_sync() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		self::prepare_sample_data(__FUNCTION__);
#		$result = self::migrator()->sync();
// TODO
#		$this->assertEquals( $expected, $result );
	}
	public function test_migration_commands_into_string() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
// TODO
	}
	public function test_create_migration_body() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
// TODO
	}
	public function test_dump_db_installer_sql() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
// TODO
	}
}
