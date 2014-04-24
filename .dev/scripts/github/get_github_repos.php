#!/usr/bin/php
<?php

require __DIR__.'/github_api_funcs.php';

$user = 'yfix';

$info = get_data_from_url(__DIR__.'/data/'.$user.'_info.json', 'https://api.github.com/users/'.$user);
save_php_data(__DIR__.'/data/'.$user.'_info.php', $info);

$repos = array();
foreach (range(1, ceil($info['public_repos'] / 100)) as $i) {
	$_repos = get_data_from_url(__DIR__.'/data/'.$user.'_repos_'.$i.'.json', 'https://api.github.com/users/'.$user.'/repos?per_page=100&page='.$i);
	foreach ((array)$_repos as $a) {
		foreach ($a as $k => $v) {
			if (in_array($k, array('owner')) || strpos($k, '_url') !== false) {
				unset($a[$k]);
			}
		}
		$repos[] = $a;
	}
}
save_php_data(__DIR__.'/data/'.$user.'_repos.php', $repos);
