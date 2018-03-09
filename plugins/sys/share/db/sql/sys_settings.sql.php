<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item` varchar(255) NOT NULL DEFAULT \'\',
  `value` text NOT NULL,
  `date` datetime NOT NULL,
  `config` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item` (`item`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
