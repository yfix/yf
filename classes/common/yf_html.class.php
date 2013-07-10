<?php

/**
* Implementation of common used functions (standalone)
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
 */
class yf_html {

	/** @var bool */
	public $AUTO_ASSIGN_IDS = true;
// TODO: check ids for uniqueness

	/**
	* This function generate select box with tree hierarhy inside
	*/
	function select_box ($name, $values, $selected = '', $show_text = true, $type = 2, $add_str = '', $translate = 0, $level = 0) {
		$selected = strval($selected);
		// (example: $add_str = "size=6")
		$body = $level == 0 ? "\n".'<select name="'.$name.'"'.($this->AUTO_ASSIGN_IDS ? ' id="'.$name.'_box"' : '').$add_str.">\n" : '';
		if ($show_text && $level == 0) {
			$body .= "\t".'<option value="">'.($show_text == 1 ? '-'.t('select').' '.t($name).'-' : $show_text)."</option>\n";
		}
		$self_func = __FUNCTION__;
		foreach ((array)$values as $key => $cur_value) {
			if (is_array($cur_value)) {
				$body .= "\t".'<optgroup label="'.$key.'" title="'.($translate ? t($key) : $key).'">'."\n";
				$body .= $this->$self_func($name, $cur_value, $selected, $show_text, $type, $add_str, $translate, $level + 1);
				$body .= "\t</optgroup>\n";
			} else {
				$_what_compare = strval($type == 1 ? $cur_value : $key);
				$body .= "\t".'<option value="'.$key.'" '.($_what_compare == $selected ? 'selected="selected"' : '').'>'.($translate ? t($cur_value) : $cur_value)."</option>\n";
			}
		}
		$body .= $level == 0 ? "</select>\n" : '';
		return $body;
	}

	/**
	* Generate multi-select box
	*/
	function multi_select($name, $values, $selected = '', $show_text = false, $type = 2, $add_str = '', $translate = 0, $level = 0, $disabled = false) {
		if (!is_array($selected)) {
			$selected = strval($selected);
		}
		if ($disabled  == 1) {
			$disabled = "disabled";
		} else {
			$disabled = '';
		}
		// (example: $add_str = "size=6") disabled
		$body = $level == 0 ? "\n".'<select '.$disabled.' multiple name="'.$name.'[]"'.($this->AUTO_ASSIGN_IDS ? ' id="'.$name.'_box"' : '').$add_str.">\n" : '';
		if ($show_text && $level == 0) {
			$body .= "\t".'<option value="">-'.t('select').' '.t($name)."-</option>\n";
		}
		$self_func = __FUNCTION__;
		foreach ((array)$values as $key => $value) {
			if (is_array($value)) {
				$body .= "\t".'<optgroup label="'.$key.'" title="'.($translate ? t($key) : $key).'">'."\n";
				$body .= $this->$self_func($name, $value, $selected, $show_text, $type, $add_str, $translate, $level + 1);
				$body .= "\t</optgroup>\n";
			} else {
				// Selected value could be an array
				if (is_array($selected)) {
					if ($type == 1) {
						$sel_text = in_array($value, $selected) ? 'selected="selected"' : '';
					} else {
						$sel_text = isset($selected[$key]) ? 'selected="selected"' : '';
					}
				} elseif (strlen($selected)) {
					$_what_compare = strval($type == 1 ? $value : $key);
					$sel_text = $_what_compare == $selected ? 'selected="selected"' : '';
				} else {
					$sel_text = '';
				}
				$body .= "\t".'<option value="'.$key.'" '.$sel_text.'>'.($translate ? t($value) : $value)."</option>\n";
			}
		}
		$body .= $level == 0 ? "</select>\n" : '';
		return $body;
	}

