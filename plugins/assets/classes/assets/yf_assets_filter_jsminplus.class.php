<?php

class yf_assets_filter_jsminplus
{
    /**
     * @param mixed $in
     * @param mixed $params
     */
    public function apply($in, $params = [])
    {
        require_php_lib('jsminplus');
        if ( ! class_exists('\JSMinPlus')) {
            throw new Exception('Assets: class \JSMinPlus not found');
            return $in;
        }
        return \JSMinPlus::minify($in);
    }
}
