<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL,
	`desc` varchar(255) NOT NULL,
	`code` text NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL,
	PRIMARY KEY	(`id`)
';