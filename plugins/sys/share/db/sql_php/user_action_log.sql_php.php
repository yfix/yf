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
        'user_id' => [
            'name' => 'user_id',
            'type' => 'int',
            'length' => 10,
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
        'object_id' => [
            'name' => 'object_id',
            'type' => 'int',
            'length' => 10,
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
        'offer_id' => [
            'name' => 'offer_id',
            'type' => 'int',
            'length' => 10,
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
        'amount' => [
            'name' => 'amount',
            'type' => 'decimal',
            'length' => 10,
            'decimals' => '2',
            'unsigned' => false,
            'nullable' => false,
            'default' => null,
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'add_time' => [
            'name' => 'add_time',
            'type' => 'int',
            'length' => 10,
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
        'object' => [
            'name' => 'object',
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
        'action' => [
            'name' => 'action',
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
        'tpl_name' => [
            'name' => 'tpl_name',
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
        'comment' => [
            'name' => 'comment',
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
        'recalculation' => [
            'name' => 'recalculation',
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
    ],
    'indexes' => [
        'PRIMARY' => [
            'name' => 'PRIMARY',
            'type' => 'primary',
            'columns' => [
                'id' => 'id',
            ],
        ],
        'tpl_name' => [
            'name' => 'tpl_name',
            'type' => 'index',
            'columns' => [
                'tpl_name' => 'tpl_name',
            ],
        ],
        'offer_id' => [
            'name' => 'offer_id',
            'type' => 'index',
            'columns' => [
                'offer_id' => 'offer_id',
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
        'engine' => 'InnoDB',
        'charset' => 'utf8',
    ],
];
