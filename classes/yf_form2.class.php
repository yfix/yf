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
	*
	*	{catch("field_name")}some_other_field{/catch} {form_row('text','%field_name')}
	*	{catch("t_password")}My password inside replace['t_password']{/catch} {form_row('text','pswd','%t_password')}
	*/
	function tpl_row($type = 'input', $replace = array(), $name, $desc = '', $extra = array()) {
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
		if (is_string($extra)) {
			$extra = trim($extra);
		}
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
		if ($this->_params['show_alerts']) {
			$errors = common()->_get_error_messages();
			if ($errors) {
				$e = array();
				foreach ((array)$errors as $msg) {
					$e[] = '<div class="alert alert-error"><button type="button" class="close" data-dismiss="alert">&times;</button>'.$msg.'</div>';
				}
				array_unshift($this->_body, implode(PHP_EOL, $e));
			}
		}
		// Ensure that form begin and ending will be in the right place of the output
		$form_begin = $this->_body['form_begin'];
		unset($this->_body['form_begin']);
		$form_end = $this->_body['form_end'];
		unset($this->_body['form_end']);
		return $form_begin. PHP_EOL. implode(PHP_EOL, $this->_body). PHP_EOL. $form_end;
	}

	/**
	*/
	function form_begin($name = '', $method = '', $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		// Shortcut: use second param as $extra
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
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
		$extra['method'] = $extra['method'] ?: $method;
		$extra['action'] = isset($r[$name]) ? $r[$name] : './?object='.$_GET['object'].'&action='.$_GET['action']. ($_GET['id'] ? '&id='.$_GET['id'] : ''). $this->_params['links_add'];
		$extra['class'] = $extra['class'] ?: 'form-horizontal';
		$extra['autocomplete'] = $extra['autocomplete'] ?: true;

		$body = '<form '.$this->_attrs($extra, array('method','action','class','style','id','name','autocomplete','enctype')).'>';

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
	* We need this to avoid encoding & => &amp; by standard htmlspecialchars()
	*/
	function _htmlchars($str = '') {
		$replace = array(
			'"' => '&quot;',
			"'" => '&apos;',
			'<'	=> '&lt;',
			'>'	=> '&gt;',
		);
		return str_replace(array_keys($replace), array_values($replace), $str);
	}

	/**
	*/
	function _attrs($extra = array(), $names = array()) {
		$body = array();
		foreach ((array)$names as $name) {
			if (!$name || !isset($extra[$name])) {
				continue;
			}
			$val = $extra[$name];
			$body[$name] = $this->_htmlchars($name).'="'.$this->_htmlchars($val).'"';
		}
		foreach ((array)$extra['attr'] as $name => $val) {
			if (!$name || !isset($val)) {
				continue;
			}
			$body[$name] = $this->_htmlchars($name).'="'.$this->_htmlchars($val).'"';
		}
		return ' '.implode(' ', $body).' ';
	}

	/**
	*/
	function _prepare_custom_attr($attr = array()) {
		$body = array();
		foreach ((array)$attr as $k => $v) {
			$body[] = $this->_htmlchars($k).'="'.$this->_htmlchars($v).'"';
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
	*/
	function _row_html($content, $extra = array(), $replace = array()) {
		$css_framework = conf('css_framework');
		if ($css_framework) {
#			return _class('html')->form_row($content, $extra, $replace, $this);
		}
		if (conf('form_input_no_append')) {
			$extra['append'] = '';
			$extra['prepend'] = '';
		}
		return '
			<div class="control-group form-group'.(isset($extra['errors'][$extra['name']]) ? ' error' : '').'">'.PHP_EOL
				.($extra['desc'] ? '<label class="control-label col-lg-2" for="'.$extra['id'].'">'.t($extra['desc']).'</label>'.PHP_EOL : '')
				.(!$extra['wide'] ? '<div class="controls col-lg-4">'.PHP_EOL : '')

					.(($extra['prepend'] || $extra['append']) ? '<div class="input-group '.($extra['prepend'] ? 'input-prepend' : '').($extra['append'] ? ' input-append' : '').'">'.PHP_EOL : '')
					.($extra['prepend'] ? '<span class="add-on input-group-addon">'.$extra['prepend'].'</span>'.PHP_EOL : '')

					.$content.PHP_EOL

					.($extra['append'] ? '<span class="add-on input-group-addon">'.$extra['append'].'</span>'.PHP_EOL : '')
					.(($extra['prepend'] || $extra['append']) ? '</div>'.PHP_EOL : '')

					.($extra['edit_link'] ? ' <a href="'.$extra['edit_link'].'" class="btn btn-mini btn-xs"><i class="icon-edit"></i> '.t('Edit').'</a>'.PHP_EOL : '')
					.(($extra['link_url'] && $extra['link_name']) ? ' <a href="'.$extra['link_url'].'" class="btn">'.t($extra['link_name']).'</a>'.PHP_EOL : '')

					.($extra['inline_help'] ? '<span class="help-inline">'.$extra['inline_help'].'</span>'.PHP_EOL : '')
					.($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '')
					.(isset($extra['ckeditor']) ? $this->_ckeditor_html($extra, $replace) : '')

				.(!$extra['wide'] ? '</div>'.PHP_EOL : '')
			.'</div>'.PHP_EOL
		;
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
		$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
		$extra['contenteditable'] = isset($extra['ckeditor']) ? 'true' : 'false';
		$extra['id'] = $extra['id'] ?: 'content_editable';

		$attrs_names = array('id','contenteditable','style','class');
		$body = $this->_row_html(isset($extra['ckeditor']) ? '<div '.$this->_attrs($extra, $attrs_names).'>'.$text.'</div>' : $text, $extra, $replace);

		if ($this->_chained_mode) {
			$this->_body[] = $body;
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
		$extra['errors'] = common()->_get_error_messages();
		$extra['id'] = $extra['id'] ?: $name;
		$extra['placeholder'] = t($extra['placeholder'] ?: $desc);
		$extra['value'] = isset($extra['value']) ? $extra['value'] : $r[$name];
		// Compatibility with filter
		if (!strlen($extra['value'])) {
			if (isset($extra['selected'])) {
				$extra['value'] = $extra['selected'];
			} elseif (isset($this->_params['selected'])) {
				$extra['value'] = $this->_params['selected'][$name];
			}
		}
		$extra['type'] = $extra['type'] ?: 'text';
		$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
		$extra['inline_help'] = isset($extra['errors'][$name]) ? $extra['errors'][$name] : $extra['inline_help'];
		$extra['class'] = 'form-control '.$this->_prepare_css_class('', $r[$name], $extra);
		// Supported: mini, small, medium, large, xlarge, xxlarge
		if ($extra['sizing']) {
			$extra['class'] .= ' input-'.$extra['sizing'];
		}
		$vr = $this->_validate_rules[$name];
		if ($vr['required']) {
			$extra['required'] = 1;
		}
		// http://stackoverflow.com/questions/10281962/is-it-minlength-in-html5
		if ($vr['min_length'] && !isset($extra['pattern'])) {
			$extra['pattern'] = '.{'.$vr['min_length'][1].','.($vr['max_length'] ? $vr['max_length'][1] : '').'}';
		}
		if ($vr['max_length'] && !isset($extra['maxlength'])) {
			$extra['maxlength'] = $vr['max_length'][1];
		}
		$extra['name'] = $name;
		$extra['desc'] = $desc;

		$attrs_names = array('name','type','id','class','style','placeholder','value','data','size','maxlength','pattern','disabled','required','autocomplete');
		$body = $this->_row_html('<input '.$this->_attrs($extra, $attrs_names).'>', $extra, $replace);

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
		$extra['errors'] = common()->_get_error_messages();
		$extra['id'] = $extra['id'] ? $extra['id'] : $name;
		$extra['placeholder'] = t(isset($extra['placeholder']) ? $extra['placeholder'] : $desc);
		$value = isset($extra['value']) ? $extra['value'] : $r[$name];
		// Compatibility with filter
		if (!strlen($value)) {
			if (isset($extra['selected'])) {
				$value = $extra['selected'];
			} elseif (isset($this->_params['selected'])) {
				$value = $this->_params['selected'][$name];
			}
		}
		$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
		$extra['inline_help'] = isset($extra['errors'][$name]) ? $extra['errors'][$name] : $extra['inline_help'];
		$extra['contenteditable'] = $extra['contenteditable'] ?: 'true';
		$extra['class'] = 'ckeditor form-control '.$this->_prepare_css_class('', $r[$extra['name']], $extra);
		$extra['name'] = $name;
		$extra['desc'] = $desc;

		$attrs_names = array('id','name','placeholder','contenteditable','class','style','cols','rows');
		$body = $this->_row_html('<textarea '.$this->_attrs($extra, $attrs_names).'>'.(!isset($extra['no_escape']) ? $this->_htmlchars($value) : $value).'</textarea>', $extra, $replace);

		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
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
		$body .= tpl()->_stpl_exists($stpl_name) ? tpl()->parse($stpl_name, (array)$extra + (array)$replace ) : '';

		// Avoid including ckeditor scripts several times on same page
		$this->_ckeditor_scripts_included = true;

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
		$extra['id'] = $extra['id'] ? $extra['id'] : $name;
		$extra['value'] = isset($extra['value']) ? $extra['value'] : $r[$name];
		$extra['name'] = $name;
		$extra['type'] = 'hidden';

		$attrs_names = array('type','id','name','value','data');
		$body = '<input '.$this->_attrs($extra, $attrs_names).'>';

		if ($this->_chained_mode) {
			$this->_body[] = $body;
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
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
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
		$extra['sizing'] = isset($extra['sizing']) ? $extra['sizing'] : 'small';
		$extra['maxlength'] = isset($extra['maxlength']) ? $extra['maxlength'] : '10';
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
		$extra['prepend'] = isset($extra['prepend']) ? $extra['prepend'] : '$';
		$extra['append'] = isset($extra['append']) ? $extra['append'] : '.00';
		$extra['sizing'] = isset($extra['sizing']) ? $extra['sizing'] : 'small';
		$extra['maxlength'] = isset($extra['maxlength']) ? $extra['maxlength'] : '8';
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
		$extra['errors'] = common()->_get_error_messages();
		$extra['inline_help'] = isset($extra['errors'][$name]) ? $extra['errors'][$name] : $extra['inline_help'];
		$extra['name'] = $name;
		$extra['desc'] = $desc;
		$extra['id'] = $name;

		$selected = $r[$name];
		if (isset($extra['selected'])) {
			$selected = $extra['selected'];
		} elseif (isset($this->_params['selected'])) {
			$selected = $this->_params['selected'][$name];
		}

		$body = $this->_row_html(_class('html_controls')->radio_box($name, $extra['items'], $selected, false, 2, '', false), $extra, $replace);

		if ($this->_chained_mode) {
			$this->_body[] = $body;
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
		// Shortcut: use first or second param as $extra
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		}
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$value = isset($extra['value']) ? $extra['value'] : 'Save';
		$r = $replace ? $replace : $this->_replace;
		$extra['errors'] = common()->_get_error_messages();
		$extra['id'] = $extra['id'] ?: $name;
		$extra['link_url'] = $extra['link_url'] ? (isset($r[$extra['link_url']]) ? $r[$extra['link_url']] : '') : '';
		if (preg_match('~^[a-z0-9_-]+$~ims', $extra['link_url'])) {
			$extra['link_url'] = '';
		}
		$extra['link_name'] = $extra['link_name'] ?: '';
		$extra['class'] = 'btn btn-primary '.$this->_prepare_css_class('', $r[$name], $extra);
		$extra['inline_help'] = isset($extra['errors'][$name]) ? $extra['errors'][$name] : $extra['inline_help'];
		$extra['value'] = t($value);
		$extra['desc'] = ''; // We do not need label here
		$extra['type'] = 'submit';

		$attrs_names = array('type','name','id','class','style','value','disabled');
		$body = $this->_row_html('<input '.$this->_attrs($extra, $attrs_names).'>', $extra, $replace);

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
		$extra['errors'] = common()->_get_error_messages();
		$extra['inline_help'] = isset($extra['errors'][$name]) ? $extra['errors'][$name] : $extra['inline_help'];
		$extra['name'] = $name;
		$extra['desc'] = $extra['no_label'] ? '' : $desc;
		$value = $r[$name];
		if (is_array($extra['data'])) {
			if (isset($extra['data'][$value])) {
				$value = $extra['data'][$value];
			} elseif (isset($extra['data'][$name])) {
				$value = $extra['data'][$name];
			}
		}
		$value = !isset($extra['no_escape']) ? $this->_htmlchars($value) : $value;

		$content = '';
		if ($extra['link']) {
			$content = '<a href="'.$extra['link'].'" class="btn btn-mini btn-xs">'.$value.'</a>';
		} else {
			$content = '<span class="'.$this->_prepare_css_class('label label-info', $r[$name], $extra).'">'.$value.'</span>';
		}
		$body = $this->_row_html($content, $extra, $replace);

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
	function info_date($name = '', $format = '', $extra = array(), $replace = array()) {
		$r = $replace ? $replace : $this->_replace;
		$replace[$name] = _format_date($r[$name], $format);
		$this->_replace[$name] = $replace[$name];
		return $this->info($name, $format, $extra, $replace);
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
	function _get_selected($name, $extra, $replace) {
		$r = $replace ?: $this->_replace;
		$selected = $r[$name];
		if (isset($extra['selected'])) {
			$selected = $extra['selected'];
		} elseif (isset($this->_params['selected'])) {
			$selected = $this->_params['selected'][$name];
		}
		return $selected;
	}

	/**
	*/
	function _html_control($name, $values, $extra = array(), $replace = array(), $func) {
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
		$r = $replace ?: $this->_replace;
		$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
		$extra['desc'] = isset($extra['desc']) ? $extra['desc'] : ucfirst(str_replace('_', ' ', $name));
		$extra['errors'] = common()->_get_error_messages();
		$extra['inline_help'] = isset($extra['errors'][$name]) ? $extra['errors'][$name] : $extra['inline_help'];
		$extra['values'] = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		$extra['selected'] = $this->_get_selected($name, $extra, $replace);
		$extra['id'] = $name;
		$extra['name'] = $name;

		$body = $this->_row_html(_class('html_controls')->$func($extra), $extra, $replace);

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
		$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
		$extra['desc'] = isset($extra['desc']) ? $extra['desc'] : ucfirst(str_replace('_', ' ', $name));
		$extra['errors'] = common()->_get_error_messages();
		$extra['inline_help'] = isset($extra['errors'][$name]) ? $extra['errors'][$name] : $extra['inline_help'];
		$extra['values'] = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		$extra['selected'] = $this->_get_selected($name, $extra, $replace);
		$extra['id'] = $name;
		$extra['name'] = $name;
		$extra['desc'] = $desc;

		$body = $this->_row_html($r[$name], $extra, $replace);

		if ($this->_chained_mode) {
			$this->_body[] = $body;
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
		return $this->_html_control($name, $values, $extra, $replace, 'select_box');
	}

	/**
	*/
	function multi_select($name, $values, $extra = array(), $replace = array()) {
		return $this->_html_control($name, $values, $extra, $replace, 'multi_select_box');
	}

	/**
	*/
	function multi_select_box($name, $values, $extra = array(), $replace = array()) {
		return $this->_html_control($name, $values, $extra, $replace, 'multi_select_box');
	}

	/**
	*/
	function check_box($name, $values, $extra = array(), $replace = array()) {
		return $this->_html_control($name, $values, $extra, $replace, 'check_box');
	}

	/**
	*/
	function multi_check_box($name, $values, $extra = array(), $replace = array()) {
		return $this->_html_control($name, $values, $extra, $replace, 'multi_check_box');
	}

	/**
	*/
	function radio_box($name, $values, $extra = array(), $replace = array()) {
		return $this->_html_control($name, $values, $extra, $replace, 'radio_box');
	}

	/**
	*/
	function date_box($name, $values = array(), $extra = array(), $replace = array()) {
		return $this->_html_control($name, $values, $extra, $replace, 'date_box2');
	}

	/**
	*/
	function time_box($name, $values = array(), $extra = array(), $replace = array()) {
		return $this->_html_control($name, $values, $extra, $replace, 'time_box2');
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
	*/
	function birth_box($name = '', $values = array(), $extra = array(), $replace = array()) {
		if (!$name) {
			$name = 'birth';
		}
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
// TODO
		return $this->text($name, $desc, $extra, $replace);
	}

	/**
	*/
	function template_select_box($name = '', $desc = '', $extra = array(), $replace = array()) {
// TODO
		return $this->text($name, $desc, $extra, $replace);
	}

	/**
	*/
	function location_select_box($name = '', $desc = '', $extra = array(), $replace = array()) {
// TODO
		return $this->text($name, $desc, $extra, $replace);
	}

	/**
	*/
	function icon_select_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		return $this->text($name, $desc, $extra, $replace);
// TODO

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
// TODO
/*
	<div class="control-group'.(isset($extra['errors'][$name]) ? ' error' : '').'">
		<label class="control-label" for="icon">{t(Item Icon)}</label>
		<div class="controls">
			<span class="icon_preview">{if("icon_src" ne "")}<img src="{icon_src}" />{/if}</span>
			<input type="text" id="icon" name="icon" value="{icon}" />
			<input type="button" value="V" id="icon_selector" style="display:none;" class="btn" />
		</div>
	</div>
*/
/*
		main()->NO_GRAPHICS = true;
		$icons_dir = INCLUDE_PATH. $this->ICONS_PATH;
		$cut_length = 0;
		foreach ((array)_class('dir')->scan_dir($icons_dir, true, '', '/\.(svn|git)/i') as $_icon_path) {
			$_icon_path = str_replace("\\", '/', strtolower($_icon_path));
			if (empty($cut_length)) {
				$cut_length = strpos($_icon_path, str_replace("\\", '/', strtolower($this->ICONS_PATH))) + strlen($this->ICONS_PATH);
			}
			$_icon_path = substr($_icon_path, $cut_length);
			$body[$_icon_path] = $_icon_path;
		}
		if (is_array($body)) {
			ksort($body);
		}
		echo implode(PHP_EOL, $body);
*/
		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function div_box() {
// TODO: need BS div-based select-box emulation, will be needed for several methods
	}

	/**
	*/
	function captcha($name = '', $desc = '', $extra = array(), $replace = array()) {
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
		if (!$name) {
			$name = 'captcha';
		}
		$extra['errors'] = common()->_get_error_messages();
		$extra['inline_help'] = isset($extra['errors'][$name]) ? $extra['errors'][$name] : $extra['inline_help'];
		$extra['id'] = $name;
		$extra['name'] = $name;
		$extra['desc'] = $desc;

		$body = $this->_row_html(_class('captcha')->show_block('./?object=dynamic&action=captcha_image'), $extra, $replace);

		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function custom_fields($name, $custom_fields, $extra = array(), $replace = array()) {
		if ($this->_chained_mode) {
			$replace = (array)$this->_replace + (array)$replace;
		}
		$custom_fields = explode(',', $custom_fields);
		$restore_mode = $this->_chained_mode;
		$sub_array_name = $extra['sub_array'] ?: 'custom';
		$custom_info = $this->_attrs_string2array($replace[$name]);

		$body = array();
		$this->_chained_mode = false;
		foreach ((array)$custom_fields as $field_name) {
			if (empty($field_name)) {
				continue;
			}
			$str = _class('html_controls')->input(array(
				'id'	=> 'custom_'.$field_name.'_'.$replace['id'],
				'name'	=> $sub_array_name.'['.$field_name.']', // Example: custom[color]
				'desc'	=> $field_name,
				'value'	=> $custom_info[$field_name],
			));
			$desc = ucfirst(str_replace('_', ' ', $field_name)).' [Custom]';
			$body[] = $this->container($str, $desc);
		}
		$body = implode(PHP_EOL, $body);
		$this->_chained_mode = $restore_mode;

		if ($this->_chained_mode) {
			$this->_body[] = $body;
			return $this;
		}
		return $body;
	}

	/**
	*/
	function _attrs_string2array($string = '') {
		$output_array = array();
		foreach (explode(';', trim($string)) as $tmp_string) {
			list($try_key, $try_value) = explode('=', trim($tmp_string));
			$try_key = trim(trim(trim($try_key), '"'));
			$try_value = trim(trim(trim($try_value), '"'));
			if (strlen($try_key) && strlen($try_value)) {
				$output_array[$try_key] = $try_value;
			}
		}
		return $output_array;
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

// TODO: use CSS abstraction layer
		$body = ' <a href="'.$link_url.'" class="btn btn-mini btn-xs'.($extra['class'] ? ' '.$extra['class'] : '').'"><i class="'.$icon.'"></i> '.t($name).'</a> ';

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

// TODO: use CSS abstraction layer
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
	function validate($validate_rules = array(), $post = array()) {
		$form_global_validate = isset($this->_params['validate']) ? $this->_params['validate'] : $this->_replace['validate'];
		foreach ((array)$form_global_validate as $name => $rules) {
			$this->_validate_rules[$name] = $rules;
		}
		foreach ((array)$validate_rules as $name => $rules) {
			$this->_validate_rules[$name] = $rules;
		}
		$this->_validate_rules = $this->_validate_rules_cleanup($this->_validate_rules);
		// Do not do validation until data is empty (usually means that form is just displayed and we wait user input)
		$data = (array)(!empty($post) ? $post : $_POST);
		if (empty($data)) {
			return $this;
		}
		// Processing of prepared rules
		$validate_ok = $this->_validate_rules_process($this->_validate_rules, $data);
		if ($validate_ok) {
			$this->_validate_ok = true;
		} else {
			$this->_validate_ok = false;
		}
		$this->_validated_fields = $data;
		return $this;
	}

	/**
	* Examples of validate rules setting:
	* 	'name1' => 'trim|required',
	* 	'name2' => array('trim', 'required'),
	* 	'name3' => array('trim|required', 'other_rule|other_rule2|other_rule3'),
	* 	'name4' => array('trim|required', function() { return true; } ),
	* 	'name5' => array('trim', 'required', function() { return true; } ),
	* 	'__all_before__' => 'trim',
	* 	'__all_after__' => 'some_method2|some_method3',
	*/
	function _validate_rules_cleanup($validate_rules = array()) {
		// Add these rules to all validation rules, before them
		$_name = '__before__';
		$all_before = '';
		if (isset($validate_rules[$_name])) {
			$all_before = $validate_rules[$_name];
			if (!is_array($all_before)) {
				$all_before = explode('|', $all_before);
			}
			unset($validate_rules[$_name]);
		}

		// Add these rules to all validation rules, after them
		$_name = '__after__';
		$all_after = '';
		if (isset($validate_rules[$_name])) {
			$all_after = $validate_rules[$_name];
			if (!is_array($all_after)) {
				$all_after = explode('|', $all_after);
			}
			unset($validate_rules[$_name]);
		}
		unset($_name);

		$out = array();
		foreach ((array)$validate_rules as $name => $rules) {
			if (empty($rules)) {
				continue;
			}
			$_rules = array();
			if (is_array($rules)) {
				if ($all_before) {
// TODO: fix me
#					$tmp = $all_before;
#					foreach ($rules as $v) {
#						$tmp[] = $v;
#					}
#					$rules = $tmp;
#					unset($tmp);
#					$rules = $all_before + $rules;
				}
				if ($all_after) {
#					$rules = $rules + $all_after;
				}
			} else {
				if ($all_before) {
#					$rules = array($all_before, $rules);
				}
				if ($all_after) {
#					$rules = array($rules, $all_after);
				}
			}
			foreach ((array)$rules as $rule) {
				if (is_callable($rule)) {
					$_rules[] = $rule;
				} elseif (is_string($rule)) {
					$rule = explode('|', $rule);
				}
#				if (is_array($rule)) {
#					foreach ($rule as $r2) {
#						if (false !== strpos($r2, '|')) {
#						}
#					}
#				}
				if (is_array($rule)) {
					foreach ($rule as $r2) {
						$r2 = trim($r2);
						$r_param = null;
						// Parsing these: min_length[6], matches[form_item], is_unique[table.field]
						$pos = strpos($r2, '[');
						if ($pos !== false) {
							$r_param = trim(trim(substr($r2, $pos), ']['));
							$r2 = trim(substr($r2, 0, $pos));
						}
						// Ensure we will not call duplicate rules on same field
						$_rules[$r2] = array($r2, $r_param);
					}
				}
			}
			if ($_rules) {
				$out[$name] = $_rules;
			}
		}
#echo '<pre>'.print_r($out, 1).'</pre>';
		return $out;
	}

	/**
	*/
	function _validate_rules_process($validate_rules = array(), &$data) {
		$validate_ok = true;
		foreach ((array)$validate_rules as $name => $rules) {
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
						$is_ok = _class('validate')->$func($data[$name], array('param' => $param), $data, $error_msg);
						if (!$is_ok && empty($error_msg)) {
							$error_msg = t('form_validate_'.$func, array('%field' => $name, '%param' => $param));
						}
					}
				}
				if (!$is_ok) {
					$validate_ok = false;
					if (!$error_msg) {
						$error_msg = 'Wrong field '.$name;
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
		if (!$this->_validate_ok || !$table || !$type) {
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
		// Callback/hook function implementation
		if ($data && $table && $extra['on_before_update']) {
			$func = $extra['on_before_update'];
			$func($data, $table, $fields, $type, $extra);
		}
		if ($data && $table) {
			if ($type == 'update') {
				db()->update($table, db()->es($data), $extra['where_id']);
			} elseif ($type == 'insert') {
				db()->insert($table, db()->es($data));
			}
			// Callback/hook function implementation
			if ($extra['on_after_update']) {
				$func = $extra['on_after_update'];
				$func($data, $table, $fields, $type, $extra);
			}
			if ($extra['on_success_text']) {
				common()->set_notice($extra['on_success_text']);
			}
			$redirect_link = $extra['redirect_link'] ? $extra['redirect_link'] : ($this->_replace['redirect_link'] ? $this->_replace['redirect_link'] : $this->_replace['back_link']);
			if (!$redirect_link) {
				$redirect_link = './?object='.$_GET['object']. ($_GET['action'] != 'show' ? '&action='.$_GET['action'] : ''). ($_GET['id'] ? '&id='.$_GET['id'] : '');
			}
			if (!$extra['no_redirect']) {
				js_redirect($redirect_link);
			}
		}
		return $this;
	}
}
