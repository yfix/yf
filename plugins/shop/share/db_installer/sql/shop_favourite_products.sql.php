<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `add_date` int(11) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
';