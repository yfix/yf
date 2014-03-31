<?php

class form2_navbar {
	function go() {
#		$body[] = form_item()->country_box(array('selected' => 'US'));
		$body[] = form_item()->country_box(array('selected' => 'US', 'renderer' => 'div_box'));
		$body[] = form_item()->language_box(array('selected' => 'ru', 'renderer' => 'div_box'));
		$body[] = form_item()->currency_box(array('selected' => 'UAH', 'renderer' => 'div_box'));
		$body[] = form_item()->timezone_box(array('selected' => 'UTC', 'renderer' => 'div_box'));
		return implode($body);
	}
}