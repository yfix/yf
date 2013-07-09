<?php

/**
* Interface to the Maxmind phone verification services
*/
class yf_maxmind_phone_verify {

	/** @var */
	public $_timeout			= 0;
	/** @var */
	public $_license_key		= "";
	/** @var */
	public $_min_phone_digits	= 7;
	/** @var */
	public $_max_phone_digits	= 10;
	/** @var bool Basic phone check (phone_type) */
	public $IDENTIFY_PHONE		= true;
	/** @var bool Extended phone check (makes a call with code) */
	public $VERIFY_PHONE 		= false;
	/** @var */
	public $_cc_to_verify		= array(
		"AU",
		"GB",
		"US",
		"CA",
	);
	/** @var bool This option is REQUIRED to operate correctly */
	public $REMOTE_ENABLED 	= false;
	/** @var Debug log text */
	public $_action_log		= "";
	/** @var Error log text */
	public $_error_log			= "";

	//-----------------------------------------------------------------------------
	// YF module constructor
	function  _init() {
		$this->_maxmind_phone_id_code = main()->get_data("maxmind_phone_id_code");
		$this->_call_code = main()->get_data("call_codes");
	}

	//-----------------------------------------------------------------------------
	// Check if given country is allowed to check phone
	function  _country_allowed($cc = "") {
		if ($cc && in_array(strtoupper($cc), $this->_cc_to_verify)) {
			return true;
		}
		return false;
	}

	//-----------------------------------------------------------------------------
	// Check given phone format
	function _check_phone_format($phone_num = "") {
		if ($phone_num && strlen($phone_num) <= ($this->_max_phone_digits + 3)) {
			$phone_num = preg_replace("/[^0-9\+]/ims", "", $phone_num);
			if (!preg_match("/^[\+]{0,1}[0-9]{".intval($this->_min_phone_digits).",}\$/ims", $phone_num)) {
				return false;
			}
			if (strlen($phone_num) > $this->_max_phone_digits && !preg_match("/^[\+][0-9]+\$/ims", $phone_num)) {
				return false;
			}
			return true;
		}
		return false;
	}

