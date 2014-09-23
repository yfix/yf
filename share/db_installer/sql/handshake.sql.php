<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender` int(11) NOT NULL DEFAULT \'0\',
  `receiver` int(11) NOT NULL DEFAULT \'0\',
  `text` varchar(64) NOT NULL,
  `add_date` text NOT NULL,
  `action_date` text NOT NULL,
  `status` text NOT NULL,
  PRIMARY KEY (`id`)
';
