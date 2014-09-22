<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL,
	`desc` varchar(255) NOT NULL,
	`points` varchar(255) NOT NULL,
	`min_value` varchar(255) NOT NULL,
	`min_time` int(10) unsigned NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL,
	`table_name` varchar(255) NOT NULL,
	PRIMARY KEY	(`id`)
';