<?php

return [
    'fields' => [
        'id' => [
            'name' => 'id',
            'type' => 'int',
            'length' => 10,
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
        'title' => [
            'name' => 'title',
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
        'desc' => [
            'name' => 'desc',
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
        'group_id' => [
            'name' => 'group_id',
            'type' => 'smallint',
            'length' => 3,
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
        'group_name' => [
            'name' => 'group_name',
            'type' => 'varchar',
            'length' => 255,
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
        'type' => [
            'name' => 'type',
            'type' => 'varchar',
            'length' => 255,
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
        'keyword' => [
            'name' => 'keyword',
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
        'value' => [
            'name' => 'value',
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
        'default' => [
            'name' => 'default',
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
        'extra' => [
            'name' => 'extra',
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
        'eval_php' => [
            'name' => 'eval_php',
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
        'position' => [
            'name' => 'position',
            'type' => 'smallint',
            'length' => 3,
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
        'display' => [
            'name' => 'display',
            'type' => 'tinyint',
            'length' => 1,
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
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
