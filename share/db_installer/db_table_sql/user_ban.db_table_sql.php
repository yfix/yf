<?php
$data = '
	`id` mediumint(6) unsigned NOT NULL auto_increment,
	`user_name` varchar(255) NOT NULL default \'\',
	`email` varchar(255) NOT NULL default \'\',
	`passwd` varchar(255) NOT NULL default \'\',
	`text` varchar(255) NOT NULL default \'\',
	`tel` varchar(255) NOT NULL default \'\',
	`fax` varchar(255) NOT NULL default \'\',
	`url` varchar(255) NOT NULL default \'\',
	`recip_url` varchar(255) NOT NULL default \'\',
	`ban_ads` tinyint(1) NOT NULL default \'1\',
	`ban_reviews` tinyint(1) NOT NULL default \'1\',
	`ban_images` tinyint(1) NOT NULL default \'0\',
	`ban_email` tinyint(1) NOT NULL default \'1\',
	`ban_forum` tinyint(1) NOT NULL default \'1\',
	PRIMARY KEY	(`id`)
';