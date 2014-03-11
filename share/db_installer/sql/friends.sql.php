<?php
$data = '
	`user_id` int(10) unsigned NOT NULL default \'0\',
	`friends_list` text NOT NULL,
	UNIQUE KEY `user_id` (`user_id`)
';