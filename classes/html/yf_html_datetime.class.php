<?php

/**
*/
class yf_html_datetime {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function date_box ($selected = '', $years = '', $name_postfix = '', $add_str = '', $show_what = 'ymd', $show_text = 1, $translate = 1) {
		if (is_array($selected)) {
			$extra = (array)$extra + $name;
			$selected = $extra['selected'];

			$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
			$selected = $extra['selected'];

			$years = isset($extra['years']) ? $extra['years'] : '';
			$show_what = isset($extra['show_what']) ? $extra['show_what'] : 'ymd';
			$show_text = isset($extra['show_text']) ? $extra['show_text'] : 1;
			$translate = isset($extra['translate']) ? $extra['translate'] : 1;
			$add_str = isset($extra['add_str']) ? $extra['add_str'] : '';
			if ($extra['class']) {
				$add_str .= ' class="'.$extra['class'].'" ';
			}
			if ($extra['style']) {
				$add_str .= ' style="'.$extra['style'].'" ';
			}
		}
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if (strlen($selected))	{
			// Process timestamp (convert it to the 'Y-m-d' pattern)
			if (is_numeric($selected)) {
				$selected = gmdate('Y-m-d', $selected);
			}
			list($year, $month, $day) = explode('-', $selected);
		}
		if (strlen($years)) {
			list($start_year, $end_year) = explode('-', $years);
		} else {
			$start_year = 1900;
			$end_year = gmdate('Y');
		}
		$y .= '<select name="year'.$name_postfix.'"'.(_class('html')->AUTO_ASSIGN_IDS ? ' id="'.$extra['id']. $name_postfix.'_year"' : '').' class="span1 col-lg-1">'.PHP_EOL;
		$y .= $show_text ? '<option '.(!$year ? 'selected="selected"' : '').' value="">-'.($translate ? t('year') : 'year').'-</option>'.PHP_EOL : '';
		for ($a = $start_year; $a <= $end_year; $a++) {
			$y .= '<option '.(($year == $a) ? 'selected="selected"' : '').' value="'.$a.'">'.$a.'</option>'.PHP_EOL;
		}
		$y .= '</select>'.PHP_EOL;
		$m .= '<select name="month'.$name_postfix.'"'.(_class('html')->AUTO_ASSIGN_IDS ? ' id="'.$extra['id']. $name_postfix.'_month"' : '').' class="span1 col-lg-1">'.PHP_EOL;
		$m .= $show_text ? '<option '.(!$month ? 'selected="selected"' : '').' value="">-'.($translate ? t('month') : 'month').'-</option>'.PHP_EOL : '';
		for ($a = 1; $a <= 12; $a++) {
			$m .= '<option '.(($month == $a) ? 'selected="selected"' : '').' value="'.$a.'">'.($translate ? t($this->_months($a)) : $this->_months($a)) .'</option>'.PHP_EOL;
		}
		$m .= '</select>'.PHP_EOL;
		$d .= '<select name="day'.$name_postfix.'"'.(_class('html')->AUTO_ASSIGN_IDS ? ' id="'.$extra['id']. $name_postfix.'_day"' : '').' class="span1 col-lg-1">'.PHP_EOL;
		$d .= $show_text ? '<option '.(!$day ? 'selected="selected"' : '').' value="">-'.($translate ? t('day') : 'day').'-</option>'.PHP_EOL : '';
		for ($a = 1; $a <= 31; $a++) {
			$d .= '<option '.(($day == $a) ? 'selected="selected"' : '').' value="'.$a.'">'.$a.'</option>'.PHP_EOL;
		}
		$d .= '</select>'.PHP_EOL;
		// Process order
		$tmp_array = array(
			'y'	=> '{%year%}',
			'm'	=> '{%month%}',
			'd'	=> '{%day%}',
		);
		if (empty($show_what)) {
			$show_what = 'ymd';
		}
		$show_what = str_replace(array_keys($tmp_array), array_values($tmp_array), $show_what);
		return str_replace(array_values($tmp_array), array($y,$m,$d), $show_what);
	}

