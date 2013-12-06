<?php
$data = '
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(64) NOT NULL default \'\',
	`url` varchar(255) NOT NULL,
	`desc` varchar(255) NOT NULL,
	`meta_keywords` text NOT NULL,
	`meta_desc` text NOT NULL,
	`image` int(10) unsigned NOT NULL,
	`sort_order` int(3) NOT NULL,
	PRIMARY KEY	(`id`)
';