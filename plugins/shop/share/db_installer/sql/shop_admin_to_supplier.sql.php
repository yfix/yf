<?php
return '
  `admin_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `main_cat_id` int(11) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`admin_id`,`supplier_id`)
';