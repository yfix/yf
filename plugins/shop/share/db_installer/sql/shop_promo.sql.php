<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `product_id` int(11) NOT NULL DEFAULT \'0\',
  `is_product` tinyint(4) NOT NULL DEFAULT \'0\',
  `url` varchar(255) NOT NULL,
  `image` text NOT NULL,
  `type` enum(\'slider\',\'basket\') NOT NULL,
  `position` varchar(16) NOT NULL DEFAULT \'\',
  `status` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
';