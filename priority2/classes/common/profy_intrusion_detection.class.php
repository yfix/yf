<?php

/**
* Intrusion detection methods here
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_intrusion_detection {

	/**
	* Contructor
	*/
	function _init () {
		$this->BASE_PATH = PF_PATH.'libs/phpids/';
		// set the include path properly for PHPIDS
		set_include_path(
			get_include_path()
			. PATH_SEPARATOR
			. $this->BASE_PATH
		);
		$this->config = array(
			// basic settings - customize to make the PHPIDS work at all
			'General'	=> array(
				"filter_type"		=> "xml",
				"base_path"			=> $this->BASE_PATH,
				"use_base_path"		=> false,
				"filter_path"		=> $this->BASE_PATH."IDS/default_filter.xml",
				"tmp_path"			=> INCLUDE_PATH. "uploads/tmp/",
				"scan_keys"			=> false,
				// in case you want to use a different HTMLPurifier source, specify it here
				// By default, those files are used that are being shipped with PHPIDS
				//"HTML_Purifier_Path"	=> $this->BASE_PATH."IDS/vendors/htmlpurifier/HTMLPurifier.auto.php",
				//"HTML_Purifier_Cache"	=> $this->BASE_PATH."IDS/vendors/htmlpurifier/HTMLPurifier/DefinitionCache/Serializer",
				// define which fields contain html and need preparation before 
				// hitting the PHPIDS rules (new in PHPIDS 0.5)
				"html"				=> array(
					"__wysiwyg",
				),
				// define which fields contain JSON data and should be treated as such 
				// for fewer false positives (new in PHPIDS 0.5.3)
				"json"				=> array(
					"__jsondata",
				),
				// define which fields shouldn't be monitored (a[b]=c should be referenced via a.b)
				"exceptions"		=> array(
					"__utmz",
					"__utmc",
				),
				// PHPIDS should run with PHP 5.1.2 but this is untested - set 
				// this value to force compatibilty with minor versions
				"min_php_version"	=> "5.1.6",
			),
			// If you use the PHPIDS logger you can define specific configuration here
			'Logging'	=> array(
				// file logging
				"path"			=> INCLUDE_PATH. "phpids_log.txt",
				// email logging
				// note that enabling safemode you can prevent spam attempts,
				// see documentation
				//"recipients"	=> array(
				//	"test@test.com.invalid",
				//),
				//"subject"		=> "PHPIDS detected an intrusion attempt!",
				//"header"		=> "From: <PHPIDS> info@php-ids.org",
				//"envelope"		=> "",
				//"safemode"		=> true,
				//"allowed_rate"	=> 15,
				// database logging
// CHANGE_ME
				//"wrapper"		=> "mysql:host=".DB_HOST.";port=3306;dbname=".DB_NAME,
				//"user"			=> DB_USER,
				//"password"		=> DB_PSWD,
// TODO
				//"table"			=> db('intrusions'),
				// If you would like to use other methods than file caching you can configure them here
			),
			'Caching'	=> array(
				// caching:      session|file|database|memcached|none
				"caching"=> "none",
				//"expiration_time" => 600,
				// file cache    
				//"path"			=> INCLUDE_PATH. "uploads/tmp/default_filter.cache",
				// database cache
				//"wrapper"		=> "mysql:host=".DB_HOST.";port=3306;dbname=".DB_NAME,
				//"user"		=> DB_USER
				//"password"	=> DB_PSWD,
				//"table"		=> db('phpids_cache'),
				// memcached     
				//"host"		=> "localhost",
                //"port"		=> "11211",
				//"key_prefix"	=> "PHPIDS",
				//"tmp_path"	=> "tmp/memcache.timestamp",
			),
		);
		_mkdir_m($this->config['General']['tmp_path']);
	}

	/**
	* Do check
	*/
	function check () {
		include_once $this->BASE_PATH. 'IDS/Init.php';
		$request = array(
			'REQUEST'	=> $_REQUEST,
			'GET'		=> $_GET,
			'POST'		=> $_POST,
			'COOKIE'	=> $_COOKIE
		);
//		$init = IDS_Init::init(PF_PATH.'libs/phpids/'.'IDS/Config/Config.ini');
		$init = IDS_Init::init();
		$init->setConfig($this->config, true);
		$ids = new IDS_Monitor($request, $init);
		$result = $ids->run();
		if (!$result->isEmpty()) {
			// Take a look at the result object
//			echo $result;
			trigger_error("IDS: Possible intrusion detected, result: ".$result, E_USER_WARNING);
		}
		return false;
	}
}
