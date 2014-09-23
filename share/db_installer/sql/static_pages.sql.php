<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`cat_id` int(10) unsigned NOT NULL default \'0\',
	`name` varchar(255) NOT NULL default \'\',
	`text` text NOT NULL default \'\',
	`page_title` varchar(255) NOT NULL default \'\',
	`page_heading` varchar(255) NOT NULL default \'\',
	`meta_keywords` text NOT NULL default \'\',
	`meta_desc` text NOT NULL default \'\',
	`locale` char(6) NOT NULL default \'\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	PRIMARY KEY	(`id`)
';