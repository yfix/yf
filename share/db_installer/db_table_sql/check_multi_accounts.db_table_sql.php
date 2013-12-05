<?php
$data = '
	`user_id` int(10) unsigned NOT NULL,
	`matching_users` longtext NOT NULL,
	`matching_ips` longtext NOT NULL,
	`num_m_users` int(10) unsigned NOT NULL default \'0\',
	`num_m_ips` int(10) unsigned NOT NULL default \'0\',
	`last_update` int(10) unsigned NOT NULL default \'0\',
	`cookie_match` enum(\'0\',\'1\') NOT NULL default \'0\',
	`ip_match` enum(\'0\',\'1\') NOT NULL,
	PRIMARY KEY	(`user_id`),
	KEY `ip_match` (`ip_match`),
	KEY `cookie_match` (`cookie_match`)
';