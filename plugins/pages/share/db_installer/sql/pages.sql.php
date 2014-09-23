<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`locale` char(5) NOT NULL default \'\',
	`name` varchar(255) NOT NULL default \'\',
	`title` varchar(255) NOT NULL default \'\',
	`heading` varchar(255) NOT NULL default \'\',
	`text` longtext NOT NULL default \'\',
	`meta_keywords` text NOT NULL default \'\',
	`meta_desc` text NOT NULL default \'\',
	`date_created` datetime,
	`date_modified` datetime,
	`content_type` tinyint(2) unsigned NOT NULL default \'1\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	PRIMARY KEY	(`id`),
	UNIQUE KEY	(`locale`,`name`)
';