<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`block_id` smallint(5) unsigned NOT NULL default \'0\',
	`rule_type` enum(\'DENY\',\'ALLOW\') NOT NULL default \'DENY\',
	`user_groups` varchar(255) NOT NULL default \'\',
	`methods` text NOT NULL default \'\',
	`themes` text NOT NULL default \'\',
	`locales` text NOT NULL default \'\',
	`others` text NOT NULL default \'\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	`order` int(10) unsigned NOT NULL default \'0\',
	`site_ids` varchar(1000) NOT NULL,
	`server_ids` varchar(1000) NOT NULL,
	`server_roles` varchar(1000) NOT NULL,
	PRIMARY KEY  (`id`)
';