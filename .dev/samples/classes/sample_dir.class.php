<?php

class sample_dir
{
    /***/
    public function _init()
    {
        _class('core_api')->add_syntax_highlighter();
    }

    /***/
    public function _hook_side_column()
    {
        $items = [];
        $url = url('/@object');
        $methods = get_class_methods(_class('dir'));
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
}
