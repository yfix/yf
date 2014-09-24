<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT \'0\',
  `add_date` int(11) NOT NULL DEFAULT \'0\',
  `action` varchar(127) NOT NULL DEFAULT \'\',
  `item_id` int(11) NOT NULL DEFAULT \'0\',
  `ip` char(15) NOT NULL,
  `table` varchar(127) NOT NULL DEFAULT \'\',
  `data` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`)
';
