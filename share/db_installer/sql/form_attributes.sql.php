<?php
return '
  `form_id` varchar(64) NOT NULL DEFAULT \'\',
  `field` varchar(64) NOT NULL DEFAULT \'\',
  `attr` varchar(64) NOT NULL DEFAULT \'\',
  `value` longtext NOT NULL DEFAULT \'\',
  `active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`form_id`,`field`,`attr`)
';
