<?php

/**
* JavaScript-based abstrat calendar builder
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_js_calendar {

	/** @var array */
	var $_avail_drivers	= array(
		"yui",
		"zapatec",
		"jquery",
	);
	/** @var string */
	var $_CUR_DRIVER	= "zapatec";
	/** @var object @conf_skip */
	var $DRIVER_OBJ		= null;

	/**
	* Constructor (PHP 4.x)
	*/
	function yf_js_calendar () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*/
	function __construct () {
		define("JS_CAL_DRIVERS_DIR", "classes/js_calendars/");
	}

	/**
	* YF Constructor
	* 
	* @access	private
	*/
	function _init () {
		// Quick check
		if (empty($this->_CUR_DRIVER) || !in_array($this->_CUR_DRIVER, $this->_avail_drivers)) {
			return false;
		}
		$this->DRIVER_OBJ = main()->init_class($this->_CUR_DRIVER."_calendar", JS_CAL_DRIVERS_DIR);
	}

	/**
	* Constructor (PHP 5.x)
	*/
	function _set_params ($params = array()) {
		if (!is_object($this->DRIVER_OBJ)) {
			return false;
		}
		$this->DRIVER_OBJ->_set_selected_dates($params["selected_dates"]);
		$this->DRIVER_OBJ->_set_on_select_link($params["on_select_link"]);
		$this->DRIVER_OBJ->_set_start_date($params["start_date"]);
		$this->DRIVER_OBJ->_set_end_date($params["end_date"]);
		$this->DRIVER_OBJ->_set_cur_month($params["cur_month"]);
		return true;
	}

	/**
	* Display calendar code
	*
	* @access	public
	* @return	string
	*/
	function _display_code ($params = array()) {
		if (!is_object($this->DRIVER_OBJ)) {
			return false;
		}
		return $this->DRIVER_OBJ->_display_code();
	}

	/**
	* Display calendar container code
	*
	* @access	public
	* @return	string
	*/
	function _display_container () {
		if (!is_object($this->DRIVER_OBJ)) {
			return false;
		}
		return $this->DRIVER_OBJ->_display_container();
	}
}
