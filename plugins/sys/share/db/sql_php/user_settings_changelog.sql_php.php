<?php

return [
    'fields' => [
        'id' => [
            'name' => 'id',
            'type' => 'int',
            'length' => 11,
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
        'date' => [
            'name' => 'date',
            'type' => 'datetime',
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
        'user_id' => [
            'name' => 'user_id',
            'type' => 'int',
            'length' => 11,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => null,
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'key' => [
            'name' => 'key',
            'type' => 'varchar',
            'length' => 64,
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
        'val_old' => [
            'name' => 'val_old',
            'type' => 'longtext',
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
        'val_new' => [
            'name' => 'val_new',
            'type' => 'longtext',
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
            'length' => 16,
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
        'ua' => [
            'name' => 'ua',
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
    ],
    'indexes' => [
        'PRIMARY' => [
            'name' => 'PRIMARY',
            'type' => 'primary',
            'columns' => [
                'id' => 'id',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
        'engine' => 'InnoDB',
        'charset' => 'utf8',
    ],
];
