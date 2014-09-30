<?php
return '
  `code` char(6) NOT NULL DEFAULT \'\',
  `name` varchar(64) NOT NULL DEFAULT \'\',
  `name_eng` varchar(64) NOT NULL DEFAULT \'\',
  `offset` varchar(16) NOT NULL DEFAULT \'\',
  `active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`code`)
';