	/**
	* Processing radio buttons
	*/
	function radio_box ($box_name, $values, $selected = '', $flow_vertical = false, $type = 2, $add_str = '', $translate = 0) {
		$selected = strval($selected);
		foreach ((array)$values as $value => $name) {
			if (common()->BOXES_USE_STPL) { 
				$_what_compare = strval($type == 1 ? $name : $value);
				$replace = array(
					"name"		=> $box_name,
					"value"		=> $value,
					"selected"	=> $_what_compare == $selected ? 'checked="true"' : '',
					"add_str"	=> $add_str,
					"label"		=> $translate ? t($name) : $name,
					"divider"	=> $flow_vertical ? '<br />' : '&nbsp;',
				);
				$body .= tpl()->parse("system/common/radio_box_item", $replace);
			} else {
				$body .= '<label class="radio"><input type="radio" name="'.$box_name.'" id="check_'.$box_name.'" value="'.$value.'" '.$add_str.' '
					.((strval($value) == $selected) ? 'checked' : '').'>'.t($name)."</label>\n";
			}
		}
		return $body;
	}

	/**
	* Simple check box
	*/
	function check_box ($box_name, $value, $selected = '') {
		$selected = strval($selected);
		$value_to_display = '';
		if (is_string($value)) {
			$value_to_display = $value;
		}
		if ($translate) {
			$value_to_display = t($value_to_display);
		}
		$body .= '<label class="checkbox"><input type="checkbox" name="'.$box_name.'" id="'.$box_name.'_box" value="1" '.($selected ? 'checked' : '').'>'.$value_to_display. "</label>";
		return $body;
	}

	/**
	* Processing many checkboxes at one time
	*/
	function multi_check_box ($box_name, $values, $selected = array(), $flow_vertical = false, $type = 2, $add_str = '', $translate = 0, $name_as_array = false) {
		if (!is_array($selected)) {
			$selected = strval($selected);
		}
		foreach ((array)$values as $key => $value) {
			$sel_text = '';
			// Selected value could be an array
			if (is_array($selected)) {
				if ($type == 1) {
					$sel_text = in_array($value, $selected) ? 'checked' : '';
				} else {
					$sel_text = isset($selected[$key]) ? 'checked' : '';
				}
			} elseif (strlen($selected)) {
				$_what_compare = strval($type == 1 ? $value : $key);
				$sel_text = $_what_compare == $selected ? 'checked="true"' : '';
			} else {
				$sel_text = '';
			}
			
			if($name_as_array){
				$name = $box_name."[".$key."]";
			}else{
				$name = $box_name."_".$key;
			}
			
			if (common()->BOXES_USE_STPL) {
			
				$replace = array(
					"name"		=> $name,
					"value"		=> $key,
					"selected"	=> $sel_text,
					"add_str"	=> $add_str,
					"label"		=> $translate ? t($value) : $value,
					"divider"	=> $flow_vertical ? '<br />' : '&nbsp;',
				);
				$body .= tpl()->parse("system/common/check_box_item", $replace);
			} else {
// TODO: auto ID
				$body .= '<input type="checkbox" name="'.$name.'" class="check" value="'.$key.'" '.$sel_text.' '.$add_str.'>'.($translate ? t($value) : $value). ($flow_vertical ? "<br />" : "&nbsp;"). "\n";
			}
		}
		return $body;
	}

