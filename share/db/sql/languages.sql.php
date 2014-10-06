<?php
return '
  `code` char(2) NOT NULL DEFAULT \'\',
  `code3` char(3) NOT NULL DEFAULT \'\',
  `name` varchar(64) NOT NULL DEFAULT \'\',
  `native` varchar(64) NOT NULL DEFAULT \'\',
  `country` char(2) NOT NULL DEFAULT \'\',
  `active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`code`)
';
