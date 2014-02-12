<?php
$data = '
	`set_id` int(10) unsigned NOT NULL DEFAULT \'0\',
	`product_id` int(10) unsigned NOT NULL DEFAULT \'0\',
	`quantity` int(10) unsigned NOT NULL DEFAULT \'0\',
	`attributes` text NOT NULL,
	`sum` decimal(12,2) NOT NULL,
	KEY `set_id_product_id` (`set_id`,`product_id`)
';