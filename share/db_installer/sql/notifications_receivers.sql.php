<?php
return '
	`notification_id` int(11) NOT NULL,
	`receiver_id` int(11) NOT NULL,
	`receiver_type` enum(\'user_id\',\'admin_id\',\'user_id_tmp\') NOT NULL,
	`is_read` tinyint(4) NOT NULL DEFAULT \'0\',
	PRIMARY KEY (`notification_id`,`receiver_id`,`receiver_type`)
';