	/**
	*/
	function time_box ($selected = '', $name_postfix = '', $add_str = '', $show_text = 1, $translate = 1) {
		if (is_array($selected)) {
			$extra = (array)$extra + $name;
			$selected = $extra['selected'];
			$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
			$selected = $extra['selected'];
			$show_text = isset($extra['show_text']) ? $extra['show_text'] : 1;
			$translate = isset($extra['translate']) ? $extra['translate'] : 1;
			$add_str = isset($extra['add_str']) ? $extra['add_str'] : '';
			if ($extra['class']) {
				$add_str .= ' class="'.$extra['class'].'" ';
			}
			if ($extra['style']) {
				$add_str .= ' style="'.$extra['style'].'" ';
			}
		}
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if (strlen($selected))	{
			// Process timestamp (convert it to the 'Y-m-d' pattern)
			if (is_numeric($selected)) {
				$selected = gmdate('H:i:s', $selected);
			}
			list ($hour, $minute, $second) = explode(':', $selected);
		}
		$body .= '<select name="hour'.$name_postfix.'"'.(_class('html')->AUTO_ASSIGN_IDS ? ' id="'.$extra['id']. $name_postfix.'_hour"' : '').' class="span1 col-lg-1">'.PHP_EOL;
		$body .= $show_text ? '<option '.($hour == '' ? 'selected="selected"' : '').' value="">-'.($translate ? t('hour') : 'hour').'-</option>'.PHP_EOL : '';
		for ($a = 0; $a <= 23; $a++) {
			$body .= '<option '.(($hour == $a && $hour != '') ? 'selected="selected"' : '').' value="'.$a.'">'.$a.'</option>'.PHP_EOL;
		}
		$body .= '</select>'.PHP_EOL;
		$body .= '<select name="minute'.$name_postfix.'"'.(_class('html')->AUTO_ASSIGN_IDS ? ' id="'.$extra['id']. $name_postfix.'_minute"' : '').' class="span1 col-lg-1">'.PHP_EOL;
		$body .= $show_text ? '<option '.($minute == '' ? 'selected="selected"' : '').' value="">-'.($translate ? t('minute') : 'minute').'-</option>'.PHP_EOL : '';
		for ($a = 0; $a <= 59; $a++) {
			$body .= '<option '.(($minute == $a && $minute != '') ? 'selected="selected"' : '').' value="'.$a.'">'.$a.'</option>'.PHP_EOL;
		}
		$body .= '</select>'.PHP_EOL;
		$body .= '<select name="second'.$name_postfix.'"'.(_class('html')->AUTO_ASSIGN_IDS ? ' id="'.$extra['id']. $name_postfix.'_second"' : '').' class="span1 col-lg-1">'.PHP_EOL;
		$body .= $show_text ? '<option '.($second == '' ? 'selected="selected"' : '').' value="">-'.($translate ? t('second') : 'second').'-</option>'.PHP_EOL : '';
		for ($a = 0; $a <= 59; $a++) {
			$body .= '<option '.(($second == $a && $second != '') ? 'selected="selected"' : '').' value="'.$a.'">'.$a.'</option>'.PHP_EOL;
		}
		$body .= '</select>'.PHP_EOL;
		return $body;
	}

	/**
	*/
	function date_box2 ($name, $selected = '', $years = '', $add_str = '', $show_what = 'ymd', $show_text = 1, $translate = 1) {
		if (is_array($selected)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
			$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
			$selected = $extra['selected'];
			$years = isset($extra['years']) ? $extra['years'] : '';
			$show_what = isset($extra['show_what']) ? $extra['show_what'] : 'ymd';
			$show_text = isset($extra['show_text']) ? $extra['show_text'] : 1;
			$translate = isset($extra['translate']) ? $extra['translate'] : 1;
			$add_str = isset($extra['add_str']) ? $extra['add_str'] : '';
			if ($extra['class']) {
				$add_str .= ' class="'.$extra['class'].'" ';
			}
			if ($extra['style']) {
				$add_str .= ' style="'.$extra['style'].'" ';
			}
		}
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if (strlen($selected))	{
			// Process timestamp (convert it to the 'Y-m-d' pattern)
			if (is_numeric($selected)) {
				$selected = gmdate('Y-m-d', $selected);
			}
			list($year, $month, $day) = explode('-', $selected);
		}
		if (strlen($years)) {
			list($start_year, $end_year) = explode('-', $years);
		} else {
			$start_year = 1900;
			$end_year = gmdate('Y');
		}
		$y .= PHP_EOL.'<select name="'.$name.'[year]"'.(_class('html')->AUTO_ASSIGN_IDS ? ' id="'.$extra['id'].'_year"' : '').' class="span1 col-lg-1">'.PHP_EOL;
		$y .= $show_text ? '<option '.(!$year ? 'selected="selected"' : '').' value="">-'.($translate ? t('year') : 'year').'-</option>'.PHP_EOL : '';
		for ($a = $start_year; $a <= $end_year; $a++) {
			$y .= '<option '.(($year == $a) ? 'selected="selected"' : '').' value="'.$a.'">'.$a.'</option>'.PHP_EOL;
		}
		$y .= '</select>'.PHP_EOL;
		$m .= '<select name="'.$name.'[month]"'.(_class('html')->AUTO_ASSIGN_IDS ? ' id="'.$extra['id'].'_month"' : '').' class="span1 col-lg-1">'.PHP_EOL;
		$m .= $show_text ? '<option '.(!$month ? 'selected="selected"' : '').' value="">-'.($translate ? t('month') : 'month').'-</option>'.PHP_EOL : '';
		for ($a = 1; $a <= 12; $a++) {
			$m .= '<option '.(($month == $a) ? 'selected="selected"' : '').' value="'.$a.'">'.($translate ? t($this->_months($a)) : $this->_months($a)) .'</option>'.PHP_EOL;
		}
		$m .= '</select>'.PHP_EOL;
		$d .= '<select name="'.$name.'[day]"'.(_class('html')->AUTO_ASSIGN_IDS ? ' id="'.$extra['id'].'_day"' : '').' class="span1 col-lg-1">'.PHP_EOL;
		$d .= $show_text ? '<option '.(!$day ? 'selected="selected"' : '').' value="">-'.($translate ? t('day') : 'day').'-</option>'.PHP_EOL : '';
		for ($a = 1; $a <= 31; $a++) {
			$d .= '<option '.(($day == $a) ? 'selected="selected"' : '').' value="'.$a.'">'.$a.'</option>'.PHP_EOL;
		}
		$d .= '</select>'.PHP_EOL;
		$tmp_array = array(
			'y'	=> '{%year%}',
			'm'	=> '{%month%}',
			'd'	=> '{%day%}',
		);
		if (empty($show_what)) {
			$show_what = 'ymd';
		}
		$show_what = str_replace(array_keys($tmp_array), array_values($tmp_array), $show_what);
		return str_replace(array_values($tmp_array), array($y,$m,$d), $show_what);
	}

