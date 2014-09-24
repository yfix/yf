<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `comment` varchar(255) NOT NULL,
  `frequency` varchar(255) NOT NULL,
  `admin_id` int(10) unsigned NOT NULL,
  `update_date` int(10) unsigned NOT NULL,
  `active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
