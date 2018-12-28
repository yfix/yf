<?php

/**
 * Resultset storage for model.
 */
class yf_model_result
{
    protected $_model = null;

    /**
     * @param mixed $data
     * @param mixed $model
     */
    public function __construct($data, $model)
    {
        $this->_set_data($data);
        $this->_model = $model;
    }

    /**
     * @param mixed $name
     */
    public function __get($name)
    {
        if ($name !== 'model' && ! isset($this->$name)) {
            return $this->_model->$name;
        }
        return $this->$name;
    }

    /**
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        $this->_sync_model_data();
        return call_user_func_array([$this->_model, $name], $args);
    }

    /**
     * @param mixed $data
     */
    public function _set_data($data)
    {
        foreach ((array) $data as $k => $v) {
            $first = substr($k, 0, 1);
            if (ctype_alpha($first)) {
                $this->$k = $v;
            }
        }
    }


    public function _sync_model_data()
    {
        foreach (get_object_vars($this) as $var => $value) {
            if (substr($var, 0, 1) === '_') {
                continue;
            }
            $this->_model->$var = $value;
        }
    }


    public function _get_model()
    {
        return $this->_model;
    }
}
