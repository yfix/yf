<?php

class yf_assets_filter_cssmin
{
    /**
     * @param mixed $in
     * @param mixed $params
     */
    public function apply($in, $params = [])
    {
        require_php_lib('cssmin');
        if ( ! class_exists('\CssMin')) {
            throw new Exception('Assets: class \CssMin not found');
            return $in;
        }
        return \CssMin::minify($in);
    }
}
