<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL default \'\',
	`search_url` varchar(255) NOT NULL default \'\',
	`q_s_word` varchar(255) NOT NULL default \'\',
	`q_s_word2` varchar(255) NOT NULL default \'\',
	`q_s_charset` varchar(255) NOT NULL default \'\',
	`def_charset` varchar(255) NOT NULL default \'\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	PRIMARY KEY	(`id`)
';