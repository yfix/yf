<?php
$data = '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL DEFAULT \'0\',
  `name` varchar(64) NOT NULL DEFAULT \'\',
  `type` varchar(64) NOT NULL DEFAULT \'\',
  `value_list` text NOT NULL,
  `default_value` text NOT NULL,
  `order` int(10) unsigned NOT NULL DEFAULT \'0\',
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`)
';