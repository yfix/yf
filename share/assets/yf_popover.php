<?php

return array(
	'versions' => array(
		'master' => array(
			'jquery' => 
<<<END
	try {
		$('.yf_tip').popover({
			'trigger' : 'hover',
			'delay'   : { 'show' : 100, 'hide' : 500 }
		})
	} catch(e) { console.log(e) }
END
		),
	),
);
