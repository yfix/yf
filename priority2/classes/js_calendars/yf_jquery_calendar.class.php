<?php

/**
* JQuery Calendar handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_jquery_calendar {

	/** @var string @conf_skip */
	public $_selected_days		= "";
	/** @var string @conf_skip ex. "2006-13-01" */
	public $_cur_date			= "";
	/** @var string @conf_skip ex. 2006-01-01 */
	public $_start_date		= "";
	/** @var string @conf_skip ex. 2007-01-01 */
	public $_end_date			= "";
	/** @var string @conf_skip ex. http://somepath?date= */
	public $_on_select_link	= "";
	/** @var string ex. cal1_container */
	public $_cal_container_id	= "cal1_container";

	/**
	* Constructor (PHP 4.x)
	*
	* @access	public
	* @return	void
	*/
	function yf_jquery_calendar () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*
	* @access	public
	* @return	void
	*/
	function __construct () {
		// Set current date
		$this->_cur_date = date("m/d/Y");
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
			$tmp_dates[] = date("['Y','m','d']", is_int($cur_date) ? $cur_date : strtotime($cur_date));
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
		$this->_cur_date = date("Y-m-d", is_int($date_to_set) ? $date_to_set : strtotime($date_to_set));
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
		$min_time	= strtotime($this->_start_date);
		$max_time	= strtotime($this->_end_date);
		$min_year	= date("Y", $min_time);
		$max_year	= date("Y", $max_time);
		$replace = array(
			"selected_days"			=> $this->_selected_days,
			"cur_date"				=> strtotime($this->_cur_date),
			"min_time"				=> $min_time,
			"max_time"				=> $max_time,
			"min_date"				=> date("Y,m,d", $min_time),
			"max_date"				=> date("Y,m,d", $max_time),
			"cal_container_id"		=> $this->_cal_container_id,
			"on_select_link"		=> $this->_on_select_link,
			"year_range"			=> ($min_year - ($min_year == $max_year ? 1 : 0)).":".$max_year,
		);
		return tpl()->parse("system/js_calendars/jquery_calendar", $replace);
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
		return tpl()->parse("system/js_calendars/jquery_container", $replace);
	}
}
