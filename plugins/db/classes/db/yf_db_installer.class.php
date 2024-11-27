<?php

/**
 * Database structure installer core.
 */
abstract class yf_db_installer
{
    /** @var array */
    public $TABLES_SQL = [];
    /** @var array */
    public $TABLES_SQL_PHP = [];
    /** @var array */
    public $TABLES_DATA = [];
    /** @var bool */
    public $USE_CACHE = true;
    /** @var bool */
    public $USE_LOCKING = false;
    /** @var int */
    public $LOCK_TIMEOUT = 600;
    /** @var string */
    public $LOCK_FILE_NAME = 'db_installer.lock';
    /** @var bool */
    public $RESTORE_FULLTEXT_INDEX = true;
    /** @var bool */
    public $USE_SQL_IF_NOT_EXISTS = true;
    /** @var bool */
    public $SHARDING_BY_YEAR = false;
    /** @var bool */
    public $SHARDING_BY_MONTH = false;
    /** @var bool */
    public $SHARDING_BY_DAY = false;
    /** @var bool */
    public $SHARDING_BY_COUNTRY = false;
    /** @var bool */
    public $SHARDING_BY_LANG = false;

    public $SYS_TABLES_STRUCTS = [];
    public $OTHER_TABLES_STRUCTS = [];
    public $SYS_TABLES_DATAS = [];
    public $OTHER_TABLES_DATAS = [];
    public $create_table_pre_callbacks = [];
    public $create_table_post_callbacks = [];
    public $alter_table_pre_callbacks = [];
    public $alter_table_post_callbacks = [];

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }

    /**
     * Framework constructor.
     */
    public function _init()
    {
        $this->LOCK_FILE_NAME = PROJECT_PATH . $this->LOCK_FILE_NAME;
        $this->load_data();
    }


    public function load_data()
    {
        $t_names = [];
        // Preload db installer SQL CREATE TABLE DDL statements
        $ext = '.sql.php';
        $patterns = [
            'framework' => YF_PATH . 'db/sql/*' . $ext,
            'project' => PROJECT_PATH . 'db/sql/*' . $ext,
            'app' => APP_PATH . 'db/sql/*' . $ext,
            'plugins_framework' => YF_PATH . 'plugins/*/db/sql/*' . $ext,
            'plugins_project' => PROJECT_PATH . 'plugins/*/db/sql/*' . $ext,
            'plugins_app' => APP_PATH . 'plugins/*/db/sql/*' . $ext,
            'share_framework' => YF_PATH . 'share/db/sql/*' . $ext,
            'share_project' => PROJECT_PATH . 'share/db/sql/*' . $ext,
            'share_app' => APP_PATH . 'share/db/sql/*' . $ext,
            'plugins_share_framework' => YF_PATH . 'plugins/*/share/db/sql/*' . $ext,
            'plugins_share_project' => PROJECT_PATH . 'plugins/*/share/db/sql/*' . $ext,
            'plugins_share_app' => APP_PATH . 'plugins/*/share/db/sql/*' . $ext,
        ];
        foreach ($patterns as $glob) {
            foreach (glob($glob) as $path) {
                $t_name = substr(basename($path), 0, -strlen($ext));
                $t_names[$t_name] = $path;
            }
        }
        // Allow override in project
        foreach ($t_names as $t_name => $path) {
            $this->TABLES_SQL[$t_name] = include $path;
        }

        // Preload db installer PHP array of CREATE TABLE DDL statements
        $ext = '.sql_php.php';
        $patterns = [
            'framework' => YF_PATH . 'db/sql_php/*' . $ext,
            'project' => PROJECT_PATH . 'db/sql_php/*' . $ext,
            'app' => APP_PATH . 'db/sql_php/*' . $ext,
            'plugins_framework' => YF_PATH . 'plugins/*/db/sql_php/*' . $ext,
            'plugins_project' => PROJECT_PATH . 'plugins/*/db/sql_php/*' . $ext,
            'plugins_app' => APP_PATH . 'plugins/*/db/sql_php/*' . $ext,
            'share_framework' => YF_PATH . 'share/db/sql_php/*' . $ext,
            'share_project' => PROJECT_PATH . 'share/db/sql_php/*' . $ext,
            'share_app' => APP_PATH . 'share/db/sql_php/*' . $ext,
            'plugins_share_framework' => YF_PATH . 'plugins/*/share/db/sql_php/*' . $ext,
            'plugins_share_project' => PROJECT_PATH . 'plugins/*/share/db/sql_php/*' . $ext,
            'plugins_share_app' => APP_PATH . 'plugins/*/share/db/sql_php/*' . $ext,
        ];

        foreach ($patterns as $glob) {
            foreach (glob($glob) as $path) {
                $t_name = substr(basename($path), 0, -strlen($ext));
                $t_names[$t_name] = $path;
            }
        }
        // Allow override in project
        foreach ($t_names as $t_name => $path) {
            $this->TABLES_SQL_PHP[$t_name] = include $path;
        }

        // Preload db installer data PHP arrays needed to be inserted after CREATE TABLE == initial data
        $ext = '.data.php';
        $patterns = [
            'framework' => YF_PATH . 'db/data/*' . $ext,
            'project' => PROJECT_PATH . 'db/data/*' . $ext,
            'app' => APP_PATH . 'db/data/*' . $ext,
            'plugins_framework' => YF_PATH . 'plugins/*/db/data/*' . $ext,
            'plugins_project' => PROJECT_PATH . 'plugins/*/db/data/*' . $ext,
            'plugins_app' => APP_PATH . 'plugins/*/db/data/*' . $ext,
            'share_framework' => YF_PATH . 'share/db/data/*' . $ext,
            'share_project' => PROJECT_PATH . 'share/db/data/*' . $ext,
            'share_app' => APP_PATH . 'share/db/data/*' . $ext,
            'plugins_share_framework' => YF_PATH . 'plugins/*/share/db/data/*' . $ext,
            'plugins_share_project' => PROJECT_PATH . 'plugins/*/share/db/data/*' . $ext,
            'plugins_share_app' => APP_PATH . 'plugins/*/share/db/data/*' . $ext,
        ];

        foreach ($patterns as $glob) {
            foreach (glob($glob) as $path) {
                $t_name = substr(basename($path), 0, -strlen($ext));
                $t_names[$t_name] = $path;
            }
        }
        // Allow override in project
        foreach ($t_names as $t_name => $path) {
            $this->TABLES_DATA[$t_name] = include $path;
        }
        // Project has higher priority than framework (allow to change anything in project)
        // Try to load db structure from project file
        // Sample contents part: 	$project_data['OTHER_TABLES_STRUCTS'] = my_array_merge((array)$project_data['OTHER_TABLES_STRUCTS'], array(
        $structure_file = PROJECT_PATH . 'project_db_structure.php';
        if (file_exists($structure_file)) {
            include_once $structure_file;
        }
        foreach ((array) $project_data as $cur_array_name => $_cur_data) {
            $this->$cur_array_name = my_array_merge((array) $this->$cur_array_name, (array) $_cur_data);
        }
        // Compatibility with old codebase
        foreach ((array) $this->SYS_TABLES_STRUCTS as $k => $v) {
            $this->TABLES_SQL[$k] = $v;
        }
        foreach ((array) $this->OTHER_TABLES_STRUCTS as $k => $v) {
            $this->TABLES_SQL[$k] = $v;
        }
        foreach ((array) $this->SYS_TABLES_DATAS as $k => $v) {
            $this->TABLES_DATA[$k] = $v;
        }
        foreach ((array) $this->OTHER_TABLES_DATAS as $k => $v) {
            $this->TABLES_DATA[$k] = $v;
        }
    }

    /**
     * Do create table.
     * @param mixed $table_name
     * @param mixed $db
     */
    public function create_table($table_name, $db)
    {
        $table_found = false;
        if (empty($table_name)) {
            return false;
        }
        if ( ! $this->get_lock()) {
            return false;
        }
        $table_data = [];
        if (isset($this->TABLES_SQL[$table_name])) {
            $table_found = true;
        } elseif (isset($this->TABLES_SQL['sys_' . $table_name])) {
            $table_name = 'sys_' . $table_name;
            $table_found = true;
        }
        $full_table_name = $db->DB_PREFIX . $table_name;
        if ($table_found) {
            $table_sql = $this->TABLES_SQL[$table_name];
            $sql_php = $this->TABLES_SQL_PHP[$table_name];
            $table_data = $this->TABLES_DATA[$table_name];
        } else {
            // Try if sharded table
            $shard_table_name = $this->get_shard_table_name($table_name, $db);
            if ($shard_table_name) {
                $table_sql = $this->TABLES_SQL[$shard_table_name];
                $sql_php = $this->TABLES_SQL_PHP[$shard_table_name];
                $table_data = $this->TABLES_DATA[$shard_table_name];
            }
        }
        if (empty($table_sql)) {
            return false;
        }
        if ( ! $sql_php) {
            $sql_php = $this->create_table_sql_to_php($table_sql);
        }
        $sql_php = $this->create_table_pre_hook($full_table_name, $sql_php, $db);
        $result = $this->do_create_table($full_table_name, $sql_php, $db);
        if ( ! $result) {
            return false;
        }
        $this->create_table_post_hook($full_table_name, $sql_php, $db);
        // Check if we also need to insert some data into new system table
        if ($table_data && is_array($table_data)) {
            $insert_result = $db->insert_safe($full_table_name, $table_data);
            if ( ! main()->is_unit_test()) {
                $result = $insert_result;
            }
        }
        $this->release_lock();
        return $result;
    }

    /**
     * Do alter table structure.
     * @param mixed $table_name
     * @param mixed $column_name
     * @param mixed $db
     */
    public function alter_table($table_name, $column_name, $db)
    {
        if ( ! $this->get_lock()) {
            return false;
        }
        if (strlen($db->DB_PREFIX) && substr($table_name, 0, strlen($db->DB_PREFIX)) === $db->DB_PREFIX) {
            $table_name = substr($table_name, strlen($db->DB_PREFIX));
        }
        $avail_tables = $db->meta_tables();
        if ( ! in_array($db->DB_PREFIX . $table_name, $avail_tables)) {
            return false;
        }
        $sql_php = [];
        // Try to get already pregenerated data
        if ( ! $sql_php) {
            $sql_php = $this->TABLES_SQL_PHP[$table_name];
            if ( ! $sql_php && isset($this->TABLES_SQL[$table_name])) {
                $sql_php = $this->create_table_sql_to_php($this->TABLES_SQL[$table_name]);
            }
        }
        if ( ! $sql_php) {
            // Try if sharded table
            $shard_table_name = $this->get_shard_table_name($table_name, $db);
            if ($shard_table_name) {
                $sql_php = $this->TABLES_SQL_PHP[$shard_table_name];
                if ( ! $sql_php && isset($this->TABLES_SQL[$shard_table_name])) {
                    $sql_php = $this->create_table_sql_to_php($this->TABLES_SQL[$shard_table_name]);
                }
            }
        }
        if ( ! $sql_php) {
            return false;
        }
        if ( ! isset($sql_php['fields'][$column_name])) {
            return false;
        }
        $full_table_name = $db->DB_PREFIX . $table_name;
        $sql_php = $this->alter_table_pre_hook($full_table_name, $column_name, $sql_php, $db);
        $result = $this->do_alter_table($full_table_name, $column_name, $sql_php, $db);
        $this->alter_table_post_hook($full_table_name, $column_name, $sql_php, $db);
        $this->release_lock();
        return $result;
    }

    /**
     * Try to find table structure with sharding in mind.
     * @param mixed $table_name
     * @param mixed $db
     */
    public function get_shard_table_name($table_name, $db)
    {
        $shard_table_name = '';
        // Try sharding by year/month/day (example: db('currency_pairs_log_2013_07_01') from db('currency_pairs_log'))
        if ( ! $shard_table_name && $this->SHARDING_BY_DAY) {
            $name = $table_name;
            $shard_day = (int) substr($name, -strlen('01'));
            $shard_month = (int) substr($name, -strlen('07_01'), strlen('07'));
            $shard_year = (int) substr($name, -strlen('2013_07_01'), strlen('2013'));
            $has_divider = (substr($name, -strlen('_2013_07_01'), 1) === '_');
            if ($has_divider && $shard_year >= 1970 && $shard_year <= 2050 && $shard_month >= 1 && $shard_month <= 12 && $shard_day >= 1 && $shard_day <= 31) {
                $shard_table_name = substr($name, 0, -strlen('_2013_07_01'));
            }
        }
        // Try sharding by year/month (example: db('stats_cars_2009_08'), db('stats_cars_2009_07'), db('stats_cars_2009_06') from db('stats_cars'))
        if ( ! $shard_table_name && $this->SHARDING_BY_MONTH) {
            $name = $table_name;
            $shard_month = (int) substr($name, -strlen('07'));
            $shard_year = (int) substr($name, -strlen('2013_08'), strlen('2013'));
            $has_divider = (substr($name, -strlen('_2013_08'), 1) === '_');
            if ($has_divider && $shard_year >= 1970 && $shard_year <= 2050 && $shard_month >= 1 && $shard_month <= 12) {
                $shard_table_name = substr($name, 0, -8);
            }
        }
        // Try sharding by year (example: db('stats_cars_2009'), from db('stats_cars'))
        if ( ! $shard_table_name && $this->SHARDING_BY_YEAR) {
            $name = $table_name;
            $shard_year = (int) substr($name, -strlen('2013'));
            $has_divider = (substr($name, -strlen('_2013'), 1) === '_');
            if ($has_divider && $shard_year >= 1970 && $shard_year <= 2050) {
                $shard_table_name = substr($name, 0, -strlen('_2009'));
            }
        }
        // Try sharding by lang (example: db('some_data_en'), db('some_data_ru') from db('some_data'))
        if ( ! $shard_table_name && $this->SHARDING_BY_LANG) {
            $name = $table_name;
            $shard_lang = substr($name, -strlen('ru'));
            $has_divider = (substr($name, -strlen('_ru'), 1) === '_');
            if ($has_divider && preg_match('/[a-z]{2}/', $shard_lang)) {
                $shard_table_name = substr($name, 0, -strlen('_ru'));
            }
        }
        // Try sharding by country (example: db('cars_es'), db('cars_uk'), db('cars_de') from db('cars'))
        if ( ! $shard_table_name && $this->SHARDING_BY_COUNTRY) {
            $name = $table_name;
            $shard_country = substr($name, -strlen('es'));
            $has_divider = (substr($name, -strlen('_es'), 1) === '_');
            if ($has_divider && preg_match('/[a-z]{2}/', $shard_country)) {
                $shard_table_name = substr($name, 0, -strlen('_es'));
            }
        }
        if ($shard_table_name) {
            if (isset($this->TABLES_SQL[$shard_table_name])) {
                $table_found = true;
            } elseif (isset($this->TABLES_SQL['sys_' . $shard_table_name])) {
                $table_found = true;
                $shard_table_name = 'sys_' . $shard_table_name;
            } else {
                // Not really found
                $shard_table_name = '';
            }
        }
        return $shard_table_name;
    }

    /**
     * @param mixed $data
     */
    public function create_table_php_to_sql($data)
    {
        return _class('db_ddl_parser_mysql', 'classes/db/')->create($data);
    }

    /**
     * Alias.
     * @param mixed $sql
     */
    public function create_table_sql_to_php($sql)
    {
        return $this->db_table_struct_into_array($sql);
    }

    /**
     * @param mixed $sql
     */
    public function db_table_struct_into_array($sql)
    {
        $options = '';
        // Get table options from table structure. Example: /** ENGINE=MEMORY **/
        if (preg_match('#\/\*\*(?P<raw_options>[^\*\/]+)\*\*\/#i', trim($sql), $m)) {
            // Cut comment with options from source table structure to prevent misunderstanding
            $sql = str_replace($m[0], '', $sql);
            $options = $m['raw_options'];
        }
        $tmp_name = '';
        if (false === strpos(strtoupper($sql), 'CREATE TABLE')) {
            $tmp_name = 'tmp_name_not_exists';
            $sql = 'CREATE TABLE `' . $tmp_name . '` (' . $sql . ')';
        }
        // Place them into the end of the DDL
        if ($options) {
            $sql = rtrim(rtrim(rtrim($sql), ';')) . ' ' . $options;
        }
        $result = _class('db_ddl_parser_mysql', 'classes/db/')->parse($sql);
        if ($result && $tmp_name) {
            $result['name'] = '';
        }
        return $result;
    }

    /**
     * This method can be inherited in project with custom rules inside.
     * Or use array or pattern callbacks. Example:
     *	$this->create_table_pre_callbacks = array(
     *		'^b_bets.*' => function($table, $sql_php, $db, $m) {
     *			return $struct;
     *		}
     *	);.
     * @param mixed $full_table_name
     * @param mixed $sql_php
     * @param mixed $db
     */
    public function create_table_pre_hook($full_table_name, $sql_php, $db)
    {
        _class('core_events')->fire('db.before_create_table', [
            'table' => $full_table_name,
            'sql_php' => $sql_php,
            'db' => $db,
        ]);
        foreach ((array) $this->create_table_pre_callbacks as $regex => $func) {
            if ( ! preg_match('/' . $regex . '/ims', $full_table_name, $m)) {
                continue;
            }
            $sql_php = $func($full_table_name, $sql_php, $db, $m);
        }
        return $sql_php;
    }

    /**
     * This method can be inherited in project with custom rules inside.
     * @param mixed $full_table_name
     * @param mixed $sql_php
     * @param mixed $db
     */
    public function create_table_post_hook($full_table_name, $sql_php, $db)
    {
        _class('core_events')->fire('db.after_create_table', [
            'table' => $full_table_name,
            'sql_php' => $sql_php,
            'db' => $db,
        ]);
        foreach ((array) $this->create_table_post_callbacks as $regex => $func) {
            if ( ! preg_match('/' . $regex . '/ims', $full_table_name, $m)) {
                continue;
            }
            $results[$regex] = $func($full_table_name, $sql_php, $db, $m);
        }
        return $results;
    }

    /**
     * This method can be inherited in project with custom rules inside.
     * @param mixed $table_name
     * @param mixed $column_name
     * @param mixed $sql_php
     * @param mixed $db
     */
    public function alter_table_pre_hook($table_name, $column_name, $sql_php, $db)
    {
        _class('core_events')->fire('db.before_alter_table', [
            'table' => $table_name,
            'column' => $column_name,
            'sql_php' => $sql_php,
            'db' => $db,
        ]);
        foreach ((array) $this->alter_table_pre_callbacks as $regex => $func) {
            if ( ! preg_match('/' . $regex . '/ims', $table_name, $m)) {
                continue;
            }
            $sql_php = $func($table_name, $column_name, $sql_php, $db, $m);
        }
        return $sql_php;
    }

