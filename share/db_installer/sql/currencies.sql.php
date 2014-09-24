<?php
return '
  `id` char(3) NOT NULL DEFAULT \'\',
  `name` varchar(64) NOT NULL DEFAULT \'\',
  `name_eng` varchar(64) NOT NULL DEFAULT \'\',
  `sign` varchar(32) NOT NULL DEFAULT \'\',
  `number` int(10) NOT NULL DEFAULT \'0\',
  `minor_units` int(2) NOT NULL DEFAULT \'0\',
  `country_name` varchar(64) NOT NULL DEFAULT \'\',
  `country_code` char(2) NOT NULL DEFAULT \'\',
  `continent_code` char(2) NOT NULL DEFAULT \'\',
  `active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
';
