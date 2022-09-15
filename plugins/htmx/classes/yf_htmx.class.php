<?php

class yf_htmx
{
    public $is_hx   = null;

    public $req = [
    'request'      => false,
    'boosted'      => false,
    'target'       => '',
    'trigger'      => '',
    'trigger_name' => '',
    'current_url'  => '',
  ];

    public $current_url  = null;

    public $target       = null;
    public $trigger      = null;
    public $trigger_name = null;
    public $boosted      = null;

    public $_api;

    public function _init()
    {
        $this->_api = _class('api');
        $this->_api->JSON_VULNERABILITY_PROTECTION = false;
        $this->_is_hx();
        $this->target  = @$_SERVER['HTTP_HX_TARGET'];
        $this->boosted = @$_SERVER['HTTP_HX_BOOSTED'];
        $this->_get_req();
    }

    public function _is_hx()
    {
        $r = (bool) @$_SERVER['HTTP_HX_REQUEST'];
        $this->is_hx = $r;
        return  $r;
    }

    public function _get_req()
    {
        foreach ($this->req as $k => &$v) {
            $key = 'HTTP_HX_' . strtoupper($k);
            if (isset($_SERVER[$key])) {
                $v = $_SERVER[$key];
            }
        }
    }

    public function _firewall()
    {
        if ( ! $this->is_hx) {
            $this->_reject();
        }
        return  $this->is_hx;
    }

    public function _reject()
    {
        no_graphics(true);
        $this->_api->_reject();
    }

    public function _forbidden()
    {
        no_graphics(true);
        $this->_api->_forbidden();
    }

    public function _error()
    {
        no_graphics(true);
        $this->_api->_error();
    }

    public function _redirect($url)
    {
        no_graphics(true);
        header('HX-Redirect: ' . $url);
        exit;
    }

    public function _redirect_login()
    {
        $this->_redirect('/login/');
    }

    public function _refresh()
    {
        no_graphics(true);
        header('HX-Refresh: true');
        exit;
    }

    public function _robot_none()
    {
        return  $this->_api->_robot_none();
    }

    public function _cache($options = null)
    {
        return  $this->_api->_cache($options);
    }

    public function _cache_none()
    {
        return  $this->_api->_cache(['no-store' => true]);
    }

    public function _result($r)
    {
        no_graphics(true);
        $this->_robot_none();
        echo $r;
        exit;
    }
}
