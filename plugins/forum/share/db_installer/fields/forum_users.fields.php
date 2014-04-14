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
		'status' => 
		array (
			'type' => 'char',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => 'a',
			'auto_inc' => 0,
		),
		'name' => 
		array (
			'type' => 'varchar',
			'length' => '24',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'pswd' => 
		array (
			'type' => 'varchar',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_lastvisit' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'user_regdate' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'group' => 
		array (
			'type' => 'tinyint',
			'length' => '4',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '0',
			'auto_inc' => 0,
		),
		'user_posts' => 
		array (
			'type' => 'mediumint',
			'length' => '8',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'user_timezone' => 
		array (
			'type' => 'float',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'user_dateformat' => 
		array (
			'type' => 'varchar',
			'length' => '14',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'd M Y H:i',
			'auto_inc' => 0,
		),
		'user_rank' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '0',
			'auto_inc' => 0,
		),
		'user_avatar' => 
		array (
			'type' => 'varchar',
			'length' => '100',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_email' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_icq' => 
		array (
			'type' => 'varchar',
			'length' => '15',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_website' => 
		array (
			'type' => 'varchar',
			'length' => '100',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_from' => 
		array (
			'type' => 'varchar',
			'length' => '100',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_sig' => 
		array (
			'type' => 'text',
			'length' => ',',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_aim' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_yim' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_msnm' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_occ' => 
		array (
			'type' => 'varchar',
			'length' => '100',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_interests' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_birth' => 
		array (
			'type' => 'date',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'view_sig' => 
		array (
			'type' => 'tinyint',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '1',
			'auto_inc' => 0,
		),
		'view_images' => 
		array (
			'type' => 'tinyint',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '1',
			'auto_inc' => 0,
		),
		'view_avatars' => 
		array (
			'type' => 'tinyint',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '1',
			'auto_inc' => 0,
		),
		'posts_per_page' => 
		array (
			'type' => 'tinyint',
			'length' => '3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'topics_per_page' => 
		array (
			'type' => 'tinyint',
			'length' => '3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'dst_status' => 
		array (
			'type' => 'tinyint',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'language' => 
		array (
			'type' => 'varchar',
			'length' => '12',
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
