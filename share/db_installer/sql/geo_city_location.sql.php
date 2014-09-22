<?php
return '
	`loc_id` int(10) unsigned NOT NULL,
	`country` char(2) NOT NULL,
	`region` char(3) NOT NULL,
	`city` varchar(32) NOT NULL,
	`postal_code` char(5) NOT NULL,
	`latitude` float NOT NULL,
	`longitude` float NOT NULL,
	`dma_code` int(8) unsigned NOT NULL,
	`area_code` int(8) unsigned NOT NULL,
	PRIMARY KEY	(`loc_id`),
	KEY `country` (`country`)
';