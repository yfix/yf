<?php

/**
 * Core input.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_input
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

    /**
     * Helper to get/set GET vars.
     * @param null|mixed $key
     * @param null|mixed $val
     */
    public function get($key = null, $val = null)
    {
        if ($val !== null) {
            $_GET[$key] = $val;
        }
        if (DEBUG_MODE && function_exists('debug')) {
            debug('input_' . __FUNCTION__ . '[]', [
                'name' => $key,
                'val' => $val,
                'op' => $val !== null ? 'set' : 'get',
                'trace' => main()->trace_string(),
            ]);
        }
        return $key === null ? $_GET : $_GET[$key];
    }

    /**
     * Helper to get/set POST vars.
     * @param null|mixed $key
     * @param null|mixed $val
     */
    public function post($key = null, $val = null)
    {
        if ($val !== null) {
            $_POST[$key] = $val;
        }
        if (DEBUG_MODE && function_exists('debug')) {
            debug('input_' . __FUNCTION__ . '[]', [
                'name' => $key,
                'val' => $val,
                'op' => $val !== null ? 'set' : 'get',
                'trace' => main()->trace_string(),
            ]);
        }
        return $key === null ? $_POST : $_POST[$key];
    }

    /**
     * Helper to get/set SESSION vars.
     * @param null|mixed $key
     * @param null|mixed $val
     */
    public function session($key = null, $val = null)
    {
        if ($val !== null) {
            $_SESSION[$key] = $val;
        }
        if (DEBUG_MODE && function_exists('debug')) {
            debug('input_' . __FUNCTION__ . '[]', [
                'name' => $key,
                'val' => $val,
                'op' => $val !== null ? 'set' : 'get',
                'trace' => main()->trace_string(),
            ]);
        }
        return $key === null ? $_SESSION : $_SESSION[$key];
    }

    /**
     * Helper to get/set SERVER vars.
     * @param null|mixed $key
     * @param null|mixed $val
     */
    public function server($key = null, $val = null)
    {
        if ($val !== null) {
            $_SERVER[$key] = $val;
        }
        if (DEBUG_MODE && function_exists('debug')) {
            debug('input_' . __FUNCTION__ . '[]', [
                'name' => $key,
                'val' => $val,
                'op' => $val !== null ? 'set' : 'get',
                'trace' => main()->trace_string(),
            ]);
        }
        return $key === null ? $_SERVER : $_SERVER[$key];
    }

    /**
     * Helper to get/set COOKIE vars.
     * @param null|mixed $key
     * @param null|mixed $val
     */
    public function cookie($key = null, $val = null)
    {
        if ($val !== null) {
            // TODO: check and use main() settings for cookies
            setcookie($key, $val);
        }
        if (DEBUG_MODE && function_exists('debug')) {
            debug('input_' . __FUNCTION__ . '[]', [
                'name' => $key,
                'val' => $val,
                'op' => $val !== null ? 'set' : 'get',
                'trace' => main()->trace_string(),
            ]);
        }
        return $key === null ? $_COOKIE : $_COOKIE[$key];
    }

    /**
     * Helper to get/set ENV vars.
     * @param null|mixed $key
     * @param null|mixed $val
     */
    public function env($key = null, $val = null)
    {
        if ($val !== null) {
            $_ENV[$key] = $val;
        }
        if (DEBUG_MODE && function_exists('debug')) {
            debug('input_' . __FUNCTION__ . '[]', [
                'name' => $key,
                'val' => $val,
                'op' => $val !== null ? 'set' : 'get',
                'trace' => main()->trace_string(),
            ]);
        }
        return $key === null ? $_ENV : $_ENV[$key];
    }

    /**
     * Checks whether current page was requested with POST method.
     */
    public function is_post()
    {
        return (bool) main()->is_post();
    }

    /**
     * Checks whether current page was requested with AJAX.
     */
    public function is_ajax()
    {
        return (bool) main()->is_ajax();
    }

    /**
     * Checks whether current page was requested from console.
     */
    public function is_console()
    {
        return (bool) main()->is_console();
    }

    /**
     * Checks whether current page is a redirect.
     */
    public function is_redirect()
    {
        return (bool) main()->is_redirect();
    }
}
