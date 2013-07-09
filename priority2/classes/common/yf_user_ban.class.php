<?php

/**
* User ban chacks
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_user_ban {

	/** @var bool Use preg_match or simple strpos */
	public $USE_REGEXES	= 1;
	/** @var array */
	public $BAN_CHECKS		= array();

	/**
	* Constructor (PHP 4.x)
	* 
	* @access	private
	* @return	void
	*/
	function yf_user_ban () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	* 
	* @access	private
	* @return	void
	*/
	function __construct () {
		// First we need to get all "user_ban" checks from db
		$Q = db()->query("SELECT * FROM ".db('user_ban')."");
		while ($A = db()->fetch_assoc($Q)) $this->BAN_CHECKS[$A["id"]] = $A;
// TODO: need to move into sys cache
	}

	/**
	* Do check if user is banned for some reason
	* 
	* @access	public
	* @param	array	Array of fields to check
	* @param	array	User info (optional)
	* @return
	*/
	function _check ($input = array(), $user_info = array()) {
		// Default banned status
		$BANNED_STATUS = false;
		// Nothing to check
		if (empty($input) || empty($input["user_id"])) {
			return $BANNED_STATUS;
		}
		// Default user ban fields
		$ban_fields = array(
			"ban_ads"			=> 0,
			"ban_reviews"		=> 0,
			"ban_images"		=> 0,
			"ban_email"			=> 0,
			"ban_forum"			=> 0,
			"ban_comments"		=> 0,
			"ban_blog"			=> 0,
			"ban_bad_contact"	=> 0,
			"ban_reput"			=> 0,
		);
		// Start process ban checks
		foreach ((array)$this->BAN_CHECKS as $CUR_CHECKS) {
			// Process input fields
			foreach ((array)$input as $input_name => $input_value) {
				// Switch between active checks
				$WHAT_FOUND = $this->_try_to_find($input_name, $CUR_CHECKS, $input_value);
				if (!$WHAT_FOUND) {
					continue;
				}
				// Process ban status if found one
				$BANNED_STATUS = true;
				// Try to find ban reasons
				foreach ((array)$ban_fields as $ban_name => $ban_value) {
					// User already banned for this type
					if ($ban_value == 1 || empty($CUR_CHECKS[$ban_name])) {
						continue;
					}
					// Else - do ban current type
					$ban_fields[$ban_name]		= 1;
					$ban_reasons[$input_name]	= $WHAT_FOUND;
				}
			}
		}
		// Create SQL query for the user table
		$sql3 = array();
		foreach ((array)$ban_fields as $ban_name => $new_ban_value) {
			// No need to change ban status
			if ($new_ban_value == 0) {
				continue;
			}
			// Check if this ban type is already banned for the current user
			if (!empty($user_info) && $user_info[$ban_name] == $new_ban_value) {
				continue;
			}
			// Do add SQL for the update query
			$sql3[] = " "._es($ban_name)."='1' ";
		}
		// Do update user's table (if needed)
		if (!empty($sql3)) {
			$NEW_ADMIN_COMMENTS = "\r\n==============\r\nAuto-banned on "._format_date(time())." (action: ".$_GET["object"]."->".$_GET["action"]."; ".implode(",", $ban_reasons).")";
			$sql4 = "UPDATE ".db('user')." SET 
					".implode(",", $sql3).", 
					admin_comments = CONCAT(admin_comments, '"._es($NEW_ADMIN_COMMENTS)."')
				WHERE id=".intval($input["user_id"]);
			db()->query($sql4);
		}
		return $BANNED_STATUS;
	}

	/**
	* Try to find ban reason
	*/
	function _try_to_find ($input_name = "", $CUR_CHECKS = array(), $where_search = "") {
		// Default value
		$IS_FOUND	= false;
		$WHAT_FOUND	= "";
		// Stop here if missing some params
		$what_to_find = $CUR_CHECKS[$input_name];
		if (empty($input_name) || empty($CUR_CHECKS) || !strlen($where_search)) {
			return $IS_FOUND;
		}
		// Switch between active checks
		if (strlen($what_to_find) && in_array($input_name, array("name", "email", "password", "phone", "fax", "url", "recip_url"))) {
			$IS_FOUND = $this->_search_item($where_search, $what_to_find);
			$_where_searched = $where_search;
		}
		if ($input_name == "text") {
			if (strlen($what_to_find)) {
				$IS_FOUND = $this->_search_item($where_search, $what_to_find);
				$_where_searched = $where_search;
			}
			// Also check for emails and phones
			if (!$IS_FOUND && strlen($CUR_CHECKS["email"])) {
				$IS_FOUND = $this->_search_item($where_search, $CUR_CHECKS["email"]);
				$_where_searched = "email";
			}
			if (!$IS_FOUND && strlen($CUR_CHECKS["phone"])) {
				$IS_FOUND = $this->_search_item($where_search, $CUR_CHECKS["phone"]);
				$_where_searched = "phone";
			}
			if (!$IS_FOUND && strlen($CUR_CHECKS["fax"])) {
				$IS_FOUND = $this->_search_item($where_search, $CUR_CHECKS["fax"]);
				$_where_searched = "fax";
			}
			// Complex search
			if (!$IS_FOUND && strlen($CUR_CHECKS["phone"])) {
				$IS_FOUND = $this->_complex_phone_search($where_search, $CUR_CHECKS["phone"]);
				$_where_searched = "phone";
			}
			// Complex search
			if (!$IS_FOUND && strlen($CUR_CHECKS["fax"])) {
				$IS_FOUND = $this->_complex_phone_search($where_search, $CUR_CHECKS["fax"]);
				$_where_searched = "fax";
			}
		}
		if (in_array($input_name, array("ad_text","forum_text","comment_text","email_text"))) {
			if (strlen($what_to_find)) {
				$IS_FOUND = $this->_search_item($where_search, $what_to_find);
				$_where_searched = $where_search;
			}
			// Also check for emails and phones
			if (!$IS_FOUND && strlen($CUR_CHECKS["email"])) {
				$IS_FOUND = $this->_search_item($where_search, $CUR_CHECKS["email"]);
				$_where_searched = "email";
			}
			if (!$IS_FOUND && strlen($CUR_CHECKS["phone"])) {
				$IS_FOUND = $this->_search_item($where_search, $CUR_CHECKS["phone"]);
				$_where_searched = "phone";
			}
			if (!$IS_FOUND && strlen($CUR_CHECKS["fax"])) {
				$IS_FOUND = $this->_search_item($where_search, $CUR_CHECKS["fax"]);
				$_where_searched = "fax";
			}
			// Also try to apply checks for text
			if (!$IS_FOUND && strlen($CUR_CHECKS["text"])) {
				$IS_FOUND = $this->_search_item($where_search, $CUR_CHECKS["text"]);
				$_where_searched = "text";
			}
			// Complex search
			if (!$IS_FOUND && strlen($CUR_CHECKS["phone"])) {
				$IS_FOUND = $this->_complex_phone_search($where_search, $CUR_CHECKS["phone"]);
				$_where_searched = "phone";
			}
			// Complex search
			if (!$IS_FOUND && strlen($CUR_CHECKS["fax"])) {
				$IS_FOUND = $this->_complex_phone_search($where_search, $CUR_CHECKS["fax"]);
				$_where_searched = "fax";
			}
		}
		// Prepare default message what was found
		if ($IS_FOUND && empty($WHAT_FOUND)) {
			$WHAT_FOUND = "(reason_id:".$CUR_CHECKS["id"]."), ".(!empty($_where_searched) ? " checked_field:".$_where_searched.", " : "").$input_name."=".(strlen($where_search) > 32 ? substr($where_search, 0, 32)."..." : $where_search);
		}
		return $WHAT_FOUND;
	}

	/**
	* Try to find phone by regexp
	*/
	function _complex_phone_search ($input_text = "", $phone = "") {
		if (empty($phone) || empty($input_text)) {
			return false;
		}
		$pattern = "";
		// Prepare regexp (we need to check different chunks)
		for ($i = 0; $i < strlen($phone); $i++) {
			$cur_symbol = intval($phone{$i});
			$pattern .= "[^0-9]{0,3}".$cur_symbol;
		}
		// Cut all repeated tabs, spaces, CR, LF
		$input_text = preg_replace("/[\s\t\r\n]+/ims", " ", $input_text);
		// Try to match prepared pattern
		return preg_match("/".$pattern."/ims", $input_text);
	}

	/**
	* Try to find item by regexp
	*/
	function _search_item ($where_search = "", $what_to_search = "") {
		$IS_FOUND = false;
		if ($this->USE_REGEXES) {
			$IS_FOUND = @preg_match("/".str_replace("/", "\/", $what_to_search)."/ims", $where_search);
		} else {
			$IS_FOUND = (false !== strpos($where_search, $what_to_search));
		}
		return $IS_FOUND;
	}
}
