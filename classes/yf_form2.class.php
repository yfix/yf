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
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* We cleanup object properties when cloning
	*/
	function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	* Need to avoid calling render() without params
	*/
	function __toString() {
		return $this->render();
	}

	/**
	* Wrapper for template engine
	* Example:
	*	return form2($replace)
	*		->text('login','Login')
	*		->text('password','Password')
	*		->text('first_name','First Name')
	*		->text('last_name','Last Name')
	*		->text('go_after_login','Url after login')
	*		->box_with_link('group_box','Group','groups_link')
	*		->active('active','Active')
	*		->info('add_date','Added');
	*/
	function chained_wrapper($replace = array(), $params = array()) {
		$this->_chained_mode = true;
		$this->_replace = $replace;
		$this->_params = $params;
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
	*	{form_row('form_end')}
	*/
	function tpl_row($type = 'input', $replace = array(), $name, $desc = '', $extra = array()) {
		return $this->$type($name, $desc, $extra, $replace);
	}

	/**
	* Enable automatic fields parsing mode
	*/
	function auto($table, $id, $params = array()) {
		if ($params['links_add']) {
			$this->_params['links_add'] = $params['links_add'];
		}
		$columns = db()->meta_columns($table);
		$info = db()->get('SELECT * FROM '.db()->es($table).' WHERE id='.intval($id));
		foreach ((array)$info as $k => $v) {
			$this->_replace[$k] = $v;
		}
		foreach((array)$columns as $name => $details) {
			$type = strtoupper($details['type']);
			if (strpos($type, 'TEXT') !== false) {
				$this->textarea($name);
			} else {
				$this->text($name);
			}
		}
		$this->save_and_back();
		return $this;
	}

	/**
	* Render result form html, gathered by row functions
	* Params here not required, but if provided - will be passed to form_begin()
	*/
	function render($extra = array(), $replace = array()) {
		// Call these methods, if not done yet, save 2 api calls
		if (!isset($this->_body['form_begin'])) {
			$this->form_begin('', '', $extra, $replace);
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
	*/
	function form_begin($name = '', $method = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		// Merge params passed to table2() and params passed here, with params here have more priority:
		$tmp = $this->_params;
		foreach ((array)$extra as $k => $v) {
			$tmp[$k] = $v;
		}
		$extra = $tmp;
		unset($tmp);

		if (!$name) {
			$name = $extra['name'] ? $extra['name']: 'form_action';
		}
		if (!$method) {
			$method = $extra['method'] ? $extra['method']: 'post';
		}
		$enctype = '';
		if ($extra['enctype']) {
			$enctype = $extra['enctype'];
		} elseif ($extra['for_upload']) {
			$enctype = 'multipart/form-data';
		}
		$r = $replace ? $replace : $this->_replace;
		$form_action = isset($r[$name]) ? $r[$name] : './?object='.$_GET['object'].'&action='.$_GET['action']. ($_GET['id'] ? '&id='.$_GET['id'] : ''). $this->_params['links_add'];
		$form_class = $extra['class'] ? $extra['class'] : 'form-horizontal';
		$body = '<form method="'.$method.'" action="'.$form_action.'" class="'.$form_class.'"'
			.($extra['style'] ? ' style="'.$extra['style'].'"' : '')
			.($extra['id'] ? ' id="'.$extra['id'].'"' : '')
			.($extra['name'] ? ' name="'.$extra['name'].'"' : '')
			.($extra['autocomplete'] ? ' autocomplete="'.$extra['autocomplete'].'"' : '')			
			.($extra['enctype'] ? ' enctype="'.$extra['enctype'].'"' : '')
			.($extra['attr'] ? ' '.$this->_prepare_custom_attr($extra['attr']) : '')
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
			$replace = (array)$this->_replace + (array)$replace;
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
			$replace = (array)$this->_replace + (array)$replace;
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		if (!$desc) {
			$desc = ucfirst(str_replace('_', ' ', $name));
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$id = $extra['id'] ? $extra['id'] : $name;
		$placeholder = isset($extra['placeholder']) ? $extra['placeholder'] : $desc;
		$value = isset($extra['value']) ? $extra['value'] : $r[$name];
		// Compatibility with filter
		if (!strlen($value)) {
			if (isset($extra['selected'])) {
				$value = $extra['selected'];
			} elseif (isset($this->_params['selected'])) {
				$value = $this->_params['selected'][$name];
			}
		}
		$input_type = isset($extra['type']) ? $extra['type'] : 'text';
		$edit_link = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		$prepend = $extra['prepend'] ? $extra['prepend'] : '';
		$append = $extra['append'] ? $extra['append'] : '';
		$css_class = $this->_prepare_css_class('', $r[$name], $extra);
		// Supported: mini, small, medium, large, xlarge, xxlarge
		if ($extra['sizing']) {
			$extra['class'] .= ' input-'.$extra['sizing'];
		}
		if (!isset($extra['no_escape'])) {
			$value = htmlspecialchars($value, ENT_QUOTES);
		}
		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$id.'">'.t($desc).'</label>
				<div class="controls">'
					.(($prepend || $append) ? '<div class="'.($prepend ? 'input-prepend' : '').($append ? ' input-append' : '').'">' : '')
					.($prepend ? '<span class="add-on">'.$prepend.'</span>' : '')
					.'<input type="'.$input_type.'" id="'.$id.'" name="'.$name.'" placeholder="'.t($placeholder).'" value="'.$value.'"'
					.($css_class ? ' class="'.$css_class.'"' : '')
					.($extra['style'] ? ' style="'.$extra['style'].'"' : '')
					.($extra['data'] ? ' data="'.$extra['data'].'"' : '')
					.($extra['size'] ? ' size="'.$extra['size'].'"' : '')
					.($extra['maxlength'] ? ' maxlength="'.$extra['maxlength'].'"' : '')
					.($extra['disabled'] ? ' disabled' : '')
					.($extra['required'] ? ' required' : '')
					.($extra['autocomplete'] ? ' autocomplete="'.$extra['autocomplete'].'"' : '')
					.($extra['attr'] ? ' '.$this->_prepare_custom_attr($extra['attr']) : '')
					.'>'
					.($append ? '<span class="add-on">'.$append.'</span>' : '')
					.(($prepend || $append) ? '</div>' : '')
					.($edit_link ? ' <a href="'.$edit_link.'" class="btn btn-mini"><i class="icon-edit"></i> '.t('Edit').'</a>' : '')
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
					.(isset($extra['ckeditor']) ? $this->_ckeditor_html($extra, $replace) : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function textarea($name, $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		if (!$desc) {
			$desc = ucfirst(str_replace('_', ' ', $name));
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$id = $extra['id'] ? $extra['id'] : $name;
		$placeholder = isset($extra['placeholder']) ? $extra['placeholder'] : $desc;
		$value = isset($extra['value']) ? $extra['value'] : $r[$name];
		// Compatibility with filter
		if (!strlen($value)) {
			if (isset($extra['selected'])) {
				$value = $extra['selected'];
			} elseif (isset($this->_params['selected'])) {
				$value = $this->_params['selected'][$name];
			}
		}
		$edit_link = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		if (!isset($extra['no_escape'])) {
			$value = htmlspecialchars($value, ENT_QUOTES);
		}
		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$id.'">'.t($desc).'</label>
				<div class="controls">
					<textarea id="'.$id.'" name="'.$name.'" placeholder="'.t($placeholder).'" contenteditable="true"'
					.($extra['cols'] ? ' cols="'.$extra['cols'].'"' : '')
					.($extra['rows'] ? ' rows="'.$extra['rows'].'"' : '')
					.' class="ckeditor '.$this->_prepare_css_class('', $r[$name], $extra).'"'
					.($extra['style'] ? ' style="'.$extra['style'].'"' : '')
					.($extra['data'] ? ' data="'.$extra['data'].'"' : '')
					.($extra['attr'] ? ' '.$this->_prepare_custom_attr($extra['attr']) : '')
					.'>'.$value.'</textarea>'
					.($edit_link ? ' <a href="'.$edit_link.'" class="btn btn-mini"><i class="icon-edit"></i> '.t('Edit').'</a>' : '')
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
					.(isset($extra['ckeditor']) ? $this->_ckeditor_html($extra, $replace) : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function _prepare_custom_attr($attr = array()) {
		$body = array();
		foreach ((array)$attr as $k => $v) {
			$body[] = '"'.htmlspecialchars($k, ENT_QUOTES).'"="'.htmlspecialchars($v, ENT_QUOTES).'"';
		}
		return implode(" ", $body);
	}

	/**
	*/
	function _show_tip($value = '', $extra = array(), $replace = array()) {
		return _class('graphics')->_show_help_tip(array(
			'tip_id'	=> $value,
			'replace'	=> $replace,
		));
	}

	/**
	*/
	function _prepare_css_class($default_class = '', $value = '', $extra = array()) {
		$css_class = $default_class;
		if ($extra['badge']) {
			$badge = is_array($extra['badge']) && isset($extra['badge'][$value]) ? $extra['badge'][$value] : $extra['badge'];
			if ($badge) {
				$css_class = 'badge badge-'.$badge;
			}
		} elseif ($extra['label']) {
			$label = is_array($extra['label']) && isset($extra['label'][$value]) ? $extra['label'][$value] : $extra['label'];
			if ($label) {
				$css_class = 'label label-'.$label;
			}
		} elseif ($extra['class']) {
			$_css_class = is_array($extra['class']) && isset($extra['class'][$value]) ? $extra['class'][$value] : $extra['class'];
			if ($_css_class) {
				$css_class = $_css_class;
			}
		}
		return $css_class;
	}

	/**
	* Embedding ckeditor (http://ckeditor.com/) with kcfinder (http://kcfinder.sunhater.com/).
	* Best way to include it into project: 
	*
	* git submodule add https://github.com/ckeditor/ckeditor-releases.git www/ckeditor/ && cd www/ckeditor/ && git checkout latest/full
	* git submodule add git@github.com:yfix/yf_kcfinder.git www/kcfinder
	* 
	* 'www/' usually means PROJECT_PATH inside project working copy.
	* P.S. You can use free CDN for ckeditor as alternate solution: <script src="//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.0.1/ckeditor.js"></script>
	*/
	function _ckeditor_html($extra = array(), $replace = array()) {
		if (!is_array($extra)) {
			return '';
		}
		$params = $extra['ckeditor'];
		if (!is_array($params)) {
			$params = array();
		}
		if ($this->_ckeditor_scripts_included) {
			return '';
		}
		$ck_path = $params['ck_path'] ? $params['ck_path'] : 'ckeditor/ckeditor.js';
		$fs_ck_path = PROJECT_PATH. $ck_path;
		$web_ck_path = WEB_PATH. $ck_path;
		if (!file_exists($fs_ck_path)) {
			return '';
		}
		$content_id = $extra['id'] ? $extra['id'] : 'content_editable';
		$hidden_id = $params['hidden_id'] ? $params['hidden_id'] : '';

		$body .= '<script type="text/javascript">
			$(function(){
				var _content_id = "#'.$content_id.'";
				var _hidden_id = "#'.$hidden_id.'";
				$(_content_id).parents("form").submit(function(){
					$("input[type=hidden]" + _hidden_id).val( $(_content_id).html() );
				})
			})
			</script>';

		// Main ckeditor script
		$body .= '<script src="'.$web_ck_path.'" type="text/javascript"></script>'.PHP_EOL;

		// Theme-wide ckeditor config inside stpl (so any engine vars can be processed or included there)
		$stpl_name = 'ckeditor_config'; // Example filesystem location: PROJECT_PATH.'templates/admin/ckeditor_config.stpl'
		if (!isset($replace['content_id'])) {
			$replace['content_id'] = $content_id;
		}
		$body .= tpl()->_stpl_exists($stpl_name) ? tpl()->parse($stpl_name, my_array_merge($extra, $replace)) : '';

		// Avoid including ckeditor scripts several times on same page
		$this->_ckeditor_scripts_included = true;

		return $body;
	}

	/**
	* Bootstrap-compatible html wrapper for any custom content inside.
	* Can be used for inline rich editor editing with ckeditor, enable with: $extra = array('ckeditor' => true)
	*/
	function container($text, $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		$text = strval($text);
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		$edit_link = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
		$content_id = $extra['id'] ? $extra['id'] : 'content_editable';
		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">'
				.($desc ? '<label class="control-label">'.t($desc).'</label>' : '')
				.(isset($extra['ckeditor']) ? '<div contenteditable="true" id="'.$content_id.'">' : '')
				.(!$extra['wide'] ? '<div class="controls">'.$text.'</div>' : $text)
				.(isset($extra['ckeditor']) ? '</div>' : '')
				.($edit_link ? ' <a href="'.$edit_link.'" class="btn btn-mini"><i class="icon-edit"></i> '.t('Edit').'</a>' : '')
				.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
				.(isset($extra['ckeditor']) ? $this->_ckeditor_html($extra, $replace) : '')
			.'</div>
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
			$replace = (array)$this->_replace + (array)$replace;
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		$id = $extra['id'] ? $extra['id'] : $name;
		$value = isset($extra['value']) ? $extra['value'] : $r[$name];
		if (!isset($extra['no_escape'])) {
			$value = htmlspecialchars($value, ENT_QUOTES);
		}
		$body = '<input type="hidden" id="'.$id.'" name="'.$name.'" value="'.$value.'"'.($extra['data'] ? ' data="'.$extra['data'].'"' : '').'>';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function text($name, $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		$extra['type'] = 'text';
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	*/
	function password($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		$extra['type'] = 'password';
		$extra['prepend'] = '<i class="icon-key"></i>';
		if (!$name) {
			$name = 'password';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	*/
	function file($name, $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		$extra['type'] = 'file';
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* Custom
	*/
	function login($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		$extra['type'] = 'text';
		$extra['prepend'] = '<i class="icon-user"></i>';
		if (!$name) {
			$name = 'login';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function email($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		$extra['type'] = 'email';
		$extra['prepend'] = '@';
		if (!$name) {
			$name = 'email';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function number($name, $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		if ($extra['min']) {
			$extra['attr']['min'] = $extra['min'];
			unset($extra['min']);
		}
		if ($extra['max']) {
			$extra['attr']['max'] = $extra['max'];
			unset($extra['max']);
		}
		if ($extra['step']) {
			$extra['attr']['step'] = $extra['step'];
			unset($extra['step']);
		}
		$extra['type'] = 'number';
		if (!isset($extra['sizing'])) { $extra['sizing'] = 'small'; }
		if (!isset($extra['maxlength'])) { $extra['maxlength'] = '10'; }
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
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		$extra['type'] = 'text';
		if (!isset($extra['prepend'])) { $extra['prepend'] = '$'; }
		if (!isset($extra['append'])) { $extra['append'] = '.00'; }
		if (!isset($extra['sizing'])) { $extra['sizing'] = 'small'; }
		if (!isset($extra['maxlength'])) { $extra['maxlength'] = '8'; }
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function url($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		$extra['type'] = 'url';
		$extra['prepend'] = 'url';
		if (!$name) {
			$name = 'url';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function color($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = 'color';
		if (!$name) {
			$name = 'color';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function date($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = 'date';
		if (!$name) {
			$name = 'date';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function datetime($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = 'datetime';
		if (!$name) {
			$name = 'datetime';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function datetime_local($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = 'datetime-local';
		if (!$name) {
			$name = 'datetime_local';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function month($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = 'month';
		if (!$name) {
			$name = 'month';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function range($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = 'range';
		if (!$name) {
			$name = 'range';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function search($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = 'search';
		if (!$name) {
			$name = 'search';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function tel($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = 'tel';
		if (!$name) {
			$name = 'tel';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function time($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = 'time';
		if (!$name) {
			$name = 'time';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function week($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = 'week';
		if (!$name) {
			$name = 'week';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	*/
	function active_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$name) {
			$name = 'active';
		}
		if (!$desc) {
			$desc = ucfirst(str_replace('_', ' ', $name));
		}
		if (!$extra['items']) {
			$extra['items'] = array(
				'0' => '<span class="label label-warning">'.t('Disabled').'</span>',
				'1' => '<span class="label label-success">'.t('Active').'</span>',
			);
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		$selected = $r[$name];
		if (isset($extra['selected'])) {
			$selected = $extra['selected'];
		} elseif (isset($this->_params['selected'])) {
			$selected = $this->_params['selected'][$name];
		}
		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">'
					.common()->radio_box($name, $extra['items'], $selected, false, 2, '', false)
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function allow_deny_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['items'] = array(
			'DENY' => '<span class="label label-warning">'.t('Deny').'</span>', 
			'ALLOW' => '<span class="label label-success">'.t('Allow').'</span>',
		);
		return $this->active_box($name, $desc, $extra, $replace);
	}

	/**
	*/
	function yes_no_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
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
			$replace = (array)$this->_replace + (array)$replace;
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$id = $extra['id'] ? $extra['id'] : $name;
		$value = isset($extra['value']) ? $extra['value'] : 'Save';
		$link_url = $extra['link_url'] ? (isset($r[$extra['link_url']]) ? $r[$extra['link_url']] : '') : '';
		if (preg_match('~^[a-z0-9_-]+$~ims', $link_url)) {
			$link_url = '';
		}
		$link_name = $extra['link_name'] ? $extra['link_name'] : '';
		$css_class = $this->_prepare_css_class('', $r[$name], $extra);
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];

		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<div class="controls">
					<input type="submit" value="'.t($value).'" class="btn btn-primary'.($extra['class'] ? ' '.$extra['class'] : '').'"'
					.($extra['style'] ? ' style="'.$extra['style'].'"' : '')
					.($extra['data'] ? ' data="'.$extra['data'].'"' : '')
					.($css_class ? ' class="'.$css_class.'"' : '')
					.($extra['attr'] ? ' '.$this->_prepare_custom_attr($extra['attr']) : '')
					.'>'
					.($link_url ? ' <a href="'.$link_url.'" class="btn">'.t($link_name).'</a>' : '')
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
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
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
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
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
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
			$replace = (array)$this->_replace + (array)$replace;
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		if (!$desc) {
			$desc = ucfirst(str_replace('_', ' ', $name));
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$value = $r[$name];
		if (is_array($extra['data'])) {
			if (isset($extra['data'][$value])) {
				$value = $extra['data'][$value];
			} elseif (isset($extra['data'][$name])) {
				$value = $extra['data'][$name];
			}
		}
		if (!isset($extra['no_escape'])) {
			$value = htmlspecialchars($value, ENT_QUOTES);
		}
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];

		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">'
				.(!$extra['no_label'] ? '<label class="control-label">'.t($desc).'</label>' : '')
				.'<div class="controls">'
					.($extra['link'] ? '<a href="'.$extra['link'].'" class="btn btn-mini">'.$value.'</a>' : '<span class="'.$this->_prepare_css_class('label label-info', $r[$name], $extra).'">'.$value.'</span>')
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
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
	function user_info($name = '', $desc = '', $extra = array(), $replace = array()) {
		$name = 'user_name';
		$user_id = $this->_replace['user_id'];
		$this->_replace[$name] = db()->get_one('SELECT CONCAT(login," ",email) AS user_name FROM '.db('user').' WHERE id='.intval($user_id));
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['link'] = './?object=members&action=edit&id='.$user_id;
		return $this->info($name, $desc, $extra, $replace);
	}

	/**
	*/
	function link($name = '', $link = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$replace[$name] = $name;
		$extra['link'] = $link;
		$extra['no_label'] = 1;
		return $this->info($name, $desc, $extra, $replace);
	}

	/**
	*/
	function box($name, $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$edit_link = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];

		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">'
					.$r[$name]
					.($edit_link ? ' <a href="'.$edit_link.'" class="btn btn-mini"><i class="icon-edit"></i> '.t('Edit').'</a>' : '')
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function box_with_link($name, $desc = '', $link = '', $replace = array()) {
		return $this->box($name, $desc, array('edit_link' => $link), $replace);
	}

	/**
	*/
	function select_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		if (!$extra['no_translate']) {
			$values = t($values);
		}
		$selected = $r[$name];
		if (isset($extra['selected'])) {
			$selected = $extra['selected'];
		} elseif (isset($this->_params['selected'])) {
			$selected = $this->_params['selected'][$name];
		}
		$desc = isset($extra['desc']) ? $extra['desc'] : ucfirst(str_replace('_', ' ', $name));
		$show_text = isset($extra['show_text']) ? $extra['show_text'] : 1;
		$type = isset($extra['type']) ? $extra['type'] : 2;
		$translate = isset($extra['translate']) ? $extra['translate'] : 0;
		$level = isset($extra['level']) ? $extra['level'] : 0;
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		$add_str = isset($extra['add_str']) ? $extra['add_str'] : '';
		if ($extra['class']) {
			$add_str .= ' class="'.$extra['class'].'" ';
		}
		if ($extra['style']) {
			$add_str .= ' style="'.$extra['style'].'" ';
		}

		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">'
					.common()->select_box($name, $values, $selected, $show_text, $type, $add_str, $translate)
					.($extra['edit_link'] ? ' <a href="'.$extra['edit_link'].'" class="btn btn-mini"><i class="icon-edit"></i> '.t('Edit').'</a>' : '')
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function multi_select_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		if (!$extra['no_translate']) {
			$values = t($values);
		}
		$selected = $r[$name];
		if (isset($extra['selected'])) {
			$selected = $extra['selected'];
		} elseif (isset($this->_params['selected'])) {
			$selected = $this->_params['selected'][$name];
		}
		$desc = isset($extra['desc']) ? $extra['desc'] : ucfirst(str_replace('_', ' ', $name));
		$show_text = isset($extra['show_text']) ? $extra['show_text'] : 1;
		$type = isset($extra['type']) ? $extra['type'] : 2;
		$translate = isset($extra['translate']) ? $extra['translate'] : 0;
		$level = isset($extra['level']) ? $extra['level'] : 0;
		$disabled = isset($extra['disabled']) ? $extra['disabled'] : false;
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		$add_str = isset($extra['add_str']) ? $extra['add_str'] : '';
		if ($extra['class']) {
			$add_str .= ' class="'.$extra['class'].'" ';
		}
		if ($extra['style']) {
			$add_str .= ' style="'.$extra['style'].'" ';
		}

		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">'
					.common()->multi_select_box($name, $values, $selected, $show_text, $type, $add_str, $translate, $level, $disabled)
					.($extra['edit_link'] ? ' <a href="'.$extra['edit_link'].'" class="btn btn-mini"><i class="icon-edit"></i> '.t('Edit').'</a>' : '')
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function check_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		if (!is_array($extra)) {
			if (!empty($extra) && is_string($extra)) {
				$desc = $extra;
			}
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		if (!$extra['no_translate']) {
			$values = t($values);
		}
		$selected = $r[$name];
		if (isset($extra['selected'])) {
			$selected = $extra['selected'];
		} elseif (isset($this->_params['selected'])) {
			$selected = $this->_params['selected'][$name];
		}
		$desc = isset($extra['desc']) ? $extra['desc'] : ucfirst(str_replace('_', ' ', $name));
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		$add_str = isset($extra['add_str']) ? $extra['add_str'] : '';
		if ($extra['class']) {
			$add_str .= ' class="'.$extra['class'].'" ';
		}
		if ($extra['style']) {
			$add_str .= ' style="'.$extra['style'].'" ';
		}
		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">'
					.common()->check_box($name, $values, $selected, $add_str)
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function multi_check_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		if (!$extra['no_translate']) {
			$values = t($values);
		}
		$selected = $r[$name];
		if (isset($extra['selected'])) {
			$selected = $extra['selected'];
		} elseif (isset($this->_params['selected'])) {
			$selected = $this->_params['selected'][$name];
		}
		$desc = isset($extra['desc']) ? $extra['desc'] : ucfirst(str_replace('_', ' ', $name));
		$type = isset($extra['type']) ? $extra['type'] : 2;
		$translate = isset($extra['translate']) ? $extra['translate'] : 0;
		$flow_vertical = isset($extra['flow_vertical']) ? $extra['flow_vertical'] : false;
		$name_as_array = isset($extra['name_as_array']) ? $extra['name_as_array'] : false;
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		$add_str = isset($extra['add_str']) ? $extra['add_str'] : '';
		if ($extra['class']) {
			$add_str .= ' class="'.$extra['class'].'" ';
		}
		if ($extra['style']) {
			$add_str .= ' style="'.$extra['style'].'" ';
		}
		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">'
					.common()->multi_check_box($name, $values, $selected, $flow_vertical, $type, $add_str, $translate, $name_as_array)
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function radio_box($name, $values, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		if (!$extra['no_translate']) {
			$values = t($values);
		}
		$selected = $r[$name];
		if (isset($extra['selected'])) {
			$selected = $extra['selected'];
		} elseif (isset($this->_params['selected'])) {
			$selected = $this->_params['selected'][$name];
		}
		$desc = isset($extra['desc']) ? $extra['desc'] : ucfirst(str_replace('_', ' ', $name));
		$type = isset($extra['type']) ? $extra['type'] : 2;
		$translate = isset($extra['translate']) ? $extra['translate'] : 0;
		$flow_vertical = isset($extra['flow_vertical']) ? $extra['flow_vertical'] : false;
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		$add_str = isset($extra['add_str']) ? $extra['add_str'] : '';
		if ($extra['class']) {
			$add_str .= ' class="'.$extra['class'].'" ';
		}
		if ($extra['style']) {
			$add_str .= ' style="'.$extra['style'].'" ';
		}
		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">'
					.common()->radio_box($name, $values, $selected, $flow_vertical, $type, $add_str, $translate)
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function date_box($name, $values = array(), $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
//		if (!$values) {
//			$values = array();
//		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		$selected = $r[$name];
		if (isset($extra['selected'])) {
			$selected = $extra['selected'];
		} elseif (isset($this->_params['selected'])) {
			$selected = $this->_params['selected'][$name];
		}
		$desc = isset($extra['desc']) ? $extra['desc'] : ucfirst(str_replace('_', ' ', $name));
		$years = isset($extra['years']) ? $extra['years'] : '';
		$show_what = isset($extra['show_what']) ? $extra['show_what'] : 'ymd';
		$show_text = isset($extra['show_text']) ? $extra['show_text'] : 1;
		$translate = isset($extra['translate']) ? $extra['translate'] : 1;
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		$add_str = isset($extra['add_str']) ? $extra['add_str'] : '';
		if ($extra['class']) {
			$add_str .= ' class="'.$extra['class'].'" ';
		}
		if ($extra['style']) {
			$add_str .= ' style="'.$extra['style'].'" ';
		}

		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">'
					.common()->date_box2($name, $selected, $years, $add_str, $show_what, $show_text, $translate)
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function time_box($name, $values = array(), $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		$selected = $r[$name];
		if (isset($extra['selected'])) {
			$selected = $extra['selected'];
		} elseif (isset($this->_params['selected'])) {
			$selected = $this->_params['selected'][$name];
		}
		$desc = isset($extra['desc']) ? $extra['desc'] : ucfirst(str_replace('_', ' ', $name));
		$show_text = isset($extra['show_text']) ? $extra['show_text'] : 1;
		$translate = isset($extra['translate']) ? $extra['translate'] : 1;
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		$add_str = isset($extra['add_str']) ? $extra['add_str'] : '';
		if ($extra['class']) {
			$add_str .= ' class="'.$extra['class'].'" ';
		}
		if ($extra['style']) {
			$add_str .= ' style="'.$extra['style'].'" ';
		}

		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">'
					.common()->time_box2($name, $selected, $add_str, $show_text, $translate)
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function datetime_box($name, $values = array(), $extra = array(), $replace = array()) {
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
			$replace = (array)$this->_replace + (array)$replace;
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$r = $replace ? $replace : $this->_replace;
		if (!$link && $extra['link_variants']) {
			foreach((array)$extra['link_variants'] as $link_variant) {
				if (isset($r[$link_variant])) {
					$link = $link_variant;
				}
			}
		}
		$link_url = isset($r[$link]) ? $r[$link] : $link;
		$icon = $extra['icon'] ? $extra['icon']: 'icon-tasks';
		$body = ' <a href="'.$link_url.'" class="btn btn-mini'.($extra['class'] ? ' '.$extra['class'] : '').'"><i class="'.$icon.'"></i> '.t($name).'</a> ';
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
		if (!$name) {
			$name = 'Edit';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['link_variants'] = array('edit_link','edit_url');
		$extra['icon'] = 'icon-edit';
		$extra['class'] = 'ajax_edit';
		return $this->tbl_link($name, $link, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_delete($name = '', $link = '', $extra = array(), $replace = array()) {
		if (!$name) {
			$name = 'Delete';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['link_variants'] = array('delete_link','delete_url');
		$extra['icon'] = 'icon-trash';
		$extra['class'] = 'ajax_delete';
		return $this->tbl_link($name, $link, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_clone($name = '', $link = '', $extra = array(), $replace = array()) {
		if (!$name) {
			$name = 'Clone';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['link_variants'] = array('clone_link','clone_url');
		$extra['icon'] = 'icon-plus';
		$extra['class'] = 'ajax_clone';
		return $this->tbl_link($name, $link, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_view($name = '', $link = '', $extra = array(), $replace = array()) {
		if (!$name) {
			$name = 'View';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['link_variants'] = array('view_link','view_url');
		$extra['icon'] = 'icon-eye-open';
		$extra['class'] = 'ajax_view';
		return $this->tbl_link($name, $link, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_active($name = '', $link = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		if (!$name) {
			$name = 'active';
		}
		if (!is_array($extra)) {
			$extra = array();
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
	function birth_box($name = '', $values = array(), $extra = array(), $replace = array()) {
		if (!$name) {
			$name = 'birth';
		}
// TODO: customize for birth input needs
		return $this->date_box($name, $values, $extra, $replace);
	}

	/**
	*/
	function country_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (!$name) {
			$name = 'country';
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$countries = main()->get_data('countries');
		return $this->select_box($name, $countries, $extra, $replace);
	}

	/**
	*/
	function region_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (!$name) {
			$name = 'region';
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$regions = main()->get_data('regions');
		return $this->select_box($name, $regions, $extra, $replace);
	}

	/**
	*/
	function currency_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (!$name) {
			$name = 'currency';
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$currencies = main()->get_data('currencies');
		return $this->select_box($name, $currencies, $extra, $replace);
	}

	/**
	*/
	function language_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (!$name) {
			$name = 'language';
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$languages = main()->get_data('languages');
		return $this->select_box($name, $languages, $extra, $replace);
	}

	/**
	*/
	function timezone_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (!$name) {
			$name = 'timezone';
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$timezones = main()->get_data('timezones');
		return $this->select_box($name, $timezones, $extra, $replace);
	}

	/**
	* Image upload
	*/
	function image($name, $desc = '', $extra = array(), $replace = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
// TODO: show already uploaded image, link to delete it, input to upload new
		return $this;
	}

	/**
	*/
	function method_select_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		if (!$name) {
			$name = 'method_name';
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$desc) {
			$desc = 'Custom class method';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$extra['edit_link']) {
			$extra['edit_link'] = 'modules_link';
		}
		if (!$extra['box_name']) {
			$extra['box_name'] = 'methods_box';
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$edit_link = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
// TODO: load methods select box right here, to be able to more easily embed this into other places

		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">
					<input type="text" id="'.$name.'" name="'.$name.'" value="'.$r[$name].'">
					'.$r[$extra['box_name']].' <input type="button" class="btn btn-mini insert_selected_word" value="&lt;&lt;" title="'.t('Insert Selected Word').'">'
					.($edit_link ? ' <a href="'.$edit_link.'" class="btn btn-mini"><i class="icon-edit"></i> '.t('Edit').'</a>' : '')
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function template_select_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		if (!$name) {
			$name = 'stpl_name';
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$desc) {
			$desc = 'Custom template';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$extra['edit_link']) {
			$extra['edit_link'] = 'stpls_link';
		}
		if (!$extra['box_name']) {
			$extra['box_name'] = 'stpls_box';
		}
		$r = $replace ? $replace : $this->_replace;
		$errors = common()->_get_error_messages();
		$edit_link = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
// TODO: load templates select box right here, to be able to more easily embed this into other places

		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
				<label class="control-label" for="'.$name.'">'.t($desc).'</label>
				<div class="controls">
					<input type="text" id="'.$name.'" name="'.$name.'" value="'.$r[$name].'">
					'.$r[$extra['box_name']].' <input type="button" class="btn btn-mini insert_selected_word" value="&lt;&lt;" title="'.t('Insert Selected Word').'">'
					.($edit_link ? ' <a href="'.$edit_link.'" class="btn btn-mini"><i class="icon-edit"></i> '.t('Edit').'</a>' : '')
					.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
				.'</div>
			</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function icon_select_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (!$name) {
			$name = 'icon';
		}
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
/*
	<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">
		<label class="control-label" for="icon">{t(Item Icon)}</label>
		<div class="controls">
			<span class="icon_preview">{if("icon_src" ne "")}<img src="{icon_src}" />{/if}</span>
			<input type="text" id="icon" name="icon" value="{icon}" />
			<input type="button" value="V" id="icon_selector" style="display:none;" class="btn" />
		</div>
	</div>
*/
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function captcha($var_name = '', $desc = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$desc) {
			$desc = 'Captcha';
		}
		if (!$var_name) {
			$var_name = 'captcha_block';
		}
		if (isset($replace[$var_name])) {
			$text = $replace[$var_name];
		} else {
			$text = _class('captcha')->show_block('./?object=dynamic&action=captcha_image');
		}
		$errors = common()->_get_error_messages();
		$inline_help = isset($errors[$name]) ? $errors[$name] : $extra['inline_help'];
		$body = '
			<div class="control-group'.(isset($errors[$name]) ? ' error' : '').'">'
				.($desc ? '<label class="control-label">'.t($desc).'</label>' : '')
				.'<div class="controls">'.$text.'</div>'
				.($inline_help ? '<span class="help-inline">'.$inline_help.'</span>' : '')
				.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
			.'</div>
		';
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			$this->_validate_rules[$name] = $extra['validate'];
			return $this;
		}
		return $body;
	}

	/**
	*/
	function validate($validate_rules = array(), $post = array()) {
		$data = (array)(!empty($post) ? $post : $_POST);
		if (empty($data)) {
			return $this;
		}
		$form_global_validate = isset($this->_params['validate']) ? $this->_params['validate'] : $this->_replace['validate'];
		foreach ((array)$form_global_validate as $name => $rules) {
			$this->_validate_rules[$name] = $rules;
		}
		foreach ((array)$validate_rules as $name => $rules) {
			$this->_validate_rules[$name] = $rules;
		}
		// Cleanup rules before we start
		$tmp = array();
		foreach ((array)$this->_validate_rules as $name => $rules) {
			if (empty($rules)) {
				continue;
			}
			$_rules = array();
			foreach ((array)$rules as $rule) {
				if (is_callable($rule)) {
					$_rules[] = $rule;
				} elseif (is_string($rule)) {
					foreach (explode('|', $rule) as $r2) {
						$r2 = trim($r2);
						$r_param = null;
						// Parsing these: min_length[6], matches[form_item], is_unique[table.field]
						$pos = strpos($r2, '[');
						if ($pos !== false) {
							$r_param = trim(trim(substr($r2, $pos), ']['));
							$r2 = trim(substr($r2, 0, $pos));
						}
						$_rules[] = array($r2, $r_param);
					}
				}
			}
			if ($_rules) {
				$tmp[$name] = $_rules;
			}
		}
		$this->_validate_rules = $tmp;
		unset($tmp);

		// Processing of prepared rules
		foreach ((array)$this->_validate_rules as $name => $rules) {
			foreach ((array)$rules as $rule) {
				$is_ok = true;
				$error_msg = '';
				if (is_callable($rule)) {
					$is_ok = $rule($data[$name], null, $data);
				} else {
					$func = $rule[0];
					$param = $rule[1];
					// PHP pure function, from core or user
					if (function_exists($func)) {
						$data[$name] = $func($data[$name]);
					// Method from 'validate'
					} else {
						$is_ok = _class('validate')->$func($data[$name], array('param' => $param), $data);
						if (!$is_ok) {
							$error_msg = t('form_validate_'.$func, array('%field' => $name));
						}
					}
				}
				if (!$is_ok) {
					if (!$error_msg) {
						$error_msg = 'Wrong field '.$name;
					}
					_re($error_msg, $name);
					// In case when we see any validation rule is not OK - we stop checking further for this field
					continue 2;
				}
			}
		}
		if ( ! common()->_error_exists()) {
			$this->_validate_ok = true;
		} else {
			$this->_validate_ok = false;
		}

		$this->_validated_fields = $data;

		return $this;
	}

	/**
	*/
	function db_insert_if_ok($table, $fields, $add_fields = array(), $extra = array()) {
		$extra['add_fields'] = $add_fields;
		return $this->_db_change_if_ok($table, $fields, 'insert', $extra);
	}

	/**
	*/
	function db_update_if_ok($table, $fields, $where_id, $extra = array()) {
		$extra['where_id'] = $where_id;
		return $this->_db_change_if_ok($table, $fields, 'update', $extra);
	}

	/**
	*/
	function _db_change_if_ok($table, $fields, $type, $extra = array()) {
		if (!$this->_validate_ok || !$table || !$fields || !$type) {
			return $this;
		}
		$data = array();
		foreach ((array)$fields as $k => $name) {
			// Example $fields = array('login','email');
			if (is_numeric($k)) {
				$db_field_name = $name;
			// Example $fields = array('pswd' => 'password');
			} else {
				$db_field_name = $name;
				$name = $k;
			}
			if (isset($this->_validated_fields[$name])) {
				$data[$db_field_name] = $this->_validated_fields[$name];
			}
		}
		// This is non-validated list of fields to add to the insert array
		foreach ((array)$extra['add_fields'] as $db_field_name => $value) {
			$data[$db_field_name] = $value;
		}
		if ($data && $table) {
			if ($type == 'update') {
				db()->update($table, $data, $extra['where_id']);
			} elseif ($type == 'insert') {
				db()->insert($table, $data);
			}
			$redirect_link = $extra['redirect_link'] ? $extra['redirect_link'] : $this->_replace['back_link'];
			if (!$redirect_link) {
				$redirect_link = './?object='.$_GET['object']. ($_GET['action'] != 'show' ? $_GET['action'] : ''). ($_GET['id'] ? $_GET['id'] : '');
			}
			if (!$extra['no_redirect']) {
				js_redirect($redirect_link);
			}
		}
		return $this;
	}
}
