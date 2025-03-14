<?php

/**
 * Modules template class.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_module
{
    /** @var bool */
    public $USE_CAPTCHA = true;
    /** @var bool Force stripslashes on '_format_text' method */
    public $FORCE_STRIPSLASHES = false;
    /** @var bool Use post preview or not */
    public $USE_PREVIEW = false;
    /** @var array @conf_skip */
    public $_comments_params = [];
    /** @var array @conf_skip */
    public $_poll_params = [];
    /** @var array @conf_skip */
    public $_tags_params = [];

    public $USER_ID = null;
    public $USER_GROUP = null;
    public $CAPTCHA = null;

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
     * @param mixed $params
     */
    public function _view_comments($params = [])
    {
        return module_safe('comments')->_show_for_object((array) $params + (array) $this->_comments_params);
    }

    /**
     * @param mixed $params
     */
    public function _get_num_comments($params = [])
    {
        if ( ! is_array($params)) {
            $params = ['objects_ids' => $params];
        }
        return module_safe('comments')->_get_num_comments((array) $params + (array) $this->_comments_params);
    }

    /**
     * @param mixed $params
     */
    public function _comment_is_allowed($params = [])
    {
        return true;
    }

    /**
     * @param mixed $params
     */
    public function _comment_edit_allowed($params = [])
    {
        return (bool) ($this->USER_ID && $params['user_id'] && $params['user_id'] == $this->USER_ID);
    }

    /**
     * @param mixed $params
     */
    public function _comment_delete_allowed($params = [])
    {
        return (bool) $this->USER_ID && $params['user_id'] && $params['user_id'] == $this->USER_ID;
    }

    /**
     * @param mixed $params
     */
    public function add_comment($params = [])
    {
        if ($_POST['submit'] == 'Preview') {
            return module('preview')->_display_preview(['text' => $_POST['text']]);
        }
        return module('comments')->_add((array) $this->_comments_params + (array) $params);
    }

    /**
     * @param mixed $params
     */
    public function edit_comment($params = [])
    {
        return module_safe('comments')->_edit((array) $this->_comments_params + (array) $params);
    }

    /**
     * @param mixed $params
     */
    public function delete_comment($params = [])
    {
        return module_safe('comments')->_delete((array) $this->_comments_params + (array) $params);
    }

    /**
     * Show captcha image.
     */
    public function captcha_image()
    {
        $this->_captcha_load_code();
        if (is_object($this->CAPTCHA)) {
            $this->CAPTCHA->show_image();
        }
    }

    /**
     * Validate captcha (posted code).
     */
    public function _captcha_check()
    {
        $this->_captcha_load_code();
        if (is_object($this->CAPTCHA)) {
            $this->CAPTCHA->check('captcha');
        }
    }

    /**
     * Display captcha html block code.
     */
    public function _captcha_block()
    {
        $this->_captcha_load_code();
        if (is_object($this->CAPTCHA)) {
            return $this->CAPTCHA->show_block('./?object=' . $_GET['object'] . '&action=captcha_image');
        }
    }

    /**
     * Load captcha code.
     */
    public function _captcha_load_code()
    {
        if ( ! $this->USE_CAPTCHA) {
            return false;
        }
        if (is_object($this->CAPTCHA)) {
            return false;
        }
        $this->CAPTCHA = _class('captcha');
    }

    /**
     * Display rate box (stars etx).
     * @param mixed $params
     */
    public function _show_rate_box($params = [])
    {
        return module_safe('rate')->_show_for_object($params);
    }

    /**
     * Display rate box (stars etx).
     * @param mixed $params
     */
    public function _prefetch_rate_infos($params = [])
    {
        return module_safe('rate')->_prefetch_rate_infos($params);
    }

    /**
     * Format given text (convert BB Codes, new lines etc).
     * @param mixed $body
     */
    public function _format_text($body = '')
    {
        // Stop here if text is empty
        if (empty($body)) {
            return '';
        }
        if ($this->FORCE_STRIPSLASHES) {
            $body = stripslashes($body);
        }
        if ($this->USE_BB_CODES) {
            $body = _class('bb_codes')->_process_text($body);
        } else {
            $body = nl2br(_prepare_html($body, 0));
        }
        return $body;
    }

    /**
     * Process custom box.
     * @param mixed $name
     * @param mixed $selected
     */
    public function _box($name = '', $selected = '')
    {
        if (empty($name) || empty($this->_boxes[$name])) {
            return false;
        }
        return eval('return common()->' . $this->_boxes[$name] . ';');
    }

    /**
     * Display preview of current module (usually popup window).
     * @param mixed $params
     * @param mixed $template
     */
    public function display_preview($params = [], $template = '')
    {
        if ($_POST != null) {
            return _class_safe('preview')->_display_preview(['replace' => $this->_prepare_preview()]);
        }
    }


    public function _prepare_preview()
    {
        return false;
    }

    /**
     * @param mixed $params
     * @param mixed $template
     */
    public function _display_submit_buttons($params = [], $template = '')
    {
        return $this->USE_PREVIEW ? _class_safe('preview')->_display_buttons() : '';
    }

    /**
     * @param mixed $ids
     * @param mixed $params
     */
    public function _show_tags($ids = [], $params = [])
    {
        return module_safe('tags')->_show($ids, (array) $this->_tags_params + (array) $params);
    }


    public function edit_tag()
    {
        return module_safe('tags')->_edit_tags($_GET['id']);
    }

    /**
     * Search by tag in a current module.
     */
    public function tag()
    {
        return module_safe('tags')->search();
    }

    /**
     * Shows poll block.
     * @param mixed $object_id
     * @param mixed $object_name
     */
    public function _poll($object_id, $object_name = '')
    {
        return module_safe('poll')->_show_poll_block($object_id, $object_name);
    }


    public function create_poll()
    {
        return module_safe('poll')->_create($this->_poll_params);
    }


    public function delete_poll()
    {
        return module_safe('poll')->delete($this->_poll_params);
    }


    public function view_poll_results()
    {
        return module_safe('poll')->owner_view($this->_poll_params);
    }
}
