<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`title` varchar(255) NOT NULL default \'\',
	`head_text` text NOT NULL,
	`full_text` text NOT NULL,
	`meta_keywords` text NOT NULL,
	`meta_desc` text NOT NULL,
	`add_date` int(10) unsigned NOT NULL default \'0\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	PRIMARY KEY  (`id`)
';