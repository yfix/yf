<?php
$data = '
	`id` int(10) unsigned NOT NULL,
	`sender_id` int(10) unsigned NOT NULL default \'0\',
	`receiver_id` int(10) unsigned NOT NULL default \'0\',
	`s_section` enum(\'sent\',\'trash\') NOT NULL default \'sent\',
	`r_section` enum(\'inbox\',\'trash\') NOT NULL default \'inbox\',
	`s_status` enum(\'read\',\'unread\',\'replied\',\'sent\',\'approved\',\'disapproved\',\'deleted\') NOT NULL default \'unread\',
	`r_status` enum(\'read\',\'unread\',\'replied\',\'approved\',\'disapproved\',\'deleted\') NOT NULL default \'unread\',
	`add_date` int(10) unsigned NOT NULL default \'0\',
	`subject` varchar(255) NOT NULL default \'\',
	`message` text NOT NULL,
	`type` varchar(20) NOT NULL default \'standard\',
	`special_id` int(10) unsigned NOT NULL default \'0\',
	PRIMARY KEY	(`id`),
	KEY `receiver_id` (`receiver_id`),
	KEY `sender_id` (`sender_id`)
';