	/**
	* This function generate date box
	*/
	function date_box ($selected = '', $years = '', $name_postfix = '', $add_str = '', $order = "ymd", $show_text = 1, $translate = 1) {
		if (strlen($selected))	{
			// Process timestamp (convert it to the "Y-m-d" pattern)
			if (is_numeric($selected)) {
				$selected = gmdate("Y-m-d", $selected);
			}
			list($year, $month, $day) = explode("-", $selected);
		}
		if (strlen($years)) {
			list($start_year, $end_year) = explode("-", $years);
		} else {
			$start_year = 1900;
			$end_year = gmdate('Y');
		}
		$y .= "\n<select name=\"year".$name_postfix."\"".($this->AUTO_ASSIGN_IDS ? " id=\"year_".$name_postfix."_box\"" : "").">\n";
		$y .= $show_text ? "\t<option ".(!$year ? 'selected="selected"' : "")." value=\"\">-".($translate ? t('year') : 'year')."-</option>\n" : "";
		for ($a = $start_year; $a <= $end_year; $a++) {
			$y .= "\t\t\t<option ".(($year == $a) ? 'selected="selected"' : "")." value=\"".$a."\">".$a."</option>\n";
		}
		$y .= "</select>\n";
		$m .= "<select name=\"month".$name_postfix."\"".($this->AUTO_ASSIGN_IDS ? " id=\"month_".$name_postfix."_box\"" : "").">\n";
		$m .= $show_text ? "\t<option ".(!$month ? 'selected="selected"' : "")." value=\"\">-".($translate ? t('month') : 'month')."-</option>\n" : "";
		for ($a = 1; $a <= 12; $a++) {
			$m .= "\t<option ".(($month == $a) ? 'selected="selected"' : "")." value=\"".$a."\">".($translate ? t($this->month($a)) : $this->month($a)) ."</option>\n";
		}
		$m .= "</select>\n";
		$d .= "<select name=\"day".$name_postfix."\"".($this->AUTO_ASSIGN_IDS ? " id=\"day_".$name_postfix."_box\"" : "").">\n";
		$d .= $show_text ? "\t<option ".(!$day ? 'selected="selected"' : "")." value=\"\">-".($translate ? t('day') : 'day')."-</option>\n" : "";
		for ($a = 1; $a <= 31; $a++) {
			$d .= "\t<option ".(($day == $a) ? 'selected="selected"' : "")." value=\"".$a."\">".$a."</option>\n";
		}
		$d .= "</select>\n";
		// Process order
		$tmp_array = array(
			"y"	=> '{%year%}',
			"m"	=> '{%month%}',
			"d"	=> '{%day%}',
		);
		if (empty($order)) {
			$order = "ymd";
		}
		$order = str_replace(array_keys($tmp_array), array_values($tmp_array), $order);
		return str_replace(array_values($tmp_array), array($y,$m,$d), $order);
	}

	/**
	* This function generate time box
	*/
	function time_box ($selected = '', $name_postfix = '', $add_str = '', $show_text = 1, $translate = 1) {
		if (strlen($selected))	{
			// Process timestamp (convert it to the "Y-m-d" pattern)
			if (is_numeric($selected)) {
				$selected = gmdate("H:i:s", $selected);
			}
			list ($hour, $minute, $second) = explode(":", $selected);
		}
		$body .= "\n<select name=\"hour".$name_postfix."\"".($this->AUTO_ASSIGN_IDS ? " id=\"hour_".$name_postfix."_box\"" : "").">\n";
		$body .= $show_text ? "\t<option ".($hour == "" ? 'selected="selected"' : "")." value=\"\">-".($translate ? t('hour') : 'hour')."-</option>\n" : "";
		for ($a = 0; $a <= 23; $a++) {
			$body .= "\t<option ".(($hour == $a && $hour != "") ? 'selected="selected"' : "")." value=\"".$a."\">".$a."</option>\n";
		}
		$body .= "</select>\n";
		$body .= "<select name=\"minute".$name_postfix."\"".($this->AUTO_ASSIGN_IDS ? " id=\"minute_".$name_postfix."_box\"" : "").">\n";
		$body .= $show_text ? "\t<option ".($minute == "" ? 'selected="selected"' : "")." value=''>-".($translate ? t('minute') : 'minute')."-</option>\n" : "";
		for ($a = 0; $a <= 59; $a++) {
			$body .= "\t<option ".(($minute == $a && $minute != "") ? 'selected="selected"' : "")." value=\"".$a."\">".$a."</option>\n";
		}
		$body .= "</select>\n";
		$body .= "<select name=\"second".$name_postfix."\"".($this->AUTO_ASSIGN_IDS ? " id=\"second_".$name_postfix."_box\"" : "").">\n";
		$body .= $show_text ? "\t<option ".($second == "" ? 'selected="selected"' : "")." value=''>-".($translate ? t('second') : 'second')."-</option>\n" : "";
		for ($a = 0; $a <= 59; $a++) {
			$body .= "\t<option ".(($second == $a && $second != "") ? 'selected="selected"' : "")." value=\"".$a."\">".$a."</option>\n";
		}
		$body .= "</select>\n";
		return $body;
	}

	/**
	* Month name
	*/
	function month ($num, $lang = "") {
		$m_array = array(
			"January",
			"February",
			"March",
			"April",
			"May",
			"June",
			"July",
			"August",
			"September",
			"October",
			"November",
			"December",
		);
		$num--;
		return (($num > 12) || ($num < 0)) ? $num : $m_array[$num];
	}

