<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT \'0\',
  `add_date` int(11) NOT NULL DEFAULT \'0\',
  `action` varchar(127) NOT NULL DEFAULT \'\',
  `product_id` int(11) NOT NULL DEFAULT \'0\',
  `image_id` int(11) NOT NULL DEFAULT \'0\',
  `data` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
';