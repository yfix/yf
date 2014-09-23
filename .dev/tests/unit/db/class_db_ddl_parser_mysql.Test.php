<?php

require_once __DIR__.'/db_offline_abstract.php';

/**
 * @requires extension mysql
 */
class class_db_ddl_parser_mysql_test extends db_offline_abstract {

	/***/
	public function test_sql_to_php_sakila() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$parser = _class('db_ddl_parser_mysql', 'classes/db/');
		$parser->RAW_IN_RESULTS = true;

		$fixtures_path = __DIR__.'/fixtures/';
		foreach (glob($fixtures_path.'*.sql') as $path) {
			$sql = file_get_contents($path);
			$php_path = substr($path, 0, -strlen('.sql')). '.php';
			if (!file_exists($php_path)) {
				continue;
			}
			$expected = include $php_path;
			$response = $parser->parse($sql);
			if (empty($expected)) {
				$str = var_export($response, 1);
				$str = str_replace('  ', "\t", $str);
				$str = str_replace('array (', 'array(', $str);
				$str = preg_replace('~=>[\s]+array\(~ims', '=> array(', $str);

#				file_put_contents($php_path, '<?php'.PHP_EOL.'return '.$str.';');
			}

			$this->assertSame($expected, $response);

			// Check that without SQL newlines or pretty formatting code works the same
			$response = $parser->parse(str_replace(array("\r","\n"), ' ', $sql));
			$this->assertSame($expected, $response);
		}
	}

	/***/
	public function test_php_to_sql_sakila() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$parser = _class('db_ddl_parser_mysql', 'classes/db/');

		$fixtures_path = __DIR__.'/fixtures/';
		foreach (glob($fixtures_path.'*.php') as $path) {
			$php_create = include $path;
			$sql_path = substr($path, 0, -strlen('.php')). '.sql';
			if (!file_exists($sql_path)) {
				continue;
			}
			$expected = trim(file_get_contents($sql_path));
			$response = trim($parser->create($php_create));

			$this->assertSame($expected, $response);
		}
	}

	/**
	* In this test we ensure that all generated sql_php files are up-to-date and can be easily reconstructed
	*/
	public function test_sql_to_php_db_installer() {
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

		$this->assertNotEmpty($tables_sql);
		$this->assertNotEmpty($tables_php);
		$this->assertEquals(array_keys($tables_sql), array_keys($tables_php));
		
		foreach ((array)$tables_sql as $name => $sql) {
			$orig_sql = $sql;

			$expected = $tables_php[$name];
			$this->assertNotEmpty($expected);

			$options = '';
			// Get table options from table structure. Example: /** ENGINE=MEMORY **/
			if (preg_match('#\/\*\*(?P<raw_options>[^\*\/]+)\*\*\/#i', trim($sql), $m)) {
				// Cut comment with options from source table structure to prevent misunderstanding
				$sql = str_replace($m[0], '', $sql);
				$options = $m['raw_options'];
			}
			$tmp_name = 'tmp_name_not_exists';
			if (false === strpos(strtoupper($sql), 'CREATE TABLE')) {
				$sql = 'CREATE TABLE `'.$tmp_name.'` ('.$sql.')';
			}
			// Place them into the end of the DDL
			if ($options) {
				$sql = rtrim(rtrim(rtrim($sql), ';')).' '.$options;
			}

			$response = $parser->parse($sql);
			unset($expected['name']);
			unset($response['name']);
			$this->assertEquals($expected, $response, 'Parse create table raw: '.$name);

			$response2 = $db_installer->create_table_sql_to_php($sql);
			unset($response2['name']);
			$this->assertEquals($expected, $response2, 'Parse create table with db_installer create_table_sql_to_php: '.$name);
		}
	}

	/***/
	public function test_php_to_sql_db_installer() {
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

		$this->assertNotEmpty($tables_sql);
		$this->assertNotEmpty($tables_php);
		$this->assertEquals(array_keys($tables_sql), array_keys($tables_php));
		
		foreach ((array)$tables_php as $name => $sql_php) {
			$this->assertNotEmpty($sql_php);

			$sql = $tables_sql[$name];

			$options = '';
			// Get table options from table structure. Example: /** ENGINE=MEMORY **/
			if (preg_match('#\/\*\*(?P<raw_options>[^\*\/]+)\*\*\/#i', trim($sql), $m)) {
				// Cut comment with options from source table structure to prevent misunderstanding
				$sql = str_replace($m[0], '', $sql);
				$options = $m['raw_options'];
			}
			$tmp_name = 'tmp_name_not_exists';
			if (false === strpos(strtoupper($sql), 'CREATE TABLE')) {
				$sql = 'CREATE TABLE `'.$tmp_name.'` ('.$sql.')';
			}
			// Place them into the end of the DDL
			if ($options) {
				$sql = rtrim(rtrim(rtrim($sql), ';')).' '.$options;
			}

			$expected = $sql;
			$this->assertNotEmpty($expected);

			$sql_php['name'] = $tmp_name;
			$response = $parser->create($sql_php);
			$this->assertEquals($expected, $response, 'Create table DDL SQL from sql_php for: '.$name);

#			$response2 = $db_installer->create_table_php_to_sql($sql_php);
#			unset($response2['name']);
#			$this->assertEquals($expected, $response2, 'Create table from sql_php for: '.$name);
		}
	}
}
