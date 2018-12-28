<?php

/**
 * Dashboards for user.
 */
class yf_dashboards
{
    /**
     * Bootstrap CSS classes used to create configurable grid.
     */
    private $_col_classes = [
        1 => 'span12 col-md-12 column',
        2 => 'span6 col-md-6 column',
        3 => 'span4 col-md-4 column',
        4 => 'span3 col-md-3 column',
        6 => 'span2 col-md-2 column',
        12 => 'span1 col-md-1 column',
    ];

    // TODO: add options for items:
    // min_height=0|(int)
    // max_height=0|(int)

    // TODO: в дашборде  сдлеать по умолчанию вызов метода show если указан тролько класс  register == register.show


    public function _init()
    {
        $this->_auto_info['php_item'] = [
            'id' => 'php_item',
            'name' => 'CLONEABLE: php item name',
            'desc' => 'CLONEABLE: php item desc',
            'configurable' => [],
            'cloneable' => 1,
            'auto_type' => 'php_item',
        ];
        $this->_auto_info['block_item'] = [
            'id' => 'php_item',
            'name' => 'CLONEABLE: block item name',
            'desc' => 'CLONEABLE: block item desc',
            'configurable' => [],
            'cloneable' => 1,
            'auto_type' => 'block_item',
        ];
        $this->_auto_info['stpl_item'] = [
            'id' => 'stpl_item',
            'name' => 'CLONEABLE: stpl item name',
            'desc' => 'CLONEABLE: stpl item desc',
            'configurable' => [],
            'cloneable' => 1,
            'auto_type' => 'stpl_item',
        ];
    }

    /**
     * Designed to be used by other modules to show configured dashboard.
     * @param mixed $params
     */
    public function display($params = [])
    {
        if (is_string($params)) {
            $name = $params;
        }
        if ( ! is_array($params)) {
            $params = [];
        }
        if ( ! $params['name'] && $name) {
            $params['name'] = $name;
        }
        if ( ! $params['name']) {
            return _e('Empty dashboard name');
        }
        $this->_name = $params['name'];
        return $this->view($params);
    }

    /**
     * Similar to 'display', but for usage inside this module (action links and more).
     * @param mixed $params
     */
    public function view($params = [])
    {
        if ( ! is_array($params)) {
            $params = [];
        }
        $ds_name = isset($params['name']) ? $params['name'] : ($this->_name ? $this->_name : $_GET['id']);
        $ds = $this->_get_dashboard_data($ds_name);
        if ( ! $ds['id']) {
            return _e('No such record');
        }
        $items_configs = $ds['data']['items_configs'];
        $ds_settings = $ds['data']['settings'];
        $num_columns = isset($this->_col_classes[$ds_settings['columns']]) ? $ds_settings['columns'] : 3;
        if ($ds_settings['full_width']) {
            $filled_columns = 0;
            foreach ((array) $ds['data']['columns'] as $column_id => $column_items) {
                $empty_items = true;
                foreach ((array) $column_items as $name_id) {
                    if ($name_id) {
                        $empty_items = false;
                        break;
                    }
                }
                if ( ! $empty_items) {
                    $filled_columns++;
                }
            }
            $num_columns = $filled_columns;
        }
        foreach ((array) $ds['data']['columns'] as $column_id => $column_items) {
            $css_class_override = '';
            // find grid classes inside items
            foreach ((array) $column_items as $item_id) {
                $item_config = $items_configs[$item_id . '_' . $item_id];
                if ($item_config['grid_class']) {
                    $css_class_override = $item_config['grid_class'];
                }
            }
            $columns[$column_id] = [
                'num' => $column_id,
                'class' => $css_class_override ?: $this->_col_classes[$num_columns],
                'items' => $this->_view_widget_items($column_items, $items_configs, $ds_settings),
            ];
        }
        $replace = [
//			'edit_link'	=> DEBUG_MODE ? ADMIN_WEB_PATH.'?object=manage_dashboards&action=edit&id='.$ds['id'] : '',
            'columns' => $columns,
        ];
        return tpl()->parse(__CLASS__ . '/view_main', $replace);
    }

