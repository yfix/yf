<?php
return '
	`id` varchar(32) NOT NULL default \'\',
	`user_id` int(10) unsigned NOT NULL,
	`user_group` int(10) unsigned NOT NULL,
	`time` int(10) unsigned NOT NULL default \'0\',
	`type` enum(\'user\',\'admin\') NOT NULL,
	`ip` varchar(16) NOT NULL,
	`user_agent` varchar(255) NOT NULL,
	`query_string` varchar(255) NOT NULL,
	`site_id` tinyint(3) unsigned NOT NULL,
	PRIMARY KEY	(`id`),
	KEY `user_id` (`user_id`)
	/** ENGINE=MEMORY **/ 
';