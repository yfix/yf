<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`key` varchar(255) NOT NULL,
	`value` text NOT NULL,
	`desc` text NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL,
	PRIMARY KEY (`id`)
	/** DEFAULT CHARSET=UTF8 **/ 
';