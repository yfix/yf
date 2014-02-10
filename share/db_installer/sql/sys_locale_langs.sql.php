<?php
$data = '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(12) NOT NULL DEFAULT \'\',
  `name` varchar(64) NOT NULL DEFAULT \'\',
  `charset` varchar(32) NOT NULL,
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'0\',
  `is_default` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `locale` (`locale`)
';