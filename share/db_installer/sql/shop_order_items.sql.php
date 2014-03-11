<?php
$data = '
  `order_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `type` int(10) unsigned NOT NULL DEFAULT \'0\',
  `product_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `param_id` varchar(128) NOT NULL DEFAULT \'\',
  `user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `quantity` int(10) unsigned NOT NULL DEFAULT \'0\',
  `price` decimal(12,2) NOT NULL,
  `status` int(10) unsigned NOT NULL DEFAULT \'0\',
  KEY `order_id` (`order_id`)
';