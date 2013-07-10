<?php

/**
* Form2, using bootstrap html/css framework
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_form2 {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* Render result form html, gathered by row functions
	*/
	function render() {
		return implode("\n", $this->_body);
	}

	/**
	* Wrapper for template engine
	* Example:
	*	return common()->form2($replace)
	*		->form_begin()
	*		->text("login","Login")
	*		->text("password","Password")
	*		->text("first_name","First Name")
	*		->text("last_name","Last Name")
	*		->text("go_after_login","Url after login")
	*		->box_with_link("group_box","Group","groups_link")
	*		->active("active","Active")
	*		->info("add_date","Added")
	*		->form_end()
	*		->render();
	*/
	function chained_wrapper($replace = array()) {
		$this->_chained_mode = true;
		$this->_replace = $replace;
// TODO: need to change API to create new class instance on every chained request
// TODO: test how this will work with several forms
		return $this;
	}

	/**
	* Wrapper for template engine
	* Example template:
	*	{form_row("form_begin")}
	*	{form_row("text","login")}
	*	{form_row("text","password")}
	*	{form_row("text","first_name")}
	*	{form_row("text","last_name")}
	*	{form_row("text","go_after_login","Url after login")}
	*	{form_row("box_with_link","group_box","Group","groups_link")}
	*	{form_row("active_box")}
	*	{form_row("info","add_date","Added")}
	*	{form_row("save_and_back")}
	*	{form_row("form_end")}
	*/
	function tpl_row($type = "input", $replace = array(), $name, $desc = "", $extra = array()) {
// TODO: integrate with named errors
#		$errors = array();
		return $this->$type($name, $desc, $extra, $replace);
	}

	/**
	*/
	function text($name, $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$id = $extra["id"] ? $extra["id"] : $name;
		$placeholder = isset($extra["placeholder"]) ? $extra["placeholder"] : $desc;
		$value = isset($extra["value"]) ? $extra["value"] : $replace[$name];

		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$id.'">'.t($desc).'</label>
				<div class="controls">
					<input type="text" id="'.$id.'" name="'.$name.'" placeholder="'.t($placeholder).'" value="'.htmlspecialchars($value, ENT_QUOTES).'"'
					.($extra["class"] ? ' class="'.$extra["class"].'"' : '')
					.($extra["style"] ? ' style="'.$extra["style"].'"' : '')
					.($extra["data"] ? ' data="'.$extra["data"].'"' : '')
					.'>'
					.(isset($errors[$name]) ? '<span class="help-inline">'.$errors[$name].'</span>' : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function textarea($name, $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$id = $extra["id"] ? $extra["id"] : $name;
		$placeholder = isset($extra["placeholder"]) ? $extra["placeholder"] : $desc;
		$value = isset($extra["value"]) ? $extra["value"] : $replace[$name];

		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$id.'">'.t($desc).'</label>
				<div class="controls">
					<textarea id="'.$id.'" name="'.$name.'" placeholder="'.t($placeholder).'"'
					.($extra["cols"] ? ' cols="'.$extra["cols"].'"' : '')
					.($extra["rows"] ? ' rows="'.$extra["rows"].'"' : '')
					.($extra["class"] ? ' class="'.$extra["class"].'"' : '')
					.($extra["style"] ? ' style="'.$extra["style"].'"' : '')
					.($extra["data"] ? ' data="'.$extra["data"].'"' : '')
					.'>'.htmlspecialchars($value, ENT_QUOTES).'</textarea>'
					.(isset($errors[$name]) ? '<span class="help-inline">'.$errors[$name].'</span>' : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function active_box($name = "", $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$name) {
			$name = "active";
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$_statuses = array(
			'0' => '<span class="label label-warning">'.t('Disabled').'</span>',
			'1' => '<span class="label label-success">'.t('Active').'</span>',
		);
		$selected = $replace[$name];
		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">'
					.common()->radio_box($name, $_statuses, $selected, false, 2, "", false)
					.(isset($errors[$name]) ? '<span class="help-inline">'.$errors[$name].'</span>' : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function allow_deny_box($name = "", $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$name) {
			$name = "active";
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$_statuses = array(
			'DENY' => '<span class="label label-warning">'.t('Deny').'</span>', 
			'ALLOW' => '<span class="label label-success">'.t('Allow').'</span>',
		);
		$selected = $replace[$name];
		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">'
					.common()->radio_box($name, $_statuses, $selected, false, 2, "", false)
					.(isset($errors[$name]) ? '<span class="help-inline">'.$errors[$name].'</span>' : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function save($name = "", $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$id = $extra["id"] ? $extra["id"] : $name;
		$value = isset($extra["value"]) ? $extra["value"] : 'Save';

		$body = '
			<div class="control-group">
				<div class="controls">
					<input type="submit" value="'.t($value).'" class="btn'.($extra["class"] ? ' '.$extra["class"] : '').'"'
					.($extra["style"] ? ' style="'.$extra["style"].'"' : '')
					.($extra["data"] ? ' data="'.$extra["data"].'"' : '')
					.'>'
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function save_and_back($name = "", $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$name) {
			$name = 'back_link';
		}
		$id = $extra["id"] ? $extra["id"] : $name;
		$value = isset($extra["value"]) ? $extra["value"] : 'Save';

		$body = '
			<div class="control-group">
				<div class="controls">
					<input type="submit" value="'.t('Save').'" class="btn'.($extra["class"] ? ' '.$extra["class"] : '').'"'
					.($extra["style"] ? ' style="'.$extra["style"].'"' : '')
					.($extra["data"] ? ' data="'.$extra["data"].'"' : '')
					.'>'
					.($replace[$name] ? ' <a href="'.$replace[$name].'" class="btn">'.t('Back').'</a>' : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function save_and_clear($name = "", $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$name) {
			$name = 'clear_link';
		}
		$id = $extra["id"] ? $extra["id"] : $name;
		$value = isset($extra["value"]) ? $extra["value"] : 'Save';

		$body = '
			<div class="control-group">
				<div class="controls">
					<input type="submit" value="'.t($value).'" class="btn'.($extra["class"] ? ' '.$extra["class"] : '').'"'
					.($extra["style"] ? ' style="'.$extra["style"].'"' : '')
					.($extra["data"] ? ' data="'.$extra["data"].'"' : '')
					.'>'
					.($replace[$name] ? ' <a href="'.$replace[$name].'" class="btn">'.t('Clear').'</a>' : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function info($name, $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$body = '
			<div class="control-group">
				<label class="control-label">'.t($desc).'</label>
				<div class="controls"><span class="label label-info">'.htmlspecialchars($replace[$name], ENT_QUOTES).'</span></div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function box($name, $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.$replace[$name].'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function box_with_link($name, $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (is_string($extra)) {
			$extra = array(
				"edit_link" => $extra,
			);
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$edit_link = $replace[$extra["edit_link"]];
		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.$replace[$name].' <a href="'.$edit_link.'"><i class="icon-edit"></i></a></div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	* Just html wrapper for any custom content inside
	*/
	function container($text, $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.$text.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function select_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $replace[$name];
		$show_text = isset($extra["show_text"]) ? $extra["show_text"] : 1;
		$type = isset($extra["type"]) ? $extra["type"] : 2;
		$add_str = isset($extra["add_str"]) ? $extra["add_str"] : "";
		$translate = isset($extra["translate"]) ? $extra["translate"] : 0;
		$level = isset($extra["level"]) ? $extra["level"] : 0;
		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.common()->select_box($name, $values, $selected, $show_text, $type, $add_str, $translate, $level).'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function multi_select_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $replace[$name];
		$show_text = isset($extra["show_text"]) ? $extra["show_text"] : 1;
		$type = isset($extra["type"]) ? $extra["type"] : 2;
		$add_str = isset($extra["add_str"]) ? $extra["add_str"] : "";
		$translate = isset($extra["translate"]) ? $extra["translate"] : 0;
		$level = isset($extra["level"]) ? $extra["level"] : 0;
		$disabled = isset($extra["disabled"]) ? $extra["disabled"] : false;
		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.common()->multi_select_box($name, $values, $selected, $show_text, $type, $add_str, $translate, $level, $disabled).'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function check_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $replace[$name];
		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.common()->check_box($name, $values, $selected).'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function multi_check_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $replace[$name];
		$type = isset($extra["type"]) ? $extra["type"] : 2;
		$add_str = isset($extra["add_str"]) ? $extra["add_str"] : "";
		$translate = isset($extra["translate"]) ? $extra["translate"] : 0;
		$flow_vertical = isset($extra["flow_vertical"]) ? $extra["flow_vertical"] : false;
		$name_as_array = isset($extra["name_as_array"]) ? $extra["name_as_array"] : false;
		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.common()->multi_check_box($name, $values, $selected, $flow_vertical, $type, $add_str, $translate, $name_as_array).'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function radio_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $replace[$name];
		$type = isset($extra["type"]) ? $extra["type"] : 2;
		$add_str = isset($extra["add_str"]) ? $extra["add_str"] : "";
		$translate = isset($extra["translate"]) ? $extra["translate"] : 0;
		$flow_vertical = isset($extra["flow_vertical"]) ? $extra["flow_vertical"] : false;
		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.common()->select_box($name, $values, $selected, $flow_vertical, $type, $add_str, $translate).'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
//	function date_box ($selected_date = "", $years = "", $name_postfix = "", $add_str = "", $order = "ymd", $show_text = 1, $translate = 1) {
	function date_box($name, $values, $extra = array(), $replace = array()) {
// TODO
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
/*		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $replace[$name];
*/
// TODO: need to put first param $name instead of name_postfix (compare html: date[year],date[month],date[day] with year_postfix,month_postfix,day_postfix)
		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.common()->date_box($selected_date, $years, $name_postfix, $add_str, $order, $show_text, $translate).'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
//	function time_box ($selected_time = "", $name_postfix = "", $add_str = "", $show_text = 1, $translate = 1) {
	function time_box($name, $values, $extra = array(), $replace = array()) {
// TODO
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
/*		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $replace[$name];
*/
// TODO: need to put first param $name instead of name_postfix (compare: time[hour],time[minute],time[second] with hour_postfix,minute_postfix,second_postfix)
		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.common()->time_box($selected_time, $name_postfix, $add_str, $show_text, $translate).'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function datetime_box() {
// TODO
	}

	/**
	*/
	function birth_box() {
// TODO
	}

	/**
	* Input type=file
	*/
	function file() {
// TODO
	}

	/**
	* Image upload
	*/
	function image() {
// TODO
	}

	/**
	*/
	function form_begin($name = "", $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$name) {
			$name = "form_action";
		}
// TODO: enctype="multipart/form-data"
		$body = '<form method="post" action="'.$replace[$name].'" class="form-horizontal">';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function form_end($name = "", $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$body = '</form>';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function tbl_link($name, $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
// TODO
$body = 'TODO';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function tbl_link_edit($name, $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
// TODO
$body = 'TODO';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function tbl_link_del($name, $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
// TODO
$body = 'TODO';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function tbl_link_active($name = "", $desc = "", $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!$name) {
			$name = "active_link";
		}
		$active_link = $replace[$name];
// TODO
$body = 'TODO';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}
}
