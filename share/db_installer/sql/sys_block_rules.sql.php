<?php
return '
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`block_id` smallint(5) unsigned NOT NULL DEFAULT \'0\',
	`rule_type` enum(\'DENY\',\'ALLOW\') NOT NULL DEFAULT \'DENY\',
	`user_groups` varchar(255) NOT NULL DEFAULT \'\',
	`methods` text NOT NULL,
	`themes` text NOT NULL,
	`locales` text NOT NULL,
	`others` text NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
	`order` int(10) unsigned NOT NULL DEFAULT \'0\',
	`site_ids` varchar(1000) NOT NULL,
	`server_ids` varchar(1000) NOT NULL,
	`server_roles` varchar(1000) NOT NULL,
	PRIMARY KEY (`id`)
';