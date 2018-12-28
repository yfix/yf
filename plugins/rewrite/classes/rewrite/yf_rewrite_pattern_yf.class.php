<?php

/**
 * Default YF rewrite pattern.
 */
class yf_rewrite_pattern_yf
{
    public $allowed_url_params = [
        'utm_source',
        'utm_medium',
        'utm_content',
        'utm_campaign',
        'utm_term',
    ];

    /**
     * Build url.
     * @param mixed $a
     * @param mixed $class_rewrite
     */
    public function _build($a, $class_rewrite)
    {
        $u = false;
        if ( ! empty($class_rewrite->BUILD_RULES)) {
            foreach ((array) $class_rewrite->BUILD_RULES as $func) {
                $is_last = true;
                $u = $func($a, $class_rewrite, $is_last);
                if ($u && $is_last) {
                    break;
                }
            }
        }
        if ( ! $u) {
            if ($a['task'] == 'login' || $a['task'] == 'logout') {
                $u = $a['task'];
                unset($a['task']);
                if ($a['id']) {
                    $u .= '/' . $a['id'];
                    unset($a['id']);
                }
            } elseif ($a['object'] === 'static_pages' && in_array($a['id'], $this->_get_static_pages_names())) {
                $u = $a['id'];
            } else {
                $u = [];
                if ( ! empty($a['object'])) {
                    $u[] = $a['object'];
                    if (empty($a['action'])) {
                        $a['action'] = 'show';
                    }
                    // Make urls shorter if default action found.
                    if ($a['action'] != 'show') {
                        $u[] = $a['action'];
                    }
                    if ( ! empty($a['id'])) {
                        $u[] = $a['id'];
                    }
                    // When only page passed, without id, we set id=0
                    if ( ! empty($a['page'])) {
                        if (empty($a['id'])) {
                            $u[] = 0;
                        }
                        $u[] = $a['page'];
                    }
                }
                $u = implode('/', $u);
            }
        }
        $arr = $a;
        $fragment = $arr['fragment'];
        $lang = $arr['lang'];
        unset($arr['object'], $arr['action'], $arr['host'], $arr['port'], $arr['id'], $arr['page'], $arr['fragment'], $arr['lang']);
        //		foreach ((array)$class_rewrite->allowed_url_params as $name) {
        //			if (isset($_GET[$name]) && preg_match('~^[a-z0-9_-]+$~i', $_GET[$name])) {
        //				$arr[$name] = $_GET[$name];
        //			}
        //		}
        if ( ! empty($arr)) {
            $u .= (strpos($u, '?') === false ? '?' : '&') . urldecode(http_build_query($arr));
        }
        if ($fragment) {
            $u .= '#' . $fragment;
        }
        if (strlen($lang) === 2) {
            $u = $lang . '/' . $u;
        }
        if ($class_rewrite->USE_WEB_PATH) {
            $url = WEB_PATH;
        } else {
            $url = 'http://' . $a['host'] . ($a['port'] && ! in_array($a['port'], ['80', '443']) ? ':' . $a['port'] : '') . '/';
        }
        return $class_rewrite->_correct_protocol($url . $u);
    }

    /**
     * Parse url into GET params.
     * @param mixed $host
     * @param mixed $url
     * @param mixed $query
     * @param mixed $url_str
     * @param mixed $class_rewrite
     */
    public function _parse($host, $url, $query, $url_str, $class_rewrite)
    {
        $s = '';
        if (false !== strpos($url[0], '%')) {
            $url[0] = urldecode($url[0]);
        }
        if ( ! empty($class_rewrite->PARSE_RULES)) {
            foreach ((array) $class_rewrite->PARSE_RULES as $func) {
                $is_last = true;
                $s = $func($url, $query, $host, $class_rewrite, $is_last);
                if ($s && $is_last) {
                    break;
                }
            }
        }
        if ( ! $s) {
            $static_pages = $this->_get_static_pages_names();
            // Examples: /login    /logout
            if ($url[0] == 'login' || $url[0] == 'logout') {
                $s = 'task=' . $url[0];
                if (isset($url[1])) {
                    $s .= '&id=' . $url[1];
                    unset($url[1]);
                }
            } elseif (in_array($url[0], $static_pages)) {
                $s = 'object=static_pages&id=' . $url[0];
            // Examples: /table2_test/0/5,  where 5 - page number
            } elseif ( ! empty($url[0]) && is_numeric($url[1]) && is_numeric($url[2])) {
                $s = 'object=' . $url[0] . '&action=show';
                $url[3] = $url[2]; // page
                $url[2] = $url[1]; // id
            // Examples: /user_profile/5
            } elseif ( ! empty($url[0]) && is_numeric($url[1])) {
                $s = 'object=' . $url[0] . '&action=show';
                $url[2] = $url[1]; // id
            // Examples: /test/oauth
            } elseif ( ! empty($url[0]) && ! empty($url[1])) {
                $s = 'object=' . $url[0] . '&action=' . $url[1];
            // Examples: /test/  /test
            } elseif ( ! empty($url[0])) {
                $s = 'object=' . $url[0] . '&action=show';
            // Examples: define('SITE_DEFAULT_PAGE', './?object=index&action=some_action')
            } elseif (defined('SITE_DEFAULT_PAGE')) {
                $s = ltrim(SITE_DEFAULT_PAGE, './?');
            // Default inner url
            } else {
                $s = 'object=home_page&action=show';
            }
        }
        foreach ((array) $class_rewrite->allowed_url_params as $name) {
            if (isset($_GET[$name]) && preg_match('~^[a-z0-9_-]+$~i', $_GET[$name])) {
                $query[$name] = $_GET[$name];
            }
        }
        if (isset($url[2]) && strlen($url[2])) {
            $s .= '&id=' . $url[2];
        }
        if (isset($url[3]) && strlen($url[3])) {
            $s .= '&page=' . $url[3];
        }
        if ($s != '') {
            parse_str($s, $arr);
            foreach ((array) $query as $k => $v) {
                $arr[$k] = $v;
            }
        }
        if ( ! isset($class_rewrite->_ARGS_DIRTY)) {
            $class_rewrite->_ARGS_DIRTY = $arr;
            main()->_ARGS_DIRTY = &$class_rewrite->_ARGS_DIRTY;
        }
        // Filter bad symbols
        $cleanup_regex = '~[^a-z0-9_-]+~ims';
        $arr['object'] = trim(preg_replace($cleanup_regex, '', trim($arr['object'])), '-');
        $arr['action'] = trim(preg_replace($cleanup_regex, '', trim($arr['action'])), '-_');
        return $arr;
    }


    public function _get_static_pages_names()
    {
        if ( ! isset($this->_static_pages)) {
            $main = main();
            if ( ! $main->STATIC_PAGES_ROUTE_TOP) {
                $this->_static_pages = [];
            } else {
                $this->_static_pages = $main->get_data('static_pages_names');
            }
        }
        return $this->_static_pages;
    }
}
