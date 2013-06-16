<?php

/**
* Form2
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

	function tpl_row($type = "input", $replace = array(), $name, $desc = "", $more_param = "") {
// TODO: integrate with named errors
#		$errors = array();
		$desc = t($desc);
		if ($type == "text") {
			return '
			<div class="control-group '.(isset($errors[$name]) ? 'error' : '').'">
				<label class="control-label" for="'.$name.'">'.$desc.'</label>
				<div class="controls"><input type="text" id="'.$name.'" name="'.$name.'" placeholder="'.$desc.'" value="'.htmlspecialchars($replace[$name], ENT_QUOTES).'">'
					.(isset($errors[$name]) ? '<span class="help-inline">'.$errors[$name].'</span>' : '')
				.'</div>
			</div>
			';
		} elseif ($type == "textarea") {
			return '
			<div class="control-group '.(isset($errors[$name]) ? 'error' : '').'">
				<label class="control-label" for="'.$name.'">'.$desc.'</label>
				<div class="controls"><textarea id="'.$name.'" name="'.$name.'" placeholder="'.$desc.'">'.htmlspecialchars($replace[$name], ENT_QUOTES).'</textarea>'
					.(isset($errors[$name]) ? '<span class="help-inline">'.$errors[$name].'</span>' : '')
				.'</div>
			</div>
			';
		} elseif ($type == "active") {
			$_statuses = array(
				'0' => '<span class="label label-warning">'.t('Disabled').'</span>', 
				'1' => '<span class="label label-success">'.t('Active').'</span>',
			);
			$selected = $replace[$name];
			return '<div class="control-group '.(isset($errors[$name]) ? 'error' : '').'">
				<label class="control-label" for="'.$name.'">'.$desc.'</label>
				<div class="controls">'
					.common()->radio_box($name, $_statuses, $selected, false, 2, "", false)
					.(isset($errors[$name]) ? '<span class="help-inline">'.$errors[$name].'</span>' : '')
				.'</div>
			</div>
			';
		} elseif ($type == "save") {
			return '<div class="control-group">
				<div class="controls">
					<input type="submit" value="'.t('Save').'" class="btn" />'
				.'</div>
			</div>
			';
		} elseif ($type == "save_and_back") {
			return '<div class="control-group">
				<div class="controls">
					<input type="submit" value="'.t('Save').'" class="btn" />'
					.($replace['back_link'] ? ' <a href="'.$replace['back_link'].'" class="btn">'.t('Back').'</a>' : '')
				.'</div>
			</div>
			';
		} elseif ($type == "info") {
			return '<div class="control-group">
				<label class="control-label">'.t($desc).'</label>
				<div class="controls"><span class="label label-info">'.htmlspecialchars($replace[$name], ENT_QUOTES).'</span></div>
			</div>
			';
		} elseif ($type == "box") {
			return '<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.$replace[$name].'</div>
			</div>
			';
		} elseif ($type == "box_with_link") {
			$edit_link = $replace[$more_param];
			return '<div class="control-group">
				<label class="control-label" for="group_box">'.t($desc).'</label>
				<div class="controls">'.$replace[$name].' <a href="'.$edit_link.'"><i class="icon-edit"></i></a></div>
			</div>
			';
		} elseif ($type == "tbl_link") {
			return 'TODO';
		} elseif ($type == "tbl_link_edit") {
			return 'TODO';
		} elseif ($type == "tbl_link_del") {
			return 'TODO';
		} elseif ($type == "tbl_link_active") {
			return 'TODO';
		} elseif ($type == "form_begin") {
			return '<form method="post" action="'.$replace['form_action'].'" class="form-horizontal">';
		} elseif ($type == "form_end") {
			return '</form>';
		}
	}
}
