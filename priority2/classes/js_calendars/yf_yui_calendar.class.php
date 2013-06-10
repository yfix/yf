<?php

/**
* YAHOO Calendar handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_yui_calendar {

	/* Example:
		$YUI_CAL_OBJ = main()->init_class("yui_calendar", "classes/");
		$YUI_CAL_OBJ->_set_selected_dates(array("2006-02-12", "2006-02-15", "2006-02-16", "2006-02-17"));
		$YUI_CAL_OBJ->_set_on_select_link(WEB_PATH."?object=".__CLASS__."&action=".$_GET["action"]."&id=");
		// Process template
		$replace = array(
			"calendar_code"		=> $YUI_CAL_OBJ->_display_code(),
			"calendar_container"=> $YUI_CAL_OBJ->_display_container(),
		);
	*/
	/** @var string @conf_skip ex. "02/02/2006,02/22/2006-02/23/2006" */
	public $_selected_days		= "";
	/** @var string @conf_skip ex. "02/2006" */
	public $_cur_month			= "";
	/** @var string @conf_skip ex. 2006-01-01 */
	public $_start_date		= "";
	/** @var string @conf_skip ex. 2007-01-01 */
	public $_end_date			= "";
	/** @var string @conf_skip ex. "January","February", ... */
	public $_month_names		= "";
	/** @var string @conf_skip ex. "Monday","Tuesday", ... */
	public $_week_days_short	= "";
	/** @var string @conf_skip ex. 0 -> european style, 6 -> US style */
	public $_start_weekday		= 1;
	/** @var string @conf_skip ex. http://somepath?date= */
	public $_on_select_link	= "";
	/** @var string ex. cal1Container */
	public $_cal_container_id	= "cal1_container";

	/**
	* Constructor (PHP 4.x)
	*
	* @access	public
	* @return	void
	*/
	function yf_yui_calendar () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*
	* @access	public
	* @return	void
	*/
	function __construct () {
		// Set current month
		$this->_cur_month = date("m")."/".date("Y");
		// Set default start and end dates
		$this->_start_date	= date("Y-m-d", strtotime("-2 months"));
		$this->_end_date	= date("Y-m-d");
		// Set month names
		for ($i = 1; $i <= 12; $i++) {
			$tmp_month_names[] = "\"".date("F", strtotime("1990-".$i."-01"))."\"";
		}
		$this->_month_names = implode(",", $tmp_month_names);
		// Set weekdays names short (2 digits)
		for ($i = 1; $i <= 7; $i++) {
			$tmp_week_days_short[] = "\"".substr(date("D", strtotime("2010-03-".$i)), 0, 2)."\"";
		}
		$this->_week_days_short = implode(",", $tmp_week_days_short);
	}

	/**
	* Set selected days
	*
	* @access	public
	* @return	void
	*/
	function _set_selected_dates ($dates = array()) {
		foreach ((array)$dates as $cur_date) {
			$tmp_dates[] = date("m/d/Y", is_int($cur_date) ? $cur_date : strtotime($cur_date));
		}
		if (is_array($tmp_dates) && !empty($tmp_dates)) {
			$this->_selected_days = implode(",", $tmp_dates);
		}
	}

	/**
	* Set start date
	*
	* @access	public
	* @return	void
	*/
	function _set_start_date ($date_to_set = array()) {
		$this->_start_date = date("Y-m-d", is_int($date_to_set) ? $date_to_set : strtotime($date_to_set));
	}

	/**
	* Set end date
	*
	* @access	public
	* @return	void
	*/
	function _set_end_date ($date_to_set = array()) {
		$this->_end_date = date("Y-m-d", is_int($date_to_set) ? $date_to_set : strtotime($date_to_set));
	}

	/**
	* Set current month
	*
	* @access	public
	* @return	void
	*/
	function _set_cur_month ($date_to_set = array()) {
		$this->_cur_month = date("m/Y", is_int($date_to_set) ? $date_to_set : strtotime($date_to_set));
	}

	/**
	* Set on_select_link
	*
	* @access	public
	* @return	void
	*/
	function _set_on_select_link ($link_text = "") {
		$this->_on_select_link = $link_text;
	}

	/**
	* Display calendar code
	*
	* @access	public
	* @return	string
	*/
	function _display_code () {
		$replace = array(
			"selected_days"			=> $this->_selected_days,
			"cur_month"				=> $this->_cur_month,
			"min_days_from_now"		=> ceil((time() - strtotime($this->_start_date)) / 86400),
			"max_days_from_now"		=> ceil((time() - strtotime($this->_end_date)) / 86400),
			"cal_container_id"		=> $this->_cal_container_id,
			"month_names"			=> $this->_month_names,
			"week_days_short"		=> $this->_week_days_short,
			"start_weekday"			=> $this->_start_weekday,
			"on_select_link"		=> $this->_on_select_link,
		);
		return tpl()->parse("system/yui_calendar", $replace);
	}

	/**
	* Display calendar container
	*
	* @access	public
	* @return	string
	*/
	function _display_container () {
		$replace = array(
			"cal_container_id"		=> $this->_cal_container_id,
		);
		return tpl()->parse("system/yui_container", $replace);
	}
}
