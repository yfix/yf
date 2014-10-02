<?php
return '
  `key` varchar(64) NOT NULL DEFAULT \'\',
  `value` longtext NOT NULL DEFAULT \'\',
  `time` int(10) unsigned NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`key`)
  /** ENGINE=InnoDB **/
';
