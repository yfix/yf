<?php
return array(
	'fields' => array(
		'id' => array(
			'name' => 'id',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'sender_id' => array(
			'name' => 'sender_id',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'receiver_id' => array(
			'name' => 'receiver_id',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		's_section' => array(
			'name' => 's_section',
			'type' => 'enum',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => 'sent',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => array(
				'sent' => 'sent',
				'trash' => 'trash',
			),
		),
		'r_section' => array(
			'name' => 'r_section',
			'type' => 'enum',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => 'inbox',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => array(
				'inbox' => 'inbox',
				'trash' => 'trash',
			),
		),
		's_status' => array(
			'name' => 's_status',
			'type' => 'enum',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => 'unread',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => array(
				'read' => 'read',
				'unread' => 'unread',
				'replied' => 'replied',
				'sent' => 'sent',
				'approved' => 'approved',
				'disapproved' => 'disapproved',
				'deleted' => 'deleted',
			),
		),
		'r_status' => array(
			'name' => 'r_status',
			'type' => 'enum',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => 'unread',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => array(
				'read' => 'read',
				'unread' => 'unread',
				'replied' => 'replied',
				'approved' => 'approved',
				'disapproved' => 'disapproved',
				'deleted' => 'deleted',
			),
		),
		'add_date' => array(
			'name' => 'add_date',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'subject' => array(
			'name' => 'subject',
			'type' => 'varchar',
			'length' => '255',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'message' => array(
			'name' => 'message',
			'type' => 'text',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'type' => array(
			'name' => 'type',
			'type' => 'varchar',
			'length' => '20',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => 'standard',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'special_id' => array(
			'name' => 'special_id',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
	),
	'indexes' => array(
		'PRIMARY' => array(
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => array(
				'id' => 'id',
			),
		),
		'receiver_id' => array(
			'name' => 'receiver_id',
			'type' => 'index',
			'columns' => array(
				'receiver_id' => 'receiver_id',
			),
		),
		'sender_id' => array(
			'name' => 'sender_id',
			'type' => 'index',
			'columns' => array(
				'sender_id' => 'sender_id',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
