<?php
return '
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8 DEFAULT \'\',
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  `locale` char(7) NOT NULL DEFAULT \'en\',
  PRIMARY KEY (`id`)
';
