#!/usr/bin/php
<?php

require_once dirname(__DIR__).'/scripts_init.php';

$ext = '.sql_php.php';
$globs_php = array(
	'yf_main'		=> YF_PATH.'share/db_installer/sql_php/*'.$ext,
	'yf_plugins'	=> YF_PATH.'plugins/*/share/db_installer/sql_php/*'.$ext,
);
foreach ($globs_php as $glob) {
	foreach (glob($glob) as $f) {
		echo '== '.$f. PHP_EOL;
		$t_name = substr(basename($f), 0, -strlen($ext));

		$sql_file = dirname(dirname($f)).'/sql/'.$t_name. '.sql.php';
		echo '++ sql: '.$sql_file. PHP_EOL;
		if (file_exists($sql_file)) {
#			echo 'exists, skipped'. PHP_EOL;
#			continue;
		}

		$sql_dir = dirname($sql_file);
		if (!file_exists($sql_dir)) {
			mkdir($sql_dir, 0755, true);
		}

		$data = include $f; // $data should be loaded from file

		if (!$data) {
			echo '-- ERROR: empty data'. PHP_EOL;
			continue;
		}

		$tmp_name = 'tmp_name_not_exists';

		$data['name'] = $tmp_name;
		$sql = _class('db_ddl_parser_mysql', 'classes/db/')->create($data);

		$sql_a = explode(PHP_EOL, trim($sql));
		$last_index = count($sql_a) - 1;
		$last_item = $sql_a[$last_index];
		unset($sql_a[0]);
		unset($sql_a[$last_index]);

		// Add commented table attributes
		$options = array();
		foreach ((array)$data['options'] as $k => $v) {
			if ($k == 'charset') {
				$k = 'DEFAULT CHARSET';
			}
			$options[$k] = strtoupper($k).'='.$v;
		}
		$sql_a[] = $options ? '  /** '.implode(' ', $options).' **/' : '';

		$sql = trim(implode(PHP_EOL, $sql_a));

		if (!$sql) {
			echo '-- ERROR: empty sql'. PHP_EOL;
			continue;
		}

		$body = '<?'.'php'.PHP_EOL.'return \''. PHP_EOL. $sql. PHP_EOL. '\';'.PHP_EOL;
echo $body .PHP_EOL.PHP_EOL;
		if (file_exists($sql_file) && md5($body) == md5(file_get_contents($sql_file))) {
			continue;
		}
#		file_put_contents($sql_file, $body);
	}
}
