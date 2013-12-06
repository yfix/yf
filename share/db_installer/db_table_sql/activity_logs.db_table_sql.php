<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`task_id` tinyint(3) unsigned NOT NULL,
	`user_id` int(10) unsigned NOT NULL,
	`add_date` int(10) unsigned NOT NULL,
	`add_points` int(10) NOT NULL,
	PRIMARY KEY	(`id`),
	KEY `user_id` (`user_id`)
';