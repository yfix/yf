<?php

/**
* Time zone container
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_time_zone {

	/** @var array @conf_skip */
	public $_time_zones = array(
		'-12'	=> "(GMT - 12:00 hours) Enitwetok, Kwajalien",
		'-11'	=> "(GMT - 11:00 hours) Midway Island, Samoa",
		'-10'	=> "(GMT - 10:00 hours) Hawaii",
		'-9'	=> "(GMT - 9:00 hours) Alaska",
		'-8'	=> "(GMT - 8:00 hours) Pacific Time (US &amp; Canada)",
		'-7'	=> "(GMT - 7:00 hours) Mountain Time (US &amp; Canada)",
		'-6'	=> "(GMT - 6:00 hours) Central Time (US &amp; Canada), Mexico City",
		'-5'	=> "(GMT - 5:00 hours) Eastern Time (US &amp; Canada), Bogota, Lima",
		'-4'	=> "(GMT - 4:00 hours) Atlantic Time (Canada), Caracas, La Paz",
		'-3.5'	=> "(GMT - 3:30 hours) Newfoundland",
		'-3'	=> "(GMT - 3:00 hours) Brazil, Buenos Aires, Falkland Is.",
		'-2'	=> "(GMT - 2:00 hours) Mid-Atlantic, Ascention Is., St Helena",
		'-1'	=> "(GMT - 1:00 hours) Azores, Cape Verde Islands",
		'0'		=> "(GMT) Casablanca, Dublin, London, Lisbon, Monrovia",
		'1'		=> "(GMT + 1:00 hours) Brussels, Copenhagen, Madrid, Paris",
		'2'		=> "(GMT + 2:00 hours) Kaliningrad, South Africa, Warsaw",
		'3'		=> "(GMT + 3:00 hours) Baghdad, Riyadh, Moscow, Nairobi",
		'3.5'	=> "(GMT + 3:30 hours) Tehran",
		'4'		=> "(GMT + 4:00 hours) Abu Dhabi, Baku, Muscat, Tbilisi",
		'4.5'	=> "(GMT + 4:30 hours) Kabul",
		'5'		=> "(GMT + 5:00 hours) Ekaterinburg, Karachi, Tashkent",
		'5.5'	=> "(GMT + 5:30 hours) Bombay, Calcutta, Madras, New Delhi",
		'6'		=> "(GMT + 6:00 hours) Almaty, Colomba, Dhakra",
		'7'		=> "(GMT + 7:00 hours) Bangkok, Hanoi, Jakarta",
		'8'		=> "(GMT + 8:00 hours) Hong Kong, Perth, Singapore, Taipei",
		'9'		=> "(GMT + 9:00 hours) Osaka, Sapporo, Seoul, Tokyo, Yakutsk",
		'9.5'	=> "(GMT + 9:30 hours) Adelaide, Darwin",
		'10'	=> "(GMT + 10:00 hours) Melbourne, Papua New Guinea, Sydney",
		'11'	=> "(GMT + 11:00 hours) Magadan, New Caledonia, Solomon Is.",
		'12'	=> "(GMT + 12:00 hours) Auckland, Fiji, Marshall Island",
	);

	/**
	*  Time Zone Box
	*/
	function _time_zone_box ($name_in_form = "time_zone", $selected = "0") {
		return common()->select_box($name_in_form, $this->_time_zones, $selected, 0, 2, "", false);
	}
}
