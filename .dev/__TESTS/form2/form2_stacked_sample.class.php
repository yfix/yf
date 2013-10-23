<?php

class form2_stacked_sample {
	function show() {
		$replace = array(
			'title'			=> 'title',
			'amount'		=> '50',
			'type'			=> common()->select_box('type',array(1,2)),
			'split_period'	=> common()->select_box('split',array(1,2)),
			'duration'		=> array(
				'day'	=> 10,
				'week'	=> 2,
				'month'	=> 3,
				'year'	=> 0,
			),
		);
		$body .= form($replace)
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
			->submit()
		;
		return $body;
	}
}
