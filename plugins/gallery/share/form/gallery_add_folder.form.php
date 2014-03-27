<?php

$data = array(
	'title' => array(
		'tip' => t('Up to %maxlen characters', array('%maxlen' => (int)module('gallery')->MAX_FOLDER_TITLE_LENGTH)),
	),
	'comment' => array(
		'tip' => t('Optional field. You can write a short description or comment text to this folder. Up to %maxlen characters', array('%maxlen' => (int)module('gallery')->MAX_FOLDER_COMMENT_LENGTH)),
	),
	'privacy' => array(
		'tip' => t('You can restrict assess to the folder. Making it private will allow you to load photos to your gallery, but they will not be published on your site. Nobody will be able to see them. Allowing public access to the folder makes all of the photos visible to all the site visitors.'),
	),
	'allow_comments' => array(
		'tip' => t('Disable comments, if you do not want site visitors to write them'),
	),
	'password' => array(
		'tip' => t('Set the password, if you want to make the folder password protected. Attention! This will make this folder not accessible to most of the site visitors! Do not use protection for open folders! Just leave this field blank if you do not want to restrict access.'),
	),
);
