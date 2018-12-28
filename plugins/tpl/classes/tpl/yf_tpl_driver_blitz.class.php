<?php


class yf_tpl_driver_blitz
{
    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $arguments
     */
    public function __call($name, $arguments)
    {
        trigger_error(__CLASS__ . ': No method ' . $name, E_USER_WARNING);
        return false;
    }

    /**
     * Constructor.
     */
    public function _init()
    {
        if ( ! class_exists('Blitz')) {
            //			trigger_error(__CLASS__.': Blitz class not found, and it is required for this tpl driver.', E_USER_ERROR);
        }
        // TODO
    }

    /**
     * @param mixed $name
     * @param mixed $replace
     * @param mixed $params
     */
    public function parse($name, $replace = [], $params = [])
    {
        if ( ! class_exists('Blitz')) {
            return $params['string'];
        }
        if ($params['string']) {
            $view = new Blitz();
            $view->load($params['string']);
            return $view->parse($replace);
        }
        // TODO: test me and connect YF template loader
    }
}
