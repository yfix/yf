<?php
return '
  `ip` varchar(20) NOT NULL DEFAULT \'\',
  `admin_id` int(10) NOT NULL DEFAULT \'0\',
  `time` int(10) NOT NULL DEFAULT \'0\',
  `ban_type` varchar(16) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`ip`)
';
