<?php

class form2_auto_test {
	function show() {
		return form('', array(
				'no_form'	=> 1,
				'tabs'		=> array(
					'class'		=> 'span6 col-lg-6',
					'show_all'	=> 1,
					'no_headers'=> 1,
				),
			))
#			->tab_start('auto1')
#			->container(
#				form('SELECT * FROM '.db('user').' WHERE id=1')->auto()
#			)
#			->tab_end()
			->tab_start('auto2')
			->container(
				form()->auto(db('user'), 1)
			)
			->tab_end()
		;
	}
}