/**
     * This method can be inherited in project with custom rules inside.
     * @param mixed $table_name
     * @param mixed $column_name
     * @param mixed $sql_php
     * @param mixed $db
     */
    public function alter_table_post_hook($table_name, $column_name, $sql_php, $db)
    {
        _class('core_events')->fire('db.after_alter_table', [
            'table' => $table_name,
            'column' => $column_name,
            'sql_php' => $sql_php,
            'db' => $db,
        ]);
        foreach ((array) $this->alter_table_post_callbacks as $regex => $func) {
            if ( ! preg_match('/' . $regex . '/ims', $table_name, $m)) {
                continue;
            }
            $results[$regex] = $func($table_name, $column_name, $sql_php, $db, $m);
        }
        return $results;
    }

    /**
     * @param mixed $table_name
     * @param mixed $db
     */
    public function get_table_struct_array_by_name($table_name, $db)
    {
        list(, $create_sql) = array_values($db->get('SHOW CREATE TABLE ' . $db->escape_key($table_name)));
        return $this->db_table_struct_into_array($create_sql);
    }

    /**
     * @param mixed $only_what
     * @param mixed $db
     */
    public function get_all_struct_array($only_what, $db)
    {
        $structs_array = [];
        foreach ((array) $db->meta_tables() as $full_table_name) {
            $structs_array[substr($full_table_name, strlen($db->DB_PREFIX))] = $this->get_table_struct_array_by_name($full_table_name, $db);
        }
        return $structs_array;
    }

    /**
     * @param mixed $sql
     * @param mixed $db_error
     * @param mixed $db
     */
    abstract protected function repair($sql, $db_error, $db);

    /**
     * @param mixed $full_table_name
     * @param mixed $db
     */
    abstract protected function do_create_table($full_table_name, array $sql_php, $db);

    /**
     * @param mixed $table_name
     * @param mixed $column_name
     * @param mixed $db
     */
    abstract protected function do_alter_table($table_name, $column_name, array $sql_php, $db);


    protected function get_lock()
    {
        if ( ! $this->USE_LOCKING) {
            return true;
        }
        if (file_exists($this->LOCK_FILE_NAME)) {
            if ((time() - filemtime($this->LOCK_FILE_NAME)) > $this->LOCK_TIMEOUT) {
                unlink($this->LOCK_FILE_NAME);
            } else {
                return false;
            }
        }
        return file_put_contents($this->LOCK_FILE_NAME, time());
    }


    protected function release_lock()
    {
        if ( ! $this->USE_LOCKING) {
            return true;
        }
        unlink($this->LOCK_FILE_NAME);
        return true;
    }
}
