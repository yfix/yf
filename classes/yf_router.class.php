<?php

/**
 * Incoming requests router.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_router
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
     * Method that allows to change standard tasks mapping (if needed).
     */
    public function _route_request()
    {
        /* // Map example
        if ($_GET['object'] == 'forum') {
            $_GET = array();
            $_GET['object'] = 'gallery';
            $_GET['action'] = 'show';
        }
        */
        // Custom routing for static pages (eq. for URL like /terms/ instead of /static_pages/show/terms/)
        if ( ! main()->STATIC_PAGES_ROUTE_TOP || MAIN_TYPE_ADMIN) {
            return false;
        }
        $_user_modules = main()->get_data('user_modules');
        // Do not override existing modules
        if (isset($_user_modules[$_GET['object']])) {
            return false;
        }
        $static_pages_names = main()->get_data('static_pages_names');
        $replaced_obj = str_replace('_', '-', $_GET['object']);
        if (in_array($_GET['object'], (array) $static_pages_names)) {
            $_GET['id'] = $_GET['object'];
            $_GET['object'] = 'static_pages';
            $_GET['action'] = 'show';
        } elseif (in_array($replaced_obj, (array) $static_pages_names)) {
            $_GET['id'] = $replaced_obj;
            $_GET['object'] = 'static_pages';
            $_GET['action'] = 'show';
        }
    }
}
