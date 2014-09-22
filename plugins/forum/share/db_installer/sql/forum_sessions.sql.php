<?php
return '
	`id` varchar(32) NOT NULL default \'0\',
	`user_id` mediumint(8) unsigned NOT NULL default \'0\',
	`user_name` varchar(64) default NULL,
	`user_group` smallint(3) unsigned default NULL,
	`ip_address` varchar(16) default NULL,
	`user_agent` varchar(64) default NULL,
	`login_date` int(10) unsigned NOT NULL default \'0\',
	`last_update` int(10) unsigned default NULL,
	`login_type` tinyint(1) unsigned default NULL,
	`location` varchar(40) default NULL,
	`in_forum` smallint(5) unsigned NOT NULL default \'0\',
	`in_topic` int(10) unsigned default NULL,
	PRIMARY KEY	(`id`),
	KEY `in_topic` (`in_topic`),
	KEY `in_forum` (`in_forum`)
';