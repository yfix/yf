<?php


class yf_dynamic_preview
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
     * @param mixed $extra
     */
    public function preview($extra = [])
    {
        conf('ROBOTS_NO_INDEX', true);
        no_graphics(true);
        if (main()->USER_ID != 1) {
            return print _403('You should be logged as user 1');
        }
        // Example of url: /dynamic/preview/static_pages/29/
        $object = preg_replace('~[^a-z0-9_]+~ims', '', $_GET['id']);
        $id = preg_replace('~[^a-z0-9_]+~ims', '', $_GET['page']);
        if ( ! strlen($object)) {
            return print _403('Object is required');
        }
        $ref = $_SERVER['HTTP_REFERER'];
        $body = '';
        if (is_post() && isset($_POST['text'])) {
            $u_ref = parse_url($ref);
            $u_self = parse_url(WEB_PATH);
            $u_adm = parse_url(ADMIN_WEB_PATH);
            if ($u_ref['host'] && $u_ref['host'] == $u_self['host'] && $u_ref['host'] == $u_adm['host'] && $u_ref['path'] == $u_adm['path']) {
                $body = $_POST['text'];
            } else {
                return print _403('Preview security check not passed');
            }
        }
        if ( ! $body) {
            $q = from($object)->whereid($id);
            if ($object == 'static_pages') {
                $body = $q->one('text');
            } elseif ($object == 'tips') {
                $body = $q->one('text');
            } elseif ($object == 'faq') {
                $body = $q->one('text');
            } elseif ($object == 'news') {
                $body = $q->one('full_text');
            }
        }
        $body = '<div class="container">' . $body . '</div>';
        return print common()->show_empty_page($body);
    }
}
