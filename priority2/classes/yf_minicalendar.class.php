<?php

/**
 * YF Minicalendar
 * 
 * @package		YF
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 * @revision	$Revision$
 */
class yf_minicalendar {

	/** @var @conf_skip */
	var $stamp;
	/** @var @conf_skip */
	var $nameWeek;
	/** @var @conf_skip */
	var $nameMonthFull;
	/** @var @conf_skip */
	var $nameMonth;
	/** @var @conf_skip */
	var $selYear;
	/** @var @conf_skip */
	var $selMonth;
	/** @var @conf_skip */
	var $startYear;
	/** @var @conf_skip */
	var $endYear;
	/** @var @conf_skip */
	// Set whether use "US-style" (with week starts on sunday) for true or 
	// "European-style" (Week starts on monday) for false
	var $sun_first = true;

	/**
	 * YF Minicalendar
	 */
	function yf_minicalendar() {
		$this->setWeek(array("Mon","Tue","Thu","Wed","Fri","Sat","Sun"));
		$this->setMonthFull(array("January","February","March","April","May","June","July","August","September","October","November","December"));
		$this->setMonth(array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"));
		$this->setTransYM("Year","Month");
		$this->setYears(date('Y')-5,date('Y')+5);
		$this->setStamp(time());
	}

	/**
	 *   Construct
	 */
	function __construct() {
		return $this->minicalendar();
	}

	/**
	 * SetWeek
	 */
	function setWeek($sWeek) {// Set translate for days name
		$this->nameWeek = $sWeek;
	}

	/**
	 * SetMonthFull
	 */
	function setMonthFull($sMonth) {
		$this->nameMonthFull = $sMonth;
	}

	/**
	 * SetMonth
	 */
	function setMonth($sMonth) {
		$this->nameMonth = $sMonth;
	}

	/**
	 * SetTransYM
	 */
	function setTransYM($tYear,$tMonth) {
		$this->selYear = $tYear;
		$this->selMonth = $tMonth;
	}

	/**
	 * SetYears
	 */
	function setYears($Year_s,$Year_e) {
		$this->startYear = $Year_s;
		$this->endYear = $Year_e;
	}

	/**
	 * SetStamp
	 */
	function setStamp($sStamp) {
		$this->stamp = $sStamp;
	}

	/**
	 * Createcalendar
	 */
	function createcalendar() {
		$r['nweek'] = '';
		foreach ((array)$this->nameWeek as $v) $r['nweek'] .= '"'.$v.'", ';
		$r['nweek'] = substr($r['nweek'], 0, -2);

		$r['nmonth'] = '';
		foreach ((array)$this->nameMonthFull as $v) $r['nmonth'] .= '"'.$v.'", ';
		$r['nmonth'] = substr($r['nmonth'], 0 ,-2);

		$r['nmonthsmall'] = '';
		foreach ((array)$this->nameMonth as $v) $r['nmonthsmall'] .= '"'.$value.'", ';
		$r['nmonthsmall'] = substr($r['nmonthsmall'],0,-2);

		$r['sel_year']		= '"'.$this->selYear.'"';
		$r['sel_month']		= '"'.$this->selMonth.'"';
		$r['year_start']	= $this->startYear;
		$r['year_end']		= $this->endYear;
		$r['sun_first']		= var_export($this->sun_first, true);
		$r['time']			= $this->stamp*1000;

		$r['css_link'] = WEB_PATH."templates/".conf('theme')."/minicalendar.css";
		return tpl()->parse('system/minicalendar', $r);
	}

	/**
	 * Show calendar calling image with the specified ID
	 */
	function _show_image($id) {
		return "<img src='".WEB_PATH."templates/".conf('theme')."/images/calendar.gif' border=0 style=\"cursor:hand;\" onClick=\"showcalendar('".$id."');\" alt='Click Here' title='Click Here'>";
	}
}
