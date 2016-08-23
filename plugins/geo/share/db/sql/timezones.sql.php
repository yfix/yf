<?php
return '
  `name` varchar(64) NOT NULL DEFAULT \'\',
  `offset` varchar(16) NOT NULL DEFAULT \'\',
  `seconds` int(11) NOT NULL DEFAULT \'0\',
  `active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`name`)
';
