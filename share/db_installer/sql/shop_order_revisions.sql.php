<?php
$data = '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT \'0\',
  `add_date` int(11) NOT NULL DEFAULT \'0\',
  `action` varchar(127) NOT NULL DEFAULT \'\',
  `item_id` int(11) NOT NULL DEFAULT \'0\',
  `data` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`)
';
