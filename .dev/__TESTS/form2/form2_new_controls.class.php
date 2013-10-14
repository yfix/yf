<?php

class test2 {
	function show() {
		return form($replace/*, array('css_framework' => 'empty')*/)
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

			->div_box('testdiv', array('val1','val2'))
			->currency_box()
/*
			->country_box()
			->region_box()
			->language_box()
			->timezone_box()
/*
			->method_select_box()
			->template_select_box()
			->location_select_box()
			->icon_select_box()
			->image()
			->birth_box()
*/
			->submit()
		;
		return $body;
	}
}
