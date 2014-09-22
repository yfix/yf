<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`tag_id` int(10) unsigned NOT NULL,
	`object_id` int(10) unsigned NOT NULL,
	`object_name` varchar(64) NOT NULL default \'\',
	`owner_id` int(10) unsigned NOT NULL,
	`user_id` int(10) unsigned NOT NULL,
	`date` int(10) unsigned NOT NULL,
	`text` varchar(128) NOT NULL default \'\',
	`site_id` int(10) unsigned NOT NULL,
	`ip` varchar(16) NOT NULL default \'\',
	`user_agent` varchar(255) NOT NULL default \'\',
	`referer` varchar(255) NOT NULL default \'\',
	`request_uri` varchar(255) NOT NULL default \'\',
	PRIMARY KEY  (`id`)
';