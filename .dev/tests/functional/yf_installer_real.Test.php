<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class yf_installer_real_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'mysql5';
		self::_connect();
		self::utils()->truncate_database(self::db_name());
		self::$_bak['ERROR_AUTO_REPAIR'] = self::db()->ERROR_AUTO_REPAIR;
		self::db()->ERROR_AUTO_REPAIR = true;
		$GLOBALS['db'] = self::db();
	}
	public static function tearDownAfterClass() {
#		self::utils()->truncate_database(self::db_name());
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
		self::db()->ERROR_AUTO_REPAIR = self::$_bak['ERROR_AUTO_REPAIR'];
	}
	public static function db_name() {
		return self::$DB_NAME;
	}
	public static function table_name($name) {
		return $name;
	}
	public function test_do_install() {
/*
		$tmp_dir = '/tmp/yf_sample_app/';
		$_POST = array(
			'install_project_path'				=> $tmp_dir,
			'install_yf_path'					=> YF_PATH,
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
		print_r($_POST);
		require YF_PATH.'.dev/install/install.php';
*/
	}
}
