<?php

$data = array(
	'folder_id' => array(
		'tip' => t('Folders allow you to organize your gallery in a more structured way. If you have only a few photos you do not need it, but if you have several sets of photos, it is better to create several folders for them. Splitting your gallery to several subgalleries of 10-20 images each is a good practice.'),
	),
	'photo_file' => array(
		'tip' => t('JPEGs only, up to %max bytes', array('%max' => (int)module('gallery')->MAX_IMAGE_SIZE)),
	),
	'title' => array(
		'tip' => t('Optional field. Although you may leave it blank, photo title will allow your site pages to be better positioned on search engines. Up to %max characters', array('%max' => (int)module('gallery')->MAX_IMAGE_SIZE)),
	),
	'comments' => array(
		'tip' => t('Optional field. You can write a short photo description here. Remember, although you may leave it blank, description texts allow your site pages to be better positioned on search engines.'),
	),
	'tags' => array(
		'tip' => t('Optional field. Tags allow you to further organize your gallery by adding simple one-two word descrtiptions of the content. 
				Remember, although you may leave it blank, tags allow your site pages to be better positioned on search engines. Up to %maxnum tags, from %minlen to %maxlen characters each; one tag per line'
		, array(
			'%maxnum' => (int)module_safe('tags')->TAGS_PER_OBJ, 
			'%minlen' => (int)module_safe('tags')->MIN_KEYWORD_LENGTH, 
			'%maxlen' => (int)module_safe('tags')->MAX_KEYWORD_LENGTH,
		)),
	),
);
