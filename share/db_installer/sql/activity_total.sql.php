<?php
return '
	`user_id` int(10) unsigned NOT NULL,
	`points` int(10) unsigned NOT NULL,
	`exchanged_act_points` INT UNSIGNED NOT NULL,
	`last_update` int(10) unsigned NOT NULL,
	PRIMARY KEY	(`user_id`)
';