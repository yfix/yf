<?php
return '
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(64) NOT NULL,
	`desc` varchar(255) NOT NULL,
	`type` enum(\'user\',\'admin\') NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL,
	`stpl_name` varchar(255) NOT NULL,
	`method_name` varchar(255) NOT NULL,
	`custom_fields` text NOT NULL,
	PRIMARY KEY (`id`)
';