<?php


class yf_dynamic_lang
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
     * Change current user language.
     */
    public function change_lang()
    {
        if ( ! $this->_parent->ALLOW_LANG_CHANGE) {
            return _e('Changing language not allowed!');
        }
        $new_lang = _prepare_html($_REQUEST['lang_id']);
        if ( ! empty($new_lang) && conf('languages::' . $new_lang . '::active')) {
            $_SESSION['user_lang'] = $new_lang;
            $old_location = './?object=account';
            if ( ! empty($_POST['back_url'])) {
                $old_location = str_replace(WEB_PATH, './', $_POST['back_url']);
            }
            return js_redirect($old_location/*. '&lang='.(!isset($_GET['language']) ? $_SESSION['user_lang'] : $_GET['language'])*/);
        }
        return js_redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * Display form.
     */
    public function change_lang_form()
    {
        return $this->_change_lang_form();
    }

    /**
     * BLock with change lang and skin selects.
     */
    public function _change_lang_form()
    {
        if ( ! $this->_parent->ALLOW_LANG_CHANGE) {
            return false;
        }
        foreach ((array) conf('languages') as $lang_info) {
            if ( ! $lang_info['active']) {
                continue;
            }
            $lang_names[$lang_info['locale']] = $lang_info['name'];
        }
        if (empty($lang_names)) {
            return false;
        }
        $atts = ' onchange="this.form.submit();"';
        $replace = [
            'form_action' => './?object=' . str_replace(YF_PREFIX, '', __CLASS__) . '&action=change_lang',
            'lang_box' => common()->select_box('lang_id', [t('Language') => $lang_names], conf('language'), false, 2, $atts, false),
            'back_url' => WEB_PATH . '?object=' . $_GET['object'] . ($_GET['action'] != 'show' ? '&action=' . $_GET['action'] : '') . ( ! empty($_GET['id']) ? '&id=' . $_GET['id'] : '') . ( ! empty($_GET['page']) ? '&page=' . $_GET['page'] : ''),
        ];
        return tpl()->parse('system/change_lang_form', $replace);
    }
}
