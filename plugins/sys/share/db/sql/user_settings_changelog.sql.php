<?php

return '
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `key` varchar(64) NOT NULL,
  `val_old` longtext NOT NULL,
  `val_new` longtext NOT NULL,
  `ip` varchar(16) NOT NULL,
  `ua` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
