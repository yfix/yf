<?php
$data = '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL DEFAULT \'0\',
  `user_id` int(11) NOT NULL DEFAULT \'0\',
  `name` varchar(128) NOT NULL DEFAULT \'\',
  `email` varchar(128) NOT NULL DEFAULT \'\',
  `is_notify` tinyint(4) NOT NULL DEFAULT \'0\',
  `content` text NOT NULL,
  `add_date` int(11) NOT NULL DEFAULT \'0\',
  `pros` text NOT NULL,
  `cons` text NOT NULL,
  `rating` int(11) NOT NULL DEFAULT \'0\',
  `votes_yes` int(11) NOT NULL DEFAULT \'0\',
  `votes_no` int(11) NOT NULL DEFAULT \'0\',
  `ip` varchar(16) NOT NULL DEFAULT \'\',
  `user_agent` varchar(128) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`)
';