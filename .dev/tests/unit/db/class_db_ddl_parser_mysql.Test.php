<?php

require_once __DIR__.'/db_offline_abstract.php';

/**
 * @requires extension mysql
 */
class class_db_ddl_parser_mysql_test extends db_offline_abstract {
	/***/
	public function test_sakila() {
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
#			if (empty($expected)) {
#				file_put_contents($php_path, '<?php'.PHP_EOL.'return '.var_export($response, 1).';');
#			}
			$this->assertEquals($expected, $response);

			// Check that without SQL newlines or pretty formatting code works the same
			$response = $parser->parse(str_replace(array("\r","\n"), ' ', $sql));
			$this->assertEquals($expected, $response);
		}
	}
	/***/
	public function test_yf() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$db_installer = _class('db_installer_mysql', 'classes/db/');

		$tables_sql = array();
		$tables_php = array();

		// Load install data from external files
		$globs_sql = array(
			'yf_main'			=> YF_PATH.'share/db_installer/sql/*.sql.php',
			'yf_plugins'		=> YF_PATH.'plugins/*/share/db_installer/sql/*.sql.php',
		);
		foreach ($globs_sql as $glob) {
			foreach (glob($glob) as $f) {
				$t_name = substr(basename($f), 0, -strlen('.sql.php'));
				require_once $f; // $data should be loaded from file
				$tables_sql[$t_name] = $data;
			}
		}
		$globs_php = array(
			'yf_main'			=> YF_PATH.'share/db_installer/sql_php/*.sql_php.php',
			'yf_plugins'		=> YF_PATH.'plugins/*/share/db_installer/sql_php/*.sql_php.php',
		);
		foreach ($globs_sql as $glob) {
			foreach (glob($glob) as $f) {
				$t_name = substr(basename($f), 0, -strlen('.sql.php'));
				require_once $f; // $data should be loaded from file
				$tables_php[$t_name] = $data;
			}
		}

#		$this->assertEquals(array_keys($tables_sql), array_keys($tables_php));
		
#		foreach ($tables_sql) {
#		}
#var_dump($db_installer);
	}
}
