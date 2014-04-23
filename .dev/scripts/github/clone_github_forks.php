#!/usr/bin/php
<?php

require __DIR__.'/github_api_funcs.php';

$user = 'yfix';
include __DIR__.'/data/'.$user.'_repos.php';

$d = '/home/www/github_forks/';
!file_exists($d) && mkdir($d, 1);

# https://help.github.com/articles/syncing-a-fork
foreach ($data as $k => $a) {
	if (!$a['fork']) {
		continue;
	}
	echo PHP_EOL.'('.($k+1).'/'.count($data).') == '.$a['full_name'].' =='.PHP_EOL.PHP_EOL;
	$target = $d.$a['name'];
	if (file_exists($target.'/.git/config')) {
		passthru('(cd '.$target.' && git pull -r)');
	} else {
		$clone_url = 'https://github.com/yfix/'.$a['name'].'.git';
		passthru('git clone '.$clone_url.' '.$target);
	}
	passthru('git remote -v');
}