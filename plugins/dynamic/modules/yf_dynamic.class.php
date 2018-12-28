<?php


class yf_dynamic
{
    /** @var bool */
    public $ERROR_IMAGE_INTERNAL = false;
    /** @var bool */
    public $ALLOW_LANG_CHANGE = true;
    /** @var bool */
    public $VARS_IGNORE_CASE = true;
    /** @var int Quantity of finded users by user search (for 'find_users' function) */
    public $USER_RESULTS_LIMIT = 20;
    /** @var array */
    public $AJAX_VALIDATE_ALLOWED = [
        'user.login',
        'user.email',
        'user.nick',
        'captcha',
    ];

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
     * Default method.
     */
    public function show()
    {
        no_graphics(true);
        return _404();
    }

    /**
     * Execute selected php func.
     */
    public function php_func()
    {
        no_graphics(true);
        if ( ! main()->CONSOLE_MODE) {
            exit('No direct access to method allowed');
        }
        $params = common()->get_console_params();

        $func = preg_replace('#[^a-z0-9\_]+#', '', substr(trim($params['func']), 0, 32));
        if (function_exists($func)) {
            echo $func($params['name']);
        } else {
            echo 'Error: no such func: ' . $func;
        }

        exit();
    }

    /**
     * Display image with error text inside.
     */
    public function _show_error_image()
    {
        $func = __FUNCTION__;
        return _class('dynamic_image', 'modules/dynamic/')->$func();
    }

    /**
     * Display 'dynamic' image (block hotlinking).
     */
    public function image()
    {
        $func = __FUNCTION__;
        return _class('dynamic_image', 'modules/dynamic/')->$func();
    }

    /**
     * Display dynamic|on-the-fly asset content (CSS|JS).
     * @param mixed $type
     */
    public function asset($type = '')
    {
        $func = __FUNCTION__;
        return _class('dynamic_assets', 'modules/dynamic/')->$func();
    }


    public function asset_css()
    {
        $func = __FUNCTION__;
        return _class('dynamic_assets', 'modules/dynamic/')->$func();
    }


    public function asset_js()
    {
        $func = __FUNCTION__;
        return _class('dynamic_assets', 'modules/dynamic/')->$func();
    }


    public function asset_jquery()
    {
        $func = __FUNCTION__;
        return _class('dynamic_assets', 'modules/dynamic/')->$func();
    }


    public function asset_ng()
    {
        $func = __FUNCTION__;
        return _class('dynamic_assets', 'modules/dynamic/')->$func();
    }

    /**
     * Change current user language.
     */
    public function change_lang()
    {
        $func = __FUNCTION__;
        return _class('dynamic_lang', 'modules/dynamic/')->$func();
    }

    /**
     * Display form.
     */
    public function change_lang_form()
    {
        return $this->_change_lang_form();
    }

    /**
     * BLock with change lang and skin selects.
     */
    public function _change_lang_form()
    {
        $func = __FUNCTION__;
        return _class('dynamic_lang', 'modules/dynamic/')->$func();
    }

    /**
     * AJAX-based method save current locale variable.
     */
    public function save_locale_var()
    {
        $func = __FUNCTION__;
        return _class('dynamic_edit', 'modules/dynamic/')->$func();
    }

    /**
     * AJAX-based method edit selected template for the current locale.
     */
    public function edit_locale_stpl()
    {
        $func = __FUNCTION__;
        return _class('dynamic_edit', 'modules/dynamic/')->$func();
    }

    /**
     * AJAX-based method edit selected tooltip.
     */
    public function edit_tip()
    {
        $func = __FUNCTION__;
        return _class('dynamic_edit', 'modules/dynamic/')->$func();
    }

    /**
     * Show bookmarks method.
     */
    public function show_bookmarks()
    {
        return _class('graphics_bookmarks', 'classes/graphics/')->_show_bookmarks_extended();
    }

    /**
     * Show rss method.
     */
    public function show_rss()
    {
        return _class('graphics_bookmarks', 'classes/graphics/')->_show_rss_extended();
    }

    /**
     * find users over nick or email.
     */
    public function find_users()
    {
        $func = __FUNCTION__;
        return _class('dynamic_find', 'modules/dynamic/')->$func();
    }

    /**
     * find users over nick or email.
     */
    public function find_ids()
    {
        $func = __FUNCTION__;
        return _class('dynamic_find', 'modules/dynamic/')->$func();
    }


    public function captcha_image()
    {
        return _class('captcha')->show_image();
    }


    public function ajax_validate()
    {
        $func = __FUNCTION__;
        return _class('dynamic_validate', 'modules/dynamic/')->$func();
    }

    /**
     * Output sample placeholder image, useful for designing wireframes and prototypes.
     */
    public function placeholder()
    {
        $func = __FUNCTION__;
        return _class('dynamic_image', 'modules/dynamic/')->$func();
    }

    /**
     * Helper to output placeholder image, by default output is data/image.
     * @param mixed $extra
     */
    public function placeholder_img($extra = [])
    {
        $func = __FUNCTION__;
        return _class('dynamic_image', 'modules/dynamic/')->$func($extra);
    }

    /**
     * @param mixed $extra
     */
    public function preview($extra = [])
    {
        $func = __FUNCTION__;
        return _class('dynamic_preview', 'modules/dynamic/')->$func($extra);
    }
}
