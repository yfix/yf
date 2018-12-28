<?php

class yf_assets_filter_jsmin
{
    /**
     * @param mixed $in
     * @param mixed $params
     */
    public function apply($in, $params = [])
    {
        require_php_lib('jsmin');
        if ( ! class_exists('\JSMin')) {
            throw new Exception('Assets: class \JSMin not found');
            return $in;
        }
        return \JSMin::minify($in);
    }
}
