<?php
return "
	`id`          int(11)      NOT NULL  AUTO_INCREMENT,
	`city_raw`    varchar(255) DEFAULT NULL,
	`address_raw` varchar(255) DEFAULT NULL,
	`tel_raw`     varchar(255) DEFAULT NULL,
	`city`        varchar(255) DEFAULT NULL,
	`branch_no`   int(11)      DEFAULT NULL,
	`address`     varchar(255) DEFAULT NULL,
	`info`        varchar(255) DEFAULT NULL,
	`location`    varchar(255) DEFAULT NULL,
	`tel`         varchar(255) DEFAULT NULL,
	`time_in_1`   varchar(255) DEFAULT NULL,
	`time_in_2`   varchar(255) DEFAULT NULL,
	`time_out_1`  varchar(255) DEFAULT NULL,
	`time_out_2`  varchar(255) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE `city_raw__address_raw` (`city_raw`,`address_raw`),
	KEY `city`      (`city`),
	KEY `branch_no` (`branch_no`),
	KEY `address`   (`address`)
";

