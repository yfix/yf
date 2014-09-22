<?php
return '
	`user_id` int(10) unsigned NOT NULL auto_increment,
	`verified_url_1` varchar(255) NOT NULL,
	`verified_url_2` varchar(255) NOT NULL,
	`verified_url_3` varchar(255) NOT NULL,
	`site_url` varchar(255) NOT NULL,
	`whois` text NOT NULL,
	`status` enum(\'new\',\'waiting\',\'verified\',\'declined\') NOT NULL,
	`admin_comment` text NOT NULL,
	`submit_date` int(10) unsigned NOT NULL,
	`last_update` int(10) unsigned NOT NULL,
	PRIMARY KEY	(`user_id`),
	KEY `status` (`status`),
	KEY `last_update` (`last_update`)
';