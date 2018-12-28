<?php

/**
 * YF Helper for cookies.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_cookie
{
    /** @var string */
    public $EXPIRE = 0;
    /** @var string */
    public $PATH = '/';
    /** @var string */
    public $DOMAIN = '';
    /** @var string */
    public $SECURE = false;
    /** @var string */
    public $HTTPONLY = false;

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
        $main = main();
        // Import cookie settings from main class, but prefixed with "COOKIE_"
        $prefix = 'COOKIE_';
        $plen = strlen($prefix);
        foreach ($main as $k => $v) {
            if (substr($k, 0, $plen) !== $plen) {
                continue;
            }
            $name = substr($k, $plen);
            $this->$name = $v;
        }
        /*
                $cookie_life_time = conf('cookie_life_time');
                if (isset($cookie_life_time)) {
                    $this->COOKIE_LIFE_TIME = 86400 * $cookie_life_time;
                }
                if (empty($this->COOKIE_PATH)) {
                    $url_parts = @parse_url(WEB_PATH);
                    $this->COOKIE_PATH = $url_parts['path'];
                }
        */
    }

    /**
     * @param mixed $name
     */
    public function get($name)
    {
        return $_COOKIE[$name];
    }

    /**
     * @param mixed $name
     * @param mixed $value
     * @param mixed $expire
     * @param null|mixed $path
     * @param null|mixed $domain
     * @param null|mixed $secure
     * @param null|mixed $httponly
     * @param mixed $params
     */
    public function set($name, $value = '', $expire = 0, $path = null, $domain = null, $secure = null, $httponly = null, $params = [])
    {
        if (is_array($expire)) {
            $params = (array) $params + $expire;
            $expire = null;
        }
        $expire = isset($params['expire']) ? $params['expire'] : (isset($expire) ? $expire : $this->EXPIRE);
        $path = isset($params['path']) ? $params['path'] : (isset($path) ? $path : $this->PATH);
        $domain = isset($params['domain']) ? $params['domain'] : (isset($domain) ? $domain : $this->DOMAIN);
        $secure = isset($params['secure']) ? $params['secure'] : (isset($secure) ? $secure : $this->SECURE);
        $httponly = isset($params['httponly']) ? $params['httponly'] : (isset($httponly) ? $httponly : $this->HTTPONLY);
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * @param mixed $name
     * @param mixed $expire
     * @param null|mixed $path
     * @param null|mixed $domain
     * @param null|mixed $secure
     * @param null|mixed $httponly
     * @param mixed $params
     */
    public function del($name, $expire = 0, $path = null, $domain = null, $secure = null, $httponly = null, $params = [])
    {
        return $this->set($name, '', $expire, $path, $domain, $secure, $httponly, $params);
    }
}
