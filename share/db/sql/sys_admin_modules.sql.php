<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT \'\',
  `description` varchar(255) NOT NULL,
  `version` varchar(16) NOT NULL,
  `author` varchar(255) NOT NULL,
  `active` enum(\'0\',\'1\') NOT NULL,
  PRIMARY KEY (`id`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
