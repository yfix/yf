<?php

return array(
	'versions' => array('master' => array(
		'jquery' => 
			'try {
				$(".yf_tip").popover({
					"trigger"	: "hover",
					"delay"		: { "show" : 0, "hide" : 0 },
					"animation" : false
				})
			} catch(e) { console.log(e) }'
	)),
);
