#!/usr/bin/php
<?php

require_once dirname(__DIR__).'/scripts_init.php';

$ext = '.sql.php';
$globs_sql = array(
	'yf_main'		=> YF_PATH.'share/db_installer/sql/*'.$ext,
	'yf_plugins'	=> YF_PATH.'plugins/*/share/db_installer/sql/*'.$ext,
);
foreach ($globs_sql as $glob) {
	foreach (glob($glob) as $f) {
		echo '== '.$f. PHP_EOL;
		$t_name = substr(basename($f), 0, -strlen($ext));

		$sql_php_file = dirname(dirname($f)).'/sql_php/'.$t_name.'.sql_php.php';
		echo '++ sql_php: '.$sql_php_file. PHP_EOL;
		if (file_exists($sql_php_file)) {
#			echo 'exists, skipped'. PHP_EOL;
#			continue;
		}

		$sql_php_dir = dirname($sql_php_file);
		if (!file_exists($sql_php_dir)) {
			mkdir($sql_php_dir, 0755, true);
		}

		$data = include $f; // $data should be loaded from file

		if (!$data) {
			echo '-- ERROR: empty data'. PHP_EOL;
			continue;
		}

		$a = _class('db_installer_mysql', 'classes/db/')->_db_table_struct_into_array($data);
		if (isset($a['name'])) {
			unset($a['name']);
		}

		if (!$a) {
			echo '-- ERROR: empty sql_php'. PHP_EOL;
			continue;
		}
#		print_r($a);

		$str = var_export($a, 1);
		$str = str_replace('  ', "\t", $str);
		$str = str_replace('array (', 'array(', $str);
		$str = preg_replace('~=>[\s]+array\(~ims', '=> array(', $str);

		$body = '<?'.'php'.PHP_EOL.'return '.$str.';'.PHP_EOL;
		if (file_exists($sql_php_file) && md5($body) == md5(file_get_contents($sql_php_file))) {
			continue;
		}
		file_put_contents($sql_php_file, $body);
	}
}
