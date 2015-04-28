<?php

class form2_stacked_sample {
	function show() {
		$replace = array(
			'title'			=> 'title',
			'amount'		=> '50',
		);
		$body .= form($replace)
			->validate(array(
				'duration_month2' => 'trim|required|gt[10]',
				'desc' => 'trim|required',
			))
			->text('title')
			->select_box('want', array('val1','val2'))
			->row_start(array('desc' => 'For a period of'))
				->number('duration_day', 'day')
				->number('duration_week', 'week')
				->number('duration_month', 'month')
				->number('duration_year', 'year')
			->row_end()
			->row_start(array('desc' => 'Interest rate'))
				->number('percent', array('class_add' => 'input-small'))
				->button('per', array('disabled' => 1))
				->select_box('split', array('val1','val2'))
			->row_end()
			->row_start(array('desc' => 'For a period of'))
				->number('duration_day2', 'day')
				->number('duration_week2', 'week', array('show_label' => 1))
				->number('duration_month2', 'month')
				->number('duration_year2', 'year')
			->row_end()
			->row_start(array('desc' => 'order'))
				->select_box('order_by', array('name' => 'name', 'desc' => 'desc'), array('show_text' => 1, 'class_add' => 'input-medium'))
				->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'), array('outer_label' => 'Direction'))
			->row_end()
			->textarea('desc')
			->submit()
		;
		return $body
			. $this->_self_source(__FUNCTION__)
		;
	}
	function _self_source($method) {
		asset('highlightjs');
		$source = _class('core_api')->get_method_source(__CLASS__, $method);
		return '<div id="func_self_source_'.$name.'"><pre class="prettyprint lang-php"><code>'._prepare_html($source['source']).'</code></pre></div> ';
	}
}
