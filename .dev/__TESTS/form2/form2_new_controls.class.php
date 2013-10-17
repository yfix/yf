<?php

class form2_new_controls {
	function show() {
		$params = array('no_form' => 1);//, array('css_framework' => 'empty','class' => 'form-inline')
		return form($r, $params)
/*
			->text('title')
			->select_box('want', array('val1','val2'))
			->row_start(array('desc' => 'For a period of'))
				->number('duration_day', 'day')
				->number('duration_week', 'week')
				->number('duration_month', 'month')
				->number('duration_year', 'year')
			->row_end()
			->row_start(array('desc' => 'Interest rate'))
				->number('percent', array('class' => 'input-small'))
				->button('per', array('disabled' => 1))
				->select_box('split', array('val1','val2'))
			->row_end()
			->textarea('desc')
*/
#			->div_box('testdiv', array('val1','val2'))

			->navbar_start()->currency_box()->navbar_end()
			->navbar_start()->language_box()->navbar_end()
			->navbar_start()->timezone_box()->navbar_end()
			->navbar_start()->country_box()->navbar_end()
#			->navbar_start()->region_box()->navbar_end()
/*
			->method_select_box()
			->template_select_box()
			->location_select_box()
			->icon_select_box()
			->image()
			->birth_box()
*/
#			->submit()
		;
		return $body;
	}
}
