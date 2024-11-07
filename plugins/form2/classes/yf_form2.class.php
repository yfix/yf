<?php

/**
 * Form2 high-level generator and handler, mostly using bootstrap html/css framework.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_form2
{
    public $CLASS_FORM_MAIN = 'form-horizontal';// col-md-6'
    public $CLASS_FORM_CONTROL = 'form-control';
    public $CLASS_CKEDITOR = 'ckeditor';
    public $CLASS_TPL_BADGE = 'badge badge-%name';
    public $CLASS_TPL_LABEL = 'label label-%name';
    public $CLASS_BTN_MINI = 'btn btn-default btn-mini btn-xs';
    public $CLASS_BTN_DEFAULT = 'btn btn-default';
    public $CLASS_BTN_SUBMIT = 'btn btn-default btn-primary';
    public $CLASS_ICON_SAVE = 'icon-save fa fa-save';
    public $CLASS_ICON_PSWD = 'icon-key fa fa-key fa-fw';
    public $CLASS_ICON_LOGIN = 'icon-user fa fa-user fa-fw';
    public $CLASS_ICON_EMAIL = 'icon-email fa fa-at fa-fw';
    public $CLASS_ICON_CURRENCY = 'icon-dollar fa fa-dollar fa-fw';
    public $CLASS_ICON_CALENDAR = 'icon icon-calendar fa fa-calendar fa-fw';
    public $CLASS_ICON_PREVIEW = 'icon-eye fa fa-eye';
    public $CLASS_LABEL_INFO = 'label label-info';
    public $CLASS_ERROR = 'alert alert-error alert-danger';
    public $CLASS_REQUIRED = 'control-group-required form-group-required';
    public $CLASS_STACKED_ROW = 'stacked-row';

    public $CONF_BOXES_USE_BTN_GROUP = false;
    public $CONF_CSRF_PROTECTION = true;
    public $CONF_CSRF_NAME = '_token';
    public $CONF_FORM_ID_FIELD = '__form_id__';
    public $CONF_FORM_AUTOID_PREFIX = 'form_autoid_';

    public $_chained_mode = null;
    public $_extend = [];
    public $_replace = [];
    public $_params = [];
    public $_sql = null;
    public $_form_id = null;
    public $_isset_hidden_form_id = null;
    public $_isset_hidden_token = null;
    public $_rendered = null;
    public $_extra = null;
    public $_body = [];
    public $_validate_rules = [];
    public $_validate = null;
    public $_db_change_if_ok = null;
    public $_on = [];
    public $_stacked_mode_on = null;
    public $_tabbed_mode_on = null;
    public $_tabs_counter = 0;
    public $_tabs_name = null;
    public $_tabs_extra = null;
    public $_pair_allow_deny = null;
    public $_pair_yes_no = null;
    public $_validate_rules_names = [];

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args, $this->_chained_mode);
    }

    /**
     * We cleanup object properties when cloning.
     */
    public function __clone()
    {
        $keep_prefix = 'CLASS_';
        $keep_len = strlen($keep_prefix);
        $keep_prefix2 = 'CONF_';
        $keep_len2 = strlen($keep_prefix2);
        foreach ((array) get_object_vars($this) as $k => $v) {
            if (substr($k, 0, $keep_len) === $keep_prefix) {
                continue;
            }
            if (substr($k, 0, $keep_len2) === $keep_prefix2) {
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
     * Wrapper for template engine
     * Example:
     *	return form($replace)
     *		->text('login','Login')
     *		->text('password','Password')
     *		->text('first_name','First Name')
     *		->text('last_name','Last Name')
     *		->text('go_after_login','Url after login')
     *		->box_with_link('group_box','Group','groups_link')
     *		->active('active','Active')
     *		->info('add_date','Added');.
     * @param mixed $replace
     * @param mixed $params
     */
    public function chained_wrapper($replace = [], $params = [])
    {
        if ($replace && is_string($replace)) {
            $sql = $replace;
            $this->_sql = $sql;
            $db = is_object($params['db']) ? $params['db'] : db();
            $replace = $db->get_2d($sql);
        }
        if ( ! is_array($replace)) {
            $replace = [];
        }
        if (isset($params['filter']) && ! is_array($params['filter']) && is_numeric($params['filter']) || is_bool($params['filter']) && ! empty($params['filter'])) {
            $filter_name = $params['filter_name'] ?: $_GET['object'] . '__' . $_GET['action'];
            $params['selected'] = $_SESSION[$filter_name];
            $replace['form_action'] = $replace['form_action'] ?: url('/@object/filter_save/' . $filter_name);
            $replace['clear_url'] = $replace['clear_url'] ?: url('/@object/filter_save/' . $filter_name . '/clear');
            if (MAIN_TYPE_ADMIN && ! isset($params['class'])) {
                $params['class'] = 'form-vertical';
            }
        }
        if ( ! $params['no_chained_mode']) {
            $this->_chained_mode = true;
        }
        $this->_replace = $replace;
        $this->_params = $params;
        return $this;
    }

    /**
     * @param mixed $a
     * @param mixed $params
     * @param mixed $replace
     */
    public function array_to_form($a = [], $params = [], $replace = [])
    {
        $this->_params = $params + (array) $this->_params;
        $this->_replace = $replace + (array) $this->_replace;
        // Example of row: ['text', 'login', ['class' => 'input-medium']]
        foreach ((array) $a as $v) {
            $func = '';
            if (is_string($v)) {
                $func = $v;
                $v = [];
            } elseif (is_array($v)) {
                $func = $v[0];
            }
            if ( ! $func || ! method_exists($this, $func)) {
                continue;
            }
            $this->$func($v[1], $v[2], $v[3], $v[4], $v[5]);
        }
        return $this;
    }

    /**
     * Wrapper for template engine
     * Example template:
     *	{form_row('form_begin')}
     *	{form_row('text','login')}
     *	{form_row('text','password')}
     *	{form_row('text','first_name')}
     *	{form_row('text','last_name')}
     *	{form_row('text','go_after_login','Url after login')}
     *	{form_row('box_with_link','group_box','Group','groups_link')}
     *	{form_row('active_box')}
     *	{form_row('info','add_date','Added')}
     *	{form_row('save_and_back')}
     *	{form_row('form_end')}.
     *
     *	{catch("field_name")}some_other_field{/catch} {form_row('text','%field_name')}
     *	{catch("t_password")}My password inside replace['t_password']{/catch} {form_row('text','pswd','%t_password')}
     * @param mixed $type
     * @param mixed $replace
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     */
    public function tpl_row($type = 'input', $replace = [], $name = '', $desc = '', $extra = [])
    {
        $name = trim($name);
        if ($name && $name[0] == '%') {
            $_name = substr($name, 1);
            if (isset($replace[$_name])) {
                $name = $replace[$_name];
            }
        }
        $desc = trim($desc);
        if ($desc && $desc[0] == '%') {
            $_desc = substr($desc, 1);
            if (isset($replace[$_desc])) {
                $desc = $replace[$_desc];
            }
        }
        // Allow to pass extra params like this: param1=val1;param2=val2
        if (is_string($extra)) {
            $extra = trim($extra);
            if (false !== strpos($extra, ';') && false !== strpos($extra, '=')) {
                $extra = _attrs_string2array($extra);
            }
        }
        if ( ! is_array($extra)) {
            // Suppose we have 3rd argument as edit link here
            if ( ! empty($extra)) {
                $extra = ['edit_link' => $extra];
            } else {
                $extra = [];
            }
        }
        return $this->$type($name, $desc, $extra, $replace);
    }

    /**
     * Enable automatic fields parsing mode.
     * @param mixed $table
     * @param mixed $id
     * @param mixed $params
     */
    public function auto($table = '', $id = '', $params = [])
    {
        return _class('form2_auto', 'classes/form2/')->auto($table, $id, $params, $this);
    }

    /**
     * @param mixed $form_id
     */
    public function _get_extra_override($form_id = '')
    {
        if ( ! strlen($form_id)) {
            return [];
        }
        $autoid_prefix = $this->CONF_FORM_AUTOID_PREFIX;
        if (is_unit_test() || (strlen($autoid_prefix) && substr($form_id, 0, strlen($autoid_prefix)) === $autoid_prefix)) {
            return [];
        }
        $extra_override = [];
        // Data from database have highest priority, so we init it first
        $all_attrs_override = main()->get_data('form_attributes');
        $extra_override = $all_attrs_override[$form_id];
        // Search for override params inside shared files
        $suffix = $form_id . '.form.php';
        $slen = strlen($suffix);
        $patterns = [
            'framework' => [
                YF_PATH . 'form/' . $form_id . '*' . $suffix,
                YF_PATH . 'plugins/*/form/' . $form_id . '*' . $suffix,
                YF_PATH . 'share/form/' . $form_id . '*' . $suffix,
            ],
            'config' => [
                CONFIG_PATH . 'form/' . $form_id . '*' . $suffix,
                CONFIG_PATH . 'plugins/*/form/' . $form_id . '*' . $suffix,
                CONFIG_PATH . 'share/form/' . $form_id . '*' . $suffix,
            ],
            'project' => [
                PROJECT_PATH . 'form/' . $form_id . '*' . $suffix,
                PROJECT_PATH . 'plugins/*/form/' . $form_id . '*' . $suffix,
                PROJECT_PATH . 'share/form/' . $form_id . '*' . $suffix,
            ],
        ];
        if (SITE_PATH != PROJECT_PATH) {
            $patterns['site'] = [
                SITE_PATH . 'form/' . $form_id . '*' . $suffix,
                SITE_PATH . 'plugins/*/form/' . $form_id . '*' . $suffix,
                SITE_PATH . 'share/form/' . $form_id . '*' . $suffix,
            ];
        }
        $names = [];
        foreach ($patterns as $paths) {
            foreach ($paths as $path) {
                foreach (glob($path) as $matchedPath) {
                    $name = substr(basename($matchedPath), 0, -$slen);
                    $names[$name] = $matchedPath;
                }
            }
        }
        // Allow override framework defaults inside project
        foreach ($names as $name => $path) {
            $data = [];
            include $path;
            foreach ((array) $data as $field => $attrs) {
                $extra_override[$field] = (array) $extra_override[$field] + (array) $attrs;
            }
        }
        return $extra_override;
    }

    /**
     * @param mixed $extra
     * @param mixed $replace
     */
    public function _get_form_id($extra = [], $replace = [])
    {
        $form_id = $this->_form_id;
        $replace = $replace ?: $this->_replace;
        $params = &$this->_params;
        if (isset($replace[$this->CONF_FORM_ID_FIELD]) && $replace[$this->CONF_FORM_ID_FIELD]) {
            $form_id = $replace[$this->CONF_FORM_ID_FIELD];
        } elseif (isset($params[$this->CONF_FORM_ID_FIELD]) && $params[$this->CONF_FORM_ID_FIELD]) {
            $form_id = $params[$this->CONF_FORM_ID_FIELD];
        }
        if ( ! $form_id) {
            $form_id = $this->CONF_FORM_AUTOID_PREFIX . strtolower($_GET['object'] . '_' . $_GET['action']) . '_' . ++main()->_unique_widget_ids['form'];
        }
        $this->_form_id = $form_id;
        return $form_id;
    }

    /**
     * @param mixed $form_id
     */
    public function _set_hidden_form_id($form_id = '')
    {
        if ($this->_isset_hidden_form_id) {
            return true;
        }
        if ( ! $form_id) {
            $form_id = $this->_get_form_id();
        }
        $this->_replace[$this->CONF_FORM_ID_FIELD] = $form_id;
        $this->hidden($this->CONF_FORM_ID_FIELD, ['value' => $form_id]);
        $this->_isset_hidden_form_id = true;
    }

    /**
     * @param mixed $csrf_guard
     */
    public function _set_hidden_token($csrf_guard)
    {
        if ($this->_isset_hidden_token) {
            return true;
        }
        $this->_replace[$this->CONF_CSRF_NAME] = $csrf_guard->generate();
        $this->hidden($this->CONF_CSRF_NAME);
        $this->_isset_hidden_token = true;
    }

    /**
     * Render result form html, gathered by row functions
     * Params here not required, but if provided - will be passed to form_begin().
     * @param mixed $extra
     * @param mixed $replace
     */
    public function render($extra = [], $replace = [])
    {
        if (isset($this->_rendered)) {
            return $this->_rendered;
        }
        if (DEBUG_MODE) {
            $ts = microtime(true);
        }
        _class('core_events')->fire('form.before_render', [$extra, $replace, $this]);
        $this->_extra = $extra;
        $on_before_render = $extra['on_before_render'] ?? $this->_on['on_before_render'] ?? null;
        if (is_callable($on_before_render)) {
            $on_before_render($extra, $replace, $this);
        }
        if ( ! is_array($this->_body)) {
            $this->_body = [];
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra_override = [];
        $form_id = $this->_get_form_id($extra, $replace);
        if ($form_id) {
            $extra_override = $this->_get_extra_override($form_id);
        }
        $headless_form = ($extra['no_form'] || $this->_params['no_form']);

        $csrf_protect = isset($extra['csrf']) ? (bool) $extra['csrf'] : (isset($this->_params['csrf']) ? $this->_params['csrf'] : $this->CONF_CSRF_PROTECTION);
        if (isset($extra['method']) && strtolower($extra['method']) != 'post') {
            $csrf_protect = false;
        }
        if ($headless_form) {
            $csrf_protect = false;
        }
        if ($csrf_protect && is_callable($csrf_protect)) {
            $csrf_protect = $csrf_protect($this, $extra);
        }
        if ($csrf_protect) {
            $csrf_guard = _class('csrf_guard')->configure([
                'form_id' => $form_id,
                'token_name' => $this->CONF_CSRF_NAME,
            ]);
        }
        if (is_post()) {
            $is_current_form = isset($_POST[$this->CONF_FORM_ID_FIELD]) && ($_POST[$this->CONF_FORM_ID_FIELD] == $form_id);
            if ($csrf_protect && $is_current_form && ! $csrf_guard->validate($_POST[$this->CONF_CSRF_NAME])) {
                // We need this as validation now is skipping empty values
                if ( ! isset($_POST[$this->CONF_CSRF_NAME]) || (trim($_POST[$this->CONF_CSRF_NAME]) == '')) {
                    $_POST[$this->CONF_CSRF_NAME] = '__wrong_token_' . md5(microtime()) . '__';
                }
                $this->_params['show_alerts'] = true;
                $this->_validate_rules[$this->CONF_CSRF_NAME] = function ($in, $p, $a, &$error_msg) use ($form_id, $csrf_guard) {
                    $csrf_guard->log_error(['form_id' => $form_id]);
                    $error_msg = 'Invalid CSRF token. Send the form again. If you did not send this request then close this page.';
                    return false;
                };
                $this->_set_hidden_token($csrf_guard);
            }

            $on_post = isset($extra['on_post']) ? $extra['on_post'] : $this->_on['on_post'];
            if (is_callable($on_post)) {
                $on_post($extra, $replace, $this);
            }
            $v = $this->_validate;
            if (isset($v) && is_callable($v['func'])) {
                $func = $v['func'];
                $func($v['validate_rules'], $v['post'], $v['extra'], $this);
            }
            $up = $this->_db_change_if_ok;
            if (isset($up) && is_callable($up['func'])) {
                $func = $up['func'];
                $func($up['table'], $up['fields'], $up['type'], $up['extra'], $this);
            }
        }
        if ($csrf_protect) {
            $this->_set_hidden_token($csrf_guard);
        }

        $r = (array) $this->_replace + (array) $replace;

        if ( ! $headless_form) {
            // Call these methods, if not done yet, save 2 api calls
            if ( ! isset($this->_body['form_begin'])) {
                $this->form_begin('', '', $extra + ($extra_override['form_begin'] ?? []), $r);
            }
            if ( ! isset($this->_body['form_end'])) {
                $this->form_end($extra + ($extra_override['form_end'] ?? []), $r);
            }
            // Force form_begin as first array element
            $form_begin = $this->_body['form_begin'];
            unset($this->_body['form_begin']);
            array_unshift($this->_body, $form_begin);

            // Force form_end as last array element
            $form_end = $this->_body['form_end'];
            unset($this->_body['form_end']);
            $this->_body['form_end'] = $form_end;
        }

        $tabbed_mode = false;
        $tabbed_buffer = [];
        $tabs = [];
        $tabs_extra = [];
        $tabs_name = '';
        $tabs_container = '';

        // Create tree of row_start and its children
        $item_row = [];
        $row_items = [];
        $row = false;
        $body_ids_to_extra = [];
        foreach ((array) $this->_body as $k => $v) {
            if ($v['name'] == 'row_start') {
                $row = $k;
            } elseif ($v['name'] == 'row_end') {
                $row = false;
            } elseif ($row) {
                $item_row[$k] = $row;
                $row_items[$row][$k] = $v['extra']['id'] ?: $v['extra']['name'];
            }
        }
        $all_errors = common()->_get_error_messages();

        foreach ((array) $this->_body as $k => $v) {
            if ( ! is_array($v)) {
                continue;
            }
            $_extra = ($v['extra'] ?? []) + ($extra_override[$v['extra']['name']] ?? []);
            $_replace = ($r ?? []) + ($v['replace'] ?? []);
            $func = $v['func'];
            if ($v['name'] == 'row_start') {
                // Mark row as containing errors, if children elements has at least one error
                foreach ((array) $row_items[$k] as $_k => $_id) {
                    if ( ! $_id || ! isset($all_errors[$_id])) {
                        continue;
                    }
                    $_extra['errors'][$v['extra']['name']] = $all_errors[$_id];
                }
            }
            if ($this->_stacked_mode_on) {
                $_extra['stacked'] = $_extra['stacked'] ?: true;
            }
            // Callback to decide if we need to show this field or not
            if (isset($_extra['display_func']) && is_callable($_extra['display_func'])) {
                $_display_allowed = $_extra['display_func']($_extra, $_replace, $this);
                if ( ! $_display_allowed) {
                    $this->_body[$k] = '';
                    continue;
                }
            }
            if (DEBUG_MODE) {
                $_debug_fields[$k] = [
                    'name' => $v['name'],
                    'extra' => $_extra,
                ];
            }
            $this->_body[$k]['rendered'] = $func($_extra, $_replace, $this);

            if ($this->_tabbed_mode_on) {
                $tabbed_mode = true;
                $tabbed_buffer[$k] = $this->_body[$k]['rendered'];
                if ($v['name'] == 'tab_start') {
                    $this->_tabs_counter++;
                    $tabs_name = $this->_tabs_name ?: 'tabs_' . $this->_tabs_counter;
                    $tabs_extra['by_id'][$tabs_name] = $this->_tabs_extra;
                }
                if ($v['name'] == 'tab_start' && ! $tabs_container) {
                    $tabs_container = $k;
                    $this->_body[$k]['rendered'] = '__TAB_START__';
                } else {
                    unset($this->_body[$k]);
                }
            } elseif ($tabbed_mode) { // switch off
                if ( ! $this->_tabbed_mode_on) {
                    $tabbed_mode = false;
                }
                $tabs[$tabs_name] = implode(PHP_EOL, $tabbed_buffer);
                $tabbed_buffer = [];
                $tabs_name = '';
            }
        }
        if ($tabbed_buffer) {
            $tabs['tab_last'] = implode(PHP_EOL, $tabbed_buffer);
            $tabbed_buffer = [];
        }
        if ($tabs) {
            $this->_body[$tabs_container]['rendered'] = html()->tabs($tabs, (array) $this->_params['tabs'] + (array) $tabs_extra);
        }
        if ($this->_params['show_alerts']) {
            $errors = common()->_get_error_messages();
            if ($errors) {
                $e = [];
                foreach ((array) $errors as $msg) {
                    $e[] = '<div class="' . $this->CLASS_ERROR . '"><button type="button" class="close" data-dismiss="alert">&times;</button>' . $msg . '</div>';
                }
                $this->_body = array_slice($this->_body, 0, 1, true) + ['error_message' => implode(PHP_EOL, $e)] + array_slice($this->_body, 1, null, true);
            }
        }
        if ($this->_params['stpl'] || $this->_params['return_array']) {
            $data = [];
            foreach ($this->_body as $k => $v) {
                $name = fix_html_attr_id($v['extra']['name'] ?: $v['extra']['id'] ?: $k);
                if ($name === 'form_action') {
                    $name = 'form_begin';
                }
                if (isset($data['form'][$name]) && ! empty($data['form'][$name])) {
                    if (in_array($name, ['form_id', '_token', 'token'])) {
                        // allow only once
                    } else {
                        if ( ! is_array($data['form'][$name])) {
                            $tmp = $data['form'][$name];
                            $data['form'][$name] = [];
                            $data['form'][$name][] = $tmp;
                            unset($tmp);
                        }
                        $data['form'][$name][] = $v['rendered'];
                    }
                } else {
                    $_rendered = '';
                    if (is_array($v)) {
                        $_rendered = array_key_exists('rendered', $v) ? (string) $v['rendered'] : '';
                    } else {
                        $_rendered = $v;
                    }
                    $data['form'][$name] = $_rendered;
                }
            }
            // Fixes for easier usage
            if ($data['form']['form_id']) {
                $data['form']['form_begin'] .= PHP_EOL . $data['form']['form_id'];
                unset($data['form']['form_id']);
            }
            if (isset($data['form']['token']) && ! isset($data['form']['_token'])) {
                $data['form']['_token'] = $data['form']['token'];
                unset($data['form']['token']);
            }
            if (isset($data['form']['_token'])) {
                $data['form']['form_begin'] .= PHP_EOL . $data['form']['_token'];
                unset($data['form']['_token']);
            }
            if ( ! isset($data['form']['begin'])) {
                $data['form']['begin'] = $data['form']['form_begin'];
                unset($data['form']['form_begin']);
            }
            if ( ! isset($data['form']['end'])) {
                $data['form']['end'] = $data['form']['form_end'];
                unset($data['form']['form_end']);
            }
            if ($this->_params['return_array']) {
                return $data['form'];
            }
            if (false === strpos($this->_params['stpl'], ' ') && tpl()->exists($this->_params['stpl'])) {
                $this->_rendered = tpl()->parse($this->_params['stpl'], $data);
            } else {
                $this->_rendered = tpl()->parse_string($this->_params['stpl'], $data);
            }

            unset($data);
        } else {
            $rendered = [];
            foreach ($this->_body as $k => $v) {
                if (is_array($v)) {
                    $rendered[$k] = array_key_exists('rendered', $v) ? (string) $v['rendered'] : '';
                } else {
                    $rendered[$k] = $v;
                }
            }
            $this->_rendered = implode(PHP_EOL, $rendered);
            unset($rendered);
        }
        unset($this->_body); // Save some memory

        $css_framework = $extra['css_framework'] ?: ($this->_params['css_framework'] ?: conf('css_framework'));
        $extra['css_framework'] = $css_framework;
        $this->_rendered = _class('html5fw')->form_render_out($this->_rendered, $extra, $r, $this);

        $on_after_render = $extra['on_after_render'] ?? $this->_on['on_after_render'] ?? null;
        if (is_callable($on_after_render)) {
            $on_after_render($extra, $replace, $this);
        }
        _class('core_events')->fire('form.after_render', [$extra, $replace, $this]);
        if (DEBUG_MODE) {
            debug('form2[]', [
                'params' => $this->_params,
                'fields' => $_debug_fields,
                'time' => round(microtime(true) - $ts, 5),
                'trace' => main()->trace_string(),
            ]);
        }
        return $this->_rendered;
    }

    /**
     * @param mixed $name
     * @param mixed $method
     * @param mixed $extra
     * @param mixed $replace
     */
    public function form_begin($name = '', $method = '', $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        // Merge params passed to table2() and params passed here, with params here have more priority:
        $tmp = $this->_params;
        foreach ((array) $extra as $k => $v) {
            $tmp[$k] = $v;
        }
        $extra = $tmp;
        unset($tmp);

        $extra['name'] = $extra['name'] ?: ($name ?: 'form_action');
        $extra['method'] = $extra['method'] ?: ($method ?: 'post');

        $func = function ($extra, $r, $form) {
            $enctype = '';
            if ($extra['enctype']) {
                $enctype = $extra['enctype'];
            } elseif ($extra['for_upload']) {
                $enctype = 'multipart/form-data';
            }
            $extra['enctype'] = $enctype;
            if ( ! isset($extra['action'])) {
                $get_id = isset($_GET['id']) && strlen($_GET['id']) ? urlencode($_GET['id']) : '';
                $get_page = isset($_GET['page']) && strlen($_GET['page']) ? urlencode($_GET['page']) : '';
                $extra['action'] = isset($r[$extra['name']]) ? $r[$extra['name']] : url('/@object/@action/' . $get_id . '/' . $get_page) . $form->_params['links_add'];
            }
            if (MAIN_TYPE_USER) {
                if (strpos($extra['action'], 'http://') === false && strpos($extra['action'], 'https://') !== 0) {
                    $extra['action'] = process_url($extra['action'], true);
                }
            }
            $extra['class'] = $extra['class'] ?: $form->CLASS_FORM_MAIN;// col-md-6';
            if ($extra['class_add']) {
                $extra['class'] .= ' ' . $extra['class_add'];
            }
            $extra['autocomplete'] = isset($extra['autocomplete']) ? $extra['autocomplete'] : true;

            $advanced_js_validation = conf('form_advanced_js_validation');
            if ($advanced_js_validation) {
                $extra['data-fv-framework'] = 'bootstrap';
            }

            $body = '<form' . _attrs($extra, ['method', 'action', 'class', 'style', 'id', 'name', 'autocomplete', 'enctype', 'novalidate', 'target']) . '>' . PHP_EOL;
            $form->_fieldset_mode_on = true;
            $body .= '<fieldset' . _attrs($extra['fieldset'], ['class', 'style', 'id', 'name']) . '>';
            if ($extra['legend']) {
                $body .= PHP_EOL . '<legend>' . _htmlchars(t($extra['legend'])) . '</legend>' . PHP_EOL;
            }
            return $body;
        };
        if ($this->_chained_mode) {
            $this->_body[__FUNCTION__] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $extra
     * @param mixed $replace
     */
    public function form_end($extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $func = function ($extra, $r, $form) {
            $form->_fieldset_mode_on = false;
            $body = '</fieldset>' . PHP_EOL;
            $body .= '</form>' . PHP_EOL;
            return $body;
        };
        if ($this->_chained_mode) {
            $this->_body[__FUNCTION__] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * Shortcut for adding fieldset.
     * @param mixed $name
     * @param mixed $extra
     */
    public function fieldset_start($name = '', $extra = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        $extra['name'] = $extra['name'] ?: $name;
        $func = function ($extra, $r, $form) {
            if ($form->_fieldset_mode_on) {
                $body = '</fieldset>' . PHP_EOL;
            } else {
                $form->_fieldset_mode_on = true;
            }
            $body .= '<fieldset' . _attrs($extra, ['class', 'style', 'id', 'name']) . '>';
            if ($extra['legend']) {
                $body .= PHP_EOL . '<legend>' . _htmlchars(t($extra['legend'])) . '</legend>' . PHP_EOL;
            }
            return $body;
        };
        $replace = [];
        if ($this->_chained_mode || $extra['chained_mode']) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * Paired with fieldset_start.
     * @param mixed $extra
     */
    public function fieldset_end($extra = [])
    {
        $func = function ($extra, $r, $form) {
            if ($form->_fieldset_mode_on) {
                $form->_fieldset_mode_on = false;
                return '</fieldset>' . PHP_EOL;
            }
        };
        $replace = [];
        if ($this->_chained_mode || $extra['chained_mode']) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * Shortcut for starting form row, needed to build row with several inlined inputs.
     * @param mixed $name
     * @param mixed $extra
     */
    public function row_start($name = '', $extra = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        $extra['name'] = $extra['name'] ?: $name;
        $replace = [];
        $func = function ($extra, $r, $form) {
            // auto-close row_end(), if not called implicitely
            if ($form->_stacked_mode_on) {
                $form->row_end();
            }
            $form->_stacked_mode_on = true;
            if ( ! isset($extra['id']) && $extra['name']) {
                $extra['id'] = $extra['name'];
            }
            $extra['class_add_form_group'] = trim($form->CLASS_STACKED_ROW . ' ' . $extra['class_add_form_group']);
            return $form->_row_html('', ['only_row_start' => 1] + (array) $extra);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * Paired with row_start.
     * @param mixed $extra
     */
    public function row_end($extra = [])
    {
        $replace = [];
        $func = function ($extra, $r, $form) {
            $form->_stacked_mode_on = false;
            return $form->_row_html('', ['only_row_end' => 1] + (array) $extra);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * Shortcut for making tabbable form.
     * @param mixed $name
     * @param mixed $extra
     */
    public function tab_start($name = '', $extra = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        $replace = [];
        $extra['name'] = $extra['name'] ?: $name;
        $func = function ($extra, $r, $form) {
            // auto-close tab_end(), if not called implicitely
            if ($form->_tabbed_mode_on) {
                $form->tab_end();
            }
            $form->_tabbed_mode_on = true;
            $form->_tabs_name = $extra['name'];
            $form->_tabs_extra = $extra;
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * Paired with tab_start.
     * @param mixed $extra
     */
    public function tab_end($extra = [])
    {
        $replace = [];
        $func = function ($extra, $r, $form) {
            $form->_tabbed_mode_on = false;
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $content
     * @param mixed $extra
     * @param mixed $replace
     */
    public function _row_html($content, $extra = [], $replace = [])
    {
        if ( ! strlen($content) && ($extra['hide_empty'] || $this->_params['hide_empty'])) {
            return '';
        }
        if ($this->_params['only_content']) {
            return $content;
        }
        if ($this->_params['dd_mode']) {
            return $this->_dd_row_html($content, $extra, $replace);
        }
        if ($extra['form_input_no_append'] || $this->_params['form_input_no_append'] || conf('form_input_no_append')) {
            $extra['append'] = '';
            $extra['prepend'] = '';
        }
        if ($this->_stacked_mode_on) {
            $extra['stacked'] = $extra['stacked'] ?: true;
        }
        $css_framework = $extra['css_framework'] ?: ($this->_params['css_framework'] ?: conf('css_framework'));
        $extra['css_framework'] = $css_framework;
        return _class('html5fw')->form_row($content, $extra, $replace, $this);
    }

    /**
     * Generate form row using dl>dt,dd html tags. Useful for user profle and other simple table-like content.
     * @param mixed $content
     * @param mixed $extra
     * @param mixed $replace
     */
    public function _dd_row_html($content, $extra = [], $replace = [])
    {
        if ($extra['hide_empty'] && ! strlen($content)) {
            return '';
        }
        if ($this->_stacked_mode_on) {
            $extra['stacked'] = true;
        }
        $css_framework = $extra['css_framework'] ?: ($this->_params['css_framework'] ?: conf('css_framework'));
        $extra['css_framework'] = $css_framework;
        return _class('html5fw')->form_dd_row($content, $extra, $replace, $this);
    }

    /**
     * @param mixed $value
     * @param mixed $extra
     * @param mixed $replace
     */
    public function _show_tip($value = '', $extra = [], $replace = [])
    {
        return tip($value, $replace);
    }

    /**
     * @param mixed $attr
     */
    public function _prepare_custom_attr($attr = [])
    {
        $body = [];
        foreach ((array) $attr as $k => $v) {
            $body[] = _htmlchars($k) . '="' . _htmlchars($v) . '"';
        }
        return implode(' ', $body);
    }

    /**
     * @param mixed $default_class
     * @param mixed $value
     */
    public function _prepare_css_class($default_class = '', $value = '', &$extra)
    {
        $css_class = $default_class;
        if ($extra['badge']) {
            $badge = is_array($extra['badge']) && isset($extra['badge'][$value]) ? $extra['badge'][$value] : $extra['badge'];
            if ($badge) {
                $css_class = str_replace('%name', $badge, $this->CLASS_TPL_BADGE);
            }
        } elseif ($extra['label']) {
            $label = is_array($extra['label']) && isset($extra['label'][$value]) ? $extra['label'][$value] : $extra['label'];
            if ($label) {
                $css_class = str_replace('%name', $label, $this->CLASS_TPL_LABEL);
            }
        } elseif ($extra['class']) {
            $_css_class = is_array($extra['class']) && isset($extra['class'][$value]) ? $extra['class'][$value] : $extra['class'];
            if ($_css_class) {
                $css_class = $_css_class;
            }
        }
        // Needed to not modify original class of element (sometimes complex), but just add css class there
        if (isset($extra['class_add'])) {
            $_css_class_add = is_array($extra['class_add']) && isset($extra['class_add'][$value]) ? $extra['class_add'][$value] : $extra['class_add'];
            if ($_css_class_add) {
                $css_class .= ' ' . $_css_class_add;
            }
        }
        if ($this->_params['big_labels']) {
            $css_class .= ' labels-big';
        }
        return $css_class ? ' ' . $css_class : '';
    }

    /**
     * @param mixed $default
     */
    public function _prepare_id(&$extra, $default = '')
    {
        $out = $extra['id'];
        if ( ! $out) {
            $out = $extra['name'];
            $is_html_array = (false !== strpos($out, '['));
            if ($is_html_array) {
                $out = str_replace(['[', ']'], ['_', ''], trim($out, ']['));
            }
        }
        ! $out && $out = $default;
        return $out;
    }

    /**
     * @param mixed $input
     */
    public function _prepare_desc(&$extra, $input = '')
    {
        $out = $extra['desc'];
        ! $out && $out = $input;
        if ( ! $out) {
            $out = ucfirst(str_replace('_', ' ', $extra['name']));
            $is_html_array = (false !== strpos($out, '['));
            if ($is_html_array) {
                $out = str_replace(['[', ']'], ['.', ''], trim($out, ']['));
            }
        }
        return $out;
    }


    public function _prepare_value(&$extra, &$replace, &$params)
    {
        $name = $extra['name'];
        $is_html_array = (false !== strpos($name, '['));
        if ($is_html_array) {
            $name_dots = str_replace(['[', ']'], ['.', ''], trim($name, ']['));
            $replace_dots = array_dot($replace);
        }
        $value = '';
        if ($extra['value'] ?? false) {
            $value = $extra['value'];
        } elseif ($replace[$name] ?? false) {
            $value = $replace[$name];
        } elseif ($is_html_array && ($replace_dots[$name_dots] ?? false)) {
            $value = $replace_dots[$name_dots];
        } elseif ($extra['selected'] ?? false) {
            $value = $extra['selected'];
        } elseif ($params['selected'][$name] ?? false) {
            $value = $params['selected'][$name];
        } elseif ($is_html_array && ($params['selected'][$name_dots] ?? false)) {
            $value = $params['selected'][$name_dots];
        }
        return $value;
    }

    /**
     * @param mixed $name
     */
    public function _prepare_selected($name, &$extra, &$r)
    {
        $is_array = strpos($name, '[');
        if ($is_array !== false) {
            $value = &$r;
            $keys = explode('[', $name);
            foreach ($keys as $key) {
                $key = trim(rtrim($key, ']'));
                if ( ! isset($value[$key])) {
                    $value = null;
                    break;
                }
                $value = &$value[$key];
            }
            $selected = $value;
        } else {
            $selected = $r[$name];
        }
        // $selected = $r[$name];
        if (isset($extra['selected'])) {
            $selected = $extra['selected'];
        } elseif (isset($this->_params['selected'])) {
            $selected = $this->_params['selected'][$name];
        }
        return $selected;
    }

    /**
     * @param mixed $name
     */
    public function _prepare_inline_error(&$extra, $name = '')
    {
        $name = $name ?: $extra['name'];
        $is_html_array = (false !== strpos($name, '['));
        if ($is_html_array) {
            $name_orig = $name;
            $name = str_replace(['[', ']'], ['.', ''], trim($name, ']['));
        }
        $extra['errors'] = common()->_get_error_messages();
        if (isset($extra['errors'][$name])) {
            $remove_errors = true;
            $var_name = 'do_not_remove_errors';
            if ($extra[$var_name] || $this->_params[$var_name] || $this->_extra[$var_name] || $this->_params['extra'][$var_name]) {
                $remove_errors = false;
            }
            if ($remove_errors) {
                common()->_remove_error_messages($name);
            }
            $extra['inline_help'] = $extra['errors'][$name];
        }
    }

    /**
     * Bootstrap-compatible html wrapper for any custom content inside.
     * Can be used for inline rich editor editing with ckeditor, enable with: $extra = ['ckeditor' => true].
     * @param mixed $text
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function container($text, $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $text = (string) $text;
        $extra['text'] = $text;
        $extra['desc'] = $this->_prepare_desc($extra, $desc);

        $func = function ($extra, $r, $form) {
            $extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
            $extra['contenteditable'] = isset($extra['ckeditor']) ? 'true' : 'false';
            $extra['id'] = $form->_prepare_id($extra, 'content_editable');
            $extra['desc'] = ! $form->_params['no_label'] ? $extra['desc'] : '';

            $attrs_names = ['id', 'contenteditable', 'style', 'class', 'title'];
            if ($extra['ckeditor']) {
                $extra['ckeditor_inline'] = true;
            }
            return $form->_row_html(isset($extra['ckeditor']) ? '<div' . _attrs($extra, $attrs_names) . '>' . $extra['text'] . '</div>' : $extra['text'], $extra, $r);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * General input.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function input($name, $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['name'] = $extra['name'] ?: $name;
        $extra['desc'] = $this->_prepare_desc($extra, $desc);
        $func = function ($extra, $r, $form) {
            $form->_prepare_inline_error($extra);
            $extra['id'] = $form->_prepare_id($extra);
            $extra['placeholder'] = t(isset($extra['placeholder']) ? $extra['placeholder'] : $extra['desc']);
            $extra['value'] = $form->_prepare_value($extra, $r, $form->_params);
            $extra['type'] = $extra['type'] ?: 'text';
            $extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
            $extra['class'] = $form->CLASS_FORM_CONTROL . $form->_prepare_css_class('', $r[$extra['name']], $extra);
            if ($this->_params['filter'] && ! isset($extra['sizing'])) {
                $extra['sizing'] = 'sm';
            }
            // Supported: sm, lg
            if ($extra['sizing']) {
                $extra['class'] .= ' input-' . $extra['sizing'];
            }
            if ($form->_params['no_label']) {
                $extra['desc'] = '';
            }
            $extra = $form->_input_assign_params_from_validate($extra);
            $attrs_names = ['name', 'type', 'id', 'class', 'style', 'placeholder', 'value', 'data', 'size', 'maxlength', 'pattern', 'disabled', 'readonly', 'required', 'autocomplete', 'accept', 'target', 'autofocus', 'title', 'min', 'max', 'step'];
            return $form->_row_html('<input' . _attrs($extra, $attrs_names) . '>', $extra, $r);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function textarea($name, $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['name'] = $extra['name'] ?: $name;
        $extra['desc'] = $this->_prepare_desc($extra, $desc);
        $func = function ($extra, $r, $form) {
            $form->_prepare_inline_error($extra);
            $extra['id'] = $form->_prepare_id($extra);
            $extra['placeholder'] = t(isset($extra['placeholder']) ? $extra['placeholder'] : $extra['desc']);
            $extra['value'] = $form->_prepare_value($extra, $r, $form->_params);
            $extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
            $extra['contenteditable'] = ( ! isset($extra['contenteditable']) || $extra['contenteditable']) ? 'true' : false;
            $use_ckeditor = isset($extra['ckeditor']) ? $extra['ckeditor'] : false;
            $extra['class'] = ($use_ckeditor ? $form->CLASS_CKEDITOR . ' ' : '') . $form->CLASS_FORM_CONTROL . $form->_prepare_css_class('', $r[$extra['name']], $extra);
            if ($form->_params['no_label']) {
                $extra['desc'] = '';
            }
            $extra = $form->_input_assign_params_from_validate($extra);
            $attrs_names = ['id', 'name', 'placeholder', 'contenteditable', 'class', 'style', 'cols', 'rows', 'title', 'required', 'size', 'disabled', 'readonly', 'autocomplete', 'autofocus'];
            return $form->_row_html('<textarea' . _attrs($extra, $attrs_names) . '>' . ( ! isset($extra['no_escape']) ? _htmlchars($extra['value']) : $extra['value']) . '</textarea>', $extra, $r);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * Embedding ckeditor (http://ckeditor.com/) with kcfinder (http://kcfinder.sunhater.com/).
     * Best way to include it into project:.
     *
     * git submodule add https://github.com/yfix/ckeditor-releases.git www/ckeditor/ && cd www/ckeditor/ && git checkout latest/full
     * git submodule add https://github.com/yfix//kcfinder.git www/kcfinder
     *
     * 'www/' usually means PROJECT_PATH inside project working copy.
     * P.S. You can use free CDN for ckeditor as alternate solution: <script src="//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.0.1/ckeditor.js"></script>
     * @param mixed $extra
     * @param mixed $replace
     */
    public function _ckeditor_html($extra = [], $replace = [])
    {
        return _class('form2_ckeditor', 'classes/form2/')->_ckeditor_html($extra, $replace, $this);
    }

    /**
     * @param mixed $extra
     * @param mixed $replace
     */
    public function _tinymce_html($extra = [], $replace = [])
    {
        return _class('form2_tinymce', 'classes/form2/')->_tinymce_html($extra, $replace, $this);
    }

    /**
     * @param mixed $extra
     * @param mixed $replace
     */
    public function _ace_editor_html($extra = [], $replace = [])
    {
        return _class('form2_ace_editor', 'classes/form2/')->_ace_editor_html($extra, $replace, $this);
    }

    /**
     * Just hidden input.
     * @param mixed $name
     * @param mixed $extra
     * @param mixed $replace
     */
    public function hidden($name, $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['name'] = $extra['name'] ?: $name;
        $func = function ($extra, $r, $form) {
            $extra['id'] = $form->_prepare_id($extra);
            $extra['value'] = $form->_prepare_value($extra, $r, $form->_params);
            $extra['type'] = 'hidden';

            $attrs_names = ['type', 'id', 'name', 'value', 'data'];
            return '<input' . _attrs($extra, $attrs_names) . '>';
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function text($name, $desc = '', $extra = [], $replace = [])
    {
        $extra['type'] = 'text';
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function password($name = '', $desc = '', $extra = [], $replace = [])
    {
        $extra['type'] = 'password';
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $name) {
            $name = 'password';
        }
        $extra['prepend'] = isset($extra['prepend']) ? $extra['prepend'] : '<i class="' . $this->CLASS_ICON_PSWD . '"></i>';
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function file($name, $desc = '', $extra = [], $replace = [])
    {
        $extra['type'] = 'file';
        $this->_params['for_upload'] = true;
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * Image upload.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function image($name = '', $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra += $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $this->_params['for_upload'] = true;
        $extra['name'] = $extra['name'] ?: ($name ?: 'image');
        $extra['desc'] = $this->_prepare_desc($extra, $desc);
        $extra['type'] = 'file';
        return $this->input($extra['name'], $extra['desc'], $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function button($name, $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $desc) {
            $desc = ucfirst(str_replace('_', ' ', $name));
        }
        $extra['type'] = 'button';
        if ( ! isset($extra['value'])) {
            $extra['value'] = $desc;
        }
        $extra['value'] = t($extra['value']);
        if ( ! $extra['class']) {
            $extra['class'] = $this->CLASS_BTN_DEFAULT;
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * Custom.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function login($name = '', $desc = '', $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = $extra['type'] ?: 'text';
        $extra['prepend'] = isset($extra['prepend']) ? $extra['prepend'] : '<i class="' . $this->CLASS_ICON_LOGIN . '"></i>';
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'login';
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * HTML5.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function email($name = '', $desc = '', $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'email';
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'email';
        }
        $extra['prepend'] = isset($extra['prepend']) ? $extra['prepend'] : '<i class="' . $this->CLASS_ICON_EMAIL . '"></i>';
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * HTML5.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function number($name, $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'number';
        $extra['maxlength'] = isset($extra['maxlength']) ? $extra['maxlength'] : '10';
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function integer($name, $desc = '', $extra = [], $replace = [])
    {
        return $this->number($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function float($name, $desc = '', $extra = [], $replace = [])
    {
        return $this->decimal($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function decimal($name, $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['step'] = $extra['step'] ?: '0.01';
        return $this->number($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function money($name, $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['prepend'] = isset($extra['prepend']) ? $extra['prepend'] : ($this->_params['currency'] ?: '<i class="' . $this->CLASS_ICON_CURRENCY . '"></i>');
        $extra['append'] = isset($extra['append']) ? $extra['append'] : ''; // '.00';
        $extra['maxlength'] = isset($extra['maxlength']) ? $extra['maxlength'] : '8';
        return $this->decimal($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function price($name, $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['min'] = $extra['min'] ?: '0';
        return $this->money($name, $desc, $extra, $replace);
    }

    /**
     * HTML5.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function url($name = '', $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'url';
        $extra['prepend'] = isset($extra['prepend']) ? $extra['prepend'] : 'url';
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'url';
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * HTML5.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function color($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'color';
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'color';
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * HTML5.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function date($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'date';
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'date';
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * HTML5.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function datetime($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'datetime';
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'datetime';
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * HTML5.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function datetime_local($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'datetime-local';
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'datetime_local';
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * HTML5.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function month($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'month';
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'month';
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * HTML5.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function range($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'range';
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'range';
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * HTML5.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function search($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'search';
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'search';
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * HTML5.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function tel($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'tel';
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'tel';
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * Alias.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function phone($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'tel';
        if (is_array($name)) {
            $extra += $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'phone';
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * HTML5.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function time($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'time';
        if (is_array($name)) {
            $extra += $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'time';
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * HTML5.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function week($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['type'] = 'week';
        if (is_array($name)) {
            $extra += $name;
            $name = '';
        }
        if ( ! $name) {
            $name = 'week';
        }
        return $this->input($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function active_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra += $name;
            $desc = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['name'] = $extra['name'] ?: ($name ?: 'active');
        $extra['desc'] = $this->_prepare_desc($extra, $desc);
        $func = function ($extra, $r, $form) {
            $form->_prepare_inline_error($extra);
            $as_btn_group = isset($extra['btn_group']) ? $extra['btn_group'] : $form->CONF_BOXES_USE_BTN_GROUP;
            if ($as_btn_group) {
                $extra['class_add_controls'] = 'btn-group';
                $extra['controls']['data-toggle'] = 'buttons';
            }
            if ( ! $extra['items']) {
                $data_handler = $as_btn_group ? 'pair_active_btn_group' : 'pair_active';
                $extra['items'] = main()->get_data($data_handler);
            }
            $extra['values'] = $extra['items'];
            $extra['desc'] = ! $form->_params['no_label'] ? $extra['desc'] : '';
            $extra['id'] = $form->_prepare_id($extra);
            if ( ! isset($extra['horizontal'])) {
                $extra['horizontal'] = true;
            }
            $extra['selected'] = isset($extra['selected']) ? $extra['selected'] : $r[$extra['name']];
            if (isset($form->_params['selected'])) {
                $extra['selected'] = $form->_params['selected'][$extra['name']];
            }
            $extra = $form->_input_assign_params_from_validate($extra);
            if ($this->_params['filter'] && ! $extra['renderer']) {
                $extra['no_label'] = 1;
                $extra['label_right'] = 1;
                $extra['renderer'] = 'button_yes_no_box';
            }
            $renderer = $extra['renderer'] ?: 'radio_box';
            return $form->_row_html(html()->$renderer($extra), $extra, $r);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function allow_deny_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! isset($this->_pair_allow_deny)) {
            $this->_pair_allow_deny = main()->get_data('pair_allow_deny');
        }
        $extra['items'] = $this->_pair_allow_deny;
        if ($this->_params['filter'] || $extra['v2']) {
            $extra['no_label'] = 1;
            $extra['label_right'] = 1;
            $extra['renderer'] = 'button_allow_deny_box';
        }
        $func = 'active_box';
        return $this->$func($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function yes_no_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! isset($this->_pair_yes_no)) {
            $this->_pair_yes_no = main()->get_data('pair_yes_no');
        }
        $extra['items'] = $this->_pair_yes_no;
        if ($this->_params['filter'] || $extra['v2']) {
            $extra['no_label'] = 1;
            $extra['label_right'] = 1;
            $extra['renderer'] = 'button_yes_no_box';
        }
        $func = 'active_box';
        return $this->$func($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $data
     * @param mixed $extra
     * @param mixed $replace
     */
    public function order_box($name = '', $data = [], $extra = [], $replace = [])
    {
        $data = $data ?: [
            'asc' => 'Ascending',
            'desc' => 'Descending',
        ];
        $extra['horizontal'] = isset($extra['horizontal']) ? $extra['horizontal'] : 1;
        return $this->radio_box($name ?: 'order_direction', t($data), $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $value
     * @param mixed $extra
     * @param mixed $replace
     */
    public function submit($name = '', $value = '', $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($value)) {
            $extra = (array) $extra + $value;
            $value = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['name'] = $extra['name'] ?: $name;
        $extra['value'] = isset($extra['value']) ? $extra['value'] : ($value ?: 'Save');
        $extra['id'] = $extra['id'] ?: ($extra['name'] ?: strtolower($extra['value']));
        $func = function ($extra, $r, $form) {
            $form->_prepare_inline_error($extra);
            $extra['link_url'] = $extra['link_url'] ? (isset($r[$extra['link_url']]) ? $r[$extra['link_url']] : $extra['link_url']) : '';
            if (false === strpos($extra['link_url'], '/')) {
                $extra['link_url'] = '';
            }
            $extra['link_name'] = $extra['link_name'] ?: '';
            $extra['class'] = $extra['class'] ?: $form->CLASS_BTN_SUBMIT . $form->_prepare_css_class('', $r[$extra['name']], $extra);
            $extra['value'] = $extra['value'];
            $extra['type'] = 'submit';
            $button_text = $extra['desc'];
            $extra['desc'] = '';
            $extra['buttons_controls'] = true;
            if ($this->_params['filter'] && ! isset($extra['sizing'])) {
                $extra['sizing'] = 'sm';
            }
            // Supported: xs, sm, md, lg
            if ($extra['sizing']) {
                $extra['class'] .= ' btn-' . $extra['sizing'];
                $extra['link_class'] .= ' btn-' . $extra['sizing'];
            }
            $attrs_names = ['type', 'name', 'id', 'class', 'style', 'value', 'disabled', 'target'];
            if ( ! $extra['as_input']) {
                $icon = ($extra['icon'] ? '<i class="' . $extra['icon'] . '"></i> ' : '');
                $value = ( ! isset($extra['no_escape']) ? _htmlchars($extra['value']) : $extra['value']);
                $button_text = $icon . t($button_text ?: $value);
                return $form->_row_html('<button' . _attrs($extra, $attrs_names) . '>' . $button_text . '</button>', $extra, $r);
            }
            return $form->_row_html('<input' . _attrs($extra, $attrs_names) . '>', $extra, $r);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function save($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! isset($extra['icon'])) {
            $extra['icon'] = $this->CLASS_ICON_SAVE;
        }
        return $this->submit($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function save_and_back($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! $name) {
            $name = 'back_link';
            $r = $replace ? $replace : $this->_replace;
            if ( ! isset($r[$name]) && isset($r['back_url'])) {
                $name = 'back_url';
            }
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['link_url'] = $name;
        $extra['link_name'] = $desc ?: 'Back';
        if ( ! isset($extra['icon'])) {
            $extra['icon'] = $this->CLASS_ICON_SAVE;
        }
        return $this->submit($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function save_and_clear($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! $name) {
            $name = 'clear_link';
            $r = $replace ? $replace : $this->_replace;
            if ( ! isset($r[$name]) && isset($r['clear_url'])) {
                $name = 'clear_url';
            }
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['link_url'] = $name;
        $extra['link_name'] = $desc ?: 'Clear';
        if ( ! isset($extra['icon'])) {
            $extra['icon'] = $this->CLASS_ICON_SAVE;
        }
        return $this->submit($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $value
     * @param mixed $extra
     * @param mixed $replace
     */
    public function preview($name = '', $value = '', $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($value)) {
            $extra = (array) $extra + $value;
            $value = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $name = $extra['name'] ?: $name;
        $desc = '';
        $extra['desc'] = $extra['desc'] ?: 'Preview';
        $extra['class_add'] = $extra['class_add'] ?: 'preview';
        if ( ! $name) {
            $name = 'preview';
        }
        if ( ! isset($extra['icon'])) {
            $extra['icon'] = $this->CLASS_ICON_PREVIEW;
        }
        return $this->submit($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function info($name, $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['name'] = $extra['name'] ?: $name;
        $extra['desc'] = $this->_prepare_desc($extra, $desc);
        $func = function ($extra, $r, $form) {
            $form->_prepare_inline_error($extra);
            $extra['desc'] = ! $extra['no_label'] && ! $form->_params['no_label'] ? $extra['desc'] : '';

            $value = $r[$extra['name']] ?: $extra['value'];
            if (is_array($extra['data'])) {
                if (isset($extra['data'][$value])) {
                    $value = $extra['data'][$value];
                } elseif (isset($extra['data'][$extra['name']])) {
                    $value = $extra['data'][$extra['name']];
                }
            }
            $value = ! isset($extra['no_escape']) ? _htmlchars($value) : $value;
            if ( ! $extra['no_translate']) {
                $extra['desc'] = t($extra['desc']);
                $value = t($value);
            }
            if ($extra['no_text']) {
                $value = '';
            }
            if ($extra['link']) {
                if (MAIN_TYPE_ADMIN && main()->ADMIN_GROUP != 1 && ! _class('admin_methods')->_admin_link_is_allowed($extra['link'])) {
                    $extra['link'] = '';
                }
            }
            $icon = $extra['icon'] ? '<i class="' . $extra['icon'] . '"></i> ' : '';
            $content = '';
            if ($extra['link']) {
                if ($extra['rewrite']) {
                    $extra['link'] = url($extra['link']);
                }
                $extra['class'] = $extra['class'] ?: $form->CLASS_BTN_MINI;
                $extra['class'] = $form->_prepare_css_class($extra['class'], $r[$extra['name']], $extra);
                $extra['href'] = $extra['link'];
                $extra['title'] = $extra['title'] ?: $extra['desc'] ?: $extra['name'];
                $attrs_names = ['href', 'name', 'class', 'style', 'disabled', 'target', 'alt', 'title'];
                $content = '<a' . _attrs($extra, $attrs_names) . '>' . $icon . $value . '</a>';
            } else {
                $extra['class'] = $extra['class'] ?: $form->CLASS_LABEL_INFO;
                $content = '<span class="' . $form->_prepare_css_class($extra['class'], $r[$extra['name']], $extra) . '">' . $icon . $value . '</span>';
            }
            return $form->_row_html($content, $extra, $r);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function user_info($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_info', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function admin_info($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_info', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $format
     * @param mixed $extra
     * @param mixed $replace
     */
    public function info_date($name = '', $format = '', $extra = [], $replace = [])
    {
        $r = (array) $this->_replace + (array) $replace;
        if (is_array($format)) {
            $extra = (array) $extra + $format;
            $format = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['format'] = $extra['format'] ?: $format;
        $replace[$name] = _format_date($r[$name], $extra['format']);
        $this->_replace[$name] = $replace[$name];
        return $this->info($name, $format, $extra, $replace);
    }

    /**
     * Mostly for {form_row()}, as it can be emulated from php easily.
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     * @param mixed $replace
     */
    public function info_link($name = '', $link = '', $extra = [], $replace = [])
    {
        $r = (array) $this->_replace + (array) $replace;
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['link'] = $extra['link'] ?: ($link ?: $r[$name]);
        return $this->info($name, '', $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     * @param mixed $replace
     */
    public function info_lang($name = '', $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['name'] = $extra['name'] ?: $name;
        $func = function ($extra, $r, $form) {
            $extra['desc'] = ! $extra['no_label'] && ! $form->_params['no_label'] ? $extra['desc'] : '';
            $value = $r[$extra['name']] ?: $extra['value'];
            $lang = $value;
            asset('bfh-select');
            if ( ! isset($form->lang_def_country)) {
                $form->lang_def_country = main()->get_data('lang_def_country');
            }
            $content = html()->icon('bfh-flag-' . $form->lang_def_country[$lang], strtoupper($lang));
            return $form->_row_html($content, $extra, $r);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     * @param mixed $replace
     */
    public function link($name = '', $link = '', $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($link)) {
            $extra = (array) $extra + $link;
            $link = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['link'] = isset($extra['link']) ? $extra['link'] : $link;
        $extra['value'] = isset($extra['value']) ? $extra['value'] : $name;
        if ( ! $extra['desc']) {
            $extra['no_label'] = 1;
        }
        $desc = '';
        return $this->info($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $func_html_control
     */
    public function _html_control($name, $values, $extra = [], $replace = [], $func_html_control = '')
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if (is_array($name)) {
            $extra = (array) $extra + $name;
        } else {
            $extra['name'] = $name;
        }
        $desc = '';
        $extra['desc'] = $this->_prepare_desc($extra, $desc);
        $extra['values'] = isset($extra['values']) ? $extra['values'] : (array) $values; // Required
        $extra['func_html_control'] = $extra['func_html_control'] ?: $func_html_control;
        $func = function ($extra, $r, $form) {
            $form->_prepare_inline_error($extra);
            $extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
            $extra['selected'] = $form->_prepare_selected($extra['name'], $extra, $r);
            $extra['id'] = $extra['name'];
            $extra = $form->_input_assign_params_from_validate($extra);
            if ($this->_params['filter']) {
                $extra['class_add'] .= ' input-sm';
            }
            $func = $extra['func_html_control'];
            $content = _class('html')->$func($extra);
            if ($extra['no_label'] || $form->_params['no_label']) {
                $extra['desc'] = '';
            }
            if ($extra['hide_empty'] && ! strlen($content)) {
                return '';
            }
            return $form->_row_html($content, $extra, $r);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function box($name, $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['name'] = $extra['name'] ?: $name;
        $extra['desc'] = $this->_prepare_desc($extra, $desc);
        $func = function ($extra, $r, $form) {
            $form->_prepare_inline_error($extra);
            $extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
            $extra['values'] = isset($extra['values']) ? $extra['values'] : ($values ?? []); // Required
            $extra['selected'] = $form->_prepare_selected($extra['name'], $extra, $r);
            $extra['id'] = $form->_prepare_id($extra);
            $extra = $form->_input_assign_params_from_validate($extra);

            return $form->_row_html($r[$extra['name']], $extra, $r);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $link
     * @param mixed $replace
     */
    public function box_with_link($name, $desc = '', $link = '', $replace = [])
    {
        return $this->box($name, $desc, ['edit_link' => $link], $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function select_box($name, $values, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'select_box');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function multi_select($name, $values, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'multi_select_box');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function multi_select_box($name, $values, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'multi_select_box');
    }

    /**
     * @param mixed $name
     * @param mixed $value
     * @param mixed $extra
     * @param mixed $replace
     */
    public function check_box($name, $value = '', $extra = [], $replace = [])
    {
        if (is_array($value)) {
            $extra = (array) $extra + $value;
            $value = '';
        }
        $as_btn_group = isset($extra['btn_group']) ? $extra['btn_group'] : $this->CONF_BOXES_USE_BTN_GROUP;
        if ($as_btn_group) {
            $extra['class_add_controls'] = 'btn-group';
            $extra['controls']['data-toggle'] = 'buttons';
            $extra['class_add_label_checkbox'] = 'btn btn-xs btn-default';
            $extra['desc'] = '<span><i class="icon icon-check fa fa-check"></i></span> ' . $extra['desc'];
        }
        return $this->_html_control($name, $value, $extra, $replace, 'check_box');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function multi_check_box($name, $values, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'multi_check_box');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function radio_box($name, $values, $extra = [], $replace = [])
    {
        $as_btn_group = isset($extra['btn_group']) ? $extra['btn_group'] : $this->CONF_BOXES_USE_BTN_GROUP;
        if ($as_btn_group) {
            $extra['class_add_controls'] = 'btn-group';
            $extra['controls']['data-toggle'] = 'buttons';
            $extra['class_add_label_radio'] = 'btn btn-xs btn-default';
        }
        return $this->_html_control($name, $values, $extra, $replace, 'radio_box');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function div_box($name, $values, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'div_box');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function list_box($name, $values, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'list_box');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function button_box($name, $values, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'button_box');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function button_split_box($name, $values, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'button_split_box');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function button_check_box($name, $values, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'button_check_box');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function button_radio_box($name, $values, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'button_radio_box');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function button_yes_no_box($name, $values, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'button_yes_no_box');
    }

    /**
     * @param mixed $name
     * @param null|mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function select2_box($name, $values = null, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'select2_box');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function chosen_box($name, $values, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'chosen_box');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function image_select_box($name, $values, $extra = [], $replace = [])
    {
        return $this->_html_control($name, $values, $extra, $replace, 'image_select_box');
    }

    /**
     * @param mixed $name
     * @param null|mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function user_select_box($name, $values = null, $extra = [], $replace = [])
    {
        _class('form_api')->{__FUNCTION__}($name, $values, $extra, $replace);
        return $this->_html_control($name, $values, $extra, $replace, 'select2_box');
    }

    /**
     * @param mixed $name
     * @param mixed $extra
     * @param mixed $replace
     */
    public function phone_box($name = '', $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
        } else {
            $extra['name'] = $name;
        }
        if ( ! $extra['name']) {
            $name = $extra['name'] = 'phone';
        }
        $func = function ($extra, $r, $form) {
            asset('jquery-formvalidation');
            jquery('
				var yf_phone_callback = function(value, validator, $field) {
					var isValid = value === "" || $field.intlTelInput("isValidNumber"),
						err	 = $field.intlTelInput("getValidationError"),
						message = null;
					switch (err) {
						case intlTelInputUtils.validationError.INVALID_COUNTRY_CODE:
							message = "' . t('The country code is not valid') . '";
							break;
						case intlTelInputUtils.validationError.TOO_SHORT:
							message = "' . t('The phone number is too short') . '";
							break;
						case intlTelInputUtils.validationError.TOO_LONG:
							message = "' . t('The phone number is too long') . '";
							break;
						case intlTelInputUtils.validationError.NOT_A_NUMBER:
							message = "' . t('The value is not a number') . '";
							break;
						default:
							message = "' . t('The phone number is not valid') . '";
							break;
					}
					return {
						valid: isValid,
						message: message
					};
				}
				var form = $("#' . addslashes($extra['name']) . '").closest("form")
				form.formValidation({
					framework: "bootstrap",
					fields: {
						"' . addslashes($extra['name']) . '": {
							validators: {
								callback: {
									callback: yf_phone_callback,
								}
							}
						}
					}
				})
				// Revalidate the number when changing the country
				.on("click", ".country-list", function() {
					form.formValidation("revalidateField", "' . addslashes($extra['name']) . '");
				});
			');
            $form->_prepare_inline_error($extra);
            $extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
            $extra['selected'] = $form->_prepare_selected($extra['name'], $extra, $r);
            $extra['value'] = &$extra['selected'];
            $extra['id'] = $extra['id'] ?: $extra['name'];

            $extra = $form->_input_assign_params_from_validate($extra);
            $content = _class('html')->phone_box($extra);
            if ($extra['no_label'] || $form->_params['no_label']) {
                $extra['desc'] = '';
            }
            if ($extra['hide_empty'] && ! strlen($content)) {
                return '';
            }
            return $form->_row_html($content, $extra, $r);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function date_box($name = '', $values = [], $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $name) {
            $name = 'date';
        }
        return $this->_html_control($name, $values, $extra, $replace, 'date_box2');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function time_box($name = '', $values = [], $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $name) {
            $name = 'time';
        }
        return $this->_html_control($name, $values, $extra, $replace, 'time_box2');
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function datetime_box($name = '', $values = [], $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $name) {
            $name = 'datetime';
        }
        if ( ! isset($extra['show_what'])) {
            $extra['show_what'] = 'ymdhis';
        }
        return $this->date_box($name, $values, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $values
     * @param mixed $extra
     * @param mixed $replace
     */
    public function birth_box($name = '', $values = [], $extra = [], $replace = [])
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $name) {
            $name = 'birth';
        }
        return $this->date_box($name, $values, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function country_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function region_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function city_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function currency_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function locale_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function language_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function timezone_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function icon_select_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function method_select_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function user_method_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['for_type'] = 'user';
        return $this->method_select_box($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function admin_method_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['for_type'] = 'admin';
        return $this->method_select_box($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function template_select_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function user_template_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['for_type'] = 'user';
        return $this->template_select_box($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function admin_template_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['for_type'] = 'admin';
        return $this->template_select_box($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function location_select_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function user_location_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['for_type'] = 'user';
        return $this->location_select_box($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function admin_location_box($name = '', $desc = '', $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['for_type'] = 'admin';
        return $this->location_select_box($name, $desc, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function google_maps($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_google_maps', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    public function upload($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_upload', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function captcha($name = '', $desc = '', $extra = [], $replace = [])
    {
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['name'] = $extra['name'] ?: ($name ?: 'captcha');
        $extra['desc'] = $this->_prepare_desc($extra, $desc);
        $func = function ($extra, $r, $form) {
            $form->_prepare_inline_error($extra);
            $extra['id'] = $form->_prepare_id($extra);
            $extra['required'] = true;
            $extra['value'] = $r['captcha'];
            $extra['input_attrs'] = _attrs($extra, ['class', 'style', 'placeholder', 'pattern', 'disabled', 'required', 'autocomplete', 'accept', 'value', 'size']);
            $extra = $form->_input_assign_params_from_validate($extra);
            return $form->_row_html(_class('captcha')->show_block(url('/dynamic/captcha_image'), $extra), $extra, $r);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function ui_range($name, $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_ui_range', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * Custom function, useful to insert custom html and not breaking form chain.
     * @param mixed $name
     * @param mixed $func
     * @param mixed $extra
     * @param mixed $replace
     */
    public function func($name, $func, $extra = [], $replace = [])
    {
        if (is_array($func)) {
            $extra = (array) $extra + $func;
            $func = '';
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if ( ! $func) {
            if (isset($extra['callback'])) {
                $func = $extra['callback'];
            } elseif (isset($extra['function'])) {
                $func = $extra['function'];
            } else {
                $func = $extra['func'];
            }
        }
        $extra['name'] = $extra['name'] ?: $name;
        $extra['desc'] = $this->_prepare_desc($extra);
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $custom_fields
     * @param mixed $extra
     * @param mixed $replace
     */
    public function custom_fields($name, $custom_fields, $extra = [], $replace = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $extra['name'] = $extra['name'] ?: $name;
        $extra['custom_fields'] = $custom_fields;
        $func = function ($extra, $r, $form) {
            $custom_fields = explode(',', $extra['custom_fields']);
            $sub_array_name = $extra['sub_array'] ?: 'custom';
            $custom_info = _attrs_string2array($r[$extra['name']]);

            $body = [];
            $form->_chained_mode = false;
            foreach ((array) $custom_fields as $field_name) {
                if (empty($field_name)) {
                    continue;
                }
                $str = _class('html')->input([
                    'id' => 'custom_' . $field_name . '_' . $r['id'],
                    'name' => $sub_array_name . '[' . $field_name . ']', // Example: custom[color]
                    'desc' => $field_name,
                    'value' => $custom_info[$field_name],
                ]);
                $desc = ucfirst(str_replace('_', ' ', $field_name)) . ' [Custom]';
                $body[] = $form->container($str, $desc);
            }
            $form->_chained_mode = true;
            return implode(PHP_EOL, $body);
        };
        if ($this->_chained_mode) {
            $this->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
            return $this;
        }
        return $func((array) $extra + (array) $this->_extra, (array) $replace + (array) $this->_replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function stars($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_stars', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * Star selector, got from http://fontawesome.io/examples/#custom.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function stars_select($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_stars', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * Datetimepicker, src: http://tarruda.github.io/bootstrap-datetimepicker/
     * params :  no_date // no date picker
     *			no_time // no time picker.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function datetime_select($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_datetime', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * Daterange picker (Alias).
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function daterange($name = '', $desc = '', $extra = [], $replace = [])
    {
        return $this->daterange_select($name, $desc, $extra, $replace);
    }

    /**
     * Daterange picker.
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     */
    public function daterange_select($name = '', $desc = '', $extra = [], $replace = [])
    {
        return _class('form2_daterange', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     */
    public function _remove_items_by_name($name = '')
    {
        if (is_array($name)) {
            $func = __FUNCTION__;
            foreach ((array) $name as $_name) {
                $this->$func($_name);
            }
            return true;
        }
        if ( ! $name) {
            return false;
        }
        foreach ((array) $this->_body as $k => $v) {
            if ($v['name'] == $name) {
                unset($this->_body[$k]);
            }
        }
        return true;
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     * @param mixed $replace
     */
    public function tbl_link($name, $link, $extra = [], $replace = [])
    {
        return _class('form2_tbl_funcs', 'classes/form2/')->{__FUNCTION__}($name, $link, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     * @param mixed $replace
     */
    public function tbl_link_add($name = '', $link = '', $extra = [], $replace = [])
    {
        return _class('form2_tbl_funcs', 'classes/form2/')->{__FUNCTION__}($name, $link, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     * @param mixed $replace
     */
    public function tbl_link_edit($name = '', $link = '', $extra = [], $replace = [])
    {
        return _class('form2_tbl_funcs', 'classes/form2/')->{__FUNCTION__}($name, $link, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     * @param mixed $replace
     */
    public function tbl_link_delete($name = '', $link = '', $extra = [], $replace = [])
    {
        return _class('form2_tbl_funcs', 'classes/form2/')->{__FUNCTION__}($name, $link, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     * @param mixed $replace
     */
    public function tbl_link_clone($name = '', $link = '', $extra = [], $replace = [])
    {
        return _class('form2_tbl_funcs', 'classes/form2/')->{__FUNCTION__}($name, $link, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     * @param mixed $replace
     */
    public function tbl_link_view($name = '', $link = '', $extra = [], $replace = [])
    {
        return _class('form2_tbl_funcs', 'classes/form2/')->{__FUNCTION__}($name, $link, $extra, $replace, $this);
    }

    /**
     * @param mixed $name
     * @param mixed $link
     * @param mixed $extra
     * @param mixed $replace
     */
    public function tbl_link_active($name = '', $link = '', $extra = [], $replace = [])
    {
        return _class('form2_tbl_funcs', 'classes/form2/')->{__FUNCTION__}($name, $link, $extra, $replace, $this);
    }

    /**
     * Form validation handler.
     * Here we have special rule, called __form_id__ , it is used to track which form need to be validated from $_POST.
     * @param mixed $validate_rules
     * @param mixed $post
     * @param mixed $extra
     */
    public function validate($validate_rules = [], $post = [], $extra = [])
    {
        $this->_validate_prepare($validate_rules, $extra);

        $func = function ($validate_rules, $post, $extra, $form) {
            $form->_validate_prepare($validate_rules, $extra);
            $form_id = $form->_get_form_id();
            // Do not do validation until data is empty (usually means that form is just displayed and we wait user input)
            $data = (array) ( ! empty($post) ? $post : $_POST);
            foreach ((array) $data as $k => $v) {
                // We fix case when array key is present but have empty values like this: ['cat_id' => []]
                if (is_array($v) && ! empty($v)) {
                    // Convert multi-dimensional arrays into single-dimensional array dot notation: array('k1' => ['k2' => 'v2'])  ==>  ['k1.k2' => 'v2']
                    $dots = array_dot($v);
                    if ( ! empty($dots)) {
                        $data[$k] = $dots;
                    }
                }
            }
            if (empty($data)) {
                return $form;
            }
            // We need this to validate only correct form on page, where there can be several forms with validation at once
            if ($form_id && $data[$this->CONF_FORM_ID_FIELD] != $form_id) {
                return $form;
            }
            $on_before_validate = isset($extra['on_before_validate']) ? $extra['on_before_validate'] : $form->_on['on_before_validate'];
            if (is_callable($on_before_validate)) {
                $on_before_validate($form->_validate_rules, $data, $this);
            }
            $events = _class('core_events');
            $events->fire('form.before_validate', [$form->_validate_rules, $data]);
            // Processing of prepared rules
            $validate_ok = $form->_validate_rules_process($form->_validate_rules, $data, $extra);
            if ($validate_ok) {
                $form->_validate_ok = true;
                $on_validate_ok = isset($extra['on_validate_ok']) ? $extra['on_validate_ok'] : $form->_on['on_validate_ok'];
                if (is_callable($on_validate_ok)) {
                    $on_validate_ok($data, $extra, $form->_validate_rules, $this);
                }
                $events->fire('form.validate_ok', [$form->_validate_rules, $data, $extra]);
            } else {
                $form->_validate_ok = false;
                $on_validate_error = isset($extra['on_validate_error']) ? $extra['on_validate_error'] : $form->_on['on_validate_error'];
                if (is_callable($on_validate_error)) {
                    $on_validate_error($data, $extra, $form->_validate_rules, $this);
                }
                $events->fire('form.validate_error', [$form->_validate_rules, $data, $extra]);
            }
            $on_after_validate = isset($extra['on_after_validate']) ? $extra['on_after_validate'] : $form->_on['on_after_validate'];
            if (is_callable($on_after_validate)) {
                $on_after_validate($form->_validate_ok, $form->_validate_rules, $data, $extra, $this);
            }
            $events->fire('form.after_validate', [$form->_validate_ok, $form->_validate_rules, $data, $extra]);
            $form->_validated_fields = $data;
        };
        if ($this->_chained_mode) {
            $this->_validate = [
                'func' => $func,
                'extra' => $extra,
                'post' => $post,
                'validate_rules' => $validate_rules,
            ];
            return $this;
        }
        return $this;
    }

    /**
     * @param mixed $validate_rules
     * @param mixed $extra
     */
    public function _validate_prepare($validate_rules = [], $extra = [])
    {
        $form_global_validate = isset($this->_params['validate']) ? $this->_params['validate'] : (isset($this->_replace['validate']) ? $this->_replace['validate'] : []);
        foreach ((array) $form_global_validate as $name => $rules) {
            $this->_validate_rules[$name] = $rules;
        }
        foreach ((array) $this->_body as $v) {
            $_extra = $v['extra'];
            if (isset($_extra['validate']) && isset($_extra['name'])) {
                $this->_validate_rules[$_extra['name']] = $_extra['validate'];
            }
        }
        foreach ((array) $validate_rules as $name => $rules) {
            $this->_validate_rules[$name] = $rules;
        }
        $form_id = '';
        if (isset($this->_validate_rules[$this->CONF_FORM_ID_FIELD])) {
            $form_id = $this->_validate_rules[$this->CONF_FORM_ID_FIELD];
            unset($this->_validate_rules[$this->CONF_FORM_ID_FIELD]);
        } elseif (isset($this->_params[$this->CONF_FORM_ID_FIELD])) {
            $form_id = $this->_params[$this->CONF_FORM_ID_FIELD];
            unset($this->_params[$this->CONF_FORM_ID_FIELD]);
        }
        if ($form_id) {
            $this->_form_id = $form_id;
        } else {
            $form_id = $this->_get_form_id();
        }
        $this->_set_hidden_form_id($form_id);

        $this->_validate_rules = $this->_validate_rules_cleanup($this->_validate_rules);
        // Prepare array of rules by form method for quick access
        if ($this->_validate_rules) {
            foreach ((array) $this->_validate_rules as $item => $rules) {
                foreach ((array) $rules as $rule) {
                    if (is_string($rule[0])) {
                        $this->_validate_rules_names[$item][$rule[0]] = $rule[1] ?: true;
                    }
                }
            }
        }
    }

    /**
     * @param mixed $validate_rules
     */
    public function _validate_rules_process($validate_rules = [], &$data)
    {
        $validate_ok = true;
        foreach ((array) $validate_rules as $name => $rules) {
            $is_required = false;
            foreach ((array) $rules as $rule) {
                if (is_string($rule[0]) && substr($rule[0], 0, strlen('required')) === 'required') {
                    $is_required = true;
                    break;
                }
            }
            foreach ((array) $rules as $rule) {
                $is_ok = true;
                $error_msg = '';
                $func = $rule[0];
                $param = $rule[1];
                // PHP pure function, from core or user
                if (is_string($func) && function_exists($func)) {
                    $data[$name] = $this->_apply_existing_func($func, $data[$name]);
                } elseif (is_callable($func)) {
                    $is_ok = $func($data[$name], null, $data, $error_msg);
                } else {
                    if (is_array($data[$name]) && ! empty($data[$name])) {
                        foreach ($data[$name] as $k => $v) {
                            $is_ok = _class('validate')->$func($v, ['param' => $param], $data, $error_msg, ['field' => $name]);
                            if ( ! $is_ok) {
                                break;
                            }
                        }
                    } else {
                        $is_ok = _class('validate')->$func($data[$name], ['param' => $param], $data, $error_msg, ['field' => $name]);
                    }
                    if ( ! $is_ok && empty($error_msg)) {
                        $desc = $this->_find_field_desc($name) ?: $name;
                        $error_param = $this->_find_field_desc($param) ?: $param;
                        // Search for custom error message, also able to divide error by validate func
                        $error_msg = $this->_find_custom_validate_error($name, $func);
                        if ($error_msg) {
                            $error_msg = str_replace(['%field', '%param'], [$desc, $error_param], $error_msg);
                        } else {
                            // Default error message
                            $error_msg = t('form_validate_' . $func, ['%field' => $desc, '%param' => $error_param]);
                        }
                    }
                }
                // In this case we do not track error if field is empty and not required
                if ( ! $is_ok && ! $is_required && ! strlen($data[$name])) {
                    $is_ok = true;
                    $error_msg = '';
                }
                if ( ! $is_ok) {
                    $validate_ok = false;
                    if ( ! $error_msg) {
                        $error_msg = 'Wrong field ' . $name;
                    }
                    _re($error_msg, $name);
                    // In case when we see any validation rule is not OK - we stop checking further for this field
                    continue 2;
                }
            }
        }
        return $validate_ok;
    }

    /**
     * @param mixed $func
     * @param mixed $data
     */
    public function _apply_existing_func($func, $data)
    {
        if (is_array($data)) {
            $self = __FUNCTION__;
            foreach ($data as $k => $v) {
                $data[$k] = $this->$self($func, $v);
            }
            return $data;
        }
        return $func($data);
    }

    /**
     * @param mixed $name
     */
    public function _find_field_desc($name)
    {
        if ( ! strlen($name)) {
            return '';
        }
        $desc = $name;
        foreach ((array) $this->_body as $a) {
            if ( ! isset($a['extra']) || ! strlen($a['extra']['desc'])) {
                continue;
            }
            // Now we also support array elements descriptions searching
            if ($a['extra']['name'] != $name && $a['extra']['name'] != $name . '[]') {
                continue;
            }
            $desc = $a['extra']['desc'];
            break;
        }
        return $desc;
    }

    /**
     * @param mixed $name
     * @param mixed $func
     */
    public function _find_custom_validate_error($name, $func)
    {
        if ( ! strlen($name)) {
            return '';
        }
        $custom_error = '';
        foreach ((array) $this->_body as $a) {
            if ( ! isset($a['extra']) || ! isset($a['extra']['validate_error'])) {
                continue;
            }
            // Now we also support array elements descriptions searching
            if ($a['extra']['name'] != $name && $a['extra']['name'] != $name . '[]') {
                continue;
            }
            $custom_error = $a['extra']['validate_error'];
            break;
        }
        // Support for separate errors by validate functions
        if (is_array($custom_error)) {
            return isset($custom_error[$func]) ? $custom_error[$func] : '';
        }
        return $custom_error;
    }

    /**
     * Examples of validate rules setting:
     * 	'name1' => 'trim|required',
     * 	'name2' => ['trim', 'required'],
     * 	'name3' => ['trim|required', 'other_rule|other_rule2|other_rule3'],
     * 	'name4' => ['trim|required', function() { return true; } ],
     * 	'name5' => ['trim', 'required', function() { return true; } ],
     * 	'__before__' => 'trim',
     * 	'__after__' => 'some_method2|some_method3',.
     * @param mixed $validate_rules
     */
    public function _validate_rules_cleanup($validate_rules = [])
    {
        $func = __FUNCTION__;
        return _class('validate')->$func($validate_rules);
    }

    /**
     * @param mixed $raw
     */
    public function _validate_rules_array_from_raw($raw = '')
    {
        $func = __FUNCTION__;
        return _class('validate')->$func($raw);
    }

    /**
     * @param mixed $extra
     */
    public function _input_assign_params_from_validate($extra = [])
    {
        return _class('form2_validate', 'classes/form2/')->_input_assign_params_from_validate($extra, $this);
    }

    /**
     * Alias.
     * @param mixed $table
     * @param mixed $fields
     * @param mixed $add_fields
     * @param mixed $extra
     */
    public function insert_if_ok($table, $fields, $add_fields = [], $extra = [])
    {
        return $this->db_insert_if_ok($table, $fields, $add_fields, $extra);
    }

    /**
     * @param mixed $table
     * @param mixed $fields
     * @param mixed $add_fields
     * @param mixed $extra
     */
    public function db_insert_if_ok($table, $fields, $add_fields = [], $extra = [])
    {
        $extra['add_fields'] = $add_fields;
        return $this->_db_change_if_ok($table, $fields, 'insert', $extra);
    }

    /**
     * Alias.
     * @param mixed $table
     * @param mixed $fields
     * @param null|mixed $where_id
     * @param mixed $extra
     */
    public function update_if_ok($table, $fields, $where_id = null, $extra = [])
    {
        return $this->db_update_if_ok($table, $fields, $where_id, $extra);
    }

    /**
     * @param mixed $table
     * @param mixed $fields
     * @param null|mixed $where_id
     * @param mixed $extra
     */
    public function db_update_if_ok($table, $fields, $where_id = null, $extra = [])
    {
        $extra['where_id'] = $where_id;
        return $this->_db_change_if_ok($table, $fields, 'update', $extra);
    }

    /**
     * Alias.
     * @param mixed $table
     * @param mixed $fields
     * @param mixed $type
     * @param mixed $extra
     */
    public function change_if_ok($table, $fields, $type, $extra = [])
    {
        return $this->_db_change_if_ok($table, $fields, $type, $extra);
    }

    /**
     * @param mixed $table
     * @param mixed $fields
     * @param mixed $type
     * @param mixed $extra
     */
    public function _db_change_if_ok($table, $fields, $type, $extra = [])
    {
        $func = function ($table, $fields, $type, $extra, $form) {
            if ( ! $table || ! $type || empty($_POST)) {
                return $form;
            }
            $validate_ok = ($form->_validate_ok || $extra['force']);
            if ( ! $validate_ok) {
                return $form;
            }
            $data = [];
            foreach ((array) $fields as $k => $name) {
                // Example $fields = ['login','email'];
                if (is_numeric($k)) {
                    $db_field_name = $name;
                // Example $fields = ['pswd' => 'password'];
                } else {
                    $db_field_name = $name;
                    $name = $k;
                }
                if (isset($form->_validated_fields[$name])) {
                    $data[$db_field_name] = $form->_validated_fields[$name];
                }
            }
            // This is non-validated list of fields to add to the insert array
            foreach ((array) $extra['add_fields'] as $db_field_name => $value) {
                $data[$db_field_name] = $value;
            }
            // Callback/hook function implementation
            $on_before_update = isset($extra['on_before_update']) ? $extra['on_before_update'] : $form->_on['on_before_update'];
            if ($data && $table && is_callable($on_before_update)) {
                $on_before_update($data, $table, $fields, $type, $extra, $this);
            }
            _class('core_events')->fire('form.before_update', [$data, $table, $fields, $type, $extra]);
            if ($data && $table) {
                $db = is_object($form->_params['db']) ? $form->_params['db'] : db();
                if ($type == 'update') {
                    $where_id = $extra['where_id'] ?: $form->_replace[$form->_params['id'] ?: 'id'];
                    if ($where_id) {
                        $db->update_safe($table, $data, $where_id);
                    } else {
                        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' where_id param is empty, not updating table: ' . $table);
                    }
                } elseif ($type == 'insert') {
                    $db->insert_safe($table, $data);
                }
                // Callback/hook function implementation
                $on_after_update = isset($extra['on_after_update']) ? $extra['on_after_update'] : $form->_on['on_after_update'];
                if (is_callable($on_after_update)) {
                    $on_after_update($data, $table, $fields, $type, $extra, $this);
                }
                _class('core_events')->fire('form.after_update', [$data, $table, $fields, $type, $extra]);
                $on_success_text = isset($extra['on_success_text']) ? $extra['on_success_text'] : $form->_on['on_success_text'];
                if ($on_success_text) {
                    common()->set_notice($on_success_text);
                }
                $redirect_link = $extra['redirect_link'] ?: $form->_replace['redirect_link'];
                if ( ! $redirect_link) {
                    $redirect_link = $form->_replace['back_link'];
                }
                if ( ! $redirect_link || false === strpos($redirect_link, '/')) {
                    $redirect_link = url('/@object/@action/@id');
                }
                if ( ! $extra['no_redirect'] && ! main()->is_console()) {
                    js_redirect($redirect_link);
                }
            }
        };
        if ($this->_chained_mode) {
            $this->_db_change_if_ok = [
                'func' => $func,
                'table' => $table,
                'fields' => $fields,
                'type' => $type,
                'extra' => $extra,
            ];
            return $this;
        }
        return $this;
    }

    /**
     * @param mixed $func
     */
    public function on_post($func)
    {
        $this->_on[__FUNCTION__] = $func;
        return $this;
    }

    /**
     * @param mixed $func
     */
    public function on_before_render($func)
    {
        $this->_on[__FUNCTION__] = $func;
        return $this;
    }

    /**
     * @param mixed $func
     */
    public function on_after_render($func)
    {
        $this->_on[__FUNCTION__] = $func;
        return $this;
    }

    /**
     * @param mixed $func
     */
    public function on_before_validate($func)
    {
        $this->_on[__FUNCTION__] = $func;
        return $this;
    }

    /**
     * @param mixed $func
     */
    public function on_after_validate($func)
    {
        $this->_on[__FUNCTION__] = $func;
        return $this;
    }

    /**
     * @param mixed $func
     */
    public function on_validate_ok($func)
    {
        $this->_on[__FUNCTION__] = $func;
        return $this;
    }

    /**
     * @param mixed $func
     */
    public function on_validate_error($func)
    {
        $this->_on[__FUNCTION__] = $func;
        return $this;
    }

    /**
     * @param mixed $func
     */
    public function on_success_text($func)
    {
        $this->_on[__FUNCTION__] = $func;
        return $this;
    }

    /**
     * @param mixed $func
     */
    public function on_before_update($func)
    {
        $this->_on[__FUNCTION__] = $func;
        return $this;
    }

    /**
     * @param mixed $func
     */
    public function on_after_update($func)
    {
        $this->_on[__FUNCTION__] = $func;
        return $this;
    }
}
