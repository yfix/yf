<?php

load('db_driver_pdo', '', 'classes/db/');
class yf_db_driver_pdo_pgsql extends yf_db_driver_pdo
{
    public $_connect_error;
    /** @var @conf_skip */
    public $db_connect_id = null;


    public function __construct(array $params)
    {
        if ( ! extension_loaded('pdo_pgsql')) {
            trigger_error('YF PDO PgSQL db driver require missing php extension pdo_pgsql', E_USER_WARNING);
            return false;
        }
        $params['port'] = ($params['port'] ?? null) ?: 5432;
        $this->params = $params;
        $this->connect();
        return $this->db_connect_id;
    }


    public function connect()
    {
        $dsn = 'pgsql:host=' . $this->params['host'];
        if ($this->params['port'] && $this->params['port'] != $this->DEF_PORT) {
            $dsn .= ';port=' . $this->params['port'];
        }
        $dsn .= ';dbname=' . (($this->params['name'] ?? null) ?: 'template1');
        $attrs = [];
        $attrs[PDO::ATTR_TIMEOUT] = 2;
        if ($this->params['persist']) {
            $attrs[PDO::ATTR_PERSISTENT] = true;
        }
        $this->db_connect_id = new PDO($dsn, $this->params['user'], $this->params['pswd'], $attrs);
        $pdo = &$this->db_connect_id;

        if ( ! $this->db_connect_id) {
            $this->_connect_error = 'cannot_connect_to_server';
            return $this->db_connect_id;
        }
        return $this->db_connect_id;
    }

    /**
     * @param mixed $string
     */
    public function real_escape_string($string)
    {
        if ($string === null) {
            return 'NULL';
        }
        return addslashes($string);
    }

    /**
     * @param mixed $count
     * @param mixed $offset
     */
    public function limit($count, $offset)
    {
        if ($count > 0) {
            return 'LIMIT ' . $count . ($offset > 0 ? ' OFFSET ' . $offset : '');
        }
        return false;
    }

    /**
     * @param mixed $data
     */
    public function escape_key($data)
    {
        return '"' . trim($data, '"') . '"';
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
}
