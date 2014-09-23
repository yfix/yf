<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`poll_id` int(10) unsigned NOT NULL default \'0\',
	`user_id` int(10) unsigned NOT NULL default \'0\',
	`date` int(10) unsigned NOT NULL default \'0\',
	`value` tinyint(3) unsigned NOT NULL,
	PRIMARY KEY	(`id`),
	KEY `poll_id` (`poll_id`),
	KEY `user_id` (`user_id`)
';