<?php
return '
	`user_id` int(10) unsigned NOT NULL,
	`title` varchar(255) NOT NULL,
	`desc` text NOT NULL,
	`default` text NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL,
	`date_format` tinyint(3) UNSIGNED NOT NULL,
	PRIMARY KEY	(`user_id`)
';