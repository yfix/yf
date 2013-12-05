<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`day` DATE NOT NULL,
	`site_id` tinyint(3) unsigned NOT NULL default \'0\',
	`server_id` tinyint(3) unsigned NOT NULL default \'0\',
	`hits` int(10) unsigned NOT NULL default \'0\',
	`member_hits` int(10) unsigned NOT NULL default \'0\',
	`spider_hits` int(10) unsigned NOT NULL default \'0\',
	`cache_hits` int(10) unsigned NOT NULL default \'0\',
	`hosts` int(10) unsigned NOT NULL default \'0\',
	`member_hosts` int(10) unsigned NOT NULL default \'0\',
	`spider_hosts` int(10) unsigned NOT NULL default \'0\',
	PRIMARY KEY	(`id`),
	UNIQUE KEY	(`day`,`site_id`)
';