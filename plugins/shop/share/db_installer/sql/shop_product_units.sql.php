<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(32) NOT NULL,
  `description` varchar(255) NOT NULL,
  `step` varchar(255) NOT NULL DEFAULT \'1\',
  `k` decimal(8,3) unsigned NOT NULL DEFAULT \'1.000\',
  PRIMARY KEY (`id`)
';