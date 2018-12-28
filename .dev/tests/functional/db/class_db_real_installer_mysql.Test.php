<?php

require_once __DIR__ . '/db_real_abstract.php';

/**
 * @requires extension mysqli
 */
class class_db_real_installer_mysql_test extends db_real_abstract
{
    public static function db_name()
    {
        return self::$DB_NAME;
    }
    public static function table_name($name)
    {
        return self::db_name() . '.' . $name;
    }

    public static function setUpBeforeClass()
    {
        self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
        self::$DB_DRIVER = 'mysqli';
        self::_connect();
        self::utils()->truncate_database(self::db_name());
    }
    public static function tearDownAfterClass()
    {
        self::utils()->truncate_database(self::db_name());
        self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
    }

    /***/
    public function _fix_sql_php($sql_php)
    {
        $innodb_has_fulltext = self::_innodb_has_fulltext();
        if ( ! $innodb_has_fulltext) {
            // Remove fulltext indexes from db structure before creating table
            foreach ((array) $sql_php['indexes'] as $iname => $idx) {
                if ($idx['type'] == 'fulltext') {
                    unset($sql_php['indexes'][$iname]);
                }
            }
        }
        foreach ((array) $sql_php['fields'] as $fname => $f) {
            unset($sql_php['fields'][$fname]['raw']);
            unset($sql_php['fields'][$fname]['collate']);
            unset($sql_php['fields'][$fname]['charset']);
            if ($f['default'] === 'NULL') {
                $sql_php['fields'][$fname]['default'] = null;
            }
        }
        foreach ((array) $sql_php['indexes'] as $fname => $f) {
            unset($sql_php['indexes'][$fname]['raw']);
        }
        foreach ((array) $sql_php['foreign_keys'] as $fname => $fk) {
            unset($sql_php['foreign_keys'][$fname]['raw']);
            if ($fk['on_update'] === null) {
                $sql_php['foreign_keys'][$fname]['on_update'] = 'RESTRICT';
            }
            if ($fk['on_delete'] === null) {
                $sql_php['foreign_keys'][$fname]['on_delete'] = 'RESTRICT';
            }
        }
        return $sql_php;
    }

