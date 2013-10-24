#!/usr/bin/php
<?php

#require dirname(__FILE__).'/yf_unit_tests_setup.php';
#_class('db');

$tmp_dir = '/tmp/yf_for_unit_tests/';
#chdir($tmp_dir);

$_POST = array(
	'install_project_path'				=> $tmp_dir,
	'install_yf_path'					=> dirname(dirname(dirname(__FILE__))).'/',
	'install_db_host'					=> 'localhost',
	'install_db_name'					=> 'yf_for_unit_tests',
	'install_db_user'					=> 'root',
	'install_db_pswd'					=> '123456',
	'install_db_prefix'					=> 't_',
	'install_web_path'					=> 'http://localhost/yf_unit_tests/',
	'install_admin_login'				=> 'admin',
	'install_admin_pswd'				=> '123456',
	'install_rw_base'					=> '/',
	'install_web_name'					=> 'YF Test Website',
	'install_checkbox_rw_enabled'		=> '',
	'install_checkbox_db_create'		=> '1',
	'install_checkbox_db_drop_existing'	=> '1',
	'install_checkbox_demo_data'		=> '',
	'install_checkbox_debug_info'		=> '',
);

require dirname(dirname(__FILE__)).'/__INSTALL/install.php';
