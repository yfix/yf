<?php

// Navigation bar handler
class yf_site_nav_bar
{
    /** @var string */
    public $HOOK_NAME = '_nav_bar_items';
    /** @var string */
    public $HOME_LOCATION = './';
    /** @var bool */
    public $AUTO_TRANSLATE = true;
    /** @var bool */
    public $SHOW_NAV_BAR = true;

    public $_nav_item_as_array = null;

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

    // Display navigation bar
    public function _show($return_array = false)
    {
        if ($return_array) {
            $this->_nav_item_as_array = true;
        }
        $items = [];
        // Switch between specific actions
        if (in_array($_GET['object'], ['', 'home_page'])) {
            // Empty
        } else {
            if ( ! in_array($_GET['action'], ['', 'show'])) {
                $items[] = $this->_nav_item($this->_decode_from_url($_GET['object']), './?object=' . $_GET['object']);
                $items[] = $this->_nav_item($this->_decode_from_url($_GET['action']));
            } else {
                $items[] = $this->_nav_item($this->_decode_from_url($_GET['object']));
            }
        }
        // Try to get items from hook '_nav_bar_items'
        if ( ! empty($this->HOOK_NAME)) {
            $CUR_OBJ = module($_GET['object']);
            if (is_object($CUR_OBJ) && method_exists($CUR_OBJ, $this->HOOK_NAME)) {
                $hook_params = [
                    'nav_bar_obj' => $this,
                    'items' => $items,
                ];
                $func = $this->HOOK_NAME;
                $hooked_items = $CUR_OBJ->$func($hook_params);
            }
        }
        // Do not show nav bar if hooked code set that
        if ( ! $this->SHOW_NAV_BAR) {
            return false;
        }
        // Stop here if gathered nothing
        if (count((array) $items) < 1) {
            return false;
        }
        // Hook have max priority
        if ( ! empty($hooked_items)) {
            $items = $hooked_items;
        }
        // Add first item to all valid items
        array_unshift($items, $this->_nav_item('Home', $this->HOME_LOCATION, 'icon-home fa fa-home'));

        if ($return_array) {
            $this->_nav_item_as_array = false;
            return $items;
        }
        $replace = [
            'items' => implode(tpl()->parse(__CLASS__ . '/div'), $items),
            'is_logged_in' => (int) ((bool) (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0)),
            'bookmark_page' => isset($bookmark_page_code) ? $bookmark_page_code : '',
        ];
        return tpl()->parse(__CLASS__ . '/main', $replace);
    }

    // Display navigation bar item
    public function _nav_item($name = '', $nav_link = '', $nav_icon = '')
    {
        if ($this->AUTO_TRANSLATE) {
            $name = t($name);
        }
        $replace = [
            'name' => _prepare_html($name),
            'link' => $nav_link,
            'icon' => $nav_icon,
            'as_link' => ! empty($nav_link) ? 1 : 0,
            'is_logged_in' => (int) ((bool) (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0)),
        ];
        if ($this->_nav_item_as_array) {
            return $replace;
        }
        return tpl()->parse(__CLASS__ . '/item', $replace);
    }

    // Decode name
    public function _decode_from_url($text = '')
    {
        return ucwords(str_replace('_', ' ', $text));
    }

    // Encode name
    public function _encode_for_url($text = '')
    {
        return strtolower(str_replace(' ', '_', $text));
    }


    public function _show_dropdown_menu()
    {
        $items = _class('graphics')->_show_menu([
            'name' => 'user_main_menu',
            'force_stpl_name' => 'site_nav_bar/dropdown_menu',
            'return_array' => 1,
        ]);
        if ( ! $items) {
            return false;
        }
        foreach ((array) $items as $id => $item) {
            $item['need_clear'] = 0;
            if ($item['type_id'] != 1/* $item['type_id'] == 1 && !module('admin_home')->_url_allowed($item['link'])*/) {
                unset($items[$id]);
                continue;
            }
            $items[$id] = tpl()->parse('site_nav_bar/dropdown_menu_item', $item);
        }
        return tpl()->parse('site_nav_bar/dropdown_menu', [
            'items' => implode('', (array) $items),
        ]);
    }


    public function _breadcrumbs()
    {
        $items = $this->_show($return_array = true);
        if (count((array) $items) <= 1) {
            return false;
        }
        foreach ($items as $v) {
            $a[] = [
                'link' => $v['as_link'] ? $v['link'] : false,
                'name' => $v['name'],
            ];
        }
        css('.navbar { margin-bottom: 0; }');
        return _class('html')->breadcrumbs($a);
    }
}