	/**
	*/
	function time_box2 ($name, $selected = '', $add_str = '', $show_text = 1, $translate = 1) {
		if (is_array($selected)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
			$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
			$selected = $extra['selected'];
			$show_text = isset($extra['show_text']) ? $extra['show_text'] : 1;
			$translate = isset($extra['translate']) ? $extra['translate'] : 1;
			$add_str = isset($extra['add_str']) ? $extra['add_str'] : '';
			if ($extra['class']) {
				$add_str .= ' class="'.$extra['class'].'" ';
			}
			if ($extra['style']) {
				$add_str .= ' style="'.$extra['style'].'" ';
			}
		}
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if (strlen($selected))	{
			// Process timestamp (convert it to the 'Y-m-d' pattern)
			if (is_numeric($selected)) {
				$selected = gmdate('H:i:s', $selected);
			}
			list ($hour, $minute, $second) = explode(':', $selected);
		}
		$body .= PHP_EOL.'<select name="'.$name.'"[hour]'.(_class('html')->AUTO_ASSIGN_IDS ? ' id="'.$extra['id'].'_hour"' : '').' class="span1 col-lg-1">'.PHP_EOL;
		$body .= $show_text ? '<option '.($hour == "" ? 'selected="selected"' : '').' value="">-'.($translate ? t('hour') : 'hour').'-</option>'.PHP_EOL : '';
		for ($a = 0; $a <= 23; $a++) {
			$body .= '<option '.(($hour == $a && $hour != '') ? 'selected="selected"' : '').' value="'.$a.'">'.$a.'</option>'.PHP_EOL;
		}
		$body .= '</select>'.PHP_EOL;
		$body .= '<select name="'.$name.'"[minute]'.(_class('html')->AUTO_ASSIGN_IDS ? ' id="'.$extra['id'].'_minute"' : '').' class="span1 col-lg-1">'.PHP_EOL;
		$body .= $show_text ? '<option '.($minute == '' ? 'selected="selected"' : '').' value="">-'.($translate ? t('minute') : 'minute').'-</option>'.PHP_EOL : '';
		for ($a = 0; $a <= 59; $a++) {
			$body .= '<option '.(($minute == $a && $minute != '') ? 'selected="selected"' : '').' value="'.$a.'">'.$a.'</option>'.PHP_EOL;
		}
		$body .= '</select>'.PHP_EOL;
		$body .= '<select name="'.$name.'"[second]'.(_class('html')->AUTO_ASSIGN_IDS ? ' id="'.$extra['id'].'_second"' : '').' class="span1 col-lg-1">'.PHP_EOL;
		$body .= $show_text ? '<option '.($second == '' ? 'selected="selected"' : '').' value="">-'.($translate ? t('second') : 'second').'-</option>'.PHP_EOL : '';
		for ($a = 0; $a <= 59; $a++) {
			$body .= '<option '.(($second == $a && $second != '') ? 'selected="selected"' : "").' value="'.$a.'">'.$a.'</option>'.PHP_EOL;
		}
		$body .= '</select>'.PHP_EOL;
		return $body;
	}

	/**
	*/
	function datetime_box2 ($name, $selected = '', $years = '', $add_str = '', $show_what = 'ymd', $show_text = 1, $translate = 1) {
		return $this->date_box2($name, $selected, $years, $add_str, $show_what, $show_text, $translate)
			.$this->time_box2($name, $selected, $add_str, $show_text, $translate);
	}

	/**
	* Month name
	*/
	function _months ($num, $lang = '') {
		$m_array = array(
			'January',
			'February',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December',
		);
		$num--;
		return (($num > 12) || ($num < 0)) ? $num : $m_array[$num];
	}
}
