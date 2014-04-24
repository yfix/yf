#!/usr/bin/php
<?php

# Example usage: print_r( ssh_exec_all('webbox', 'uptime') );

ini_set('memory_limit','256M');
ignore_user_abort(1);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

#define('DEBUG_MODE', true);
define('SITE_DEFAULT_PAGE', './?object=admin_home');
require_once INCLUDE_PATH.'project_conf.php';
require_once YF_PATH.'classes/yf_main.class.php';
new yf_main('admin', 0, 0);

// TODO: create global func ssh() as YF built-in tool to deal with ssh servers, idea can be got from Laravel:  http://laravel.com/docs/ssh
/*
SSH::into('staging')->run(array(
    'cd /var/www',
    'git pull origin master',
));

Catching Output From Commands
You may catch the "live" output of your remote commands by passing a Closure into the run method:

SSH::run($commands, function($line) {
    echo $line.PHP_EOL;
});
*/
if (!function_exists('ssh_exec_all')) {
	function ssh_exec_all ($group_name = '', $cmd = '') {
		static $server_groups, $server_groups_names, $server_groups, $server_groups_names, $servers, $servers_ids_by_group;
		if (empty($servers)) {
			$server_groups = array();
			$server_groups_names = array();
			foreach(db_pf()->get_all('SELECT * FROM '.db_pf('server_group')) as $a) {
				$server_groups[$a['id']] = $a['name'];
				$server_groups_names[$a['name']] = $a['id'];
			}
			$servers = db_pf()->get_all('SELECT * FROM '.db_pf('servers').' WHERE active="1"');
			$servers_ids_by_group = array();
			foreach ($servers as $a) {
				$servers_ids_by_group[$a['group']][$a['id']] = $a['id'];
			}
		}
		$group_id = $server_groups_names[$group_name];
		foreach ((array)$servers_ids_by_group[$group_id] as $server_id) {
			$server_info = $servers[$server_id];
			$server_name = $server_info['name'];
			$result[$server_name] = _class('ssh')->exec($server_info, $cmd);
		}
		return $result;
	}
}
