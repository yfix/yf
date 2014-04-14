#!/usr/bin/php
<?php

#define('YF_PATH', dirname(dirname(dirname(dirname(__FILE__)))).'/');
define('YF_PATH', '/home/www/yf/');
#$dir_in = '';
#$dir_out = '';

		// Load install data from external files
		$globs_sql = array(
			'yf_main'			=> YF_PATH.'share/db_installer/sql/*.sql.php',
			'yf_plugins'		=> YF_PATH.'plugins/*/share/db_installer/sql/*.sql.php',
#			'project_main'		=> PROJECT_PATH.'share/db_installer/sql/*.sql.php',
#			'project_plugins'	=> PROJECT_PATH.'plugins/*/share/db_installer/sql/*.sql.php',
		);
		foreach ($globs_sql as $glob) {
			foreach (glob($glob) as $f) {
				$t_name = substr(basename($f), 0, -strlen('.sql.php'));
echo $f. PHP_EOL;
#				require_once $f; // $data should be loaded from file
#				$this->TABLES_SQL[$t_name] = $data;
			}
		}
		$globs_data = array(
			'yf_main'			=> YF_PATH.'share/db_installer/data/*.data.php',
			'yf_plugins'		=> YF_PATH.'plugins/*/share/db_installer/data/*.data.php',
#			'project_main'		=> PROJECT_PATH.'share/db_installer/data/*.data.php',
#			'project_plugins'	=> PROJECT_PATH.'plugins/*/share/db_installer/data/*.data.php',
		);
		foreach ($globs_data as $glob) {
			foreach (glob($glob) as $f) {
				$t_name = substr(basename($f), 0, -strlen('.data.php'));
#				require_once $f; // $data should be loaded from file
#				$this->TABLES_DATA[$t_name] = $data;
			}
		}
