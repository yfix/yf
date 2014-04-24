#!/usr/bin/php
<?php

require __DIR__.'/github_api_funcs.php';

$user = 'yfix';
include __DIR__.'/data/'.$user.'_repos.php';
foreach ($data as $k => $a) {
	if (!$a['fork']) {
		continue;
	}
	echo PHP_EOL.'('.($k+1).'/'.count($data).') == '.$a['full_name'].' =='.PHP_EOL.PHP_EOL;

	$dir = __DIR__.'/data/'.$user.'/';
	!file_exists($dir) && mkdir($dir, 1);

	$info = get_data_from_url($dir. $a['name'].'.json', 'https://api.github.com/repos/'.$user.'/'.$a['name'], $sleep = 2);
	save_php_data($dir. $a['name'].'.php', $info);
}
