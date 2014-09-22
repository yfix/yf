<?php
return '
	`user_id` int(10) unsigned NOT NULL default \'0\',
	`add_date` int(10) unsigned NOT NULL default \'0\',
	`active` enum(\'1\',\'0\') NOT NULL,
	PRIMARY KEY	(`user_id`)
';