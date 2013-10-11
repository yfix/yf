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

		$body .= tpl()->parse_string('
			<form action="{form_action}" enctype="multipart/form-data" method="post" class="form-horizontal">
				<div class="control-group form-group">
					<label class="control-label col-lg-2" for="title">Title</label>
					<div class="controls col-lg-4">
						<input name="title" type="text" id="title" class="form-control " placeholder="Title" value="{title}" required="1">
					</div>
				</div>
				{form_row("box","type","I want")}
				<div class="control-group form-group">
					<label class="control-label col-lg-2" for="amount">Amount</label>
					<div class="controls col-lg-4">
						<div class="input-group input-prepend input-append">
							<span class="add-on input-group-addon">$</span>
							<input name="amount" type="text" id="amount" class="form-control  input-small" placeholder="Amount" value="{amount}" maxlength="8" required="1">
							<span class="add-on input-group-addon">.00</span>
						</div>
					</div>
				</div>
				<div class="control-group form-group">
					<label class="control-label col-lg-2" for="amount">{t(For a period of )}</label>
					<div class="controls col-lg-4">
						<input class="input-small" type="number" min="0" max="9999" name="duration[day]"   placeholder="day">
						<input class="input-small" type="number" min="0" max="9999" name="duration[week]"  placeholder="week">
						<input class="input-small" type="number" min="0" max="9999" name="duration[month]" placeholder="month">
						<input class="input-small" type="number" min="0" max="9999" name="duration[year]"  placeholder="year">
					</div>
				</div>
				<!--  <input name="quantity" type="number" id="quantity" class="form-control  input-small" placeholder="Quantity" maxlength="10"> -->
				<div class="control-group form-group">
					<label class="control-label col-lg-2" for="amount">{t(Interest rate)}</label>
					<div class="controls col-lg-4">
						<input class="input-small" type="number" min="0" max="1000" name="percent" placeholder="percent" value="{percent}" required="1">
						<div class="input-group input-prepend input-append">
							<span class="add-on input-group-addon">{t(per)}</span>
							{split_period}
						</div>
					</div>
				</div>
				{form_row("textarea","desc","Description")}
				{form_row("save_and_back")}
				</form>
		', $replace);

		$body .= '<hr>';

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
