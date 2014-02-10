<?php
$data = '
  `var_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `value` text NOT NULL,
  `locale` varchar(12) NOT NULL DEFAULT \'\',
  KEY `lang` (`locale`),
  KEY `var_id` (`var_id`)
';