<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`user_id` int(10) unsigned NOT NULL,
	`from_url` varchar(255) NOT NULL,
	`date` int(10) unsigned NOT NULL,
	PRIMARY KEY	(`id`),
	KEY `user_id` (`user_id`)
';