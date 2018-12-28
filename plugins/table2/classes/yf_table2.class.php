<?php

/**
 * Table2 high-level generator, mostly using bootstrap html/css framework.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_table2
{
    /* Example:
        return table('SELECT * FROM '.db('admin'))
            ->text('login')
            ->text('first_name')
            ->text('last_name')
            ->link('group', './?object=admin_groups&action=edit&id=%d', $this->_admin_groups)
            ->date('add_date')
            ->text('go_after_login')
            ->btn_active()
            ->btn_edit()
            ->btn_delete()
            ->btn('log_auth')
            ->footer_link('Failed auth log', './?object=log_admin_auth_fails_viewer')
            ->footer_link('Add', './?object='.$_GET['object'].'&action=add');
    */

    public $CLASS_TABLE_MAIN = 'table table-bordered table-striped table-hover';
    public $CLASS_TOTAL_RECORDS = 'label label-info';
    public $CLASS_FOOTER_LINKS = 'controls';
    public $CLASS_HEADER_LINKS = 'controls';
    public $CLASS_CONDENSED = 'table-condensed';
    public $CLASS_VERY_CONDENSED = 'table-very-condensed';
    public $CLASS_NO_RECORDS = 'alert alert-info';
    public $CLASS_HIGHLIGHT = 'badge-warning';
    public $CLASS_TPL_ICON = 'icon icon-%name fa fa-%name';
    public $CLASS_TPL_BADGE = 'badge badge-%name';
    public $CLASS_TPL_LABEL = 'label label-%name';
    public $CLASS_CHANGE_ACTIVE = 'change_active';
    public $CLASS_BTN_MINI = 'btn btn-default btn-mini btn-xs';
    public $CLASS_ICON_BTN = 'icon-tasks fa fa-tasks';
    public $CLASS_ICON_EDIT = 'icon-edit fa fa-edit';
    public $CLASS_ICON_DELETE = 'icon-trash fa fa-trash';
    public $CLASS_ICON_ADD = 'icon-plus fa fa-plus';
    public $CLASS_ICON_CLONE = 'icon-code-fork fa fa-code-fork';
    public $CLASS_ICON_VIEW = 'icon-eye-open fa fa-eye';
    public $CLASS_ICON_STAR = 'icon-star fa fa-star';
    public $CLASS_ICON_SAVE = 'icon-save fa fa-save';
    public $CLASS_AJAX_EDIT = 'ajax_edit';
    public $CLASS_AJAX_DELETE = 'ajax_delete btn-danger';
    public $CLASS_AJAX_ADD = 'ajax_add';
    public $CLASS_AJAX_CLONE = 'ajax_clone';
    public $CLASS_AJAX_VIEW = 'ajax_view';
    public $CLASS_NO_AJAX = 'no_ajax';
    public $CLASS_STAR_OK = 'star-ok';
    public $CLASS_STAR_KO = 'star-ko';

    public $_total = 0;
    public $_pages = '';
    public $_ids = [];

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args, $return_obj = true);
    }

    /**
     * We cleanup object properties when cloning.
     */
    public function __clone()
    {
        $keep_prefix = 'CLASS_';
        $keep_len = strlen($keep_prefix);
        foreach ((array) get_object_vars($this) as $k => $v) {
            if (substr($k, 0, $keep_len) === $keep_prefix) {
                continue;
            }
            $this->$k = null;
        }
    }

    /**
     * Need to avoid calling render() without params.
     */
    public function __toString()
    {
        try {
            return (string) $this->render();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * @param mixed $name
     * @param mixed $func
     */
    public function _extend($name, $func)
    {
        $this->_extend[$name] = $func;
    }

    /**
     * Wrapper for chained mode call from common()->table2().
     * @param mixed $sql
     * @param mixed $params
     */
    public function chained_wrapper($sql = '', $params = [])
    {
        $this->_chained_mode = true;
        $this->_sql = $sql;
        $this->_params = (array) $params;
        return $this;
    }

    /**
     * Wrapper for template engine.
     * @param mixed $type
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     */
    public function tpl_row($type = 'input', $name, $desc = '', $extra = [])
    {
        return $this->$type($name, $desc, $extra);
    }

    /**
     * Enabling automatic fields parsing mode.
     * @param mixed $params
     */
    public function auto($params = [])
    {
        $this->_params['auto'] = true;
        foreach ((array) $params as $k => $v) {
            $this->_params[$k] = $v;
        }
        return $this;
    }

    /**
     * Render result table html, gathered by row functions.
     * @param mixed $params
     */
    public function render($params = [])
    {
        if (DEBUG_MODE) {
            $ts = microtime(true);
        }
        // Merge params passed to table2() and params passed here, with params here have more priority:
        foreach ((array) $params as $k => $v) {
            $this->_params[$k] = $v;
        }
        $params = &$this->_params;

        if (isset($params['data-postload-url'])) {
            _class('table2_postload', 'classes/table2/')->postload($params['postload_params'], $this);
            $params['table_attr'] = trim($params['table_attr'] . ' data-postload-url="' . _prepare_html($params['data-postload-url']) . '"');
        }
        if (isset($params['filter']) && ! is_array($params['filter']) && is_numeric($params['filter']) || is_bool($params['filter']) && ! empty($params['filter'])) {
            $filter_name = $params['filter_name'] ?: $_GET['object'] . '__' . $_GET['action'];
            $params['filter'] = $_SESSION[$filter_name];
        }

        $a = $this->_render_get_data($params);
        $data = &$a['data'];
        $ids = &$a['ids'];
        if (main()->is_post()) {
            $on_post = isset($params['on_post']) ? $params['on_post'] : $this->_on['on_post'];
            if ( ! is_array($on_post)) {
                $on_post = [$on_post];
            }
            foreach ((array) $on_post as $func_on_post) {
                if (is_callable($func_on_post)) {
                    $func_on_post($params, $a, $this);
                }
            }
        }
        // Automatically get fields from results
        if ($params['auto'] && $data) {
            $this->_render_auto($params, $data);
        }
        // Fill data array with custom fields, also fitting slots with empty strings where no custom data.
        if ($params['custom_fields'] && $data && $ids) {
            $this->_render_add_custom_fields($params, $data, $ids);
        }
        $to_hide = [];
        if ($data && $params['hide_empty']) {
            foreach ((array) current($data) as $k => $v) {
                $to_hide[$k] = $k;
            }
            foreach ((array) $data as $_id => $row) {
                foreach ((array) $row as $k => $v) {
                    if (strlen($v)) {
                        unset($to_hide[$k]);
                    }
                }
            }
        }
        if ($params['group_by'] && $data) {
            if ( ! is_array($params['group_by'])) {
                $params['group_by'] = [$params['group_by']];
            }
            foreach ((array) $params['group_by'] as $group_by) {
                $this->_rowspan[$group_by] = $this->_data_group_by($data, $group_by);
            }
        }
        $on_before_render = isset($params['on_before_render']) ? $params['on_before_render'] : $this->_on['on_before_render'];
        if ( ! is_array($on_before_render)) {
            $on_before_render = [$on_before_render];
        }
        foreach ((array) $on_before_render as $func_on_before_render) {
            if (is_callable($func_on_before_render)) {
                $func_on_before_render($params, $data, $this);
            }
        }
        _class('core_events')->fire('table.before_render', ['this' => $this]);

        if ($params['as_json']) {
            $body = $this->_render_as_json($params, $a, $to_hide);
        } else {
            $body = $this->_render_as_html($params, $a, $to_hide);
        }

        $on_after_render = isset($params['on_after_render']) ? $params['on_after_render'] : $this->_on['on_after_render'];
        if ( ! is_array($on_after_render)) {
            $on_after_render = [$on_after_render];
        }
        foreach ((array) $on_after_render as $func_on_after_render) {
            if (is_callable($func_on_after_render)) {
                $func_on_after_render($params, $a, $body, $this);
            }
        }
        if (array_key_exists('feedback', $params)) {
            $params['feedback']['total'] = count((array) $data);
        }
        if (DEBUG_MODE) {
            $this->_render_debug_info($params, $ts, main()->trace_string());
        }
        _class('core_events')->fire('table.after_render', ['this' => $this]);
        return $body;
    }

    /**
     * @param mixed $data
     * @param mixed $group_by
     */
    public function _data_group_by($data, $group_by)
    {
        $index = [];
        $inverse = [];
        foreach ($data as $id => $row) {
            if ( ! isset($row[$group_by])) {
                continue;
            }
            $val = $row[$group_by];
            $index[$id] = $val;
            if ( ! isset($inverse[$val])) {
                $inverse[$val] = $id;
            }
        }
        $rowspan = [];
        foreach (array_count_values($index) as $val => $num) {
            $id = $inverse[$val];
            $rowspan[$id] = $num;
        }
        return $rowspan;
    }

    /**
     * Render table as JSON-encoded string.
     */
    public function _render_as_json(&$params, &$a, &$to_hide)
    {
        $header_links = [];
        foreach ((array) $this->_header_links as $info) {
            $func = &$info['func'];
            $header_links[] = $func($info, $params, $this) . PHP_EOL;
        }
        $footer_links = [];
        foreach ((array) $this->_footer_links as $info) {
            $func = &$info['func'];
            $footer_links[] = $func($info, $params, $this) . PHP_EOL;
        }
        return json_encode([
            'data' => &$a['data'],
            'pages' => &$a['pages'],
            'total' => &$a['total'],
            'header_links' => $header_links,
            'footer_links' => $footer_links,
        ]);
    }

    /**
     * Render table as HTML string.
     */
    public function _render_as_html(&$params, &$a, &$to_hide)
    {
        $body = '';
        $header_links = [];
        if (MAIN_TYPE_ADMIN && ! $params['no_pages'] && ! $params['no_total'] && $a['total']) {
            $header_links[] = '<div class="' . $this->CLASS_TOTAL_RECORDS . '" style="margin: 0 5px;">' . t('Total') . ':&nbsp;' . $a['total'] . '</div>' . PHP_EOL;
        }
        $body .= ( ! $params['no_pages'] && $params['pages_on_top'] ? $a['pages'] : '') . PHP_EOL;

        foreach ((array) $this->_header_links as $info) {
            $name = $info['name'];
            $func = &$info['func'];
            $header_links[] = $func($info, $params, $this) . PHP_EOL;
        }
        $data = &$a['data'];
        if ($data) {
            if ($this->_form_params) {
                $body .= $this->_init_form()->form_begin($this->_form_params['name'], $this->_form_params['method'], $this->_form_params, $this->_form_params['replace']);
            }
            if ($header_links) {
                $body .= '<div class="' . $this->CLASS_HEADER_LINKS . '">' . implode(PHP_EOL, $header_links) . '</div>';
            }
            if ($params['condensed']) {
                $params['table_class'] .= ' ' . $this->CLASS_CONDENSED;
            } elseif ($params['very_condensed']) {
                $params['table_class'] .= ' ' . $this->CLASS_VERY_CONDENSED;
            }
            $table_class = isset($params['force_class']) ? $params['force_class'] : trim(trim($this->CLASS_TABLE_MAIN . ' ' . $params['table_class']) . ' ' . $params['table_class_add']);
            $table_attrs = (isset($params['table_attr']) ? ' ' . $params['table_attr'] : '');
            $body .= '<table class="' . trim($table_class) . '"' . $table_attrs . '>' . PHP_EOL;
            if ( ! $params['no_header'] && ! $params['rotate_table']) {
                $body .= $this->_render_thead($params, $a, $to_hide);
            }
            $sortable_url = $params['sortable'];
            if ($sortable_url && strlen($sortable_url) <= 5) {
                $sortable_url = url('/@object/sortable');
            }
            if ($params['rotate_table']) {
                $body .= $this->_render_table_contents_rotated($data, $params, $to_hide);
            } else {
                $body .= $this->_render_table_contents($data, $params, $to_hide);
            }
            if ($params['totals'] && ! $params['no_header'] && ! $params['rotate_table']) {
                $body .= $this->_render_totals($params, $a, $to_hide);
            }
            if ($params['show_total']) {
                $params['caption'] .= PHP_EOL . ' ' . t('Total records:') . ':' . $a['total'] . PHP_EOL;
            }
            if ($params['caption']) {
                $body .= '<caption>' . $params['caption'] . '</caption>' . PHP_EOL;
            }
            $body .= '</table>' . PHP_EOL;
        } else {
            if ($header_links) {
                $body .= '<div class="' . $this->CLASS_HEADER_LINKS . '">' . implode(PHP_EOL, $header_links) . '</div>';
            }
            if (isset($params['no_records_callback'])) {
                $func = $params['no_records_callback'];
                $body .= $func($params, $a, $this);
            } else {
                if (isset($params['no_records_html'])) {
                    $body .= $params['no_records_html'] . PHP_EOL;
                } else {
                    $body .= ($params['no_records_simple'] ? t('No records') : '<div class="' . $this->CLASS_NO_RECORDS . '">' . t('No records') . '</div>') . PHP_EOL;
                }
            }
        }
        $footer_links = [];
        foreach ((array) $this->_footer_links as $info) {
            $name = $info['name'];
            $func = &$info['func'];
            $footer_links[] = $func($info, $params, $this) . PHP_EOL;
        }
        if ($footer_links) {
            $body .= '<div class="' . $this->CLASS_FOOTER_LINKS . '">' . implode(PHP_EOL, $footer_links) . '</div>';
        }
        if ($data && $this->_form_params) {
            $body .= '</form>';
        }
        if ( ! isset($params['pages_on_bottom'])) {
            $params['pages_on_bottom'] = true;
        }
        $body .= ( ! $params['no_pages'] && $params['pages_on_bottom'] ? $a['pages'] : '') . PHP_EOL;
        return trim($body);
    }


    public function _render_thead(&$params, &$a, &$to_hide)
    {
        $data = &$a['data'];
        $thead_attrs = '';
        if (isset($params['thead'])) {
            $thead_attrs = is_array($params['thead']) ? _attrs($params['thead'], ['class', 'id']) : ' ' . $params['thead'];
        }
        $body .= '<thead' . $thead_attrs . '>' . PHP_EOL;
        $data1row = current($data);
        // Needed to correctly process null values, when some other rows contain real data there
        foreach ((array) $data1row as $k => $v) {
            $data1row[$k] = (string) $v;
        }
        foreach ((array) $this->_fields as $info) {
            $name = $info['name'];
            if ( ! isset($data1row[$name])) {
                // Fix for anonymous auto-named func data
                if ($info['type'] === 'func' && is_callable($info['func'])) {
                    foreach ((array) $data as $k => $v) {
                        $data[$k][$info['name']] = '';
                    }
                } else {
                    continue;
                }
            }
            if (isset($to_hide[$name])) {
                continue;
            }
            $info['extra'] = (array) $info['extra'];
            if (++$counter2 == 1 && $this->_params['first_col_width']) {
                $info['extra']['width'] = $this->_params['first_col_width'];
            }
            $th_attrs = '';
            if (isset($info['extra']['th'])) {
                $th_attrs .= is_array($info['extra']['th']) ? ' ' . _attrs($info['extra']['th'], ['class', 'id', 'width']) : ' ' . $info['extra']['th'];
            } elseif (isset($info['extra']['th_id'])) {
                $th_attrs .= $info['extra']['th_id'] ? ' id="' . $info['extra']['th_id'] . '"' : '';
            }
            $th_attrs .= $info['extra']['width'] ? ' width="' . preg_replace('~[^[0-9]%]~ims', '', $info['extra']['width']) . '"' : '';
            $th_icon_prepend = ($params['th_icon_prepend'] ? '<i class="' . str_replace('%name', $params['th_icon_prepend'], $this->CLASS_TPL_ICON) . '"></i> ' : '');
            $th_icon_append = ($params['th_icon_append'] ? ' <i class="' . str_replace('%name', $params['th_icon_append'], $this->CLASS_TPL_ICON) . '"></i>' : '');
            $tip = $info['extra']['header_tip'] ? '&nbsp;' . $this->_show_tip($info['extra']['header_tip'], $name) : '';
            $title = isset($info['extra']['th_desc']) ? $info['extra']['th_desc'] : $info['desc'];
            $body .= '<th' . $th_attrs . '>' . $th_icon_prepend . t($title) . $th_icon_prepend . $tip . '</th>' . PHP_EOL;
        }
        if ($this->_buttons) {
            $body .= '<th>' . (isset($params['actions_desc']) ? t($params['actions_desc']) : t('Actions')) . '</th>' . PHP_EOL;
        }
        $body .= '</thead>' . PHP_EOL;
        return $body;
    }


    public function _render_totals(&$params, &$a, &$to_hide)
    {
        $data = &$a['data'];
        $total_fields = $params['totals'];

        $tfoot_attrs = '';
        if (isset($params['tfoot'])) {
            $tfoot_attrs = is_array($params['tfoot']) ? _attrs($params['tfoot'], ['class', 'id']) : ' ' . $params['tfoot'];
        }
        $body .= '<tfoot' . $tfoot_attrs . '>' . PHP_EOL;
        foreach ((array) $this->_fields as $info) {
            $name = $info['name'];
            if ( ! isset($total_fields[$name])) {
                $body .= '<th></th>' . PHP_EOL;
                continue;
            }
            $total_value = $total_fields[$name];
            if (is_callable($total_value)) {
                $total_value = $total_value($this, $info, $params, $a);
            }
            $body .= '<th' . $th_attrs . '>' . $th_icon_prepend . $total_value . $th_icon_prepend . $tip . '</th>' . PHP_EOL;
        }
        if ($this->_buttons) {
            $body .= '<th>' . t('TOTALS') . '</th>' . PHP_EOL;
        }
        $body .= '</tfoot>' . PHP_EOL;

        return $body;
    }


    public function _render_get_data(&$params)
    {
        $default_per_page = MAIN_TYPE_USER ? conf('user_per_page') : conf('admin_per_page');
        if ($params['rotate_table']) {
            $default_per_page = 10;
        }
        $pager = [
            'path' => $params['pager_path'],
            'type' => $params['pager_type'],
            'records_on_page' => $params['pager_records_on_page'] ?: $default_per_page,
            'num_records' => $params['pager_num_records'] ?: 0,
            'stpl_path' => $params['pager_stpl_path'] ?: '',
            'add_get_vars' => $params['pager_add_get_vars'] ?: 1,
            'sql_callback' => $params['pager_sql_callback'] ?: null,
            'extra' => $params['extra'] ?: [],
        ];
        if ( ! $pager['extra']['sql_callback'] && $pager['sql_callback']) {
            $pager['extra']['sql_callback'] = &$pager['sql_callback'];
        }
        $sql = $this->_sql;
        $ids = [];
        if (is_array($sql)) {
            $sql_is_array = true;
        } elseif (is_callable($sql)) {
            $sql_is_callable = true;
        } elseif (is_object($sql)) {
            if ($sql instanceof yf_db_query_builder_driver) {
                $sql_is_query_builder = true;
            } else {
                $sql_is_object = true;
            }
        }
        if ($sql_is_query_builder) {
            $sql = $sql->sql();
        }
        if ($sql_is_array || $sql_is_object || $sql_is_callable) {
            if ($sql_is_object) {
                $data = obj2arr($sql);
            } elseif ($sql_is_callable) {
                $data = (array) $sql($params);
            } else {
                $data = $sql;
            }
            unset($sql);
            if ($params['filter']) {
                $this->_filter_array($data, $params['filter'], $params['filter_params']);
            }
            $pager['out'] = common()->divide_pages(null, null, null, $pager['records_on_page'], count((array) $data), $pager['stpl_path'], $pager['add_get_vars'], $pager['extra']);
            $pages = $pager['out'][1];
            if (count((array) $data) > $pager['records_on_page']) {
                $slice_start = (empty($_GET['page']) ? 0 : (int) ($_GET['page']) - 1) * $pager['records_on_page'];
                $slice_end = $pager['records_on_page'];
                $data = array_slice($data, $slice_start, $slice_end, $preserve_keys = true);
            }
            $total = count((array) $data);
            $ids = array_keys($data);
        } elseif (strlen($sql)) {
            if (is_object($params['db'])) {
                $db = $params['db'];
                $pager['extra']['db'] = $db;
            } else {
                $db = db();
            }
            // TODO: add direct support for query builder and filter
            if ($params['filter']) {
                list($filter_sql, $order_sql) = $this->_filter_sql_prepare($params['filter'], $params['filter_params'], $sql);
                // These 2 arrays needed to be able to use filter parts somehow inside methods
                $this->_filter_data = $params['filter'];
                $this->_filter_params = $params['filter_params'];
            }
            if ( ! $filter_sql && isset($params['filter_params']['__default_filter'])) {
                $filter_sql = $params['filter_params']['__default_filter'];
            }
            if ( ! $order_sql && isset($params['filter_params']['__default_order'])) {
                $order_sql = $params['filter_params']['__default_order'];
            }
            if ($filter_sql || $order_sql) {
                $sql_upper = strtoupper($sql);
                if (strpos($sql, '/*FILTER*/') !== false) {
                    $sql = str_replace('/*FILTER*/', ' ' . $filter_sql . ' ', $sql);
                } elseif (strpos($sql_upper, 'WHERE') === false) {
                    if (strpos($sql_upper, 'GROUP BY') === false) {
                        $sql .= ' WHERE 1 ' . $filter_sql;
                    } else {
                        $sql = str_replace('GROUP BY', ' WHERE 1 ' . $filter_sql . ' GROUP BY', $sql);
                    }
                } else {
                    $sql .= ' ' . $filter_sql;
                }
                if ($order_sql) {
                    if (strpos($order_sql, 'ORDER BY') === false) {
                        $order_sql = 'ORDER BY ' . $order_sql;
                    }
                    if (strpos($sql, '/*ORDER*/') !== false) {
                        $sql = str_replace('/*ORDER*/', ' ' . $order_sql . ' ', $sql);
                    } else {
                        $sql .= ' ' . $order_sql;
                    }
                }
                $this->_sql_with_filter = $sql;
            }
            $pager['out'] = common()->divide_pages($sql, $pager['path'], $pager['type'], $pager['records_on_page'], $pager['num_records'], $pager['stpl_path'], $pager['add_get_vars'], $pager['extra']);
            $add_sql = $pager['out'][0];
            $pages = $pager['out'][1];
            $total = $pager['out'][2];

            $items = [];
            $q = $db->query($sql . $add_sql);
            while ($a = $db->fetch_assoc($q)) {
                if (isset($a['id'])) {
                    $data[$a['id']] = $a;
                    $ids[$a['id']] = $a['id'];
                } else {
                    $data[] = $a;
                }
            }
        }
        // Save result details to be used by outer code
        $this->_total = $total;
        $this->_pages = $pages;
        $this->_ids = $ids;
        $this->_pager = $pager;

        return [
            'data' => $data,
            'pages' => $pages,
            'total' => $total,
            'ids' => $ids,
        ];
    }

    /**
     * Automatically get fields from results.
     */
    public function _render_auto(&$params, &$data)
    {
        if ($params['auto'] && $data) {
            $field_names = array_keys((array) current((array) $data));
            $skip_fields = [];
            foreach ((array) $this->_params['hidden_map'] as $field => $container) {
                $skip_fields[$field] = $field;
            }
            foreach ((array) $field_names as $f) {
                if (isset($skip_fields[$f])) {
                    continue;
                }
                $_extra = [];
                if (++$counter == 1 && $this->_params['first_col_width']) {
                    $_extra['width'] = $this->_params['first_col_width'];
                }
                foreach ((array) $this->_params['hidden_map'] as $field => $container) {
                    if ($container != $f) {
                        continue;
                    }
                    $_extra['hidden_data'][] = '%' . $field;
                }
                $this->text($f, $_extra);
            }
            if ( ! $params['auto_no_buttons']) {
                $this->btn_edit();
                $this->btn_delete();
                $this->footer_add();
            }
        }
    }

    /*
    * Fill data array with custom fields, also fitting slots with empty strings where no custom data. Example:
    * 	table2('SELECT * FROM '.db('user'), array('custom_fields'	=> array(
    *		'num_logins' => 'SELECT user_id, COUNT(*) AS num FROM '.db('log_user_auth').' WHERE user_id IN(%ids) GROUP BY user_id'
    *		'num_auth_fails' => 'SELECT user_id, COUNT(*) AS num FROM '.db('log_user_auth_fails').' WHERE user_id IN(%ids) GROUP BY user_id'
    * 	)))
    *	->text('name')
    *	->text('num_logins')
    *	->text('num_auth_fails')
    *	-------------------
    *	table2('SELECT * FROM '.db('shop_orders'), array('custom_fields' => array(
    *		'user' => array('SELECT id, CONCAT(login," ",email) AS name FROM '.db('user').' WHERE id IN(%ids)', 'user_id'),
    *	)))
    *	->text('user')
    *	-------------------
    *	table2(db()->from('shop_orders'), array('custom_fields' => array(
    *		'user' => array(db()->from('user')->where_raw('id IN(%ids)'), $db_func = 'get_all'),
    *	)))
    *	->func('user', function($user) { return print_r($user, 1); })
    */
    public function _render_add_custom_fields(&$params, &$data, &$ids)
    {
        if ( ! $data || ! $ids || ! $params['custom_fields']) {
            return false;
        }
        $db = is_object($params['db']) ? $params['db'] : db();
        $ids_sql = implode(',', $ids);
        $custom_foreign_fields = [];
        $db_func_orig = 'get_2d';
        foreach ((array) $params['custom_fields'] as $custom_name => $custom_sql) {
            $foreign_field = $this->_params['custom_fields_id'];
            $_ids_sql = $ids_sql;
            $db_func = $db_func_orig;
            if (is_array($custom_sql)) {
                list($custom_sql, $param2, $param3) = $custom_sql;
                // Check if second param is name of db method
                if (in_array($param2, ['get', 'get_2d', 'get_all'])) {
                    $db_func = $param2;
                    $param3 && $foreign_field = $param3;
                } else {
                    $foreign_field = $param2;
                }
            }
            // In this case we can override name of the field used in virtual foreign key, used for custom field.
            // good example is 'user_id' instead of 'id'
            if ($foreign_field) {
                if ($foreign_field != 'id') {
                    $_ids = [];
                    foreach ((array) $data as $k => $v) {
                        $_ids[$v[$foreign_field]] = $v[$foreign_field];
                    }
                    $_ids_sql = implode(',', $_ids);
                }
                $custom_foreign_fields[$custom_name] = $foreign_field;
            }
            if (is_object($custom_sql) && $custom_sql instanceof yf_db_query_builder_driver) {
                $custom_sql = $custom_sql->sql();
            } elseif (is_callable($custom_sql)) {
                $custom_sql = $custom_sql($_ids_sql, $db_func, $this, $data, $ids);
            }
            if ($custom_sql) {
                $custom_sql = str_replace('%ids', $_ids_sql, $custom_sql);
                $this->_data_sql_names[$custom_name] = $db->$db_func($custom_sql);
            }
        }
        foreach ((array) $data as $_id => $row) {
            foreach ((array) $this->_data_sql_names as $custom_name => $custom_data) {
                if ($custom_foreign_fields[$custom_name]) {
                    $_custom_id = $row[$custom_foreign_fields[$custom_name]];
                } else {
                    $_custom_id = $_id;
                }
                $data[$_id][$custom_name] = is_array($custom_data[$_custom_id]) ? $custom_data[$_custom_id] : (string) $custom_data[$_custom_id];
            }
        }
        // Needed to correctly pass inside $instance_params to each function
        $params['data_sql_names'] = $this->_data_sql_names;
    }

    /**
     * @param mixed $ts
     * @param mixed $trace
     */
    public function _render_debug_info(&$params, $ts = 0, $trace = '')
    {
        if ( ! DEBUG_MODE) {
            return false;
        }
        $_fields = [];
        foreach ((array) $this->_fields as $k => $v) {
            $_fields[$k] = ['func' => '%lambda%', 'data' => '%data%'] + $v;
        }
        $_header_links = [];
        foreach ((array) $this->_header_links as $k => $v) {
            $_header_links[$k] = ['func' => '%lambda%', 'data' => '%data%'] + $v;
        }
        $_footer_links = [];
        foreach ((array) $this->_footer_links as $k => $v) {
            $_footer_links[$k] = ['func' => '%lambda%', 'data' => '%data%'] + $v;
        }
        $_buttons = [];
        foreach ((array) $this->_buttons as $k => $v) {
            $_buttons[$k] = ['func' => '%lambda%', 'data' => '%data%'] + $v;
        }
        ! is_hhvm() && debug('table2[]', [
            'params' => $params,
            'fields' => $_fields,
            'buttons' => $_buttons,
            'header_links' => $_header_links,
            'footer_links' => $_footer_links,
            'time' => round(microtime(true) - $ts, 5),
            'trace' => $trace ?: main()->trace_string(),
        ]);
    }

    /**
     * @param mixed $data
     * @param mixed $params
     * @param mixed $to_hide
     */
    public function _render_table_contents($data, $params = [], $to_hide = [])
    {
        $tbody_attrs = '';
        if (isset($params['tbody'])) {
            $tbody_attrs = is_array($params['tbody']) ? _attrs($params['tbody'], ['class', 'id']) : ' ' . $params['tbody'];
        }
        $body .= '<tbody' . $tbody_attrs . '>' . PHP_EOL;
        foreach ((array) $data as $_id => $row) {
            $body .= '<tr' . $this->_get_attrs_string_from_params($params['tr'], $_id, $row) . '>' . PHP_EOL;
            foreach ((array) $this->_fields as $info) {
                $name = $info['name'];
                if (isset($to_hide[$name])) {
                    continue;
                }
                $body .= $this->_render_table_td($info, $row, $params, $_id);
            }
            if ($this->_buttons) {
                $body .= '<td nowrap' . $this->_get_attrs_string_from_params($params['td'], 'buttons', $row) . '>';
                foreach ((array) $this->_buttons as $info) {
                    $name = $info['name'];
                    $func = &$info['func'];
                    $_extra = &$info['extra'];
                    // Callback to decide if we need to show this field or not
                    if (isset($_extra['display_func']) && is_callable($_extra['display_func'])) {
                        $_display_allowed = $_extra['display_func']($row, $info, $params, $this);
                        if ( ! $_display_allowed) {
                            continue;
                        }
                    }
                    $body .= $func($row, $info, $params, $this) . PHP_EOL;
                }
                $body .= '</td>' . PHP_EOL;
            }
            $body .= '</tr>' . PHP_EOL;
        }
        $body .= '</tbody>' . PHP_EOL;
        return $body;
    }

    /**
     * @param mixed $data
     * @param mixed $params
     * @param mixed $to_hide
     */
    public function _render_table_contents_rotated($data = [], $params, $to_hide = [])
    {
        $tbody_attrs = '';
        if (isset($params['tbody'])) {
            $tbody_attrs = is_array($params['tbody']) ? _attrs($params['tbody'], ['class', 'id']) : ' ' . $params['tbody'];
        }
        $body .= '<tbody' . $tbody_attrs . '>' . PHP_EOL;
        foreach ((array) $this->_fields as $info) {
            $name = $info['name'];
            if (isset($to_hide[$name])) {
                continue;
            }
            $body .= '<tr' . $this->_get_attrs_string_from_params($params['tr'], $name, $row) . '>' . PHP_EOL;
            foreach ((array) $data as $_id => $row) {
                $body .= $this->_render_table_td($info, $row, $params, $_id);
            }
            $body .= '</tr>' . PHP_EOL;
        }
        if ($this->_buttons) {
            $body .= '<tr>' . PHP_EOL;
            foreach ((array) $data as $_id => $row) {
                $body .= '<td nowrap' . $this->_get_attrs_string_from_params($params['td'], $_id, $row) . '>';
                foreach ((array) $this->_buttons as $info) {
                    $name = $info['name'];
                    $func = &$info['func'];
                    $_extra = &$info['extra'];
                    // Callback to decide if we need to show this field or not
                    if (isset($_extra['display_func']) && is_callable($_extra['display_func'])) {
                        $_display_allowed = $_extra['display_func']($row, $info, $params, $this);
                        if ( ! $_display_allowed) {
                            continue;
                        }
                    }
                    $body .= $func($row, $info, $params, $this) . '<br>' . PHP_EOL;
                }
                $body .= '</td>' . PHP_EOL;
            }
            $body .= '</tr>' . PHP_EOL;
        }
        $body .= '</tbody>' . PHP_EOL;
        return $body;
    }

    /**
     * @param mixed $info
     * @param mixed $row
     * @param mixed $params
     * @param mixed $row_id
     */
    public function _render_table_td($info, $row, $params, $row_id)
    {
        $name = $info['name'];
        if ( ! array_key_exists($name, $row)) {
            return false;
        }
        $func = &$info['func'];
        $_extra = &$info['extra'];

        $td_width = ($_extra['width'] ? ' width="' . preg_replace('~[^[0-9]%]~ims', '', $_extra['width']) . '"' : '');
        $td_nowrap = ($_extra['nowrap'] ? ' nowrap="nowrap" ' : '');
        $tip = $_extra['tip'] ? '&nbsp;' . $this->_show_tip($_extra['tip'], $info['name'], $row) : '';

        if ($_extra['hl_filter'] && isset($this->_filter_data[$name])) {
            $_kw = $this->_filter_data[$name];
            if (is_string($_kw) && strlen($_kw)) {
                $row[$name] = preg_replace('~(' . preg_quote($_kw, '~') . ')~ims', '<b class="' . $this->CLASS_HIGHLIGHT . '">\1</b>', $row[$name]);
            }
        }
        if ($_extra['wordwrap']) {
            $row[$name] = wordwrap($row[$name], $_width = $_extra['wordwrap'], $_break = PHP_EOL, $_cut = true);
        }
        if (isset($_extra['transform']) && ! empty($_extra['transform'])) {
            $row[$name] = $this->_apply_transform($row[$name], $_extra['transform']);
        }
        // Callback to decide if we need to show this field or not
        if (isset($_extra['display_func']) && is_callable($_extra['display_func'])) {
            $_display_allowed = $_extra['display_func']($row, $info, $params, $this);
            if ( ! $_display_allowed) {
                return false;
            }
        }
        $td_attrs = '';
        if (isset($params['td']) || isset($_extra['td'])) {
            $td_attrs = $this->_get_attrs_string_from_params($params['td'] ?: $_extra['td'], $name, $row, $row_id);
        }
        if ($row_id !== null && isset($this->_rowspan[$name])) {
            $rowspan = $this->_rowspan[$name][$row_id];
            if ($rowspan < 1) {
                return '';
            }
            $td_attrs .= ' rowspan="' . (int) $rowspan . '"';
        }
        return '<td' . $td_width . $td_nowrap . $td_attrs . '>' . $func($row[$name], $info, $row, $params, $this) . $tip . '</td>' . PHP_EOL;
    }

    /**
     * Custom transformation function (one or several, also can be callback). Idea get from form validation rules.
     * @param mixed $text
     * @param mixed $trans
     */
    public function _apply_transform($text, $trans)
    {
        if (is_string($trans) && strpos($trans, '|') !== false) {
            $trans = explode('|', $trans);
        }
        if ( ! is_array($trans)) {
            $trans = [$trans];
        }
        foreach ($trans as $fname) {
            if (is_callable($fname)) {
                $text = $fname($text);
            } elseif (is_string($fname) && function_exists($fname)) {
                $text = $fname($text);
            }
        }
        return $text;
    }

    /**
     * @param mixed $filter_data
     * @param mixed $filter_params
     * @param mixed $__sql
     * @param null|mixed $table
     */
    public function _filter_sql_prepare($filter_data = [], $filter_params = [], $__sql = '', $table = null)
    {
        if ( ! $filter_data) {
            return '';
        }
        return _class('table2_filter', 'classes/table2/')->_filter_sql_prepare($filter_data, $filter_params, $__sql, $table);
    }

    /**
     * Simple filtering of the given array. Need to support table() raw array data with filtering.
     * @param mixed $filter
     * @param mixed $filter_params
     */
    public function _filter_array(&$data, $filter = [], $filter_params = [])
    {
        if ( ! $data || ! $filter) {
            return false;
        }
        return _class('table2_filter', 'classes/table2/')->_filter_array($data, $filter, $filter_params, $this);
    }

    /**
     * Setup form2 class instance to share its methods for form-related components like checkbox, input, etc.
     */
    public function _init_form()
    {
        if ( ! isset($this->_form)) {
            $this->_form = clone _class('form2');
            $this->_form->_chained_mode = false;
        }
        return $this->_form;
    }

    /**
     * Supported: success, warning, important, info, inverse
     * Also support array of badges/labels/classes where will try to find match for a field value.
     * @param mixed $text
     * @param mixed $extra
     * @param null|mixed $field
     */
    public function _apply_badges($text, $extra = [], $field = null)
    {
        if ($extra['badge']) {
            $badge = is_array($extra['badge']) && isset($extra['badge'][$field]) ? $extra['badge'][$field] : $extra['badge'];
            if ($badge) {
                $text = '<span class="' . str_replace('%name', $badge, $this->CLASS_TPL_BADGE) . '">' . $text . '</span>';
            }
        } elseif ($extra['label']) {
            $label = is_array($extra['label']) && isset($extra['label'][$field]) ? $extra['label'][$field] : $extra['label'];
            if ($label) {
                $text = '<span class="' . str_replace('%name', $label, $this->CLASS_TPL_LABEL) . '">' . $text . '</span>';
            }
        } elseif ($extra['class']) {
            $css_class = is_array($extra['class']) && isset($extra['class'][$field]) ? $extra['class'][$field] : $extra['class'];
            if ($css_class) {
                $text = '<span class="' . $css_class . '">' . $text . '</span>';
            }
        }
        return $text;
    }

    /**
     * @param mixed $params
     * @param mixed $_id
     * @param mixed $row
     * @param null|mixed $row_id
     */
    public function _get_attrs_string_from_params($params, $_id, $row, $row_id = null)
    {
        if ( ! $params) {
            return '';
        }
        $attrs = '';
        if (is_callable($params)) {
            $attrs = $params($row, $_id, $row_id);
        } elseif (is_array($params)) {
            $attrs = $params[$_id];
        } elseif (is_string($params)) {
            $attrs = $params;
        }
        if (is_array($attrs)) {
            $attrs = _attrs($attrs, ['class', 'style']);
        }
        return $attrs ? ' ' . $attrs : '';
    }

    /**
     * @param mixed $value
     * @param mixed $name
     * @param mixed $row
     */
    public function _show_tip($value = '', $name = '', $row = [])
    {
        $tip = '';
        if (is_string($value)) {
            $tip = $value;
        } elseif (is_array($value)) {
            if ( ! empty($row) && isset($row[$name])) {
                $tip = $value[$row[$name]];
            } elseif (isset($value[$name])) {
                $tip = $value[$name];
            }
        } elseif (is_callable($value)) {
            $tip = $value($name, $row);
        }
        return strlen($tip) ? tip($tip) : '';
    }

    /**
     * Used to embed hidden data blocks, that can be later displayed.
     * @param mixed $row
     * @param mixed $params
     * @param mixed $instance_params
     */
    public function _hidden_data_container($row, $params, $instance_params)
    {
        $extra = $params['extra'];
        $hidden_data = $extra['hidden_data'];
        if (empty($hidden_data)) {
            return '';
        }
        if ( ! is_array($hidden_data)) {
            $hidden_data = [$hidden_data];
        }
        $body = '';
        foreach ((array) $hidden_data as $data) {
            if ( ! $data) {
                continue;
            }
            // Linking data from row element, example: %explain
            if (substr($data, 0, 1) == '%') {
                $name = substr($data, 1);
                $data = isset($row[$name]) ? $row[$name] : $data;
            } else {
                $name = $params['name'];
            }
            if ($data) {
                $body .= '<div style="display:none;" data-hidden-name="' . $name . '">' . $data . '</div>';
            }
        }
        return $body;
    }

    /**
     * @param mixed $link
     */
    public function _is_link_allowed($link = '')
    {
        $link = trim($link);
        if ( ! strlen($link)) {
            return true;
        }
        $is_link_allowed = true;
        if (MAIN_TYPE_ADMIN && main()->ADMIN_GROUP != 1) {
            if (in_array($link, ['', '#', 'javascript:void();'])) {
                $is_link_allowed = true;
            } else {
                $is_link_allowed = _class('admin_methods')->_admin_link_is_allowed($link);
            }
        }
        return $is_link_allowed;
    }

    /**
     * Example: ->btn('custom', './?object=test&uid=%user_id&pid=%product_id', array('link_params' => 'user_id,product_id'));.
     * @param mixed $link
     * @param mixed $row
     * @param mixed $extra
     */
    public function _process_link_params($link, $row = [], $extra = [])
    {
        if ( ! strlen($link) || empty($row) || false === strpos($link, '%')) {
            return $link;
        }
        $params = [];
        if (isset($extra['link_params'])) {
            foreach (explode(',', $extra['link_params']) as $lp) {
                $lp = trim($lp);
                if (strlen($lp)) {
                    $params[$lp] = $lp;
                }
            }
        } else {
            $params = array_keys($row);
        }
        foreach ((array) $params as $lp) {
            $what = '%' . $lp;
            if (false === strpos($link, $what)) {
                continue;
            }
            $link = str_replace($what, urlencode($row[$lp]), $link);
        }
        if ($row[$extra['id']]) {
            $link = str_replace('%d', urlencode($row[$extra['id']]), $link);
        }
        return $link;
    }

    /**
     * Register callback for TR element.
     * @param mixed $func
     * @param mixed $extra
     */
    public function tr($func, $extra = [])
    {
        $this->_callbacks['tr'][] = [$func, $extra];
        return $this;
    }

    /**
     * Register callback for TD element.
     * @param mixed $func
     * @param mixed $extra
     */
    public function td($func, $extra = [])
    {
        $this->_callbacks['td'][] = [$func, $extra];
        return $this;
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     */
    public function text($name, $desc = '', $extra = [])
    {
        // Shortcut: use second param as $extra
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $desc) {
            $desc = ucfirst(str_replace('_', ' ', $extra['desc'] ?: $name));
        }
        $this->_fields[] = [
            'type' => __FUNCTION__,
            'name' => $name,
            'extra' => $extra,
            'desc' => $desc,
            'link' => $extra['link'],
            'data' => $extra['translate'] ? t($extra['data']) : $extra['data'],
            'func' => function ($field, $params, $row, $instance_params, $table) {
                $name = $params['name'];
                $extra = $params['extra'];
                $orig_extra = $extra;
                if ($extra['padding'] && $row['level']) {
                    $body = '<span style="padding-left:' . ($row['level'] * 20) . 'px; padding-right:5px;">&#9492;</span>';
                }
                if ( ! $params['data'] && $extra['data_name']) {
                    $params['data'] = $instance_params['data_sql_names'][$extra['data_name']];
                }
                if ( ! $params['data']) {
                    $text = $field;
                } else {
                    if (is_string($params['data'])) {
                        $text = $params['data'];
                    } else {
                        $text = (isset($params['data'][$field]) ? $params['data'][$field] : $field);
                    }
                }
                $text = (string) $text;
                // Example of overriding data using field from same row: text('id', array('link' => '/shop/products/%d', 'rewrite' => 1, 'data' => '@name'));
                if (substr($text, 0, 1) == '@') {
                    $text = $row[substr($text, 1)];
                }
                if ($extra['translate']) {
                    $text = t($text);
                }
                if ($params['max_length'] && strlen($text) > $params['max_length']) {
                    $text = substr($text, 0, $params['max_length']);
                }
                $is_link_allowed = true;
                if ($params['link']) {
                    $link = $params['link'] . $instance_params['links_add'];
                }
                if ($link) {
                    $link_field_name = $extra['link_field_name'];
                    $link_id = $link_field_name ?: $name;
                    $link = $table->_process_link_params($link, $row, $extra + ['id' => $link_id]);
                    $is_link_allowed = $table->_is_link_allowed($link);
                }
                $icon = $extra['icon'] ? '<i class="' . trim($extra['icon']) . '"></i>&nbsp;' : '';
                if ($link && $is_link_allowed) {
                    if ($extra['rewrite']) {
                        if ($extra['rewrite'] === 'user') {
                            $link = url_user($link);
                        } elseif ($extra['rewrite'] === 'admin') {
                            $link = url_admin($link);
                        } else {
                            $link = url($link);
                        }
                    }
                    $extra['href'] = $link;
                    if ( ! isset($extra['nowrap']) || $extra['nowrap']) {
                        $text = str_replace(' ', '&nbsp;', $text);
                    }
                    $class = ($extra['class'] ?: $extra['a_class']) ?: $table->CLASS_BTN_MINI;
                    if ($extra['class_add']) {
                        $class .= ' ' . $extra['class_add'];
                    }
                    $extra['class'] = $class;
                    $link_trim_width = conf('link_trim_width') ?: 100;
                    if (isset($extra['link_trim_width'])) {
                        $link_trim_width = $extra['link_trim_width'];
                    }
                    $link_text = strlen($text) ? mb_strimwidth($text, 0, $link_trim_width, '...') : t('link');
                    if ($link_text === 'link') {
                        $link_text = '';
                    }
                    $extra['title'] = trim($extra['link_title']) ?: trim($text);
                    if ($extra['hidden_toggle']) {
                        $extra['data-hidden-toggle'] = $extra['hidden_toggle'];
                    }
                    $body .= strlen($link_text) ? '<a' . _attrs($extra, ['href', 'class', 'title']) . '>' . $icon . _prepare_html($link_text) . '</a>' : '';
                } else {
                    if (isset($extra['nowrap']) && $extra['nowrap']) {
                        $text = str_replace(' ', '&nbsp;', $text);
                    }
                    $body .= $icon . $text;
                }
                $body .= $extra['hidden_data'] ? $table->_hidden_data_container($row, $params, $instance_params) : '';
                return $table->_apply_badges($body, $orig_extra, $field);
            },
        ];
        return $this;
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function text_padded($name, $extra = [])
    {
        $extra['padding'] = true;
        return $this->text($name, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $data
     * @param mixed $extra
     */
    public function link($name, $link = '', $data = '', $extra = [])
    {
        if (is_array($link)) {
            $extra = (array) $extra + $link;
            $link = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ($link) {
            $extra['link'] = $link;
        }
        if ($data) {
            $extra['data'] = $data;
        }
        return $this->text($name, '', $extra);
    }

    /**
     * Currently designed only for admin usage.
     * @param mixed $name
     * @param mixed $extra
     */
    public function user($name = '', $extra = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $name = $extra['name'] ?: $name;
        if ( ! $extra['link']) {
            $extra['link'] = url_admin('/members/edit/%d');
        }
        $func_name = __FUNCTION__;
        $this->on_before_render(function ($p, $data, $table) use ($name, $extra, $func_name) {
            if ( ! $data) {
                return false;
            }
            $field = $name;
            $user_ids = [];
            foreach ((array) $data as $a) {
                $id = $a[$field];
                if ( ! $id) {
                    continue;
                }
                if (is_numeric($id)) {
                    $id && $user_ids[$id] = $id;
                } elseif (is_array($id)) {
                    foreach ((array) $id as $_id) {
                        $_id && $user_ids[$_id] = $_id;
                    }
                } elseif (false !== strpos($id, ',')) {
                    foreach (explode(',', $id) as $_id) {
                        $_id = (int) trim($_id);
                        $_id && $user_ids[$_id] = $_id;
                    }
                }
            }
            if ( ! $user_ids) {
                return false;
            }
            foreach ((array) from('user')->whereid(array_keys($user_ids))->all('id,name,login,email,phone,active') as $a) {
                $table->_data_for_func[$func_name][$a['id']] = $a;
            }
        });
        return $this->func($name, function ($id, $e, $a, $p, $table) use ($func_name) {
            if ( ! $id) {
                return false;
            }
            $out = [];
            $user_ids = [];
            if (is_numeric($id)) {
                $user_ids[$id] = $id;
            } elseif (is_array($id)) {
                foreach ((array) $id as $_id) {
                    $_id && $user_ids[$_id] = $_id;
                }
            } elseif (false !== strpos($id, ',')) {
                foreach (explode(',', $id) as $_id) {
                    $_id = (int) trim($_id);
                    $_id && $user_ids[$_id] = $_id;
                }
            }
            foreach ((array) $user_ids as $id) {
                if ( ! isset($table->_data_for_func[$func_name][$id])) {
                    $out[$id] = $id;
                    continue;
                }
                $u = $table->_data_for_func[$func_name][$id];
                $uname = ($u['login'] ?: $u['name'] ?: $u['email'] ?: $u['phone']);
                $out[$id] = a(str_replace('%d', $id, $e['extra']['link']), _truncate($uname, 50) . '&nbsp;[' . $id . ']', 'fa fa-user');
            }
            return implode(PHP_EOL, $out);
        }, $extra);
    }

    /**
     * Currently designed only for admin usage.
     * @param mixed $name
     * @param mixed $link
     * @param mixed $data
     * @param mixed $extra
     */
    public function admin($name = '', $link = '', $data = '', $extra = [])
    {
        if (is_array($link)) {
            $extra = (array) $extra + $link;
            $link = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $name) {
            $name = $extra['name'] ?: 'user_id';
        }
        if ($link) {
            $extra['link'] = $link;
        }
        if ( ! $extra['link']) {
            $extra['link'] = url_admin('/admin/edit/%d');
        }
        if ( ! $extra['link_field_name']) {
            $extra['link_field_name'] = $name;
        }
        $extra['data'] = $data ?: $extra['data'];
        $_name = 'user';
        $this->_params['custom_fields'][$_name] = ['SELECT id, CONCAT(id, IF(STRCMP(login,""), CONCAT("; ",login), "")) AS user_name FROM ' . db('admin') . ' WHERE id IN(%ids)', $name];
        return $this->text($_name, '', $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     */
    public function date($name, $desc = '', $extra = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $desc) {
            $desc = ucfirst(str_replace('_', ' ', $extra['desc'] ?: $name));
        }
        $this->_fields[] = [
            'type' => __FUNCTION__,
            'name' => $name,
            'extra' => $extra,
            'desc' => $desc,
            'func' => function ($field, $params, $row, $instance_params, $table) {
                $extra = $params['extra'];
                $text = str_replace(' ', '&nbsp;', _format_date($field, $extra['format']));
                return $table->_apply_badges($text, $extra, $field);
            },
        ];
        return $this;
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     */
    public function stars($name, $desc = '', $extra = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $desc) {
            $desc = ucfirst(str_replace('_', ' ', $extra['desc'] ?: $name));
        }
        $this->_fields[] = [
            'type' => __FUNCTION__,
            'name' => $name,
            'extra' => $extra,
            'desc' => $desc,
            'func' => function ($field, $params, $row, $instance_params, $table) {
                $extra = $params['extra'];
                $extra['id'] = $extra['name'];
                $color_ok = $extra['color_ok'] ?: 'yellow';
                $color_ko = $extra['color_ko'] ?: '';
                $class = $extra['class'] ?: $table->CLASS_ICON_STAR;
                $class_ok = $extra['class_ok'] ?: $table->CLASS_STAR_OK;
                $class_ko = $extra['class_ko'] ?: $table->CLASS_STAR_KO;
                $max = $extra['max'] ?: 5;
                $stars = $extra['stars'] ?: 5;
                $input = isset($row[$extra['name']]) ? $row[$extra['name']] : $field;
                foreach (range(1, $stars) as $num) {
                    $is_ok = $input >= ($num * $max / $stars) ? 1 : 0;
                    $body[] = '<i class="' . $class . ' ' . ($is_ok ? $class_ok : $class_ko) . '" style="color:' . ($is_ok ? $color_ok : $color_ko) . ';" title="' . $input . '"></i>';
                }
                return implode(PHP_EOL, $body);
            },
        ];
        return $this;
    }

    /**
     * @param mixed $name
     * @param mixed $path
     * @param mixed $link
     * @param mixed $extra
     */
    public function image($name, $path, $link = '', $extra = [])
    {
        return _class('table2_image', 'classes/table2/')->image($name, $path, $link = '', $extra, $this);
    }

    /**
     * Callback function will be populated with these params: function($field, $params, $row, $instance_params) {}.
     * @param mixed $name
     * @param null|mixed $func
     * @param mixed $extra
     */
    public function func($name, $func = null, $extra = [])
    {
        if ( ! is_string($name) && is_callable($name)) {
            if (is_array($func)) {
                $extra = (array) $extra + $func;
            }
            $func = $name;
            if (isset($extra['name'])) {
                $name = $extra['name'];
            } else {
                $name = __FUNCTION__ . '_' . ++$this->_auto_names[__FUNCTION__];
                $extra['desc'] = $extra['desc'] ?: ''; // Prevent auto-generated id desc
            }
            $this->_params['custom_fields'][$name] = $func;
        }
        $desc = isset($extra['desc']) ? $extra['desc'] : ucfirst(str_replace('_', ' ', $name));
        $this->_fields[] = [
            'type' => __FUNCTION__,
            'name' => $name,
            'extra' => $extra,
            'desc' => $desc,
            'func' => $func,
        ];
        return $this;
    }

    /**
     * Column counter, knows about pagination.
     * @param mixed $extra
     */
    public function rownum($extra = [])
    {
        $table = $this;
        $func = function ($val, $extra, $row) use ($table) {
            $pager = $table->_pager;
            $first = $pager['out'][3];
            $cur = ++$table->_rownum_counter;
            return $first + $cur;
        };
        return $this->func($func, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function allow_deny($name, $extra = [])
    {
        if ( ! isset($this->_pair_allow_deny)) {
            $this->_pair_allow_deny = str_replace('class="', 'disabled class="', main()->get_data('pair_allow_deny'));
        }
        $extra['data'] = $this->_pair_allow_deny;
        return $this->func($name, function ($field, $params, $row) {
            $extra = (array) $params['extra'];
            $extra['data'] = (array) $extra['data'];
            return $extra['data'][$field];
        }, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function yes_no($name = '', $extra = [])
    {
        if ( ! isset($this->_pair_yes_no)) {
            $this->_pair_yes_no = str_replace('class="', 'disabled class="', main()->get_data('pair_yes_no'));
        }
        $extra['data'] = $this->_pair_yes_no;
        return $this->func($name, function ($field, $params, $row) {
            $extra = (array) $params['extra'];
            $extra['data'] = (array) $extra['data'];
            return $extra['data'][$field];
        }, $extra);
    }

    /**
     * Show multiple selected data items
     * Example of data: $this->_params['custom_fields'][$_name] = array('SELECT id, CONCAT(login," ",email) AS user_name FROM '.db('user').' WHERE id IN(%ids)', $name);.
     * @param mixed $name
     * @param mixed $data
     * @param mixed $extra
     */
    public function data($name, $data = [], $extra = [])
    {
        $this->form();
        $extra['data'] = $data;
        return $this->func($name, function ($field, $params, $row) {
            $extra = $params['extra'];
            $out = [];
            foreach (explode(',', trim(trim(str_replace(',' . PHP_EOL, ',', $field), ','))) as $k => $v) {
                $v = trim($v);
                if ( ! strlen($v)) {
                    continue;
                }
                if ( ! empty($extra['data'][$v])) {
                    $out[$v] = trim($extra['data'][$v]);
                }
            }
            $body = $out ? implode('<br>', $out) : t('--All--');
            return '<small>' . str_replace([' ', "\t"], '&nbsp;', $body) . '</small>';
        }, $extra);
    }

    /**
     * Display language with default country flag for language.
     * @param mixed $name
     * @param mixed $data
     * @param mixed $extra
     */
    public function lang($name, $data = [], $extra = [])
    {
        $_this = $this;
        return $this->func($name, function ($lang) use ($_this) {
            asset('bfh-select');
            if ( ! isset($_this->lang_def_country)) {
                $_this->lang_def_country = main()->get_data('lang_def_country');
            }
            return html()->icon('bfh-flag-' . $_this->lang_def_country[$lang], strtoupper($lang));
        }, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     */
    public function btn($name, $link, $extra = [])
    {
        if (is_array($link)) {
            $extra = $link;
            $link = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $this->_buttons[] = [
            'type' => __FUNCTION__,
            'name' => $name,
            'extra' => $extra,
            'link' => $link,
            'func' => function ($row, $params, $instance_params, $table) {
                $extra = $params['extra'];
                $override_id = '';
                if (isset($extra['id'])) {
                    $override_id = $extra['id'];
                }
                if (isset($instance_params['id'])) {
                    $override_id = $instance_params['id'];
                }
                if ($instance_params['btn_no_text'] || $extra['btn_no_text']) {
                    $no_text = 1;
                }
                $id = $override_id ? $override_id : 'id';
                $icon = ($extra['icon'] ? ' ' . $extra['icon'] : $table->CLASS_ICON_BTN);
                $link = trim($params['link'] . $instance_params['links_add']);
                if (strlen($link)) {
                    $link = $table->_process_link_params($link, $row, $extra + ['id' => $id]);
                    if ( ! $table->_is_link_allowed($link)) {
                        return '';
                    }
                }
                if ($extra['rewrite']) {
                    if ($extra['rewrite'] === 'user') {
                        $link = url_user($link);
                    } elseif ($extra['rewrite'] === 'admin') {
                        $link = url_admin($link);
                    } else {
                        $link = url($link);
                    }
                }
                $extra['href'] = $link;
                $class = ($extra['class'] ?: $extra['a_class']) ?: $table->CLASS_BTN_MINI;
                if ($extra['class_add']) {
                    $class .= ' ' . $extra['class_add'];
                }
                if ($extra['no_ajax'] || $instance_params['no_ajax']) {
                    $class .= ' ' . $table->CLASS_NO_AJAX;
                }
                $extra['class'] = $class;
                if ($extra['hidden_toggle']) {
                    $extra['data-hidden-toggle'] = $extra['hidden_toggle'];
                }
                $title = $params['name'] ?: $extra['title'] ?: $extra['desc'];
                if ($title) {
                    $extra['title'] = $title;
                }
                $renderer = $extra['renderer'] ?: 'a';
                if ($renderer == 'a') {
                    $body = '<a' . _attrs($extra, ['href', 'class', 'target', 'title']) . '><i class="' . trim($icon) . '"></i>' . (empty($no_text) ? ' ' . t($params['name']) : '') . '</a> ';
                } elseif ($renderer == 'button') {
                    $body = '<button' . _attrs($extra, ['class', 'target', 'title']) . '><i class="' . trim($icon) . '"></i>' . (empty($no_text) ? ' ' . t($params['name']) : '') . '</button> ';
                }

                $body .= $extra['hidden_data'] ? $table->_hidden_data_container($row, $params, $instance_params) : '';
                return $body;
            },
        ];
        return $this;
    }

    /**
     * Callback function will be populated with these params: function($row, $params, $instance_params) {}.
     * @param mixed $name
     * @param mixed $func
     * @param mixed $extra
     */
    public function btn_func($name, $func, $extra = [])
    {
        if ( ! $desc && isset($extra['desc'])) {
            $desc = $extra['desc'];
        }
        if ( ! $desc) {
            $desc = ucfirst(str_replace('_', ' ', $name));
        }
        $this->_buttons[] = [
            'type' => __FUNCTION__,
            'name' => $name,
            'extra' => $extra,
            'desc' => $desc,
            'func' => $func,
        ];
        return $this;
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     */
    public function btn_edit($name = '', $link = '', $extra = [])
    {
        if (is_array($name)) {
            $extra = $name;
            $name = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $name) {
            $name = $extra['name'] ?: 'Edit';
        }
        if ( ! $link) {
            $link = $extra['link'] ?: url('/@object/edit/%d');
        }
        if ( ! $extra['no_ajax']) {
            $extra['class_add'] .= ' ' . $this->CLASS_AJAX_EDIT;
        }
        if ( ! isset($extra['icon'])) {
            $extra['icon'] = $this->CLASS_ICON_EDIT;
        }
        if ( ! isset($extra['data-test'])) {
            $extra['data-test'] = 'edit';
        }
        return $this->btn($name, $link, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     */
    public function btn_delete($name = '', $link = '', $extra = [])
    {
        if (is_array($name)) {
            $extra = $name;
            $name = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $name) {
            $name = $extra['name'] ?: 'Delete';
        }
        if ( ! $link) {
            $link = $extra['link'] ?: url('/@object/delete/%d');
        }
        if ( ! $extra['no_ajax']) {
            $extra['class_add'] .= ' ' . $this->CLASS_AJAX_DELETE;
        }
        if ( ! isset($extra['icon'])) {
            $extra['icon'] = $this->CLASS_ICON_DELETE;
        }
        if ( ! isset($extra['data-test'])) {
            $extra['data-test'] = 'delete';
        }
        return $this->btn($name, $link, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     */
    public function btn_clone($name = '', $link = '', $extra = [])
    {
        if (is_array($name)) {
            $extra = $name;
            $name = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $name) {
            $name = $extra['name'] ?: 'Clone';
        }
        if ( ! $link) {
            $link = $extra['link'] ?: url('/@object/clone_item/%d');
        }
        if ( ! $extra['no_ajax']) {
            $extra['class_add'] .= ' ' . $this->CLASS_AJAX_CLONE;
        }
        if ( ! isset($extra['icon'])) {
            $extra['icon'] = $this->CLASS_ICON_CLONE;
        }
        if ( ! isset($extra['data-test'])) {
            $extra['data-test'] = 'clone';
        }
        return $this->btn($name, $link, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     */
    public function btn_view($name = '', $link = '', $extra = [])
    {
        if (is_array($name)) {
            $extra = $name;
            $name = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $name) {
            $name = $extra['name'] ?: 'View';
        }
        if ( ! $link) {
            $link = $extra['link'] ?: url('/@object/view/%d');
        }
        if ( ! $extra['no_ajax']) {
            $extra['class_add'] .= ' ' . $this->CLASS_AJAX_VIEW;
        }
        if ( ! isset($extra['icon'])) {
            $extra['icon'] = $this->CLASS_ICON_VIEW;
        }
        if ( ! isset($extra['data-test'])) {
            $extra['data-test'] = 'view';
        }
        return $this->btn($name, $link, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     */
    public function btn_active($name = '', $link = '', $extra = [])
    {
        if (is_array($name)) {
            $extra = $name;
            $name = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $name) {
            $name = $extra['name'] ?: 'Active';
        }
        if ( ! $link) {
            $link = $extra['link'] ?: url('/@object/active/%d');
        }
        $this->_buttons[] = [
            'type' => __FUNCTION__,
            'name' => $name,
            'extra' => $extra,
            'link' => $link,
            'func' => function ($row, $params, $instance_params, $table) {
                $extra = $params['extra'];
                $override_id = '';
                if (isset($extra['id'])) {
                    $override_id = $extra['id'];
                }
                if (isset($instance_params['id'])) {
                    $override_id = $instance_params['id'];
                }
                $id = $override_id ? $override_id : 'id';
                $link = $params['link'] . $instance_params['links_add'];
                if (strlen($link)) {
                    $link = $table->_process_link_params($link, $row, $extra + ['id' => $id]);
                    if ( ! $table->_is_link_allowed($link)) {
                        return '';
                    }
                }
                if ($extra['rewrite']) {
                    if ($extra['rewrite'] === 'user') {
                        $link = url_user($link);
                    } elseif ($extra['rewrite'] === 'admin') {
                        $link = url_admin($link);
                    } else {
                        $link = url($link);
                    }
                }
                $extra['href'] = $link;
                $extra['title'] = $params['name'];
                if ( ! $extra['no_ajax']) {
                    $extra['class'] = $table->CLASS_CHANGE_ACTIVE;
                }
                if ( ! isset($extra['data-test'])) {
                    $extra['data-test'] = 'activate';
                }
                if ( ! isset($table->_pair_active)) {
                    $table->_pair_active = main()->get_data('pair_active');
                }
                $values = $table->_pair_active;
                $is_active = (int) ((bool) $row[strtolower($params['name'])]);
                $val = $values[$is_active];
                if ($extra['short']) {
                    return html()->btn_active($link, $is_active, $extra);
                }
                return ! $extra['disabled'] ? '<a' . _attrs($extra, ['href', 'class', 'title']) . '>' . $val . '</a> ' : $val;
            },
        ];
        return $this;
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     */
    public function header_link($name, $link, $extra = [])
    {
        $extra['display_in'] = 'header';
        return $this->footer_link($name, $link, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     */
    public function footer_link($name, $link, $extra = [])
    {
        $item = [
            'type' => __FUNCTION__,
            'name' => $name,
            'extra' => $extra,
            'link' => $link,
            'func' => function ($params, $instance_params, $table) {
                $extra = $params['extra'];
                $id = isset($extra['id']) ? $extra['id'] : 'id';
                $link = $params['link'] . $instance_params['links_add'];
                if (strlen($link)) {
                    $link = $table->_process_link_params($link, $row, $extra + ['id' => $id]);
                    if ( ! $table->_is_link_allowed($link)) {
                        return '';
                    }
                }
                if ($extra['rewrite']) {
                    if ($extra['rewrite'] === 'user') {
                        $link = url_user($link);
                    } elseif ($extra['rewrite'] === 'admin') {
                        $link = url_admin($link);
                    } else {
                        $link = url($link);
                    }
                }
                $extra['href'] = $link;
                $icon = ($extra['icon'] ? ' ' . $extra['icon'] : $table->CLASS_ICON_BTN);
                $class = ($extra['class'] ?: $extra['a_class']) ?: $table->CLASS_BTN_MINI;
                if ($extra['class_add']) {
                    $class .= ' ' . $extra['class_add'];
                }
                if ($extra['no_ajax'] || $instance_params['no_ajax']) {
                    $class .= ' ' . $table->CLASS_NO_AJAX;
                }
                $extra['class'] = $class;
                return '<a' . _attrs($extra, ['href', 'class', 'title']) . '><i class="' . $icon . '"></i> ' . t($params['name']) . '</a> ';
            },
        ];
        if ( ! $extra['display_in']) {
            $extra['display_in'] = 'footer';
        }
        if ($extra['display_in'] == 'header' || $extra['copy_to_header']) {
            $this->_header_links[] = $item;
        }
        if ($extra['display_in'] == 'footer' || $extra['copy_to_footer']) {
            $this->_footer_links[] = $item;
        }
        return $this;
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     */
    public function header_add($name = '', $link = '', $extra = [])
    {
        if (is_array($name)) {
            $extra = $name;
            $name = $extra['name'];
        }
        $extra['display_in'] = 'header';
        return $this->footer_add($name, $link, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     */
    public function footer_add($name = '', $link = '', $extra = [])
    {
        if (is_array($name)) {
            $extra = $name;
            $name = $extra['name'];
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $name) {
            $name = $extra['name'] ?: 'add';
        }
        if ( ! $link) {
            $link = $extra['link'] ?: url('/@object/add');
        }
        if ( ! $extra['no_ajax']) {
            $extra['class_add'] .= ' ' . $this->CLASS_AJAX_ADD;
        }
        if ( ! isset($extra['icon'])) {
            $extra['icon'] = $this->CLASS_ICON_ADD;
        }
        return $this->footer_link($name, $link, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function header_submit($name = '', $extra = [])
    {
        if (is_array($name)) {
            $extra = $name;
            $name = $extra['name'];
        }
        $extra['display_in'] = 'header';
        return $this->footer_submit($name, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function footer_submit($name = '', $extra = [])
    {
        if (is_array($name)) {
            $extra = $name;
            $name = $extra['name'];
        }
        $item = [
            'type' => __FUNCTION__,
            'name' => $name,
            'extra' => $extra,
            'link' => $link,
            'func' => function ($params, $instance_params, $table) {
                $extra = $params['extra'];
                $value = $params['name'] ? $params['name'] : 'Submit';
                if (is_array($value) && empty($extra)) {
                    $extra = $value;
                    $value = '';
                }
                $value = $extra['value'] ?: $value;
                $desc = $extra['desc'] ?: $value;
                $extra['type'] = 'submit';
                $extra['name'] = trim($extra['name'] ?: $value);
                $icon = ($extra['icon'] ? ' ' . $extra['icon'] : $table->CLASS_ICON_SAVE);
                $class = ($extra['class'] ?: $extra['a_class']) ?: $table->CLASS_BTN_MINI;
                $extra['class'] = $class;
                return '<button' . _attrs($extra, ['type', 'name', 'class', 'value']) . '><i class="' . trim($icon) . '"></i> ' . t($desc) . '</button>';
            },
        ];
        if ( ! $extra['display_in']) {
            $extra['display_in'] = 'footer';
        }
        if ($extra['display_in'] == 'header' || $extra['copy_to_header']) {
            $this->_header_links[] = $item;
        }
        if ($extra['display_in'] == 'footer' || $extra['copy_to_footer']) {
            $this->_footer_links[] = $item;
        }
        return $this;
    }

    /**
     * Simply tells that current table should consist of form inside.
     * @param mixed $action
     * @param mixed $method
     * @param mixed $extra
     */
    public function form($action = '', $method = '', $extra = [])
    {
        if (isset($this->_form_params) && ! $extra['force']) {
            return $this;
        }
        if (is_array($action)) {
            $extra = $action;
            $action = '';
        }
        if (is_array($method)) {
            $extra = $method;
            $method = '';
        }
        $this->_form_params = [
            'action' => $action ?: url('/@object/@action/@id'),
            'method' => $method ?: 'POST',
            'extra' => (array) $extra,
        ];
        return $this;
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function input($name, $extra = [])
    {
        $this->form();
        return $this->func($name, function ($field, $params, $row) {
            $extra = $params['extra'];
            if ($extra['padding'] && $row['level']) {
                $padding = '<span style="padding-left:' . ($row['level'] * 20) . 'px; padding-right:5px; float: left; font-size: 25px; line-height: 30px;">&#9492;</span>';
            }
            $value = $field;
            if ($extra['propose_url_from'] && ! strlen($value)) {
                $value = common()->_propose_url_from_name($row[$extra['propose_url_from']]);
            }
            return $padding . _class('html')->input([
                'id' => 'input_' . $params['name'] . '_' . $row['id'],
                'name' => $params['name'] . '[' . $row['id'] . ']',
                'desc' => $params['name'],
                'value' => $value,
                'style' => $extra['padding'] && $row['level'] ? 'width:calc(100% - ' . ($row['level'] * 20 + 30) . 'px)' : '',
            ] + (array) $extra);
        }, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function input_padded($name, $extra = [])
    {
        $extra['padding'] = true;
        return $this->input($name, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function icon($name, $extra = [])
    {
        $this->form();
        return $this->func($name, function ($field, $params, $row) {
            $icon = trim($field);
            if ( ! $icon) {
                return '';
            }
            // Icon class from bootstrap icon class names
            if (preg_match('/^icon\-[a-z0-9_-]+$/i', $icon)) {
                return '<i class="' . $icon . '"></i>';
            }
            $_icon_path = PROJECT_PATH . 'uploads/icons/' . $icon;
            if (file_exists(INCLUDE_PATH . $_icon_path)) {
                $icon_src = WEB_PATH . $_icon_path;
            }
            if ($icon_src) {
                return '<img src="' . _prepare_html($icon_src) . '" />';
            }

            return '';
        }, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function check_box($name, $extra = [])
    {
        $this->form();
        return $this->func($name, function ($field, $params, $row) {
            $extra = $params['extra'];
            if ( ! is_array($extra)) {
                $extra = [];
            }
            if ( ! $extra['name']) {
                $extra['name'] = $params['name'];
            }
            if (false === strpos($extra['name'], '[')) {
                $extra['name'] .= '[' . $field . ']';
            }
            $extra['desc'] = $extra['desc'] ?: '';
            $extra['id'] = 'checkbox_' . $field;
            return _class('html')->check_box($extra);
        }, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function radio_box($name, $extra = [])
    {
        $this->form();
        return $this->func($name, function ($field, $params, $row) {
            $extra = $params['extra'];
            if ( ! is_array($extra)) {
                $extra = [];
            }
            if ( ! $extra['name']) {
                $extra['name'] = $params['name'];
            }
            if (false === strpos($extra['name'], '[')) {
                $extra['name'] .= '[' . $field . ']';
            }
            //			$extra['desc'] = $extra['desc'] ?: '';
            $extra['id'] = 'radiobox_' . $field;
            return _class('html')->radio_box($extra);
        }, $extra);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     */
    public function select_box($name, $extra = [])
    {
        $this->form();
        return $this->func($name, function ($field, $params, $row) {
            $extra = $params['extra'];
            if ( ! is_array($extra)) {
                $extra = [];
            }
            if ( ! $extra['name']) {
                $extra['name'] = $params['name'];
            }
            if (false === strpos($extra['name'], '[')) {
                $extra['name'] .= '[' . $field . ']';
            }
            $extra['id'] = 'selectbox_' . $field;
            return _class('html')->select_box($extra);
        }, $extra);
    }

    /**
     * @param mixed $func
     */
    public function on_post($func)
    {
        $this->_on[__FUNCTION__][] = $func;
        return $this;
    }

    /**
     * @param mixed $func
     */
    public function on_before_render($func)
    {
        $this->_on[__FUNCTION__][] = $func;
        return $this;
    }

    /**
     * @param mixed $func
     */
    public function on_after_render($func)
    {
        $this->_on[__FUNCTION__][] = $func;
        return $this;
    }
}
