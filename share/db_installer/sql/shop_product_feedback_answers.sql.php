<?php
$data = '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT \'0\',
  `product_id` int(11) NOT NULL DEFAULT \'0\',
  `feedback_id` int(11) NOT NULL DEFAULT \'0\',
  `name` varchar(128) NOT NULL DEFAULT \'\',
  `email` varchar(128) NOT NULL DEFAULT \'\',
  `content` text NOT NULL,
  `add_date` int(11) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
';