	/**
	* Encode given address to prevent spam-bots harvesting
	*
	*	Output: the email address as a mailto link, with each character
	*		of the address encoded as either a decimal or hex entity, in
	*		the hopes of foiling most address harvesting spam bots. E.g.:
	*
	*	  <a href="&#x6D;&#97;&#105;&#108;&#x74;&#111;:&#102;&#111;&#111;&#64;&#101;
	*		x&#x61;&#109;&#x70;&#108;&#x65;&#x2E;&#99;&#111;&#109;">&#102;&#111;&#111;
	*		&#64;&#101;x&#x61;&#109;&#x70;&#108;&#x65;&#x2E;&#99;&#111;&#109;</a>
	*
	* @public
	* @param	string	an email address to encode, e.g. "foo@example.com"
	* @param	bool	switch between returning HTML link or just encode text
	* @return	string
	*/
	function encode_email($addr = "", $as_html_link = false) {
		if ($as_html_link) {
			$addr = "mailto:" . $addr;
		}
		$length = strlen($addr);
		// leave ':' alone (to spot mailto: later)
		$addr = preg_replace_callback('/([^\:])/', array($this, '_encode_email_address_callback'), $addr);
		// Convert into HTML anchor link
		if ($as_html_link) {
			$addr = "<a href=\"".$addr."\">".$addr."</a>";
		}
		// strip the mailto: from the visible part
		$addr = preg_replace('/">.+?:/', '">', $addr);
		return $addr;
	}

	/**
	* Callback internal method for "encode_email"
	*
	* @private
	* @param	array	matches
	* @return	string	encoded output
	*/
	function _encode_email_address_callback($matches) {
		$char = $matches[1];
		$r = rand(0, 100);
		// roughly 10% raw, 45% hex, 45% dec
		// '@' *must* be encoded. I insist.
		if ($r > 90 && $char != '@') {
			return $char;
		}
		if ($r < 45) {
			return '&#x'.dechex(ord($char)).';';
		}
		return '&#'.ord($char).';';
	}

	/**
	* Creates hyperlink from text
	*/
	function hyperlink(&$text) {
		// match protocol://address/path/file.extension?some=variable&another=asf%
		$text = preg_replace("/\s(([a-zA-Z]+:\/\/)([a-z][a-z0-9_\..-]*[a-z]{2,6})([a-zA-Z0-9\/*-?&%]*))\s/i", " <a href=\"$1\">$3</a> ", $text);
		// match www.something.domain/path/file.extension?some=variable&another=asf%
		$text = preg_replace("/\s(www\.([a-z][a-z0-9_\..-]*[a-z]{2,6})([a-zA-Z0-9\/*-?&%]*))\s/i", " <a href=\"http://$1\">$2</a> ", $text);
		return $text;
	}

