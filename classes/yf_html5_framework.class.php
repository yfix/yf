<?php

/**
* Abstraction layer over HTML5/CSS frameworks.
* Planned support for these plugins: 
*	Bootstrap 2		http://twbs.github.io/bootstrap/2.3.2/
*	Bootstrap 3		http://twbs.github.io/bootstrap/3
*	Zurb Foundation	http://foundation.zurb.com/
*	Pure CSS		http://purecss.io/
*/
class yf_html5_framework {

	/** @var */
	public $DEFAULT_CSS_FRAMEWORK = 'bs2';

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
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
	*/
	function form_row ($content, $extra = array(), $replace = array(), $obj) {
		$css_framework = $extra['css_framework'] ?: conf('css_framework');
		if (!$css_framework) {
			$css_framework = $this->DEFAULT_CSS_FRAMEWORK;
		}
		return _class('html5_framework_'.$css_framework, 'classes/html5_framework/')->form_row($content, $extra, $replace, $obj);
	}

	/**
	*/
	function form_dd_row ($content, $extra = array(), $replace = array(), $obj) {
		$css_framework = $extra['css_framework'] ?: conf('css_framework');
		if (!$css_framework) {
			$css_framework = $this->DEFAULT_CSS_FRAMEWORK;
		}
		return _class('html5_framework_'.$css_framework, 'classes/html5_framework/')->form_dd_row($content, $extra, $replace, $obj);
	}

	/**
	*/
	function form_render_out ($content, $extra = array(), $replace = array(), $obj) {
		$css_framework = $extra['css_framework'] ?: conf('css_framework');
		if (!$css_framework) {
			$css_framework = $this->DEFAULT_CSS_FRAMEWORK;
		}
		return _class('html5_framework_'.$css_framework, 'classes/html5_framework/')->form_render_out($content, $extra, $replace, $obj);
	}
}
