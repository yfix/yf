#!/usr/bin/php
<?php

require_once dirname(dirname(__FILE__)).'/scripts_init.php';

$globs_sql = array(
	'yf_main'		=> YF_PATH.'share/db_installer/sql/*.sql.php',
	'yf_plugins'	=> YF_PATH.'plugins/*/share/db_installer/sql/*.sql.php',
);
foreach ($globs_sql as $glob) {
	foreach (glob($glob) as $f) {
		echo '== '.$f. PHP_EOL;
		$t_name = substr(basename($f), 0, -strlen('.sql.php'));
		$fields_file = dirname(dirname($f)).'/fields/'.$t_name.'.fields.php';
		echo '++ fields: '.$fields_file. PHP_EOL;
		if (file_exists($fields_file)) {
#			echo 'exists, skipped'. PHP_EOL;
#			continue;
		}
		$fields_dir = dirname($fields_file);
		if (!file_exists($fields_dir)) {
			mkdir($fields_dir, 0755, true);
		}

		$data = '';
		include $f; // $data should be loaded from file

		if (!$data) {
			echo '-- ERROR: empty data'. PHP_EOL;
			continue;
		}

		$a = _class('db_installer_mysql', 'classes/db/')->_db_table_struct_into_array($data);

		if (!$a) {
			echo '-- ERROR: empty fields'. PHP_EOL;
			continue;
		}
#		print_r($a);
		$body = '<?'.'php'.PHP_EOL.'$data = '.str_replace('  ', "\t", var_export($a, 1)).';'.PHP_EOL;
		if (file_exists($fields_file) && md5($body) == md5(file_get_contents($fields_file))) {
			continue;
		}
		file_put_contents($fields_file, $body);
	}
}
