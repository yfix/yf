<?php
return '
  `product_id` int(11) unsigned NOT NULL,
  `related_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`related_id`)
';