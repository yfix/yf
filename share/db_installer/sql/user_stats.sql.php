<?php
$data = '
	`user_id` int(10) unsigned NOT NULL,
	`group_id` tinyint(3) unsigned NOT NULL,
	`ads` int(10) unsigned NOT NULL,
	`reviews` int(10) unsigned NOT NULL,
	`gallery_photos` int(10) unsigned NOT NULL,
	`blog_posts` int(10) unsigned NOT NULL,
	`que_answers` int(10) unsigned NOT NULL,
	`forum_posts` int(10) unsigned NOT NULL,
	`comments` int(10) unsigned NOT NULL,
	`articles` INT(10) UNSIGNED NOT NULL,
	`interests` INT(10) UNSIGNED NOT NULL,
	`favorite_users` int(10) unsigned NOT NULL,
	`ignored_users` int(10) unsigned NOT NULL,
	`paid_orders` int(10) unsigned NOT NULL,
	`friend_of` int(10) unsigned NOT NULL,
	`friends` int(10) unsigned NOT NULL,
	`nick` varchar(255) NOT NULL,
	`profile_url` varchar(255) NOT NULL,
	PRIMARY KEY	(`user_id`)
';