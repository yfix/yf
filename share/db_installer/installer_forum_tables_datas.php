<?php
// YF_FORUM datas
$data = my_array_merge((array)$data, array(
"forum_categories"	=> array(
	99999 => array(
		"id"	=> 1,
		"name"	=> "Main",
		"desc"	=> "Your first forum category",
	),
),
"forum_forums"	=> array(
	99999 => array(
		"id"		=> 1,
		"category"	=> 1,
		"name"		=> "Test forum",
		"desc"		=> "First auto-created test forum",
		"created"	=> time(),
	),
),
));

