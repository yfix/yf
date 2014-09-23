<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`object` varchar(32) NOT NULL default \'\',
	`action` varchar(32) NOT NULL default \'\',
	`query_string` varchar(128) NOT NULL default \'\',
	`site_id` tinyint(3) unsigned NOT NULL default \'0\',
	`group_id` tinyint(3) unsigned NOT NULL default \'1\',
	`hash` varchar(32) NOT NULL default \'\',
	PRIMARY KEY (`id`),
	UNIQUE KEY `object` (`object`,`action`,`query_string`,`site_id`)
';