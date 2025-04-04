<?php

require_once __DIR__ . '/db_real_abstract.php';

/**
 */
class class_db_real_utils_mysql_test extends db_real_abstract
{
    public static $is_mysql8 = false;

    public static function _need_skip_test($name)
    {
        return false;
    }
    public static function db_name()
    {
        return self::$DB_NAME;
    }
    public static function table_name($name)
    {
        $prefix = self::$db->DB_PREFIX;
        $plen = strlen($prefix);
        $need_prefix = false;
        if ($plen && strlen($name) && substr($name, 0, $plen) !== $prefix) {
            $need_prefix = true;
        }
        return self::db_name() . '.' . ($need_prefix ? $prefix : '') . $name;
    }
    public static function setUpBeforeClass() : void
    {
        self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
        self::$DB_DRIVER = 'mysqli';
        self::_connect();
        self::$DB_VERSION = self::db()->get_server_version();
        self::$is_mysql8 = version_compare(self::$DB_VERSION, '8.0.0') >= 0;
        if (self::$is_mysql8) {
            self::$CHARSET = "utf8mb3";
        } else {
            self::$CHARSET = "utf8";
        }
        self::$db->query('DROP DATABASE IF EXISTS ' . self::$DB_NAME);
    }
    public static function tearDownAfterClass() : void
    {
        self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
    }

    public function test_fix_table_name()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = 'missing_tbl_' . substr(md5(microtime()), 0, 8);
        $table_prepared = $this->table_name($table);
        $this->assertNotEquals($table, $table_prepared);
        $this->assertEquals(self::$db->DB_PREFIX, self::utils()->db->DB_PREFIX);

