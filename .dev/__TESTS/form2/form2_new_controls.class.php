<?php

class form2_new_controls {
	function show() {
/*
		return '
<div class="bfh-selectbox">
  <input type="hidden" name="selectbox3" value="">
  <a class="bfh-selectbox-toggle" role="button" data-toggle="bfh-selectbox" href="#">
    <span class="bfh-selectbox-option bfh-selectbox-medium" data-option="12">Option 12</span>
    <b class="caret"></b>
  </a>
  <div class="bfh-selectbox-options">
    <input type="text" class="bfh-selectbox-filter">
    <div role="listbox">
      <ul role="option">
        <li><a tabindex="-1" href="#" data-option="1">Option 1</a></li>
        <li><a tabindex="-1" href="#" data-option="2">Option 2</a></li>
        <li><a tabindex="-1" href="#" data-option="3">Option 3</a></li>
        <li><a tabindex="-1" href="#" data-option="4">Option 4</a></li>
        <li><a tabindex="-1" href="#" data-option="5">Option 5</a></li>
        <li><a tabindex="-1" href="#" data-option="6">Option 6</a></li>
        <li><a tabindex="-1" href="#" data-option="7">Option 7</a></li>
        <li><a tabindex="-1" href="#" data-option="8">Option 8</a></li>
        <li><a tabindex="-1" href="#" data-option="9">Option 9</a></li>
        <li><a tabindex="-1" href="#" data-option="10">Option 10</a></li>
        <li><a tabindex="-1" href="#" data-option="11">Option 11</a></li>
        <li><a tabindex="-1" href="#" data-option="12">Option 12</a></li>
        <li><a tabindex="-1" href="#" data-option="13">Option 13</a></li>
        <li><a tabindex="-1" href="#" data-option="14">Option 14</a></li>
        <li><a tabindex="-1" href="#" data-option="15">Option 15</a></li>
      </ul>
    </div>
  </div>
</div>
		';
*/
/*
		return '
<div>
	<span class="bfh-countries" data-country="US" data-flags="true"></span>
</div>

<div>
	<i class="bfh-flag-AF"></i>Afghanistan
</div>

<div class="bfh-selectbox" data-flags="true">
	<input type="hidden" value="US">
	<a class="bfh-selectbox-toggle" role="button" data-toggle="bfh-selectbox" href="#">
		<span class="bfh-selectbox-option bfh-selectbox-medium" data-option=""><i class="glyphicon bfh-flag-US"></i> United States</span>
		<b class="caret"></b>
	</a>
	<div class="bfh-selectbox-options">
		<input type="text" class="bfh-selectbox-filter">
		<div role="listbox">
			<ul role="option">
				<li><a tabindex="-1" href="#" data-option=""></a></li>
				<li><a tabindex="-1" href="#" data-option="AF"><i class="glyphicon bfh-flag-AF"></i>Afghanistan</a></li>
				<li><a tabindex="-1" href="#" data-option="US"><i class="glyphicon bfh-flag-US"></i>United States</a></li>
			</ul>
		</div>
	</div>
</div>
		';
*/
/*
		$data = array();
		$a = db()->get_all('SELECT * FROM '.db('countries').' ORDER BY name ASC');
		foreach ((array)$a as $v) {
			$data[$v['code']] = '<i class="bfh-flag-'.$v['code'].'"></i> '. trim($v['name']).' ['.strtoupper($v['code']).']';
		}
		return _class('html_controls')->list_box('country', $data, 'US', array());
*/
		return form()
			->currency_box(array('selected' => 'RUB'))
			->language_box(array('selected' => 'uk'))
			->timezone_box(array('selected' => 'UTC'))
			->country_box(array('selected' => 'US'))
			->region_box()
		;

#		$params = array('no_form' => 1);//, array('css_framework' => 'empty','class' => 'form-inline')
#		return form($r, $params)
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
/*
			->navbar_start()->currency_box()->navbar_end()
			->navbar_start()->language_box()->navbar_end()
			->navbar_start()->timezone_box()->navbar_end()
			->navbar_start()->country_box()->navbar_end()
			->navbar_start()->region_box()->navbar_end()
*/
/*
			->method_select_box()
			->template_select_box()
			->location_select_box()
			->icon_select_box()
			->image()
			->birth_box()
*/
#			->submit();
		return $body;
	}
}
