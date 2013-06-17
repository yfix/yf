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
	function tpl_row($type = "input", $replace = array(), $name, $desc = "", $more_param = "") {
// TODO: integrate with named errors
#		$errors = array();
		return $this->$type($name, $desc, $more_param, $replace);
	}

	/**
	*/
	function text($name, $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$body = '
			<div class="control-group '.(isset($errors[$name]) ? 'error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls"><input type="text" id="'.$name.'" name="'.$name.'" placeholder="'.t($desc).'" value="'.htmlspecialchars($replace[$name], ENT_QUOTES).'">'
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
	function textarea($name, $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$body = '
			<div class="control-group '.(isset($errors[$name]) ? 'error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls"><textarea id="'.$name.'" name="'.$name.'" placeholder="'.t($desc).'">'.htmlspecialchars($replace[$name], ENT_QUOTES).'</textarea>'
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
	function active_box($name = "", $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
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
		$body = '<div class="control-group '.(isset($errors[$name]) ? 'error' : '').'">
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
	function save($name = "", $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		$body = '<div class="control-group">
				<div class="controls">
					<input type="submit" value="'.t('Save').'" class="btn" />'
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
	function save_and_back($name = "", $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!$name) {
			$name = 'back_link';
		}
		$body = '<div class="control-group">
				<div class="controls">
					<input type="submit" value="'.t('Save').'" class="btn" />'
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
	function info($name, $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$body = '<div class="control-group">
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
	function box($name, $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		$body = '<div class="control-group">
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
	function box_with_link($name, $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		$edit_link = $replace[$more_param];
		$body = '<div class="control-group">
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
	*/
	function form_begin($name = "", $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!$name) {
			$name = "form_action";
		}
		$body = '<form method="post" action="'.$replace[$name].'" class="form-horizontal">';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function form_end($name = "", $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
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
	function tbl_link($name, $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		$body = 'TODO';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function tbl_link_edit($name, $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		$body = 'TODO';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function tbl_link_del($name, $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		$body = 'TODO';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function tbl_link_active($name = "", $desc = "", $more_param = "", $replace = array()) {
		if ($this->_chained_mode) {
			$replace = $this->_replace;
		}
		if (!$name) {
			$name = "active_link";
		}
		$active_link = $replace[$name];
		$body = 'TODO';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}
}
