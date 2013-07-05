<?php

//-----------------------------------------------------------------------------
// This class handle registration process
class yf_register {

	/** @var bool Allow additional JavaScript based form validation*/
	public $_JS_VALIDATION			= true;
	/** @var bool Do confirm registrations by email or not */
	public $CONFIRM_REGISTER		= true;
	/** @var int Confirm expiration time in seconds */
	public $CONFIRM_TTL			= 864000; // 10 * 24 * 60 * 60 = 10 days
	/** @var bool Check if user have been registered such email */
	public $CHECK_EMAIL_IN_DELETED = true;
	/** @var bool Check that user agreed with site terms and conditions */
	public $CHECK_TERMS_AGREE		= true;
	/** @var int Number of the first step */
	public $_FIRST_STEP_NUM		= 2;
	/** @var bool Use AJAX-based pre-checking of unique fields availiability */
	public $USE_JS_CHECK_AVAIL		= true;
	/** @var bool Do not allow to register logged in users */
	public $DENY_FOR_LOGGED_IN		= true;
	/** @var array Nick allowed synmbols */
	public $NICK_ALLOWED_SYMBOLS	= array("a-z","0-9","_","\-","@","#"," ");
	/** @var bool Use captcha or not */
	public $USE_CAPTCHA			= true;

	//-----------------------------------------------------------------------------
	// YF module constructor
	function _init () {
		// Get user account type
		$this->_account_types	= main()->get_data("user_groups");
		if (!isset($_POST["account_type"])) {
			$_POST["account_type"] = $this->_account_types[2];
		}
		// Array of select boxes to process
		$this->_boxes = array(
			"status"		=> 'select_box("status",		$this->_statuses,		$selected, false, 2, "", false)',
			"sex"			=> 'select_box("sex",			$this->_sex,			$selected, false, 2, "", false)',
//			"age"			=> 'select_box("age",			$this->_ages,			$selected, " ", 2, "", false)',
			"birth_date"	=> 'date_box($selected, "1915-".(date("Y") - 8), "_birth")',
		);
		// Array of form fields to process
		$this->_text_fields = array(
			"nick",
			"login",
			"email",
			"city",
			"zip_code",
			"phone",
			"fax",
			"icq",
			"yahoo",
			"aim",
			"msn",
			"jabber",
			"skype",
			"birth_date",
		);
		// Array of dynamic info
		if (main()->USER_INFO_DYNAMIC) {
			$sql = "SELECT * FROM `".db('user_data_info_fields')."` WHERE `active`=1 ORDER BY `order`, `name`";
			$Q = db()->query($sql);
			while ($A = db()->fetch_assoc($Q)) {
				$this->_dynamic_fields_info[$A["id"]] = $A;
				$this->_dynamic_fields_names[$A["id"]] = $A["name"];
			}
		}
		$this->_required_fields	= array(
			"nick",
			"login",
			"email"
		);
		// Fill array of sexes
		$this->_sex = array(
			"Male"			=> t("Male"),
			"Female"		=> t("Female"), 
		);
		// Fill array of ages
		for ($i = 10/*18*/; $i <= 75; $i++) {
			$this->_ages[$i] = $i;
		}
		// Try to init captcha
		if ($this->USE_CAPTCHA) {
			$this->CAPTCHA = main()->init_class("captcha", "classes/");
//			$this->CAPTCHA->set_image_size(120, 50);
//			$this->CAPTCHA->font_height = 16;
		}
	}

	//-----------------------------------------------------------------------------
	// Default function
	function show () {
		if ($this->USER_ID && $this->DENY_FOR_LOGGED_IN) {
			return _e("You are already registered on our site!");
		}
		// Default step value
		if (!strlen($_POST['step'])) {
			$_POST['step'] = $this->_FIRST_STEP_NUM;
		} else {
			$_POST['step'] = intval($_POST['step']);
		}
		// Show required function output
		$method_name = "_step_".$_POST['step'];
		if (method_exists($this, $method_name)) {
			return $this->$method_name();
		}
	}

