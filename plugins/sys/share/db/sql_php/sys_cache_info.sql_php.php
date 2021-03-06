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
        'object' => [
            'name' => 'object',
            'type' => 'varchar',
            'length' => 32,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => '',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => true,
            'values' => null,
        ],
        'action' => [
            'name' => 'action',
            'type' => 'varchar',
            'length' => 32,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => '',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => true,
            'values' => null,
        ],
        'query_string' => [
            'name' => 'query_string',
            'type' => 'varchar',
            'length' => 128,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => '',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => true,
            'values' => null,
        ],
        'site_id' => [
            'name' => 'site_id',
            'type' => 'tinyint',
            'length' => 3,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => true,
            'values' => null,
        ],
        'group_id' => [
            'name' => 'group_id',
            'type' => 'tinyint',
            'length' => 3,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => '1',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'hash' => [
            'name' => 'hash',
            'type' => 'varchar',
            'length' => 32,
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
    ],
    'indexes' => [
        'PRIMARY' => [
            'name' => 'PRIMARY',
            'type' => 'primary',
            'columns' => [
                'id' => 'id',
            ],
        ],
        'object' => [
            'name' => 'object',
            'type' => 'unique',
            'columns' => [
                'object' => 'object',
                'action' => 'action',
                'query_string' => 'query_string',
                'site_id' => 'site_id',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