	/**
	*/
	function date_box2 ($name, $selected = "", $years = "", $add_str = "", $order = "ymd", $show_text = 1, $translate = 1) {
		if (strlen($selected))	{
			// Process timestamp (convert it to the "Y-m-d" pattern)
			if (is_numeric($selected)) {
				$selected = gmdate("Y-m-d", $selected);
			}
			list($year, $month, $day) = explode("-", $selected);
		}
		if (strlen($years)) {
			list($start_year, $end_year) = explode("-", $years);
		} else {
			$start_year = 1900;
			$end_year = gmdate('Y');
		}
		$y .= "\n".'<select name="'.$name.'[year]"'.($this->AUTO_ASSIGN_IDS ? ' id="'.$name.'_year_box"' : '').">\n";
		$y .= $show_text ? "\t".'<option '.(!$year ? 'selected="selected"' : '').' value="">-'.($translate ? t('year') : 'year')."-</option>\n" : '';
		for ($a = $start_year; $a <= $end_year; $a++) {
			$y .= "\t\t\t<option ".(($year == $a) ? 'selected="selected"' : '').' value="'.$a.'">'.$a."</option>\n";
		}
		$y .= "</select>\n";
		$m .= '<select name="'.$name.'[month]"'.($this->AUTO_ASSIGN_IDS ? ' id="'.$name.'_month_box"' : '').">\n";
		$m .= $show_text ? "\t<option ".(!$month ? 'selected="selected"' : '').' value="">-'.($translate ? t('month') : 'month')."-</option>\n" : "";
		for ($a = 1; $a <= 12; $a++) {
			$m .= "\t<option ".(($month == $a) ? 'selected="selected"' : '').' value="'.$a.'">'.($translate ? t($this->month($a)) : $this->month($a)) ."</option>\n";
		}
		$m .= "</select>\n";
		$d .= '<select name="'.$name.'[day]"'.($this->AUTO_ASSIGN_IDS ? ' id="'.$name.'_day_box"' : '').">\n";
		$d .= $show_text ? "\t<option ".(!$day ? 'selected="selected"' : '').' value="">-'.($translate ? t('day') : 'day')."-</option>\n" : '';
		for ($a = 1; $a <= 31; $a++) {
			$d .= "\t<option ".(($day == $a) ? 'selected="selected"' : '').' value="'.$a.'">'.$a."</option>\n";
		}
		$d .= "</select>\n";
		$tmp_array = array(
			"y"	=> '{%year%}',
			"m"	=> '{%month%}',
			"d"	=> '{%day%}',
		);
		if (empty($order)) {
			$order = "ymd";
		}
		$order = str_replace(array_keys($tmp_array), array_values($tmp_array), $order);
		return str_replace(array_values($tmp_array), array($y,$m,$d), $order);
	}

	/**
	*/
	function time_box2 ($name, $selected = "", $add_str = "", $show_text = 1, $translate = 1) {
		if (strlen($selected))	{
			// Process timestamp (convert it to the "Y-m-d" pattern)
			if (is_numeric($selected)) {
				$selected = gmdate("H:i:s", $selected);
			}
			list ($hour, $minute, $second) = explode(":", $selected);
		}
		$body .= "\n".'<select name="'.$name.'"[hour]'.($this->AUTO_ASSIGN_IDS ? ' id="'.$name.'_hour_box"' : '').">\n";
		$body .= $show_text ? "\t<option ".($hour == "" ? 'selected="selected"' : "").' value="">-'.($translate ? t('hour') : 'hour')."-</option>\n" : "";
		for ($a = 0; $a <= 23; $a++) {
			$body .= "\t<option ".(($hour == $a && $hour != "") ? 'selected="selected"' : "").' value="'.$a.'">'.$a."</option>\n";
		}
		$body .= "</select>\n";
		$body .= '<select name="'.$name.'"[minute]'.($this->AUTO_ASSIGN_IDS ? ' id="'.$name.'_minute_box"' : "").">\n";
		$body .= $show_text ? "\t<option ".($minute == "" ? 'selected="selected"' : "").' value="">-'.($translate ? t('minute') : 'minute')."-</option>\n" : "";
		for ($a = 0; $a <= 59; $a++) {
			$body .= "\t<option ".(($minute == $a && $minute != "") ? 'selected="selected"' : "").' value="'.$a.'">'.$a."</option>\n";
		}
		$body .= "</select>\n";
		$body .= '<select name="'.$name.'"[second]'.($this->AUTO_ASSIGN_IDS ? ' id="'.$name.'_second_box"' : "").">\n";
		$body .= $show_text ? "\t<option ".($second == "" ? 'selected="selected"' : "").' value="">-'.($translate ? t('second') : 'second')."-</option>\n" : "";
		for ($a = 0; $a <= 59; $a++) {
			$body .= "\t<option ".(($second == $a && $second != "") ? 'selected="selected"' : "").' value="'.$a.'">'.$a."</option>\n";
		}
		$body .= "</select>\n";
		return $body;
	}

	/**
	*/
	function datetime_box2 ($name, $selected = "", $years = "", $add_str = "", $order = "ymd", $show_text = 1, $translate = 1) {
		return $this->date_box2($name, $selected, $years, $add_str, $order, $show_text, $translate)
			.$this->time_box2($name, $selected, $add_str, $show_text, $translate);
	}
}
