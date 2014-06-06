<?php

/**
* Form2 high-level generator and handler, mostly using bootstrap html/css framework
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_form2 {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args, $this->_chained_mode);
	}

	/**
	*/
	function _extend($name, $func) {
		$this->_extend[$name] = $func;
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
		if ($replace && is_string($replace)) {
			$sql = $replace;
			$this->_sql = $sql;
			$replace = db()->get_2d($sql);
		}
		if (!$params['no_chained_mode']) {
			$this->_chained_mode = true;
		}
		$this->_replace = $replace;
		$this->_params = $params;
		return $this;
	}

	/**
	*/
	function array_to_form($a = array(), $params = array(), $replace = array()) {
		$this->_params = $params + (array)$this->_params;
		$this->_replace = $replace + (array)$this->_replace;
		// Example of row: array('text', 'login', array('class' => 'input-medium'))
		foreach ((array)$a as $v) {
			$func = '';
			if (is_string($v)) {
				$func = $v;
				$v = array();
			} elseif (is_array($v)) {
				$func = $v[0];
			}
			if (!$func || !method_exists($this, $func)) {
				continue;
			}
			$this->$func($v[1], $v[2], $v[3], $v[4], $v[5]);
		}
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
		// Allow to pass extra params like this: param1=val1;param2=val2
		if (is_string($extra)) {
			$extra = trim($extra);
			if (false !== strpos($extra, ';') && false !== strpos($extra, '=')) {
				$extra = _attrs_string2array($extra);
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
	function auto($table = '', $id = '', $params = array()) {
		return _class('form2_auto', 'classes/form2/')->auto($table, $id, $params, $this);
	}

	/**
	*/
	function _get_extra_override($form_id = '') {
		if (!strlen($form_id)) {
			return array();
		}
		$extra_override = array();
		// Data from database have highest priority, so we init it first
		$all_attrs_override = main()->get_data('form_attributes');
		$extra_override = $all_attrs_override[$form_id];
		// Search for override params inside shared files
		$suffix = $form_id.'.form.php';
		$paths = array(
			'yf_main'			=> YF_PATH. 'share/form/'.$suffix,
			'yf_plugins'		=> YF_PATH. 'plugins/*/share/form/'.$suffix,
			'project_config'	=> CONFIG_PATH. 'share/form/'.$suffix,
			'project_main'		=> PROJECT_PATH. 'share/form/'.$suffix,
			'project_plugins'	=> PROJECT_PATH. 'plugins/*/share/form/'.$suffix,
		);
		if (SITE_PATH != PROJECT_PATH) {
			$paths['site_main'] = SITE_PATH. 'share/form/'.$suffix;
		}
		foreach ((array)$paths as $glob) {
			foreach (glob($glob) as $f) {
				include $f;
				foreach ((array)$data as $field => $attrs) {
					$extra_override[$field] = (array)$extra_override[$field] + (array)$attrs;
				}
			}
		}
		return $extra_override;
	}

	/**
	* Render result form html, gathered by row functions
	* Params here not required, but if provided - will be passed to form_begin()
	*/
	function render($extra = array(), $replace = array()) {
		if (isset($this->_rendered)) {
			return $this->_rendered;
		}
		if (DEBUG_MODE) {
			$ts = microtime(true);
		}
		$on_before_render = isset($extra['on_before_render']) ? $extra['on_before_render'] : $this->_on['on_before_render'];
		if (is_callable($on_before_render)) {
			$on_before_render($extra, $replace, $this);
		}
		if (main()->is_post()) {
			$on_post = isset($extra['on_post']) ? $extra['on_post'] : $this->_on['on_post'];
			if (is_callable($on_post)) {
				$on_post($extra, $replace, $this);
			}
			$v = $this->_validate;
			if (isset($v) && is_callable($v['func'])) {
				$func = $v['func'];
				$func($v['validate_rules'], $v['post'], $v['extra'], $this);
			}
			$up = $this->_db_change_if_ok;
			if (isset($up) && is_callable($up['func'])) {
				$func = $up['func'];
				$func($up['table'], $up['fields'], $up['type'], $up['extra'], $this);
			}
		}
		if (!is_array($this->_body)) {
			$this->_body = array();
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra_override = array();
		$form_id = isset($this->_replace['__form_id__']) ? $this->_replace['__form_id__'] : $this->_form_id;
		if ($form_id) {
			$extra_override = $this->_get_extra_override($form_id);
		}
		$r = (array)$this->_replace + (array)$replace;

		if (!$extra['no_form'] && !$this->_params['no_form']) {
			// Call these methods, if not done yet, save 2 api calls
			if (!isset($this->_body['form_begin'])) {
				$this->form_begin('', '', $extra + (array)$extra_override['form_begin'], $r);
			}
			if (!isset($this->_body['form_end'])) {
				$this->form_end($extra + (array)$extra_override['form_end'], $r);
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
					$e[] = '<div class="alert alert-error alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>'.$msg.'</div>';
				}
				array_unshift($this->_body, implode(PHP_EOL, $e));
			}
		}
		$tabbed_mode = false;
		$tabbed_buffer = array();
		$tabs = array();
		$tabs_name = '';
		$tabs_container = '';

		foreach ((array)$this->_body as $k => $v) {
			if (!is_array($v)) {
				continue;
			}
			$_extra = (array)$v['extra'] + (array)$extra_override[$v['extra']['name']];
			$_replace = (array)$r + (array)$v['replace'];
			$func = $v['func'];
			if ($this->_stacked_mode_on) {
				$_extra['stacked'] = true;
			}
			// Callback to decide if we need to show this field or not
			if (isset($_extra['display_func']) && is_callable($_extra['display_func'])) {
				$_display_allowed = $_extra['display_func']($_extra, $_replace, $this);
				if (!$_display_allowed) {
					$this->_body[$k] = '';
					continue;
				}
			}
			if (DEBUG_MODE) {
				$_debug_fields[$k] = array(
					'name'	=> $v['name'],
					'extra'	=> $_extra,
				);
			}
			$this->_body[$k] = $func($_extra, $_replace, $this);

			if ($this->_tabbed_mode_on) {
				$tabbed_mode = true;
				$tabbed_buffer[$k] = $this->_body[$k];
				if ($v['name'] == 'tab_start') {
					$this->_tabs_counter++;
					$tabs_name = $this->_tabs_name ?: 'tabs_'.$this->_tabs_counter;
				}
				if ($v['name'] == 'tab_start' && !$tabs_container) {
					$tabs_container = $k;
					$this->_body[$k] = '__TAB_START__';
				} else {
					unset($this->_body[$k]);
				}
			} elseif ($tabbed_mode) { // switch off
				if (!$this->_tabbed_mode_on) {
					$tabbed_mode = false;
				}
				$tabs[$tabs_name] = implode(PHP_EOL, $tabbed_buffer);
				$tabbed_buffer = array();
				$tabs_name = '';
			}
		}
		if ($tabbed_buffer) {
			$tabs['tab_last'] = implode(PHP_EOL, $tabbed_buffer);
			$tabbed_buffer = array();
		}
		if ($tabs) {
			$this->_body[$tabs_container] = _class('html')->tabs($tabs, $this->_params['tabs']);
		}
		$this->_rendered = implode(PHP_EOL, $this->_body);

		$css_framework = $extra['css_framework'] ?: ($this->_params['css_framework'] ?: conf('css_framework'));
		$extra['css_framework'] = $css_framework;
		$this->_rendered = _class('html5_framework')->form_render_out($this->_rendered, $extra, $r, $this);

		$on_after_render = isset($extra['on_after_render']) ? $extra['on_after_render'] : $this->_on['on_after_render'];
		if (is_callable($on_after_render)) {
			$on_after_render($extra, $replace, $this);
		}
		if (DEBUG_MODE) {
			debug('form2[]', array(
				'params'	=> $this->_params,
				'fields'	=> $_debug_fields,
				'time'		=> round(microtime(true) - $ts, 5),
				'trace'		=> main()->trace_string(),
			));
		}
		return $this->_rendered;
	}

	/**
	*/
	function form_begin($name = '', $method = '', $extra = array(), $replace = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
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
			if (!isset($extra['action'])) {
				$extra['action'] = isset($r[$extra['name']]) ? $r[$extra['name']] : './?object='.$_GET['object'].'&action='.$_GET['action']. ($_GET['id'] ? '&id='.$_GET['id'] : ''). $_this->_params['links_add'];
			}
			$extra['action'] = process_url($extra['action'], true);
			$extra['class'] = $extra['class'] ?: 'form-horizontal';// col-md-6';
			if ($extra['class_add']) {
				$extra['class'] .= ' '.$extra['class_add'];
			}
			$extra['autocomplete'] = $extra['autocomplete'] ?: true;

			$body = '<form'._attrs($extra, array('method','action','class','style','id','name','autocomplete','enctype','novalidate')).'>'.PHP_EOL;
			$_this->_fieldset_mode_on = true;
			$body .= '<fieldset'._attrs($extra['fieldset'], array('class','style','id','name')).'>';
			if ($extra['legend']) {
				$body .= PHP_EOL.'<legend>'._htmlchars(t($extra['legend'])).'</legend>'.PHP_EOL;
			}
			return $body;
		};
		if ($this->_chained_mode) {
			$this->_body[__FUNCTION__] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	*/
	function form_end($extra = array(), $replace = array()) {
		if (!is_array($extra)) {
			$extra = array();
		}
		$func = function($extra, $r, $_this) {
			$_this->_fieldset_mode_on = false;
			$body .= '</fieldset>'.PHP_EOL;
			$body .= '</form>'.PHP_EOL;
			return $body;
		};
		if ($this->_chained_mode) {
			$this->_body[__FUNCTION__] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	* Shortcut for adding fieldset
	*/
	function fieldset_start($name = '', $extra = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		$extra['name'] = $extra['name'] ?: $name;
		$func = function($extra, $r, $_this) {
			if ($_this->_fieldset_mode_on) {
				$body = '</fieldset>'.PHP_EOL;
			} else {
				$_this->_fieldset_mode_on = true;
			}
			$body .= '<fieldset'._attrs($extra, array('class','style','id','name')).'>';
			if ($extra['legend']) {
				$body .= PHP_EOL.'<legend>'._htmlchars(t($extra['legend'])).'</legend>'.PHP_EOL;
			}
			return $body;
		};
		if ($this->_chained_mode || $extra['chained_mode']) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	* Paired with fieldset_start
	*/
	function fieldset_end($extra = array()) {
		$func = function($extra, $r, $_this) {
			if ($_this->_fieldset_mode_on) {
				$_this->_fieldset_mode_on = false;
				return '</fieldset>'.PHP_EOL;
			}
		};
		if ($this->_chained_mode || $extra['chained_mode']) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	* Shortcut for starting form row, needed to build row with several inlined inputs
	*/
	function row_start($extra = array()) {
		$func = function($extra, $r, $_this) {
			// auto-close row_end(), if not called implicitely
			if ($_this->_stacked_mode_on) {
				$_this->row_end();
			}
			$_this->_stacked_mode_on = true;
			$_this->_prepare_inline_error($extra);
			return $_this->_row_html('', array('only_row_start' => 1) + (array)$extra);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
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
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	* Shortcut for making tabbable form
	*/
	function tab_start($name = '', $extra = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		$extra['name'] = $extra['name'] ?: $name;
		$func = function($extra, $r, $_this) {
			// auto-close tab_end(), if not called implicitely
			if ($_this->_tabbed_mode_on) {
				$_this->tab_end();
			}
			$_this->_tabbed_mode_on = true;
			$_this->_tabs_name = $extra['name'];
			$_this->_tabs_extra = $extra;
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	* Paired with tab_start
	*/
	function tab_end($extra = array()) {
		$func = function($extra, $r, $_this) {
			$_this->_tabbed_mode_on = false;
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	*/
	function _row_html($content, $extra = array(), $replace = array()) {
		if (!strlen($content) && ($extra['hide_empty'] || $this->_params['hide_empty'])) {
			return '';
		}
		if ($this->_params['only_content']) {
			return $content;
		}
		if ($this->_params['dd_mode']) {
			return $this->_dd_row_html($content, $extra, $replace);
		}
		if ($extra['form_input_no_append'] || $this->_params['form_input_no_append'] || conf('form_input_no_append')) {
			$extra['append'] = '';
			$extra['prepend'] = '';
		}
		if ($this->_stacked_mode_on) {
			$extra['stacked'] = true;
		}
		$css_framework = $extra['css_framework'] ?: ($this->_params['css_framework'] ?: conf('css_framework'));
		$extra['css_framework'] = $css_framework;
		return _class('html5_framework')->form_row($content, $extra, $replace, $this);
	}

	/**
	* Generate form row using dl>dt,dd html tags. Useful for user profle and other simple table-like content
	*/
	function _dd_row_html($content, $extra = array(), $replace = array()) {
		if ($extra['hide_empty'] && !strlen($content)) {
			return '';
		}
		if ($this->_stacked_mode_on) {
			$extra['stacked'] = true;
		}
		$css_framework = $extra['css_framework'] ?: ($this->_params['css_framework'] ?: conf('css_framework'));
		$extra['css_framework'] = $css_framework;
		return _class('html5_framework')->form_dd_row($content, $extra, $replace, $this);
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
	function _prepare_custom_attr($attr = array()) {
		$body = array();
		foreach ((array)$attr as $k => $v) {
			$body[] = _htmlchars($k).'="'._htmlchars($v).'"';
		}
		return implode(" ", $body);
	}

	/**
	*/
	function _prepare_css_class($default_class = '', $value = '', &$extra) {
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
		// Needed to not modify original class of element (sometimes complex), but just add css class there
		if (isset($extra['class_add'])) {
			$_css_class_add = is_array($extra['class_add']) && isset($extra['class_add'][$value]) ? $extra['class_add'][$value] : $extra['class_add'];
			if ($_css_class_add) {
				$css_class .= ' '.$_css_class_add;
			}
		}
		if ($this->_params['big_labels']) {
			$css_class .= ' labels-big';
		}
		return $css_class ? ' '.$css_class : '';
	}

	/**
	*/
	function _prepare_id(&$extra, $default = '') {
		$out = $extra['id'];
		if (!$out) {
			$out = $extra['name'];
			$is_html_array = (false !== strpos($out, '['));
			if ($is_html_array) {
				$out = str_replace(array('[',']'), array('_',''), trim($out,']['));
			}
		}
		!$out && $out = $default;
		return $out;
	}

	/**
	*/
	function _prepare_desc(&$extra, $input = '') {
		$out = $extra['desc'];
		!$out && $out = $input;
		if (!$out) {
			$out = ucfirst(str_replace('_', ' ', $extra['name']));
			$is_html_array = (false !== strpos($out, '['));
			if ($is_html_array) {
				$out = str_replace(array('[',']'), array('.',''), trim($out,']['));
			}
		}
		return $out;
	}

	/**
	*/
	function _prepare_value(&$extra, &$replace, &$params) {
		$name = $extra['name'];
		$is_html_array = (false !== strpos($name, '['));
		if ($is_html_array) {
			$name_dots = str_replace(array('[',']'), array('.',''), trim($name,']['));
			$replace_dots = array_dot($replace);
		}
		$value = '';
		if ($extra['value']) {
			$value = $extra['value'];
		} elseif ($replace[$name]) {
			$value = $replace[$name];
		} elseif ($is_html_array && $replace_dots[$name_dots]) {
			$value = $replace_dots[$name_dots];
		} elseif ($extra['selected']) {
			$value = $extra['selected'];
		} elseif ($params['selected'][$name]) {
			$value = $params['selected'][$name];
		} elseif ($is_html_array && $params['selected'][$name_dots]) {
			$value = $params['selected'][$name_dots];
		}
		return $value;
	}

	/**
	*/
	function _prepare_selected($name, &$extra, &$r) {
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
	function _prepare_inline_error(&$extra, $name = '') {
		$name = $name ?: $extra['name'];
		$is_html_array = (false !== strpos($name, '['));
		if ($is_html_array) {
			$name_orig = $name;
			$name = str_replace(array('[',']'), array('.',''), trim($name,']['));
		}
		$extra['errors'] = common()->_get_error_messages();
		if (isset($extra['errors'][$name])) {
			$extra['inline_help'] = $extra['errors'][$name];
		}
	}

	/**
	* Bootstrap-compatible html wrapper for any custom content inside.
	* Can be used for inline rich editor editing with ckeditor, enable with: $extra = array('ckeditor' => true)
	*/
	function container($text, $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		$text = strval($text);
		$extra['text'] = $text;
		$extra['desc'] = $this->_prepare_desc($extra, $desc);

		$func = function($extra, $r, $_this) {
			$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
			$extra['contenteditable'] = isset($extra['ckeditor']) ? 'true' : 'false';
			$extra['id'] = $_this->_prepare_id($extra, 'content_editable');
			$extra['desc'] = !$_this->_params['no_label'] ? $extra['desc'] : '';

			$attrs_names = array('id','contenteditable','style','class','title');
			return $_this->_row_html(isset($extra['ckeditor']) ? '<div'._attrs($extra, $attrs_names).'>'.$extra['text'].'</div>' : $extra['text'], $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	* General input
	*/
	function input($name, $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['desc'] = $this->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $_this) {
			$_this->_prepare_inline_error($extra);
			$extra['id'] = $_this->_prepare_id($extra);
			$extra['placeholder'] = t($extra['placeholder'] ?: $extra['desc']);
			$extra['value'] = $_this->_prepare_value($extra, $r, $_this->_params);
			$extra['type'] = $extra['type'] ?: 'text';
			$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
			$extra['class'] = 'form-control'.$_this->_prepare_css_class('', $r[$extra['name']], $extra);
			// Supported: mini, small, medium, large, xlarge, xxlarge
			if ($extra['sizing']) {
				$extra['class'] .= ' input-'.$extra['sizing'];
			}
			if ($_this->_params['no_label']) {
				$extra['desc'] = '';
			}
			$extra = $_this->_input_assign_params_from_validate($extra);
			$attrs_names = array('name','type','id','class','style','placeholder','value','data','size','maxlength','pattern','disabled','required','autocomplete','accept','target','autofocus','title','min','max','step');
			return $_this->_row_html('<input'._attrs($extra, $attrs_names).'>', $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	*/
	function textarea($name, $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['desc'] = $this->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $_this) {
			$_this->_prepare_inline_error($extra);
			$extra['id'] = $_this->_prepare_id($extra);
			$extra['placeholder'] = t(isset($extra['placeholder']) ? $extra['placeholder'] : $extra['desc']);
			$extra['value'] = $_this->_prepare_value($extra, $r, $_this->_params);
			$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
			$extra['contenteditable'] = $extra['contenteditable'] ?: 'true';
			$extra['class'] = 'ckeditor form-control'.$_this->_prepare_css_class('', $r[$extra['name']], $extra);
			if ($_this->_params['no_label']) {
				$extra['desc'] = '';
			}
			$extra = $_this->_input_assign_params_from_validate($extra);
			$attrs_names = array('id','name','placeholder','contenteditable','class','style','cols','rows','title','required');
			return $_this->_row_html('<textarea'._attrs($extra, $attrs_names).'>'.(!isset($extra['no_escape']) ? _htmlchars($extra['value']) : $extra['value']).'</textarea>', $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
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
		return _class('form2_ckeditor', 'classes/form2/')->_ckeditor_html($extra, $replace, $this);
	}

	/**
	*/
	function _tinymce_html($extra = array(), $replace = array()) {
		return _class('form2_tinymce', 'classes/form2/')->_tinymce_html($extra, $replace, $this);
	}

	/**
	*/
	function _ace_editor_html($extra = array(), $replace = array()) {
		return _class('form2_ace_editor', 'classes/form2/')->_ace_editor_html($extra, $replace, $this);
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
			$extra['id'] = $_this->_prepare_id($extra);
			$extra['value'] = $_this->_prepare_value($extra, $r, $_this->_params);
			$extra['type'] = 'hidden';

			$attrs_names = array('type','id','name','value','data');
			return '<input'._attrs($extra, $attrs_names).'>';
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
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
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'password';
		}
		$extra['prepend'] = isset($extra['prepend']) ? $extra['prepend'] : '<i class="icon-key"></i>';
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
	function button($name, $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		if (!$desc) {
			$desc = ucfirst(str_replace('_', ' ', $name));
		}
		$extra['type'] = 'button';
		if (!isset($extra['value'])) {
			$extra['value'] = $desc;
		}
		$extra['value'] = t($extra['value']);
		if (!$extra['class']) {
			$extra['class'] = 'btn btn-default';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* Custom
	*/
	function login($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = $extra['type'] ?: 'text';
		$extra['prepend'] = '<i class="icon-user"></i>';
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'login';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function email($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'email';
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'email';
		}
		$extra['prepend'] = isset($extra['prepend']) ? $extra['prepend'] : '@';
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function number($name, $desc = '', $extra = array(), $replace = array()) {
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
	function float($name, $desc = '', $extra = array(), $replace = array()) {
		return $this->decimal($name, $desc, $extra, $replace);
	}

	/**
	*/
	function decimal($name, $desc = '', $extra = array(), $replace = array()) {
		$extra['step'] = $extra['step'] ?: '0.01';
		return $this->number($name, $desc, $extra, $replace);
	}

	/**
	*/
	function money($name, $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		$extra['prepend'] = isset($extra['prepend']) ? $extra['prepend'] : ($this->_params['currency'] ?: '$');
		$extra['append'] = isset($extra['append']) ? $extra['append'] : ''; // '.00';
		$extra['sizing'] = isset($extra['sizing']) ? $extra['sizing'] : 'small';
		$extra['maxlength'] = isset($extra['maxlength']) ? $extra['maxlength'] : '8';
		return $this->decimal($name, $desc, $extra, $replace);
	}

	/**
	*/
	function price($name, $desc = '', $extra = array(), $replace = array()) {
		$extra['min'] = $extra['min'] ?: '0';
		return $this->money($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function url($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'url';
		$extra['prepend'] = 'url';
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'url';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function color($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'color';
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'color';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function date($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'date';
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'date';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function datetime($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'datetime';
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'datetime';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function datetime_local($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'datetime-local';
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'datetime_local';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function month($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'month';
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'month';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function range($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'range';
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'range';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function search($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'search';
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'search';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function tel($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'tel';
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'tel';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* Alias
	*/
	function phone($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'tel';
		if (is_array($name)) {
			$extra += $name;
			$name = '';
		}
		if (!$name) {
			$name = 'phone';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function time($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'time';
		if (is_array($name)) {
			$extra += $name;
			$name = '';
		}
		if (!$name) {
			$name = 'time';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	* HTML5
	*/
	function week($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['type'] = 'week';
		if (is_array($name)) {
			$extra += $name;
			$name = '';
		}
		if (!$name) {
			$name = 'week';
		}
		return $this->input($name, $desc, $extra, $replace);
	}

	/**
	*/
	function active_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($name)) {
			$extra += $name;
			$desc = '';
		}
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'active');
		$extra['desc'] = $this->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $_this) {
			$_this->_prepare_inline_error($extra);
			if (!$extra['items']) {
				if (!isset($_this->_pair_active)) {
					$_this->_pair_active = main()->get_data('pair_active');
				}
				$extra['items'] = $_this->_pair_active;
			}
			$extra['values'] = $extra['items'];
			$extra['desc'] = !$_this->_params['no_label'] ? $extra['desc'] : '';
			$extra['id'] = $_this->_prepare_id($extra);
			if (!isset($extra['horizontal'])) {
				$extra['horizontal'] = true;
			}
			$extra['selected'] = isset($extra['selected']) ? $extra['selected'] : $r[$extra['name']];
			if (isset($_this->_params['selected'])) {
				$extra['selected'] = $_this->_params['selected'][$extra['name']];
			}
			return $_this->_row_html(_class('html')->radio_box($extra), $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	*/
	function allow_deny_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (!isset($this->_pair_allow_deny)) {
			$this->_pair_allow_deny = main()->get_data('pair_allow_deny');
		}
		$extra['items'] = $this->_pair_allow_deny;
		return $this->active_box($name, $desc, $extra, $replace);
	}

	/**
	*/
	function yes_no_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (!isset($this->_pair_yes_no)) {
			$this->_pair_yes_no = main()->get_data('pair_yes_no');
		}
		$extra['items'] = $this->_pair_yes_no;
		return $this->active_box($name, $desc, $extra, $replace);
	}

	/**
	*/
	function submit($name = '', $value = '', $extra = array(), $replace = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (is_array($value)) {
			$extra = (array)$extra + $value;
			$value = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['value'] = isset($extra['value']) ? $extra['value'] : ($value ?: 'Save');
		$func = function($extra, $r, $_this) {
			$_this->_prepare_inline_error($extra);
			$extra['id'] = $extra['id'] ?: ($extra['name'] ?: strtolower($extra['value']));
			$extra['link_url'] = $extra['link_url'] ? (isset($r[$extra['link_url']]) ? $r[$extra['link_url']] : $extra['link_url']) : '';
			if (preg_match('~^[a-z0-9_-]+$~ims', $extra['link_url'])) {
				$extra['link_url'] = '';
			}
			$extra['link_name'] = $extra['link_name'] ?: '';
			$extra['class'] = $extra['class'] ?: 'btn btn-default btn-primary'.$_this->_prepare_css_class('', $r[$extra['name']], $extra);
			$extra['value'] = t($extra['value']);
			$extra['desc'] = ''; // We do not need label here
			$extra['type'] = 'submit';

			$attrs_names = array('type','name','id','class','style','value','disabled','target');
			if (!$extra['as_input']) {
				$icon = ($extra['icon'] ? '<i class="'.$extra['icon'].'"></i> ' : '');
				$value = (!isset($extra['no_escape']) ? _htmlchars($extra['value']) : $extra['value']);
				return $_this->_row_html('<button'._attrs($extra, $attrs_names).'>'.$icon. $value.'</button>', $extra, $r);
			} else {
				return $_this->_row_html('<input'._attrs($extra, $attrs_names).'>', $extra, $r);
			}
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	*/
	function save($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (!isset($extra['icon'])) {
			$extra['icon'] = 'icon-save';
		}
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
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		$extra['link_url'] = $name;
		$extra['link_name'] = $desc ?: 'Back';
		if (!isset($extra['icon'])) {
			$extra['icon'] = 'icon-save';
		}
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
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		$extra['link_url'] = $name;
		$extra['link_name'] = $desc ?: 'Clear';
		if (!isset($extra['icon'])) {
			$extra['icon'] = 'icon-save';
		}
		return $this->submit($name, $desc, $extra, $replace);
	}

	/**
	*/
	function info($name, $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['desc'] = $this->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $_this) {
			$_this->_prepare_inline_error($extra);
			$extra['desc'] = !$extra['no_label'] && !$_this->_params['no_label'] ? $extra['desc'] : '';

			$value = $r[$extra['name']] ?: $extra['value'];
			if (is_array($extra['data'])) {
				if (isset($extra['data'][$value])) {
					$value = $extra['data'][$value];
				} elseif (isset($extra['data'][$extra['name']])) {
					$value = $extra['data'][$extra['name']];
				}
			}
			$value = !isset($extra['no_escape']) ? _htmlchars($value) : $value;
			if (!$extra['no_translate']) {
				$extra['desc'] = t($extra['desc']);
				$value = t($value);
			}
			if ($extra['no_text']) {
				$value = '';
			}
			if ($extra['link']) {
				if (MAIN_TYPE_ADMIN && main()->ADMIN_GROUP != 1 && !_class('common_admin')->_admin_link_is_allowed($extra['link'])) {
					$extra['link'] = '';
				}
			}
			$icon = $extra['icon'] ? '<i class="'.$extra['icon'].'"></i> ' : '';
			$content = '';
			if ($extra['link']) {
				if ($extra['rewrite']) {
					$extra['link'] = url($extra['link']);
				}
				$extra['class'] = $extra['class'] ?: 'btn btn-default btn-mini btn-xs';
				$extra['class'] = $_this->_prepare_css_class($extra['class'], $r[$extra['name']], $extra);
				$extra['href'] = $extra['link'];
				$extra['title'] = $extra['title'] ?: $extra['desc'] ?: $extra['name'];
				$attrs_names = array('href','name','class','style','disabled','target','alt','title');
				$content = '<a'._attrs($extra, $attrs_names).'>'.$icon. $value.'</a>';
			} else {
				$extra['class'] = $extra['class'] ?: 'label label-info';
				$content = '<span class="'.$_this->_prepare_css_class($extra['class'], $r[$extra['name']], $extra).'">'.$icon. $value.'</span>';
			}
			return $_this->_row_html($content, $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	*/
	function user_info($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_info', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function admin_info($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_info', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function info_date($name = '', $format = '', $extra = array(), $replace = array()) {
		$r = (array)$this->_replace + (array)$replace;
		if (is_array($format)) {
			$extra = (array)$extra + $format;
			$format = '';
		}
		$extra['format'] = $extra['format'] ?: $format;
		$replace[$name] = _format_date($r[$name], $extra['format']);
		$this->_replace[$name] = $replace[$name];
		return $this->info($name, $format, $extra, $replace);
	}

	/**
	* Mostly for {form_row()}, as it can be emulated from php easily
	*/
	function info_link($name = '', $link = '', $extra = array(), $replace = array()) {
		$r = (array)$this->_replace + (array)$replace;
		$extra['link'] = $extra['link'] ?: ($link ?: $r[$name]);
		return $this->info($name, '', $extra, $replace);
	}

	/**
	*/
	function link($name = '', $link = '', $extra = array(), $replace = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (is_array($link)) {
			$extra = (array)$extra + $link;
			$link = '';
		}
		$extra['link'] = isset($extra['link']) ? $extra['link'] : $link;
		$extra['value'] = isset($extra['value']) ? $extra['value'] : $name;
		if (!$extra['desc']) {
			$extra['no_label'] = 1;
		}
		return $this->info($name, $desc, $extra, $replace);
	}

	/**
	*/
	function _html_control($name, $values, $extra = array(), $replace = array(), $func_html_control = '') {
		$extra['name'] = $extra['name'] ?: $name;
		$extra['desc'] = $this->_prepare_desc($extra, $desc);
		$extra['values'] = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		$extra['func_html_control'] = $extra['func_html_control'] ?: $func_html_control;
		$func = function($extra, $r, $_this) {
			$_this->_prepare_inline_error($extra);
			$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
			$extra['selected'] = $_this->_prepare_selected($extra['name'], $extra, $r);
			$extra['id'] = $extra['name'];

			$func = $extra['func_html_control'];
			$content = _class('html')->$func($extra);
			if ($extra['no_label'] || $_this->_params['no_label']) {
				$extra['desc'] = '';
			}
			if ($extra['hide_empty'] && !strlen($content)) {
				return '';
			}
			return $_this->_row_html($content, $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	*/
	function box($name, $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['desc'] = $this->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $_this) {
			$_this->_prepare_inline_error($extra);
			$extra['edit_link'] = $extra['edit_link'] ? (isset($r[$extra['edit_link']]) ? $r[$extra['edit_link']] : $extra['edit_link']) : '';
			$extra['values'] = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
			$extra['selected'] = $_this->_prepare_selected($extra['name'], $extra, $r);
			$extra['id'] = $_this->_prepare_id($extra);

			return $_this->_row_html($r[$extra['name']], $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
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
		if (is_array($value)) {
			$extra = (array)$extra + $value;
			$value = '';
		}
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
	function button_box($name, $values, $extra = array(), $replace = array()) {
		return $this->_html_control($name, $values, $extra, $replace, 'button_box');
	}

	/**
	*/
	function button_split_box($name, $values, $extra = array(), $replace = array()) {
		return $this->_html_control($name, $values, $extra, $replace, 'button_split_box');
	}

	/**
	*/
	function select2_box($name, $values, $extra = array(), $replace = array()) {
		return $this->_html_control($name, $values, $extra, $replace, 'select2_box');
	}

	/**
	*/
	function chosen_box($name, $values, $extra = array(), $replace = array()) {
		return $this->_html_control($name, $values, $extra, $replace, 'chosen_box');
	}

	/**
	*/
	function date_box($name = '', $values = array(), $extra = array(), $replace = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'date';
		}
		return $this->_html_control($name, $values, $extra, $replace, 'date_box2');
	}

	/**
	*/
	function time_box($name = '', $values = array(), $extra = array(), $replace = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'time';
		}
		return $this->_html_control($name, $values, $extra, $replace, 'time_box2');
	}

	/**
	*/
	function datetime_box($name = '', $values = array(), $extra = array(), $replace = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'datetime';
		}
		if (!isset($extra['show_what'])) {
			$extra['show_what'] = 'ymdhis';
		}
		return $this->date_box($name, $values, $extra, $replace);
	}

	/**
	*/
	function birth_box($name = '', $values = array(), $extra = array(), $replace = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (!$name) {
			$name = 'birth';
		}
		return $this->date_box($name, $values, $extra, $replace);
	}

	/**
	*/
	function country_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function region_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function city_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function currency_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function language_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function timezone_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function icon_select_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function method_select_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function user_method_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['for_type'] = 'user';
		return $this->method_select_box($name, $desc, $extra, $replace);
	}

	/**
	*/
	function admin_method_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['for_type'] = 'admin';
		return $this->method_select_box($name, $desc, $extra, $replace);
	}

	/**
	*/
	function template_select_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function user_template_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['for_type'] = 'user';
		return $this->template_select_box($name, $desc, $extra, $replace);
	}

	/**
	*/
	function admin_template_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['for_type'] = 'admin';
		return $this->template_select_box($name, $desc, $extra, $replace);
	}

	/**
	*/
	function location_select_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_boxes', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function user_location_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['for_type'] = 'user';
		return $this->location_select_box($name, $desc, $extra, $replace);
	}

	/**
	*/
	function admin_location_box($name = '', $desc = '', $extra = array(), $replace = array()) {
		$extra['for_type'] = 'admin';
		return $this->location_select_box($name, $desc, $extra, $replace);
	}

	/**
	* Image upload
	*/
	function image($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_image', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function google_maps($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_google_maps', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function file_uploader($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_file_uploader', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	function upload($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_upload', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function captcha($name = '', $desc = '', $extra = array(), $replace = array()) {
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'captcha');
		$extra['desc'] = $this->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $_this) {
			$_this->_prepare_inline_error($extra);
			$extra['id'] = $_this->_prepare_id($extra);
			$extra['required'] = true;
			$extra['value'] = $r['captcha'];
			$extra['input_attrs'] = _attrs($extra, array('class','style','placeholder','pattern','disabled','required','autocomplete','accept','value'));
			return $_this->_row_html(_class('captcha')->show_block('./?object=dynamic&action=captcha_image', $extra), $extra, $r);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	*/
	function ui_range($name, $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_ui_range', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	* Custom function, useful to insert custom html and not breaking form chain
	*/
	function func($name, $func, $extra = array(), $replace = array()) {
		if (is_array($func)) {
			$extra = (array)$extra + $func;
			$func = '';
		}
		if (!$func) {
			if (isset($extra['callback'])) {
				$func = $extra['callback'];
			} elseif (isset($extra['function'])) {
				$func = $extra['function'];
			} else {
				$func = $extra['func'];
			}
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['desc'] = $this->_prepare_desc($extra);
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	*/
	function custom_fields($name, $custom_fields, $extra = array(), $replace = array()) {
		$extra['name'] = $extra['name'] ?: $name;
		$extra['custom_fields'] = $custom_fields;
		$func = function($extra, $r, $_this) {
			$custom_fields = explode(',', $extra['custom_fields']);
			$sub_array_name = $extra['sub_array'] ?: 'custom';
			$custom_info = _attrs_string2array($r[$extra['name']]);

			$body = array();
			$_this->_chained_mode = false;
			foreach ((array)$custom_fields as $field_name) {
				if (empty($field_name)) {
					continue;
				}
				$str = _class('html')->input(array(
					'id'	=> 'custom_'.$field_name.'_'.$r['id'],
					'name'	=> $sub_array_name.'['.$field_name.']', // Example: custom[color]
					'desc'	=> $field_name,
					'value'	=> $custom_info[$field_name],
				));
				$desc = ucfirst(str_replace('_', ' ', $field_name)).' [Custom]';
				$body[] = $_this->container($str, $desc);
			}
			$_this->_chained_mode = true;
			return implode(PHP_EOL, $body);
		};
		if ($this->_chained_mode) {
			$this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $this;
		}
		return $func((array)$extra + (array)$this->_extra, (array)$replace + (array)$this->_replace, $this);
	}

	/**
	*/
	function stars($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_stars', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	* Star selector, got from http://fontawesome.io/examples/#custom
	*/
	function stars_select($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_stars', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	* Datetimepicker, src: http://tarruda.github.io/bootstrap-datetimepicker/
	* params :  no_date // no date picker
	*			no_time // no time picker
	*/
	function datetime_select($name = '', $desc = '', $extra = array(), $replace = array()) {
		return _class('form2_datetime', 'classes/form2/')->{__FUNCTION__}($name, $desc, $extra, $replace, $this);
	}

	/**
	*/
	function tbl_link($name, $link, $extra = array(), $replace = array()) {
		return _class('form2_tbl_funcs', 'classes/form2/')->{__FUNCTION__}($name, $link, $extra, $replace, $this);
	}

	/**
	*/
	function tbl_link_edit($name = '', $link = '', $extra = array(), $replace = array()) {
		return _class('form2_tbl_funcs', 'classes/form2/')->{__FUNCTION__}($name, $link, $extra, $replace, $this);
	}

	/**
	*/
	function tbl_link_delete($name = '', $link = '', $extra = array(), $replace = array()) {
		return _class('form2_tbl_funcs', 'classes/form2/')->{__FUNCTION__}($name, $link, $extra, $replace, $this);
	}

	/**
	*/
	function tbl_link_clone($name = '', $link = '', $extra = array(), $replace = array()) {
		return _class('form2_tbl_funcs', 'classes/form2/')->{__FUNCTION__}($name, $link, $extra, $replace, $this);
	}

	/**
	*/
	function tbl_link_view($name = '', $link = '', $extra = array(), $replace = array()) {
		return _class('form2_tbl_funcs', 'classes/form2/')->{__FUNCTION__}($name, $link, $extra, $replace, $this);
	}

	/**
	*/
	function tbl_link_active($name = '', $link = '', $extra = array(), $replace = array()) {
		return _class('form2_tbl_funcs', 'classes/form2/')->{__FUNCTION__}($name, $link, $extra, $replace, $this);
	}

	/**
	* Form validation handler.
	* Here we have special rule, called __form_id__ , it is used to track which form need to be validated from $_POST.
	*/
	function validate($validate_rules = array(), $post = array(), $extra = array()) {
		$this->_validate_prepare($validate_rules, $extra);

		$func = function($validate_rules, $post, $extra, $_this) {
			$_this->_validate_prepare($validate_rules, $extra);
			$form_id = $_this->_form_id;
			$form_id_field = $_this->_form_id_field;
			// Do not do validation until data is empty (usually means that form is just displayed and we wait user input)
			$data = (array)(!empty($post) ? $post : $_POST);
			// Convert multi-dimensional arrays into single-dimensional array dot notation: array('k1' => array('k2' => 'v2'))  ==>  array('k1.k2' => 'v2')
			$data = array_dot($data);
			if (empty($data)) {
				return $_this;
			}
			// We need this to validate only correct form on page, where there can be several forms with validation at once
			if ($form_id && $data[$form_id_field] != $form_id) {
				return $_this;
			}
			$on_before_validate = isset($extra['on_before_validate']) ? $extra['on_before_validate'] : $_this->_on['on_before_validate'];
			if (is_callable($on_before_validate)) {
				$on_before_validate($_this->_validate_rules, $data);
			}
			// Processing of prepared rules
			$validate_ok = $_this->_validate_rules_process($_this->_validate_rules, $data, $extra);
			if ($validate_ok) {
				$_this->_validate_ok = true;
			} else {
				$_this->_validate_ok = false;
				$on_validate_error = isset($extra['on_validate_error']) ? $extra['on_validate_error'] : $_this->_on['on_validate_error'];
				if (is_callable($on_validate_error)) {
					$on_validate_error($_this->_validate_rules, $data, $extra);
				}
			}
			$on_after_validate = isset($extra['on_after_validate']) ? $extra['on_after_validate'] : $_this->_on['on_after_validate'];
			if (is_callable($on_after_validate)) {
				$on_after_validate($_this->_validate_ok, $_this->_validate_rules, $data, $extra);
			}
			$_this->_validated_fields = $data;
		};
		if ($this->_chained_mode) {
			$this->_validate = array(
				'func'		=> $func,
				'extra'		=> $extra,
				'post'		=> $post,
				'validate_rules' => $validate_rules,
			);
			return $this;
		}
		return $this;
	}

	/**
	*/
	function _validate_prepare($validate_rules = array(), $extra = array()) {
		$form_global_validate = isset($this->_params['validate']) ? $this->_params['validate'] : (isset($this->_replace['validate']) ? $this->_replace['validate'] : array());
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
		$form_id = '';
		$form_id_field = '__form_id__';
		if (isset($this->_validate_rules[$form_id_field])) {
			$form_id = $this->_validate_rules[$form_id_field];
			unset($this->_validate_rules[$form_id_field]);
		} elseif (isset($this->_params[$form_id_field])) {
			$form_id = $this->_params[$form_id_field];
			unset($this->_params[$form_id_field]);
		}
		if ($form_id) {
			$this->_form_id = $form_id;
			$this->_form_id_field = $form_id_field;
			$this->hidden($form_id_field, array('value' => $form_id));
		}
		$this->_validate_rules = $this->_validate_rules_cleanup($this->_validate_rules);
		// Prepare array of rules by form method for quick access
		if ($this->_validate_rules) {
			foreach ((array)$this->_validate_rules as $item => $rules) {
				foreach ((array)$rules as $rule) {
					if (is_string($rule[0])) {
						$this->_validate_rules_names[$item][$rule[0]] = $rule[1] ?: true;
					}
				}
			}
		}
		return ;
	}

	/**
	*/
	function _validate_rules_process($validate_rules = array(), &$data) {
		$validate_ok = true;
		foreach ((array)$validate_rules as $name => $rules) {
			$is_required = false;
			foreach ((array)$rules as $rule) {
				if ($rule[0] == 'required') {
					$is_required = true;
					break;
				}
			}
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
						$desc = $this->_find_field_desc($name) ?: $name;
						$error_msg = t('form_validate_'.$func, array('%field' => $desc, '%param' => $param));
					}
				}
				// In this case we do not track error if field is empty and not required
				if (!$is_ok && !$is_required && !strlen($data[$name])) {
					$is_ok = true;
					$error_msg = '';
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
	function _find_field_desc($name) {
		if (!strlen($name)) {
			return '';
		}
		$desc = $name;
		foreach ((array)$this->_body as $a) {
			if (!isset($a['extra']) || $a['extra']['name'] != $name || !strlen($a['extra']['desc'])) {
				continue;
			}
			$desc = $a['extra']['desc'];
			break;
		}
		return $desc;
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
		$func = __FUNCTION__;
		return _class('validate')->$func($validate_rules);
	}

	/**
	*/
	function _validate_rules_array_from_raw($raw = '') {
		$func = __FUNCTION__;
		return _class('validate')->$func($raw);
	}

	/**
	*/
	function _input_assign_params_from_validate($extra = array()) {
		return _class('form2_validate', 'classes/form2/')->_input_assign_params_from_validate($extra, $this);
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
		$func = function($table, $fields, $type, $extra, $_this) {
			if (!$table || !$type || empty($_POST)) {
				return $_this;
			}
			$validate_ok = ($_this->_validate_ok || $extra['force']);
			if (!$validate_ok) {
				return $_this;
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
				if (isset($_this->_validated_fields[$name])) {
					$data[$db_field_name] = $_this->_validated_fields[$name];
				}
			}
			// This is non-validated list of fields to add to the insert array
			foreach ((array)$extra['add_fields'] as $db_field_name => $value) {
				$data[$db_field_name] = $value;
			}
			// Callback/hook function implementation
			$on_before_update = isset($extra['on_before_update']) ? $extra['on_before_update'] : $_this->_on['on_before_update'];
			if ($data && $table && is_callable($on_before_update)) {
				$on_before_update($data, $table, $fields, $type, $extra);
			}
			if ($data && $table) {
				if ($type == 'update') {
					db()->update($table, db()->es($data), $extra['where_id']);
				} elseif ($type == 'insert') {
					db()->insert($table, db()->es($data));
				}
				// Callback/hook function implementation
				$on_after_update = isset($extra['on_after_update']) ? $extra['on_after_update'] : $_this->_on['on_after_update'];
				if (is_callable($on_after_update)) {
					$on_after_update($data, $table, $fields, $type, $extra);
				}
				$on_success_text = isset($extra['on_success_text']) ? $extra['on_success_text'] : $_this->_on['on_success_text'];
				if ($on_success_text) {
					common()->set_notice($on_success_text);
				}
				$redirect_link = $extra['redirect_link'] ? $extra['redirect_link'] : ($_this->_replace['redirect_link'] ? $_this->_replace['redirect_link'] : $_this->_replace['back_link']);
				if (!$redirect_link) {
					$redirect_link = './?object='.$_GET['object']. ($_GET['action'] != 'show' ? '&action='.$_GET['action'] : ''). ($_GET['id'] ? '&id='.$_GET['id'] : '');
				}
				if (!$extra['no_redirect']) {
					js_redirect($redirect_link);
				}
			}
		};
		if ($this->_chained_mode) {
			$this->_db_change_if_ok = array(
				'func'		=> $func,
				'table'		=> $table,
				'fields'	=> $fields,
				'type'		=> $type,
				'extra'		=> $extra,
			);
			return $this;
		}
		return $this;
	}

	/**
	*/
	function on_post($func) {
		$this->_on[__FUNCTION__] = $func;
		return $this;
	}

	/**
	*/
	function on_before_render($func) {
		$this->_on[__FUNCTION__] = $func;
		return $this;
	}

	/**
	*/
	function on_after_render($func) {
		$this->_on[__FUNCTION__] = $func;
		return $this;
	}

	/**
	*/
	function on_before_validate($func) {
		$this->_on[__FUNCTION__] = $func;
		return $this;
	}

	/**
	*/
	function on_after_validate($func) {
		$this->_on[__FUNCTION__] = $func;
		return $this;
	}

	/**
	*/
	function on_validate_error($func) {
		$this->_on[__FUNCTION__] = $func;
		return $this;
	}

	/**
	*/
	function on_success_text($func) {
		$this->_on[__FUNCTION__] = $func;
		return $this;
	}

	/**
	*/
	function on_before_update($func) {
		$this->_on[__FUNCTION__] = $func;
		return $this;
	}

	/**
	*/
	function on_after_update($func) {
		$this->_on[__FUNCTION__] = $func;
		return $this;
	}
}
