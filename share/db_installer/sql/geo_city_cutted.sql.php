<?php
$data = '
	`country` char(2) NOT NULL,
	`city` varchar(32) NOT NULL,
	`region` char(3) NOT NULL,
	`population` int(8) NOT NULL default \'0\',
	KEY `country` (`country`)
';