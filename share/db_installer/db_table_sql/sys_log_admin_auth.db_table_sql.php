<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`admin_id` int(10) unsigned NOT NULL default \'0\',
	`login` varchar(255) NOT NULL default \'\',
	`group` int(10) unsigned NOT NULL default \'0\',
	`date` int(10) unsigned NOT NULL default \'0\',
	`session_id` varchar(32) NOT NULL default \'\',
	`ip` varchar(16) NOT NULL default \'\',
	`user_agent` varchar(255) NOT NULL default \'\',
	`referer` varchar(255) NOT NULL default \'\',
	`activity` int(10) unsigned NOT NULL default \'0\',
	PRIMARY KEY	(`id`),
	KEY `ip` (`ip`),
	KEY `admin_id` (`admin_id`),
	KEY `date` (`date`)
';