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
#			->container('<div class="navbar span2"><div class="navbar-inner"><ul class="nav">'.form($r, $params)->currency_box(array('stacked' => 1)).'</ul></div></div>', array('stacked' => 1))
#			->navbar_wrap(form($r, $params)->currency_box(array('stacked' => 1)))
#			->navbar_wrap(form($r, $params)->currency_box(array('stacked' => 1)))

			->navbar_start()->currency_box()->navbar_end()
			->navbar_start()->language_box()->navbar_end()
			->navbar_start()->timezone_box()->navbar_end()

#			->navbar_wrap(form($r, $params)->currency_box(array('stacked' => 1)))
#			->container('<div class="navbar span3"><div class="navbar-inner"><ul class="nav">'
#				.form()->tpl_row('currency_box', $replace = array(), $name = '', $desc = '', $extra = array('no_label' => 1, 'stacked' => 1))
#				.'</ul></div></div>'
#			)
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
#			->submit()
		;
		return $body;
	}
}
