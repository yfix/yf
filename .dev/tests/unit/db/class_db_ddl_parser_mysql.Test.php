<?php

require_once __DIR__.'/db_offline_abstract.php';

/**
 * @requires extension mysql
 */
class class_db_ddl_parser_mysql_test extends db_offline_abstract {
	public function test_1() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$fixtures_path = __DIR__.'/fixtures/';
		foreach (glob($fixtures_path.'*.sql') as $path) {
			$sql = file_get_contents($path);
			$php_path = substr($path, 0, -strlen('.sql')). '.php';
			if (!file_exists($php_path)) {
				continue;
			}
			$expected = include $php_path;
			$response = _class('db_ddl_parser_mysql', 'classes/db/')->parse($sql);
#			if (empty($expected)) {
#				file_put_contents($php_path, '<?php'.PHP_EOL.'return '.var_export($response, 1).';');
#			}
			$this->assertEquals($expected, $response);

			// Check that without SQL newlines or pretty formatting code works the same
			$response = _class('db_ddl_parser_mysql', 'classes/db/')->parse(str_replace(array("\r","\n"), ' ', $sql));
			$this->assertEquals($expected, $response);
		}
	}
}
