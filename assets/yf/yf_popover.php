<?php

return [
    'versions' => ['master' => [
        'jquery' => 'try {
				$(".yf_tip").popover({
					"trigger"	: "hover click focus",
//					"delay"		: { "show" : 0, "hide" : 200 },
					"animation" : false,
					"placement" : "bottom",
				})
			} catch(e) { console.log(e) }',
    ]],
];
