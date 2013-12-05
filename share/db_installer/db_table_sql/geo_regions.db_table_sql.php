<?php
$data = '
	`country` char(2) NOT NULL,
	`code` char(2) NOT NULL,
	`name` varchar(255) NOT NULL,
	KEY `country` (`country`),
	KEY `code` (`code`)
';