<?php
return '
  `user_id` bigint(20) NOT NULL,
  `user_type` enum(\'user_id\',\'user_id_tmp\',\'admin_id\') NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`user_type`)
';
