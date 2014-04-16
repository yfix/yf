<?php
$data = "
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`city` varchar(255) DEFAULT NULL,
	`address` varchar(255) DEFAULT NULL,
	`tel` varchar(255) DEFAULT NULL,
	`time_in_1` varchar(255) DEFAULT NULL,
	`time_in_2` varchar(255) DEFAULT NULL,
	`time_out_1` varchar(255) DEFAULT NULL,
	`time_out_2` varchar(255) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE `city_address` (`city`,`address`),
	KEY `city` (`city`),
	KEY `address` (`address`)
";

