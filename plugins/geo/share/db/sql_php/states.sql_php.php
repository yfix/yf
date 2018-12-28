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
            'unique' => false,
            'values' => null,
        ],
        'name' => [
            'name' => 'name',
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
        'code' => [
            'name' => 'code',
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
        'country_code' => [
            'name' => 'country_code',
            'type' => 'char',
            'length' => 2,
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
        'code' => [
            'name' => 'code',
            'type' => 'unique',
            'columns' => [
                'code' => 'code',
            ],
        ],
        'state' => [
            'name' => 'state',
            'type' => 'index',
            'columns' => [
                'name' => 'name',
            ],
        ],
        'country_code' => [
            'name' => 'country_code',
            'type' => 'index',
            'columns' => [
                'country_code' => 'country_code',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
