<?php

/**
* Test sub-class
*/
class profy_test_ssh {

	/**
	* Profy module constructor
	*/
	function _init () {
		$this->TEST_OBJ = module("test");
	}

	/**
	* Testing SSH wrapper
	*/
	function run_test () {
		$_GET["id"] = preg_replace("/[^0-9\.]/i", "", substr($_GET["id"], 0, 15));
		$server_info = array(
			"ssh_host"	=> $_GET["id"] ? $_GET["id"] : "192.168.1.2",
			"ssh_user"	=> "root",
			"ssh_pswd"	=> "123456",
		);
		$SSH_OBJ = main()->init_class("ssh", "classes/");

		$body .= "<br /><b>Test exec \"ifconfig\"</b><br /><br />";
		$body .= _ssh_exec($server_info, "ifconfig");
		$body .= "<br />";

		$body .= "<br /><b>File info: \"/etc/fstab\"</b><br /><br />";
		$body .= print_r($SSH_OBJ->file_info($server_info, "/etc/fstab"), 1);
		$body .= "<br />";

		$body .= "<br /><b>Read \"/etc/fstab\"</b><br /><br />";
		$body .= $SSH_OBJ->read_file($server_info, "/etc/fstab");
		$body .= "<br />";

		$body .= "<br /><b>Test remote \"file_exists\"</b><br /><br />";
		$_is_file_exists = $SSH_OBJ->file_exists($server_info, "/etc/fstab");
		$body .= "<br /><br />File must exist: ". ($_is_file_exists ? "EXIST" : "NOT EXIST")."<br /><br />";
		$_is_file_exists2 = $SSH_OBJ->file_exists($server_info, "/var/www/UPANEL/db_setup132.php");
		$body .= "<br /><br />File must NOT exist: ". ($_is_file_exists2 ? "EXIST" : "NOT EXIST")."<br /><br />";

//		$SSH_OBJ->rmdir($server_info, "/var/www/__tmp");
//		$SSH_OBJ->mkdir($server_info, "/var/www/__tmp/111/222/333");
//		$SSH_OBJ->mkdir($server_info, "/var/www/__tmp/111/222/444");
//		$SSH_OBJ->write_file($server_info, INCLUDE_PATH."db_setup.php", "/var/www/__tmp/111/222/333/tmp.tmp");
//		$SSH_OBJ->write_string($server_info, "...Testing string...", "/var/www/__tmp/111/222/333/tmp2.tmp");
//		$SSH_OBJ->write_string($server_info, array("/var/www/__tmp2/testing_1" => "some content 1", "/var/www/__tmp2/testing_2" => "some content 2"));
//		$SSH_OBJ->unlink($server_info, "/var/www/__tmp/111/222/333/tmp.tmp");
//		$DIR_OBJ = main()->init_class("dir", "classes/");
//		$DIR_OBJ->delete_dir(INCLUDE_PATH."__123testing123", 1);
//		$SSH_OBJ->download_dir($server_info, "/etc/apache2", INCLUDE_PATH."__123testing123");
//		$SSH_OBJ->upload_dir($server_info, PF_PATH."__UPDATES", "/var/www/__tmp_new", "", "/(svn|git)/i");
//		$SSH_OBJ->rmdir($server_info, "/var/www/__tmp");

//		$body .= "<br /><b>Scan Dir: \"/etc\" with \"0\" depth level (only direct children)</b><br /><br />";
//		$_result = $SSH_OBJ->scan_dir($server_info, "/var/www/UPANEL/templates", "", "/(svn|git)/i", 1);
//		$_result = $SSH_OBJ->scan_dir($server_info, "/etc", "", "/(svn|git)/i", 0);
//		$body .= "<small>".print_r($_result, 1)."</small>";
//		$body .= print_r($SSH_OBJ->clean_path($_result), 1);
//		$body .= "<br />";

		return "<pre>\n".$body."\n</pre>";
	}
}
