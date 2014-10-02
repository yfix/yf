<?php
return '
  `code` char(2) NOT NULL,
  `country` char(2) NOT NULL,
  `name` varchar(255) NOT NULL,
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  KEY `country` (`country`),
  KEY `code` (`code`)
';
