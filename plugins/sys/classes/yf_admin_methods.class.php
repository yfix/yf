<?php

/**
 * Common admin methods hidden by simple api.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
#[AllowDynamicProperties]
class yf_admin_methods
{
    public $params = [];
    public $_params = [];
    public $default_ckeditor_params = [
        'file_browser' => 'internal',
    ];

    public $_preload_complete = null;

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }

    /**
     * @param mixed $name
     */
    public function __get($name)
    {
        if ( ! $this->_preload_complete) {
            $this->_preload_data();
        }
        return $this->$name;
    }

    /**
     * @param mixed $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if ( ! $this->_preload_complete) {
            $this->_preload_data();
        }
        $this->$name = $value;
        return $this->$name;
    }


    public function _preload_data()
    {
        if ($this->_preload_complete) {
            return true;
        }
        $this->_preload_complete = true;
        $this->USER_ID = main()->USER_ID;
        $this->USER_GROUP = main()->USER_GROUP;
        $this->ADMIN_ID = main()->ADMIN_ID;
        $this->ADMIN_GROUP = main()->ADMIN_GROUP;
        $this->CENTER_BLOCK_ID = _class('core_blocks')->_get_center_block_id();
        $this->ADMIN_URL_HOST = parse_url(WEB_PATH, PHP_URL_HOST);
        $this->ADMIN_URL_PATH = parse_url(WEB_PATH, PHP_URL_PATH);
    }

    /**
     * @param mixed $params
     */
    public function show($params = [])
    {
        if (is_string($params)) {
            $params = ['table' => $params];
        }
        if ( ! is_array($params)) {
            $params = [];
        }
        $params += (array) $this->params;

        $filter_name = $params['filter_name'] ?: $_GET['object'] . '__' . $_GET['action'];
        $db = is_object($params['db']) ? $params['db'] : db();
        $table = $db->_fix_table_name($params['table']);
        return table(from($table), [
            'id' => $params['id'] ?: 'id',
            'filter' => $_SESSION[$filter_name],
            'filter_params' => $params['filter_params'],
        ])->auto();
    }

    /**
     * @param mixed $params
     */
    public function add($params = [])
    {
        if (is_string($params)) {
            $params = ['table' => $params];
        }
        if ( ! is_array($params)) {
            $params = [];
        }
        $params += (array) $this->params;

        $replace = [
            'form_action' => $params['form_action'] ?: url('/@object/@action/' . $params['links_add']),
            'back_link' => $params['back_link'] ?: url('/@object/' . $params['links_add']),
        ];
        $db = is_object($params['db']) ? $params['db'] : db();
        $table = $db->_fix_table_name($params['table']);
        if ( ! $table) {
            $error = 'Wrong table name';
            if ($params['return_form']) {
                return _e($error);
            }
            _re($error);
            return $replace;
        }
        $fields = $params['fields'];
        $primary_field = $params['id'] ? $params['id'] : 'id';
        if ( ! $fields) {
            $columns = $db->meta_columns($table);
            if (isset($columns[$primary_field])) {
                unset($columns[$primary_field]);
            }
            $fields = array_keys($columns);
        }
        if (is_post()) {
            if ( ! common()->_error_exists()) {
                $sql = [];
                foreach ((array) $fields as $f) {
                    if (isset($_POST[$f])) {
                        $sql[$f] = $_POST[$f];
                    }
                }
                if (is_callable($params['on_before_update'])) {
                    $params['on_before_update']($sql);
                }

                $db->insert_safe($table, $sql);
                $new_id = $db->insert_id();
                if ($params['revisions']) {
                    module_safe('manage_revisions')->add($params['table'], $new_id, 'add');
                }
                common()->admin_wall_add([$_GET['object'] . ': added record into table ' . $table, $new_id]);

                if (is_callable($params['on_after_update'])) {
                    $params['on_after_update']($sql, $new_id);
                }
                $form_action = $params['form_action'] ?: url('/@object/@action/' . $params['links_add']);
                if ($new_id) {
                    $form_action .= '&id=' . $new_id;
                }
                $form_action = str_replace(['add', '_add'], ['edit', '_edit'], $form_action);
                return js_redirect($form_action);
            }
            if (is_callable($params['on_error'])) {
                $params['on_error']();
            }
        }
        if (is_callable($params['on_before_show'])) {
            $params['on_before_show']($_POST);
        }
        $DATA = $_POST;
        if ( ! $params['no_escape']) {
            $DATA = _prepare_html($DATA);
        }
        foreach ((array) $fields as $f) {
            $replace[$f] = $DATA[$f];
        }
        if ($params['return_form']) {
            return form($replace)->auto($table, $id ?? '', [
                'links_add' => $params['links_add'],
                'db' => $db,
            ]);
        }
        return $replace;
    }

    /**
     * @param mixed $params
     */
    public function edit($params = [])
    {
        if (is_string($params)) {
            $params = ['table' => $params];
        }
        if ( ! is_array($params)) {
            $params = [];
        }
        $params += (array) $this->params;

        $replace = [
            'form_action' => $params['form_action'] ?: url('/@object/@action/' . urlencode($_GET['id']) . '/' . $params['links_add']),
            'back_link' => $params['back_link'] ?: url('/@object/' . $params['links_add']),
        ];
        $db = is_object($params['db']) ? $params['db'] : db();
        $table = $db->_fix_table_name($params['table']);
        if ( ! $table) {
            $error = 'Wrong table name';
            if ($params['return_form']) {
                return _e($error);
            }
            _re($error);
            return $replace;
        }
        $fields = $params['fields'];
        $primary_field = $params['id'] ?: $params['primary'] ?: 'id';
        $secondary_field = $params['secondary'] ?: 'name';
        $id = isset($params['input_' . $primary_field]) ? $params['input_' . $primary_field] : $_GET['id'];
        if ( ! $fields) {
            $columns = $db->meta_columns($table);
            if (isset($columns[$primary_field])) {
                unset($columns[$primary_field]);
            }
            $fields = array_keys($columns);
        }
        $a = $db->from($table)->where($primary_field, '=', $id)->get();
        if ( ! $a && $secondary_field && in_array($secondary_field, $fields)) {
            $a = $db->from($table)->where($secondary_field, '=', $id)->get();
        }
        if ( ! $a) {
            $error = 'Wrong id';
            if ($params['return_form']) {
                return _e($error);
            }
            _re($error);
            return $replace;
        }
        if (is_post()) {
            if ( ! common()->_error_exists()) {
                $sql = [];
                foreach ((array) $fields as $f) {
                    if (isset($_POST[$f])) {
                        $sql[$f] = $_POST[$f];
                    }
                }
                if (is_callable($params['on_before_update'])) {
                    $params['on_before_update']($sql);
                }

                if ($params['revisions']) {
                    module_safe('manage_revisions')->add([
                        'object_name' => $params['table'],
                        'object_id' => $id,
                        'old' => $a,
                        'new' => $_POST,
                        'action' => 'update',
                    ]);
                }

                $db->update_safe($table, $sql, '`' . $db->es($primary_field) . '`="' . $db->es($id) . '"');
                common()->admin_wall_add([$_GET['object'] . ': updated record in table ' . $table, $id]);

                if (is_callable($params['on_after_update'])) {
                    $params['on_after_update']($sql);
                }
                return js_redirect($replace['form_action']);
            }
            if (is_callable($params['on_error'])) {
                $params['on_error']();
            }
        }
        $DATA = $a;
        if (is_callable($params['on_before_show'])) {
            $params['on_before_show']($DATA);
        }
        foreach ((array) $a as $k => $v) {
            if ( ! isset($replace[$k])) {
                $replace[$k] = $DATA[$k];
            }
        }
        if ($params['return_form']) {
            return form($replace)->auto($table, $id, [
                'links_add' => $params['links_add'],
                'db' => $db,
            ]);
        }
        return $replace;
    }

    /**
     * @param mixed $params
     */
    public function delete($params = [])
    {
        if (is_string($params)) {
            $params = [
                'table' => $params,
            ];
        }
        if ( ! is_array($params)) {
            $params = [];
        }
        $params += (array) $this->params;

        $db = is_object($params['db']) ? $params['db'] : db();
        $table = $db->_fix_table_name($params['table']);
        if ( ! $table) {
            _re('Wrong table name');
            return false;
        }
        $fields = $params['fields'];
        $primary_field = $params['id'] ? $params['id'] : 'id';
        $id = isset($params['input_' . $primary_field]) ? $params['input_' . $primary_field] : $_GET['id'];

        if ( ! empty($id)) {
            if (is_callable($params['on_before_update'])) {
                $params['on_before_update']($fields);
            }

            if ($params['revisions']) {
                $a = $db->get('SELECT * FROM ' . $db->es($table) . ' WHERE `' . $db->es($primary_field) . '`="' . $db->es($id) . '"');
                module_safe('manage_revisions')->add([
                    'object_name' => $params['table'],
                    'object_id' => $id,
                    'old' => $a,
                    'action' => 'delete',
                ]);
            }

            $db->query('DELETE FROM ' . $db->es($table) . ' WHERE `' . $db->es($primary_field) . '`="' . $db->es($id) . '" LIMIT 1');
            common()->admin_wall_add([$_GET['object'] . ': deleted record from table ' . $table, $id]);

            if (is_callable($params['on_after_update'])) {
                $params['on_after_update']($fields);
            }
        }
        if (conf('IS_AJAX')) {
            echo $_GET['id'];
        } else {
            return js_redirect(url('/@object/' . _add_get() . $params['links_add']));
        }
    }

    /**
     * @param mixed $params
     */
    public function active($params = [])
    {
        if (is_string($params)) {
            $params = [
                'table' => $params,
            ];
        }
        if ( ! is_array($params)) {
            $params = [];
        }
        $params += (array) $this->params;

        $db = is_object($params['db']) ? $params['db'] : db();
        $table = $db->_fix_table_name($params['table']);
        if ( ! $table) {
            _re('Wrong table name');
            return false;
        }
        $fields = $params['fields'];
        $primary_field = $params['id'] ? $params['id'] : 'id';
        $id = isset($params['input_' . $primary_field]) ? $params['input_' . $primary_field] : $_GET['id'];

        if ( ! empty($id)) {
            $info = $db->query_fetch('SELECT * FROM ' . $db->es($table) . ' WHERE `' . $db->es($primary_field) . '`="' . $db->es($id) . '" LIMIT 1');
        }
        if ($info) {
            if (is_callable($params['on_before_update'])) {
                $params['on_before_update']($info);
            }

            if ($params['revisions']) {
                $n = $info;
                $n['active'] = (int) ! $info['active'];
                module_safe('manage_revisions')->add([
                    'object_name' => $params['table'],
                    'object_id' => $id,
                    'old' => $info,
                    'new' => $n,
                    'action' => 'active',
                ]);
            }

            $db->update_safe($table, ['active' => (int) ! $info['active']], $db->es($primary_field) . '="' . $db->es($id) . '"');
            common()->admin_wall_add([$_GET['object'] . ': item in table ' . $table . ' ' . ($info['active'] ? 'inactivated' : 'activated'), $id]);

            if (is_callable($params['on_after_update'])) {
                $params['on_after_update']($info);
            }
        }
        if (conf('IS_AJAX')) {
            echo $info['active'] ? 0 : 1;
        } else {
            return js_redirect(url('/@object/' . _add_get() . $params['links_add']));
        }
    }

    /**
     * @param mixed $params
     */
    public function clone_item($params = [])
    {
        if (is_string($params)) {
            $params = [
                'table' => $params,
            ];
        }
        if ( ! is_array($params)) {
            $params = [];
        }
        $params += (array) $this->params;

        $db = is_object($params['db']) ? $params['db'] : db();
        $table = $db->_fix_table_name($params['table']);
        if ( ! $table) {
            return false;
        }
        $fields = $params['fields'];
        $primary_field = $params['id'] ? $params['id'] : 'id';
        $id = isset($params['input_' . $primary_field]) ? $params['input_' . $primary_field] : $_GET['id'];

        if ( ! empty($id)) {
            $info = $db->query_fetch('SELECT * FROM ' . $db->es($table) . ' WHERE `' . $db->es($primary_field) . '`="' . $db->es($id) . '" LIMIT 1');
        }
        if ($info) {
            $sql = $info;
            unset($sql[$primary_field]);

            if (is_callable($params['on_before_update'])) {
                $params['on_before_update']($sql);
            }

            $db->insert_safe($table, $sql);
            $new_id = $db->insert_id();

            if ($params['revisions']) {
                module_safe('manage_revisions')->add($params['table'], $new_id, 'add');
            }

            common()->admin_wall_add([$_GET['object'] . ': item cloned in table ' . $table, $new_id]);

            if (is_callable($params['on_after_update'])) {
                $params['on_after_update']($sql, $new_id);
            }
        }
        if (conf('IS_AJAX')) {
            echo $new_id ? 1 : 0;
        } else {
            return js_redirect(url('/@object/' . _add_get() . $params['links_add']));
        }
    }

    /**
     * @param mixed $params
     */
    public function filter_save($params = [])
    {
        if ( ! is_array($params)) {
            $params = [];
        }
        $filter_save_silent = $params['filter_save_silent'] ?: $this->_params['filter_save_silent'];
        $redirect_url = $params['redirect_url'] ?: $this->_params['redirect_url'];
        $filter_data = $params['filter_data'] ?: $_GET['filter'];
        $filter_name = $params['filter_name'] ?: $this->_params['filter_name'] ?: $_GET['object'] . '__show';
        $need_clear = $params['filter_clear'] ?: ($_GET['id'] == 'clear' || $_GET['page'] == 'clear');

        $data = $_SESSION[$filter_name];
        if ($need_clear) {
            $data = [];
        }
        if ($need_clear || $filter_save_silent) {
            // Example: &filter=admin_id:1,ip:127.0.0.1
            if ($filter_data) {
                foreach (explode(',', $filter_data) as $item) {
                    list($k, $v) = explode(':', $item);
                    if ($k && isset($v)) {
                        $data[$k] = $v;
                    }
                }
            }
        } elseif ($_POST) {
            $data = $_POST;
            foreach (explode('|', 'clear_url|form_id|submit|_token') as $f) {
                if (isset($data[$f])) {
                    unset($data[$f]);
                }
            }
        }
        $_SESSION[$filter_name] = $data;

        if ($filter_save_silent) {
            return $data;
        }
        return js_redirect($redirect_url ?: url('/@object/' . str_replace($_GET['object'] . '__', '', $filter_name)));
    }

    /**
     * @param mixed $params
     */
    public function _show_filter($params = [])
    {
        $params += (array) $this->params;

        if ( ! in_array($_GET['action'], ['show'])) {
            return false;
        }
        $filter_name = $_GET['object'] . '__' . $_GET['action'];
        $r = [
            'form_action' => url('/@object/filter_save/' . $filter_name),
            'clear_url' => url('/@object/filter_save/' . $filter_name . '/clear'),
        ];
        $order_fields = [];
        foreach (explode('|', 'admin_id|login|group|date|ip|user_agent|referer') as $f) {
            $order_fields[$f] = $f;
        }
        return form($r, [
                'selected' => $_SESSION[$filter_name],
                'db' => $params['db'],
            ])
            ->number('admin_id')
            ->text('ip')
            ->select_box('order_by', $order_fields, ['show_text' => 1])
            ->radio_box('order_direction', ['asc' => 'Ascending', 'desc' => 'Descending'])
            ->save_and_clear();
    }

    /**
     * @param mixed $params
     */
    public function _get_filter($params = [])
    {
        $filter_name = $params['filter_name'] ?: $this->_params['filter_name'];
        if ( ! $filter_name) {
            $filter_name = $_GET['object'] . '__' . $_GET['action'];
        }
        return (array) $_SESSION[$filter_name];
    }

    /**
     * Return default config used by CKEditor.
     * @param mixed $params
     */
    public function _get_cke_config($params = [])
    {
        asset('ckeditor-plugin-save');
        asset('ckeditor-plugin-autosave');
        asset('ckeditor-plugin-html5-video');
        asset('ckeditor-plugin-youtube');
        //		asset('ckeditor-plugin-fontawesome4');

        $override = [];
        if ( ! is_array($params)) {
            $params = [];
        }
        foreach ((array) $this->default_ckeditor_params as $k => $v) {
            if ( ! isset($params[$k])) {
                $params[$k] = $v;
            }
        }
        $toolbar_add = [];
        if ($params['toolbar_add']) {
            $toolbar_add = $params['toolbar_add'];
            unset($params['toolbar_add']);
        }
        if ($params['file_browser'] === 'internal' && MAIN_TYPE_ADMIN) {
            $override += [
                'filebrowserBrowseUrl' => url('/ck_file_browser'),
                'filebrowserUploadUrl' => url('/ck_file_browser'),
                'filebrowserImageBrowseUrl' => url('/ck_file_browser'),
                'filebrowserImageUploadUrl' => url('/ck_file_browser/upload_image/' . (int) ($_GET['id']) . '/?type=image'),
//				'filebrowserFlashBrowseUrl' => url('/ck_file_browser'),
//				'filebrowserFlashUploadUrl' => url('/ck_file_browser/upload_image/'.intval($_GET['id']).'/?type=flash'),
            ];
            unset($params['file_browser']);
        }
        foreach ((array) $params as $k => $v) {
            $override[$k] = $v;
        }
        return (array) $override + [
            'toolbar' => [
                [
                    'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo', 'RemoveFormat', 'Format', 'Bold', 'Italic', 'Underline',
                    'FontSize', 'TextColor', 'NumberedList', 'BulletedList', 'Table', '-', 'Blockquote', 'Link', 'Unlink', 'Image', 'Video', 'Youtube', '-',
                    'SpecialChar', 'FontAwesome', '-', 'Source', '-', 'Save', '-', 'Maximize', //, 'Preview'
                ] + (array) $toolbar_add, // TODO: testme
            ],
            'language' => conf('language'),
            'removeButtons' => 'Flash',
            'removePlugins' => 'bidi,dialogadvtab,horizontalrule,pagebreak,showborders,templates',
            'format_tags' => 'p;h1;h2;h3;h4;h5;h6;pre;address', //,div',
//			'allowedContent' => true,
            'extraAllowedContent' => implode('; ', ['a[*]{*}(*)', 'img[*]{*}(*)', 'video[*]{*}', 'source[*]{*}', 'div(*){*}[*]', 'table', 'tr', 'th', 'td', 'caption']),
            'extraPlugins' => 'autosave,video,youtube', //,preview', //,widget,lineutils,fontawesome',
            'forcePasteAsPlainText' => true,
//			'contentsCss' => array(
//				'http://netdna.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css',
//				'http://netdna.bootstrapcdn.com/bootswatch/3.3.2/slate/bootstrap.min.css',
//				'http://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css'
//			),
        ];
        // Other config variant example
        /*
        return '
            CKEDITOR.replace("'.$textarea_id.'", {
                toolbar: [
                    [ "Cut", "Copy", "Paste", "PasteText", "PasteFromWord", "-", "Undo", "Redo" ], [ "RemoveFormat" ], [ "Bold", "Italic", "Underline" ],
                    [ "FontSize" ], [ "TextColor" ], [ "NumberedList", "BulletedList", "-", "Blockquote" ], [ "Link", "Unlink", "SpecialChar" ], [ "Source" ], [ "Maximize" ]
                ],
                language: "'.conf('language').'",
                removePlugins: "bidi,dialogadvtab,div,filebrowser,flash,horizontalrule,iframe,pagebreak,showborders,stylescombo,table,tabletools,templates",
            });
        ';*/
    }

    /**
     * @param mixed $link
     */
    public function _admin_link_is_allowed($link = '')
    {
        // Currently this works only for admin section
        if (MAIN_TYPE == 'user') {
            return false;
        }
        // Guests can see nothing
        if ( ! strlen($link) || ! main()->ADMIN_ID || MAIN_TYPE == 'user') {
            return false;
        }
        // Super-admin can see any links
        if (main()->ADMIN_GROUP === 1) {
            return true;
        }
        $link_parts = parse_url($link);
        // Outer links simply allowed
        if (isset($link_parts['scheme']) && $link_parts['host'] && $link_parts['path']) {
            if ($link_parts['host'] . $link_parts['path'] != $this->ADMIN_URL_HOST . $this->ADMIN_URL_PATH) {
                return true;
            }
        }
        // Maybe this is also outer link and no need to block it (or maybe rewrited?)
        if ( ! isset($link_parts['query'])) {
            return true;
        }
        parse_str($link_parts['query'], $u);
        $u = (array) $u;
        if (isset($u['task']) && in_array($u['task'], ['login', 'logout'])) {
            return true;
        }
        return (int) _class('core_blocks')->_check_block_rights($this->CENTER_BLOCK_ID, $u['object'], $u['action']);
    }

    /**
     * @param mixed $data
     */
    public function admin_wall_add($data = [])
    {
        return db()->insert_safe('admin_walls', [
            'message' => isset($data['message']) ? $data['message'] : (isset($data[0]) ? $data[0] : ''),
            'object_id' => isset($data['object_id']) ? $data['object_id'] : (isset($data[1]) ? $data[1] : ''),
            'user_id' => isset($data['user_id']) ? $data['user_id'] : (isset($data[2]) ? $data[2] : main()->ADMIN_ID),
            'object' => isset($data['object']) ? $data['object'] : (isset($data[3]) ? $data[3] : $_GET['object']),
            'action' => isset($data['action']) ? $data['action'] : (isset($data[4]) ? $data[4] : $_GET['action']),
            'important' => isset($data['important']) ? $data['important'] : (isset($data[5]) ? $data[5] : 0),
            'old_data' => json_encode(isset($data['old_data']) ? $data['old_data'] : (isset($data[6]) ? $data[6] : '')),
            'new_data' => json_encode(isset($data['new_data']) ? $data['new_data'] : (isset($data[7]) ? $data[7] : '')),
            'add_date' => date('Y-m-d H:i:s'),
            'server_id' => (int) main()->SERVER_ID,
            'site_id' => (int) main()->SITE_ID,
        ]);
    }

    /**
     * This method will search and call all found hook methods from active modules.
     * @param mixed $hook_name
     * @param mixed $section
     */
    public function call_hooks($hook_name, &$params = [], $section = 'all')
    {
        $data = [];
        foreach ((array) $this->find_hooks($hook_name) as $module => $methods) {
            foreach ((array) $methods as $method) {
                $data[$module . '__' . $method] = module($module)->$method($params);
            }
        }
        return $data;
    }

    /**
     * This method will search for hooks alongside active modules.
     * @param mixed $hook_name
     * @param mixed $section
     */
    public function find_hooks($hook_name, $section = 'all')
    {
        $hooks = [];
        foreach ((array) $this->find_all_hooks($section) as $module => $_hooks) {
            foreach ((array) $_hooks as $name => $method_name) {
                if ($name == $hook_name) {
                    $hooks[$module][$name] = $method_name;
                }
            }
        }
        return $hooks;
    }

    /**
     * This method will search for hooks alongside active modules.
     * @param mixed $section
     */
    public function find_all_hooks($section = 'all')
    {
        if ( ! in_array($section, ['all', 'user', 'admin'])) {
            $section = 'all';
        }
        $cache_name = __FUNCTION__ . '__' . $section;
        $data = cache_get($cache_name);
        if ($data) {
            return $data;
        }
        $hooks_prefix = '_hook_';
        $hooks_pl = strlen($hooks_prefix);

        $modules = $this->find_active_modules($section);
        $user_modules = $modules['user'];
        foreach ((array) $user_modules as $module) {
            foreach ((array) get_class_methods(module($module)) as $method) {
                if (substr($method, 0, $hooks_pl) != $hooks_prefix) {
                    continue;
                }
                $hooks[$module][substr($method, $hooks_pl)] = $method;
            }
            if (is_array($hooks[$module])) {
                ksort($hooks[$module]);
            }
        }
        $admin_modules = $modules['admin'];
        foreach ((array) $admin_modules as $module) {
            foreach ((array) get_class_methods(module($module)) as $method) {
                if (substr($method, 0, $hooks_pl) != $hooks_prefix) {
                    continue;
                }
                $hooks[$module][substr($method, $hooks_pl)] = $method;
            }
            if (is_array($hooks[$module])) {
                ksort($hooks[$module]);
            }
        }
        if (is_array($hooks)) {
            ksort($hooks);
        }
        cache_set($cache_name, $hooks);
        return $hooks;
    }

    /**
     * This method will search for hooks alongside active modules.
     * @param mixed $section
     */
    public function find_active_modules($section = 'all')
    {
        if ( ! in_array($section, ['all', 'user', 'admin'])) {
            $section = 'all';
        }
        $cache_name = __FUNCTION__ . '__' . $section;
        $data = cache_get($cache_name);
        if ($data) {
            return $data;
        }
        if (in_array($section, ['all', 'user'])) {
            $user_modules = module('user_modules')->_get_modules(['with_sub_modules' => 1]);
        }
        if (in_array($section, ['all', 'admin'])) {
            $admin_modules_prefix = 'admin:';
            foreach ((array) module('admin_modules')->_get_modules(['with_sub_modules' => 1]) as $module_name) {
                $admin_modules[$admin_modules_prefix . $module_name] = $module_name;
            }
        }
        $modules = [];
        if ( ! empty($admin_modules)) {
            $modules['admin'] = $admin_modules;
        }
        if ( ! empty($user_modules)) {
            $modules['user'] = $user_modules;
        }
        cache_set($cache_name, $hooks);
        return $modules;
    }
}
