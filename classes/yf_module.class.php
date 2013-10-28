<?php

/**
* Modules template class
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_module {

	/** @var bool */
	public $USE_CAPTCHA		= true;
	/** @var bool Force stripslashes on '_format_text' method */
	public $FORCE_STRIPSLASHES	= false;
	/** @var bool Use post preview or not */
	public $USE_PREVIEW		= false;
	/** @var array @conf_skip */
	public $_comments_params	= array();
	/** @var array @conf_skip */
	public $_poll_params		= array();
	/** @var array @conf_skip */
	public $_tags_params		= array();

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function _load_cur_user_info () {
		// Current user ID and group
		$this->USER_ID		= &main()->USER_ID;
		$this->USER_GROUP	= &main()->USER_GROUP;
		if ($this->USER_ID) {
			$this->_user_info = &main()->USER_INFO;
			if (!$this->_user_info) {
				$this->_user_info = user($this->USER_ID);
			}
		}
	}

	/**
	*/
	function _view_comments ($params = array()) {
		return module_safe('comments')->_show_for_object( (array)$this->_comments_params + (array)$params );
	}

	/**
	*/
	function _get_num_comments ($params = array()) {
		if (!is_array($params)) {
			$params = array('objects_ids' => $params);
		}
		return module_safe('comments')->_get_num_comments( (array)$this->_comments_params, (array)$params );
	}

	/**
	*/
	function _comment_is_allowed ($params = array()) {
		return true;
	}

	/**
	*/
	function _comment_edit_allowed ($params = array()) {
		return (bool)($this->USER_ID && $params['user_id'] && $params['user_id'] == $this->USER_ID);
	}

	/**
	*/
	function _comment_delete_allowed ($params = array()) {
		return (bool)$this->USER_ID && $params['user_id'] && $params['user_id'] == $this->USER_ID;
	}

	/**
	*/
	function add_comment ($params = array()) {
		if ($_POST['submit'] == 'Preview') {
			return module('preview')->_display_preview(array('text' => $_POST['text']));
		}
		return module('comments')->_add( (array)$this->_comments_params + (array)$params );
	}

	/**
	*/
	function edit_comment ($params = array()) {
		return module_safe('comments')->_edit( (array)$this->_comments_params + (array)$params );
	}

	/**
	*/
	function delete_comment ($params = array()) {
		return module_safe('comments')->_delete( (array)$this->_comments_params + (array)$params );
	}

	/**
	* Show captcha image
	*/
	function captcha_image() {
		$this->_captcha_load_code();
		if (is_object($this->CAPTCHA)) {
			$this->CAPTCHA->show_image();
		}
	}

	/**
	* Validate captcha (posted code)
	*/
	function _captcha_check() {
		$this->_captcha_load_code();
		if (is_object($this->CAPTCHA)) {
			$this->CAPTCHA->check('captcha');
		}
	}

	/**
	* Display captcha html block code
	*/
	function _captcha_block() {
		$this->_captcha_load_code();
		if (is_object($this->CAPTCHA)) {
			return $this->CAPTCHA->show_block('./?object='.$_GET['object'].'&action=captcha_image');
		}
	}

	/**
	* Load captcha code
	*/
	function _captcha_load_code() {
		if (!$this->USE_CAPTCHA) {
			return false;
		}
		if (is_object($this->CAPTCHA)) {
			return false;
		}
		$this->CAPTCHA = _class('captcha');
	}

	/**
	* Display rate box (stars etx)
	*/
	function _show_rate_box ($params = array()) {
		return module_safe('rate')->_show_for_object($params);
	}

	/**
	* Display rate box (stars etx)
	*/
	function _prefetch_rate_infos ($params = array()) {
		return module_safe('rate')->_prefetch_rate_infos($params);
	}

	/**
	* Format given text (convert BB Codes, new lines etc)
	*/
	function _format_text ($body = '') {
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
	* Process custom box
	*/
	function _box ($name = '', $selected = '') {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval('return common()->'.$this->_boxes[$name].';');
	}

	/**
	* Display preview of current module (usually popup window)
	*/
	function display_preview($params = array(), $template = '') {
		if ($_POST != null) {
			return _class_safe('preview')->_display_preview(array('replace' => $this->_prepare_preview()));
		}
	}

	/**
	*/
	function _prepare_preview() {
		return false;
	}

	/**
	*/
	function _display_submit_buttons($params = array(), $template = '') {
		return $this->USE_PREVIEW ? _class_safe('preview')->_display_buttons() : '';
	}

	/**
	*/
	function _show_tags ($ids = array(), $params = array()) {
		return module_safe('tags')->_show($ids, (array)$this->_tags_params + (array)$params );
	}

	/**
	*/
	function edit_tag () {
		return module_safe('tags')->_edit_tags($_GET['id']);
	}

	/**
	* Search by tag in a current module
	*/
	function tag() {
		return module_safe('tags')->search();
	}

	/**
	* Shows poll block
	*/
	function _poll($object_id, $object_name = '') {
		return module_safe('poll')->_show_poll_block($object_id, $object_name);
	}

	/**
	*/
	function create_poll() {
		return module_safe('poll')->_create($this->_poll_params);
	}

	/**
	*/
	function delete_poll() {
		return module_safe('poll')->delete($this->_poll_params);
	}

	/**
	*/
	function view_poll_results() {
		return module_safe('poll')->owner_view($this->_poll_params);
	}
}
