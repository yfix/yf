<?php
$data = '
	`id` int(10) unsigned NOT NULL,
	`country` char(2) NOT NULL,
	`region_id` int(10) unsigned NOT NULL,
	`name` varchar(32) NOT NULL,
	`lat` float NOT NULL,
	`lon` float NOT NULL,
	`population` int(8) NOT NULL default \'0\',
	PRIMARY KEY	(`id`),
	KEY (`country`),
	KEY (`region_id`)
';