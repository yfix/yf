<?php

return [
    'fields' => [
        'id' => [
            'name' => 'id',
            'type' => 'int',
            'length' => 5,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => null,
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => true,
            'unique' => false,
            'values' => null,
        ],
        'lon' => [
            'name' => 'lon',
            'type' => 'float',
            'length' => null,
            'decimals' => null,
            'unsigned' => false,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'lat' => [
            'name' => 'lat',
            'type' => 'float',
            'length' => null,
            'decimals' => null,
            'unsigned' => false,
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
        'lon' => [
            'name' => 'lon',
            'type' => 'index',
            'columns' => [
                'lon' => 'lon',
                'lat' => 'lat',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
