<?php

/**
* Absttraction layer over HTML5/CSS frameworks.
* Planned support for these plugins: 
*	Bootstrap 2		http://twbs.github.io/bootstrap/2.3.2/
*	Bootstrap 3		http://twbs.github.io/bootstrap/3
*	Zurb Foundation	http://foundation.zurb.com/
*	Pure CSS		http://purecss.io/
*/
class yf_html {

	/**
	*/
	function form_row ($content, $extra = array(), $replace = array(), $obj) {
		$css_framework = $extra['css_framework'] ?: conf('css_framework');
		if ($css_framework) {
			return _class('html_'.$css_framework, 'classes/html/')->form_row($content, $extra, $replace, $obj);
		}
	}

	/**
	*/
	function table_header () {
// TODO
	}

	/**
	*/
	function navbar () {
// TODO
	}

	/**
	*/
	function breadcrumbs () {
// TODO
	}

	/**
	*/
	function dd_table($replace = array(), $field_types = array(), $extra = array()) {
		$form = form($replace, array(
			'legend' => $replace['title'],
			'no_form' => 1,
			'dd_mode' => 1,
			'dd_class' => 'span6',
		));
		foreach ($replace as $name => $val) {
			$func = 'container';
			$_extra = array(
				'desc' => $name,
				'value' => $val,
			);
			$ft = $field_types[$name];
			if (isset($ft)) {
				if (is_array($ft)) {
					if (isset($ft['func'])) {
						$func = $ft['func'];
					}
					$_extra = (array)$ft + $_extra;
				} else {
					$func = $ft;
				}
			}
			if ($func) {
				$form->$func($val, $_extra);
			}
		}
		$legend = $extra['legend'] ? '<legend>'._prepare_html($extra['legend']).'</legend>' : '';
		return '<div class="row-fluid">'.$legend.'<div class="span6">'.$form.'</div></div>';
	}
}