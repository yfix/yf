<?php

class form2_stacked_sample {
	function show() {
		$replace = array(
			'title'			=> 'title',
			'amount'		=> '50',
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
