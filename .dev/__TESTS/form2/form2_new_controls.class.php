<?php

class form2_new_controls {
	function show() {
		return form($r)
			->icon_select_box(array('selected' => 'icon-anchor'))
			->currency_box(array('selected' => 'RUB'))
			->language_box(array('selected' => 'uk'))
			->timezone_box(array('selected' => 'UTC'))
			->country_box(array('selected' => 'US'))
			->user_method_box(array('desc' => 'user method'))
			->admin_method_box(array('desc' => 'admin method'))
			->user_template_box(array('desc' => 'user template'))
			->admin_template_box(array('desc' => 'admin template'))
			->user_location_box(array('desc' => 'user location'))
			->admin_location_box(array('desc' => 'admin location'))
			->region_box() // TODO
#			->image()
#			->time_box()
#			->date_box()
#			->datetime_box()
#			->birth_box()
			->navbar_start()->div_box('testdiv1', array('val1','val2'))->navbar_end()
			->navbar_start()->div_box('testdiv2', array('val1','val2'))->navbar_end()
/*
			->navbar_start()->currency_box()->navbar_end()
			->navbar_start()->language_box()->navbar_end()
			->navbar_start()->timezone_box()->navbar_end()
			->navbar_start()->country_box()->navbar_end()
			->navbar_start()->region_box()->navbar_end()
*/
		;
	}
}
