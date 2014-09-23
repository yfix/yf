<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`title` varchar(255) NOT NULL default \'\',
	`post` text NOT NULL,
	`forum` text NOT NULL,
	`author_id` mediumint(8) unsigned NOT NULL default \'0\',
	`html_enabled` tinyint(1) NOT NULL default \'0\',
	`views` int(10) unsigned NOT NULL default \'0\',
	`start_time` int(10) unsigned NOT NULL default \'0\',
	`end_time` int(10) unsigned NOT NULL default \'0\',
	`active` tinyint(1) NOT NULL default \'1\',
	PRIMARY KEY	(`id`)
';