	//-----------------------------------------------------------------------------
	// Do send request (or use cached result instead)
	function _send_request($phone_num = "", $owner_id = "", $cc = "", $verify_code = "", $license_key = "") {
		$result = false;
		$this->_action_log	= "";
		$this->_error_log	= "";
		// Check allowed country
		if (!$cc || !$phone_num || !in_array($cc, $this->_cc_to_verify) || strlen($phone_num) > ($this->_max_phone_digits + 3)) {
			$this->_error_log = t('wrong phone number format 1');
			return false;
		} 
		if (empty($license_key)) {
			$license_key	= $this->_license_key;
		}
		if (empty($verify_code) || (!empty($verify_code) && !preg_match("/^([0-9]{4})\$/ims", $verify_code))) {
			//random verify code generation
			$verify_code = rand(1000, 9999);
		}
		$phone_num = preg_replace("/[^0-9\+]/ims", "", $phone_num);
		if (!preg_match("/^[\+]{0,1}[0-9]{".intval($this->_min_phone_digits).",}\$/ims", $phone_num)) {
			$this->_error_log = t('wrong phone number format 2');
			return false;
		}
		if (strlen($phone_num) > $this->_max_phone_digits && !preg_match("/^[\+][0-9]+\$/ims", $phone_num)) {
			$phone_num = substr($phone_num, strlen($phone_num)-$this->_max_phone_digits, $this->_max_phone_digits);
		}
		if (!preg_match("/^[\+][0-9]+\$/ims", $phone_num)){
			$_call_code = $this->_call_code[$cc];
			$phone_num = "+".$_call_code.$phone_num;
		}
		$this->_action_log .= "Checking phone ".$phone_num."<br />";
		// search phone number in log_ dbtable		
		$DO_IDENTIFICATION 	= true;
		$A = db()->query_fetch(
			"SELECT * FROM ".db('log_maxmind_phone_verify')." 
			WHERE phone_num='".$phone_num."' AND check_type='i' AND ref_id != ''"
		);
		if ($A) {
			$this->_action_log .= "This phone already checked for identify (log id=".$A["id"].", result=".intval($A["success"]).", date="._format_date($A["date"], "long").", server answer: "._prepare_html($A["server_answer"]).")<br />";
			$this->_action_log .= "<b>indentify: Skip calling maxmind, use cached result from log</b><br />";
			$result = (bool)$A["success"];
			$DO_IDENTIFICATION 	= false;
		}

		// Replacing "+" character with hex equivalent
		$enc_phone_num = str_replace("+", "%2B", $phone_num); 

		if ($this->IDENTIFY_PHONE && $DO_IDENTIFICATION) {
			$this->_action_log .= "phone identify started...<br />";
			// maxmind returns string like 
			// phoneType=4;refid=9C908BF2D44A1A0FF4E2E0B9421B2B56;err=;city=;state=;zip=;countryname=;latitude=;longitude=		
			
			$_time_start = microtime(true);
			$url = "https://www.maxmind.com/app/phone_id_http";
			$query_string = "phone=".$enc_phone_num."&l=".$license_key;
			$curl_answer = $this->_curl_request($query_string, $url);
			$data = array();
			if ($curl_answer["content"]){
				$this->_action_log .= "sent request to the url: ".$url."?".$query_string."<br />";
				$this->_action_log .= "recieved server answer: "._prepare_html($curl_answer["content"])."<br />";
				$tmp = explode(";", $curl_answer["content"]);
				foreach ((array)$tmp as $A){
					$tmp2 = explode ("=", $A);
					$data[$tmp2[0]] = $tmp2[1];
				}
			}
			if ($curl_answer["error_text"] || empty($data) || !empty($data["err"])) {
				$this->_action_log .= "<b style='color:red;'>error occured: "._prepare_html($curl_answer["error_text"]." ".$data["err"])."</b><br />";
				$this->_error_log = "indentify error: "._prepare_html($curl_answer["error_text"]." ".$data["err"]);
			}
			// check for further verification needed
			$this->_action_log .= "phone type: \"".$this->_maxmind_phone_id_code[$data["phoneType"]]["type"]."\"<br />";
			if ($this->_maxmind_phone_id_code[$data["phoneType"]]["allowed"]) {
			 	$DO_VERIFICATION = true;
				$this->_action_log .= "phone verification needed<br />";
			} else {
				$DO_VERIFICATION = false;
				$this->_action_log .= "phone verification NOT needed<br />";
			}

			$phone_type	= $data["phoneType"]; 			
			// Save logs
			$this->_action_log .= "saving indentify log<br />";
			$error_text = "";
			$error_text = $curl_answer["error_text"]. ($data["err"] ? " maxmind_error: ".$data["err"] : "");
			db()->INSERT("log_maxmind_phone_verify", array(
				"phone_num"		=> _es($phone_num),
				"ref_id"		=> $data["refid"],
				"server_answer" => $curl_answer["content"],
				"phone_type"	=> $data["phoneType"], 
				"check_type"	=> "i",
				"owner_id"		=> $owner_id,
	
				"success"		=> (!strlen($error_text)) ? 1 : 0,
				"date"			=> time(),
				"process_time"	=> floatval(common()->_format_time_value(microtime(true) - (float)$_time_start)),
				"error_text"	=> _es($error_text),
	
				"site_id"		=> (int)conf('SITE_ID'),
				"user_id"		=> intval($_SESSION[MAIN_TYPE_ADMIN ? "admin_id" : "user_id"]),
				"user_group"	=> intval($_SESSION[MAIN_TYPE_ADMIN ? "admin_group" : "user_group"]),
				"is_admin"		=> MAIN_TYPE_ADMIN ? 1 : 0,
				"ip"			=> _es(common()->get_ip()),
				"query_string"	=> _es(WEB_PATH."?".$_SERVER["QUERY_STRING"]),
				"user_agent"	=> _es($_SERVER["HTTP_USER_AGENT"]),
				"referer"		=> _es($_SERVER["HTTP_REFERER"]),
				"request_uri"	=> _es($_SERVER["REQUEST_URI"]),
				"object"		=> _es($_GET["object"]),
				"action"		=> _es($_GET["action"]),
			));
			$this->_action_log .= "phone identify finished<br />";
			// Set result
			$result = (!strlen($error_text)) ? 1 : 0;
		}

	 	$DO_VERIFICATION = true;
		// We need to check target phone type (in $A array)
		if (!$A) {
			$this->_action_log	.= "verify: no indentify record, stop because it is required<br />";
			$this->_error_log	.= "verify: no indentify record, stop because it is required<br />";
		 	$DO_VERIFICATION = false;
		} else {
			// Phone type do not allowed for verifucation
			if (!$this->_maxmind_phone_id_code[$A["phone_type"]]["allowed"]) {
				$this->_action_log	.= "Phone type (".$A["phone_type"].") not allowed for verification<br />";
				$this->_error_log	.= "verify error: Phone type (".$A["phone_type"].") not allowed for verification<br />";
			 	$DO_VERIFICATION = false;
			} else {
				$B = db()->query_fetch(
					"SELECT * FROM ".db('log_maxmind_phone_verify')." 
					WHERE phone_num='".$phone_num."' AND check_type='v' AND ref_id != ''"
				);
			}
			if ($B) {
				$this->_action_log .= "This phone already checked for verify (log id=".$B["id"].", result=".intval($B["success"]).", date="._format_date($B["date"], "long").", server answer: "._prepare_html($B["server_answer"]).")<br />";
				$this->_action_log .= "<b>verify: Skip calling maxmind, use cached result from log</b><br />";
				$result = (bool)$B["success"];
			 	$DO_VERIFICATION = false;
			}
		}

		if ($this->VERIFY_PHONE && $DO_VERIFICATION) {
			$this->_action_log .= "phone verification started<br />";
			// Do verify phone (ordering a call)
			$_time_start = microtime(true);
			$query_string = "l=".$license_key."&phone=".$enc_phone_num."&verify_code=".$verify_code;
			$url = "https://www.maxmind.com/app/telephone_http";
			$curl_answer = $this->_curl_request($query_string, $url);
			$data = array();
			if ($curl_answer["error_text"]){
				$this->_action_log .= "verify: error occured<br />";	
				$this->_error_log = "verify error: "._prepare_html($curl_answer["error_text"]." ".$data["err"]);
			}
			if ($curl_answer["content"]){
				$this->_action_log .= "recieved server verify answer<br />";
			}
			$this->_action_log .= "saving verify log<br />";
			// Save logs
			$error_text = "";
			$error_text = $curl_answer["error_text"]. ($data["err"] ? " maxmind_error: ".$data["err"] : "");
			db()->INSERT("log_maxmind_phone_verify", array(
				"phone_num"		=> _es($phone_num),
				"verify_code"	=> intval($verify_code),
				"ref_id"		=> substr($curl_answer["content"], 6),
				"server_answer" => $curl_answer["content"],
				"check_type"	=> "v",
				"phone_type"	=> $A["phone_type"], 
				"owner_id"		=> $A["owner_id"],
	
				"success"		=> (!strlen($error_text)) ? 1 : 0,
				"date"			=> time(),
				"process_time"	=> floatval(common()->_format_time_value(microtime(true) - (float)$_time_start)),
				"error_text"	=> _es($error_text),
	
				"site_id"		=> (int)conf('SITE_ID'),
				"user_id"		=> intval($_SESSION[MAIN_TYPE_ADMIN ? "admin_id" : "user_id"]),
				"user_group"	=> intval($_SESSION[MAIN_TYPE_ADMIN ? "admin_group" : "user_group"]),
				"is_admin"		=> MAIN_TYPE_ADMIN ? 1 : 0,
				"ip"			=> _es(common()->get_ip()),
				"query_string"	=> _es(WEB_PATH."?".$_SERVER["QUERY_STRING"]),
				"user_agent"	=> _es($_SERVER["HTTP_USER_AGENT"]),
				"referer"		=> _es($_SERVER["HTTP_REFERER"]),
				"request_uri"	=> _es($_SERVER["REQUEST_URI"]),
				"object"		=> _es($_GET["object"]),
				"action"		=> _es($_GET["action"]),
			));
			$this->_action_log .= "phone verification finished<br />";
			// Set result
			$result = (!strlen($error_text)) ? 1 : 0;
		}
		return (bool)$result;
	}

	//-----------------------------------------------------------------------------
	// Requests MaxMind server using curl
	function _curl_request($query_string = "", $url = "") {
		if (!$this->REMOTE_ENABLED) {
			return array("error_text" => "Remote calls disabled (maxmind_phone_verify->REMOTE_ENABLED == false)");
		}
		// open curl
		$ch = curl_init();

		// set curl options
		$user_agent = "Mozilla/4.0 (compatible; MSIE 6.01; Windows NT 5.1)";
		$referer	= $url;

		curl_setopt($ch, CURLOPT_URL,				$url);
		curl_setopt($ch, CURLOPT_USERAGENT,			$user_agent);
		curl_setopt($ch, CURLOPT_REFERER,			$referer);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,	1);
		curl_setopt($ch, CURLOPT_TIMEOUT,			$this->_timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,	0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,	1);
		curl_setopt($ch, CURLOPT_CAINFO, 			YF_PATH."/share/ca-bundle.crt");

		// this option allows to store the result in a string 
		curl_setopt($ch, CURLOPT_POST,		  1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,	$query_string);

		$data = array();		
		// get the content
		$data["content"] = curl_exec($ch);
		// get error text for the logs (if error occured)
		$data["error_text"] = curl_error($ch);
		return $data;		
	}
}
