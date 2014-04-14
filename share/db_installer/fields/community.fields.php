<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 1,
		),
		'owner_id' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'user_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'title' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'character set utf8',
			'auto_inc' => 0,
		),
		'membership' => 
		array (
			'type' => 'enum',
			'length' => '\'open\',\'moderated\',\'closed\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'open',
			'auto_inc' => 0,
		),
		'nonmember_posting' => 
		array (
			'type' => 'enum',
			'length' => '\'0\',\'1\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'postlevel' => 
		array (
			'type' => 'enum',
			'length' => '\'members\',\'select\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'members',
			'auto_inc' => 0,
		),
		'moderated' => 
		array (
			'type' => 'enum',
			'length' => '\'0\',\'1\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'adult' => 
		array (
			'type' => 'enum',
			'length' => '\'none\',\'concepts\',\'explicit\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'none',
			'auto_inc' => 0,
		),
		'about' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'character set utf8',
			'auto_inc' => 0,
		),
		'active' => 
		array (
			'type' => 'enum',
			'length' => '\'0\',\'1\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'id' => 
		array (
			'fields' => 
			array (
				0 => 'id',
			),
			'type' => 'primary',
		),
	),
);
