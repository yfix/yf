<?php

load('db_installer', '', 'classes/db/');
class yf_db_installer_mysql extends yf_db_installer
{
    /** @var int */
    public $NUM_RETRIES = 5;
    /** @var int */
    public $RETRY_DELAY = 0;
    /** @var string */
    public $DEFAULT_CHARSET = 'utf8';
    /** @var array */
    public $_KNOWN_TABLE_OPTIONS = [
        'ENGINE',
        'TYPE',
        'AUTO_INCREMENT',
        'AVG_ROW_LENGTH',
        'CHARSET',
        'DEFAULT CHARSET',
        'CHARACTER SET',
        'DEFAULT CHARACTER SET',
        'CHECKSUM',
        'COLLATE',
        'DEFAULT COLLATE',
        'COMMENT',
        'CONNECTION',
        'DATA DIRECTORY',
        'DELAY_KEY_WRITE',
        'INDEX DIRECTORY',
        'INSERT_METHOD',
        'MAX_ROWS',
        'MIN_ROWS',
        'PACK_KEYS',
        'PASSWORD',
        'ROW_FORMAT',
        'UNION',
    ];
    /** @var array */
    public $NO_REPAIR_TABLES = [];

    public $_DEF_TABLE_OPTIONS = [];

    /**
     * Framework construct.
     */
    public function _init()
    {
        parent::_init();
        $this->_DEF_TABLE_OPTIONS = [
            'DEFAULT CHARSET' => $this->DEFAULT_CHARSET,
            'ENGINE' => 'InnoDB',
        ];
    }

    /**
     * Trying to repair given table structure (and possibly data).
     * @param mixed $sql
     * @param mixed $db_error
     * @param mixed $db
     */
    public function repair($sql, $db_error, $db)
    {
        $sql = trim($sql);
        // #1191 Can't find FULLTEXT index matching the column list
        if (in_array($db_error['code'], [1191]) && $this->RESTORE_FULLTEXT_INDEX) {
            foreach ((array) conf('fulltext_needed_for') as $_fulltext_field) {
                list($f_table, $f_field) = explode('.', $_fulltext_field);
                if (empty($f_table) || false === strpos($sql, $f_table) || empty($f_field)) {
                    continue;
                }
                // Check if such index already exists
                foreach ((array) $db->get_all('SHOW INDEX FROM ' . $f_table . '', 'Key_name') as $k => $v) {
                    if ($v['Column_name'] != $f_field) {
                        continue;
                    }
                    if (strtoupper($v['Index_type']) == 'FULLTEXT') {
                        continue 2;
                    }
                }
                // TODO: convert into db utils()
                $db->query('ALTER TABLE ' . $f_table . ' ADD FULLTEXT KEY ' . $f_field . ' (' . $f_field . ')');
            }
            return $this->db_query_safe($sql, $db);
        // Errors related to server high load (currently we will handle only SELECTs)
        // #2013 means 'Lost connection to MySQL server during query'
        // #1205 means 'Lock wait timeout expired. Transaction was rolled back' (InnoDB)
        // #1213 means 'Transaction deadlock. You should rerun the transaction.' (InnoDB)
        } elseif (in_array($db_error['code'], [2013, 1205, 1213]) && substr($sql, 0, strlen('SELECT ')) == 'SELECT ') {
            return $this->db_query_safe($sql, $db);
        // #1146 means "Table %s doesn't exist"
        } elseif ($db_error['code'] == 1146) {
            // Try to get table name from error message
            preg_match('#Table [\'][a-z_0-9]+\.([a-z_0-9]+)[\'] doesn\'t exist#ims', $db_error['message'], $m);
            $item_to_repair = trim($m[1]);
            foreach (range(1, 3) as $n) {
                $dot_pos = strpos($item_to_repair, '.');
                if (false !== $dot_pos) {
                    $item_to_repair = substr($item_to_repair, $dot_pos);
                }
            }
            if (substr($item_to_repair, 0, strlen($db->DB_PREFIX)) == $db->DB_PREFIX) {
                $item_to_repair = substr($item_to_repair, strlen($db->DB_PREFIX));
            }
            if ( ! empty($item_to_repair)) {
                if ( ! $this->create_table(str_replace('dbt_', '', $item_to_repair), $db)) {
                    return false;
                }
            }
            return $this->db_query_safe($sql, $db);
        // #1054 means "Unknown column %s"
        } elseif ($db_error['code'] == 1054) {

            // Try to get column name from error message
            preg_match('#Unknown column [\']([a-z_0-9]+)[\'] in#ims', $db_error['message'], $m);
            $item_to_repair = $m[1];
            foreach (range(1, 3) as $n) {
                $dot_pos = strpos($item_to_repair ?? '', '.');
                if (false !== $dot_pos) {
                    $item_to_repair = substr($item_to_repair, $dot_pos);
                }
            }
            // Try to get table name from SQL
            preg_match('#[\s]*(UPDATE|FROM|INTO)[\s]+[`]{0,1}([a-z_0-9]+)[`]{0,1}#ims', $sql, $m2);
            $table_to_repair = $m2[2];
            foreach (range(1, 3) as $n) {
                $dot_pos = strpos($table_to_repair, '.');
                if (false !== $dot_pos) {
                    $table_to_repair = substr($table_to_repair, $dot_pos);
                }
            }
            if (substr($table_to_repair, 0, strlen($db->DB_PREFIX)) == $db->DB_PREFIX) {
                $table_to_repair = substr($table_to_repair, strlen($db->DB_PREFIX));
            }
            if ( ! empty($item_to_repair) && ! empty($m2[2])) {
                if ( ! $this->alter_table($table_to_repair, $item_to_repair, $db)) {
                    return false;
                }
            }
            if ( ! empty($installer_result)) {
                return $this->db_query_safe($sql, $db);
            }
        }
        return true;
    }

