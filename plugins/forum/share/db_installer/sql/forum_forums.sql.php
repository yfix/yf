<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`parent` int(10) unsigned NOT NULL default \'0\',
	`category` int(10) unsigned NOT NULL default \'0\',
	`name` varchar(255) NOT NULL default \'\',
	`desc` varchar(255) NOT NULL default \'\',
	`created` int(10) unsigned NOT NULL default \'0\',
	`status` char(1) NOT NULL default \'a\',
	`active` tinyint(1) NOT NULL default \'1\',
	`icon` varchar(255) NOT NULL default \'\',
	`order` int(10) unsigned NOT NULL default \'0\',
	`num_views` int(10) unsigned NOT NULL default \'0\',
	`num_topics` int(10) unsigned NOT NULL default \'0\',
	`num_posts` int(10) unsigned NOT NULL default \'0\',
	`last_post_id` int(10) unsigned NOT NULL default \'0\',
	`last_post_date` int(10) unsigned NOT NULL default \'0\',
	`language` varchar(12) NOT NULL default \'0\',
	`options` CHAR(10) NOT NULL,
	`user_groups` varchar(255) NOT NULL default \'\',
	PRIMARY KEY	(`id`)
';