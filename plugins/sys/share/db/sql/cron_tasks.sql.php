<?php

return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `comment` varchar(255) NOT NULL,
  `frequency` varchar(255) NOT NULL,
  `exec_type` varchar(255) NOT NULL,
  `dir` varchar(255) NOT NULL,
  `admin_id` int(10) unsigned NOT NULL,
  `update_date` int(10) unsigned NOT NULL,
  `exec_time` int(10) unsigned NOT NULL DEFAULT \'600\',
  `active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
