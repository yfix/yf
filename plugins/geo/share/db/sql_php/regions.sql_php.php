<?php

return [
    'fields' => [
        'code' => [
            'name' => 'code',
            'type' => 'char',
            'length' => 2,
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
        'country' => [
            'name' => 'country',
            'type' => 'char',
            'length' => 2,
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
        'name' => [
            'name' => 'name',
            'type' => 'varchar',
            'length' => 255,
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
    ],
    'indexes' => [
        'country' => [
            'name' => 'country',
            'type' => 'index',
            'columns' => [
                'country' => 'country',
            ],
        ],
        'code' => [
            'name' => 'code',
            'type' => 'index',
            'columns' => [
                'code' => 'code',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
