<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`user_id` int(11) unsigned NOT NULL default \'0\',
	`date` int(11) unsigned NOT NULL default \'0\',
	`ship_type` int(11) unsigned NOT NULL default \'0\',
	`pay_type` int(11) unsigned NOT NULL default \'0\',
	`total_sum` decimal(12,2) NOT NULL,
	`card_num` varchar(50) NOT NULL,
	`exp_date` varchar(4) NOT NULL,
	`name` varchar(32) NOT NULL,
	`email` varchar(50) NOT NULL,
	`phone` varchar(40) NOT NULL,
	`address` text NOT NULL,
	`comment_c` text NOT NULL,
	`comment_m` text NOT NULL,
	`hash` varchar(128) NOT NULL,
	`status` varchar(16) NOT NULL,
	PRIMARY KEY	(`id`)
';