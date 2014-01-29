<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`query_method` varchar(128) NOT NULL default \'\',
	`query_table` varchar(128) NOT NULL default \'\',
	`date` datetime NOT NULL,
	`data_old` longtext NOT NULL default \'\',
	`data_new` longtext NOT NULL default \'\',
	`data_diff` longtext NOT NULL default \'\',
	`user_id` int(10) unsigned NOT NULL default \'0\',
	`user_group` int(10) unsigned NOT NULL default \'0\',
	`site_id` tinyint(3) unsigned NOT NULL default \'0\',
	`server_id` tinyint(3) unsigned NOT NULL default \'0\',
	`ip` char(15) NOT NULL,
	`url` text NOT NULL default \'\',
	`extra` longtext NOT NULL default \'\',
	PRIMARY KEY	(`id`)
';