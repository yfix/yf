<?php

load('search_driver', '', 'classes/search/');
class yf_search_driver_sphinxsearch extends yf_search_driver
{
    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }


    public function _init()
    {
        $this->PARENT = _class('send_mail');
    }


    public function search(array $params = [], &$error_message = '')
    {
        // TODO
    }
}
