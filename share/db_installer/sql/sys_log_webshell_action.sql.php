<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`microtime` decimal(13,3) unsigned NOT NULL default \'0.000\',
	`server_id` int(11) NOT NULL,
	`user_id` int(11) NOT NULL,
	`action` text NOT NULL,
	PRIMARY KEY	(`id`)
';