<?php
return '
  `source` varchar(32) NOT NULL DEFAULT \'\',
  `type` varchar(32) NOT NULL DEFAULT \'\',
  `src_item_id` int(11) NOT NULL,
  `dst_item_id` int(11) NOT NULL,
  PRIMARY KEY (`source`,`type`,`src_item_id`)
';