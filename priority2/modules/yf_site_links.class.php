<?php

//-----------------------------------------------------------------------------
// Links rotating script
class yf_site_links {

	/** @var int Number of columns to divide links display */
	public $NUM_COLS			= 2;
	/** @var int @conf_skip */
	public $USER_ID			= null;
	/** @var array @conf_skip */
	public $_user_info			= null;
	/** @var int @conf_skip */
	public $PRIORITY			= null;
	/** @var array @conf_skip Link statuses */
	public $_link_statuses		= array(
		0 => "New",
		1 => "Waiting",
		2 => "Active",
		3 => "Updated",
		4 => "Suspended",
		5 => "Outdated",
	);
	/** @var array @conf_skip Link types */
	public $_link_types		= array(
		"Text",
		"Banner",
	);

	//-----------------------------------------------------------------------------
	// YF module constructor
	function _init () {
		$GLOBALS['no_page_header'] = true;
		define("LINKS_CLASS_NAME", "site_links");

		main()->USER_ID = ($_SESSION["user_group"] == 5) ? intval($_SESSION["user_id"]) : null;
		// Get user info
		if (!empty(main()->USER_ID)) {
			$this->_user_info = db()->query_fetch("SELECT * FROM ".db('links_users')." WHERE id=".intval(main()->USER_ID));
			// Check user is valid
			if (empty($this->_user_info["id"])) {
				main()->USER_ID = null;
			}
		}
		// Link priorities array
		$this->_link_priorities	= range(0, 5);
		// Get faqs categories
		$this->CATS_OBJ			= &main()->init_class("cats", "classes/");
		$this->CATS_OBJ->_default_cats_block = "links_cats";
		$this->_site_cats_items	= $this->CATS_OBJ->_get_items_array();
		$this->_site_cats		= $this->CATS_OBJ->_prepare_for_box("", 0);
		// Get priority from site confirg file
		if (defined("SITE_FEATURED_LINKS_PRIORITY")) {
			$this->PRIORITY = SITE_FEATURED_LINKS_PRIORITY;
		}
	}

