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
			if (false !== strpos($extra, ';') && false !== strpos($extra, '=')) {
				$extra = $this->_attrs_string2array($extra);
			}
		}
		if (!is_array($extra)) {
			// Suppose we have 3rd argument as edit link here
			if (!empty($extra)) {
				$extra = array('edit_link' => $extra);
			} else {
				$extra = array();
			}
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
		if (!$extra['no_form'] && !$this->_params['no_form']) {
			// Call these methods, if not done yet, save 2 api calls
			if (!isset($this->_body['form_begin'])) {
				$this->form_begin('', '', $extra, $replace);
			}
			if (!isset($this->_body['form_end'])) {
				$this->form_end();
			}
			// Force form_begin as first array element
			$form_begin = $this->_body['form_begin'];
			unset($this->_body['form_begin']);
			array_unshift($this->_body, $form_begin);

			// Force form_end as last array element
			$form_end = $this->_body['form_end'];
			unset($this->_body['form_end']);
			$this->_body['form_end'] = $form_end;
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

		$r = (array)$this->_replace + (array)$replace;

		foreach ((array)$this->_body as $k => $v) {
			if (is_array($v)) {
				$_extra = $v['extra'];
				$func = $v['func'];
				if ($this->_stacked_mode_on) {
					$_extra['stacked'] = true;
				}
				$this->_body[$k] = $func($_extra, $r, $this);
			}
		}
		return implode(PHP_EOL, $this->_body);
	}

	/**
	*/
	function form_begin($name = '', $method = '', $extra = array(), $replace = array()) {
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

		$extra['name'] = $extra['name'] ?: ($name ?: 'form_action');
		$extra['method'] = $extra['method'] ?: ($method ?: 'post');

		$func = function($extra, $r, $_this) {
			$enctype = '';
			if ($extra['enctype']) {
				$enctype = $extra['enctype'];
			} elseif ($extra['for_upload']) {
				$enctype = 'multipart/form-data';
			}
			$extra['enctype'] = $enctype;
			$extra['action'] = isset($r[$extra['name']]) ? $r[$extra['name']] : './?object='.$_GET['object'].'&action='.$_GET['action']. ($_GET['id'] ? '&id='.$_GET['id'] : ''). $_this->_params['links_add'];
			$extra['class'] = $extra['class'] ?: 'form-horizontal';
			$extra['autocomplete'] = $extra['autocomplete'] ?: true;

			$body = '<form '.$_this->_attrs($extra, array('method','action','class','style','id','name','autocomplete','enctype')).'>';
			$body .= '<fieldset>';
			if ($extra['legend']) {
				$body .= '<legend>'.$_this->_htmlchars(t($extra['legend'])).'</legend>';
			}
			return $body;
		};
		if ($this->_chained_mode) {
			$this->_body[__FUNCTION__] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	*/
	function form_end($extra = array(), $replace = array()) {
		if (!is_array($extra)) {
			$extra = array();
		}
		$func = function($extra, $r, $_this) {
			$body = '</fieldset>';
			$body .= '</form>';
			return $body;
		};
		if ($this->_chained_mode) {
			$this->_body[__FUNCTION__] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
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
		$css_framework = $extra['css_framework'] ?: ($this->_params['css_framework'] ?: conf('css_framework'));
		if ($extra['form_input_no_append'] || $this->_params['form_input_no_append'] || conf('form_input_no_append')) {
			$extra['append'] = '';
			$extra['prepend'] = '';
		}
		if ($this->_stacked_mode_on) {
			$extra['stacked'] = true;
		}
		if ($css_framework) {
			$extra['css_framework'] = $css_framework;
			return _class('html')->form_row($content, $extra, $replace, $this);
		}
		$row_start = 
			'<div class="control-group form-group'.(isset($extra['errors'][$extra['name']]) ? ' error' : '').'">'.PHP_EOL
				.($extra['desc'] && !$extra['no_label'] ? '<label class="control-label col-lg-2" for="'.$extra['id'].'">'.t($extra['desc']).'</label>'.PHP_EOL : '')
				.(!$extra['wide'] ? '<div class="controls col-lg-4">'.PHP_EOL : '');

		$row_end =
				(!$extra['wide'] ? '</div>'.PHP_EOL : '')
			.'</div>'.PHP_EOL;

		$before_content_html = 
			(($extra['prepend'] || $extra['append']) ? '<div class="input-group '.($extra['prepend'] ? 'input-prepend' : '').($extra['append'] ? ' input-append' : '').'">'.PHP_EOL : '')
			.($extra['prepend'] ? '<span class="add-on input-group-addon">'.$extra['prepend'].'</span>'.PHP_EOL : '');

		$after_content_html = 
			($extra['append'] ? '<span class="add-on input-group-addon">'.$extra['append'].'</span>'.PHP_EOL : '')
			.(($extra['prepend'] || $extra['append']) ? '</div>'.PHP_EOL : '');

		$edit_link_html = ($extra['edit_link'] ? ' <a href="'.$extra['edit_link'].'" class="btn btn-mini btn-xs"><i class="icon-edit"></i> '.t('Edit').'</a>'.PHP_EOL : '');
		$link_name_html = (($extra['link_url'] && $extra['link_name']) ? ' <a href="'.$extra['link_url'].'" class="btn">'.t($extra['link_name']).'</a>'.PHP_EOL : '');

		$inline_help_html = ($extra['inline_help'] ? '<span class="help-inline">'.$extra['inline_help'].'</span>'.PHP_EOL : '');
		$inline_tip_html = ($extra['tip'] ? ' '.$this->_show_tip($extra['tip'], $extra, $replace) : '');

		if ($extra['only_row_start']) {
			return $row_start;
		} elseif ($extra['only_row_end']) {
			return $row_end;
		} elseif ($extra['stacked']) {
			return $before_content_html. $content. PHP_EOL. $after_content_html
				.$edit_link_html. $link_name_html. $inline_help_html. $inline_tip_html;
		} else {
			// Full variant
			return $row_start
					.$before_content_html. $content. PHP_EOL. $after_content_html
					.$edit_link_html. $link_name_html. $inline_help_html. $inline_tip_html
					.(isset($extra['ckeditor']) ? $this->_ckeditor_html($extra, $replace) : '')
				.$row_end;
		}
	}

	/**
	* Shortcut for starting form row, needed to build row with several inlined inputs
	*/
	function row_start($extra = array()) {
		$func = function($extra, $r, $_this) {
			$_this->_stacked_mode_on = true;
			$extra['errors'] = common()->_get_error_messages();
			return $_this->_row_html('', array('only_row_start' => 1) + (array)$extra);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	* Paired with row_start
	*/
	function row_end($extra = array()) {
		$func = function($extra, $r, $_this) {
			$_this->_stacked_mode_on = false;
			return $_this->_row_html('', array('only_row_end' => 1) + (array)$extra);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	* Shortcut for starting navbar, needed to test div_box containers
	*/
	function navbar_start($extra = array()) {
		$func = function($extra, $r, $_this) {
			$_this->_params['no_form'] = true;
			$_this->_stacked_mode_on = true;
			return '<div class="navbar span2"><div class="navbar-inner"><ul class="nav">';
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	* Paired with navbar_start
	*/
	function navbar_end($extra = array()) {
		$func = function($extra, $r, $_this) {
			$_this->_stacked_mode_on = false;
			return '</ul></div></div>';
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	* Bootstrap-compatible html wrapper for any custom content inside.
	* Can be used for inline rich editor editing with ckeditor, enable with: $extra = array('ckeditor' => true)
	*/
	function container($text, $desc = '', $extra = array(), $replace = array()) {
		$text = strval($text);
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['text'] = $text;
		$extra['desc'] = $extra['desc'] ?: ($desc ?: '');

		$func = function($extra, $r, $_this) {
			$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
			$extra['contenteditable'] = isset($extra['ckeditor']) ? 'true' : 'false';
			$extra['id'] = $extra['id'] ?: 'content_editable';
			$extra['name'] = $name;
			$extra['desc'] = !$_this->_params['no_label'] ? $extra['desc'] : '';

			$attrs_names = array('id','contenteditable','style','class');
			return $_this->_row_html(isset($extra['ckeditor']) ? '<div '.$_this->_attrs($extra, $attrs_names).'>'.$extra['text'].'</div>' : $extra['text'], $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	* General input
	*/
	function input($name, $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$func = function($extra, $r, $_this) {
			$extra['errors'] = common()->_get_error_messages();
			$extra['id'] = $extra['id'] ?: $extra['name'];
			$extra['placeholder'] = t($extra['placeholder'] ?: $extra['desc']);
			$extra['value'] = isset($extra['value']) ? $extra['value'] : $r[$extra['name']];
			// Compatibility with filter
			if (!strlen($extra['value'])) {
				if (isset($extra['selected'])) {
					$extra['value'] = $extra['selected'];
				} elseif (isset($_this->_params['selected'])) {
					$extra['value'] = $_this->_params['selected'][$extra['name']];
				}
			}
			$extra['type'] = $extra['type'] ?: 'text';
			$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
			$extra['inline_help'] = isset($extra['errors'][$extra['name']]) ? $extra['errors'][$extra['name']] : $extra['inline_help'];
			$extra['class'] = 'form-control '.$_this->_prepare_css_class('', $r[$extra['name']], $extra);
			// Supported: mini, small, medium, large, xlarge, xxlarge
			if ($extra['sizing']) {
				$extra['class'] .= ' input-'.$extra['sizing'];
			}
			$vr = $_this->_validate_rules[$extra['name']];
			foreach ((array)$vr as $rule) {
				if ($rule[0] == 'required') {
					$extra['required'] = 1;
					break;
				}
			}
			// http://stackoverflow.com/questions/10281962/is-it-minlength-in-html5
			if ($vr['min_length'] && !isset($extra['pattern'])) {
				$extra['pattern'] = '.{'.$vr['min_length'][1].','.($vr['max_length'] ? $vr['max_length'][1] : '').'}';
			}
			if ($vr['max_length'] && !isset($extra['maxlength'])) {
				$extra['maxlength'] = $vr['max_length'][1];
			}
			if ($_this->_params['no_label']) {
				$extra['desc'] = '';
			}
			$attrs_names = array('name','type','id','class','style','placeholder','value','data','size','maxlength','pattern','disabled','required','autocomplete');
			return $_this->_row_html('<input '.$_this->_attrs($extra, $attrs_names).'>', $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	*/
	function textarea($name, $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$func = function($extra, $r, $_this) {
			$extra['errors'] = common()->_get_error_messages();
			$extra['id'] = $extra['id'] ? $extra['id'] : $extra['name'];
			$extra['placeholder'] = t(isset($extra['placeholder']) ? $extra['placeholder'] : $extra['desc']);
			$value = isset($extra['value']) ? $extra['value'] : $r[$extra['name']];
			// Compatibility with filter
			if (!strlen($value)) {
				if (isset($extra['selected'])) {
					$value = $extra['selected'];
				} elseif (isset($_this->_params['selected'])) {
					$value = $_this->_params['selected'][$extra['name']];
				}
			}
			$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
			$extra['inline_help'] = isset($extra['errors'][$extra['name']]) ? $extra['errors'][$extra['name']] : $extra['inline_help'];
			$extra['contenteditable'] = $extra['contenteditable'] ?: 'true';
			$extra['class'] = 'ckeditor form-control '.$_this->_prepare_css_class('', $r[$extra['name']], $extra);
			if ($_this->_params['no_label']) {
				$extra['desc'] = '';
			}
			$attrs_names = array('id','name','placeholder','contenteditable','class','style','cols','rows');
			return $_this->_row_html('<textarea '.$_this->_attrs($extra, $attrs_names).'>'.(!isset($extra['no_escape']) ? $_this->_htmlchars($value) : $value).'</textarea>', $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	* Embedding ckeditor (http://ckeditor.com/) with kcfinder (http://kcfinder.sunhater.com/).
	* Best way to include it into project: 
	*
	* git submodule add https://github.com/yfix/ckeditor-releases.git www/ckeditor/ && cd www/ckeditor/ && git checkout latest/full
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
	function hidden($name, $extra = array(), $replace = array()) {
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['name'] = $extra['name'] ?: $name;
		$func = function($extra, $r, $_this) {
			$extra['id'] = $extra['id'] ? $extra['id'] : $extra['name'];
			$extra['value'] = isset($extra['value']) ? $extra['value'] : $r[$extra['name']];
			$extra['type'] = 'hidden';

			$attrs_names = array('type','id','name','value','data');
			$body = '<input '.$_this->_attrs($extra, $attrs_names).'>';
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	*/
	function text($name, $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = 'text';
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	*/
	function password($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
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
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = 'file';
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	*/
	function button($name, $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$desc) {
			$desc = ucfirst(str_replace('_', ' ', $name));
		}
		$extra['type'] = 'button';
		$extra['value'] = $desc;
		if (!$extra['class']) {
			$extra['class'] = 'btn';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* Custom
	*/
	function login($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = $extra['type'] ?: 'text';
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
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
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
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
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
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
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
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		}
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
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
	* Alias
	*/
	function phone($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['type'] = 'tel';
		if (!$name) {
			$name = 'phone';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function time($name = '', $desc = '', $extra = array(), $replace = array()) {
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
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'active');
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$func = function($extra, $r, $_this) {
			if (!$extra['items']) {
				$extra['items'] = array(
					'0' => '<span class="label label-warning">'.t('Disabled').'</span>',
					'1' => '<span class="label label-success">'.t('Active').'</span>',
				);
			}
			$extra['errors'] = common()->_get_error_messages();
			$extra['inline_help'] = isset($extra['errors'][$extra['name']]) ? $extra['errors'][$extra['name']] : $extra['inline_help'];
			$extra['desc'] = !$_this->_params['no_label'] ? $extra['desc'] : '';
			$extra['id'] = $extra['name'];

			$selected = $r[$extra['name']];
			if (isset($extra['selected'])) {
				$selected = $extra['selected'];
			} elseif (isset($_this->_params['selected'])) {
				$selected = $_this->_params['selected'][$extra['name']];
			}
			return $_this->_row_html(_class('html_controls')->radio_box($extra['name'], $extra['items'], $selected, false, 2, '', false), $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	*/
	function allow_deny_box($name = '', $desc = '', $extra = array(), $replace = array()) {
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
	function submit($name = '', $value = '', $extra = array(), $replace = array()) {
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		}
		if (is_array($value) && empty($extra)) {
			$extra = $value;
			$value = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['value'] = isset($extra['value']) ? $extra['value'] : ($value ?: 'Save');
		$func = function($extra, $r, $_this) {
			$extra['errors'] = common()->_get_error_messages();
			$extra['id'] = $extra['id'] ?: ($extra['name'] ?: strtolower($extra['value']));
			$extra['link_url'] = $extra['link_url'] ? (isset($r[$extra['link_url']]) ? $r[$extra['link_url']] : $extra['link_url']) : '';
			if (preg_match('~^[a-z0-9_-]+$~ims', $extra['link_url'])) {
				$extra['link_url'] = '';
			}
			$extra['link_name'] = $extra['link_name'] ?: '';
			$extra['class'] = 'btn btn-primary '.$_this->_prepare_css_class('', $r[$extra['name']], $extra);
			$extra['inline_help'] = isset($extra['errors'][$extra['name']]) ? $extra['errors'][$extra['name']] : $extra['inline_help'];
			$extra['value'] = t($extra['value']);
			$extra['desc'] = ''; // We do not need label here
			$extra['type'] = 'submit';

			$attrs_names = array('type','name','id','class','style','value','disabled');
			return $_this->_row_html('<input '.$_this->_attrs($extra, $attrs_names).'>', $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
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
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['link_url'] = $name;
		$extra['link_name'] = $desc ?: 'Back';
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
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['link_url'] = $name;
		$extra['link_name'] = $desc ?: 'Clear';
		return $this->submit($name, $desc, $extra, $replace);
	}

	/**
	*/
	function info($name, $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$func = function($extra, $r, $_this) {
			$extra['errors'] = common()->_get_error_messages();
			$extra['inline_help'] = isset($extra['errors'][$extra['name']]) ? $extra['errors'][$extra['name']] : $extra['inline_help'];
			$extra['desc'] = !$extra['no_label'] && !$_this->_params['no_label'] ? $extra['desc'] : '';

			$value = $r[$extra['name']];
			if (is_array($extra['data'])) {
				if (isset($extra['data'][$value])) {
					$value = $extra['data'][$value];
				} elseif (isset($extra['data'][$extra['name']])) {
					$value = $extra['data'][$extra['name']];
				}
			}
			$value = !isset($extra['no_escape']) ? $_this->_htmlchars($value) : $value;

			$content = '';
			if ($extra['link']) {
				$extra['class'] = $extra['class'] ?: 'btn btn-mini btn-xs';
				$content = '<a href="'.$extra['link'].'" class="'.$extra['class'].'">'.$value.'</a>';
			} else {
				$extra['class'] = $extra['class'] ?: 'label label-info';
				$content = '<span class="'.$_this->_prepare_css_class($extra['class'], $r[$extra['name']], $extra).'">'.$value.'</span>';
			}
			return $_this->_row_html($content, $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	*/
	function user_info($name = '', $desc = '', $extra = array(), $replace = array()) {
		$name = 'user_name';
		$user_id = $this->_replace['user_id'];

		$this->_replace[$name] = db()->get_one('SELECT CONCAT(login," ",email) AS user_name FROM '.db('user').' WHERE id='.intval($user_id));

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
	function _get_selected($name, $extra, $r) {
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
	function _html_control($name, $values, $extra = array(), $replace = array(), $func_html_control = '') {
		$extra['name'] = $extra['name'] ?: $name;
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$extra['values'] = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		$extra['func_html_control'] = $extra['func_html_control'] ?: $func_html_control;
		$func = function($extra, $r, $_this) {
			$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
			$extra['errors'] = common()->_get_error_messages();
			$extra['inline_help'] = isset($extra['errors'][$extra['name']]) ? $extra['errors'][$extra['name']] : $extra['inline_help'];
			$extra['selected'] = $_this->_get_selected($extra['name'], $extra, $r);
			$extra['id'] = $extra['name'];

			$func = $extra['func_html_control'];
			$content = _class('html_controls')->$func($extra);
			if ($extra['no_label'] || $_this->_params['no_label']) {
				$extra['desc'] = '';
			}
			return $_this->_row_html($content, $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	*/
	function box($name, $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$func = function($extra, $r, $_this) {
			$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
			$extra['errors'] = common()->_get_error_messages();
			$extra['inline_help'] = isset($extra['errors'][$extra['name']]) ? $extra['errors'][$extra['name']] : $extra['inline_help'];
			$extra['values'] = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
			$extra['selected'] = $_this->_get_selected($extra['name'], $extra, $r);
			$extra['id'] = $extra['name'];

			return $_this->_row_html($r[$extra['name']], $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
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
	function check_box($name, $value = '', $extra = array(), $replace = array()) {
		return $this->_html_control($name, $value, $extra, $replace, 'check_box');
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
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'birth';
		}
		return $this->date_box($name, $values, $extra, $replace);
	}

	/**
	*/
	function div_box($name, $values, $extra = array(), $replace = array()) {
		return $this->_html_control($name, $values, $extra, $replace, 'div_box');
	}

	/**
	*/
	function list_box($name, $values, $extra = array(), $replace = array()) {
		return $this->_html_control($name, $values, $extra, $replace, 'list_box');
	}

	/**
	*/
	function country_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'country';
		}
		$data = array();
#		$a = main()->get_data('countries');
		$a = db()->get_all('SELECT * FROM '.db('countries').' WHERE active="1" ORDER BY name ASC');
		foreach ((array)$a as $v) {
			$data[$v['code']] = '<i class="bfh-flag-'.$v['code'].'"></i> '. $v['name'].' ['.strtoupper($v['code']).']';
		}
		return $this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function region_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'region';
		}
		$data = array();
		foreach ((array)main()->get_data('regions') as $v) {
			$data[$v['code']] = $v['name'].' ['.$v['code'].']';
		}
		return $this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function currency_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'currency';
		}
		$data = array();
		foreach ((array)main()->get_data('currencies') as $v) {
			$data[$v['id']] = $v['sign'].' &nbsp; '. $v['name'].' ['.$v['id'].']';
		}
		return $this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function language_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'language';
		}
		$data = array();
// TODO: move this into main()->get_data('languages_new')
		$a = db()->get_all('SELECT * FROM '.db('languages').' WHERE active="1" ORDER BY native ASC');
		foreach ((array)$a as $v) {
			$data[$v['code']] = '<i class="bfh-flag-'.$v['code'].'"></i> '. $v['native'].' ['.$v['code'].']';
		}
		return $this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function timezone_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'timezone';
		}
		$data = array();
// TODO: move this into main()->get_data('timezones')
		$a = db()->get_all('SELECT * FROM '.db('timezones').' WHERE active="1" ORDER BY offset ASC, name ASC');
		foreach ((array)$a as $v) {
			$data[$v['code']] = $v['name'].' ['.$v['offset'].'] ['.$v['code'].']';
		}
		return $this->list_box($name, $data, $extra, $replace);
	}

	/**
	* Image upload
	*/
	function image($name, $desc = '', $extra = array(), $replace = array()) {
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
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'method';
		}
		$data = array();
// TODO
		return $this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function template_select_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'template';
		}
		$data = array();
// TODO
		return $this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function location_select_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'location';
		}
		$data = array();
// TODO
		return $this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function icon_select_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($name) && empty($extra)) {
			$extra = $name;
			$name = '';
		} elseif (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'icon';
		}
		$data = array();
// TODO
		return $this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function captcha($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'captcha');
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$func = function($extra, $r, $_this) {
			$extra['errors'] = common()->_get_error_messages();
			$extra['inline_help'] = isset($extra['errors'][$extra['name']]) ? $extra['errors'][$extra['name']] : $extra['inline_help'];
			$extra['id'] = $extra['name'];
			return $_this->_row_html(_class('captcha')->show_block('./?object=dynamic&action=captcha_image'), $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	*/
	function custom_fields($name, $custom_fields, $extra = array(), $replace = array()) {
		$extra['name'] = $extra['name'] ?: $name;
		$extra['custom_fields'] = $custom_fields;
		$func = function($extra, $r, $_this) {
			$custom_fields = explode(',', $extra['custom_fields']);
			$sub_array_name = $extra['sub_array'] ?: 'custom';
			$custom_info = $this->_attrs_string2array($r[$extra['name']]);

			$body = array();
			$this->_chained_mode = false;
			foreach ((array)$custom_fields as $field_name) {
				if (empty($field_name)) {
					continue;
				}
				$str = _class('html_controls')->input(array(
					'id'	=> 'custom_'.$field_name.'_'.$r['id'],
					'name'	=> $sub_array_name.'['.$field_name.']', // Example: custom[color]
					'desc'	=> $field_name,
					'value'	=> $custom_info[$field_name],
				));
				$desc = ucfirst(str_replace('_', ' ', $field_name)).' [Custom]';
				$body[] = $this->container($str, $desc);
			}
			$this->_chained_mode = true;
			return implode(PHP_EOL, $body);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link($name, $link, $extra = array(), $replace = array()) {
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['link'] = $extra['link'] ?: $link;
		$func = function($extra, $r, $_this) {
			$link = $extra['link'];
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
			return ' <a href="'.$link_url.'" class="btn btn-mini btn-xs'.($extra['class'] ? ' '.$extra['class'] : '').'"><i class="'.$icon.'"></i> '.t($extra['name']).'</a> ';
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
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
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'active');
		$extra['link'] = $extra['link'] ?: $link;
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$func = function($extra, $r, $_this) {
			$link = $extra['link'];
			if (!$link) {
				$link = 'active_link';
				if (!isset($r['active_link']) && isset($r['active_url'])) {
					$link = 'active_url';
				}
			}
			$link_url = isset($r[$link]) ? $r[$link] : $link;
			$is_active = $r[$extra['name']];
// TODO: use CSS abstraction layer
			return ' <a href="'.$link_url.'" class="change_active">'
						.($is_active ? '<span class="label label-success">'.t('Active').'</span>' : '<span class="label label-warning">'.t('Disabled').'</span>')
					.'</a> ';
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra);
			return $this;
		}
		return $func($extra, $replace, $this);
	}

	/**
	*/
	function validate($validate_rules = array(), $post = array()) {
		$form_global_validate = isset($this->_params['validate']) ? $this->_params['validate'] : $this->_replace['validate'];
		foreach ((array)$form_global_validate as $name => $rules) {
			$this->_validate_rules[$name] = $rules;
		}
		foreach ((array)$this->_body as $v) {
			$_extra = $v['extra'];
			if (isset($_extra['validate']) && isset($_extra['name'])) {
				$this->_validate_rules[$_extra['name']] = $_extra['validate'];
			}
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
	*/
	function _validate_rules_array_from_raw($raw = '') {
		$rules = array();
		// At first, we merging all rules sets variants into one array
		if (is_string($raw)) {
			foreach((array)explode('|', $raw) as $_item) {
				$rules[] = array($_item, null);
			}
		} elseif (is_array($raw)) {
			foreach((array)$raw as $_raw) {
				if (is_string($_raw)) {
					foreach((array)explode('|', $_raw) as $_item) {
						$rules[] = array($_item, null);
					}
				} elseif (is_callable($_raw)) {
					$rules[] = array($_raw, null);
				}
			}
		} elseif (is_callable($raw)) {
			$rules[] = array($raw, null);
		}
		return $rules;
	}

	/**
	* Examples of validate rules setting:
	* 	'name1' => 'trim|required',
	* 	'name2' => array('trim', 'required'),
	* 	'name3' => array('trim|required', 'other_rule|other_rule2|other_rule3'),
	* 	'name4' => array('trim|required', function() { return true; } ),
	* 	'name5' => array('trim', 'required', function() { return true; } ),
	* 	'__before__' => 'trim',
	* 	'__after__' => 'some_method2|some_method3',
	*/
	function _validate_rules_cleanup($validate_rules = array()) {
		// Add these rules to all validation rules, before them
		$_name = '__before__';
		$all_before = array();
		if (isset($validate_rules[$_name])) {
			$all_before = (array)$this->_validate_rules_array_from_raw($validate_rules[$_name]);
			unset($validate_rules[$_name]);
		}

		// Add these rules to all validation rules, after them
		$_name = '__after__';
		$all_after = array();
		if (isset($validate_rules[$_name])) {
			$all_after = (array)$this->_validate_rules_array_from_raw($validate_rules[$_name]);
			unset($validate_rules[$_name]);
		}
		unset($_name);

		$out = array();
		foreach ((array)$validate_rules as $name => $raw) {
			$rules = (array)$this->_validate_rules_array_from_raw($raw);
			if ($all_before) {
				$tmp = $all_before;
				foreach((array)$rules as $_item) {
					$tmp[] = $_item;
				}
				$rules = $tmp;
				unset($tmp);
			}
			if ($all_after) {
				$tmp = $rules;
				foreach((array)$all_after as $_item) {
					$tmp[] = $_item;
				}
				$rules = $tmp;
				unset($tmp);
			}
			// Here we do last parse of the rules params like 'matches[user.email]' into rule item array second element
			foreach ((array)$rules as $k => $rule) {
				if (!is_string($rule[0])) {
					continue;
				}
				$val = trim($rule[0]);
				$param = null;
				// Parsing these: min_length[6], matches[form_item], is_unique[table.field]
				$pos = strpos($val, '[');
				if ($pos !== false) {
					$param = trim(trim(substr($val, $pos), ']['));
					$val = trim(substr($val, 0, $pos));
				}
				if (!is_callable($val) && empty($val)) {
					unset($rules[$k]);
					continue;
				}
				$rules[$k] = array(
					0	=> $val,
					1	=> $param,
				);
			}
			if ($rules) {
				$out[$name] = array_values($rules); // array_values needed here to make array keys straight, unit tests will pass fine
			}
		}
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
				$func = $rule[0];
				$param = $rule[1];
				// PHP pure function, from core or user
				if (is_string($func) && function_exists($func)) {
					$data[$name] = $func($data[$name]);
				} elseif (is_callable($func)) {
					$is_ok = $func($data[$name], null, $data);
				} else {
					$is_ok = _class('validate')->$func($data[$name], array('param' => $param), $data, $error_msg);
					if (!$is_ok && empty($error_msg)) {
						$error_msg = t('form_validate_'.$func, array('%field' => $name, '%param' => $param));
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
		if (!$table || !$type || empty($_POST)) {
			return $this;
		}
		$validate_ok = ($this->_validate_ok || $extra['force']);
		if (!$validate_ok) {
			if ($extra['on_validate_error']) {
				$func = $extra['on_validate_error'];
				$func($data, $table, $fields, $type, $extra);
			}
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
