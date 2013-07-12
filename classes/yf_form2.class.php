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
	* Params here not required, but if provided - will be passed to form_begin()
	*/
	function render($name = '', $method = '', $extra = array(), $replace = array()) {
		// Call these methods, if not done yet, save 2 api calls
		if (!isset($this->_body['form_begin'])) {
			$this->form_begin($name, $method, $extra, $replace);
		}
		if (!isset($this->_body['form_end'])) {
			$this->form_end();
		}
		// Ensure that form begin and ending will be in the right place of the output
		$form_begin = $this->_body['form_begin'];
		unset($this->_body['form_begin']);
		$form_end = $this->_body['form_end'];
		unset($this->_body['form_end']);
		return $form_begin. "\n". implode("\n", $this->_body). "\n". $form_end;
	}

	/**
	* Wrapper for template engine
	* Example:
	*	return common()->form2($replace)
	*		->text("login","Login")
	*		->text("password","Password")
	*		->text("first_name","First Name")
	*		->text("last_name","Last Name")
	*		->text("go_after_login","Url after login")
	*		->box_with_link("group_box","Group","groups_link")
	*		->active("active","Active")
	*		->info("add_date","Added")
	*		->render();
	*/
	function chained_wrapper($replace = array()) {
		$this->_chained_mode = true;
		$this->_replace = $replace;
// TODO: need to change API to create new class instance on every chained request
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
	function tpl_row($type = "input", $replace = array(), $name, $desc = '', $extra = array()) {
// TODO: integrate with named errors
#		$errors = array();
		return $this->$type($name, $desc, $extra, $replace);
	}

	/**
	*/
	function form_begin($name = '', $method = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$name) {
			$name = 'form_action';
		}
		if (!$method) {
			$method = 'post';
		}
		$enctype = '';
		if ($extra['enctype']) {
			$enctype = $extra['enctype'];
		} elseif ($extra['for_upload']) {
			$enctype = 'multipart/form-data';
		}
		$r = $replace ? $replace : $this->_replace;
		$body = '<form method="'.$method.'" action="'.$r[$name].'" class="form-horizontal'.($extra['class'] ? ' '.$extra['class'] : '').'"'
			.($extra['style'] ? ' style="'.$extra['style'].'"' : '')
			.($extra['id'] ? ' id="'.$extra['id'].'"' : '')
			.($extra['name'] ? ' name="'.$extra['name'].'"' : '')
			.($extra['enctype'] ? ' enctype="'.$extra['enctype'].'"' : '')
			.'>';
		if ($this->_chained_mode) {
			$this->_body[__FUNCTION__] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function form_end($name = '', $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$body = '</form>';
		if ($this->_chained_mode) {
			$this->_body[__FUNCTION__] = $body;
			return $this;
		}
		return $body;
	}

	/**
	* General input
	*/
	function input($name, $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$r = $replace ? $replace : $this->_replace;
		$id = $extra["id"] ? $extra["id"] : $name;
		$placeholder = isset($extra["placeholder"]) ? $extra["placeholder"] : $desc;
		$value = isset($extra["value"]) ? $extra["value"] : $r[$name];
		$input_type = isset($extra["type"]) ? $extra["type"] : "text";
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		$prepend = $extra['prepend'] ? $extra['prepend'] : '';
		$append = $extra['append'] ? $extra['append'] : '';
		// Supported: mini, small, medium, large, xlarge, xxlarge
		if ($extra['sizing']) {
			$extra['class'] .= ' input-'.$extra['sizing'];
		}
		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$id.'">'.t($desc).'</label>
				<div class="controls">'
					.(($prepend || $append) ? '<div class="'.($prepend ? 'input-prepend' : '').($append ? ' input-append' : '').'">' : '')
					.($prepend ? '<span class="add-on">'.$prepend.'</span>' : '')
					.'<input type="'.$input_type.'" id="'.$id.'" name="'.$name.'" placeholder="'.t($placeholder).'" value="'.htmlspecialchars($value, ENT_QUOTES).'"'
					.($extra["class"] ? ' class="'.$extra["class"].'"' : '')
					.($extra["style"] ? ' style="'.$extra["style"].'"' : '')
					.($extra["data"] ? ' data="'.$extra["data"].'"' : '')
					.($extra["size"] ? ' size="'.$extra["size"].'"' : '')
					.($extra["maxlength"] ? ' maxlength="'.$extra["maxlength"].'"' : '')
					.($extra["disabled"] ? ' disabled' : '')
					.($extra["required"] ? ' required' : '')
					.'>'
					.($append ? '<span class="add-on">'.$append.'</span>' : '')
					.(($prepend || $append) ? '</div>' : '')
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
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
	function textarea($name, $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$r = $replace ? $replace : $this->_replace;
		$id = $extra["id"] ? $extra["id"] : $name;
		$placeholder = isset($extra["placeholder"]) ? $extra["placeholder"] : $desc;
		$value = isset($extra["value"]) ? $extra["value"] : $r[$name];
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];

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
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
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
	* Just hidden input
	*/
	function hidden($name, $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		$id = $extra["id"] ? $extra["id"] : $name;
		$value = isset($extra["value"]) ? $extra["value"] : $r[$name];

		$body = '<input type="hidden" id="'.$id.'" name="'.$name.'" value="'.htmlspecialchars($value, ENT_QUOTES).'"'.($extra["data"] ? ' data="'.$extra["data"].'"' : '').'>';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function text($name, $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'text';
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	*/
	function password($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'password';
		if (!$name) {
			$name = 'password';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	*/
	function file($name, $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'file';
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	*/
	function email($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'email';
		$extra['prepend'] = '@';
		if (!$name) {
			$name = 'email';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	*/
	function number($name, $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'number';
		$extra['sizing'] = 'small';
		$extra['maxlength'] = '10';
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	*/
	function integer($name, $desc = '', $extra = array(), $replace = array()) {
		return $this->number($name, $desc, $extra, $replace);
	}

	/**
	*/
	function money($name, $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'text';
		$extra['prepend'] = '$';
		$extra['append'] = '.00';
		$extra['sizing'] = 'small';
		$extra['maxlength'] = '8';
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	*/
	function url($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'url';
		$extra['prepend'] = 'url';
		if (!$name) {
			$name = 'url';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	*/
	function active_box($name = '', $desc = '', $extra = array(), $replace = array()) {
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
		if (!$extra['items']) {
			$extra['items'] = array(
				'0' => '<span class="label label-warning">'.t('Disabled').'</span>',
				'1' => '<span class="label label-success">'.t('Active').'</span>',
			);
		}
		$r = $replace ? $replace : $this->_replace;
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		$selected = isset($extra['selected']) ? $extra['selected'] : $r[$name];
		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">'
					.common()->radio_box($name, $extra['items'], $selected, false, 2, '', false)
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
	function allow_deny_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['items'] = array(
			'DENY' => '<span class="label label-warning">'.t('Deny').'</span>', 
			'ALLOW' => '<span class="label label-success">'.t('Allow').'</span>',
		);
		return $this->active_box($name, $desc, $extra, $replace);
	}

	/**
	*/
	function yes_no_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['items'] = array(
			'1' => '<span class="label label-success">'.t('YES').'</span>',
			'0' => '<span class="label label-warning">'.t('NO').'</span>', 
		);
		return $this->active_box($name, $desc, $extra, $replace);
	}

	/**
	*/
	function submit($name = '', $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		$id = $extra["id"] ? $extra["id"] : $name;
		$value = isset($extra["value"]) ? $extra["value"] : 'Save';
		$link_url = $extra["link_url"] ? (isset($r[$extra["link_url"]]) ? $r[$extra["link_url"]] : $extra["link_url"]) : '';
		$link_name = $extra["link_name"] ? $extra["link_name"] : '';

		$body = '
			<div class="control-group">
				<div class="controls">
					<input type="submit" value="'.t($value).'" class="btn btn-primary'.($extra["class"] ? ' '.$extra["class"] : '').'"'
					.($extra["style"] ? ' style="'.$extra["style"].'"' : '')
					.($extra["data"] ? ' data="'.$extra["data"].'"' : '')
					.'>'
					.($link_url ? ' <a href="'.$link_url.'" class="btn">'.t($link_name).'</a>' : '')
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
	function save($name = '', $desc = '', $extra = array(), $replace = array()) {
		return $this->submit($name, $desc, $extra, $replace);
	}

	/**
	*/
	function save_and_back($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (!$name) {
			$name = 'back_link';
			$r = $replace ? $replace : $this->_replace;
			if (!isset($r[$name]) && isset($r['back_url'])) {
				$name = 'back_url';
			}
		}
		if (!$desc) {
			$desc = 'Back';
		}
		$extra['link_url'] = $name;
		$extra['link_name'] = $desc;
		return $this->submit($name, $desc, $extra, $replace);
	}

	/**
	*/
	function save_and_clear($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (!$name) {
			$name = 'clear_link';
			$r = $replace ? $replace : $this->_replace;
			if (!isset($r[$name]) && isset($r['clear_url'])) {
				$name = 'clear_url';
			}
		}
		if (!$desc) {
			$desc = 'Clear';
		}
		$extra['link_url'] = $name;
		$extra['link_name'] = $desc;
		return $this->submit($name, $desc, $extra, $replace);
	}

	/**
	*/
	function info($name, $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$r = $replace ? $replace : $this->_replace;
		$body = '
			<div class="control-group">
				<label class="control-label">'.t($desc).'</label>
				<div class="controls"><span class="label label-info">'.htmlspecialchars($r[$name], ENT_QUOTES).'</span></div>
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
	function box($name, $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		$edit_link = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'
					.$r[$name]
					.($edit_link ? ' <a href="'.$edit_link.'" class="btn btn-mini"><i class="icon-edit"></i> '.t('Edit').'</a>' : '')
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
	function box_with_link($name, $desc = '', $link = '', $replace = array()) {
		return $this->box($name, $desc, array("edit_link" => $link), $replace);
	}

	/**
	* Just html wrapper for any custom content inside
	*/
	function container($text, $desc = '', $extra = array(), $replace = array()) {
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
		$r = $replace ? $replace : $this->_replace;
		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $r[$name];
		$show_text = isset($extra["show_text"]) ? $extra["show_text"] : 1;
		$type = isset($extra["type"]) ? $extra["type"] : 2;
		$add_str = isset($extra["add_str"]) ? $extra["add_str"] : '';
		$translate = isset($extra["translate"]) ? $extra["translate"] : 0;
		$level = isset($extra["level"]) ? $extra["level"] : 0;
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];

		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'
					.common()->select_box($name, $values, $selected, $show_text, $type, $add_str, $translate, $level)
					.($extra['edit_link'] ? ' <a href="'.$extra['edit_link'].'" class="btn btn-mini"><i class="icon-edit"></i> '.t('Edit').'</a>' : '')
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
	function multi_select_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $r[$name];
		$show_text = isset($extra["show_text"]) ? $extra["show_text"] : 1;
		$type = isset($extra["type"]) ? $extra["type"] : 2;
		$add_str = isset($extra["add_str"]) ? $extra["add_str"] : '';
		$translate = isset($extra["translate"]) ? $extra["translate"] : 0;
		$level = isset($extra["level"]) ? $extra["level"] : 0;
		$disabled = isset($extra["disabled"]) ? $extra["disabled"] : false;
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];

		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'
					.common()->multi_select_box($name, $values, $selected, $show_text, $type, $add_str, $translate, $level, $disabled)
					.($extra['edit_link'] ? ' <a href="'.$extra['edit_link'].'" class="btn btn-mini"><i class="icon-edit"></i> '.t('Edit').'</a>' : '')
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
	function check_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $r[$name];
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];

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
		$r = $replace ? $replace : $this->_replace;
		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $r[$name];
		$type = isset($extra["type"]) ? $extra["type"] : 2;
		$add_str = isset($extra["add_str"]) ? $extra["add_str"] : '';
		$translate = isset($extra["translate"]) ? $extra["translate"] : 0;
		$flow_vertical = isset($extra["flow_vertical"]) ? $extra["flow_vertical"] : false;
		$name_as_array = isset($extra["name_as_array"]) ? $extra["name_as_array"] : false;
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];

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
		$r = $replace ? $replace : $this->_replace;
		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $r[$name];
		$type = isset($extra["type"]) ? $extra["type"] : 2;
		$add_str = isset($extra["add_str"]) ? $extra["add_str"] : '';
		$translate = isset($extra["translate"]) ? $extra["translate"] : 0;
		$flow_vertical = isset($extra["flow_vertical"]) ? $extra["flow_vertical"] : false;
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];

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
	function date_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $r[$name];
		$years = isset($extra["years"]) ? $extra["years"] : '';
		$show_what = isset($extra["show_what"]) ? $extra["show_what"] : "ymd";
		$show_text = isset($extra["show_text"]) ? $extra["show_text"] : 1;
		$add_str = isset($extra["add_str"]) ? $extra["add_str"] : '';
		$translate = isset($extra["translate"]) ? $extra["translate"] : 1;
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];

		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.common()->date_box2($name, $selected, $years, $add_str, $show_what, $show_text, $translate).'</div>
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
	function time_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		$values = isset($extra["values"]) ? $extra["values"] : (array)$values; // Required
		$selected = isset($extra["selected"]) ? $extra["selected"] : $r[$name];
		$show_text = isset($extra["show_text"]) ? $extra["show_text"] : 1;
		$add_str = isset($extra["add_str"]) ? $extra["add_str"] : '';
		$translate = isset($extra["translate"]) ? $extra["translate"] : 1;
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];

		$body = '
			<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.common()->time_box2($name, $selected, $add_str, $show_text, $translate).'</div>
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
	function datetime_box($name, $values, $extra = array(), $replace = array()) {
		if (!isset($extra['show_what'])) {
			$extra['show_what'] = 'ymdhis';
		}
		return $this->date_box($name, $values, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link($name, $link, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		$r = $replace ? $replace : $this->_replace;
		$link_url = isset($r[$link]) ? $r[$link] : $link;
		$body = ' <a href="'.$link_url.'" class="btn btn-mini"><i class="icon-tasks"></i> '.t($name).'</a> ';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_edit($name = '', $link = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!$name) {
			$name = 'Edit';
		}
		$r = $replace ? $replace : $this->_replace;
		if (!$link) {
			$link = 'edit_link';
			if (!isset($r['edit_link']) && isset($r['edit_url'])) {
				$link = 'edit_url';
			}
		}
		$link_url = isset($r[$link]) ? $r[$link] : $link;
		$body = ' <a href="'.$link_url.'" class="btn btn-mini ajax_edit"><i class="icon-edit"></i> '.t($name).'</a> ';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_delete($name = '', $link = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!$name) {
			$name = 'Delete';
		}
		$r = $replace ? $replace : $this->_replace;
		if (!$link) {
			$link = 'delete_link';
			if (!isset($r['delete_link']) && isset($r['delete_url'])) {
				$link = 'delete_url';
			}
		}
		$link_url = isset($r[$link]) ? $r[$link] : $link;
//		$body = ' <a href="'.$link_url.'" class="btn btn-mini ajax_delete" onclick="return confirm(\''.t('Are you sure').'?\');"><i class="icon-trash"></i> '.t($name).'</a> ';
		$body = ' <a href="'.$link_url.'" class="btn btn-mini ajax_delete"><i class="icon-trash"></i> '.t($name).'</a> ';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_clone($name = '', $link = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!$name) {
			$name = 'Clone';
		}
		$r = $replace ? $replace : $this->_replace;
		if (!$link) {
			$link = 'clone_link';
			if (!isset($r['clone_link']) && isset($r['clone_url'])) {
				$link = 'clone_url';
			}
		}
		$link_url = isset($r[$link]) ? $r[$link] : $link;
		$body = ' <a href="'.$link_url.'" class="btn btn-mini ajax_clone"><i class="icon-plus"></i> '.t($name).'</a> ';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_active($name = '', $link = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!$name) {
			$name = 'active';
		}
		$r = $replace ? $replace : $this->_replace;
		if (!$link) {
			$link = 'active_link';
			if (!isset($r['active_link']) && isset($r['active_url'])) {
				$link = 'active_url';
			}
		}
		$link_url = isset($r[$link]) ? $r[$link] : $link;
		$is_active = $r[$name];
		$body = ' <a href="'.$link_url.'" class="change_active">'
			.($is_active ? '<span class="label label-success">'.t('Active').'</span>' : '<span class="label label-warning">'.t('Disabled').'</span>')
			.'</a> ';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function birth_box($name, $values, $extra = array(), $replace = array()) {
// TODO: customize for birth input needs
		return $this->date_box($name, $values, $extra, $replace);
	}

	/**
	*/
	function country_box($name, $desc = '', $extra = array(), $replace = array()) {
// TODO: nice select box with data
	}

	/**
	*/
	function currency_box($name, $desc = '', $extra = array(), $replace = array()) {
// TODO: nice select box with data
	}

	/**
	*/
	function language_box($name, $desc = '', $extra = array(), $replace = array()) {
// TODO: nice select box with data
	}

	/**
	*/
	function timezone_box($name, $desc = '', $extra = array(), $replace = array()) {
// TODO: nice select box with data
	}

// TODO: add support to these HTML5 controls: color, date, datetime, datetime-local, month, range, search, tel, time, week
// http://www.w3schools.com/html/html5_form_input_types.asp

	/**
	* Image upload
	*/
	function image($name, $desc = '', $extra = array(), $replace = array()) {
// TODO: show already uploaded image, link to delete it, input to upload new
	}
}
