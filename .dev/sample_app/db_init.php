#!/usr/bin/php
<?php

require __DIR__.'/db_setup.php';

$tmp_dir = '/tmp/yf_sample_app/';

$_POST = array(
	'install_project_path'				=> $tmp_dir,
	'install_yf_path'					=> dirname(dirname(__DIR__)).'/',
	'install_db_host'					=> DB_HOST,
	'install_db_name'					=> DB_NAME,
	'install_db_user'					=> DB_USER,
	'install_db_pswd'					=> DB_PSWD,
	'install_db_prefix'					=> DB_PREFIX,
	'install_web_path'					=> 'http://localhost:33380/',
	'install_admin_login'				=> 'admin',
	'install_admin_pswd'				=> '123456',
	'install_rw_base'					=> '/',
	'install_web_name'					=> 'YF Sample App',
	'install_checkbox_rw_enabled'		=> '',
	'install_checkbox_db_create'		=> '1',
	'install_checkbox_db_drop_existing'	=> '1',
	'install_checkbox_demo_data'		=> '',
	'install_checkbox_debug_info'		=> '',
);

require dirname(__DIR__).'/install/install.php';
