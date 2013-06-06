/*
mf - Messages Frame
uf - Users Online Frame
pf - Private Messages Frame
ff - Form frame to post new messages
rf - Rooms Frame
*/
var mf = null, 
	uf = null, 
	pf = null, 
	ff = null, 
	rf = null;

// Array of users in the current room
var CHAT_USERS = [];

//---------------------------------------------------------------------
// Initialize PROFY_CHAT class
function chat_init () {
	PROFY_CHAT = new PROFY_CHAT();
	PROFY_CHAT.init();
}

//---------------------------------------------------------------------
// Main PROFY_CHAT class
var PROFY_CHAT = function() {

	// Init mode (currently supported: "FRAMESET_MODE", "IFRAME_MODE", "DIV_MODE" (experimental))
	this.INIT_MODE			= "";
	// Array of chat vars
//	this.VARS				= CHAT_VARS;
	// Internationalization array
//	this.LANG				= CHAT_LANG;
	// Array of users info's
//	this.USERS				= CHAT_USERS;
	// Array of chat templates
//	this.HTML_TPLS			= CHAT_HTML_TPLS;
	// Array of chat colors
//	this.COLORS				= CHAT_COLORS;
	// Conection object
	this.CONN_OBJ			= null;
	// ID of the window interval
	this.INTERVAL_ID		= false;
	//
	this.msg_num			= 0;
	//
	this.private_num		= 0;
	// Current user agent
	this.ua					= navigator.userAgent.toLowerCase();
	// Check if this is Opera
	this.is_opera			= this.ua.indexOf("opera") > -1;
	// Check if this is Mozilla
	this.is_moz				= this.ua.indexOf('gecko') >- 1;
	// Check if this is IE
	this.is_ie				= window.ActiveXObject ? true : false;
	// 
	this.remember_nick		= '';

	//---------------------------------------------------------------------
	this.init				= function () {
		try {
			// Initialize debugging methods
			this.init_debug();
			// Try to initialize IFRAME version
			var frames_col = parent.window.document.getElementsByTagName("IFRAME");
			// Else try to load FRAMESET version
			if (frames_col.length == 0) {
				var frames_col = document.getElementsByTagName("FRAME");
				if (frames_col.length > 0) {
					PROFY_CHAT.INIT_MODE = "FRAMESET_MODE";
				}
			} else {
				PROFY_CHAT.INIT_MODE = "IFRAME_MODE";
			}
			// Frames mode (IFRAME_MODE or FRAMESET_MODE)
			if (PROFY_CHAT.INIT_MODE == "IFRAME_MODE" || PROFY_CHAT.INIT_MODE == "FRAMESET_MODE") {
				for (var i = 0; i < frames_col.length; i++) {
					var fid = frames_col[i].id;
					if (fid.length == 7 && fid.substring(0,5) == "chat_") {
						eval(fid.substring(5,7) + '=' + 'this.is_opera ? frames_col[i] : parent.window.frames[i]');
					}
				}
				// Check required areas (mf, ff)
				if (typeof mf == "object" && typeof ff == "object") {
					// Try to init main PROFY_CHAT structures
					ff.document.open();
					ff.document.write(CHAT_VARS["post_page_contents"]);
					ff.document.close();
					if (CHAT_VARS["own_group_id"] == "1") {
						ff.document.getElementById("chat_ban_list_button").style.display = "inline";
					}
				} else {
					// Give up...
					PROFY_CHAT.INIT_MODE = "";
				}
			// Maybe DIV_MODE ?
			} else {
				var divs_col = document.getElementsByTagName("DIV");
				for (var i = 0; i < divs_col.length; i++) {
					var fid = divs_col[i].id;
					if (fid.length == 7 && fid.substring(0,5) == "chat_") {
						eval(fid.substring(5,7) + '=' + 'document.getElementById("' + fid + '")');
					}
				}
				// Check required areas (mf, ff)
				if (typeof mf == "object" && typeof ff == "object") {
					PROFY_CHAT.INIT_MODE = "DIV_MODE";
					// Try to init main PROFY_CHAT structures
					ff.innerHTML = CHAT_VARS["post_page_contents"].replace(/<html>.*<div id=\"profy_chat\">/ig, '').replace(/(<\/body>|<\/html>)/ig, '');
				} else {
					// Give up...
					PROFY_CHAT.INIT_MODE = "";
				}
			}
			// Check if we found init mode
			if (PROFY_CHAT.INIT_MODE == "" || !PROFY_CHAT.INIT_MODE) {
				throw('Init mode not found');
			} else {
				this._copy_css_styles();
				this._set_refresh_time();
				this._rebuild_users();
				this.build_msg_divs();
				this.build_private_divs();
				if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
					this.post_form_init();
				}
			}
		} catch (e) {
			alert('Cant init PROFY_CHAT!');
			// Logging...
			PROFY_CHAT.debug_log("Cant init PROFY_CHAT!\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	// Get commands from server
	this.get_commands			= function  () {
		var known_users = "";
		for (i in CHAT_USERS) {
			known_users += CHAT_USERS[i]["user_id"] + ",";
		}
		var data_to_post = "known_users=" + known_users;
		// Make request
		PROFY_CHAT.CONN_OBJ = YAHOO.util.Connect.asyncRequest('POST', CHAT_VARS["xml_cmd_url"], PROFY_CHAT.xml_response_callback, data_to_post);
	}

	//---------------------------------------------------------------------
	// POST form handler
	this.check_form			= function () {
		try {
			// Init
			var f				= this._get_post_form_obj();
			var private_to_obj	= this._get_post_private_to_obj();
			var msg_obj			= this._get_post_msg_obj();
			// Process
			// Check if user is trying to submit empty message
			if (msg_obj.value == "") {
				alert(PROFY_CHAT.translate('empty_message'));
				return false;
			}
			// Make request
			var text_to_post = "";
			text_to_post = text_to_post + encodeURIComponent("msg") + "=" + encodeURIComponent(msg_obj.value) + "&";
			if (private_to_obj.value != "") {
				text_to_post = text_to_post + encodeURIComponent("private_to") + "=" + encodeURIComponent(private_to_obj.value) + "&";
			}
			text_to_post = text_to_post.substr(0, text_to_post.length - 1);
			this.CONN_OBJ = YAHOO.util.Connect.asyncRequest('POST', CHAT_VARS["post_form_url"], PROFY_CHAT.xml_response_callback, text_to_post);
			this.reset_form();
			this.get_commands();
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'check_form'\n", "e", e);
		}
		return false;
	}

	//---------------------------------------------------------------------
	// XML Response callback object
	this.xml_response_callback	= {
		success: function (obj) {
			// Response XML object
			var xml_obj = obj.responseXML;
			if (!xml_obj) {
				return false;
			}
			// Logging...
			PROFY_CHAT.debug_log("XML RESPONSE SUCCESS\n allResponseHeaders:\n " + obj.allResponseHeaders + "\n responseText:\n " + obj.responseText);
			// Start process items
			var cmd_items = xml_obj.getElementsByTagName("cmd");
			for (var i = 0; i < cmd_items.length; i++) {
				var cmd_name = null, cmd_value = null;
				var is_error_in_cmd = false;
				try {
					if (PROFY_CHAT.is_opera || PROFY_CHAT.is_moz) {
						cmd_name	= cmd_items[i].childNodes[1].childNodes[0].nodeValue;
						cmd_value	= cmd_items[i].childNodes[3];
					} else if (PROFY_CHAT.is_ie)  {
						cmd_name	= cmd_items[i].childNodes(0).childNodes(0).nodeValue;
						cmd_value	= cmd_items[i].childNodes(1);
					}
				} catch (e) {
					is_error_in_cmd = true;
				}
				if (!is_error_in_cmd) {
					PROFY_CHAT.process_server_cmds(cmd_name, cmd_value);
				}
			}
			return true;
		},
		failure: function (obj) {
			// Logging...
			PROFY_CHAT.debug_log("XML RESPONSE FAILURE!!!\n allResponseHeaders:\n " + obj.allResponseHeaders + "\n responseText:\n " + obj.responseText);
		}
	}

	//---------------------------------------------------------------------
	// Process server commands
	this.process_server_cmds		= function (cmd_name, cmd_value) {
		try {
			var child_nodes = cmd_value.hasChildNodes ? cmd_value.childNodes : [];
			if (typeof child_nodes != "object" || child_nodes == null || !child_nodes) {
				return false;
			}
			// Do special action on recognized command
			if (cmd_name == "users_add") {
				var users_array		= this._get_cmd_params_from_xml_nodes (child_nodes, "user");
				if (typeof users_array == "object" && users_array != null) {
					this.users_add(users_array);
				}
			} else if (cmd_name == "users_del") {
				var users_string	= this._get_text_from_xml_node(child_nodes);
				if (users_string.length > 0) {
					var users_ids = users_string.split(",");
					this.users_del(users_ids);
				}
			} else if (cmd_name == "msgs_add") {
				var msgs_array		= this._get_cmd_params_from_xml_nodes (child_nodes, "msg");
				if (typeof msgs_array == "object" && msgs_array != null) {
					this.messages_add(msgs_array);
				}
			} else if (cmd_name == "priv_add") {
				var private_array	= this._get_cmd_params_from_xml_nodes (child_nodes, "private");
				if (typeof private_array == "object" && private_array != null) {
					this.private_add(private_array);
				}
			} else if (cmd_name == "online_total") {
				var total_users		= this._get_text_from_xml_node(child_nodes);
				if (total_users) {
					CHAT_VARS["chat_total_users"] = total_users;
				}
			} else if (cmd_name == "do_alert_msg") {
				var msg_to_alert	= this._get_text_from_xml_node(child_nodes);
				if (msg_to_alert) {
					alert(msg_to_alert);
				}
			} else if (cmd_name == "do_reset_post_form") {
				this.reset_form();
			} else if (cmd_name == "do_get_cmds") {
				this.get_commands();
			} else if (cmd_name == "do_log_out") {
				this.do_log_out();
			}
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'process_server_cmds'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	// USERS ONLINE HERE
	//---------------------------------------------------------------------

	//---------------------------------------------------------------------
	this.build_user_divs		= function () {
		try {
			// Stop if there is no users frame
			if (typeof uf == "undefined" || uf == null) {
				return false;
			}
			this._kill_all_users_divs("chat_user_", uf);
			this._kill_user_dividers();
			var á				= 0;
			var	last_gender		= "";
			var	elm_inner_html	= "";
			// Show total number of online users
			var new_elm_obj			= this._create_elm("chat_users_in_all_rooms", uf);
			new_elm_obj.innerHTML	= this.translate('users_in_all_rooms') + ": " + CHAT_VARS["chat_total_users"];
			new_elm_obj.className	= "total_users";
			new_elm_obj				= null;
			// Show total number of online users
			var new_elm_obj			= this._create_elm("chat_users_in_room", uf);
			new_elm_obj.innerHTML	= this.translate('users_in_room') + ": " + CHAT_USERS.length;
			new_elm_obj.className	= "total_users2";
			new_elm_obj				= null;
			// Process users
			for (key in CHAT_USERS) {
				var new_elm_obj		= null;
				var try_elm_obj		= null;
				var cur_user_item	= CHAT_USERS[key];
				// prepare required vars
				var user_id			= cur_user_item["user_id"];
				var user_gender		= cur_user_item["gender"];
				var user_color		= cur_user_item["text_color"];
				var user_ignore		= cur_user_item["ignore_list"];
				var user_login		= cur_user_item["user_login"];
				var user_info		= cur_user_item["info_status"];
				var user_group_id	= cur_user_item["group_id"];
				// Skip wrong fields
				if (!user_id) {
					continue;
				}
				if (typeof user_info == "undefined" || user_info == null) {
					user_info		= 0;
				}
				if (typeof user_group_id == "undefined" || user_group_id == null) {
					user_group_id	= 0;
				}
				// Create div ID
				var elm_text_id = "chat_user_" + user_id;
				// Current user
				if (user_id == CHAT_VARS["own_id"]) {
					elm_inner_html = this._parse_tpl("user_elm_cur_user", {
						'user_login': user_login,
						'label'		: this.translate('YOU'),
						'user_color': user_gender == "m" ? CHAT_COLORS["user_male"] : CHAT_COLORS["user_female"]
					});
					// Show moderators online
					if (user_group_id == 1) {
						elm_inner_html += this._parse_tpl("user_elm_moderator_label", {
							'label'		: this.translate('moderator')
						});
					}
				// Other users
				} else {
					// User ignore link
					elm_inner_html = this._parse_tpl("user_elm_ignore_link", {
						'elm_id'	: "user_ignore_" + user_id,
						'user_id'	: user_id,
						'onclick'	: "parent.window.PROFY_CHAT.user_ignore(\"" + user_login + "\");",
						'title'		: user_ignore ? this.translate('Dont_ignore') : this.translate('Ignore'),
						'label'		: 'X',
						'user_color': user_ignore ? CHAT_COLORS["user_ignored"] : CHAT_COLORS["user_not_ignored"]
					});
					// User info link
					if (user_info != 0) {
						elm_inner_html += this._parse_tpl("user_elm_info_link", {
							'elm_id'	: "user_info_" + user_id,
							'user_id'	: user_id,
							'onclick'	: "parent.window.PROFY_CHAT.show_user_info(\"" + user_login + "\");",
							'title'		: user_info == 2 ? this.translate('User_Info_with_Photo') : this.translate('User_Info'),
							'label'		: user_info == 2 ? "P" : "I",
							'user_color': CHAT_COLORS["user_info"]
						});
					} else {
						// Spacer
						elm_inner_html += this._parse_tpl("user_elm_info_no_link", {
							'user_id'	: user_id,
							'elm_id'	: "user_info_" + user_id
						});
					}
					// Private link
					// Stop if there is no users frame
					if (typeof pf != "undefined") {
						elm_inner_html += this._parse_tpl("user_elm_private_link", {
							'elm_id'	: "user_private_" + user_id,
							'user_id'	: user_id,
							'onclick'	: "parent.window.PROFY_CHAT.private_to(\"" + user_login + "\");",
							'title'		: this.translate('Write_private'),
							'user_login': user_login,
							'user_color': user_gender == "m" ? CHAT_COLORS["user_male"] : CHAT_COLORS["user_female"]
						});
					} else {
						elm_inner_html += this._parse_tpl("user_elm_private_no_link", {
							'elm_id'	: "user_private_" + user_id,
							'user_id'	: user_id,
							'user_login': user_login,
							'user_color': user_gender == "m" ? CHAT_COLORS["user_male"] : CHAT_COLORS["user_female"]
						});
					}
					// Show moderators online
					if (user_group_id == 1) {
						elm_inner_html += this._parse_tpl("user_elm_moderator", {
							'label'		: this.translate('moderator')
						});
					}
					// Add buttons for ban users
					if (CHAT_VARS["own_group_id"] == 1 && user_group_id != 1) {
						elm_inner_html += this._parse_tpl("user_elm_ban_btn", {
							'elm_id'	: "user_ban_btn_" + user_id,
							'user_id'	: user_id,
							'onclick'	: "parent.window.PROFY_CHAT.do_ban_user(\"" + user_login + "\");",
							'title'		: this.translate('do_ban_user'),
							'label'		: 'X'
						});
					}
				}
				// Create user dividers
				if (key == 0 || last_gender == "" || (last_gender != "" && last_gender != user_gender)) {
					new_elm_obj				= this._create_elm("chat_user_" + user_gender, uf);
					new_elm_obj.innerHTML	= user_gender == "m" ? this.translate('MALE') : this.translate('FEMALE');
					new_elm_obj.className	= "users_" + user_gender;
					new_elm_obj				= null;
				}
				try_elm_obj = PROFY_CHAT.INIT_MODE == "DIV_MODE" 
					? document.getElementById(elm_text_id) 
					: uf.document.getElementById(elm_text_id);
				// Check if such element already exists
				if (!try_elm_obj) {
					new_elm_obj = this._create_elm(elm_text_id, uf);
					new_elm_obj.innerHTML = elm_inner_html;
				}
				last_gender = user_gender;
			}
			this._color_user_divs();
			return CHAT_USERS.length;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'build_user_divs'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this._rebuild_users			= function () {
		try {
			this.users_add(CHAT_VARS["users_online_array"]);
			return true;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_rebuild_users'\n", "e", e);
			return false;
		}
	}

	//---------------------------------------------------------------------
	this.users_add				= function (users_array) {
		try {
			// Check if current user already added
			for (key in users_array) {
				var user_pos = -1;
				for (num in CHAT_USERS) {
					if (CHAT_USERS[num]["user_id"] == users_array[key]["user_id"])	{
						user_pos = num;
						break;
					}
				}
				if (user_pos == -1) {
					CHAT_USERS[CHAT_USERS.length] = users_array[key];
				}
			}
			CHAT_USERS.sort(this._sort_by_gender_and_name);
			this.build_user_divs();
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'users_add'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this.users_del				= function (users_ids) {
		try {
			if (typeof users_ids != "object" || users_ids == "" || users_ids.length < 1) {
				return false;
			}
			// Create users array
			for (key in users_ids) {
				var user_pos = -1;
				for (num in CHAT_USERS) {
					if (CHAT_USERS[num]["user_id"] == users_ids[key]) {
						user_pos = num;
						break;
					}
				}
				if (user_pos != -1) {
					var tmp_array = [];
					var	c = 0;
					this._remove_elm("chat_user_" + users_ids[key], uf);
					for (num in CHAT_USERS) {
						if (num != user_pos) {
							tmp_array[c++] = CHAT_USERS[num];
						}
					}
					CHAT_USERS = tmp_array;
				}
			}
			CHAT_USERS.sort(this._sort_by_gender_and_name);
			this.build_user_divs();
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'users_del'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this._kill_all_users_divs	= function (prefix, frame_obj) {
		try {
			for (key in CHAT_USERS) {
				this._remove_elm(prefix + CHAT_USERS[key]["user_id"], frame_obj);
			}
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_kill_all_users_divs'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this._kill_user_dividers	= function () {
		try {
			this._remove_elm("chat_users_in_room", uf);
			this._remove_elm("chat_users_in_all_rooms", uf);
			this._remove_elm("chat_user_f", uf);
			this._remove_elm("chat_user_m", uf);
			return true;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_kill_user_dividers'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this._color_user_divs		= function () {
		try {
			var num = 0;
			// Process users
			for (key in CHAT_USERS) {
				if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
					var user_obj = document.getElementById("chat_user_" + CHAT_USERS[key]["user_id"]);
				} else {
					var user_obj = uf.document.getElementById("chat_user_" + CHAT_USERS[key]["user_id"]);
				}
				user_obj.className = "bg" + (num++%2 ? "1" : "2");
			}
			return num;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_color_user_divs'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this.show_user_info			= function (user_login) {
		try {
			var user_id = 0;
			for (key in CHAT_USERS) {
				if (user_login == CHAT_USERS[key]["user_login"]) {
					user_id = CHAT_USERS[key]["user_id"];
				}
			}
			window.open(CHAT_VARS["user_info_url"].replace(/%%id%%/i, user_id), 'chat_user_info_' + user_id, "scrollbars=auto, resizable=1");
			return true;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'show_user_info'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this.edit_ban_list			= function () {
		try {
			if (CHAT_VARS["own_group_id"] == "1") {
				window.open(CHAT_VARS["edit_ban_list_url"], 'chat_edit_ban_list',"width=600, height=450, scrollbars=auto, resizable=1");
			}
			return true;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'edit_ban_list'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this.edit_personal_info		= function () {
		try {
			window.open(CHAT_VARS["edit_info_url"], 'chat_edit_info');
			return true;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'edit_personal_info'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this.edit_settings			= function () {
		try {
			window.open(CHAT_VARS["edit_settings_url"], 'chat_edit_settings', "width=600, height=450, scrollbars=auto, resizable=1");
			return true;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'edit_settings'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	// MESSAGES METHODS
	//---------------------------------------------------------------------

	//---------------------------------------------------------------------
	this.messages_add			= function (msg_array) {
		try {
			if (typeof msg_array != "object" || !msg_array) {
				return false;
			}
			// Process users
			for (key in msg_array) {
				var new_elm_obj		= null;
				var elm_inner_html	= "";
				// Skip wrong fields
				var cur_msg_item	= msg_array[key];
				if (!cur_msg_item) {
					continue;
				}
				if (!cur_msg_item["msg_id"]) {
					continue;
				}
				// Create vars from array
				var msg_id			= cur_msg_item["msg_id"];
				var msg_user_login	= cur_msg_item["user_login"];
				var msg_text		= this._bbcode(cur_msg_item["text"]);
				var msg_date		= this._format_date(new Date(cur_msg_item["add_date"] * 1000));
				var msg_color		= cur_msg_item["text_color"];
				// Background color of the message
				var msg_bg_color	= "";
				if (msg_user_login == CHAT_VARS["own_login"]) {
					msg_bg_color = CHAT_VARS["user_color_1"];
				}
				if (msg_text.substring(0, CHAT_VARS["own_login"].length + 4) == (CHAT_VARS["own_login"] + "&gt;")) {
					msg_bg_color = CHAT_VARS["user_color_2"];
				}
				// Create user name or pad message
				var user_name	= "";
				var user_name_spacer = this._parse_tpl("msg_name_spacer", {
					'spacer'	: CHAT_VARS["sys_msg_padding"]
				});
				// Other user
				if (msg_user_login != CHAT_VARS["own_login"]) {
					user_name = this._add_user_link(msg_user_login, msg_color);
				// Current user
				} else {
					user_name = this._parse_tpl("msg_user_name", {
						'user_id'	: msg_user_login,
						'user_name'	: msg_user_login.length ? (msg_user_login + ":") : user_name_spacer
					});
				}
				// Add buttons for deleting messages
				if (CHAT_VARS["own_group_id"] == 1 && msg_user_login) {
					elm_inner_html += this._parse_tpl("msg_delete_btn", {
						'elm_id'	: "msg_del_btn_" + msg_id,
						'msg_id'	: msg_id,
						'onclick'	: "parent.window.PROFY_CHAT.do_ban_message(\"" + msg_id + "\");",
						'title'		: this.translate('do_ban_message'),
						'label'		: 'X'
					});
				}
				// Create div contents
				elm_inner_html += this._parse_tpl("msg_main_text", {
					'elm_id'	: "main_msg_" + msg_id,
					'msg_id'	: msg_id,
					'msg_date'	: msg_date,
					'msg_color'	: msg_color,
					'msg_text'	: msg_text,
					'user_name'	: user_name,
					'label'		: 'X'
				});
				// Create div ID
				var elm_text_id = "chat_msg_" + msg_id;
				var try_elm_obj	= PROFY_CHAT.INIT_MODE == "DIV_MODE" 
					? document.getElementById(elm_text_id) 
					: mf.document.getElementById(elm_text_id);
				// Check if such element already exists
				if (typeof try_elm_obj == "undefined" || !try_elm_obj) {
					new_elm_obj = this._create_elm(elm_text_id, mf);
					new_elm_obj.innerHTML = elm_inner_html;
					new_elm_obj.className = "msg_bg" + (this.msg_num++%2 ? "1" : "2");
					new_elm_obj.style.background = msg_bg_color;
				}
			}
			this._scroll_to_end(mf);
			return true;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'messages_add'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this.private_add			= function (msg_array) {
		try {
			// Stop if there is no private frame
			if (typeof pf == "undefined" || pf == null) {
				return false;
			}
			if (!msg_array) {
				return false;
			}
			// Process users
			for (key in msg_array) {
				var new_elm_obj = null;
				// Skip wrong fields
				var cur_msg_item	= msg_array[key];
				if (!cur_msg_item) {
					continue;
				}
				if (!cur_msg_item["msg_id"]) {
					continue;
				}
				// Create avrs from array
				var msg_id			= cur_msg_item["msg_id"];
				var msg_user_login	= cur_msg_item["user_login"];
				var msg_text		= this._bbcode(cur_msg_item["text"]);
				var msg_date		= this._format_date(new Date(cur_msg_item["add_date"] * 1000));
				var msg_color		= cur_msg_item["text_color"];
				var from_you		= cur_msg_item["msg_from_you"];
				var login_from		= from_you ? CHAT_VARS["own_login"] : msg_user_login;
				var login_to		= from_you ? msg_user_login : CHAT_VARS["own_login"];
				// Background color of the message (could be: default (empty), CHAT_VARS["user_color_1"], CHAT_VARS["user_color_2"])
				var msg_bg_color	= (login_to != CHAT_VARS["own_login"]) ? CHAT_VARS["user_color_3"] : CHAT_VARS["user_color_4"];
				// Create div contents
				var elm_inner_html	= "";
				elm_inner_html += this._parse_tpl("private_main_text", {
					'elm_id'	: "private_msg_" + msg_id,
					'msg_id'	: msg_id,
					'msg_date'	: msg_date,
					'msg_color'	: msg_color,
					'msg_text'	: msg_text,
					'login_from': login_from != CHAT_VARS["own_login"] ? this._add_private_link(login_from, msg_color) : login_from,
					'login_to'	: login_to != CHAT_VARS["own_login"] ? this._add_private_link(login_to, msg_color) : login_to
				});
				// Create div ID
				var elm_text_id = "chat_private_" + msg_id;
				var try_elm_obj	= PROFY_CHAT.INIT_MODE == "DIV_MODE" 
					? document.getElementById(elm_text_id) 
					: pf.document.getElementById(elm_text_id);
				// Check if such element already exists
				if (typeof try_elm_obj == "undefined" || !try_elm_obj) {
					new_elm_obj = this._create_elm(elm_text_id, pf);
					new_elm_obj.innerHTML = elm_inner_html;
					new_elm_obj.className = "msg_bg" + (this.private_num++%2 ? "1" : "2");
					new_elm_obj.style.background = msg_bg_color;
				}
			}
			this._scroll_to_end(pf);
			return true;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'private_add'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this.build_msg_divs			= function () {
		try {
			this._kill_all_msg_divs(CHAT_VARS["messages"], "chat_msg_", mf);
			this.messages_add(CHAT_VARS["messages"]);
			return true;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'build_msg_divs'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this.build_private_divs		= function () {
		try {
			this._kill_all_msg_divs(CHAT_VARS["private_msgs"], "chat_private_", pf);
			this.private_add(CHAT_VARS["private_msgs"]);
			return true;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'build_private_divs'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this.clear_msgs				= function () {
		this._kill_all_msg_divs(CHAT_VARS["messages"], "chat_msg_", mf);
		return true;
	}

	//---------------------------------------------------------------------
	this.clear_private			= function () {
		this._kill_all_msg_divs(CHAT_VARS["private_msgs"], "chat_private_", pf);
		return true;
	}

	//---------------------------------------------------------------------
	this._add_user_link		= function (user_login, text_color) {
		var output_text = "";
		if (user_login.length) {
			output_text = this._parse_tpl("add_user_link", {
				'onclick'	: (typeof pf == "object" && pf != null) ? "parent.window.PROFY_CHAT.send_to(\"" + user_login + "\")" : "",
				'text_color': text_color,
				'title'		: this.translate('Write_to'),
				'user_login': user_login
			});
		} else {
			output_text = this._parse_tpl("add_user_no_link", {
				'padding'	: CHAT_VARS["sys_msg_padding"]
			});
		}
		return output_text;
	}

	//---------------------------------------------------------------------
	this._add_private_link	= function (user_login, text_color) {
		var output_text = this._parse_tpl("add_private_link", {
			'onclick'	: (typeof pf != "undefined" && pf != null) ? "parent.window.PROFY_CHAT.private_to(\"" + user_login + "\");" : "",
			'text_color': text_color,
			'title'		: this.translate('Write_private'),
			'user_login': user_login
		});
		return output_text;
	}

	//---------------------------------------------------------------------
	this._kill_all_msg_divs		= function (elm_obj, prefix, frame_obj) {
		for (key in elm_obj) {
			this._remove_elm(prefix + elm_obj[key]["msg_id"], frame_obj);
		}
	}

	//-----------------------------------------------------------
	// POST FORM CONTENTS
	//-----------------------------------------------------------

	//---------------------------------------------------------------------
	this.post_form_init		= function () {
		try {
			// Init
			var f				= this._get_post_form_obj();
			var private_to_obj	= this._get_post_private_to_obj();
			var msg_obj			= this._get_post_msg_obj();
			// Process
			this._set_post_area_bg_color(CHAT_COLORS["post_form_def_bg"]);
			this._process_smilies();
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'post_form_init'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this._process_smilies	= function () {
		try {
			var s_path		= CHAT_VARS["path_to_smilies"];
			var s_array		= CHAT_VARS["smilies_array"];
			if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
				var s_area		= document.getElementById("chat_smilies_area");
			} else {
				var s_area		= ff.document.getElementById("chat_smilies_area");
			}
			var tmp_array	= new Array();
			var url_exists	= 0;
			if (s_array.length && s_path.length) {
				s_array.sort(PROFY_CHAT._sort_smilies);
				// Try to filter repeated images
				for (k1 in s_array) {
					url_exists = 0;
					// Try to find url in the new array
					for (k2 in tmp_array) {
						if (tmp_array[k2][2] == s_array[k1][2]) {
							url_exists = 1;
							break;
						}
					}
					// If no current url - then insert it
					if (url_exists == 0) {
						tmp_array[tmp_array.length] = s_array[k1];
					}
				}
				s_array = tmp_array;
				// Process smilies
				for (key in s_array) {
					var s_id		= s_array[key][0];
					var s_code		= s_array[key][1];
					var s_url		= s_array[key][2];
					var s_title		= s_array[key][3];
					if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
						var new_img		= document.createElement("IMG");
					} else {
						var new_img		= ff.document.createElement("IMG");
					}
					new_img.id		= "chat_smile_" + s_id;
					new_img.src		= s_path + s_url;
					new_img.title	= s_title;
					new_img.onclick	= new Function ("PROFY_CHAT._add_smile_code(\"" + s_code + "\")");
					new_img.style.margin = '2px';
					new_img.style.cursor = this.is_moz ? 'pointer' : 'hand';
					s_area.appendChild(new_img);
				}
			}
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_process_smilies'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this._add_smile_code	= function (s_code) {
		try {
			// Init
			var f				= this._get_post_form_obj();
			var private_to_obj	= this._get_post_private_to_obj();
			var msg_obj			= this._get_post_msg_obj();
			// Process
			msg_obj.focus();
			msg_obj.value = msg_obj.value + " " + s_code + " ";
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_add_smile_code'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this.chat_start_stop	= function () {
		try {
			if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
				var control_button = document.getElementById("chat_control_button");
			} else {
				var control_button = ff.document.getElementById("chat_control_button");
			}
			if (PROFY_CHAT.INTERVAL_ID) {
				window.clearInterval(PROFY_CHAT.INTERVAL_ID);
				PROFY_CHAT.INTERVAL_ID = false;
				alert(PROFY_CHAT.translate('CHAT_STOPPED!'));
				control_button.value = control_button.title = PROFY_CHAT.translate('Start_chat!');
				control_button.style.background = "green";
			} else {
				PROFY_CHAT._set_refresh_time();
				alert(PROFY_CHAT.translate('CHAT_STARTED!'));
				control_button.value = control_button.title = PROFY_CHAT.translate('Stop_chat!');
				control_button.style.background = "red";
			}
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'chat_start_stop'\n", "e", e);
		}
		return PROFY_CHAT.INTERVAL_ID;
	}

	//-----------------------------------------------------------
	this.private_to			= function (user_login) {
		try {
			// Init
			var f				= this._get_post_form_obj();
			var private_to_obj	= this._get_post_private_to_obj();
			var msg_obj			= this._get_post_msg_obj();
			// Process
			private_to_obj.value	= user_login;
			PROFY_CHAT.remember_nick= "";
			msg_obj.value			= "";
			msg_obj.focus();
			this._set_post_area_bg_color(CHAT_COLORS["post_form_def_mark"]);
			// Try to find user color
			for (key in CHAT_USERS) {
				if (user_login == CHAT_USERS[key]["user_login"]) {
					this._set_post_area_bg_color(CHAT_USERS[key]["text_color"]);
					break;
				}
			}
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'private_to'\n", "e", e);
		}
		return true;
	}

	//-----------------------------------------------------------
	this.send_to			= function (user_login) {
		try {
			// Init
			var f				= this._get_post_form_obj();
			var private_to_obj	= this._get_post_private_to_obj();
			var msg_obj			= this._get_post_msg_obj();
			// Process
			private_to_obj.value	= "";
			msg_obj.focus();
			msg_obj.value	= msg_obj.value + user_login + "> ";
			if (this.is_ie) {
				var msg_range = msg_obj.createTextRange();
				msg_range.moveEnd('sentence');
			}
			this._set_post_area_bg_color(CHAT_COLORS["post_form_def_bg"]);
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'send_to'\n", "e", e);
		}
		return true;
	}

	//-----------------------------------------------------------
	this.move_to			= function () {
		try {
			// Init
			var f				= this._get_post_form_obj();
			var private_to_obj	= this._get_post_private_to_obj();
			var msg_obj			= this._get_post_msg_obj();
			// Process
			if (private_to_obj.value != "") {
				msg_obj.value			= private_to_obj.value + "> ";
				private_to_obj.value	= "";
				msg_obj.focus();
				this._set_post_area_bg_color(CHAT_COLORS["post_form_def_bg"]);
			}
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'move_to'\n", "e", e);
		}
		return true;
	}

	//-----------------------------------------------------------
	this.reset_form			= function () {
		try {
			// Init
			var f				= this._get_post_form_obj();
			var private_to_obj	= this._get_post_private_to_obj();
			var msg_obj			= this._get_post_msg_obj();
			// Process
			if (private_to_obj.value == "") {
				this._set_post_area_bg_color(CHAT_COLORS["post_form_def_bg"]);
			}
			PROFY_CHAT.remember_nick	= '';
			msg_obj.value			= '';
			msg_obj.focus();
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'reset_form'\n", "e", e);
		}
		return true;
	}

	//-----------------------------------------------------------
	this.post_form_insert_bbcode		= function (bbcode_tag) {
		try {
			// Init
			var f				= this._get_post_form_obj();
			var private_to_obj	= this._get_post_private_to_obj();
			var msg_obj			= this._get_post_msg_obj();
			// Process
			var tag_1 = "[" + bbcode_tag + "]";
			var tag_2 = "[/" + bbcode_tag + "]";
			var txt_area = msg_obj;
			if (this.is_ie) {
				if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
					var the_selection = document.selection.createRange().text;
					if (the_selection) {
						document.selection.createRange().text = tag_1 + the_selection + tag_2;
						the_selection = '';
					} else {
						msg_obj.value += tag_1 + tag_2;
					}
				} else {
					var the_selection = ff.document.selection.createRange().text;
					if (the_selection) {
						ff.document.selection.createRange().text = tag_1 + the_selection + tag_2;
						the_selection = '';
					} else {
						msg_obj.value += tag_1 + tag_2;
					}
				}
			} else if (this.is_moz && txt_area.selectionEnd && (txt_area.selectionEnd - txt_area.selectionStart > 0)) {
				this._moz_wrap(txt_area, tag_1, tag_2);
			} else {
				msg_obj.value = msg_obj.value + tag_1 + tag_2;
			}
			this._store_caret(txt_area);
			txt_area.focus();
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'post_form_insert_bbcode'\n", "e", e);
		}
		return true;
	}

	//-----------------------------------------------------------
	this._store_caret			= function (textEl) {
		try {
			if (textEl.createTextRange) {
				if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
					textEl.caretPos = document.selection.createRange().duplicate();
				} else {
					textEl.caretPos = ff.document.selection.createRange().duplicate();
				}
			}
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_store_caret'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	// COMMON METHODS
	//---------------------------------------------------------------------

	//-----------------------------------------------------------
	this._get_post_form_obj		= function () {
		try {
			var f = null;
			if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
				f = document.getElementById("chat_post_form");
			} else {
				f = ff.document.forms[0];
			}
			// Check if we found form
			if (typeof f != "object" || f == null) {
				throw('Post form not found');
			}
			return f;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_get_post_form_obj'\n", "e", e);
		}
		return false;
	}

	//-----------------------------------------------------------
	this._get_post_msg_obj		= function () {
		try {
			var f		= this._get_post_form_obj();
			var msg_obj	= null;
			if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
				msg_obj	= document.getElementById("chat_post_msg");
			} else {
				msg_obj	= f.msg;
			}
			return msg_obj;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_get_post_msg_obj'\n", "e", e);
		}
		return false;
	}

	//-----------------------------------------------------------
	this._get_post_private_to_obj	= function () {
		try {
			var f				= this._get_post_form_obj();
			var private_to_obj	= null;
			if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
				private_to_obj	= document.getElementById("chat_post_private_to")
			} else {
				private_to_obj	= f.private_to;
			}
			return private_to_obj;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_get_post_private_to_obj'\n", "e", e);
		}
		return false;
	}

	//-----------------------------------------------------------
	this._set_post_area_bg_color	= function (new_color) {
		try {
			if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
				ff.style.background = new_color;
			} else {
				ff.document.body.style.background = new_color;
			}
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_set_post_area_bg_color'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this.do_log_out				= function () {
		parent.window.location = CHAT_VARS["logout_url"];
	}

	//---------------------------------------------------------------------
	// Set cyclic method to retrieve data from server
	this._set_refresh_time		= function () {
		if (PROFY_CHAT.INTERVAL_ID) {
			window.clearInterval(PROFY_CHAT.INTERVAL_ID);
		}
		PROFY_CHAT.INTERVAL_ID = window.setInterval("parent.window.PROFY_CHAT.get_commands()", CHAT_VARS["refresh"]);
		return true;
	}

	//---------------------------------------------------------------------
	// Create element in the DOM structure
	this._create_elm		= function (elm_id, parent_frame) {
		var new_elm = null;
		try {
			var p = null;
			if (typeof parent_frame == "object" && parent_frame != null) {
				p = parent_frame;
			} else {
				p = self.window;
			}
			if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
				new_elm = document.createElement("DIV");
			} else {
				new_elm = p.document.createElement("DIV");
			}
			new_elm.id = elm_id;
			if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
				p.appendChild(new_elm);
			} else {
				p.document.body.appendChild(new_elm);
			}
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_create_elm'\n", "e", e);
			return false;
		}
		return new_elm;
	}

	//---------------------------------------------------------------------
	// Remove element from the DOM structure
	this._remove_elm 		= function (elm_id, parent_frame, parent_elm_obj) {
		try {
			var p = null;
			if (typeof parent_frame == "object" && parent_frame != null) {
				p = parent_frame;
			} else {
				p = self.window;
			}
			// Default parent element obj
			if (parent_elm_obj == null || !parent_elm_obj) {
				if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
					parent_elm_obj = p;
				} else {
					parent_elm_obj = p.document.body;
				}
			}
			if (typeof parent_elm_obj != "object" || parent_elm_obj == null) {
				return false;
			}
			if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
				var obj_to_remove = document.getElementById(elm_id);
			} else {
				var obj_to_remove = p.document.getElementById(elm_id);
			}
			if (typeof obj_to_remove != "object" || obj_to_remove == null) {
				return false;
			}
			if (parent_elm_obj.hasChildNodes()) {
				try {
					parent_elm_obj.removeChild(obj_to_remove);
				} catch (e2) {
					// We do not not to see errors from here (it's faster than iterate within childNodes 
					// and check for element existance)
				}
			}
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_remove_elm'\n", "e", e);
			return false;
		}
		return true;
	}

	//---------------------------------------------------------------------
	// Scroll down frame contents
	this._scroll_to_end		= function (frame_id) {
		if (typeof frame_id != "object" || frame_id == null) {
			return false;
		}
		try {
			if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
// TODO
				throw ("'_scroll_to_end' not implemented in 'DIV_MODE' yet");
			} else {
				if (this.is_opera) {
					eval(frame_id.id + "." + "window.scroll(1, 100000)");
				} else if(this.is_moz) {
					frame_id.window.scrollTo(1, frame_id.window.innerHeight);
				} else if(this.is_ie) {
					frame_id.window.scrollTo(1, frame_id.document.body.scrollHeight);
				}
			}
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_scroll_to_end'\n", "e", e);
			return false;
		}
	}

	//---------------------------------------------------------------------
	// OTHER METHODS (independent from DOM structure (frames or divs mode))
	//---------------------------------------------------------------------

	//---------------------------------------------------------------------
	this.translate				= function (text) {
		text_l = text.toLowerCase();
		// Try to translate given text
		if (CHAT_LANG[text_l]) {
			text = CHAT_LANG[text_l];
		}
		return text;
	}

	//---------------------------------------------------------------------
	// X-browser XML structure extraction for commands
	this._get_cmd_params_from_xml_nodes = function (child_nodes, ATTRIB_NODE_NAME) {
		var my_array = [];
		// Process messages
		for (var key = 0; key < child_nodes.length; key++) {
			var cur_node	= this.is_ie ? child_nodes(key) : child_nodes[key];
			var msg_info	= [];
			// Skip all except text nodes
			if (cur_node.nodeType != 1) {
				continue;
			}
			if (cur_node.nodeName != ATTRIB_NODE_NAME) {
				continue;
			}
			// Process attributes
			for (var i = 0; i < cur_node.attributes.length; i++) {
				var cur_attrib = this.is_ie ? cur_node.attributes(i) : cur_node.attributes[i];
				if (cur_attrib.nodeType != 2) {
					continue;
				}
				var attrib_name		= cur_attrib.nodeName;
				var attrib_value	= cur_attrib.nodeValue;
				if (attrib_name.length == 0) {
					continue;
				}
				msg_info[attrib_name] = attrib_value;
			}
			my_array[msg_info.msg_id] = msg_info;
		}
		return my_array;
	}

	//---------------------------------------------------------------------
	// X-browser XML structure extraction for commands
	this._get_text_from_xml_node = function (child_nodes) {
		var cur_node	= this.is_ie ? child_nodes(0) : child_nodes[0];
		return cur_node.nodeValue;
	}

	//---------------------------------------------------------------------
	this._bbcode			= function (text) {
		try {
			var s_path	= CHAT_VARS["path_to_smilies"];
			var s_array	= CHAT_VARS["smilies_array"];
			var pattern;
			for (k in s_array) {
				var s_id		= s_array[k][0];
				var s_code		= s_array[k][1];
				var s_url		= s_array[k][2];
				var s_title		= s_array[k][3];
				eval ("pattern = /" + s_code.replace(/(\(|\)|\[|\]|\?|\-|\||\/)/ig, "\\$1") + "/ig;");
				var s_text		= "";
				if (CHAT_VARS["smilies_use_images"] == "1") {
					s_text = this._parse_tpl("smile_as_image", {
						'image_src'	: s_path + s_url,
						'title'		: s_title
					});
				} else {
					s_text = this._parse_tpl("smile_as_text", {
						'code'		: s_code,
						'title'		: s_title
					});
				}
				text = text.replace(pattern, s_text);
			}
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_bbcode'\n", "e", e);
		}
		return text = text.replace(/\[([\/]{0,1}[biu]{1})\]/ig,"<$1>");
	}

	//---------------------------------------------------------------------
	this._str_pad_left		= function (text, pad_symbol, pad_length) {
		text = text.toString();
		if (!pad_symbol) pad_symbol = "0";
		if (!pad_length) pad_length = 2;
		if (text.length < pad_length) for (i = 0; i < (pad_length - text.length); i++) text = pad_symbol + text.toString();
		return text;
	}

	//---------------------------------------------------------------------
	this._format_date 		= function (msg_date) {
		if (!msg_date) {
			return false;
		}
		var msg_day		= msg_date.getDate();
		var msg_month	= msg_date.getMonth();
		var msg_year	= msg_date.getFullYear();
		var msg_hours	= msg_date.getHours();
		var msg_minutes	= msg_date.getMinutes();
		var msg_seconds	= msg_date.getSeconds();
		var cur_date	= new Date(); // Current Date
		// Create string to show in box
		var add_date	= this._str_pad_left(msg_hours) + ":" + this._str_pad_left(msg_minutes);
		// If message posted not today - show full date
		if (cur_date.getDate() != msg_day || cur_date.getMonth() != msg_month || cur_date.getFullYear() != msg_year) {
			add_date	= this._str_pad_left(msg_day) + "/" + this._str_pad_left(msg_month + 1) + "/" + msg_year + " " + add_date;
		}
		// Add seconds if needed
		if (CHAT_VARS["user_msg_show_time"] == "1") {
			add_date += ":" + this._str_pad_left(msg_seconds);
		}
		var output_text = this._parse_tpl("msg_date", {
			'date_string'	: add_date
		});
		// Show time if needed
		return (CHAT_VARS["user_msg_show_time"] != "2") ? output_text : "";
	}

	//-----------------------------------------------------------
	this._moz_wrap				= function (txtarea, open, close) {
		var selLength	= txtarea.textLength;
		var selStart	= txtarea.selectionStart;
		var selEnd		= txtarea.selectionEnd;
		if (selEnd == 1 || selEnd == 2) {
			selEnd = selLength;
		}
		var s1 = (txtarea.value).substring(0,selStart);
		var s2 = (txtarea.value).substring(selStart, selEnd)
		var s3 = (txtarea.value).substring(selEnd, selLength);
		txtarea.value = s1 + open + s2 + close + s3;
		return;
	}

	//---------------------------------------------------------------------
	this._sort_smilies		= function (a, b) {
		var anew = a[2].toLowerCase();
		var bnew = b[2].toLowerCase();
		if (anew < bnew) {
			return 1;
		}
		if (anew > bnew) {
			return -1;
		}
		return 0;
	}

	//---------------------------------------------------------------------
	this._sort_by_gender_and_name	= function (a, b) {
		var anew = a["user_login"].toLowerCase();
		var bnew = b["user_login"].toLowerCase();
		// If gender is equal
		if (a["gender"] == b["gender"]) {
			if (anew < bnew) {
				return -1;
			}
			if (anew > bnew) {
				return 1;
			}
		} else {
			// Male first
			if (a["gender"] < b["gender"]) {
				return 1;
			}
			// Female last
			if (a["gender"] > b["gender"]) {
				return -1;
			}
		}
		return 0;
	}

	//---------------------------------------------------------------------
	// Parse internal template contents with given array
	this._parse_tpl	= function (tpl_name, tpl_values) {
		// Skip empty calls
		if (!tpl_name) {
			return false;
		}
		// Try to load template text
		var tpl_text = CHAT_HTML_TPLS[tpl_name];
		if (!tpl_text) {
			return false;
		}
		// Process values
		for (key in tpl_values) {
			tpl_text = tpl_text.replace('%%' + key + '%%', tpl_values[key]/*.replace(/%%/ig, '&#37;&#37;')*/);
		}
		return tpl_text;
	}

	//---------------------------------------------------------------------
	// Init debugging
	this.init_debug	= function () {
		PROFY_CHAT.dump_maxLevel = 4;
		// Add "repeat" method to the String object
		if (!String.prototype.repeat) {
			String.prototype.repeat = function(n) { 
				var s=this.toString(), ret=''; 
				while( (n--) > 0) ret+=s; 
				return ret; 
			}
		} 
		// Add "indent" method to the String object
		if (!String.prototype.indent) {
			String.prototype.indent = function(level, dontIndentFirst, indentChar) { 
				indentChar = indentChar || "\t"; 
				dontIndentFirst = Number(dontIndentFirst)||0; 
				var s = this.toString(); 
				s = s.split(/^/m); 
				for (var i=dontIndentFirst, l=s.length; i<l; i++) {
					s[i] = indentChar.repeat(level) + s[i]; 
				}
				return s.join("");
			} 
		}
	}

	//---------------------------------------------------------------------
	// Dump given element into human-readable format
	this.dump	= function (o, level) {
		level = level || 1;
		if (level > PROFY_CHAT.dump_maxLevel) {
			return "Too deep";
		}
		var ret = '';
		if (typeof(o) != 'function') {
			ret = typeof(o) + ': ';
		}
		if (typeof(o) == 'object') {
			ret += '\r\n';
			try {
				for (i in o) {
					try {
						ret += "\t".repeat(level) + i + ' => ' + (typeof(o[i]) == 'string' ? o[i] : PROFY_CHAT.dump(o[i], level+1)) + '\r\n';
					} catch (e) {
					}
				}
			} catch (e) {
				ret = "can\'t iterate over object";
			}
		} else {
			try {
				ret += o.toString().indent(level, 1);
			} catch (e) {
				ret += "can\'t convert to string";
			}
		}
		if (level) {
			return ret;
		}
	}

	//---------------------------------------------------------------------
	// Log debug method
	// log_level could be:
	// n|w|e (n == notice, w == warning, e == error) or	notice|warning|error
	//
	this.debug_log	= function (text_to_log, log_level, error_object) {
		if (typeof DEBUG_MODE == "undefined") {
			return false;
		}
		// Debug is turned off
		if (!DEBUG_MODE) {
			return false;
		}
		var _is_notice	= false;
		var _is_warning	= false;
		var _is_error	= false;
		// Default log_level
		if (typeof log_level == "undefined" || log_level == "" || log_level == 0) {
			log_level = "n";
		}
		if (log_level == "n" || log_level == "notice") {
			_is_notice	= true;
		}
		if (log_level == "w" || log_level == "warning") {
			_is_warning	= true;
		}
		if (log_level == "e" || log_level == "error") {
			_is_error	= true;
		}
		// Last level check
		if (!_is_notice && !_is_warning && !_is_error) {
			_is_notice = true;
		}
		try {
			var error_text = "";
			if (typeof error_object != "undefined") {
				error_text = error_object.toString() + "\n" + PROFY_CHAT.dump(error_object);
			}
			// Default log color
			var	_log_color	= CHAT_COLORS["log_default"];
			if (_is_notice) {
				_log_color	= CHAT_COLORS["log_notice"];
			}
			if (_is_warning) {
				_log_color	= CHAT_COLORS["log_warning"];
			}
			if (_is_error) {
				_log_color	= CHAT_COLORS["log_error"];
			}
			// Create log window if not done so yet
			if (typeof PROFY_CHAT._debug_win_id == "undefined") {
				PROFY_CHAT._debug_win_id = window.open(""/*"about:blank"/*"javascript://"*/, "debug_win"/*, "", 1*/);
				try {
					// Add empty page contents (with CSS)
					PROFY_CHAT._debug_win_id.document.write(CHAT_VARS["frame_blank_page"]);
				} catch (e1) {
					throw(e2);
				}
			}
			var cur_date = new Date();
			// Add log header to each record
			text_to_log = "<br>\n------------------ " + cur_date.toGMTString() + " ---------------<br>\n<span style='color:" + _log_color + ";'>" + (text_to_log + error_text).replace(/</ig, "&lt;").replace(/>/ig, "&gt;").replace(/\n/ig, "<br />") + "</span>";
			// Write debug info into new window
			try {
				PROFY_CHAT._debug_win_id.document.write(text_to_log);
			} catch (e2) {
				throw(e2);
			}
		} catch (e) {
			// alert(PROFY_CHAT.dump(e));
		}
	}

	//---------------------------------------------------------------------
	// Helper method
	this._get_tags			= function (tag_name, parent_frame) {
		var p = parent_frame ? parent_frame : self.window;
		// Default tag name to retrieve
		if (!tag_name) {
			tag_name = "DIV";
		}
		var tmp = "Tags \"" + tag_name + "\": \r\n\r\n";
		var tags_col = p.document.body.getElementsByTagName(tag_name);
		for (var i = 0; i < tags_col.length; i++) {
			tmp += "#" + (i + 1) + ":\t id=\"" + tags_col[i].id + "\"\t name=\"" + tags_col[i].name + "\"" + (i%2 ? "" : "\r\n");
		}
		return tmp;
	}


//////////// TODO METHODS /////////////////

	//---------------------------------------------------------------------
	this.user_ignore			= function (user_login) {
		try {
			throw ("'user_ignore' not implemented yet");
// TODO: convert
/*
		var user_id = 0;
		for (key in CHAT_USERS) {
			if (user_login == CHAT_USERS[key]["ignore_list"]) {
				user_id = CHAT_USERS[key]["user_id"];
			}
		}
		if (user_id) {
			cf.document.location.assign(CHAT_VARS["ignore_url"].replace(/%%id%%/i, user_id));
		}
		return true;
*/
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'user_ignore'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this.do_ban_user			= function (user_login) {
		try {
			throw ("'do_ban_user' not implemented yet");
// TODO: convert
/*
		var user_id = 0;
		for (key in CHAT_USERS) if (user_login == CHAT_USERS[key]["user_login"]) user_id = CHAT_USERS[key]["user_id"];
		if (user_id) {
			var minutes = parent.window.prompt(this.translate('ban_minutes'), 60);
			if (minutes) cf.document.location.assign(CHAT_VARS["ban_user_url"].replace(/%%id%%/i, user_id) + "&minutes=" + minutes);
// TODO: need to add processing of the minutes inside URL
			if (minutes) cf.document.location.assign(CHAT_VARS["ban_user_url"] + user_id + "&minutes=" + minutes);
		}
		return true;
*/
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'do_ban_user'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	this.do_ban_message			= function (msg_id) {
		try {
			throw ("'do_ban_message' not implemented yet");
// TODO: convert
//		if (msg_id) cf.document.location.assign(CHAT_VARS["ban_message_url"].replace(/%%id%%/i, msg_id));
//		return true;
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in 'do_ban_message'\n", "e", e);
		}
	}

	//---------------------------------------------------------------------
	// Copy styles from the main frame into the child ones
	this._copy_css_styles		= function () {
		if (PROFY_CHAT.INIT_MODE == "DIV_MODE") {
			return false;
		}
		// Only IE and Moz is supported
		if (!this.is_moz && !this.is_ie) {
			return false;
		}
		try {
// TODO
/*
			var sheets_col = parent.window.document.styleSheets;
			// First we try to find "profy_chat" stylesheet
			var tmp = "";

//alert(parent.window.document.styleSheets[0].imports);

			for (i = 0; i < sheets_col.length; i++) {
				if (sheets_col[i].ownerNode.tagName == "STYLE") {
//alert(sheets_col[i].imports);
					for (j = 0; j < sheets_col[i].cssRules.length; j++) {
						var k = 0;
//alert(sheets_col[i].cssRules[0].cssText);
						while (k < sheets_col[i].cssRules[j].length) {
//							tmp += "rule: " + sheets_col[i].cssRules[j].Index(k).type;
							k++;
						} 
					}
				}


//				if ( sheets_col[i].owningElement.tagName == "STYLE" ) {
//					for ( j = 0; j < document.styleSheets(i).imports.length; j++ )
//						tmp = "Imported style sheet " + j + " is at " +	document.styleSheets(i).imports(j).href;
//				}
			}

//			var sheets_col = parent.window.document.imports;

//alert(sheets_col);
//alert(tmp);

			var parent_css	= parent.window.document.styleSheets[0];
*/
			var frames_to_process = [mf,pf,uf];
			// Process frames
			for (var i = 0; i < frames_to_process.length; i++) {
				var cur_frame = frames_to_process[i];
				cur_frame.document.write(CHAT_VARS["frame_blank_page"]);
				cur_frame.document.close();
/*
				if (this.is_moz) {
					for (var j = 0; j < parent_css.cssRules.length; j++) {
						cur_frame.document.styleSheets[0].insertRule(parent_css.cssRules[j].cssText, j);
					}
				} else if (this.is_ie) {
					cur_frame.document.styleSheets[0].cssText = parent_css.cssText;
				}
*/
			}
		} catch (e) {
			// Logging...
			PROFY_CHAT.debug_log("Exception in '_copy_css_styles'\n", "e", e);
		}
	}


} // End of PROFY_CHAT class


function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}