    /***/
    public function test_sakila()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }

        $db_prefix = self::db()->DB_PREFIX;
        $innodb_has_fulltext = self::_innodb_has_fulltext();

        $this->assertEquals([], self::utils()->list_tables(self::db_name()));

        $parser = _class('db_ddl_parser_mysql', 'classes/db/');
        $parser->RAW_IN_RESULTS = true;

        $tables_php = [];
        $globs_php = [
            'fixtures' => __DIR__ . '/fixtures/*.php',
        ];
        foreach ($globs_php as $glob) {
            foreach (glob($glob) as $f) {
                $t_name = substr(basename($f), strlen('class_db_ddl_parser_mysql_test_tbl_'), -strlen('.sql'));
                $tables_php[$t_name] = include $f; // $data should be loaded from file
            }
        }
        $this->assertNotEmpty($tables_php);
        $this->assertTrue((bool) self::db()->query('SET foreign_key_checks = 0;'));
        foreach ((array) $tables_php as $name => $sql_php) {
            $sql_php = $this->_fix_sql_php($sql_php);
            $this->assertTrue(is_array($sql_php) && count((array) $sql_php) && $sql_php);
            $sql_php['name'] = $db_prefix . $name;
            $sql = $parser->create($sql_php);
            $this->assertFalse((bool) self::utils()->table_exists(self::table_name($db_prefix . $name)));
            $this->assertTrue((bool) self::db()->query($sql), 'creating table: ' . $db_prefix . $name);
            $this->assertTrue((bool) self::utils()->table_exists(self::table_name($db_prefix . $name)));

            $columns = self::utils()->list_columns(self::table_name($db_prefix . $name));
            foreach ((array) $columns as $fname => $f) {
                unset($columns[$fname]['type_raw']);
                unset($columns[$fname]['collate']);
                unset($columns[$fname]['charset']);
            }
            $this->assertEquals($sql_php['fields'], $columns, 'Compare columns with expected sql_php for table: ' . $name);
            $indexes = self::utils()->list_indexes(self::table_name($db_prefix . $name));
            $this->assertEquals($sql_php['indexes'], $indexes, 'Compare indexes with expected sql_php for table: ' . $name);
            $fks = self::utils()->list_foreign_keys(self::table_name($db_prefix . $name));
            $this->assertEquals($sql_php['foreign_keys'], $fks, 'Compare indexes with expected sql_php for table: ' . $name);
        }
        $this->assertTrue((bool) self::db()->query('SET foreign_key_checks = 1;'));
    }

    /***/
    public function test_yf_db_installer_basic()
    {
        if ($this->_need_skip_test(__FUNCTION__)) {
            return;
        }

        $db_prefix = self::db()->DB_PREFIX;
        $innodb_has_fulltext = self::_innodb_has_fulltext();

        self::utils()->truncate_database(self::db_name());
        $this->assertEquals([], self::utils()->list_tables(self::db_name()));

        $parser = _class('db_ddl_parser_mysql', 'classes/db/');
        $parser->RAW_IN_RESULTS = false;

        $tables_php = [];
        $globs_php = [
            'yf_main' => YF_PATH . 'share/db/sql_php/*.sql_php.php',
            'yf_plugins' => YF_PATH . 'plugins/*/share/db/sql_php/*.sql_php.php',
        ];
        foreach ($globs_php as $glob) {
            foreach (glob($glob) as $f) {
                $t_name = substr(basename($f), 0, -strlen('.sql_php.php'));
                $tables_php[$t_name] = include $f; // $data should be loaded from file
            }
        }
        $this->assertNotEmpty($tables_php);
        $this->assertTrue((bool) self::db()->query('SET foreign_key_checks = 0;'));
        foreach ((array) $tables_php as $name => $sql_php) {
            $sql_php = $this->_fix_sql_php($sql_php);
            $this->assertTrue(is_array($sql_php) && count((array) $sql_php) && $sql_php);
            $sql_php['name'] = $db_prefix . $name;
            $sql = $parser->create($sql_php);
            $this->assertFalse((bool) self::utils()->table_exists(self::table_name($db_prefix . $name)));
            $this->assertTrue((bool) self::db()->query($sql), 'creating table: ' . $db_prefix . $name);
            $this->assertTrue((bool) self::utils()->table_exists(self::table_name($db_prefix . $name)));

            $columns = self::utils()->list_columns(self::table_name($db_prefix . $name));
            foreach ((array) $columns as $fname => $f) {
                unset($columns[$fname]['type_raw']);
                unset($columns[$fname]['collate']);
                unset($columns[$fname]['charset']);
            }
            $this->assertEquals($sql_php['fields'], $columns, 'Compare columns with expected sql_php for table: ' . $name);
            $indexes = self::utils()->list_indexes(self::table_name($db_prefix . $name));
            $this->assertEquals($sql_php['indexes'], $indexes, 'Compare indexes with expected sql_php for table: ' . $name);
            $fks = self::utils()->list_foreign_keys(self::table_name($db_prefix . $name));
            $this->assertEquals($sql_php['foreign_keys'], $fks, 'Compare indexes with expected sql_php for table: ' . $name);
        }
        $this->assertTrue((bool) self::db()->query('SET foreign_key_checks = 1;'));
    }

    /**
     * check how db installer table creating working, when selecting missing column in db, but have it in structure.
     */
    public function test_yf_db_installer_create_missing_table()
    {
        $bak['ERROR_AUTO_REPAIR'] = self::db()->ERROR_AUTO_REPAIR;
        self::db()->ERROR_AUTO_REPAIR = true;

        $db_prefix = self::db()->DB_PREFIX;
        $innodb_has_fulltext = self::_innodb_has_fulltext();

        self::utils()->truncate_database(self::db_name());
        $this->assertEquals([], self::utils()->list_tables(self::db_name()));

        $parser = _class('db_ddl_parser_mysql', 'classes/db/');
        $parser->RAW_IN_RESULTS = false;

        $tables_php = [];
        $globs_php = [
            'yf_main' => YF_PATH . 'share/db/sql_php/*.sql_php.php',
            'yf_plugins' => YF_PATH . 'plugins/*/share/db/sql_php/*.sql_php.php',
        ];
        foreach ($globs_php as $glob) {
            foreach (glob($glob) as $f) {
                $t_name = substr(basename($f), 0, -strlen('.sql_php.php'));
                $tables_php[$t_name] = include $f; // $data should be loaded from file
            }
        }
        $this->assertNotEmpty($tables_php);
        $this->assertTrue((bool) self::db()->query('SET foreign_key_checks = 0;'));
        foreach ((array) $tables_php as $name => $sql_php) {
            $sql_php = $this->_fix_sql_php($sql_php);
            $this->assertTrue(is_array($sql_php) && count((array) $sql_php) && $sql_php);
            $this->assertFalse((bool) self::utils()->table_exists(self::table_name($db_prefix . $name)));
            $this->assertTrue((bool) self::db()->query('SELECT * FROM ' . self::table_name($db_prefix . $name) . ' LIMIT 1'), 'selecting from table: ' . $db_prefix . $name);
            $this->assertTrue((bool) self::utils()->table_exists(self::table_name($db_prefix . $name)));

            $columns = self::utils()->list_columns(self::table_name($db_prefix . $name));
            foreach ((array) $columns as $fname => $f) {
                unset($columns[$fname]['type_raw']);
                unset($columns[$fname]['collate']);
                unset($columns[$fname]['charset']);
            }
            $this->assertEquals($sql_php['fields'], $columns, 'Compare columns with expected sql_php for table: ' . $name);
            $indexes = self::utils()->list_indexes(self::table_name($db_prefix . $name));
            $this->assertEquals($sql_php['indexes'], $indexes, 'Compare indexes with expected sql_php for table: ' . $name);
            $fks = self::utils()->list_foreign_keys(self::table_name($db_prefix . $name));
            $this->assertEquals($sql_php['foreign_keys'], $fks, 'Compare indexes with expected sql_php for table: ' . $name);
        }
        $this->assertTrue((bool) self::db()->query('SET foreign_key_checks = 1;'));

        self::db()->ERROR_AUTO_REPAIR = $bak['ERROR_AUTO_REPAIR'];
    }

    /**
     * check how db installer table altering working when selecting missing column in db, but have it in structure.
     */
    public function test_yf_db_installer_alter_table()
    {
        $bak['ERROR_AUTO_REPAIR'] = self::db()->ERROR_AUTO_REPAIR;
        self::db()->ERROR_AUTO_REPAIR = true;

        $db_prefix = self::db()->DB_PREFIX;
        $innodb_has_fulltext = self::_innodb_has_fulltext();

        self::utils()->truncate_database(self::db_name());
        $this->assertEquals([], self::utils()->list_tables(self::db_name()));

        $tables_php = [];
        $globs_php = [
            'yf_main' => YF_PATH . 'share/db/sql_php/*.sql_php.php',
            'yf_plugins' => YF_PATH . 'plugins/*/share/db/sql_php/*.sql_php.php',
        ];
        foreach ($globs_php as $glob) {
            foreach (glob($glob) as $f) {
                $t_name = substr(basename($f), 0, -strlen('.sql_php.php'));
                $tables_php[$t_name] = include $f; // $data should be loaded from file
            }
        }
        $this->assertNotEmpty($tables_php);
        $this->assertTrue((bool) self::db()->query('SET foreign_key_checks = 0;'));

        $t1 = array_slice($tables_php, 0, 1, true);
        $t2 = array_slice($tables_php, 1, 1, true);
        $tables_php = [
            key($t1) => current($t1),
            key($t2) => current($t2),
        ];
        foreach ((array) $tables_php as $name => $sql_php) {
            $table = $db_prefix . $name;
            $sql_php = $this->_fix_sql_php($sql_php);
            $this->assertTrue(is_array($sql_php) && count((array) $sql_php) && $sql_php);
            $this->assertFalse((bool) self::utils()->table_exists(self::table_name($table)));
            $this->assertTrue((bool) self::db()->query('SELECT * FROM ' . self::table_name($table) . ' LIMIT 1'), 'selecting from table: ' . $table);
            $this->assertTrue((bool) self::utils()->table_exists(self::table_name($table)));

            $cols = $sql_php['fields'];
            $last_name = key(array_reverse($cols));

            $before_cols = self::utils()->list_columns(self::table_name($table));
            $this->assertTrue((bool) self::utils()->drop_column(self::table_name($table), $last_name));
            $after_cols = self::utils()->list_columns(self::table_name($table));
            $this->assertNotEquals($before_cols, $after_cols);

            $this->assertTrue(self::db()->query('SELECT `' . implode('`,`', array_keys($before_cols)) . '` FROM ' . $table . ' LIMIT 1'));

            $after2_cols = self::utils()->list_columns(self::table_name($table));
            $this->assertEquals($before_cols, $after2_cols);
        }
        $this->assertTrue((bool) self::db()->query('SET foreign_key_checks = 1;'));

        self::db()->ERROR_AUTO_REPAIR = $bak['ERROR_AUTO_REPAIR'];
    }


    public function test_yf_db_installer_sharding()
    {
        $bak['ERROR_AUTO_REPAIR'] = self::db()->ERROR_AUTO_REPAIR;
        self::db()->ERROR_AUTO_REPAIR = true;

        $db_prefix = self::db()->DB_PREFIX;
        $innodb_has_fulltext = self::_innodb_has_fulltext();
        $db_installer = _class('db_installer_mysql', 'classes/db/');
        $bak['SHARDING_BY_YEAR'] = $db_installer->SHARDING_BY_YEAR;
        $bak['SHARDING_BY_MONTH'] = $db_installer->SHARDING_BY_MONTH;
        $bak['SHARDING_BY_DAY'] = $db_installer->SHARDING_BY_DAY;
        $bak['SHARDING_BY_LANG'] = $db_installer->SHARDING_BY_LANG;
        $db_installer->SHARDING_BY_YEAR = true;
        $db_installer->SHARDING_BY_MONTH = true;
        $db_installer->SHARDING_BY_DAY = true;
        $db_installer->SHARDING_BY_LANG = true;

        self::utils()->truncate_database(self::db_name());
        $this->assertEquals([], self::utils()->list_tables(self::db_name()));

        $tables_php = [];
        $globs_php = [
            'yf_main' => YF_PATH . 'share/db/sql_php/*.sql_php.php',
            'yf_plugins' => YF_PATH . 'plugins/*/share/db/sql_php/*.sql_php.php',
        ];
        foreach ($globs_php as $glob) {
            foreach (glob($glob) as $f) {
                $t_name = substr(basename($f), 0, -strlen('.sql_php.php'));
                $tables_php[$t_name] = include $f; // $data should be loaded from file
            }
        }
        $this->assertNotEmpty($tables_php);
        $this->assertTrue((bool) self::db()->query('SET foreign_key_checks = 0;'));

        $t1 = array_slice($tables_php, 0, 1, true);
        $t2 = array_slice($tables_php, 1, 1, true);
        $t3 = array_slice($tables_php, 2, 1, true);
        $t4 = array_slice($tables_php, 3, 1, true);
        $t5 = array_slice($tables_php, 4, 1, true);
        $tables_php = [
            key($t1) => current($t1),
            key($t2) . '_2020' => current($t2),
            key($t3) . '_2020_03' => current($t3),
            key($t4) . '_2020_03_28' => current($t4),
            key($t5) . '_ru' => current($t5),
        ];
        foreach ((array) $tables_php as $name => $sql_php) {
            $table = $db_prefix . $name;
            $sql_php = $this->_fix_sql_php($sql_php);
            $this->assertTrue(is_array($sql_php) && count((array) $sql_php) && $sql_php);
            $this->assertFalse((bool) self::utils()->table_exists(self::table_name($table)));
            $this->assertTrue((bool) self::db()->query('SELECT * FROM ' . self::table_name($table) . ' LIMIT 1'), 'selecting from table: ' . $table);
            $this->assertTrue((bool) self::utils()->table_exists(self::table_name($table)));

            $cols = $sql_php['fields'];
            $last_name = key(array_reverse($cols));

            $before_cols = self::utils()->list_columns(self::table_name($table));
            $this->assertTrue((bool) self::utils()->drop_column(self::table_name($table), $last_name));
            $after_cols = self::utils()->list_columns(self::table_name($table));
            $this->assertNotEquals($before_cols, $after_cols);

            $this->assertTrue(self::db()->query('SELECT `' . implode('`,`', array_keys($before_cols)) . '` FROM ' . $table . ' LIMIT 1'));

            $after2_cols = self::utils()->list_columns(self::table_name($table));
            $this->assertEquals($before_cols, $after2_cols);
        }
        $this->assertTrue((bool) self::db()->query('SET foreign_key_checks = 1;'));

        $db_installer->SHARDING_BY_YEAR = $bak['SHARDING_BY_YEAR'];
        $db_installer->SHARDING_BY_MONTH = $bak['SHARDING_BY_MONTH'];
        $db_installer->SHARDING_BY_DAY = $bak['SHARDING_BY_DAY'];
        $db_installer->SHARDING_BY_LANG = $bak['SHARDING_BY_LANG'];
        self::db()->ERROR_AUTO_REPAIR = $bak['ERROR_AUTO_REPAIR'];
    }

    /***/
    public function test_yf_db_installer_fix_table_indexes()
    {
        // TODO
    }

    /***/
    public function test_yf_db_installer_fix_table_foreign_keys()
    {
        // TODO
    }
    // TODO: db installer events before/after create/alter table tests
    // TODO: db installer extending tests

    /**
     * When installer working, but not successful, we are loosing stats of the original query.
     */
    public function test_query_stats_override()
    {
        $bak['ERROR_AUTO_REPAIR'] = self::db()->ERROR_AUTO_REPAIR;
        self::db()->ERROR_AUTO_REPAIR = true;

        $db_prefix = self::db()->DB_PREFIX;

        self::utils()->truncate_database(self::db_name());
        $this->assertEquals([], self::utils()->list_tables(self::db_name()));

        $tables_php = [];
        $globs_php = [
            'yf_main' => YF_PATH . 'share/db/sql_php/*.sql_php.php',
            'yf_plugins' => YF_PATH . 'plugins/*/share/db/sql_php/*.sql_php.php',
        ];
        foreach ($globs_php as $glob) {
            foreach (glob($glob) as $f) {
                $t_name = substr(basename($f), 0, -strlen('.sql_php.php'));
                $tables_php[$t_name] = include $f; // $data should be loaded from file
            }
        }
        $this->assertNotEmpty($tables_php);
        $this->assertTrue((bool) self::db()->query('SET foreign_key_checks = 0;'));

        $t1 = array_slice($tables_php, 0, 1, true);
        $t2 = array_slice($tables_php, 1, 1, true);
        $tables_php = [
            key($t1) => current($t1),
//			key($t2)	=> current($t2),
        ];
        foreach ((array) $tables_php as $name => $sql_php) {
            $table = $db_prefix . $name;
            /*
                        $this->assertFalse( (bool)self::utils()->table_exists(self::table_name($table)) );
            
            var_dump('affected rows: '.self::db()->affected_rows());
            var_dump('num rows: '.self::db()->num_rows());
            var_dump('insert_id: '.self::db()->insert_id());
            
            #var_dump('insert', self::db()->insert($table, array('id' => 1)));
            #var_dump('insert', self::db()->insert_safe($table, array('id' => 1)));
            #var_dump('insert', self::db()->insert($table, array('id2' => 1)));
            var_dump('insert', self::db()->insert_safe($table, array('id2' => 1)));
            #var_dump('select', self::db()->query('select * from '.$table));
            #var_dump('delete', self::db()->query('delete from '.$table));
            #var_dump('update', self::db()->update($table, array('id' => 1), 1));
            
            var_dump('affected rows: '.self::db()->affected_rows());
            var_dump('num rows: '.self::db()->num_rows());
            var_dump('insert_id: '.self::db()->insert_id());
            
                        $this->assertTrue( (bool)self::utils()->table_exists(self::table_name($table)) );
            */
/*
            $sql_php = $this->_fix_sql_php($sql_php);
            $this->assertTrue( is_array($sql_php) && count((array)$sql_php) && $sql_php );
            $this->assertFalse( (bool)self::utils()->table_exists(self::table_name($table)) );
            $this->assertTrue( (bool)self::db()->query('SELECT * FROM '.self::table_name($table).' LIMIT 1'), 'selecting from table: '.$table );
            $this->assertTrue( (bool)self::utils()->table_exists(self::table_name($table)) );

            $cols = $sql_php['fields'];
            $last_name = key(array_reverse($cols));

            $before_cols = self::utils()->list_columns(self::table_name($table));
            $this->assertTrue( (bool)self::utils()->drop_column(self::table_name($table), $last_name) );
            $after_cols = self::utils()->list_columns(self::table_name($table));
            $this->assertNotEquals( $before_cols, $after_cols );

            $this->assertTrue( self::db()->query('SELECT `'.implode('`,`', array_keys($before_cols)).'` FROM '.$table.' LIMIT 1') );

            $after2_cols = self::utils()->list_columns(self::table_name($table));
            $this->assertEquals( $before_cols, $after2_cols );
*/
        }
        $this->assertTrue((bool) self::db()->query('SET foreign_key_checks = 1;'));

        self::db()->ERROR_AUTO_REPAIR = $bak['ERROR_AUTO_REPAIR'];
    }
}