	//-----------------------------------------------------------------------------
	// Show captcha image
	function show_image() {
		is_object($this->CAPTCHA) ? $this->CAPTCHA->show_image() : "";
	}

	//-----------------------------------------------------------------------------
	// First step (selection of account type)
	function _step_1 () {
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"],
			"back"			=> tpl()->parse("system/back", $replace),
			"home"			=> tpl()->parse("system/home", array("home_link" => WEB_PATH)),
			"error_message"	=> _e(),
		);
		return tpl()->parse($_GET["object"]."/step_1", $replace);
	}

	//-----------------------------------------------------------------------------
	// Second step
	function _step_2 ($FORCE_DISPLAY_FORM = false) {
/*
		if (empty($_POST["account_type"]) || !in_array($_POST["account_type"], $this->_account_types)) {
			_re(t('wrong_account_type'));
		}
*/
		// If no errors - continue
		if (!common()->_error_exists() || $FORCE_DISPLAY_FORM) {
			// Create JS array for required fields
			if ($this->_JS_VALIDATION && is_array($this->_required_fields)) {
				foreach ((array)$this->_required_fields as $v) {
					$v2.= "'".$v."', ";
					$v3.= "'".t($v)."', ";
				}
				$replace2["js_array_1"]	= substr($v2, 0, -2);
				$replace2["js_array_2"]	= substr($v3, 0, -2);
			}
			$replace2["account_type"] = $_POST["account_type"];
			// Create array to replace inside template
			$replace = array(
				"form_action"	=> "./?object=".$_GET["object"],
				"js_validator"	=> $this->_JS_VALIDATION ? tpl()->parse($_GET["object"]."/js_step2", $replace2) : "",
				"js_form_code"	=> $this->_JS_VALIDATION ? " onsubmit=\"return form_check(this);\" " : "",
				"account_type"	=> $_POST["account_type"],
				"back"			=> tpl()->parse("system/back", $replace),
				"home"			=> tpl()->parse("system/home", array("home_link" => WEB_PATH)),
				"captcha_block"	=> is_object($this->CAPTCHA) ? $this->CAPTCHA->show_block("./?object=".$_GET["object"]."&action=show_image") : "",
				"error_message"	=> _e(),
				"js_check_avail"=> intval((bool)$this->USE_JS_CHECK_AVAIL),
			);
			// Process boxes
			foreach ((array)$this->_boxes as $item_name => $v) {
				$replace[$item_name."_box"] = $this->_box($item_name, $_POST[$item_name]);
			}
			
			// Fill all other form fields with values
			foreach ((array)$this->_text_fields as $name) {
				$replace[$name] = $_POST[$name];
			}
			$replace["recip_url"] = isset($_POST["recip_url"]) ? $_POST["recip_url"] : "http://";

			$replace["dynamic_fields"] = "";
			// Process Dynamic fields

			// Dynamic info
			if (main()->USER_INFO_DYNAMIC) {
				$OBJ_DYNAMIC_INFO = &main()->init_class("dynamic_info", "classes/");
				$replace["dynamic_items"] = $OBJ_DYNAMIC_INFO->_edit($this->USER_ID, $_SESSION["register"]["dynamic_info"]);
			}
		
			// Parse template contents
			$body = tpl()->parse($_GET["object"]."/step_2_".$_POST["account_type"], $replace);
		} else {
			$body .= $this->_step_1();
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Confirmation step
	function _step_3 ($FORCE_DISPLAY_FORM = false) {
		$_SESSION["register"]["dynamic_info"] = $_POST;
		// Validate captcha
		if (is_object($this->CAPTCHA)) {
			$this->CAPTCHA->check("captcha");
		}
		// Validate form fields
		$this->_validate_form();
		// If no errors - continue
		if (!common()->_error_exists() || $FORCE_DISPLAY_FORM) {
			// Fill all other form fields with values
			$fields = array_merge(array_keys($this->_boxes)
				, $this->_text_fields
				, array("password", "password2")
				, (array)$this->_dynamic_fields_names
			);
			
			foreach ((array)$fields as $name) {
				$replace[$name] = strlen($_POST[$name]) ? $_POST[$name] : t("Not entered");
				$replace["hidden_fields"] .= tpl()->parse($_GET["object"]."/hidden_field", array("name" => $name, "value" => $_POST[$name]));
				if (in_array($name, (array)$this->_dynamic_fields_names)) {
					$dynamic_fields .= tpl()->parse("system/dynamic_fields_view", array("label" => $name, "value" => strlen($_POST[$name]) ? $_POST[$name] : t("Not entered")));
				}
			}
			
			$js_validator = "";
			if ($this->_JS_VALIDATION) {
				$js_validator = tpl()->parse($_GET["object"]."/js_step3", array("confirm_register" => $this->CONFIRM_REGISTER ? 1 : 0));
			}
			$replace = array_merge($replace, array(
				"dynamic_fields"	=> $dynamic_fields,
				"form_action"		=> "./?object=".$_GET["object"],
				"account_type"		=> $_POST["account_type"],
				"state"				=> strlen($_POST["state"]) ? $this->_states[$_POST["state"]] : t("Not entered"),
				"country"			=> strlen($_POST["country"]) ? $this->_countries[$_POST["country"]] : t("Not entered"),
				"height"			=> $_POST["height"] ? $this->_heights[$_POST["height"]] : t("Not entered"),
				"weight"			=> $_POST["weight"] ? $this->_weights[$_POST["weight"]] : t("Not entered"),
				"js_validator"		=> $js_validator,
				"js_form_code"		=> $this->_JS_VALIDATION ? " onsubmit=\"return form_check(this);\" " : "",
				"error_message"		=> _e(),
				"confirm_register"	=> $this->CONFIRM_REGISTER ? 1 : 0,
			));
			// Parse template contents
			$body = tpl()->parse($_GET["object"]."/step_3_".$_POST["account_type"], $replace);
		} else {
			$body .= $this->_step_2(true);
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Last step (inserting data into database, sending email with verification code)
	function _step_4 () {
// TODO
		$birth_date = $_POST["birth_date"];
	
		// Validate form fields
		$this->_validate_form();
		// Validate that user agreed with site terms and conditions
		if ($this->CHECK_TERMS_AGREE && empty($_POST["terms_agree"])) {
			_re(t("You must agree with site Terms of Service"));
		}
		// If no errors - continue
		if (!common()->_error_exists()) {
			
			// Prepare values
			foreach ((array)$_POST as $k => $v) {
				$_POST[$k] = _es($v);
			}
			// Get user group ID
			$tmp_type = array_flip($this->_account_types);
			$group_id = $tmp_type[$_POST["account_type"]];
			$member_date = time();
			// Generate SQL query text
			$sql_array = array(
				"group"				=> intval($group_id),
				"nick"				=> $_POST["nick"],
				"name"				=> $_POST["account_type"] == "agency" ? $_POST["nick"] : "",
				"login"				=> $_POST['login'],
			  	"email"				=> $_POST['email'],
				"password"			=> $_POST['password'],
				"address"			=> $_POST["address"],
				"phone"				=> $_POST['phone'],
				"city"				=> $_POST["city"],
				"zip_code"			=> $_POST["zip_code"],
				"state"				=> $_POST["state"],
				"country"			=> $_POST["country"],
				"sex"				=> $_POST["sex"],
				"birth_date"		=> $birth_date,
				"ip"				=> _es(common()->get_ip()),
				"add_date"			=> intval($member_date),
				"active"			=> $this->CONFIRM_REGISTER ? 0 : 1,
			);
			db()->INSERT("user", $sql_array);
			// Get new user ID
			$NEW_USER_ID = db()->insert_id();
			
			// Update dynamic fields
			if (main()->USER_INFO_DYNAMIC) {
				$OBJ_DYNAMIC_INFO = &main()->init_class("dynamic_info", "classes/");
				$replace["dynamic_items"] = $OBJ_DYNAMIC_INFO->_save($NEW_USER_ID, $_SESSION["register"]["dynamic_info"]);
			}
			
			$replace["nick"] = $_POST["nick"];
			// Send registration confirmation email
			if ($this->CONFIRM_REGISTER) {
				// Generate verification code
				$verify_code = base64_encode($NEW_USER_ID . "wvcn" . $member_date);
				// Update record with new code
				update_user($NEW_USER_ID, array("verify_code"=>$verify_code));
				// Try to send confirmation email
				$email_sent = $this->_send_email_with_code($verify_code);
				// Check email sending result
				if ($email_sent) {
					$replace["confirm_register"] = 1;
					$body .= tpl()->parse($_GET["object"]."/success_".$_POST["account_type"], $replace);
				} else {
					$replace2 = array(
						"contact_link" => process_url("./?object=help&action=email_form"),
					);
					$body .= tpl()->parse($_GET["object"]."/error_sending_email", $replace2);
				}
			} else {
				$replace["confirm_register"] = 0;
				$body .= tpl()->parse($_GET["object"]."/success_".$_POST["account_type"], $replace);
			}
		} else {
			$body .= $this->_step_2(true);
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Send email with verification code
	function _send_email_with_code ($code = "") {
		$replace = array(
			"nick"			=> $_POST["nick"],
			"confirm_code"	=> $code,
			"conf_link"		=> process_url("./?object=".$_GET["object"]."&action=confirm&id=".$code),
			"aol_link"		=> process_url("./?object=".$_GET["object"]."&action=confirm&id=".$code),
			"conf_link_aol"	=> process_url("./?object=".$_GET["object"]."&action=confirm_aol&id=".$code),
			"conf_form_url"	=> process_url("./?object=login_form&action=account_inactive"),
			"admin_name"	=> SITE_ADVERT_NAME,
			"advert_url"	=> SITE_ADVERT_URL,
		);
		$text = tpl()->parse($_GET["object"]."/email_confirm_".$_POST["account_type"], $replace);
		// prepare email data
		$email_from	= SITE_ADMIN_EMAIL;
		$name_from	= SITE_ADVERT_NAME;
		$email_to	= $_POST["email"];
		$name_to	= $_POST["nick"];
		$subject	= t("Membership confirmation required!");
		return common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
	}

	//-----------------------------------------------------------------------------
	// Send success email
	function _send_success_email () {
		$replace = array(
			"code"			=> $code,
			"nick"			=> $_POST["nick"],
			"password"		=> $_POST["password"],
			"advert_name"	=> SITE_ADVERT_NAME,
			"advert_url"	=> SITE_ADVERT_URL,
		);
		$text = tpl()->parse($_GET["object"]."/email_success_".$_POST["account_type"], $replace);
		// prepare email data
		$email_from	= SITE_ADMIN_EMAIL;
		$name_from	= SITE_ADVERT_NAME;
		$email_to	= $_POST["email"];
		$name_to	= $_POST["nick"];
		$subject	= t("Thank you for registering with us!");
		return common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
	}

	//-----------------------------------------------------------------------------
	// Validate form for steps 3 and 4
	function _validate_form () {
		// Cleanup all $_POST fields
		foreach ((array)$_POST as $k => $v) {
			trim($_POST[$k]);
		}
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_POST["country"]) && substr($_POST["country"], 0, 2) == "f_") {
			$_POST["country"] = substr($_POST["country"], 2);
		}
/*
		// Validate form information
		if (!isset($_POST["account_type"]) || !in_array(strtolower($_POST["account_type"]), $this->_account_types)) {
			_re(t('wrong_account_type'));
		}
*/
		// Init default validator
		$VALIDATE_OBJ = main()->init_class("validate", "classes/");
		// Check user nick (required)
		$VALIDATE_OBJ->_check_user_nick();
		// Check if login is already registered by someone
		if ($_POST["login"] == "") {
			_re(t('Login required'));
		} elseif (!preg_match('/^[a-z0-9]+$/i', $_POST["login"])) {
			_re(t("Login is wrong. Only english letters and digits are allowed, no symbols"));
		} elseif (db()->query_num_rows("SELECT `id` FROM `".db('user')."` WHERE `login`='"._es($_POST['login'])."'") >= 1) {
			_re(t("This login")." (".$_POST['login'].")".t("has already been registered with us!"));
		}
		// Check passowrds
		if ($_POST["password"] == "") {
			_re(t('Password is required!'));
		}
		if ($_POST["password2"] == "") {
			_re(t('Please re-enter your password again!'));
		} elseif ($_POST["password"] != $_POST["password2"]) {
			_re(t("Two passwords don't match!"));
		}
		// Check if email is already registered for someone
		if (!preg_match('#^[0-9a-z]([-_.]?[0-9a-z])*@[0-9a-z]([-.]?[0-9a-z])*\.[a-z]{2,3}$#ims', $_POST["email"])) {
			_re(t('Invalid e-mail, please check your spelling!'));
		} elseif (db()->query_num_rows("SELECT `id` FROM `".db('user')."` WHERE `email`='"._es($_POST['email'])."'") >= 1) {
			_re(t("This e-mail")." (".$_POST['email'].") ".t("has already been registered with us")."!<br>".t("Use")." <a href='./?object=get_pswd'>".t("password reminder")."</a> ".t("in case you forgot your password")."!");
/*
		} elseif ($this->CHECK_EMAIL_IN_DELETED) {
			// Check if such email has been already registered in some account
			// and then account was deleted
			if (db()->query_num_rows("SELECT `id` FROM `".db('user_deleted')."` WHERE `email`='"._es($_POST['email'])."'") >= 1) {
				_re("This e-mail (".$_POST['email'].") was registered with us and then account was deleted!<br /><a href='./?object=help&action=email_form'>Contact</a> site admin if you have any questions");
			}
*/
		}
		// Check other fields
		if ($_POST["measurements"] != "" && !preg_match('#[0-9]{2}([A-G]{0,4})-([0-9]{2})-([0-9]{2})#', $_POST["measurements"])) {
			_re(t('Invalid measurements (example, 36DD-27-32)!'));
		}
		if ($_POST["recip_url"] == "http://" || $_POST["recip_url"] == "") {
			$_POST["recip_url"] = "";
		} elseif (!preg_match('#^http://[_a-z0-9-]+\.[_a-z0-9-]+#i', $_POST["recip_url"])) {
			_re(t('Invalid reciprocal URL'));
		}
		foreach ((array)$this->_required_fields as $_field => $_name) {
			if (!strlen($_POST[is_numeric($_field) ? $_name : $_field])) {
				_re(t($_name)." ".t('required'));
			}
		}
		
		// Validate birth date
		$VALIDATE_OBJ->_check_birth_date($this->_user_info["birth_date"]);
	}

	//-----------------------------------------------------------------------------
	// Confirm registration for common users
	function confirm () {
		// Send registration confirmation email
		if (!$this->CONFIRM_REGISTER) {
			return tpl()->parse($_GET["object"]."/confirm_messages", array("msg" => "confirm_not_needed"));
		}
		// Check confirmation code
		if (!strlen($_GET["id"])) {
			return _e(t("Confirmation ID is required!"));
		}
		// Decode confirmation number
		list($user_id, $member_date) = explode("wvcn", trim(base64_decode($_GET["id"])));
		$user_id		= intval($user_id);
		$member_date	= intval($member_date);
		// Get target user info
		if (!empty($user_id)) {
			$target_user_info = user($user_id);
		}
		// User id is required
		if (empty($target_user_info["id"])) {
			return _e("Wrong user ID");
		}
		// Check if user already confirmed
		if ($target_user_info["active"]) {
			return tpl()->parse($_GET["object"]."/confirm_messages", array("msg" => "already_confirmed"));
		}
		// Check if code is expired
		if (!common()->_error_exists()) {
			if (!empty($member_date) && (time() - $member_date) > $this->CONFIRM_TTL) {
				_re(t("Confirmation code has expired."));
			}
		}
		if (!common()->_error_exists()) {
			// Check whole code
			if ($_GET["id"] != $target_user_info["verify_code"]) {
				_re(t("Wrong confirmation code"));
			}
		}
		if (!common()->_error_exists()) {
			// Do update user's table (confirm account)
			update_user($user_id, array("active"=>1));
			// Display success message
			return tpl()->parse($_GET["object"]."/confirm_messages", array("msg" => "confirm_success"));
		}
		// Display form
		$body .= _e();
		$body .= tpl()->parse($_GET["object"]."/enter_code", $replace3);
		$body .= tpl()->parse($_GET["object"]."/resend_code", $replace4);
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Manually enter code
	function enter_code () {
		// Do activate
		if (isset($_POST["confirm_code"])) {
			$_GET["id"] = $_POST["confirm_code"];
			return $this->confirm();
		}
		// Display form
		$replace = array(
		);
		return tpl()->parse($_GET["object"]."/enter_code", $replace);
	}

	//-----------------------------------------------------------------------------
	// Re-send confirmation code
	function resend_code () {
		// Process posted form
		if (!empty($_POST["email"])) {
			// Check if such user exists
			$user_info = db()->query_fetch("SELECT * FROM `".db('user')."` WHERE `email`='"._es($_POST["email"])."'");
			if (empty($user_info)) {
				return _e("No such user");
			}
			// Check if account already activated
			if ($user_info["active"]) {
				return "Your account is already activated.";
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Generate verification code
				$code = base64_encode($user_info["id"] . "wvcn" . time());
				// Update record with new code
				update_user($user_info["id"], array("verify_code" => $code));
				// Prepare email template
				$replace = array(
					"nick"			=> $user_info["nick"],
					"confirm_code"	=> $code,
					"conf_link"		=> process_url("./?object=".$_GET["object"]."&action=confirm&id=".$code),
					"aol_link"		=> process_url("./?object=".$_GET["object"]."&action=confirm&id=".$code),
					"conf_link_aol"	=> process_url("./?object=".$_GET["object"]."&action=confirm_aol&id=".$code),
					"conf_form_url"	=> process_url("./?object=login_form&action=account_inactive"),
					"admin_name"	=> SITE_ADVERT_NAME,
					"advert_url"	=> SITE_ADVERT_URL,
				);
				$text = tpl()->parse($_GET["object"]."/email_confirm_".$this->_account_types[$user_info["group"]], $replace);
				// Prepare email
				$email_from	= SITE_ADMIN_EMAIL;
				$name_from	= SITE_ADVERT_NAME;
				$email_to	= $user_info["email"];
				$name_to	= $user_info["nick"];
				$subject	= t("Membership confirmation required!");
				$send_result = common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
				// Check result
				if ($send_result) {
					return "Code sent. Please check your email.";
				} else {
					return "Error sending mail. Please contact site admin.";
				}
			}
		}
		// Display form
		$replace = array(
		);
		return tpl()->parse($_GET["object"]."/resend_code", $replace);
	}

	//-----------------------------------------------------------------------------
	// Confirm registration for users from AOL
	function confirm_aol () {
// TODO: Need to check if need this
		if (strlen($_GET["code"])) {
			list($user_id, $member_date) = explode("wvcn", trim($_GET["code"]));
			// Check required params
			if (is_numeric($user_id) && is_numeric($member_date) && (($member_date - time()) > $this->CONFIRM_TTL)) {
				update_user($user_id,  array("active"=>1));
				echo "Confirmed!";
			}
		}
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	//-----------------------------------------------------------------------------
	// 
	function check_avail_field () {
		main()->NO_GRAPHICS = true;
		$FIELD_TO_CHECK = $_POST["id"];
		$TEXT_TO_CHECK	= $_POST["value"];
		if (empty($FIELD_TO_CHECK) || empty($TEXT_TO_CHECK)) {
			return false;
		}
		// Switch between fields to check
		if ($FIELD_TO_CHECK == "nick") {
			$_nick_pattern = implode("", $this->NICK_ALLOWED_SYMBOLS);
			$MIN_NICK_LENGTH = 2;
			if (empty($TEXT_TO_CHECK) || (strlen($TEXT_TO_CHECK) < $MIN_NICK_LENGTH)) {
				$msg = t("Nick must have at least")." ".$MIN_NICK_LENGTH." ".t("symbols");
			} elseif (!preg_match("/^[".$_nick_pattern."]+\$/iu", $TEXT_TO_CHECK)) {
				$msg = t("Nick can contain only these characters").": \"".stripslashes(implode("\" , \"", $this->NICK_ALLOWED_SYMBOLS))."\"";
			} elseif (db()->query_num_rows("SELECT `id` FROM `".db('user')."` WHERE `nick`='"._es($TEXT_TO_CHECK)."'") >= 1) {
				$msg = t("Nick")." (\"".$TEXT_TO_CHECK."\") ".t("is already reserved. Please try another one.");
			}
		} elseif ($FIELD_TO_CHECK == "login") {
			if ($TEXT_TO_CHECK == "") {
				$msg = t('Login required');
			} elseif (db()->query_num_rows("SELECT `id` FROM `".db('user')."` WHERE `login`='"._es($TEXT_TO_CHECK)."'") >= 1) {
				$msg = t("This login")." (".$TEXT_TO_CHECK.") ".t("has already been registered with us")."!";
			}
		} elseif ($FIELD_TO_CHECK == "email") {
			// Check if email is already registered for someone
			if (!common()->email_verify($TEXT_TO_CHECK)) {
				$msg = t('Invalid e-mail, please check your spelling!');
			} elseif (db()->query_num_rows("SELECT `id` FROM `".db('user')."` WHERE `email`='"._es($TEXT_TO_CHECK)."'") >= 1) {
				// Check if account with such email was deleted and try to restore it
				list($deleted_id) = db()->query_fetch("SELECT `id` AS `0` FROM `".db('user')."` WHERE `email`='"._es($TEXT_TO_CHECK)."' AND `is_deleted`='1'");
				if (!empty($deleted_id) && $this->ALLOW_RESTORE_ACCOUNT) {
					$this->_TRYING_RESTORE_ID = $deleted_id;
				} else {
					$msg = t("This e-mail")." (".$TEXT_TO_CHECK.") ".t("has already been registered with us")."!<br>".t("Use")." <a href='".process_url("./?object=get_pswd")."'>".t("password reminder")."</a> ".t("in case you forgot your password")."!";
				}
/*
			// if restoring is "off" - just do simple check
			} elseif ($this->CHECK_EMAIL_IN_DELETED && !$this->ALLOW_RESTORE_ACCOUNT) {
				// Check if such email has been already registered in some account and then account was deleted
				if (db()->query_num_rows("SELECT `id` FROM `".db('user_deleted')."` WHERE `email`='"._es($TEXT_TO_CHECK)."'") >= 1) {
					$msg = "This e-mail (".$TEXT_TO_CHECK.") was registered with us and then account was deleted!<br /><a href='".process_url("./?object=help&action=email_form")."'>Contact</a> site admin if you have any questions";
				}
*/
			}
		}
		// AVAIL response
		if (empty($msg)) {
			echo 1;
		// means RESERVED, also provide reason msg
		} else {
			echo $msg;
		}
	}
}
