<?php
$data = '
  `product_set_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `product_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `param_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `quantity` int(10) unsigned NOT NULL DEFAULT \'0\',
  KEY `basket_id` (`product_set_id`)
';