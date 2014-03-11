<?php
$data = '
  `supplier_id` int(11) NOT NULL DEFAULT \'0\',
  `cat_id` int(11) NOT NULL DEFAULT \'0\',
  `name` varchar(255) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`supplier_id`,`cat_id`,`name`)
';