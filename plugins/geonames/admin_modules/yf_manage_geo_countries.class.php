<?php

/**
*/
load('admin_methods', 'framework', 'classes/');
class yf_manage_geo_countries extends yf_admin_methods {
	public $params = array(
		'table' => 'countries',
		'id'	=> 'code',
	);
}