    /**
     * @param mixed $full_table_name
     * @param mixed $db
     */
    // TODO: convert into db utils()
    public function do_create_table($full_table_name, array $sql_php, $db)
    {
        if ( ! empty($this->NO_REPAIR_TABLES) && in_array($full_table_name, $this->NO_REPAIR_TABLES)) {
            return false;
        }

        $sql_php = $this->fix_sql_php($sql_php, $db);
        foreach ((array) $this->_DEF_TABLE_OPTIONS as $k => $v) {
            if ( ! isset($sql_php['options'][$k])) {
                $sql_php['options'][$k] = $v;
            }
        }
        $sql_php['name'] = $full_table_name;
        $sql = _class('db_ddl_parser_mysql', 'classes/db/')->create($sql_php);
        return $db->query($sql);
    }

    /**
     * @param mixed $full_table_name
     * @param mixed $column_name
     * @param mixed $db
     */
    // TODO: convert into db utils()
    public function do_alter_table($full_table_name, $column_name, array $sql_php, $db)
    {
        $column_data = $sql_php['fields'][$column_name];
        $sql = 'ALTER TABLE ' . $full_table_name . ' ADD ' . _class('db_ddl_parser_mysql', 'classes/db/')->create_column_line($column_name, $column_data);
        return $db->query($sql);
    }

    /**
     * Execute original query again safely.
     * @param mixed $sql
     * @param mixed $db
     */
    public function db_query_safe($sql, $db)
    {
        if (isset($db->_repairs_by_sql[$sql]) && $db->_repairs_by_sql[$sql] >= $this->NUM_RETRIES) {
            return false;
        }
        @$db->_repairs_by_sql[$sql]++;
        $result = $db->query($sql);
        if ( ! $result) {
            if ($this->RETRY_DELAY) {
                sleep($this->RETRY_DELAY);
            }
            $result = $this->repair($sql, $db_error, $db);
        }
        return $result;
    }

    /**
     * @param mixed $db
     */
    public function innodb_has_fulltext($db)
    {
        if ( ! isset($db->_innodb_has_fulltext)) {
            $db->_innodb_has_fulltext = (bool) version_compare($db->get_server_version(), '5.6.0', '>');
        }
        return $db->_innodb_has_fulltext;
    }

    /**
     * @param mixed $db
     */
    public function fix_sql_php(array $sql_php, $db)
    {
        $innodb_has_fulltext = $this->innodb_has_fulltext($db);
        if ( ! $innodb_has_fulltext) {
            // Remove fulltext indexes from db structure before creating table
            foreach ((array) $sql_php['indexes'] as $iname => $idx) {
                if ($idx['type'] == 'fulltext') {
                    unset($sql_php['indexes'][$iname]);
                }
            }
        }
        return $sql_php;
    }
}
