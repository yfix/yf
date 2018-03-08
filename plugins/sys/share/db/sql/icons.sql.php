<?php
return '
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT \'\',
  `active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
';
