<?php

load('db_driver', '', 'classes/db/');
abstract class yf_db_driver_pdo extends yf_db_driver
{
    /**
     * @param mixed $name
     */
    public function select_db($name)
    {
        return $this->db_connect_id ? (bool) $this->query('USE ' . $name) : false;
    }


    public function close()
    {
        if ($this->db_connect_id) {
            $this->db_connect_id = null;
            return true;
        }
        return false;
    }

    /**
     * @param mixed $query
     */
    public function query($query)
    {
        if ( ! $this->db_connect_id) {
            return false;
        }
        $this->_last_query_error = null;
        try {
            $result = $this->db_connect_id->query($query);
        } catch (PDOException $ex) {
            $this->_query_error = $ex;
        }
        $this->_last_query_id = $result;
        return $result;
    }

    /**
     * @param mixed $query_id
     */
    public function num_rows($query_id)
    {
        return $query_id ? $query_id->rowCount() : false;
    }

    /**
     * @param mixed $query_id
     */
    public function affected_rows($query_id = false)
    {
        return $this->_last_query_id ? $this->_last_query_id->rowCount() : false;
    }

    /**
     * @param mixed $query_id
     */
    public function insert_id($query_id = false)
    {
        return $this->db_connect_id->lastInsertId();
    }

    /**
     * @param mixed $query_id
     */
    public function fetch_row($query_id)
    {
        return $query_id ? $query_id->fetch(PDO::FETCH_NUM) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function fetch_assoc($query_id)
    {
        return $query_id ? $query_id->fetch(PDO::FETCH_ASSOC) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function fetch_array($query_id)
    {
        return $query_id ? $query_id->fetch(PDO::FETCH_BOTH) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function fetch_object($query_id)
    {
        return $query_id ? $query_id->fetch(PDO::FETCH_OBJ) : false;
    }

    /**
     * @param mixed $query_id
     */
    public function free_result($query_id = false)
    {
        if ( ! $query_id) {
            return false;
        }
        $query_id = null;
        return true;
    }


    public function error()
    {
        if ($this->db_connect_id) {
            $info = $this->db_connect_id->errorInfo();
            return [
                'message' => $info[2],
                'code' => $info[1],
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
        return $this->db_connect_id->beginTransaction();
    }


    public function commit()
    {
        return $this->db_connect_id->commit();
    }


    public function rollback()
    {
        return $this->db_connect_id->rollBack();
    }


    public function get_server_version()
    {
        return $this->db_connect_id ? $this->db_connect_id->getAttribute(PDO::ATTR_SERVER_VERSION) : false;
    }


    public function get_host_info()
    {
        return $this->db_connect_id ? $this->db_connect_id->getAttribute(PDO::ATTR_SERVER_INFO) : false;
    }
}
