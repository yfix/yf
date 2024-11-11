<?php

load('db_driver', '', 'classes/db/');
class yf_db_driver_mysqli extends yf_db_driver
{
    /** @var @conf_skip */
    public $db_connect_id = null;
    /** @var string */
    public $SQL_MODE = '';
    /** @var int */
    public $CONNECT_TIMEOUT = 5;
    /** @var array of ini settings to apply before connect */
    public $INI_OPTS = [
        'mysqli.reconnect' => true,
    ];
    /** @var array of strings */
    public $SQL_AFTER_CONNECT = [
        'SET SQL_MODE = ""',
        'SET interactive_timeout = 3600',
        'SET wait_timeout = 3600',
    ];
    /** @var array of strings */
    public $SQL_AFTER_CONNECT_CONSOLE = [
        'SET SQL_MODE = ""',
        'SET interactive_timeout = 86400',
        'SET wait_timeout = 86400',
    ];
    /** @var array of callables */
    public $ON_BEFORE_CONNECT = [];
    /** @var array of callables */
    public $ON_AFTER_CONNECT = [];

    public $params = [];
    public $_connect_error = null;
    public $DEF_PORT = null;


    public function __construct(array $params)
    {
        if ( ! function_exists('mysqli_init')) {
            trigger_error('YF MySQLi db driver require missing php extension mysqli', E_USER_ERROR);
            return false;
        }
        $params['port'] = $params['port'] ?: 3306;
        if ($params['socket'] && ! file_exists($params['socket'])) {
            $params['socket'] = '';
        }
        $params['charset'] = $params['charset'] ?: (defined('DB_CHARSET') ? DB_CHARSET : 'utf8');
        $this->params = $params;
        $connected = $this->connect();
        if ($params['charset'] && $connected && $this->db_connect_id) {
            // See http://php.net/manual/en/mysqlinfo.concepts.charset.php
            mysqli_set_charset($this->db_connect_id, 'utf8');
        }
        return $this->db_connect_id;
    }


    public function _on_before_connect_default()
    {
        if (is_console()) {
            $this->params['persist'] = true;
            $this->SQL_AFTER_CONNECT = $this->SQL_AFTER_CONNECT_CONSOLE;
        }
    }


    public function connect()
    {
        $this->db_connect_id = mysqli_init();
        if ( ! $this->db_connect_id || mysqli_connect_errno()) {
            $this->_connect_error = 'cannot_connect_to_server';
            $this->db_connect_id = null;
            return false;
        }
        foreach ((array) $this->INI_OPTS as $ini_name => $ini_val) {
            ini_set($ini_name, $ini_val);
        }
        if ( ! $this->ON_BEFORE_CONNECT) {
            $this->ON_BEFORE_CONNECT[] = function () {
                return $this->_on_before_connect_default();
            };
        }
        foreach ((array) $this->ON_BEFORE_CONNECT as $func) {
            if (is_callable($func)) {
                $func($this);
            }
        }
        if ($this->params['socket']) {
            $connect_host = $this->params['socket'];
        } else {
            $connect_port = $this->params['port'] && $this->params['port'] != $this->DEF_PORT ? $this->params['port'] : '';
            $connect_host = ($this->params['persist'] ? 'p:' : '') . $this->params['host'] . ($connect_port ? ':' . $connect_port : '');
        }
        if ( ! $this->params['persist']) {
            mysqli_options($this->db_connect_id, MYSQLI_OPT_CONNECT_TIMEOUT, $this->CONNECT_TIMEOUT);
        }
        $is_connected = mysqli_real_connect($this->db_connect_id, $this->params['host'], $this->params['user'], $this->params['pswd'], '', $this->params['port'], $this->params['socket'], $this->params['ssl'] ? MYSQLI_CLIENT_SSL : 0);
        if ( ! $is_connected) {
            $this->_connect_error = 'cannot_connect_to_server';
            return false;
        }
        foreach ((array) $this->SQL_AFTER_CONNECT as $sql) {
            $this->query($sql);
        }

        if ($this->params['name'] != '') {
            $dbselect = $this->select_db($this->params['name']);
            // Try to create database, if not exists and if allowed
            if ( ! $dbselect && $this->params['allow_auto_create_db'] && preg_match('/^[a-z0-9][a-z0-9_]+[a-z0-9]$/i', $this->params['name'])) {
                $res = $this->query('CREATE DATABASE IF NOT EXISTS ' . $this->params['name']);
                if ($res) {
                    $dbselect = $this->select_db($this->params['name']);
                }
            }
            if ( ! $dbselect) {
                $this->_connect_error = 'cannot_select_db';
            }
            foreach ((array) $this->ON_AFTER_CONNECT as $func) {
                if (is_callable($func)) {
                    $func($this, $dbselect);
                }
            }
            return $dbselect;
        }
    }

    /**
     * @param mixed $name
     */
    public function select_db($name)
    {
        return $this->db_connect_id ? mysqli_select_db($this->db_connect_id, $name) : false;
    }


    public function close()
    {
        return mysqli_close($this->db_connect_id);
    }

