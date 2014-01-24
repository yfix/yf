<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`date` datetime NOT NULL,
	`data_old` longtext NOT NULL default \'\',
	`data_new` longtext NOT NULL default \'\',
	`data_diff` longtext NOT NULL default \'\',
	`object_name` varchar(64) NOT NULL default \'\',
	`object_id` varchar(64) NOT NULL default \'\',
	`user_id` int(10) unsigned NOT NULL default \'0\',
	`site_id` tinyint(3) unsigned NOT NULL default \'0\',
	`server_id` tinyint(3) unsigned NOT NULL default \'0\',
	`ip` char(15) NOT NULL,
	`query_type` varchar(32) NOT NULL default \'\',
	`comment` varchar(255) NOT NULL default \'\',
	PRIMARY KEY	(`id`)
';