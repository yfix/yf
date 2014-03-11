<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`engine` int(10) unsigned NOT NULL default \'0\',
	`text` varchar(64) NOT NULL,
	`ref_url` text NOT NULL,
	`site_url` varchar(255) NOT NULL default \'\',
	`site_id` int(10) unsigned NOT NULL default \'0\',
	`hits` int(10) unsigned NOT NULL default \'0\',
	`last_update` int(10) unsigned NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	PRIMARY KEY	(`id`),
	UNIQUE KEY `text` (`text`),
	KEY `hits` (`hits`),
	FULLTEXT KEY `text_2` (`text`)
';