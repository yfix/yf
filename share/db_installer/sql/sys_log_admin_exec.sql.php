<?php
$data = '
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`date` int(10) unsigned NOT NULL DEFAULT \'0\',
	`query_string` varchar(255) NOT NULL DEFAULT \'\',
	`request_uri` varchar(255) NOT NULL,
	`exec_time` float NOT NULL DEFAULT \'0\',
	`num_dbq` smallint(5) unsigned NOT NULL DEFAULT \'0\',
	`page_size` int(10) unsigned NOT NULL DEFAULT \'0\',
	`admin_id` int(10) unsigned NOT NULL DEFAULT \'0\',
	`admin_group` tinyint(3) unsigned NOT NULL,
	`ip` varchar(16) NOT NULL DEFAULT \'\',
	`user_agent` varchar(255) NOT NULL DEFAULT \'\',
	`referer` varchar(255) NOT NULL DEFAULT \'\',
	`site_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
	`server_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
	PRIMARY KEY (`id`),
	KEY `admin_group` (`admin_group`)
';