    /**
     * @param mixed $query
     */
    public function query($query)
    {
        return $this->db_connect_id && strlen($query) ? mysqli_query($this->db_connect_id, $query) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function num_rows($query_id)
    {
        return $query_id ? mysqli_num_rows($query_id) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function affected_rows($query_id = false)
    {
        return $this->db_connect_id ? mysqli_affected_rows($this->db_connect_id) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function insert_id($query_id = false)
    {
        return $this->db_connect_id ? mysqli_insert_id($this->db_connect_id) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function fetch_row($query_id)
    {
        return $query_id ? mysqli_fetch_row($query_id) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function fetch_assoc($query_id)
    {
        return $query_id ? mysqli_fetch_assoc($query_id) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function fetch_array($query_id)
    {
        return $query_id ? mysqli_fetch_array($query_id) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function fetch_object($query_id)
    {
        return $query_id ? mysqli_fetch_object($query_id) : false;
    }

    /**
     * @param mixed $string
     */
    public function real_escape_string($string)
    {
        if ( ! $this->db_connect_id) {
            return _class('db')->_mysql_escape_mimic($string);
        }
        if ($string === null) {
            return 'NULL';
        } elseif (is_float($string)) {
            return str_replace(',', '.', $string);
        } elseif (is_int($string)) {
            return $string;
        } elseif (is_bool($string)) {
            return (int) $string;
        }
        return mysqli_real_escape_string($this->db_connect_id, $string);
    }

    /**
     * @param mixed $query_id
     */
    public function free_result($query_id)
    {
        if ($query_id) {
            mysqli_free_result($query_id);
            // We need this for compatibility, because mysqli_free_result() returns "void"
            return true;
        }
        return true;
    }


    public function error()
    {
        if (mysqli_connect_errno()) {
            return [
                'message' => mysqli_connect_error(),
                'code' => mysqli_connect_errno(),
            ];
        } elseif ($this->db_connect_id) {
            return [
                'message' => mysqli_error($this->db_connect_id),
                'code' => mysqli_errno($this->db_connect_id),
            ];
        } elseif ($this->_connect_error) {
            return [
                'message' => 'YF: Connect error: ' . $this->_connect_error,
                'code' => '9999',
            ];
        }
        return false;
    }


    public function begin()
    {
        return $this->query('START TRANSACTION');
    }


    public function commit()
    {
        return $this->query('COMMIT');
    }


    public function rollback()
    {
        return $this->query('ROLLBACK');
    }

    /**
     * @param mixed $count
     * @param mixed $offset
     */
    public function limit($count, $offset)
    {
        $sql = '';
        if ($count > 0) {
            $offset = ($offset > 0) ? $offset : 0;
            $sql .= 'LIMIT ' . ($offset ? $offset . ', ' : '') . $count;
        }
        return $sql;
    }

    /**
     * @param mixed $data
     */
    public function escape_key($data)
    {
        return '`' . trim($data, '`') . '`';
    }

    /**
     * @param mixed $data
     */
    public function escape_val($data)
    {
        if ($data === null) {
            return 'NULL';
        }
        return '\'' . $data . '\'';
    }


    public function get_server_version()
    {
        return $this->db_connect_id ? mysqli_get_server_info($this->db_connect_id) : false;
    }


    public function get_host_info()
    {
        return $this->db_connect_id ? mysqli_get_host_info($this->db_connect_id) : false;
    }

    /**
     * @param mixed $query
     */
    public function prepare($query)
    {
        return mysqli_prepare($this->db_connect_id, $query);
    }

    /**
     * @param mixed $stmt
     * @param mixed $data
     */
    public function bind_params($stmt, $data = [])
    {
        $types_string = '';
        foreach ((array) $data as $k => $v) {
            $var_type = substr($k, 0, 1);
            $types_string .= $var_type;
            $params[] = '$data[\'' . $k . '\']';
        }
        return eval('return mysqli_stmt_bind_param($stmt, \'' . $types_string . '\', ' . implode(',', $params) . ');');
    }

    /**
     * @param mixed $stmt
     */
    public function execute($stmt)
    {
        return mysqli_stmt_execute($stmt);
    }

    /**
     * Query with preparing.
     * @param mixed $query
     * @param mixed $data
     */
    public function query_fetch_prepared($query, $data = [])
    {
        $stmt = mysqli_prepare($this->db_connect_id, $query);
        $this->bind_params($stmt, $data);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $result);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }


    public function get_last_warnings()
    {
        if ( ! $this->db_connect_id) {
            return false;
        }
        $q = $this->query('SHOW WARNINGS');
        if ( ! $q) {
            return false;
        }
        $warnings = [];
        // Example: Warning (1264): Data truncated for column 'Name' at row 1
        while ($a = $this->fetch_assoc($q)) {
            $warnings[] = $a;
        }
        return $warnings;
    }


    public function get_last_query_info()
    {
        if ( ! $this->db_connect_id) {
            return false;
        }
        // Example: Records: 42 Deleted: 0 Skipped: 0 Warnings: 0
        return mysqli_info($this->db_connect_id);
    }
}
