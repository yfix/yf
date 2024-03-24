<?php

/**
 * Abstraction layer on basket inner representation
 * with basic manipulation methods: get/set/del/clean...
 */
class yf_shop__basket_api
{
    public $storage_name = 'shop_basket_storage';

    /**
     * API Wrapper, allowing to chain itself.
     */
    public function _basket_api()
    {
        return $this;
    }

    /***/
    public function get($k, $k2 = false)
    {
        if ( ! empty($k2)) {
            return $_SESSION[$storage_name][$k][$k2];
        }
        return $_SESSION[$storage_name][$k];
    }

    /***/
    public function get_all()
    {
        return $_SESSION[$storage_name];
    }

    /***/
    public function set($k, $v)
    {
        $_SESSION[$storage_name][$k] = $v;
        return true;
    }

    /***/
    public function del($k)
    {
        unset($_SESSION[$storage_name][$k]);
        return true;
    }

    /***/
    public function clean()
    {
        $_SESSION[$storage_name] = [];
        return true;
    }
}
