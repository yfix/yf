<?php

load('db_driver', '', 'classes/db/');
class yf_db_driver_pgsql extends yf_db_driver
{
    /** @var @conf_skip */
    public $db_connect_id = null;


    public function __construct(array $params)
    {
        if ( ! function_exists('pg_connect')) {
            trigger_error('YF PgSQL db driver require missing php extension pgsql', E_USER_ERROR);
            return false;
        }
        $this->params = $params;
        $this->connect();
        return $this->db_connect_id;
    }


    public function connect()
    {
        $dsn = 'host=' . $this->params['host'] . ' ';
        if ($this->params['port']) { // Default is 5432
            $dsn .= ' port=' . $this->params['port'] . ' ';
        }
        if (strlen($this->params['user'])) {
            $dsn .= ' user=' . $this->params['user'] . ' ';
        }
        if (strlen($this->params['pswd'])) {
            $dsn .= ' password=' . $this->params['pswd'] . ' ';
        }
        $db_name = $this->params['name'] ?: 'template1';
        $dsn .= ' dbname=' . $db_name . ' ';
        $dsn .= ' connect_timeout=5 ';
        $this->db_connect_id = $this->params['persist'] ? pg_pconnect($dsn) : pg_connect($dsn);
        if ( ! $this->db_connect_id) {
            $this->_connect_error = 'cannot_connect_to_server';
            return $this->db_connect_id;
        }
        return $this->db_connect_id;
    }


    public function close()
    {
        return $this->db_connect_id ? pg_close($this->db_connect_id) : false;
    }

    /**
     * @param mixed $query
     */
    public function query($query)
    {
        return $this->db_connect_id && strlen($query) ? pg_query($this->db_connect_id, $query) : false;
    }


    public function error()
    {
        if ($this->db_connect_id) {
            return [
                'message' => pg_last_error($this->db_connect_id),
                'code' => '8888',
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
     * @param mixed $query_id
     */
    public function num_rows($query_id)
    {
        return $query_id ? pg_numrows($query_id) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function fetch_row($query_id)
    {
        return $query_id ? pg_fetch_row($query_id) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function fetch_assoc($query_id)
    {
        return $query_id ? pg_fetch_assoc($query_id) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function fetch_array($query_id)
    {
        return $query_id ? pg_fetch_array($query_id) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function fetch_object($query_id)
    {
        return $query_id ? pg_fetch_object($query_id) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function insert_id($query_id = false)
    {
        $q = $this->query('SELECT lastval()');
        if ($q) {
            list($insert_id) = (array) $this->fetch_row($q);
            return $insert_id;
        }
        return false;
    }

    /**
     * @param mixed $query_id
     */
    public function affected_rows($query_id = false)
    {
        return $query_id ? pg_affected_rows($query_id) : false;
    }

    /**
     * @param mixed $string
     */
    public function real_escape_string($string)
    {
        if ($string === null) {
            return 'NULL';
        }
        return pg_escape_string($string);
    }

    /**
     * @param mixed $query_id
     */
    public function free_result($query_id = false)
    {
        return $query_id ? pg_freeresult($query_id) : false;
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


    public function get_server_version()
    {
        if ( ! $this->db_connect_id) {
            return false;
        }
        $version = pg_version($this->db_connect_id);
        return $version['server_version'];
    }


    public function get_host_info()
    {
        return $this->db_connect_id ? pg_host($this->db_connect_id) : false;
    }
}
