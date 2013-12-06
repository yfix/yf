<?php
$data = '
	`c` char(2) NOT NULL default \'\',
	 `n` varchar(64) NOT NULL default \'\',
	 `f` enum(\'0\',\'1\') NOT NULL default \'0\',
	`cont` char(1) NOT NULL default \'\',
	`call_code` char(4) NOT NULL default \'\',
	PRIMARY KEY  (`c`)
	/** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';