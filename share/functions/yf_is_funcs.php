<?php

if (!function_exists('no_graphics')) {
	function no_graphics($val = null) { return main()->no_graphics($val); }
}
if (!function_exists('is_no_graphics')) {
	function is_no_graphics() { return main()->no_graphics(); }
}
if (!function_exists('is_post')) {
	function is_post() { return main()->is_post(); }
}
if (!function_exists('is_db')) {
	function is_db() { return main()->is_db(); }
}
if (!function_exists('is_ajax')) {
	function is_ajax() { return main()->is_ajax(); }
}
if (!function_exists('is_console')) {
	function is_console() { return main()->is_console(); }
}
if (!function_exists('is_redirect')) {
	function is_redirect() { return main()->is_redirect(); }
}
if (!function_exists('is_common_page')) {
	function is_common_page() { return main()->is_common_page(); }
}
if (!function_exists('is_unit_test')) {
	function is_unit_test() { return main()->is_unit_test(); }
}
if (!function_exists('is_logged_in')) {
	function is_logged_in() { return main()->is_logged_in(); }
}
if (!function_exists('is_spider')) {
	function is_spider() { return main()->is_spider(); }
}
if (!function_exists('is_https')) {
	function is_https() { return main()->is_https(); }
}
if (!function_exists('is_hhvm')) {
	function is_hhvm() { return main()->is_hhvm(); }
}
if (!function_exists('is_dev')) {
	function is_dev() { return main()->is_dev(); }
}
if (!function_exists('is_debug')) {
	function is_debug() { return main()->is_debug(); }
}
if (!function_exists('is_banned')) {
	function is_banned() { return main()->is_banned(); }
}
if (!function_exists('is_site_path')) {
	function is_site_path() { return main()->is_site_path(); }
}
if (!function_exists('is_403')) {
	function is_403() { return main()->is_403(); }
}
if (!function_exists('is_404')) {
	function is_404() { return main()->is_404(); }
}
if (!function_exists('is_blocks_task_403')) {
	function is_blocks_task_403() { return main()->is_blocks_task_403(); }
}
if (!function_exists('is_blocks_task_404')) {
	function is_blocks_task_404() { return main()->is_blocks_task_404(); }
}
if (!function_exists('is_503')) {
	function is_503() { return main()->is_503(); }
}
if (!function_exists('is_cache_on')) {
	function is_cache_on() { return main()->is_cache_on(); }
}
if (!function_exists('is_output_cache_on')) {
	function is_output_cache_on() { return main()->is_output_cache_on(); }
}
if (!function_exists('is_mobile')) {
	function is_mobile() { return main()->is_mobile(); }
}
