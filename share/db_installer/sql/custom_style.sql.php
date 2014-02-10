<?php
$data = '
	`id` int(11) NOT NULL,
	`item_id` int(11) NOT NULL default \'0\',
	`page` varchar(20) NOT NULL default \'\',
	`main_text_style` varchar(255) NOT NULL default \'\',
	`link_style` varchar(255) NOT NULL default \'\',
	`link_hover_style` varchar(255) NOT NULL default \'\',
	`back_color` varchar(16) NOT NULL default \'\',
	`background` varchar(10) NOT NULL default \'\',
	`is_scrollback` int(11) NOT NULL default \'0\',
	`is_tiledback` int(11) NOT NULL default \'0\',
	`is_transback` int(11) NOT NULL default \'0\',
	`image_color` varchar(16) NOT NULL default \'\',
	`image_hover_color` varchar(16) NOT NULL default \'\',
	`is_lightimg` int(11) NOT NULL default \'0\',
	`is_fliphorimg` int(11) NOT NULL default \'0\',
	`is_flipvertimg` int(11) NOT NULL default \'0\',
	`th_backcolor` varchar(16) NOT NULL default \'\',
	`th_style` varchar(255) NOT NULL default \'\',
	`table_backcolor` varchar(16) NOT NULL default \'\',
	`table_bordercolor` varchar(16) NOT NULL default \'\',
	PRIMARY KEY	(`id`)
';