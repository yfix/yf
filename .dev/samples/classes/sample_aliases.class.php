<?php

class sample_aliases
{
    /***/
    public function _init()
    {
        _class('core_api')->add_syntax_highlighter();
    }

    /***/
    public function _get_aliases()
    {
        $out = [];
        preg_match_all('~function\s+([a-z0-9_]+)~ims', file_get_contents(YF_PATH . 'functions/yf_aliases.php'), $m);
        foreach ((array) $m[1] as $name) {
            if (substr($name, 0, 2) === '__') {
                continue;
            }
            $out[$name] = $name;
        }
        ksort($out);
        return $out;
    }

    /***/
    public function _hook_side_column()
    {
        $items = [];
        $url = url('/@object');
        $methods = $this->_get_aliases();
        $sample_methods = get_class_methods($this);
        sort($methods);
        foreach ((array) $sample_methods as $name) {
            if (in_array($name, $methods)) {
                continue;
            }
            $methods[] = $name;
        }
        foreach ((array) $methods as $name) {
            if ($name == 'show' || substr($name, 0, 1) == '_') {
                continue;
            }
            $items[] = [
                'name' => $name . ( ! in_array($name, $sample_methods) ? ' <sup class="text-error text-danger"><small>TODO</small></sup>' : ''),
                'link' => url('/@object/@action/' . $name),
            ];
        }
        return _class('html')->navlist($items);
    }

    /***/
    public function show()
    {
        return _class('docs')->_show_for($this);
    }

    /***/
    public function a()
    {
        return a('/docs/html', 'Block me', 'fa fa-lock');
    }

    /***/
    public function tip()
    {
        return tip('This is custom text to be displayed inside tooltip, also you can use tip short names, editable from admin panel');
    }
}
