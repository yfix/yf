<?php
$data = '
	`order_id` int(10) unsigned NOT NULL default \'0\',
	`product_id` int(10) unsigned NOT NULL default \'0\',
	`user_id` int(10) unsigned NOT NULL default \'0\',
	`quantity` int(10) unsigned NOT NULL default \'0\',
	`attributes` text NOT NULL default \'\',
	`sum` decimal(12,2) NOT NULL,
	KEY `order_id` (`order_id`)
';