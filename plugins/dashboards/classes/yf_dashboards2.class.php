<?php

/**
 * dashboards2 for user.
 */
class yf_dashboards2
{
    /**
     * Bootstrap CSS classes used to create configurable grid.
     */

    // not using now
    private $_col_classes = [
        1 => 'span12 col-md-12 column',
        2 => 'span6 col-md-6 column',
        3 => 'span4 col-md-4 column',
        4 => 'span3 col-md-3 column',
        6 => 'span2 col-md-2 column',
        12 => 'span1 col-md-1 column',
    ];

    private $_debug_info = [];
    private $_time_start;
    // TODO: add options for items:
    // min_height=0|(int)
    // max_height=0|(int)

    // TODO: в дашборде  сдлеать по умолчанию вызов метода show если указан тролько класс  register == register.show


    public function _init()
    {
        conf('css_framework', 'bs3');
    }

    /**
     * Designed to be used by other modules to show configured dashboard.
     * @param mixed $params
     */
    public function display($params = [])
    {
        $this->_time_start = microtime(true);
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

        $dashboard_data = $this->view($params);

        $this->_debug_info['total_time'] = round(microtime(true) - $this->_time_start, 5);
        DEBUG_MODE && debug('dashboard', $this->_debug_info);
        return $dashboard_data;
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
        $this->_debug_info['name'] = $ds_name;
        $ds = $this->_get_dashboard_data($ds_name);

        if ( ! $ds['id']) {
            return _e('No such record');
        }

        $grid = '';
        if (isset($ds['data']['rows']) && is_array($ds['data']['rows'])) {
            $grid = $this->_get_grid($ds['data']['rows']);
        }
        return $grid;
    }


    public function _get_grid($data = [])
    {
        foreach ((array) $data as $row_id => $row_items) {
            $cols = '';
            if (isset($row_items['cols']) && is_array($row_items['cols'])) {
                foreach ((array) $row_items['cols'] as $col_id => $col_items) {
                    $content = '';
                    if (is_array($col_items['class'])) {
                        $col_class = $col_items['class'][0];
                    } else {
                        $col_class = $col_items['class'];
                    }
                    $row_class = $col_items['class'][0];
                    if (isset($col_items['content']) && is_array($col_items['content'])) {
                        foreach ((array) $col_items['content'] as $content_id => $content_items) {
                            if (isset($content_items['rows']) && is_array($content_items['rows'])) {
                                $content .= $this->_get_grid($content_items['rows']);
                            }
                            if (isset($content_items['widget']) && is_array($content_items['widget'])) {
                                $content .= $this->_view_widget_items($content_items['widget']);
                            }
                        }
                    }
                    $id = '';
                    if ( ! empty($col_items['id'])) {
                        $id = ' id="' . $col_items['id'] . '" ';
                    }
                    if (is_array($col_items['class'])) {
                        $cols .= '<div class="col-md-' . $col_class . '"' . $id . ' > ' . $content . ' </div>';
                    } else {
                        $cols .= '<div class="' . $col_class . '"' . $id . '> ' . $content . ' </div>';
                    }
                }
            }
            $rows[] = [
                'cols' => $cols,
                'id' => $row_items['id'],
                'class' => trim($row_items['class']),
            ];
        }
        $replace = [
            'rows' => $rows,
        ];
        return tpl()->parse(__CLASS__ . '/view_main', $replace);
    }


    /**
     * @param mixed $widgets
     */
    public function _view_widget_items($widgets = [])
    {
        $_orig_object = $_GET['object'];
        $_orig_action = $_GET['action'];
        $is_cloneable_item = true;


        $module_name = '';
        $method_name = '';
        $content = '';
        if ($is_cloneable_item) {
            if ($widgets['type'] == 'php') {
                /*
                    if (strlen($info['code'])) {
                        //						$content = eval('<?'.'php '.$info['code']);
                    } elseif ($info['method_name']) {
                        //						list($module_name, $method_name) = explode('.', $info['method_name']);
                    }
                 */
                list($module_name, $method_name) = explode('.', $widgets['val']);
            } elseif ($widgets['type'] == 'block') {
                $content = _class('core_blocks')->show_block(['block_id' => $info['block_name']]);
            } elseif ($widgets['type'] == 'stpl') {
                /*
                    if (strlen($info['code'])) {
                        $content = tpl()->parse_string($info['code']);
                    } elseif ($info['stpl_name']) {
                        $content = tpl()->parse($info['stpl_name']);
                    }
                 */
            }
        }
        //		list($module_name, $method_name) = explode('::', $info['full_name']);


        if ($module_name && $method_name) {
            // This is needed to correctly execute widget (maybe not nicest method, I know...)
            //$_GET['object'] = $module_name;
            //$_GET['action'] = $method_name;
            //

            $_time_start = microtime(true);
            $module_obj = module_safe($module_name);
            if (is_object($module_obj) && method_exists($module_obj, $method_name)) {
                $content = $module_obj->$method_name($saved_config);
                $this->_debug_info['widgets'][] = [
                    'class_name' => $module_name,
                    'action' => $method_name,
                    'time' => round(microtime(true) - $_time_start, 5),
                ];
            } else {
                trigger_error(__CLASS__ . ': called module.method from widget not exists: ' . $module_name . '.' . $method_name . '', E_USER_WARNING);
            }
            $_GET['object'] = $_orig_object;
            $_GET['action'] = $_orig_action;
        }

        return $content;
        /*
        $items[$info['auto_id']] = tpl()->parse(__CLASS__.'/view_item', array(
            //	'id'			=> $info['auto_id'].'_'.$info['auto_id'],
            'name'			=> _prepare_html($widgets["type"]),
            'content'		=> $content,
            'has_config'	=> 0,
            //				'css_class'		=> $saved_config['color'],
            'hide_header'	=> 1,
            'hide_border'	=> 1,
        ));

        if (!$items) {
            return '';
        }
        return implode(PHP_EOL, $items);
        */
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
        $ds = db()->get('SELECT * FROM ' . db('dashboards2') . ' WHERE name="' . db()->es($id) . '" OR id=' . (int) $id);
        if ($ds) {
            $ds['data'] = object_to_array(json_decode($ds['data']));
        }
        $this->_dashboard_data[$id] = $ds;
        return $ds;
    }
}
