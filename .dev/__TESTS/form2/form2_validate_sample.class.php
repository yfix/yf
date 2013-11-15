<?php

class form2_validate_sample {
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
		$validate_rules = array(
			'__form_id__'	=> 'validate_sample_form',
			'title'         => array('trim|required|xss_clean'),
			'type'          => array('trim|required|xss_clean'),
			'amount'        => array('trim|required|min_length[1]|max_length[10]|xss_clean|numeric'),
			'percent'       => array('trim|required|min_length[1]|max_length[4]|xss_clean|numeric'),
			'split_period'  => array('trim|min_length[1]|max_length[1]|xss_clean|'),
			'descr'         => array('trim|xss_clean'),
			'duration'      => 'required_any[duration_*]',
			'integer'       => array('integer'),
		);
		$a = $_POST;		
		$form_html .= form($a)
			->validate($validate_rules)
		//	->db_insert_if_ok('credits', array('group','email','password','first_name','last_name','middle_name'), array('add_date' => time()), array('on_success_text' => 'Your account was created successfully!'))
			->text('title')
			->select_box('type', array(1,2), array('desc' => 'I want'))
            ->money('amount')
            ->row_start(array('desc' => 'For a period of', 'name' => 'duration'))
                ->number('duration_day', 'day', array('class' => 'input-small'))
                ->number('duration_week', 'week', array('class' => 'input-small'))
                ->number('duration_month', 'month', array('class' => 'input-small'))
                ->number('duration_year', 'year', array('class' => 'input-small'))
            ->row_end()
            ->row_start(array('desc' => 'Interest rate'))
                ->number('percent', array('class' => 'input-small'))
                ->button('per', array('disabled' => 1))
                ->select_box('split_period', array('val1','val2'))
            ->row_end()
            ->text('integer')
            ->textarea('desc')
            ->submit()
        ;
        $body .= _e();
        $body .= $form_html;
        return  $body ;

	}
}
