<?php

/**
 * Date slider processor
  */
class yf_date_slider {

	/** @var int default period for the stats in DAYS */
	public $STATS_PERIOD_VIEW					= 30;

	/**
	* Framework constructor
	*/
	function _init() {
		$this->month_abbr = array(
			"Jan",
			"Feb",
			"Mar",
			"Apr",
			"May",
			"Jun",
			"Jul",
			"Aug",
			"Sep",
			"Oct",
			"Nov",
			"Dec", 
		);	
	}

	/**
	* Show date slider
	*/
	function show($params = array()) {

		list($date_from, $date_to) = array_values((array)$this->get_unix_time());

		if(!empty($params["date_from"])){
			list($date_from, $date_to) = array_values((array)$params);
		}
		
		$str_date_from = $date_from ? gmdate("Y-m-d", $date_from) : "";
		$str_date_to = $date_to ? gmdate("Y-m-d", $date_to) : "";

		$date_interval = $this->_localize_date($str_date_from)." - ".$this->_localize_date($str_date_to);

		$replace = array(
			"date_interval"	=> $date_interval,
			"date_from"		=> $str_date_from,
			"date_to"			=> $str_date_to,
			"stats_period"	=> $this->STATS_PERIOD_VIEW,
		);
		
		return tpl()->parse("system/date_slider", $replace);
	}

	/**
	* 
	*/
	function get_unix_time() {
		if ($_GET['date_from']) {
			$date_from = strtotime($_GET['date_from']);
		} 
		if ($_GET['date_to']) {
			$date_to = strtotime($_GET['date_to']);
		} 
		if(!$date_to){
			$date_to = time();
		}
		if(!$date_from){
			$date_from = $date_to - $this->STATS_PERIOD_VIEW;
		}
		return array("date_from" => $date_from, "date_to" => $date_to);
	}

	/**
	* Localize date from format "15-05-2009" to format "15 May 2009"
	*/
	function _localize_date ($date) {
		preg_match("/\-([0-9]{2})\-/i", $date, $m);
		$key = intval($m[1]) - 1;
		$localized = str_replace($m[0], " ".$this->month_abbr[$key]." ", $date);
		return  $localized;
	}	

}