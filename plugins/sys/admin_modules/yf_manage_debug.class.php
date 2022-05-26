<?php

class yf_manage_debug
{
    public function _get_debug_files()
    {
        return _class('dir')->scan_fast(APP_PATH . 'debug/');
    }

    public function show()
    {
        $files = $this->_get_debug_files();
        rsort($files);
        $data = [];
        foreach ($files as $f) {
            $_id = substr(basename($f), 0, -strlen('.json'));
            $data[$_id] = [
                'id' => $_id,
                'date' => filemtime($f),
                'size' => filesize($f),
            ];
        }
        return table($data, ['condensed' => true, 'hide_empty' => true, 'pager_records_on_page' => 100])
            ->text('id', ['desc' => 'key', 'link' => url('/@object/view/%d')])
            ->text('size')
            ->date('date', '', ['format' => 'long']);
    }

    public function view()
    {
        $id = preg_replace('~[^a-z0-9_]~i', '', $_GET['id'] ?? '');
        $path = APP_PATH . 'debug/' . $id . '.json';
        if ( ! strlen($id) || ! file_exists($path)) {
            return trigger_error('Debug info not exists: ' . $id, E_USER_WARNING);
        }
        $data = json_decode(file_get_contents($path), JSON_OBJECT_AS_ARRAY);
        $meta = [];
        if (isset($data['meta'])) {
            $meta = $data['meta'];
            $data = $data['data'];
        }
        $body[] = _class('html')->tabs($data, [
            'selected' => 'DEBUG_YF',
            'no_auto_desc' => 1,
            'vertical_mode' => 1,
        ]);

        return '<h3>' . $id . '</h3>' .
            ($meta ? json_encode($meta) : '') .
            implode($body);
    }
}
