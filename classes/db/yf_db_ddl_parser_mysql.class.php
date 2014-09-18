<?php

class yf_db_ddl_parser_mysql {

	/**
	*/
	function phpsqlparser ($raw) {
		require_once '/home/www/TODO/_test_php_sql_parser/vendor/autoload.php';
		$parser = new PHPSQLParser();
		return $parser->parse($raw);
	}
}
