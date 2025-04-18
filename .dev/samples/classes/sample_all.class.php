<?php

class sample_all
{
    /***/
    public function _init()
    {
        _class('core_api')->add_syntax_highlighter();
        $this->lang = conf('language');
    }

    /***/
    public function _hook_side_column()
    {
        $items = [];
        $url = url('/@object');
        $methods = $this->_get_texts();
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
                'name' => $name/*. (!in_array($name, $sample_methods) ? ' <sup class="text-error text-danger"><small>TODO</small></sup>' : '')*/,
                'link' => url('/@object/@action/' . urlencode($name)),
            ];
        }
        return _class('html')->navlist($items);
    }

    /***/
    public function show()
    {
        $docs = _class('docs');
        $dir = $docs->docs_dir . '/' . $this->lang . '/';
        $dir_len = strlen($dir);
        $ext = '.stpl';
        $ext_len = strlen($ext);

        $name = preg_replace('~[^a-z0-9/_-]+~ims', '', $_GET['id']);
        if ( ! strlen($name)) {
            $all = $this->_get_texts($dir);
            ksort($all);
            $name = current($all);
        }
        $replace = [];
        if (strlen($name)) {
            $f = $dir . $name . '.stpl';
            if ( ! file_exists($f)) {
                return _404('Not found');
            }
            return '<section class="page-contents">' . tpl()->parse_string(file_get_contents($f), $replace, 'docs_all_' . $name) . '</section>';
        }
    }

    /***/
    public function _get_texts($dir = '')
    {
        $dir = $dir ?: _class('docs')->docs_dir . '/' . $this->lang . '/';
        $dir_len = strlen($dir);
        $ext = '.stpl';
        $ext_len = strlen($ext);
        $names = [];
        foreach ((array) _class('dir')->rglob($dir) as $path) {
            if (substr($path, -$ext_len) !== $ext) {
                continue;
            }
            $name = substr($path, $dir_len, -$ext_len);
            $names[$name] = $name;
        }
        return $names;
    }
}
