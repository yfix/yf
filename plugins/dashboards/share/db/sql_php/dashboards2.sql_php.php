<?php

return [
    'fields' => [
        'id' => [
            'name' => 'id',
            'type' => 'int',
            'length' => 11,
            'decimals' => null,
            'unsigned' => false,
            'nullable' => false,
            'default' => null,
            'charset' => null,
            'collate' => null,
            'auto_inc' => true,
            'primary' => true,
            'unique' => true,
            'values' => null,
        ],
        'name' => [
            'name' => 'name',
            'type' => 'varchar',
            'length' => 50,
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
        'data' => [
            'name' => 'data',
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
        'type' => [
            'name' => 'type',
            'type' => 'enum',
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
            'values' => [
                'user' => 'user',
                'admin' => 'admin',
            ],
        ],
        'active' => [
            'name' => 'active',
            'type' => 'enum',
            'length' => null,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => '1',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => [
                1 => '1',
                0 => '0',
            ],
        ],
        'lock' => [
            'name' => 'lock',
            'type' => 'enum',
            'length' => null,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => [
                1 => '1',
                0 => '0',
            ],
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
        'id' => [
            'name' => 'id',
            'type' => 'unique',
            'columns' => [
                'id' => 'id',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