	//-----------------------------------------------------------------------------
	// Show categories
	function show () {
		if ($_GET["id"]) {
			if (is_numeric($_GET["id"])) {
				$_GET["cat_id"]		= $_GET["id"];
			} else {
				$_GET["cat_name"]	= $_GET["id"];
			}
		}
		$_GET["cat_id"]		= intval($_GET["cat_id"]);
		$_GET["cat_name"]	= _es(trim(stripslashes($_GET["cat_name"])));
		// Default page	
		if (empty($_GET["cat_name"]) && empty($_GET["cat_id"])) {
			$num_cats = count($this->_site_cats_items);
			$i = 0;
			foreach ((array)$this->_site_cats_items as $_cat_id => $A) {
				// Try to find middle record
				if (!($i++ % ceil($num_cats / $this->NUM_COLS))) {
					$cats .= tpl()->parse(LINKS_CLASS_NAME."/main_div");
				}
				$replace2 = array(
					"url"	=> "./?object=".LINKS_CLASS_NAME."&action=show&id=".urlencode($A["url"]),
					"name"	=> $A["name"],
				);
				$cats .= tpl()->parse(LINKS_CLASS_NAME."/main_item", $replace2);
			}
			// Process featured links
			$Q2 = db()->query(
				"SELECT * FROM ".db('links_links')." 
				WHERE status IN(1,2) 
					AND priority >= ".intval($this->PRIORITY)." 
/*					AND site".(int)conf('SITE_ID')."=1 */
				ORDER BY email1_time DESC"
			);
			$num_links = db()->num_rows($Q2);
			$i = 0;
			while ($A2 = db()->fetch_assoc($Q2)) {
				// Try to find middle record
				if (!($i++ % ceil($num_links / $this->NUM_COLS))) {
					$links .= tpl()->parse(LINKS_CLASS_NAME."/main_div");
				}
				$replace2 = array(
					"url"	=> $A2["url"],
					"name"	=> $A2["title"],
				);
				$links .= tpl()->parse(LINKS_CLASS_NAME."/main_item", $replace2);
			}
			$replace = array(
				"cats"			=> $cats,
				"links"			=> $links,
				"top_list_url"	=> "./?object=".LINKS_CLASS_NAME."&action=top_list",
				"register_url"	=> "./?object=".LINKS_CLASS_NAME."&action=register",
				"edit_url"		=> main()->USER_ID ? "./?object=".LINKS_CLASS_NAME."&action=account"._add_get() : "./?object=".LINKS_CLASS_NAME."&action=login",
			);
			$body = tpl()->parse(LINKS_CLASS_NAME."/main", $replace);

		// Category contents
		} elseif (!empty($_GET["cat_name"]) || !empty($_GET["cat_id"])) {
			// Get category details
			if (!empty($_GET["cat_name"])) {
				foreach ((array)$this->_site_cats_items as $_cat_id => $A) {
					if ($A["url"] == $_GET["cat_name"]) {
						$cat_info = $A;
						break;
					}
				}
			} else {
				$cat_info = $this->_site_cats_items[$_GET["cat_id"]];
			}
			if ($cat_info["id"]) {
				$GLOBALS['_links_cat_name'] = $cat_info["name"];
				// Get links inside this category
				$Q = db()->query(
					"SELECT * FROM ".db('links_links')." 
					WHERE cat_id=".intval($cat_info["id"])." 
						AND status IN(1,2) 
/*						AND site".(int)conf('SITE_ID')."=1 */
					ORDER BY email1_time DESC"
				);
				$num_links = db()->num_rows($Q);
				$i = 0;
				// Get links info
				while ($A = db()->fetch_assoc($Q)) {
					$links[$A["id"]] = $A;
					// Fill text and banner links ids
					if ($A["type"] == 1) {
						$banner_links_ids[$A["id"]] = $A["id"];
					} else {
						$text_links_ids[$A["id"]] = $A["id"];
					}
				}
				// Try to show 2 texts and one banner here
				$max_text	= count($text_links_ids);
				$max_banner	= count($banner_links_ids);
				$a = 0;
				$b = 0;
				if ($num_links)	for ($i = 0; $i < $num_links; $i++) {
					if (($i % 3 != 0 && $max_text > $a) || $max_banner <= $b) {
						$link_info	= $links[array_shift($text_links_ids)];
						$tpl_name	= "text";
					} else {
						$link_info	= $links[array_shift($banner_links_ids)];
						$tpl_name	= "banner";
					}
					$replace2 = array(
						"site_url"	=> $link_info["url"],
						"site_name"	=> $link_info["title"],
						"banner_url"=> $link_info["banner_url"],
						"img_alt"	=> $link_info["title"],
						"text"		=> $link_info["description"],
					);
					if ($tpl_name == "banner" && empty($link_info["banner_url"])) {
						continue;
					}
					$items .= tpl()->parse(LINKS_CLASS_NAME."/in_cat_item_".$tpl_name, $replace2);
				}
				$replace = array(
					"cat_name"		=> $cat_info["name"],
					"items"			=> !empty($items) ? $items : "<div align='center'>No links here.</div>",
					"add_link_url"	=> main()->USER_ID ? "./?object=".LINKS_CLASS_NAME."&action=add_link"._add_get() : "./?object=".LINKS_CLASS_NAME."&action=login",
					"links_main_url"=> "./?object=".LINKS_CLASS_NAME,
					"bottom"		=> $cat_info["bottom"],
				);
				$body = tpl()->parse(LINKS_CLASS_NAME."/in_cat_main", $replace);
			} else {
				$body = "<div align='center'>No links here.</div>";
			}
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Process registration
	function register () {
		// Show registration form
		if (empty($_POST["go"])) {
			$replace = array(
				"form_action"	=> "./?object=".LINKS_CLASS_NAME."&action=register",
				"name"			=> "",
				"email"			=> "",
				"login_url"		=> process_url("./?object=".LINKS_CLASS_NAME."&action=login"),
			);
			$body = tpl()->parse(LINKS_CLASS_NAME."/register", $replace);
		} else {
			// Verify required vars
			if (empty($_POST["name"])) {
				_re("Name is too short!");
			} elseif (strlen($_POST["name"]) > 40) {
				_re("Name is too long! Maximum length allowed is 40 characters!");
			}
			if (!common()->email_verify($_POST["email"])) {
				_re("Invalid E-mail!");
			}
			if (strlen($_POST["password"]) < 3) {
				_re("Password is too short. Minimum length is 3 characters!");
			} elseif (strlen($_POST["password"]) > 12) {
				_re("Password is too long.  Maximum length allowed is 12 characters!");
			}
			if ($_POST["password"] != $_POST["password2"]) {
				_re("Passwords do not match!");
			}
			if (db()->query_num_rows("SELECT * FROM ".db('links_users')." WHERE email='"._es(stripslashes($_POST["email"]))."'")) {
				_re("User with this email already exsists! Please login with your account password. You can add multiple sites to your account.");
			}
			// Check if errors occured
			if (!common()->_error_exists()) {
				$sql = "INSERT INTO ".db('links_users')." (
						name,
						email,
						password,
						time
					) VALUES (
						'"._es($_POST["name"])."',
						'"._es($_POST["email"])."',
						'"._es($_POST["password"])."',
						".time()."
					)\r\n";
				db()->query($sql);
				// Send confirmation email
				$replace = array(
					"name"		=> $_POST["name"],
					"email"		=> $_POST["email"],
					"password"	=> $_POST["password"],
					"login_url"	=> process_url("./?object=".LINKS_CLASS_NAME."&action=login"),
				);
				$text = tpl()->parse(LINKS_CLASS_NAME."/email_register", $replace);
				common()->send_mail(SITE_ADMIN_EMAIL_LINKS, "Admin ".SITE_ADVERT_NAME, $_POST["email"], $_POST["name"], "Your link exchange account has been created!", $text, nl2br($text));
				// Show success message
				$replace2 = array(
					"login_url"	=> process_url("./?object=".LINKS_CLASS_NAME."&action=login"),
				);
				$body = tpl()->parse(LINKS_CLASS_NAME."/register_success", $replace2);
			} else {
				$body = _e();
			}
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Process login
	function login () {
		// Process log in
		if (!empty($_POST["go"]) && !empty($_POST["email"]) && !empty($_POST["password"])) {
			$A = db()->query_fetch("SELECT * FROM ".db('links_users')." WHERE email='"._es(trim($_POST["email"]))."' AND password='"._es(trim($_POST["password"]))."' LIMIT 1");
			if ($A['id']) {
				// Insert data into session
				$_SESSION['user_id']	= $A["id"];
				$_SESSION['user_group']	= 5;
				// Return user back
				return js_redirect("./?object=".LINKS_CLASS_NAME."&action=account"._add_get());
			} else {
				$replace = array(
					"login_form_url"	=> process_url("./?object=".LINKS_CLASS_NAME."&action=login"),
				);
				return tpl()->parse("login_form/wrong_login", $replace);
			}
		// Show login form
		} else {
			$replace = array(
				"form_action"	=> "./?object=".LINKS_CLASS_NAME."&action=login",
				"register_url"	=> "./?object=".LINKS_CLASS_NAME."&action=register",
				"get_pswd_url"	=> "./?object=".LINKS_CLASS_NAME."&action=get_pswd",
			);
			$body = tpl()->parse(LINKS_CLASS_NAME."/login_form", $replace);
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Retrieve lost password
	function get_pswd () {
		// Process retrieving password
		if (!empty($_POST["email"])) {
			$A = db()->query_fetch("SELECT * FROM ".db('links_users')." WHERE email='"._es(trim($_POST["email"]))."' LIMIT 1");
			if (empty($A['id'])) {
				_re("User not found!");
			}
			// Check errors
			if (!common()->_error_exists()) {
				$replace = array(
					"name"		=> _display_name($A),
					"email"		=> $A["email"],
					"password"	=> $A["password"],
					"login_url"	=> process_url("./?object=".LINKS_CLASS_NAME."&action=login"),
				);
				// Send email with requested info
				$text = tpl()->parse(LINKS_CLASS_NAME."/email_get_pswd", $replace);
				common()->send_mail(SITE_ADMIN_EMAIL_LINKS, SITE_ADVERT_NAME, $A["email"], _display_name($A["name"]), "Your Password", $text, $text);
				// Show success message
				$body = tpl()->parse(LINKS_CLASS_NAME."/get_pswd_success", $replace);
			} else {
				$body = _e();
			}
		// Show login form
		} else {
			$replace = array(
				"form_action"	=> "./?object=".LINKS_CLASS_NAME."&action=get_pswd"._add_get(),
			);
			$body = tpl()->parse(LINKS_CLASS_NAME."/get_pswd", $replace);
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Main user account
	function account () {
		// Only for the authorized users
		if (!main()->USER_ID) {
			return js_redirect("./?object=".LINKS_CLASS_NAME."&action=login");
		}
		// Get user links
		$Q = db()->query("SELECT * FROM ".db('links_links')." WHERE user_id=".intval(main()->USER_ID));
		while ($A = db()->fetch_assoc($Q)) $links[$A["id"]] = $A;
		// Process links
		foreach ((array)$links as $link_id => $A) {
			$replace2 = array(
				"num"			=> ++$i,
				"bg_class"		=> !($i % 2) ? "bg1" : "bg2",
				"site_title"	=> $A["title"],
				"cat_name"		=> $this->_site_cats[$A["cat_id"]],
				"type"			=> $this->_link_types[$A["type"]],
				"site_url"		=> $A["url"],
				"link_url"		=> $A["link_url"],
				"status"		=> $this->_link_statuses[$A["status"]],
				"add_date"		=> _format_date($A["email1_time"]),
				"edit_url"		=> "./?object=".LINKS_CLASS_NAME."&action=edit_link&id=".$A["id"]._add_get(),
				"delete_url"	=> "./?object=".LINKS_CLASS_NAME."&action=delete_link&id=".$A["id"]._add_get(),
			);
			$items .= tpl()->parse(LINKS_CLASS_NAME."/account_item", $replace2);
		}
		$replace = array(
			"add_site_url"	=> "./?object=".LINKS_CLASS_NAME."&action=add_link"._add_get(),
			"items"			=> $items,
		);
		return tpl()->parse(LINKS_CLASS_NAME."/account_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Show edit form
	function edit_link () {
		// Only for the authorized users
		if (!main()->USER_ID) {
			return js_redirect("./?object=".LINKS_CLASS_NAME."&action=login");
		}
		$_GET["id"] = intval($_GET["id"]);
		// Try to get link detailed info
		if (!empty($_GET["id"])) {
			$link_info = db()->query_fetch("SELECT * FROM ".db('links_links')." WHERE id=".intval($_GET["id"]));
		}
		// Check owner of the link
		if (empty($link_info["id"])) {
			return _e("Not your link!");
		}
		// Check for same site
		if ($link_info["status"] == 4) {
			_re("This site is suspended!");
		}
		// Check if errors occured
		if (!common()->_error_exists()) {
			$user_info = &$this->_user_info;
			// Process main template
			$replace = array(
				"banners_url"		=> "./?object=banners",
				"site_list_url"		=> "./?object=".LINKS_CLASS_NAME."&action=account"._add_get(),
				"form_action"		=> "./?object=".LINKS_CLASS_NAME."&action=update_link&id=".$link_info["id"]._add_get(),
				"add_date"			=> _format_date($link_info["email1_time"]),
				"site_title"		=> $link_info["title"],
				"site_url"			=> $link_info["url"],
				"link_url"			=> $link_info["link_url"],
				"banner_url"		=> $link_info["banner_url"],
				"desc"				=> $link_info["description"],
				"link_type_box"		=> $this->_link_type_box($link_info["type"]),
				"site_cat_box"		=> $this->_site_cat_box($link_info["cat_id"]),
				"sites_box"			=> $this->_show_links_sites($link_info),
			);
			return tpl()->parse(LINKS_CLASS_NAME."/edit_link", $replace);
		} else {
			return _e();
		}
	}

	//-----------------------------------------------------------------------------
	// Show add form
	function add_link () {
		// Only for the authorized users
		if (!main()->USER_ID) {
			return js_redirect("./?object=".LINKS_CLASS_NAME."&action=login");
		}
		$user_info = &$this->_user_info;
		// Process main template
		$replace = array(
			"banners_url"		=> "./?object=banners"._add_get(),
			"site_list_url"		=> "./?object=".LINKS_CLASS_NAME."&action=account"._add_get(),
			"form_action"		=> "./?object=".LINKS_CLASS_NAME."&action=insert_link"._add_get(),
			"add_date"			=> _format_date($link_info["email1_time"]),
			"site_title"		=> $link_info["title"],
			"site_url"			=> $link_info["url"],
			"link_url"			=> $link_info["link_url"],
			"banner_url"		=> $link_info["banner_url"],
			"desc"				=> $link_info["description"],
			"link_type_box"		=> $this->_link_type_box($link_info["type"]),
			"site_cat_box"		=> $this->_site_cat_box($link_info["cat_id"]),
			"sites_box"			=> $this->_show_links_sites($link_info),
		);
		return tpl()->parse(LINKS_CLASS_NAME."/add_link", $replace);
	}

	//-----------------------------------------------------------------------------
	// Insert new record
	function insert_link () {
		// Only for the authorized users
		if (!main()->USER_ID) {
			return js_redirect("./?object=".LINKS_CLASS_NAME."&action=login");
		}
		// Verify required vars
		$this->_verify_link_post();
		// Check if errors occured
		if (!common()->_error_exists()) {
			// Check for same site
			if (db()->query_num_rows("SELECT * FROM ".db('links_links')." WHERE url LIKE '%"._es($_POST["url"])."%'")) {
				_re("Site already exsist!");
			}
		}
		// Check if errors occured
		if (!common()->_error_exists()) {
			// Get category info
			$cat_info = $this->_site_cats_items[$_POST["cat_id"]];
			// Check for same site
			if (empty($cat_info["id"])) {
				_re("Wrong category ID!");
			}
		}
		// Check if errors occured
		if (!common()->_error_exists()) {
			// Ge user info
			$user_info = &$this->_user_info;
			// Process banner
			$this->_get_remote_image();
			// Process sites
			$Q = db()->query("SELECT * FROM ".db('links_sites')."");
			while ($A = db()->fetch_array($Q)) {
				$_POST["site"][$A["id"]] = intval($_POST["site"][$A["id"]]);
				if (!empty($_POST["site"][$A["id"]])) {
					$site_names .= $A["title"]." - ".$A["url"]."\r\n";
				}
			}
			// Generate sites SQL
			for ($i = 1; $i <= 30; $i++) {
				$sites_sql_array1[$i] = "\r\n site".$i." ";
				$sites_sql_array2[$i] = "\r\n".intval($_POST["site"][$i])." ";
			}
			// Generate SQL
			$sql = "INSERT INTO ".db('links_links')." (
						user_id,
						cat_id,
						status,
						type,
						title,
						url,
						link_url,
						banner_url,
						description,
						priority,
						email1_time,
						".implode(",", $sites_sql_array1)."
				) VALUES (
						".main()->USER_ID.",
						".intval($_POST["cat_id"]).",
						0,
						".intval($_POST["type"]).",
						'"._es($_POST["title"])."',
						'"._es($_POST["url"])."',
						'"._es($_POST["link_url"])."',
						'"._es($_POST["banner_url"])."',
						'"._es($_POST["description"])."',
						0,
						".time().",
						".implode(",", $sites_sql_array2)."
				)\r\n";
			db()->query($sql);
			// Send email to the user
			$replace = array(
				"name"				=> $_POST["name"],
				"title"				=> $_POST["title"],
				"url"				=> $_POST["url"],
				"link_url"			=> $_POST["link_url"],
				"banner_url"		=> $_POST["banner_url"],
				"cat_name"			=> $cat_info["name"],
				"desc"				=> $_POST["description"],
				"banners_url"		=> "./?object=banners",
				"site_names"		=> $site_names,
				"site_names_html"	=> $site_names,
//				"site_names_html"	=> $site_names_html,
				"login_url"			=> process_url("./?object=".LINKS_CLASS_NAME."&action=login"),
			);
			$text = tpl()->parse(LINKS_CLASS_NAME."/email_add_link", $replace);
			$email_to	= $user_info["email"];
			$name_to	= _display_name($user_info);
			common()->send_mail(SITE_ADMIN_EMAIL_LINKS, SITE_ADVERT_NAME, $email_to, $name_to, "Your Link Submission", $text, $text);
			// Show success message
			$body = tpl()->parse(LINKS_CLASS_NAME."/add_link_success", $replace);
		} else {
			$body = _e();
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Update existsing record
	function update_link () {
		// Only for the authorized users
		if (!main()->USER_ID) {
			return js_redirect("./?object=".LINKS_CLASS_NAME."&action=login");
		}
		$_GET["id"] = intval($_GET["id"]);
		// Try to get link detailed info
		if ($_GET["id"]) {
			$link_info = db()->query_fetch("SELECT * FROM ".db('links_links')." WHERE id=".$_GET["id"]);
		}
		// Check owner of the link
		if (empty($link_info["id"])) {
			return _e("Not your link!");
		}
		// Verify required vars
		$this->_verify_link_post();
		// Check if errors occured
		if (!common()->_error_exists()) {
			// Get category info
			$cat_info = $this->_site_cats_items[$_POST["cat_id"]];
			// Check for same site
			if (empty($cat_info["id"])) {
				_re("Wrong category ID!");
			}
		}
		// Check if errors occured
		if (!common()->_error_exists()) {
			// Ge user info
			$user_info = &$this->_user_info;
			// Process banner
			$this->_get_remote_image();
			// Process sites
			$Q = db()->query("SELECT * FROM ".db('links_sites')."");
			while ($A = db()->fetch_array($Q)) {
				$_POST["site"][$A["id"]] = intval($_POST["site"][$A["id"]]);
				if (!empty($_POST["site"][$A["id"]])) {
					$site_names .= $A["title"]." - ".$A["url"]."\r\n";
				}
			}
			// Generate sites SQL
			for ($i = 1; $i <= 30; $i++) {
				$sites_sql_array[$i] = "\r\n site".$i." = ".intval($_POST["site"][$i])." ";
			}
			// Generate SQL
			$sql = "UPDATE ".db('links_links')." SET 
					cat_id		= ".intval($_POST["cat_id"]).",
					title			= '"._es($_POST["title"])."',
					url			= '"._es($_POST["url"])."',
					link_url		= '"._es($_POST["link_url"])."',
					banner_url	= '"._es($_POST["banner_url"])."',
					description	= '"._es($_POST["description"])."',
					type			= ".intval($_POST["link_type"]).",
					".implode(",", $sites_sql_array)."
				 WHERE id=".$_GET["id"];
			db()->query($sql);
			return js_redirect("./?object=".LINKS_CLASS_NAME."&action=account"._add_get());
		} else {
			$body = _e();
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Do delete link
	function delete_link () {
		// Only for the authorized users
		if (!main()->USER_ID) {
			return js_redirect("./?object=".LINKS_CLASS_NAME."&action=login");
		}
		$_GET["id"] = intval($_GET["id"]);
		// Try to get link detailed info
		if ($_GET["id"]) {
			$link_info = db()->query_fetch("SELECT * FROM ".db('links_links')." WHERE id=".$_GET["id"]);
		}
		// Check owner of the link
		if (empty($link_info["id"])) {
			return _e("Not your link!");
		}
		// Do delete
		if (!empty($_POST["go"])) {
			// Do delete link
			db()->query("DELETE FROM ".db('links_links')." WHERE id=".$_GET["id"]." LIMIT 1");
			return js_redirect("./?object=".LINKS_CLASS_NAME."&action=account"._add_get());
		// Show confirmation dialog
		} else {
			$replace = array(
				"form_action"	=> "./?object=".LINKS_CLASS_NAME."&action=delete_link&id=".$_GET["id"]._add_get(),
				"cancel_url"	=> "./?object=".LINKS_CLASS_NAME."&action=account"._add_get(),
				"title"			=> $link_info["title"],
			);
			$body = tpl()->parse(LINKS_CLASS_NAME."/delete_confirm", $replace);
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Verify posted data
	function _verify_link_post() {
		// Check required data
		if (strlen($_POST["title"]) < 3) {
			_re("Site title is too short!");
		}
		if (strlen($_POST["title"]) > 50) {
			_re("Site title is too long! Maximum 50 characters allowed!");
		}
		if (strlen($_POST["url"]) < 10) {
			_re("Site URL is too short!");
		}
		if (strlen($_POST["url"]) > 250) {
			_re("Site URL is too long!");
		}
		if (!common()->url_verify($_POST["url"])) {
			_re("Invalid site URL!");
		}
		if (strlen($_POST["link_url"]) < 10) {
			_re("Link URL is too short!");
		}
		if (strlen($_POST["link_url"]) > 250) {
			_re("Link URL is too long!");
		}
		if (!common()->url_verify($_POST["link_url"])) {
			_re("Invalid link URL syntax!");
		}
		if (!$_POST["banner_url"] && $_POST["type"] == 1) {
			_re("Banner URL is too short!");
		}
		if (strlen($_POST["banner_url"]) > 250 && $_POST["type"] == 1) {
			_re("Banner URL is too long!");
		}
		if ($_POST["banner_url"] == "http://") {
			$_POST["banner_url"] = "";
		} elseif (!common()->url_verify($_POST["banner_url"]) && $_POST["type"] == 1) {
			_re("Invalid banner URL!");
		}
		if (!$_POST["description"] && $_POST["type"] == 0) {
			_re("Site description is too short!");
		} elseif (strlen($_POST["description"]) > 512 && $_POST["type"] == 0) {
			_re("Site description is too long!");
		}
		// Clean up description
		$_POST["description"] = str_replace("'", "&#39;", htmlspecialchars(strip_tags(stripslashes($_POST["description"]))));
		$_POST["description"] = _check_words_length ($_POST["description"]);
	}

	//-----------------------------------------------------------------------------
	// Process banners file from remote image
	function _get_remote_image() {
		if (empty($_POST["banner_url"]) || !$_POST["get_banner"]) {
			return false;
		}
		$url = $_POST["banner_url"];
		preg_match('#\.(jpg|jpeg|gif|png)$#i', $url, $ext);
		$new_file = uniqid('img') . "." . $ext['1'];
		$new_file_path = SITE_LINKS_BANNERS_DIR. $new_file;
		// Check remote file size and get it
		$tmp_var = "";
		if (common()->remote_file_size($_POST["banner_url"]) <= 1000000) {
			$tmp_var = common()->get_remote_page($_POST["banner_url"]);
		}
		if (!empty($tmp_var)) {
			file_put_contents(INCLUDE_PATH. $new_file_path, $tmp_var);
		}
		// If upload is ok - chenge banner url to new one
		if (file_exists(INCLUDE_PATH. $new_file_path) && filesize(INCLUDE_PATH. $new_file_path) > 10) {
			$_POST["banner_url"] = SITE_ADVERT_URL. $new_file_path;
			return $_POST["banner_url"];
		}
		return false;
	}

	//-----------------------------------------------------------------------------
	// Show sites
	function _show_links_sites ($link_info = array()) {
		$items = "";
		$Q = db()->query("SELECT * FROM ".db('links_sites')."");
		while ($A = db()->fetch_array($Q)) {
			$replace = array(
				"site_id"		=> $A["id"],
				"check"			=> !empty($link_info["site".$A["id"]]) ? "checked" : "",
				"site_url"		=> $A["url"],
				"site_name"		=> $A["title"],
				"links_url"		=> $A["links_url"],
				"banners_url"	=> $A["banner_url"],
			);
			$items .= tpl()->parse(LINKS_CLASS_NAME."/sites_item", $replace);
		}
		return $items;
	}

	//-----------------------------------------------------------------------------
	// Show list of top links
	function top_list () {
		$replace = array(
			"add_link"		=> "./?object=".LINKS_CLASS_NAME."&action=add_link",
			"edit_link"		=> "./?object=".LINKS_CLASS_NAME."&action=account",
			"banners_link"	=> "./?object=banners",
		);
		return tpl()->parse(LINKS_CLASS_NAME."/top_list", $replace);
	}

	// ##### BOXES SECTION ##### //

	//-----------------------------------------------------------------------------
	//
	function _link_type_box ($selected = "") {
		return common()->radio_box("link_type", $this->_link_types, $selected, false, 2, "", false);
	}

	//-----------------------------------------------------------------------------
	//
	function _site_cat_box ($selected = "") {
		return common()->select_box("cat_id", $this->_site_cats, $selected, false, 2, "", false);
	}
}
