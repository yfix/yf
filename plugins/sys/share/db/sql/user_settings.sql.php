<?php

return '
  `user_id` int(11) unsigned NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` longtext NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`user_id`,`key`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
