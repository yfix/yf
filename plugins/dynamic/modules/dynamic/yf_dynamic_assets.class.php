<?php


class yf_dynamic_assets
{
    public function __construct()
    {
        $this->_parent = module('dynamic');
    }

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
     * Display dynamic|on-the-fly asset content (CSS|JS).
     * @param mixed $type
     */
    public function asset($type = '')
    {
        session_write_close();
        no_graphics(true);
        $name = strtolower(preg_replace('~[^a-z0-9_-]+~ims', '', trim($_GET['id'])));
        $type = strtolower(preg_replace('~[^a-z0-9_-]+~ims', '', trim($type ?: $_GET['page'])));
        if ( ! strlen($name) || ! strlen($type) || ! in_array($type, ['css', 'js', 'jquery', 'ng'])) {
            _404();
            exit();
        }
        $class_assets = _class('assets');

        $content_types = [
            'js' => 'text/javascript',
            'css' => 'text/css',
        ];
        $content_types['jquery'] = $content_types['js'];
        $content_types['ng'] = $content_types['js'];

        if (in_array($type, ['css', 'js'])) {
            $content = $class_assets->get_asset($name, $type);
            foreach ($content as $v) {
                $ctype = $class_assets->detect_content_type($type, $v);
                $_out = '';
                if ($ctype === 'inline') {
                    $_out = $v;
                } elseif ($ctype === 'file') {
                    $_out = file_get_contents($v);
                } elseif ($ctype === 'url') {
                    $_out = file_get_contents($v);
                }
                if (DEBUG_MODE) {
                    $out[] = '/* DEBUG: asset: ' . $name . ', type: ' . $type . ', ctype: ' . $ctype . ', length: ' . strlen($_out) . ', src: ' . $v . ' */';
                }
                $out[] = $_out;
            }
        }
        $out = implode(PHP_EOL . PHP_EOL, $out);

        $now = time();
        $max_age = 3600;
        header('Content-Type: ' . $content_types[$type]);
        header('Content-Length: ' . strlen($out));
        header('Cache-Control: max-age=3600, must-revalidate');
        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', $now + $max_age));
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', $now));
        header('X-Robots-Tag: noindex,nofollow,noarchive,nosnippet');
        header_remove('Pragma');
        header_remove('Set-Cookie');

        echo $out;
        exit;
    }


    public function asset_css()
    {
        return $this->asset('css');
    }


    public function asset_js()
    {
        return $this->asset('js');
    }


    public function asset_jquery()
    {
        return $this->asset('jquery');
    }


    public function asset_ng()
    {
        return $this->asset('ng');
    }
}
