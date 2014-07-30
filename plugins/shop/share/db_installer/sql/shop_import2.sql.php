<?php
$data = '
	id   INT  NOT NULL AUTO_INCREMENT,
	pid  INT  NOT NULL DEFAULT 0,
	data TEXT NOT NULL DEFAULT \'\',
	PRIMARY KEY ( `id` ),
	KEY ( `pid` )
';
