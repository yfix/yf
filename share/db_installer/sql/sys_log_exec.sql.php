<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`date` int(10) unsigned NOT NULL default \'0\',
	`query_string` varchar(255) NOT NULL default \'\',
	`request_uri` varchar(255) NOT NULL,
	`exec_time` float NOT NULL default \'0\',
	`num_dbq` smallint(5) unsigned NOT NULL default \'0\',
	`page_size` int(10) unsigned NOT NULL default \'0\',
	`user_id` int(10) unsigned NOT NULL default \'0\',
	`user_group` tinyint(3) unsigned NOT NULL,
	`ip` varchar(16) NOT NULL default \'\',
	`user_agent` varchar(255) NOT NULL default \'\',
	`referer` varchar(255) NOT NULL default \'\',
	`site_id` tinyint(3) unsigned NOT NULL default \'0\',
	`server_id` tinyint(3) unsigned NOT NULL default \'0\',
	`is_admin` enum(\'0\',\'1\') NOT NULL default \'0\',
	PRIMARY KEY	(`id`),
	KEY `user_group` (`user_group`)
';