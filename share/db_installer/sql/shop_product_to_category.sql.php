<?php
$data = '
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`category_id`),
  KEY `product_id` (`product_id`),
  KEY `category_id` (`category_id`)
';