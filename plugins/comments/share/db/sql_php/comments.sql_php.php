<?php

return [
    'fields' => [
        'id' => [
            'name' => 'id',
            'type' => 'int',
            'length' => 10,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => null,
            'charset' => null,
            'collate' => null,
            'auto_inc' => true,
            'primary' => true,
            'unique' => false,
            'values' => null,
        ],
        'object_name' => [
            'name' => 'object_name',
            'type' => 'varchar',
            'length' => 24,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => null,
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'object_id' => [
            'name' => 'object_id',
            'type' => 'int',
            'length' => 10,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'parent_id' => [
            'name' => 'parent_id',
            'type' => 'int',
            'length' => 10,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'user_id' => [
            'name' => 'user_id',
            'type' => 'int',
            'length' => 10,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'user_name' => [
            'name' => 'user_name',
            'type' => 'varchar',
            'length' => 64,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => '',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'user_email' => [
            'name' => 'user_email',
            'type' => 'varchar',
            'length' => 64,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => '',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'add_date' => [
            'name' => 'add_date',
            'type' => 'int',
            'length' => 10,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'text' => [
            'name' => 'text',
            'type' => 'text',
            'length' => null,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => null,
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'ip' => [
            'name' => 'ip',
            'type' => 'varchar',
            'length' => 15,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => '',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'active' => [
            'name' => 'active',
            'type' => 'tinyint',
            'length' => 1,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'activity' => [
            'name' => 'activity',
            'type' => 'int',
            'length' => 10,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
    ],
    'indexes' => [
        'PRIMARY' => [
            'name' => 'PRIMARY',
            'type' => 'primary',
            'columns' => [
                'id' => 'id',
            ],
        ],
        'object_name' => [
            'name' => 'object_name',
            'type' => 'index',
            'columns' => [
                'object_name' => 'object_name',
            ],
        ],
        'object_id' => [
            'name' => 'object_id',
            'type' => 'index',
            'columns' => [
                'object_id' => 'object_id',
            ],
        ],
        'user_id' => [
            'name' => 'user_id',
            'type' => 'index',
            'columns' => [
                'user_id' => 'user_id',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