    /**
     * @param mixed $name_ids
     * @param mixed $items_configs
     */
    public function _view_widget_items($name_ids = [], $items_configs = [])
    {
        $list_of_hooks = $this->_get_available_widgets_hooks();

        $_orig_object = $_GET['object'];
        $_orig_action = $_GET['action'];

        foreach ((array) $name_ids as $name_id) {
            $saved_config = $items_configs[$name_id . '_' . $name_id];
            $info = $list_of_hooks[$name_id];

            $is_cloneable_item = (substr($name_id, 0, strlen('autoid')) == 'autoid');
            if ($is_cloneable_item) {
                $auto_type = $saved_config['auto_type'];
                $info = $this->_auto_info[$auto_type];
                // Merge default settings with saved override
                foreach ((array) $saved_config as $k => $v) {
                    if (strlen($v)) {
                        $info[$k] = $v;
                    }
                }
                $info['auto_id'] = $name_id;
                $info['auto_type'] = $auto_type;
            }
            if ( ! $info) {
                continue;
            }
            $module_name = '';
            $method_name = '';
            $content = '';
            if ($is_cloneable_item) {
                if ($auto_type == 'php_item') {
                    if (strlen($info['code'])) {
                        $content = eval('<?' . 'php ' . $info['code']);
                    } elseif ($info['method_name']) {
                        list($module_name, $method_name) = explode('.', $info['method_name']);
                    }
                } elseif ($auto_type == 'block_item') {
                    $content = _class('core_blocks')->show_block(['block_id' => $info['block_name']]);
                } elseif ($auto_type == 'stpl_item') {
                    if (strlen($info['code'])) {
                        $content = tpl()->parse_string($info['code']);
                    } elseif ($info['stpl_name']) {
                        $content = tpl()->parse($info['stpl_name']);
                    }
                }
            } else {
                list($module_name, $method_name) = explode('::', $info['full_name']);
            }
            if ($module_name && $method_name) {
                // This is needed to correctly execute widget (maybe not nicest method, I know...)
                $_GET['object'] = $module_name;
                $_GET['action'] = $method_name;
                $module_obj = module_safe($module_name);
                if (is_object($module_obj) && method_exists($module_obj, $method_name)) {
                    $content = $module_obj->$method_name($saved_config);
                } else {
                    trigger_error(__CLASS__ . ': called module.method from widget not exists: ' . $module_name . '.' . $method_name . '', E_USER_WARNING);
                }
                $_GET['object'] = $_orig_object;
                $_GET['action'] = $_orig_action;
            }

            $items[$info['auto_id']] = tpl()->parse(__CLASS__ . '/view_item', [
                'id' => $info['auto_id'] . '_' . $info['auto_id'],
                'name' => _prepare_html($info['name']),
                'content' => $content,
                'has_config' => $info['configurable'] ? 1 : 0,
                'css_class' => $saved_config['color'],
                'hide_header' => $saved_config['hide_header'],
                'hide_border' => $saved_config['hide_border'],
            ]);
        }
        if ( ! $items) {
            return '';
        }
        return implode(PHP_EOL, $items);
    }

    /**
     * @param mixed $id
     */
    public function _get_dashboard_data($id = '')
    {
        if ( ! $id) {
            $id = isset($params['name']) ? $params['name'] : ($this->_name ? $this->_name : $_GET['id']);
        }
        if ( ! $id) {
            return false;
        }
        if (isset($this->_dashboard_data[$id])) {
            return $this->_dashboard_data[$id];
        }
        $ds = db()->get('SELECT * FROM ' . db('dashboards') . ' WHERE name="' . db()->es($id) . '" OR id=' . (int) $id);
        if ($ds) {
            $ds['data'] = object_to_array(json_decode($ds['data']));
        }
        $this->_dashboard_data[$id] = $ds;
        return $ds;
    }


    public function _get_available_widgets_hooks()
    {
        if (isset($this->_avail_widgets)) {
            return $this->_avail_widgets;
        }
        $method_prefix = '_hook_widget_';
        $r = [
            '_hook_widget__' => '',
            '_' => '',
            ':' => '',
        ];
        $_widgets = [];
        foreach ((array) _class('user_modules', 'admin_modules/')->_get_methods(['private' => '1']) as $module_name => $module_methods) {
            foreach ((array) $module_methods as $method_name) {
                if (substr($method_name, 0, strlen($method_prefix)) != $method_prefix) {
                    continue;
                }
                $full_name = $module_name . '::' . $method_name;
                $_widgets[$module_name][$method_name] = $full_name;
            }
        }
        $widgets = [];
        foreach ((array) $_widgets as $module_name => $module_widgets) {
            foreach ((array) $module_widgets as $method_name => $full_name) {
                $auto_id = str_replace(array_keys($r), array_values($r), $full_name);
                $widgets[$auto_id] = module_safe($module_name)->$method_name(['describe_self' => true]);
                if ( ! $widgets[$auto_id]['name']) {
                    unset($widgets[$auto_id]);
                    continue;
                    //					$widgets[$auto_id]['name'] = 'TODO: '.str_replace('_', ' ', substr($method_name, strlen($method_prefix)));
                    $widgets[$auto_id]['name'] = str_replace('_', ' ', substr($method_name, strlen($method_prefix)));
                }
                if ( ! $widgets[$auto_id]['desc']) {
                    //					$widgets[$auto_id]['name'] = $module_name.':'.str_replace('_', ' ', substr($method_name, strlen($method_prefix)));
                    $widgets[$auto_id]['name'] = 'TODO: ' . str_replace('_', ' ', substr($method_name, strlen($method_prefix)));
                }
                $widgets[$auto_id]['full_name'] = $full_name;
                $widgets[$auto_id]['auto_id'] = $auto_id;
            }
        }
        if (is_array($widgets)) {
            ksort($widgets);
        }
        $this->_avail_widgets = $widgets;
        return $widgets;
    }
}
