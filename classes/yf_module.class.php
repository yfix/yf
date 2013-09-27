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
	/** @var bool Force stripslashes on "_format_text" method */
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
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* Load current user info
	*
	* @access	private
	* @return	void
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
	* Display comments
	*
	* @access	private
	* @return	void
	*/
	function _view_comments ($params = array()) {
		return module("comments")->_show_for_object( (array)$this->_comments_params + (array)$params );
	}

	/**
	* Get number of comments for the given object ids
	*
	* @access	private
	* @return	void
	*/
	function _get_num_comments ($params = array()) {
		if (!is_array($params)) {
			$params = array("objects_ids" => $params);
		}
		return module("comments")->_get_num_comments( (array)$this->_comments_params, (array)$params );
	}

	/**
	* Check if post comment is allowed (abstract)
	*
	* @access	private
	* @return	void
	*/
	function _comment_is_allowed ($params = array()) {
		return true;
	}

	/**
	* Check if comment edit allowed (abstract)
	*
	* @access	private
	* @return	void
	*/
	function _comment_edit_allowed ($params = array()) {
		$edit_allowed	= $this->USER_ID && $params["user_id"] && $params["user_id"] == $this->USER_ID;
		return (bool)$edit_allowed;
	}

	/**
	* Check if comment deletion allowed (abstract)
	*
	* @access	private
	* @return	void
	*/
	function _comment_delete_allowed ($params = array()) {
		$delete_allowed	= $this->USER_ID && $params["user_id"] && $params["user_id"] == $this->USER_ID;
		return (bool)$delete_allowed;
	}

	/**
	* Add new comment
	*
	* @access	private
	* @return	void
	*/
	function add_comment ($params = array()) {
		if ($_POST["submit"] == 'Preview') {
			return module("preview")->_display_preview(array('text' => $_POST["text"]));
		}
		return module("comments")->_add( (array)$this->_comments_params + (array)$params );
	}

	/**
	* Edit user comment
	*
	* @access	private
	* @return	void
	*/
	function edit_comment ($params = array()) {
		return module("comments")->_edit( (array)$this->_comments_params + (array)$params );
	}

	/**
	* Delete comment
	*
	* @access	private
	* @return	void
	*/
	function delete_comment ($params = array()) {
		return module("comments")->_delete( (array)$this->_comments_params + (array)$params );
	}

	/**
	* Show captcha image
	*
	* @access	private
	* @return	void
	*/
	function captcha_image() {
		$this->_captcha_load_code();
		if (is_object($this->CAPTCHA)) {
			$this->CAPTCHA->show_image();
		}
	}

	/**
	* Validate captcha (posted code)
	*
	* @access	private
	* @return	void
	*/
	function _captcha_check() {
		$this->_captcha_load_code();
		if (is_object($this->CAPTCHA)) {
			$this->CAPTCHA->check("captcha");
		}
	}

	/**
	* Display captcha html block code
	*
	* @access	private
	* @return	void
	*/
	function _captcha_block() {
		$this->_captcha_load_code();
		if (is_object($this->CAPTCHA)) {
			return $this->CAPTCHA->show_block("./?object=".$_GET["object"]."&action=captcha_image");
		}
	}

	/**
	* Load captcha code
	*
	* @access	private
	* @return	void
	*/
	function _captcha_load_code() {
		if (!$this->USE_CAPTCHA) {
			return false;
		}
		if (is_object($this->CAPTCHA)) {
			return false;
		}
		// Try to init captcha
		$this->CAPTCHA = _class("captcha");
//		$this->CAPTCHA->set_image_size(120, 50);
//		$this->CAPTCHA->font_height = 16;
	}

	/**
	* Display rate box (stars etx)
	*
	* @access	private
	* @return	void
	*/
	function _show_rate_box ($params = array()) {
		return module("rate")->_show_for_object($params);
	}

	/**
	* Display rate box (stars etx)
	*
	* @access	private
	* @return	void
	*/
	function _prefetch_rate_infos ($params = array()) {
		return module("rate")->_prefetch_rate_infos($params);
	}

	/**
	* Format given text (convert BB Codes, new lines etc)
	*
	* @access	private
	* @return	string
	*/
	function _format_text ($body = "") {
		// Stop here if text is empty
		if (empty($body)) {
			return "";
		}
		if ($this->FORCE_STRIPSLASHES) {
			$body = stripslashes($body);
		}
		if ($this->USE_BB_CODES) {
			$body = _class("bb_codes")->_process_text($body);
		} else {
			$body = nl2br(_prepare_html($body, 0));
		}
		return $body;
	}

	/**
	* Process custom box
	*
	* @access	private
	* @return	string
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Display preview of current module (usually popup window)
	*
	* @access	public
	* @return	string
	*/
	function display_preview($params = array(), $template = "") {
		if ($_POST != null) {
			return main()->call_class_method("preview", "classes/", "_display_preview", array("replace" => $this->_prepare_preview()));
		}
	}

	/**
	* Replace with custom preview if needed
	*
	* @access	private
	* @return	string
	*/
	function _prepare_preview() {
		return false;
	}

	/**
	* Preview buttons
	*
	* @access	private
	* @return	string
	*/
	function _display_submit_buttons($params = array(), $template = "") {
		return $this->USE_PREVIEW ? main()->call_class_method("preview", "classes/", "_display_buttons") : "";
	}

	/**
	* Display tags
	*
	* @access	private
	* @return	void
	*/
	function _show_tags ($ids = array(), $params = array()) {
		return module("tags")->_show($ids, (array)$this->_tags_params + (array)$params );
	}

	/**
	* Edit tags
	*
	* @access	private
	* @return	void
	*/
	function edit_tag () {
		return module("tags")->_edit_tags($_GET["id"]);
	}

	/**
	* Search by tag in a current module
	*/
	function tag() {
		return module("tags")->search();
	}

	/**
	* Shows poll block
	*/
	function _poll($object_id, $object_name = "") {
		return module("poll")->_show_poll_block($object_id, $object_name);
	}

	/**
	* Create poll
	*/
	function create_poll() {
		return module("poll")->_create($this->_poll_params);
	}

	/**
	* Delete poll
	*/
	function delete_poll() {
		return module("poll")->delete($this->_poll_params);
	}

	/**
	* View poll results
	*/
	function view_poll_results() {
		return module("poll")->owner_view($this->_poll_params);
	}
}
