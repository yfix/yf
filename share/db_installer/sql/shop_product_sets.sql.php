<?php
$data = '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` int(11) unsigned NOT NULL DEFAULT \'0\',
  `price` decimal(12,2) NOT NULL,
  `old_price` decimal(12,2) NOT NULL,
  `name` varchar(128) NOT NULL,
  `description` text NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
';