        $this->assertEquals(self::$db->DB_PREFIX . $table, self::utils()->db->_fix_table_name($table));
        $this->assertEquals(self::db_name() . '.' . self::$db->DB_PREFIX . $table, self::utils()->db->_fix_table_name($table_prepared));
    }
    public function test_list_collations()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertNotEmpty(self::utils()->list_collations());
    }
    public function test_list_charsets()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertNotEmpty(self::utils()->list_charsets());
    }

    public function test_list_databases()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $all_dbs = self::utils()->list_databases();
        $this->assertIsArray($all_dbs);
        $this->assertNotEmpty($all_dbs);
        $this->assertTrue(in_array('mysql', $all_dbs));
        $this->assertTrue(in_array('information_schema', $all_dbs));
        $this->assertTrue(in_array('performance_schema', $all_dbs));
        $this->assertTrue(in_array('sys', $all_dbs));
    }
    public function test_drop_database()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $all_dbs = self::utils()->list_databases();
        if (in_array($this->db_name(), $all_dbs)) {
            self::utils()->drop_database($this->db_name());
            $all_dbs = self::utils()->list_databases();
        }
        $this->assertFalse(in_array($this->db_name(), $all_dbs));
    }
    public function test_create_database()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $all_dbs = self::utils()->list_databases();
        if (in_array($this->db_name(), $all_dbs)) {
            self::utils()->drop_database($this->db_name());
            $all_dbs = self::utils()->list_databases();
        }
        $this->assertFalse(in_array($this->db_name(), $all_dbs));
        $this->assertNotEmpty(self::utils()->create_database($this->db_name()));
        $all_dbs = self::utils()->list_databases();
        $this->assertTrue(in_array($this->db_name(), $all_dbs));
    }
    public function test_database_exists()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertFalse(self::utils()->database_exists($this->db_name() . 'ggdfgdf'));
        $this->assertNotEmpty(self::utils()->database_exists($this->db_name()));
    }
    public function test_database_info()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $expected = [
            'name' => $this->db_name(),
            'charset' => self::$CHARSET,
            'collate' => self::$CHARSET.'_general_ci',
        ];
        $this->assertNotEmpty(self::utils()->database_info($this->db_name()));
        $this->assertNotEmpty(self::utils()->db->query('ALTER DATABASE ' . $this->db_name() . ' CHARACTER SET "'. self::$CHARSET.'" COLLATE "'. self::$CHARSET.'_general_ci"'));
        $this->assertEquals($expected, self::utils()->database_info($this->db_name()));
    }
    public function test_alter_database()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $expected = [
            'name' => $this->db_name(),
            'charset' => self::$CHARSET,
            'collate' => self::$CHARSET.'_general_ci',
        ];
        $this->assertNotEmpty(self::utils()->database_info($this->db_name()));
        $this->assertNotEmpty(self::utils()->db->query('ALTER DATABASE ' . $this->db_name() . ' CHARACTER SET "latin1" COLLATE "latin1_general_ci"'));
        $this->assertNotEquals($expected, self::utils()->database_info($this->db_name()));
        $this->assertNotEmpty(self::utils()->alter_database($this->db_name(), ['charset' => self::$CHARSET, 'collate' => self::$CHARSET.'_general_ci']));
        $this->assertEquals($expected, self::utils()->database_info($this->db_name()));
    }
    public function test_rename_database()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $NEW_DB_NAME = $this->db_name() . '_new';
        $this->assertNotEmpty(self::utils()->database_exists($this->db_name()));
        $this->assertFalse(self::utils()->database_exists($NEW_DB_NAME));
        $this->assertNotEmpty(self::utils()->rename_database($this->db_name(), $NEW_DB_NAME));
        $this->assertFalse(self::utils()->database_exists($this->db_name()));
        $this->assertNotEmpty(self::utils()->database_exists($NEW_DB_NAME));
        $this->assertNotEmpty(self::utils()->rename_database($NEW_DB_NAME, $this->db_name()));
        $this->assertNotEmpty(self::utils()->database_exists($this->db_name()));
        $this->assertFalse(self::utils()->database_exists($NEW_DB_NAME));
    }
    public function test_truncate_database()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $this->assertFalse(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->db->query('CREATE TABLE ' . $this->table_name($table) . '(id INT(10))'));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->truncate_database($this->db_name()));
        $this->assertFalse(self::utils()->table_exists($this->table_name($table)));
        $this->assertEquals([], self::utils()->list_tables($this->db_name()));
    }

    public function test_list_tables()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertTrue((bool) self::utils()->create_database($this->db_name()));

        $this->assertEquals([], self::utils()->list_tables($this->db_name()));
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $this->assertTrue((bool) self::utils()->db->query('CREATE TABLE ' . $this->table_name($table) . '(id INT(10))'));
        $this->assertEquals([$table => $table], self::utils()->list_tables($this->db_name()));
        $this->assertNotEmpty(self::utils()->db->query('DROP TABLE ' . $this->table_name($table) . ''));
        $this->assertEquals([], self::utils()->list_tables($this->db_name()));
    }
    public function test_table_exists()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $this->assertFalse(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->db->query('CREATE TABLE ' . $this->table_name($table) . '(id INT(10))'));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->db->query('DROP TABLE ' . $this->table_name($table) . ''));
        $this->assertFalse(self::utils()->table_exists($this->table_name($table)));
    }
    public function test_drop_table()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $this->assertFalse(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->db->query('CREATE TABLE ' . $this->table_name($table) . ' (id INT(10))'));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->drop_table($this->table_name($table)));
        $this->assertFalse(self::utils()->table_exists($this->table_name($table)));
    }
    public function test_table_get_columns()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertNotEmpty(self::utils()->create_database($this->db_name()));

        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10, 'unsigned' => true, 'nullable' => false, 'auto_inc' => true],
                'name' => ['name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'nullable' => false],
                'active' => ['name' => 'active', 'type' => 'enum', 'values' => [1 => '1', 0 => '0'], 'default' => '0', 'nullable' => false],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']],
            ],
        ];
        if (self::$is_mysql8) {
            unset($data['fields']['id']['length']);
        }
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $result = self::utils()->table_get_columns($this->table_name($table));
        $this->assertEquals($data['fields'], $this->_cleanup_columns_info($result));
    }
    public function test_table_info()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10, 'unsigned' => true, 'nullable' => false, 'auto_inc' => true],
                'name' => ['name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'nullable' => false],
                'active' => ['name' => 'active', 'type' => 'enum', 'values' => [1 => '1', 0 => '0'], 'default' => '0', 'nullable' => false],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']],
            ],
        ];
        if (self::$is_mysql8) {
            unset($data['fields']['id']['length']);
        }
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $expected = [
            'name' => $table,
            'db_name' => $this->db_name(),
            'columns' => $data['fields'],
            'collate' => self::$CHARSET.'_general_ci',
            'engine' => 'InnoDB',
            'rows' => '0',
            'data_size' => '16384',
            'auto_inc' => self::$is_mysql8 ? null : '1',
            'comment' => '',
            'create_time' => '2014-01-01 01:01:01',
            // 'update_time' => null,
            'charset' => self::$is_mysql8 ? null : self::$CHARSET,
        ];
        $received = self::utils()->table_info($this->table_name($table));
        if ($received) {
            $received['columns'] = $this->_cleanup_columns_info($received['columns']);
            $received['create_time'] = '2014-01-01 01:01:01';
            unset($received['row_format']);
            unset($received['update_time']);
        }
        $this->assertEquals($expected, $received);
    }
    public function test_rename_table()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $new_table = $table . '_new';
        $this->assertNotEmpty(self::utils()->rename_table($this->table_name($table), $this->db_name() . '.' . $new_table));
        $this->assertFalse(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->table_exists($this->db_name() . '.' . $new_table));
    }
    public function test_truncate_table()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $to_insert = [
            1 => ['id' => 1],
            2 => ['id' => 2],
        ];
        $this->assertNotEmpty(self::utils()->db->insert($this->table_name($table), $to_insert));
        $this->assertEquals($to_insert, self::utils()->db->from($this->table_name($table))->get_all());
        $this->assertNotEmpty(self::utils()->truncate_table($this->table_name($table)));
    }
    public function test_check_table()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = current(self::utils()->list_tables($this->db_name()));
        $this->assertNotEmpty(self::utils()->check_table($this->table_name($table)));
    }
    public function test_optimize_table()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = current(self::utils()->list_tables($this->db_name()));
        $this->assertNotEmpty(self::utils()->optimize_table($this->table_name($table)));
    }
    public function test_repair_table()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = current(self::utils()->list_tables($this->db_name()));
        $this->assertNotEmpty(self::utils()->repair_table($this->table_name($table)));
    }
    public function test_alter_table()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $old_info = self::utils()->table_info($this->table_name($table));
        $this->assertEquals('InnoDB', $old_info['engine']);
        $this->assertNotEmpty(self::utils()->alter_table($this->table_name($table), ['engine' => 'ARCHIVE']));
        $new_info = self::utils()->table_info($this->table_name($table));
        $this->assertEquals('ARCHIVE', $new_info['engine']);
    }
    public function test_create_table()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertNotEmpty(self::utils()->create_database($this->db_name()));
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertTrue((bool) self::utils()->create_table($this->table_name($table), $data));
        $this->assertTrue((bool) self::utils()->table_exists($this->table_name($table)));

        $this->assertTrue((bool) self::utils()->drop_table($this->table_name($table)));
        $this->assertFalse((bool) self::utils()->table_exists($this->table_name($table)));

        $this->assertTrue((bool) self::utils()->create_table($this->table_name($table), function ($t) {
            $t->int('id', 10);
        }));
        $this->assertTrue((bool) self::utils()->table_exists($this->table_name($table)));
    }
    public function test_create_table_complex()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertNotEmpty(self::utils()->create_database($this->db_name()));
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $this->assertTrue((bool) self::utils()->drop_table($this->table_name($table)));
        $this->assertFalse((bool) self::utils()->table_exists($this->table_name($table)));

        $this->assertTrue((bool) self::utils()->create_table($this->table_name($table), function ($t) {
            $t->small_int('actor_id', ['length' => 5, 'unsigned' => true, 'nullable' => false, 'auto_inc' => true])
            ->string('first_name', ['length' => 45, 'nullable' => false])
            ->string('last_name', ['length' => 45, 'nullable' => false])
            ->primary('actor_id')
            ->index('last_name', 'idx_actor_last_name')
            ->option('engine', 'InnoDB')
            ->option('charset', 'utf8');
        }));

        $this->assertTrue((bool) self::utils()->table_exists($this->table_name($table)));
    }
    public function test__parse_column_type()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        // @TODO
        // $this->assertEquals(['type' => 'int', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('int'));
        // $this->assertEquals(['type' => 'int', 'length' => 8, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('int(8)'));
        // $this->assertEquals(['type' => 'tinyint', 'length' => 11, 'unsigned' => true, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('tinyint(11) unsigned'));
        // $this->assertEquals(['type' => 'integer', 'length' => 8, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('integer(8)'));
        // $this->assertEquals(['type' => 'bit', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('bit'));
        // $this->assertEquals(['type' => 'decimal', 'length' => 6, 'unsigned' => false, 'decimals' => 2, 'values' => null], self::utils()->_parse_column_type('decimal(6,2)'));
        // $this->assertEquals(['type' => 'decimal', 'length' => 6, 'unsigned' => true, 'decimals' => 2, 'values' => null], self::utils()->_parse_column_type('decimal(6,2) unsigned'));
        // $this->assertEquals(['type' => 'numeric', 'length' => 6, 'unsigned' => true, 'decimals' => 2, 'values' => null], self::utils()->_parse_column_type('numeric(6,2) unsigned'));
        // $this->assertEquals(['type' => 'real', 'length' => 6, 'unsigned' => true, 'decimals' => 2, 'values' => null], self::utils()->_parse_column_type('real(6,2) unsigned'));
        // $this->assertEquals(['type' => 'float', 'length' => 6, 'unsigned' => true, 'decimals' => 2, 'values' => null], self::utils()->_parse_column_type('float(6,2) unsigned'));
        // $this->assertEquals(['type' => 'double', 'length' => 6, 'unsigned' => true, 'decimals' => 2, 'values' => null], self::utils()->_parse_column_type('double(6,2) unsigned'));
        // $this->assertEquals(['type' => 'char', 'length' => 6, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('char(6)'));
        // $this->assertEquals(['type' => 'varchar', 'length' => 256, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('varchar(256)'));
        // $this->assertEquals(['type' => 'tinytext', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('tinytext'));
        // $this->assertEquals(['type' => 'mediumtext', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('mediumtext'));
        // $this->assertEquals(['type' => 'longtext', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('longtext'));
        // $this->assertEquals(['type' => 'text', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('text'));
        // $this->assertEquals(['type' => 'tinyblob', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('tinyblob'));
        // $this->assertEquals(['type' => 'mediumblob', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('mediumblob'));
        // $this->assertEquals(['type' => 'longblob', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('longblob'));
        // $this->assertEquals(['type' => 'blob', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('blob'));
        // $this->assertEquals(['type' => 'binary', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('binary'));
        // $this->assertEquals(['type' => 'varbinary', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('varbinary'));
        // $this->assertEquals(['type' => 'timestamp', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('timestamp'));
        // $this->assertEquals(['type' => 'datetime', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('datetime'));
        // $this->assertEquals(['type' => 'date', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('date'));
        // $this->assertEquals(['type' => 'time', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('time'));
        // $this->assertEquals(['type' => 'year', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => null], self::utils()->_parse_column_type('year'));
        // $this->assertEquals(['type' => 'enum', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => ['0', '1']], self::utils()->_parse_column_type('enum(\'0\',\'1\')'));
        // $this->assertEquals(['type' => 'set', 'length' => null, 'unsigned' => false, 'decimals' => null, 'values' => ['0', '1']], self::utils()->_parse_column_type('set(\'0\',\'1\')'));
    }
    public function test_column_exists()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->column_exists($this->table_name($table), 'id'));
        $this->assertFalse(self::utils()->column_exists($this->table_name($table), 'id33'));
    }
    public function test_column_info()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $result = self::utils()->column_info($this->table_name($table), 'id');
        foreach (['name', 'type', 'length'] as $f) {
            if (self::$is_mysql8 && $f == "length") {
                continue;
            }
            $this->assertEquals($data['fields']['id'][$f], $result[$f]);
        }
    }
    public function test_add_column()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $col_info = ['name' => 'id', 'type' => 'int', 'length' => 10];
        $col_info2 = ['name' => 'id2', 'type' => 'int', 'length' => 8];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), ['fields' => ['id' => $col_info]]));
        $this->assertNotEmpty(self::utils()->column_exists($this->table_name($table), 'id'));
        $this->assertFalse(self::utils()->column_exists($this->table_name($table), 'id2'));
        $this->assertFalse(self::utils()->column_info($this->table_name($table), 'id2'));
        $this->assertNotEmpty(self::utils()->add_column($this->table_name($table), $col_info2));
        $result = self::utils()->column_info($this->table_name($table), 'id2');
        foreach (['name', 'type', 'length'] as $f) {
            if (self::$is_mysql8 && $f == "length") {
                continue;
            }
            $this->assertEquals($col_info2[$f], $result[$f]);
        }
    }
    public function test_drop_column()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => [
            'id' => ['name' => 'id', 'type' => 'int', 'length' => 10],
            'id2' => ['name' => 'id2', 'type' => 'int', 'length' => 10],
        ]];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->column_exists($this->table_name($table), 'id'));
        $this->assertNotEmpty(self::utils()->column_exists($this->table_name($table), 'id2'));
        $this->assertNotEmpty(self::utils()->drop_column($this->table_name($table), 'id2'));
        $this->assertFalse(self::utils()->column_exists($this->table_name($table), 'id2'));
    }
    public function test_rename_column()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->column_exists($this->table_name($table), 'id'));
        $this->assertFalse(self::utils()->column_exists($this->table_name($table), 'id2'));
        $this->assertNotEmpty(self::utils()->rename_column($this->table_name($table), 'id', 'id2'));
        $this->assertFalse(self::utils()->column_exists($this->table_name($table), 'id'));
        $this->assertNotEmpty(self::utils()->column_exists($this->table_name($table), 'id2'));
    }
    public function test_alter_column()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => [
            'id' => ['name' => 'id', 'type' => 'int', 'length' => 10],
            'id2' => ['name' => 'id2', 'type' => 'int', 'length' => 10],
        ]];
        if (self::$is_mysql8) {
            unset($data['fields']['id']['length']);
            unset($data['fields']['id2']['length']);
        }
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->column_exists($this->table_name($table), 'id'));

        $this->assertEquals(['id', 'id2'], array_keys(self::utils()->table_get_columns($this->table_name($table))));
        $this->assertNotEmpty(self::utils()->alter_column($this->table_name($table), 'id2', ['first' => true]));
        $this->assertEquals(['id2', 'id'], array_keys(self::utils()->table_get_columns($this->table_name($table))));
        $this->assertNotEmpty(self::utils()->alter_column($this->table_name($table), 'id2', ['after' => 'id']));
        $this->assertEquals(['id', 'id2'], array_keys(self::utils()->table_get_columns($this->table_name($table))));
    }

    public function test_list_indexes()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertNotEmpty(self::utils()->create_database($this->db_name()));
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10, 'unsigned' => true, 'nullable' => false, 'auto_inc' => true],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']],
            ],
        ];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertEquals($data['indexes'], self::utils()->list_indexes($this->table_name($table)));
    }
    public function test_index_info()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10, 'unsigned' => true, 'nullable' => false, 'auto_inc' => true],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']],
            ],
        ];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertEquals($data['indexes']['PRIMARY'], self::utils()->index_info($this->table_name($table), 'PRIMARY'));
    }
    public function test_index_exists()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10, 'unsigned' => true, 'nullable' => false, 'auto_inc' => true],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']],
            ],
        ];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->index_exists($this->table_name($table), 'PRIMARY'));
    }
    public function test_drop_index()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']],
            ],
        ];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->index_exists($this->table_name($table), 'PRIMARY'));
        $this->assertNotEmpty(self::utils()->drop_index($this->table_name($table), 'PRIMARY'));
        $this->assertFalse(self::utils()->index_exists($this->table_name($table), 'PRIMARY'));
    }
    public function test_add_index()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10],
            ],
        ];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertFalse(self::utils()->index_exists($this->table_name($table), 'PRIMARY'));
        $this->assertNotEmpty(self::utils()->add_index($this->table_name($table), 'PRIMARY', ['id'], ['type' => 'primary']));
        $this->assertNotEmpty(self::utils()->index_exists($this->table_name($table), 'PRIMARY'));

        $this->assertNotEmpty(self::utils()->drop_index($this->table_name($table), 'PRIMARY'));
        $this->assertFalse(self::utils()->index_exists($this->table_name($table), 'PRIMARY'));
        $this->assertNotEmpty(self::utils()->add_index($this->table_name($table), ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']]));
        $this->assertNotEmpty(self::utils()->index_exists($this->table_name($table), 'PRIMARY'));
    }
    public function test_update_index()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10],
                'id2' => ['name' => 'id2', 'type' => 'int', 'length' => 10],
            ],
        ];
        $index = ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->add_index($this->table_name($table), $index));
        $this->assertEquals($index, self::utils()->index_info($this->table_name($table), 'PRIMARY'));
        $index['columns'] = ['id2' => 'id2'];
        $this->assertNotEmpty(self::utils()->update_index($this->table_name($table), $index));
        $this->assertEquals($index, self::utils()->index_info($this->table_name($table), 'PRIMARY'));
    }

    public function test_list_foreign_keys()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertNotEmpty(self::utils()->create_database($this->db_name()));

        $table1 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_1';
        $table2 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_2';
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']],
            ],
        ];
        if (self::$is_mysql8) {
            unset($data['fields']['id']['length']);
        }
        $fkey = 'fkey_' . __FUNCTION__;
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table1), $data));
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table2), $data));
        $this->assertEmpty(self::utils()->list_foreign_keys($this->table_name($table1)));
        $this->assertNotEmpty(self::utils()->add_foreign_key($this->table_name($table1), $fkey, ['id'], $this->table_name($table2), ['id']));
        $def_action = 'RESTRICT';
        if (self::$is_mysql8) {
            $def_action = 'NO ACTION';
        }
        $expected = [
            $fkey => ['name' => $fkey, 'columns' => ['id' => 'id'], 'ref_table' => $table2, 'ref_columns' => ['id' => 'id'], 'on_update' => $def_action, 'on_delete' => $def_action],
        ];
        $this->assertEquals($expected, self::utils()->list_foreign_keys($this->table_name($table1)));
    }
    public function test_foreign_key_info()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table1 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_1';
        $table2 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_2';
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']],
            ],
        ];
        $fkey = 'fkey_' . __FUNCTION__;
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table1), $data));
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table2), $data));
        $this->assertEmpty(self::utils()->foreign_key_info($this->table_name($table1), $fkey));
        $this->assertNotEmpty(self::utils()->add_foreign_key($this->table_name($table1), $fkey, ['id'], $this->table_name($table2), ['id']));
        $def_action = 'RESTRICT';
        if (self::$is_mysql8) {
            $def_action = 'NO ACTION';
        }
        $expected = ['name' => $fkey, 'columns' => ['id' => 'id'], 'ref_table' => $table2, 'ref_columns' => ['id' => 'id'], 'on_update' => $def_action, 'on_delete' => $def_action];
        $this->assertEquals($expected, self::utils()->foreign_key_info($this->table_name($table1), $fkey));
    }
    public function test_foreign_key_exists()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table1 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_1';
        $table2 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_2';
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']],
            ],
        ];
        $fkey = 'fkey_' . __FUNCTION__;
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table1), $data));
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table2), $data));
        $this->assertFalse(self::utils()->foreign_key_exists($this->table_name($table1), $fkey));
        $this->assertNotEmpty(self::utils()->add_foreign_key($this->table_name($table1), $fkey, ['id'], $this->table_name($table2), ['id']));
        $this->assertNotEmpty(self::utils()->foreign_key_exists($this->table_name($table1), $fkey));
    }
    public function test_drop_foreign_key()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table1 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_1';
        $table2 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_2';
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']],
            ],
        ];
        $fkey = 'fkey_' . __FUNCTION__;
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table1), $data));
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table2), $data));
        $this->assertFalse(self::utils()->foreign_key_exists($this->table_name($table1), $fkey));
        $this->assertNotEmpty(self::utils()->add_foreign_key($this->table_name($table1), $fkey, ['id'], $this->table_name($table2), ['id']));
        $this->assertNotEmpty(self::utils()->foreign_key_exists($this->table_name($table1), $fkey));
        $this->assertNotEmpty(self::utils()->drop_foreign_key($this->table_name($table1), $fkey));
        $this->assertFalse(self::utils()->foreign_key_exists($this->table_name($table1), $fkey));
    }
    public function test_add_foreign_key()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        self::utils()->db->db->select_db($this->db_name());
        $table1 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_1';
        $table2 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_2';
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']],
            ],
        ];
        $fkey = 'fkey_' . __FUNCTION__;
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table1), $data));
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table2), $data));
        $this->assertEmpty(self::utils()->foreign_key_info($this->table_name($table1), $fkey));
        $def_action = 'RESTRICT';
        if (self::$is_mysql8) {
            $def_action = 'NO ACTION';
        }
        $expected = [
            'name' => $fkey,
            'columns' => ['id' => 'id'],
            'ref_table' => $table2,
            'ref_columns' => ['id' => 'id'],
            'on_update' => $def_action,
            'on_delete' => $def_action,
        ];
        $this->assertNotEmpty(self::utils()->add_foreign_key($this->table_name($table1), $fkey, ['id'], $this->table_name($table2), ['id']));
        $this->assertEquals($expected, self::utils()->foreign_key_info($this->table_name($table1), $fkey));

        $this->assertNotEmpty(self::utils()->drop_foreign_key($this->table_name($table1), $fkey));
        $this->assertFalse(self::utils()->foreign_key_exists($this->table_name($table1), $fkey));
        $this->assertNotEmpty(self::utils()->add_foreign_key($this->table_name($table1), $expected));
        $this->assertEquals($expected, self::utils()->foreign_key_info($this->table_name($table1), $fkey));
    }
    public function test_update_foreign_key()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table1 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_1';
        $table2 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_2';
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10],
                'id2' => ['name' => 'id2', 'type' => 'int', 'length' => 10],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']],
                'unique' => ['name' => 'unique', 'type' => 'unique', 'columns' => ['id2' => 'id2']],
            ],
        ];
        $fkey = 'fkey_' . __FUNCTION__;
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table1), $data));
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table2), $data));
        $def_action = 'RESTRICT';
        if (self::$is_mysql8) {
            $def_action = 'NO ACTION';
        }
        $expected1 = [
            'name' => $fkey,
            'columns' => ['id' => 'id'],
            'ref_table' => $table2,
            'ref_columns' => ['id' => 'id'],
            'on_update' => $def_action,
            'on_delete' => $def_action,
        ];
        $expected2 = [
            'name' => $fkey,
            'columns' => ['id2' => 'id2'],
            'ref_table' => $table2,
            'ref_columns' => ['id2' => 'id2'],
            'on_update' => $def_action,
            'on_delete' => $def_action,
        ];
        $this->assertNotEmpty(self::utils()->add_foreign_key($this->table_name($table1), $expected1));
        $this->assertEquals($expected1, self::utils()->foreign_key_info($this->table_name($table1), $fkey));
        $this->assertNotEmpty(self::utils()->update_foreign_key($this->table_name($table1), $fkey, ['id2'], $this->table_name($table2), ['id2']));
        $this->assertEquals($expected2, self::utils()->foreign_key_info($this->table_name($table1), $fkey));

        $this->assertNotEmpty(self::utils()->drop_foreign_key($this->table_name($table1), $fkey));
        $this->assertFalse(self::utils()->foreign_key_exists($this->table_name($table1), $fkey));
        $this->assertNotEmpty(self::utils()->add_foreign_key($this->table_name($table1), $expected1));
        $this->assertEquals($expected1, self::utils()->foreign_key_info($this->table_name($table1), $fkey));
        $this->assertNotEmpty(self::utils()->update_foreign_key($this->table_name($table1), $expected2));
        $this->assertEquals($expected2, self::utils()->foreign_key_info($this->table_name($table1), $fkey));
    }

    public function test_list_views()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertNotEmpty(self::utils()->create_database($this->db_name()));

        $view = self::utils()->db->DB_PREFIX . 'view_' . __FUNCTION__;
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertEmpty(self::utils()->list_views($this->db_name()));
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->create_view($this->db_name() . '.' . $view, 'SELECT * FROM ' . $this->table_name($table)));
        $this->assertNotEmpty(self::utils()->list_views($this->db_name()));
    }
    public function test_view_exists()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $view = self::utils()->db->DB_PREFIX . 'view_' . __FUNCTION__;
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertFalse(self::utils()->view_exists($this->db_name() . '.' . $view));
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->create_view($this->db_name() . '.' . $view, 'SELECT * FROM ' . $this->table_name($table)));
        $this->assertNotEmpty(self::utils()->view_exists($this->db_name() . '.' . $view));
    }
    public function test_view_info()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $view = self::utils()->db->DB_PREFIX . 'view_' . __FUNCTION__;
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertFalse(self::utils()->view_exists($this->db_name() . '.' . $view));
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->create_view($this->db_name() . '.' . $view, 'SELECT * FROM ' . $this->table_name($table)));
        $this->assertNotEmpty(self::utils()->view_info($this->db_name() . '.' . $view));
    }
    public function test_drop_view()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $view = self::utils()->db->DB_PREFIX . 'view_' . __FUNCTION__;
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertFalse(self::utils()->view_exists($this->db_name() . '.' . $view));
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->create_view($this->db_name() . '.' . $view, 'SELECT * FROM ' . $this->table_name($table)));
        $this->assertNotEmpty(self::utils()->view_exists($this->db_name() . '.' . $view));
        $this->assertNotEmpty(self::utils()->drop_view($this->db_name() . '.' . $view));
        $this->assertFalse(self::utils()->view_exists($this->db_name() . '.' . $view));
    }
    public function test_create_view()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $view = self::utils()->db->DB_PREFIX . 'view_' . __FUNCTION__;
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertFalse(self::utils()->view_exists($this->db_name() . '.' . $view));
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $this->assertNotEmpty(self::utils()->table_exists($this->table_name($table)));
        $this->assertNotEmpty(self::utils()->create_view($this->db_name() . '.' . $view, 'SELECT * FROM ' . $this->table_name($table)));
        $this->assertNotEmpty(self::utils()->view_exists($this->db_name() . '.' . $view));
    }

    public function test_list_procedures()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertNotEmpty(self::utils()->create_database($this->db_name()));

        $proc = self::utils()->db->DB_PREFIX . 'proc_' . __FUNCTION__;
        $procedures = self::utils()->list_procedures();
        if (empty($procedures)) {
            $sql = 'CREATE PROCEDURE ' . $this->db_name() . '.' . $proc . ' (OUT param1 INT) BEGIN SELECT COUNT(*) INTO param1 FROM t; END';
            $this->assertNotEmpty(self::utils()->db->query($sql));
        }
        $this->assertNotEmpty(self::utils()->list_procedures());
    }
    public function test_procedure_exists()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $proc = self::utils()->db->DB_PREFIX . 'proc_' . __FUNCTION__;
        $this->assertFalse(self::utils()->procedure_exists($this->db_name() . '.' . $proc));
        $sql = 'CREATE PROCEDURE ' . $this->db_name() . '.' . $proc . ' (OUT param1 INT) BEGIN SELECT COUNT(*) INTO param1 FROM t; END';
        $this->assertNotEmpty(self::utils()->db->query($sql));
        $this->assertNotEmpty(self::utils()->procedure_exists($this->db_name() . '.' . $proc));
    }
    public function test_procedure_info()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $proc = self::utils()->db->DB_PREFIX . 'proc_' . __FUNCTION__;
        $this->assertFalse(self::utils()->procedure_exists($this->db_name() . '.' . $proc));
        $sql = 'CREATE PROCEDURE ' . $this->db_name() . '.' . $proc . ' (OUT param1 INT) BEGIN SELECT COUNT(*) INTO param1 FROM t; END';
        $this->assertNotEmpty(self::utils()->db->query($sql));
        $this->assertNotEmpty(self::utils()->procedure_exists($this->db_name() . '.' . $proc));
        $expected = [
            'db' => $this->db_name(),
            'name' => $proc,
            'type' => 'PROCEDURE',
            'security_type' => 'DEFINER',
            'comment' => '',
        ];
        $result = self::utils()->procedure_info($this->db_name() . '.' . $proc);
        foreach ($expected as $k => $_expected) {
            $this->assertEquals($_expected, $result[$k]);
        }
    }
    public function test_drop_procedure()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $proc = self::utils()->db->DB_PREFIX . 'proc_' . __FUNCTION__;
        $this->assertFalse(self::utils()->procedure_exists($this->db_name() . '.' . $proc));
        $sql = 'CREATE PROCEDURE ' . $this->db_name() . '.' . $proc . ' (OUT param1 INT) BEGIN SELECT COUNT(*) INTO param1 FROM t; END';
        $this->assertNotEmpty(self::utils()->db->query($sql));
        $this->assertNotEmpty(self::utils()->procedure_exists($this->db_name() . '.' . $proc));
        $this->assertNotEmpty(self::utils()->drop_procedure($this->db_name() . '.' . $proc));
        $this->assertFalse(self::utils()->procedure_exists($this->db_name() . '.' . $proc));
    }
    public function test_create_procedure()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $proc = self::utils()->db->DB_PREFIX . 'proc_' . __FUNCTION__;
        $this->assertFalse(self::utils()->procedure_exists($this->db_name() . '.' . $proc));
        $this->assertNotEmpty(self::utils()->create_procedure($this->db_name() . '.' . $proc, 'SELECT COUNT(*) INTO param1 FROM t;', 'OUT param1 INT'));
        $this->assertNotEmpty(self::utils()->procedure_exists($this->db_name() . '.' . $proc));
        $expected = [
            'db' => $this->db_name(),
            'name' => $proc,
            'type' => 'PROCEDURE',
            'security_type' => 'DEFINER',
            'comment' => '',
        ];
        $result = self::utils()->procedure_info($this->db_name() . '.' . $proc);
        foreach ($expected as $k => $_expected) {
            $this->assertEquals($_expected, $result[$k]);
        }
    }

    public function test_list_functions()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertNotEmpty(self::utils()->create_database($this->db_name()));

        $func = self::utils()->db->DB_PREFIX . 'func_' . __FUNCTION__;
        $funcs = self::utils()->list_functions();
        if (empty($funcs)) {
            $sql = 'CREATE FUNCTION ' . $this->db_name() . '.' . $func . ' (s CHAR(20)) RETURNS CHAR(50) DETERMINISTIC RETURN CONCAT("Hello, ",s,"!");';
            $this->assertNotEmpty(self::utils()->db->query($sql));
        }
        $this->assertNotEmpty(self::utils()->list_functions());
    }
    public function test_function_exists()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $func = self::utils()->db->DB_PREFIX . 'func_' . __FUNCTION__;
        $this->assertFalse(self::utils()->function_exists($func));
        $sql = 'CREATE FUNCTION ' . $this->db_name() . '.' . $func . ' (s CHAR(20)) RETURNS CHAR(50) DETERMINISTIC RETURN CONCAT("Hello, ",s,"!");';
        $this->assertNotEmpty(self::utils()->db->query($sql));
        $this->assertNotEmpty(self::utils()->function_exists($func));
    }
    public function test_function_info()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $func = self::utils()->db->DB_PREFIX . 'func_' . __FUNCTION__;
        $this->assertFalse(self::utils()->function_exists($this->db_name() . '.' . $func));
        $sql = 'CREATE FUNCTION ' . $this->db_name() . '.' . $func . ' (s CHAR(20)) RETURNS CHAR(50) DETERMINISTIC RETURN CONCAT("Hello, ",s,"!");';
        $this->assertNotEmpty(self::utils()->db->query($sql));
        $expected = [
            'db' => $this->db_name(),
            'name' => $func,
            'type' => 'FUNCTION',
            'comment' => '',
        ];
        $result = self::utils()->function_info($this->db_name() . '.' . $func);
        foreach ($expected as $k => $_expected) {
            $this->assertEquals($_expected, $result[$k]);
        }
    }
    public function test_drop_function()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $func = self::utils()->db->DB_PREFIX . 'func_' . __FUNCTION__;
        $this->assertFalse(self::utils()->function_exists($func));
        $sql = 'CREATE FUNCTION ' . $this->db_name() . '.' . $func . ' (s CHAR(20)) RETURNS CHAR(50) DETERMINISTIC RETURN CONCAT("Hello, ",s,"!");';
        $this->assertNotEmpty(self::utils()->db->query($sql));
        $this->assertNotEmpty(self::utils()->function_exists($func));
        $this->assertNotEmpty(self::utils()->drop_function($func));
        $this->assertFalse(self::utils()->function_exists($func));
    }
    public function test_create_function()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $func = self::utils()->db->DB_PREFIX . 'func_' . __FUNCTION__;
        $this->assertFalse(self::utils()->function_exists($func));
        $this->assertNotEmpty(self::utils()->create_function($this->db_name() . '.' . $func, 'CONCAT("Hello, ",s,"!")', 'CHAR(50)', 's CHAR(20)'));
        $this->assertNotEmpty(self::utils()->function_exists($func));
        $expected = [
            'db' => $this->db_name(),
            'name' => $func,
            'type' => 'FUNCTION',
            'comment' => '',
        ];
        $result = self::utils()->function_info($func);
        foreach ($expected as $k => $_expected) {
            $this->assertEquals($_expected, $result[$k]);
        }
    }

    public function test_list_triggers()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertNotEmpty(self::utils()->create_database($this->db_name()));

        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));

        $trg = self::utils()->db->DB_PREFIX . 'trg_' . __FUNCTION__;
        $triggers = self::utils()->list_triggers($this->db_name());
        if (empty($triggers)) {
            $sql = 'CREATE TRIGGER ' . $this->db_name() . '.' . $trg . ' BEFORE INSERT ON ' . $this->table_name($table) . ' FOR EACH ROW SET @sum = @sum + NEW.id';
            $this->assertNotEmpty(self::utils()->db->query($sql));
        }
        $this->assertNotEmpty(self::utils()->list_triggers($this->db_name()));
    }
    public function test_trigger_exists()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $trg = self::utils()->db->DB_PREFIX . 'trg_' . __FUNCTION__;
        $this->assertFalse(self::utils()->trigger_exists($this->db_name() . '.' . $trg));
        $sql = 'CREATE TRIGGER ' . $this->db_name() . '.' . $trg . ' BEFORE INSERT ON ' . $this->table_name($table) . ' FOR EACH ROW SET @sum = @sum + NEW.id';
        $this->assertNotEmpty(self::utils()->db->query($sql));
        $this->assertNotEmpty(self::utils()->trigger_exists($this->db_name() . '.' . $trg));
    }
    public function test_trigger_info()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $trg = self::utils()->db->DB_PREFIX . 'trg_' . __FUNCTION__;
        $sql = 'CREATE TRIGGER ' . $this->db_name() . '.' . $trg . ' BEFORE INSERT ON ' . $this->table_name($table) . ' FOR EACH ROW SET @sum = @sum + NEW.id';
        $this->assertNotEmpty(self::utils()->db->query($sql));
        $expected = [
            'name' => $trg,
            'table' => $table,
            'event' => 'INSERT',
            'timing' => 'BEFORE',
            'statement' => 'SET @sum = @sum + NEW.id',
            'definer' => null,
        ];
        $result = self::utils()->trigger_info($this->db_name() . '.' . $trg);
        foreach ($expected as $k => $_expected) {
            $this->assertEquals($_expected, $result[$k]);
        }
    }
    public function test_drop_trigger()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $trg = self::utils()->db->DB_PREFIX . 'trg_' . __FUNCTION__;
        $this->assertFalse(self::utils()->trigger_exists($this->db_name() . '.' . $trg));
        $sql = 'CREATE TRIGGER ' . $this->db_name() . '.' . $trg . ' BEFORE INSERT ON ' . $this->table_name($table) . ' FOR EACH ROW SET @sum = @sum + NEW.id';
        $this->assertNotEmpty(self::utils()->db->query($sql));
        $this->assertNotEmpty(self::utils()->trigger_exists($this->db_name() . '.' . $trg));
        $this->assertNotEmpty(self::utils()->drop_trigger($this->db_name() . '.' . $trg));
        $this->assertFalse(self::utils()->trigger_exists($this->db_name() . '.' . $trg));
    }
    public function test_create_trigger()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertNotEmpty(self::utils()->create_table($this->table_name($table), $data));
        $trg = self::utils()->db->DB_PREFIX . 'trg_' . __FUNCTION__;
        $this->assertNotEmpty(self::utils()->create_trigger($trg, $this->table_name($table), 'before', 'insert', 'SET @sum = @sum + NEW.id'));
        $expected = [
            'name' => $trg,
            'table' => $table,
            'event' => 'INSERT',
            'timing' => 'BEFORE',
            'statement' => 'SET @sum = @sum + NEW.id',
            'definer' => null,
        ];
        $result = self::utils()->trigger_info($this->db_name() . '.' . $trg);
        foreach ($expected as $k => $_expected) {
            $this->assertEquals($_expected, $result[$k]);
        }
    }

    public function test_list_events()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertNotEmpty(self::utils()->create_database($this->db_name()));

        $evt = self::utils()->db->DB_PREFIX . 'evt_' . __FUNCTION__;
        $events = self::utils()->list_events($this->db_name());
        if (empty($events)) {
            $sql = 'CREATE EVENT ' . $this->db_name() . '.' . $evt . '  ON SCHEDULE AT "2014-10-10 23:59:00"  DO INSERT INTO ' . $this->db_name() . '.totals VALUES (NOW());';
            $this->assertNotEmpty(self::utils()->db->query($sql));
        }
        //		$this->assertNotEmpty( self::utils()->list_events($this->db_name()) );
    }
    public function test_event_exists()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        /*
                $evt = self::utils()->db->DB_PREFIX. 'evt_'.__FUNCTION__;
                $this->assertFalse( self::utils()->event_exists($this->db_name().'.'.$evt) );
                $sql = 'CREATE EVENT '.$this->db_name().'.'.$evt.'  ON SCHEDULE AT "2014-10-10 23:59:00"  DO INSERT INTO '.$this->db_name().'.totals VALUES (NOW());';
                $this->assertNotEmpty( self::utils()->db->query($sql) );
                $this->assertNotEmpty( self::utils()->event_exists($this->db_name().'.'.$evt) );
        */
    }
    public function test_event_info()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        /*
                $evt = self::utils()->db->DB_PREFIX. 'evt_'.__FUNCTION__;
                $this->assertFalse( self::utils()->event_exists($this->db_name().'.'.$evt) );
                $sql = 'CREATE EVENT '.$this->db_name().'.'.$evt.'  ON SCHEDULE AT "2014-10-10 23:59:00"  DO INSERT INTO '.$this->db_name().'.totals VALUES (NOW());';
                $this->assertNotEmpty( self::utils()->db->query($sql) );
                $expected = array(
                    'name' => $evt,
                    'db' => $this->db_name(),
                    'definer' => NULL,
                    'timezone' => NULL,
                    'type' => 'ONE TIME',
                    'execute_at' => NULL,
                    'interval_value' => NULL,
                    'interval_field' => NULL,
                    'starts' => NULL,
                    'ends' => NULL,
                    'status' => 'ENABLED',
                    'originator' => '0',
                );
                $result = self::utils()->event_info($this->db_name().'.'.$evt);
                foreach ($expected as $k => $_expected) {
                    $this->assertEquals( $_expected, $result[$k] );
                }
        */
    }
    public function test_drop_event()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        /*
                $evt = self::utils()->db->DB_PREFIX. 'evt_'.__FUNCTION__;
                $this->assertFalse( self::utils()->event_exists($this->db_name().'.'.$evt) );
                $sql = 'CREATE EVENT '.$this->db_name().'.'.$evt.'  ON SCHEDULE AT "2014-10-10 23:59:00"  DO INSERT INTO '.$this->db_name().'.totals VALUES (NOW());';
                $this->assertNotEmpty( self::utils()->db->query($sql) );
                $this->assertNotEmpty( self::utils()->event_exists($this->db_name().'.'.$evt) );
                $this->assertNotEmpty( self::utils()->drop_event($this->db_name().'.'.$evt) );
                $this->assertFalse( self::utils()->event_exists($this->db_name().'.'.$evt) );
        */
    }
    public function test_create_event()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        /*
                $evt = self::utils()->db->DB_PREFIX. 'evt_'.__FUNCTION__;
                $this->assertFalse( self::utils()->event_exists($this->db_name().'.'.$evt) );
                $this->assertNotEmpty( self::utils()->create_event($this->db_name().'.'.$evt, 'AT "2014-10-10 23:59:00"', 'INSERT INTO '.$this->db_name().'.totals VALUES (NOW())') );
                $expected = array(
                    'name' => $evt,
                    'db' => $this->db_name(),
                    'definer' => NULL,
                    'timezone' => NULL,
                    'type' => 'ONE TIME',
                    'execute_at' => NULL,
                    'interval_value' => NULL,
                    'interval_field' => NULL,
                    'starts' => NULL,
                    'ends' => NULL,
                    'status' => 'ENABLED',
                    'originator' => '0',
                );
                $result = self::utils()->event_info($this->db_name().'.'.$evt);
                foreach ($expected as $k => $_expected) {
                    $this->assertEquals( $_expected, $result[$k] );
                }
        */
    }

    public function test_list_users()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertNotEmpty(self::utils()->list_users());
    }
    public function test_user_exists()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $db_params = self::utils()->db->db->params;
        $variants = array_unique([
            $db_params['user'] . '@' . $db_params['host'],
            $db_params['user'] . '@localhost',
            $db_params['user'] . '@127.0.0.1',
            $db_params['user'] . '@%',
            'root@' . $db_params['host'],
            'root@localhost',
            'root@127.0.0.1',
            'root@%',
        ]);
        $at_least_one_exists = false;
        foreach ($variants as $user) {
            $exists = self::utils()->user_exists($user);
            if ($exists) {
                $at_least_one_exists = true;
                break;
            }
        }
        $this->assertTrue($at_least_one_exists, 'Check that at least one system user from possible list really exists');
    }

    public function test_escape_database_name()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertEquals('`test_db`', self::utils()->_escape_database_name('test_db'));
    }
    public function test_escape_table_name()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertEquals('', self::utils()->_escape_table_name(''));
        $this->assertEquals('`' . self::utils()->db->DB_PREFIX . 'test_table`', self::utils()->_escape_table_name('test_table'));
        $this->assertEquals('`test_db`.`' . self::utils()->db->DB_PREFIX . 'test_table`', self::utils()->_escape_table_name('test_db.test_table'));
    }
    public function test_escape_key()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertEquals('`test_key`', self::utils()->_escape_key('test_key'));
    }
    public function test_escape_val()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertEquals('\'test_val\'', self::utils()->_escape_val('test_val'));
    }
    public function test_escape_fields()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $fields = ['id1', 'id2', 'test_field'];
        $expected = ['`id1`', '`id2`', '`test_field`'];
        $this->assertEquals($expected, self::utils()->_escape_fields($fields));
    }
    public function test__es()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertEquals('hello world', self::utils()->_es('hello world'));
        $this->assertEquals('hello\\\'world\\\'', self::utils()->_es('hello\'world\''));
    }

    public function test_helper_database()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $this->assertTrue((bool) self::utils()->create_database($this->db_name()));
        $this->assertTrue((bool) self::utils()->database_exists(self::db_name()));

        $this->assertTrue((bool) self::utils()->database(self::db_name())->exists());
    }
    public function test_helper_table()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertTrue((bool) self::utils()->create_table($this->table_name($table), $data));
        $this->assertTrue((bool) self::utils()->table_exists($this->db_name() . '.' . $table));

        $this->assertTrue((bool) self::utils()->database($this->db_name())->table($table)->exists());
        $this->assertTrue((bool) self::utils()->table($this->db_name(), $table)->exists());
    }
    public function test_helper_view()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $view = self::utils()->db->DB_PREFIX . 'view_' . __FUNCTION__;
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertFalse((bool) self::utils()->view_exists($this->db_name() . '.' . $view));
        $this->assertTrue((bool) self::utils()->create_table($this->table_name($table), $data));
        $this->assertTrue((bool) self::utils()->create_view($this->db_name() . '.' . $view, 'SELECT * FROM ' . $this->table_name($table)));
        $this->assertTrue((bool) self::utils()->view_exists($this->db_name() . '.' . $view));

        $this->assertTrue((bool) self::utils()->database($this->db_name())->view($view)->exists());
        $this->assertTrue((bool) self::utils()->view($this->db_name(), $view)->exists());
    }
    public function test_helper_column()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $col_info = $data['fields']['id'];
        $this->assertTrue((bool) self::utils()->create_table($this->table_name($table), $data));
        $this->assertTrue((bool) self::utils()->column_exists($this->table_name($table), 'id'));

        $this->assertTrue((bool) self::utils()->database($this->db_name())->table($table)->column('id')->exists());
        $this->assertTrue((bool) self::utils()->table($this->db_name(), $table)->column('id')->exists());
        $this->assertTrue((bool) self::utils()->column($this->db_name(), $table, 'id')->exists());
    }
    public function test_helper_index()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table = self::utils()->db->DB_PREFIX . __FUNCTION__;
        $data = ['fields' => ['id' => ['name' => 'id', 'type' => 'int', 'length' => 10]]];
        $this->assertTrue((bool) self::utils()->create_table($this->table_name($table), $data));
        $this->assertTrue((bool) self::utils()->add_index($this->table_name($table), 'PRIMARY', ['id'], ['type' => 'primary']));
        $this->assertTrue((bool) self::utils()->index_exists($this->table_name($table), 'PRIMARY'));

        $this->assertTrue((bool) self::utils()->database($this->db_name())->table($table)->index('PRIMARY')->exists());
        $this->assertTrue((bool) self::utils()->table($this->db_name(), $table)->index('PRIMARY')->exists());
        $this->assertTrue((bool) self::utils()->index($this->db_name(), $table, 'PRIMARY')->exists());
    }
    public function test_helper_foreign_key()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $table1 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_1';
        $table2 = self::utils()->db->DB_PREFIX . __FUNCTION__ . '_2';
        $data = [
            'fields' => [
                'id' => ['name' => 'id', 'type' => 'int', 'length' => 10],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id' => 'id']],
            ],
        ];
        $fkey = 'fkey_' . __FUNCTION__;
        $this->assertTrue((bool) self::utils()->create_table($this->table_name($table1), $data));
        $this->assertTrue((bool) self::utils()->create_table($this->table_name($table2), $data));
        $def_action = 'RESTRICT';
        if (self::$is_mysql8) {
            $def_action = 'NO ACTION';
        }
        $expected = [
            'name' => $fkey,
            'columns' => ['id' => 'id'],
            'ref_table' => $table2,
            'ref_columns' => ['id' => 'id'],
            'on_update' => $def_action,
            'on_delete' => $def_action,
        ];
        $this->assertTrue((bool) self::utils()->add_foreign_key($this->table_name($table1), $fkey, ['id'], $this->table_name($table2), ['id']));
        $this->assertEquals($expected, self::utils()->foreign_key_info($this->table_name($table1), $fkey));

        $this->assertEquals($expected, self::utils()->database($this->db_name())->table($table1)->foreign_key($fkey)->info());
        $this->assertTrue((bool) self::utils()->table($this->db_name(), $table1)->foreign_key($fkey)->info());
        $this->assertTrue((bool) self::utils()->foreign_key($this->db_name(), $table1, $fkey)->info());
    }

    public function test_split_sql()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }
        $expected = ['SELECT 1', 'SELECT 2', 'SELECT 3'];
        $this->assertEquals($expected, self::utils()->split_sql('SELECT 1; SELECT 2; SELECT 3'));
        $this->assertEquals($expected, self::utils()->split_sql('SELECT 1;' . PHP_EOL . ' SELECT 2;' . PHP_EOL . ' SELECT 3'));
        $this->assertEquals($expected, self::utils()->split_sql(';;SELECT 1;;' . PHP_EOL . PHP_EOL . PHP_EOL . '; SELECT 2;;' . PHP_EOL . PHP_EOL . PHP_EOL . '; SELECT 3;;;'));
    }
    protected function _cleanup_columns_info($a)
    {
        $skip_info = ['primary', 'unique', 'type_raw', 'collate', 'row_format'];
        foreach ((array) $a as $col => $info) {
            foreach ((array) $info as $k => $v) {
                if ($v === null || in_array($k, $skip_info) || ($k === 'auto_inc' && $v === false)) {
                    unset($a[$col][$k]);
                }
            }
        }
        return $a;
    }
}
