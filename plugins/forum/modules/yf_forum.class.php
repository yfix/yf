<?php

/**
* Forums
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum {

	/** @var string @conf_skip Current forum version */
	public $VERSION				= '1.0rc1';
	/** @var array forum settings (default values) */
	var	$SETTINGS = array(
		// Most important options
		'USE_GLOBAL_USERS'		=> false,	// Use global user accounts or only forum internals
		'TOPIC_VIEW_TYPE'		=> 2,		// 1 = Tree view, 2 = Flat view
		'SHOW_TOTALS'			=> false,	// Show board totals block
		'ONLINE_USERS_STATS'	=> false, 	// Use online users tracking (stats)
		'POSTS_NEED_APPROVE'	=> false,	// Approve posts and topics creation on/off
		'ALLOW_GUESTS_POSTS'	=> false, 	// Allow unauthorized users make posts
		'POST_CUT_BAD_WORDS'	=> false, 	// Try to cut 'bad' words inside posts
		'USE_BAN_IP_FILTER'		=> false, 	// Check if user need to be blocked by IP
		'CONFIRM_REGISTER'		=> true,	// Confirm registration with email
		'SEND_NOTIFY_EMAILS'	=> false,	// Send emails to notify users about new posts
		'USE_CAPTCHA'			=> false, 	// Use CAPTCHA on some actions (register, forgot_pswd etc)
		'SEO_KEYWORDS'			=> false,	// Enable/disable search keywords
		'RECOGNIZE_SPIDERS'		=> false, 	// Recognition of well-known search engines
		'USE_READ_MESSAGES'		=> false,	// Track user read messages or not
		'READ_MSGS_DRIVER'		=> 'db',	// Driver selector for read messages tracker (db|cookies)
		'USE_OPTIMIZED_SQL'		=> true,	// Use optimized SQL for specific RDBMS when allowed (currently only MySQL >=4.1 supported)
		// Different numbers
		'NUM_TOPICS_ON_PAGE'	=> 20,		// Number of topics to show on one page
		'NUM_POSTS_ON_PAGE'		=> 15,		// Number of messages to show on one page
		'NUM_MEMBERS_ON_PAGE'	=> 10,		// Number of members to show on one page
		'NUM_NEW_POSTS'			=> 20,		// Number of new posts to show on one page
		'SUBJECT_TRIM'			=> 30,		// Number of symbols to trim in subject (on 'main page' and 'view_forum')
		'SUBJECT_WRAP'			=> 15,		// Number of symbols to wrap in subject (on 'main page' and 'view_forum')
		'MSG_TEXT_TRIM'			=> 65535,	// Number of symbols to trim comments, etc
		'LAST_POSTS_MAX_LENGTH'	=> 1000, 	// Max length of last posts (in new post form)
		'MIN_SEARCH_WORD'		=> 3, 		// Minimal search keyword length
		'MIN_USER_NAME'			=> 4,		// Min user name length
		'MAX_USER_NAME'			=> 32,		// Max user name length
		'REGISTRATION_TTL'		=> 86400,	// TTL of the register confirmation link (in seconds)
		'ANTISPAM_TIME'			=> 10,		// Min time between posts (posts from IP address with less time period are denied)
		'SESSION_EXPIRE_TIME'	=> 900,		// Session expiration (in seconds) if no activity detected
		'AVATAR_MAX_X'			=> 80,		// Avatar max width
		'AVATAR_MAX_Y'			=> 80,		// Avatar max height
		'AVATAR_MAX_BYTES'		=> 50000,	// Avatar max size
		'MAX_SIG_LENGTH'		=> 300,		// User signature max length
		'ATTACH_LIMIT_X'		=> 350,		// px
		'ATTACH_LIMIT_Y'		=> 1000,	// px
		'ATTACH_MAX_SIZE'		=> 100000,	// bytes
		// BB code options
		'BB_CODE'				=> false,	// BB Codes
		'ENABLE_SMILIES'		=> false,	// Smilies highlight status (obly when BB codes allowed)
		'SMILIES_IMAGES'		=> false,	// Show smilies images or CSS based boxes (works only if smilies are allowed)
		'SMILIES_SET'			=> 2,		// Smilies set to use
		'ENABLE_POST_ICONS'		=> false,	// Post icons on/off
		// Interface options
		'ALLOW_SKIN_CHANGE'		=> false, 	// Allow or not skins changing
		'ALLOW_LANG_CHANGE'		=> false, 	// Allow or not language changing
		'ALLOW_TOPICS_FILTER'	=> false, 	// Allow topics filter or not
		'ALLOW_FAST_JUMP_BOX'	=> false, 	// Allow fast jump box
		'ALLOW_SEARCH'			=> false, 	// Turn searching on/off
		'ALLOW_SEARCH_ALL_POSTS'=> true,	// Allow not to speciafy search keywords or user name (show all records)
		'ALLOW_ANNOUNCES'		=> false, 	// Turn anounces on/off
		'ALLOW_TRACK_TOPIC'		=> false, 	// Track topic feature on/off
		'ALLOW_TRACK_FORUM'		=> false, 	// Track forum feature on/off
		'ALLOW_EMAIL_TOPIC'		=> false, 	// Email topic feature on/off
		'ALLOW_PRINT_TOPIC'		=> false, 	// Print topic feature on/off
		'USE_FAST_REPLY'		=> false, 	// Allow ability to use fast reply form
		'USE_TOPIC_OPTIONS'		=> false, 	// Show topic options inline
		'HIDE_USERS_INFO'		=> false,	// Hide some links (for using as separate discussion boards for different objects)
		'SHOW_USER_RANKS'		=> false,	// Show user ranks
		'SHOW_USER_LEVEL'		=> false,	// Switch images-based user level on/off
		'SHOW_TOPIC_PAGES'		=> false,	// Show direct links to pages inside forum
		'SHOW_TOPIC_MOD_BOX'	=> false, 	// Show topic moderation options box
		'SHOW_HELP'				=> false,	// Display board help or not
		'SHOW_MEMBERS_LIST'		=> false,	// Display members list on/off
		'SHOW_EMPTY_CATS'		=> false,	// Show or not empty forum categories
		'ALLOW_CHANGE_TOPIC_VIEW'=> false,	// Topic view type changing
		'FAST_VIEW_REPLIERS'	=> true,	// Fast view who replied in selected topic (for view_forum)
		'FAST_TEXT_PREVIEW'		=> true,	// Fast text preview (for view_forum)
		'ALLOW_ATTACHES'		=> false, 	// Allow or not attach something to post
		'ALLOW_POLLS'			=> true, 	// Allow or not add poll to post
		'USE_SEO_LINKS'			=> false, 	// Prepare internal links for SEO (use names instead of ids where possible)
		'ALLOW_WYSIWYG_EDITOR'	=> false, 	// Try to use JavaScript-based text editor on editing posts
		// Date options
		'GLOBAL_TIME_OFFSET'	=> '0',		// Default time offset for the board (depends on where server is located)
		'DATE_FORMAT'			=> 'd/m/Y H:i:s',	// Format for PHP function date()
		'DATE_FORMAT_2'			=> 'D M d H:i:s Y', // Format for PHP function date()
		// RSS related
		'RSS_EXPORT'			=> false,	// Turn RSS export on/off
		'RSS_LATEST_IN_BOARD'	=> 15,		// Latest posts for feed in board
		'RSS_LATEST_IN_FORUM'	=> 15,		// Latest posts for feed in forum
		'RSS_LATEST_IN_TOPIC'	=> 15,		// Latest posts for feed in topic
		// Other options
		'AVATAR_IMAGE_TYPES'	=> 'gif,jpg,jpeg,png',	// Allowed avatar image types
		'ADMIN_EMAIL_FROM'		=> 'admin@example.com',	// Email from
		'SECRET_KEY'			=> '_#@%^&_secret_key',	// Secret key used for encryption data
		'AVATARS_DIR'			=> 'uploads/avatars/',	// Avatars folder
		'SMILIES_DIR'			=> 'uploads/forum/smilies/',	// Smilies folder
		'POST_ICONS_DIR'		=> 'uploads/forum/post_icons/',	// Folder for the post icons
		'ATTACHES_DIR'			=> 'uploads/forum/attaches/',	// Attaches to posts folder
		'_READ_MSGS_COOKIE'		=> '_forum_read', 		// Name of cookie for read messages
		'_READ_MSGS_TTL'		=> 864000, 				// 10 days, Time to live unread messages
	);
	/** @var array Access rights array (default values) */
	public $USER_RIGHTS = array(
		'is_admin'				=> false,
		'is_moderator'			=> false,
		'view_board'			=> true,
		'view_ip'				=> false,
		'view_member_info'		=> true,
		'view_other_topics'		=> true,
		'view_post_closed'		=> true,
		'post_new_topics'		=> true,
		'reply_own_topics'		=> true,
		'reply_other_topics'	=> true,
		'delete_own_topics'		=> false,
		'delete_other_topics'	=> false,
		'edit_own_topics'		=> false,
		'edit_other_topics'		=> false,
		'open_topics'			=> false,
		'close_topics'			=> false,
		'pin_topics'			=> false,
		'unpin_topics'			=> false,
		'move_topics'			=> false,
		'approve_topics'		=> false,
		'unapprove_topics'		=> false,
		'open_close_posts'		=> false,
		'delete_own_posts'		=> false,
		'delete_other_posts'	=> false,
		'edit_own_posts'		=> false,
		'edit_other_posts'		=> false,
		'move_posts'			=> false,
		'approve_posts'			=> false,
		'unapprove_posts'		=> false,
		'split_merge'			=> false,
		'edit_own_profile'		=> false,
		'edit_other_profile'	=> false,
		'hide_from_list'		=> false,
		'avatar_upload'			=> false,
		'use_search'			=> true,
		'use_pm'				=> false,
		'max_messages'			=> false,
		'email_friend'			=> false,
		'search_flood'			=> false,
		'make_polls'			=> false,
		'vote_polls'			=> false,
	);
	/** @var array Other vars @conf_skip */
	public $TOPIC_VIEW_TYPE = array(
		1	=> 'Standard',	// Default topic view
		2	=> 'Tree',		// Tree view
	);
	/** @var array Available forum user groups @conf_skip */
	public $FORUM_USER_GROUPS	= array(
		1	=> 'Administrator',
		2	=> 'Moderator',
		3	=> 'Member',
	);
	/** @var array CSS classes real names @conf_skip */
	public $_CSS = array(
		'show1'		=> 'forum1',
		'show2'		=> 'forum2',
		'quote'		=> 'forum_quote',
		'code'		=> 'forum_code',
		'smile'		=> 'forum_smile',
		'topic_a_1'	=> 'row1',
		'topic_a_2'	=> 'row2',
		'topic_u_1'	=> 'row2shaded',
		'topic_u_2'	=> 'row4shaded',
		'post_a_1'	=> 'post2',
		'post_u_1'	=> 'post2shaded',
	);
	/** @var array forum statuses @conf_skip */
	public $FORUM_STATUSES = array(
		0	=> array('images/forum/bf_nonew.gif',	'No New Posts'),
		1	=> array('images/forum/bf_new.gif',		'New Posts'),
		2	=> array('images/forum/bf_readonly.gif','Forum is Read-Only'),
	);
	/** @var array Topic statuses @conf_skip */
	public $TOPIC_STATUSES = array(
		0	=> array('images/forum/f_norm_no.gif',	'No New Posts'),
		1	=> array('images/forum/f_norm.gif',		'New Posts'),
		2	=> array('images/forum/f_hot_no.gif',	'No New Posts'),
		3	=> array('images/forum/f_hot.gif',		'New Posts'),
		4	=> array('images/forum/f_moved.gif',	'Moved'),
		5	=> array('images/forum/f_closed.gif',	'Closed'),
		6	=> array('images/forum/f_pinned.gif',	'Pinned'),
	);
	/** @var array Topic tree images array @conf_skip */
	public $TREE_IMAGES = array(
		1	=> '<img src=\'images/forum/to_post_no_children.gif\' />',
		2	=> '<img src=\'images/forum/to_post_with_children.gif\' />',
		3	=> '<img src=\'images/forum/to_down_pipe.gif\' /> ',
	);
	/** @var array Current user settings array @conf_skip */
	public $USER_SETTINGS = array();
	/** @var int */
	public $NUM_RSS 	= 10;

	/**
	* YF module constructor
	*/
	function _init () {
		$GLOBALS['no_page_header'] = true;
		define('FORUM_INTERNAL_CALL', intval($INTERNAL_CALL));
		// Set config vars (special name '_forum')
		foreach ((array)$GLOBALS['PROJECT_CONF']['_forum'] as $k => $v) {
			$this->SETTINGS[$k] = $v;
		}
		// Set default rights (special name '_forum_def_rights')
		foreach ((array)$GLOBALS['PROJECT_CONF']['_forum_def_rights'] as $k => $v) {
			$this->USER_RIGHTS[$k] = $v;
		}
		define('FORUM_AUTH_MODULE', $this->SETTINGS['USE_GLOBAL_USERS'] ? 'forum_auth_global' : 'forum_auth');
		if (isset($_SESSION['board_topic_view']) && in_array($_SESSION['board_topic_view'], array(1,2))) {
			$this->SETTINGS['TOPIC_VIEW_TYPE'] = intval($_SESSION['board_topic_view']);
		}
		$GLOBALS['PROJECT_CONF']['bb_codes']['SMILIES_DIR']	= module('forum')->SETTINGS['SMILIES_DIR'];
		$this->_forum_groups		= main()->get_data('forum_groups');
		$this->_forum_moderators	= main()->get_data('forum_moderators');
		$this->_load_sub_module(FORUM_AUTH_MODULE)->_verify_session_vars();
		$this->_forum_cats_array	= main()->get_data('forum_categories');
		$this->_forums_array		= main()->get_data('forum_forums');
		// Hide inactive forums and categories
		foreach ((array)$this->_forum_cats_array as $_cat_id => $_cat_info) {
			if ($_cat_info['status'] != 'a') {
				unset($this->_forum_cats_array[$_cat_id]);
			}
		}
		foreach ((array)$this->_forums_array as $_forum_id => $_forum_info) {
			if ($_forum_info['status'] != 'a') {
				unset($this->_forums_array[$_forum_id]);
			}
		}
		if ($this->SETTINGS['ALLOW_SKIN_CHANGE']) {
			$this->_skins_array		= main()->get_data('user_skins');
		}
		if ($this->SETTINGS['USE_CAPTCHA'] && in_array($_GET['action'], array('show_captcha_image','register','send_password'))) {
			$this->CAPTCHA = _class('captcha');
		}
		$this->_init_read_messages();
	}

	/**
	* Catch _ANY_ call to the class methods (yf special hook)
	*/
/*
	function _module_action_handler ($called_action) {
		$body = $this->$called_action();
		return $body;
	}
*/

	/**
	* Show forum layout (default function)
	*/
	function show () {
		return $this->_load_sub_module('forum_view_home')->_show_main();
	}

	/**
	* View forum contents
	*/
	function view_forum () {
		return $this->_load_sub_module('forum_view_forum')->_show_main();
	}

	/**
	* View topic contents
	*/
	function view_topic () {
		$submodule_name = 'forum_view_topic_'.($this->SETTINGS['TOPIC_VIEW_TYPE'] == 1 ? 'tree' : 'flat');
		return $this->_load_sub_module($submodule_name)->_show_main();
	}

	/**
	* View single post
	*/
	function view_post () {
		$GLOBALS['show_only_post_id'] = true;
		$this->SETTINGS['TOPIC_VIEW_TYPE'] = 2;

		$submodule_name = 'forum_view_topic_'.($this->SETTINGS['TOPIC_VIEW_TYPE'] == 1 ? 'tree' : 'flat');
		return $this->_load_sub_module($submodule_name)->_show_main();
	}

	/**
	* View single anounce
	*/
	function view_announce () {
		return $this->_load_sub_module('forum_announce')->_view_announce();
	}

	/**
	* View forum statistics
	*/
	function view_stats () {
		return $this->_load_sub_module('forum_online_users')->_view_stats();
	}

	/**
	* Members list (with search)
	*/
	function view_members () {
		return $this->_load_sub_module('forum_members')->_show_main();
	}

	/**
	* View user's profile
	*/
	function view_profile () {
		return $this->_load_sub_module('forum_user')->_view_profile();
	}

	/**
	* View latest posts
	*/
	function view_new_posts () {
		return $this->_load_sub_module('forum_search')->_view_new_posts();
	}

	/**
	* Alias for the 'view_new_posts'
	*/
	function latest () {
		return $this->view_new_posts();
	}

	/**
	* View unread messages groupped by topics
	*/
	function unread () {
		return $this->_load_sub_module('forum_search')->_view_unread_topics();
	}

	/**
	* Searching current forum
	*/
	function search () {
		// Check CPU Load
		if (conf('HIGH_CPU_LOAD') == 1) {
			return common()->server_is_busy();
		}
		return $this->_load_sub_module('forum_search')->_show_main();
	}

	/**
	* Log in function
	*/
	function login () {
		return $this->_load_sub_module('forum_auth')->_login();
	}

	/**
	* Log out function
	*/
	function logout () {
		return $this->_load_sub_module('forum_auth')->_logout();
	}

	/**
	* Retrieve forgotten password
	*/
	function send_password () {
		return $this->_load_sub_module('forum_user')->_send_password();
	}

	/**
	* Registration
	*/
	function register () {
		return $this->_load_sub_module('forum_user')->_register();
	}

	/**
	* Confirm registration function
	*/
	function confirm_register () {
		return $this->_load_sub_module('forum_user')->_confirm_register();
	}

	/**
	* Forum help
	*/
	function help () {
		return $this->_load_sub_module('forum_help')->_show_main();
	}

	/**
	* Contact with admin form
	*/
	function contact_admin () {
		return $this->_load_sub_module('forum_help')->_contact_admin();
	}

	/**
	* Help on bb code
	*/
	function bb_code_help () {
		return $this->_load_sub_module('forum_help')->_bb_code_help();
	}

	/**
	* New topic creation form
	*/
	function new_topic () {
		return $this->_load_sub_module('forum_post')->_new_topic();
	}

	/**
	* Add new post item
	*/
	function new_poll () {
		return $this->_load_sub_module('forum_post')->_new_poll();
	}

	/**
	* Reply to the existing topic (post message)
	*/
	function reply () {
		return $this->_load_sub_module('forum_post')->_reply();
	}

	/**
	* Reply to the existing topic (post message)
	*/
	function reply_no_quote () {
		$GLOBALS['_forum_reply_no_quote'] = true;
		return $this->_load_sub_module('forum_post')->_reply();
	}

	/**
	* Add new post item
	*/
	function new_post () {
		return $this->_load_sub_module('forum_post')->_new_post();
	}

	/**
	* Edit post
	*/
	function edit_post () {
		return $this->_load_sub_module('forum_post')->_edit_post();
	}

	/**
	* Delete post
	*/
	function delete_post ($SILENT_MODE = false, $_FORCE_ID = 0) {
		return $this->_load_sub_module('forum_post')->_delete_post($SILENT_MODE, $_FORCE_ID);
	}

	/**
	* Save new post
	*/
	function save_post () {
		return $this->_load_sub_module('forum_post')->_save_post();
	}

	/**
	* Edit personal info
	*/
	function edit_profile () {
		return $this->_load_sub_module('forum_user')->_edit_profile();
	}

	/**
	* Delete user profile
	*/
	function delete_profile () {
		return $this->_load_sub_module('forum_user')->_delete_profile();
	}

	/**
	* Edit personal board settings
	*/
	function settings () {
		return $this->_load_sub_module('forum_user')->_edit_settings();
	}

	/**
	* Alias for 'settings'
	*/
	function edit_settings () {
		return $this->settings();
	}

	/**
	* Subscribe to the forum
	*/
	function subscribe_forum () {
		return $this->_load_sub_module('forum_tracker')->_subscribe_forum();
	}

	/**
	* Subscribe to the topic
	*/
	function subscribe_topic () {
		return $this->_load_sub_module('forum_tracker')->_subscribe_topic();
	}

	/**
	* Manage forums subscriptions
	*/
	function tracker_manage_forums () {
		return $this->_load_sub_module('forum_tracker')->_manage_forums();
	}

	/**
	* Manage topics subscriptions
	*/
	function tracker_manage_topics () {
		return $this->_load_sub_module('forum_tracker')->_manage_topics();
	}

	/**
	* View printable version of the topic
	*/
	function print_topic () {
		return $this->_load_sub_module('forum_print')->_show_topic();
	}

	/**
	* View light forum version
	*/
	function low () {
		return $this->_load_sub_module('forum_low')->_show_main();
	}

	/**
	* Delete cookies set by this forum
	*/
	function del_cookies () {
		return $this->_load_sub_module('forum_auth')->_del_cookies();
	}

	/**
	* Mark all messages read
	*/
	function mark_read () {
		return $this->_load_sub_module('forum_read')->_mark_read();
	}

	/**
	* Send topic by email
	*/
	function email_topic () {
		return $this->_load_sub_module('forum_utils')->_email_topic();
	}

	/**
	* Report post
	*/
	function report_post () {
		return $this->_load_sub_module('forum_utils')->_report_post();
	}

	/**
	* View Reports
	*/
	function view_reports () {
		return $this->_load_sub_module('forum_utils')->_view_reports();
	}

	/**
	* Close report
	*/
	function close_reports () {
		return $this->_load_sub_module('forum_utils')->_close_reports();
	}

	/**
	* Forum user control panel
	*/
	function user_cp () {
		return $this->_load_sub_module('forum_user')->_user_cp();
	}

	/**
	* Send email to the user
	*/
	function email_user () {
		return $this->_load_sub_module('forum_user')->_email_user();
	}

	/**
	* Delete avatar
	*/
	function delete_avatar () {
		return $this->_load_sub_module('forum_user')->_delete_avatar();
	}

	/**
	* Site jump display box
	*/
	function site_jump () {
		$new_action = $new_id = '';
		if (!empty($_POST['fast_nav'])) {
			// Check posted data
			if (is_numeric($_POST['fast_nav'])) {
				$new_action = 'view_forum';
				$new_id		= intval($_POST['fast_nav']);
			} elseif (false !== strpos($_POST['fast_nav'], 'cat_')) {
				$new_action = 'show';
				$new_id		= substr($_POST['fast_nav'], 4);
			} elseif ($_POST['fast_nav'] == 'sj_home') {
				$new_action = 'show';
			} elseif ($_POST['fast_nav'] == 'sj_search') {
				$new_action = 'search';
			} elseif ($_POST['fast_nav'] == 'sj_help') {
				$new_action = 'help';
			}
		}
		if (!empty($new_action)) {
			return js_redirect('./?object='.'forum'.($new_action != 'home' ? '&action='.$new_action : '').(!empty($new_id) ? '&id='.$new_id : ''));
		}
		// Default redirect back
		return js_redirect($_SERVER['HTTP_REFERER'], false);
	}

	/**
	* Change language
	*/
	function change_lang () {
		return $this->_load_sub_module('forum_utils')->_change_lang();
	}

	/**
	* Change skin
	*/
	function change_skin () {
		return $this->_load_sub_module('forum_utils')->_change_skin();
	}

	/**
	* Change topic view
	*/
	function change_topic_view () {
		return $this->_load_sub_module('forum_utils')->_change_topic_view();
	}

	/**
	* Administration control panel
	*/
	function admin () {
		return $this->_load_sub_module('forum_admin')->_show_main();
	}

	/**
	* Board synchronization
	*/
	function sync_board () {
		return $this->_load_sub_module('forum_sync')->_sync_board();
	}

	/**
	* Forum synchronization
	*/
	function sync_forum () {
		return $this->_load_sub_module('forum_sync')->_sync_forum();
	}

	/**
	* Edit announces
	*/
	function edit_announces () {
		return $this->_load_sub_module('forum_announce')->_edit_main();
	}

	/**
	* Show captcha image
	*/
	function show_captcha_image() {
		if ($this->SETTINGS['USE_CAPTCHA'] && is_object($this->CAPTCHA)) {
			$this->CAPTCHA->show_image();
		}
	}

	/**
	* Display RSS feed for whole board
	*/
	function rss_board() {
		return $this->_load_sub_module('forum_rss')->_display_for_board();
	}

	/**
	* Display RSS feed for given forum
	*/
	function rss_forum() {
		return $this->_load_sub_module('forum_rss')->_display_for_forum();
	}

	/**
	* Display RSS feed for given topic
	*/
	function rss_topic() {
		return $this->_load_sub_module('forum_rss')->_display_for_topic();
	}

	/**
	* Compact topic repliers view (usually for popup or for ajax)
	*/
	function compact_topic_repliers () {
		return $this->_load_sub_module('forum_compact_view')->_topic_repliers();
	}

	/**
	* Compact post preview (usually for popup or for ajax)
	*/
	function compact_post_preview () {
		return $this->_load_sub_module('forum_compact_view')->_post();
	}

	/**
	* Delete attached file
	*/
	function delete_attach () {
		return $this->_load_sub_module('forum_post')->_delete_attach();
	}

	/**
	* Do vote in poll
	*/
	function poll_vote () {
		if (!module('forum')->SETTINGS['ALLOW_POLLS']) {
			return $this->_show_error('Polls are disabled');
		}
		if (!module('forum')->USER_RIGHTS['vote_polls']) {
			return $this->_show_error('You are not allowed to vote in polls');
		}
		$return_path = './?object='.$_GET['object'].'&action=view_topic&id='.$_GET['id'];
		module_safe('poll')->show(array(
			'silent'		=> 1,
			'object_name'	=> $_GET['object'],
			'object_id'		=> $_GET['id'],
			'return_path'	=> $return_path,
			'stpl_main'		=> $_GET['object'].'/poll_vote',
			'stpl_view'		=> $_GET['object'].'/poll_results',
		));
		return js_redirect($return_path);
	}

	/**
	* Display poll results
	*/
	function poll_results () {
		$GLOBALS['POLL_ONLY_RESULTS'] = true;
		return $this->_load_sub_module('forum_view_topic_flat')->_show_main();
	}

	/* ####### PRIVATE METHODS START ###### */

	/**
	* Display image button for RSS feed
	*/
	function _show_rss_link($feed_link = '', $feed_name = '') {
		// Do not show export links if turned off
		if (empty($this->SETTINGS['RSS_EXPORT'])) {
			return '';
		}
		// Process template
		$replace = array(
			'feed_link'	=> $feed_link,
			'feed_name'	=> _prepare_html($feed_name),
		);
		return tpl()->parse('system/xml_button', $replace);
	}

	/**
	* Process main template
	*/
	function _show_main_tpl($items = '') {
		return $this->_load_sub_module('forum_main_tpl')->_show_main_tpl($items);
	}

	/**
	* Show board fast navigation box
	*/
	function _board_fast_nav_box () {
		return $this->_load_sub_module('forum_fast_nav')->_board_fast_nav_box();
	}

	/**
	* Show user info in post
	*/
	function _show_user_details($user_info = array(), $is_online = 0, $post_user_name = '', $post_id = 0) {
		$submodule_name = $this->SETTINGS['USE_GLOBAL_USERS'] ? 'forum_user_details_global' : 'forum_user_details';
		return $this->_load_sub_module($submodule_name)->_show_user_details($user_info, $is_online, $post_user_name, $post_id);
	}

	/**
	* Get users infos
	*/
	function _get_users_infos($users_ids = array(), $params = array()) {
		$submodule_name = $this->SETTINGS['USE_GLOBAL_USERS'] ? 'forum_user_details_global' : 'forum_user_details';
		return $this->_load_sub_module($submodule_name)->_get_users_infos($users_ids, $params);
	}

	/**
	* Cut BB Codes from the given text
	*/
	function _cut_bb_codes ($body = '') {
		return preg_replace('/\[[^\]]+\]/ims', '', $body);
	}

	/**
	* Display user's email
	*/
	function _display_email ($email = '', $add_mailto = 1) {
		if (!empty($email))	{
			$parts = explode('@', $email);
		}
		return (is_array($parts) && count($parts) == 2) ? "<script>document.write(".($add_mailto ? "'mai' +'lto:' +" : "")."'".$parts[0]."'+'@' + '".$parts[1]."')</script>" : $email;
	}

	/**
	* Display link to the user's profile
	*/
	function _user_profile_link ($user_id = 0, $force_forum_link = 0) {
		if (empty($user_id) || $this->SETTINGS['HIDE_USERS_INFO']) {
			return '';
		}
		return ($this->SETTINGS['USE_GLOBAL_USERS'] && !$force_forum_link ? _profile_link($user_id) : './?object='.'forum'.'&action=view_profile&id='.$user_id);
	}

	/**
	* Display formatted date
	*/
	function _show_date ($input_date = '', $place = '') {
		if (empty($input_date)) {
			$input_date = time();
		}
		$date_to_show = $input_date + ($this->SETTINGS['GLOBAL_TIME_OFFSET'] * 3600) + (FORUM_USER_TIME_ZONE * 3600);
		// Different date formats
		if ($place == 'footer') {
			$date_format = $this->SETTINGS['DATE_FORMAT_2'];
			$use_add_text = false;
		} else {
			$date_format = $this->SETTINGS['DATE_FORMAT'];
			$use_add_text = true;
		}
		// Enable today and yesterday recognition
		$today_starts		= strtotime(date('Y-m-d'));
		$yesterday_starts	= $today_starts - 24*3600;
		$yesterday_ends		= $today_starts - 1;
		// Show formatted date
		if ($date_to_show >= $today_starts && $use_add_text) {
			$output = t('Today').', '.date('H:i', $date_to_show);
		} elseif ($date_to_show >= $yesterday_starts && $date_to_show < $yesterday_ends && $use_add_text) {
			$output = t('Yesterday').', '.date('H:i', $date_to_show);
		} else {
			$output = date($date_format, $date_to_show);
		}
		return $output;
	}

	/**
	* Show error message
	*/
	function _show_error($text = '', $use_main_tpl = 1) {
		if (module('forum')->SETTINGS['USE_GLOBAL_USERS']) {
			$body = _e($text);
			return $use_main_tpl ? $this->_show_main_tpl($body) : $body;
		}
		if (!strlen($text)) {
			$text = t('Unknown error');
		} elseif (common()->_error_exists()) {
			$text = _e();
		}
		$admin_email = explode('@', $this->SETTINGS['ADMIN_EMAIL_FROM']);
		$replace = array(
			'text'				=> $text,
			'is_logged_in'		=> intval(FORUM_USER_ID),
			'back_url'			=> $this->USER_RIGHTS['view_board'] && !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
			'form_action'		=> './?object='.'forum'.'&action=login',
			'forgot_pswd_link'	=> './?object='.'forum'.'&action=send_password',
			'register_link'		=> './?object='.'forum'.'&action=register',
			'help_link'			=> './?object='.'forum'.'&action=help',
			'contact_admin_link'=> './?object='.'forum'.'&action=contact_admin',
			'admin_email_1'		=> $admin_email[0],
			'admin_email_2'		=> $admin_email[1],
			'show_login_form'	=> !FORUM_USER_ID && $this->USER_RIGHTS['view_board'],
			'show_useful_links'	=> $this->USER_RIGHTS['view_board'],
		);
		$body = tpl()->parse('forum'.'/errors_main', $replace);
		return $use_main_tpl ? $this->_show_main_tpl($body) : $body;
	}

	/**
	* Try to merge moderator rights with current ones
	*/
	function _apply_moderator_rights () {
		if (!FORUM_IS_MODERATOR) {
			return false;
		}
		if (!in_array($_GET['action'], array('view_forum','view_topic','view_post','admin'))) {
			return false;
		}
		// Try to find current moderator rights
		foreach ((array)$this->_forum_moderators as $m_id => $m_info) {
			if (FORUM_USER_ID == $m_info['member_id']) {
				$cur_moderator_info = $m_info;
				break;
			}
		}
		if (empty($cur_moderator_info)) {
			return false;
		}
		$cur_moderator_info['forums_array'] = explode(',', $cur_moderator_info['forums_list']);
		$CORRECT_FORUM = false;
		// Try to find current forum
		if ($_GET['action'] == 'view_forum' && in_array($_GET['id'], $cur_moderator_info['forums_array'])) {
			$CORRECT_FORUM = true;
		} elseif (in_array($this->FORUM_OBJ->_topic_info['forum'], $cur_moderator_info['forums_array'])) {
			$CORRECT_FORUM = true;
		}
		if (!$CORRECT_FORUM) {
			return false;
		}
		// Do merge rights
		foreach ((array)$this->USER_RIGHTS as $rights_key => $rights_value) {
			if (!isset($cur_moderator_info[$rights_key])) {
				continue;
			}
			module('forum')->USER_RIGHTS[$rights_key] = $cur_moderator_info[$rights_key];
		}
	}

	/**
	* Try to load forum sub_module
	*/
	function _load_sub_module ($module_name = '') {
		return _class($module_name, 'modules/forum/');
	}

	/**
	* Init read messages info
	*/
	function _init_read_messages() {
		return $this->_load_sub_module('forum_read')->_init_read_messages();
	}

	/**
	* Set topic read (if needed)
	*/
	function _set_topic_read ($topic_info = array()) {
		return $this->_load_sub_module('forum_read')->_set_topic_read ($topic_info);
	}

	/**
	* Get topic is read status (if needed)
	*/
	function _get_topic_read ($topic_info = array()) {
		return $this->_load_sub_module('forum_read')->_get_topic_read ($topic_info);
	}

	/**
	* Get forum is read status (if needed)
	*/
	function _get_forum_read ($forum_info = array()) {
		return $this->_load_sub_module('forum_read')->_get_forum_read ($forum_info);
	}

	/**
	* Get array of sub forums
	*/
	function _get_sub_forums_ids ($parent_id = 0, $only_first_level = false) {
		$sub_ids = array();
		if (empty($parent_id)) {
			return $sub_ids;
		}
		foreach ((array)$this->_forums_array as $_info) {
			if ($_info['parent'] != $parent_id) {
				continue;
			}
			$sub_ids[$_info['id']] = $_info['id'];
			if (!$only_first_level) {
				$sub_ids = array_merge($sub_ids, (array)$this->_get_sub_forums_ids($_info['id']));
			}
		}
		return $sub_ids;
	}

	/**
	* Get array of parent forums
	*/
	function _get_parent_forums_ids ($cur_id = 0, $level = 0) {
		$forums_ids = array();
		if (empty($cur_id) || empty($this->_forums_array[$cur_id])) {
			return $forums_ids;
		}
		foreach ((array)$this->_get_parent_forums_ids($this->_forums_array[$cur_id]['parent'], $level + 1) as $_parent_id) {
			$forums_ids[$_parent_id] = $_parent_id;
		}
		if ($level > 0) {
			$forums_ids[$cur_id] = $cur_id;
		}
		return $forums_ids;
	}

	/**
	* Create forum profile record automatically if needed (only for the forum_global mode)
	*/
	function _start_forum_account ($user_id = 0) {
		if (!module('forum')->SETTINGS['USE_GLOBAL_USERS']) {
			return false;
		}
		return $this->_load_sub_module('forum_user_details_global')->_start_forum_account($user_id);
	}

	/**
	* Return path to the user's attach images
	*/
	function _get_attach_path ($post_id = 0) {
		return module('forum')->SETTINGS['ATTACHES_DIR']. _gen_dir_path($post_id, '', 0). $post_id.'.jpg';
	}

	/**
	* Check if current moderator allowed inside current forum
	*/
	function _moderate_forum_allowed ($forum_id = 0) {
		if (FORUM_IS_ADMIN) {
			return true;
		}
		if (!FORUM_IS_MODERATOR || empty($forum_id)) {
			return false;
		}
		// Try to find current moderator rights
		foreach ((array)$this->_forum_moderators as $m_id => $m_info) {
			if (FORUM_USER_ID == $m_info['member_id']) {
				$cur_moderator_info = $m_info;
				break;
			}
		}
		if (empty($cur_moderator_info)) {
			return false;
		}
		$allowed_forums_ids = explode(',', $cur_moderator_info['forums_list']);
		if (in_array($forum_id, $allowed_forums_ids)) {
			return true;
		}
		return false;
	}

	/**
	* Prepare link to the forum
	*/
	function _link_to_forum ($forum_id = 0) {
		if (module('forum')->SETTINGS['USE_SEO_LINKS']) {
			$forum_name = $this->_forums_array[$forum_id]['name'];
			$forum_name = preg_replace('/[^a-z0-9\-\_]/ims', '_', strtolower($forum_name));
		}
		return './?object='.'forum'.'&action=view_forum&id='.(!empty($forum_name) ? $forum_name : $forum_id);
	}

	/**
	* Display Wysiwyg editor code
	*/
	function _show_wysiwyg_editor ($text = '') {
		if (!module('forum')->SETTINGS['ALLOW_WYSIWYG_EDITOR']) {
			return false;
		}
		return _class_safe('text_editor')->_display_code($text, 'text2', 'bbcode');
	}

	/**
	* Prepare post subject for display in last post blocks
	*/
	function _cut_subject_for_last_post ($subject = '') {
		$subject = module('forum')->_cut_bb_codes($subject);
		if (_strlen($subject) > module('forum')->SETTINGS['SUBJECT_TRIM']) {
			$subject = _substr($subject, 0, module('forum')->SETTINGS['SUBJECT_TRIM']).'...';
		}
		$subject = _wordwrap($subject, module('forum')->SETTINGS['SUBJECT_WRAP'], ' ', true);
		return $subject;
	}

	/**
	* Hook for the site_map
	*/
	function _site_map_items ($SITE_MAP_OBJ = false) {
		return $this->_load_sub_module('forum_integration')->_site_map_items($SITE_MAP_OBJ);
	}

	/**
	* Hook for navigation bar
	*/
	function _nav_bar_items ($params = array()) {
		return $this->_load_sub_module('forum_integration')->_nav_bar_items($params);
	}

	/**
	* Integration into home page
	*/
	function _for_home_page($NUM_NEWEST_FORUM_POSTS = 4, $NEWEST_FORUM_TEXT_LEN = 100, $params = array()) {
		return $this->_load_sub_module('forum_integration')->_for_home_page($NUM_NEWEST_FORUM_POSTS, $NEWEST_FORUM_TEXT_LEN, $params);
	}

	/**
	* Integration into user profile
	*/
	function _for_user_profile($user_id, $MAX_SHOW_FORUM_POSTS = 10) {
		return $this->_load_sub_module('forum_integration')->_for_user_profile($user_id, $MAX_SHOW_FORUM_POSTS);
	}
	
	/**
	* Forum last post
	*/
	function _widget_last_post ($params = array()) {
		if ($params['describe']) {
			return array('allow_cache' => 1, 'cache_ttl' => 300);
		}
		return $this->_for_home_page(1, 100, array('for_widgets' => 1));
	}
	
	/**
	* Forum last posts
	*/
	function _widget_last_posts ($params = array()) {
		if ($params['describe']) {
			return array('allow_cache' => 1, 'cache_ttl' => 300);
		}
		return $this->_for_home_page(4, 100, array('for_widgets' => 1));
	}
	
	/**
	* General rss
	*/
	function _rss_general() {
		return $this->_load_sub_module('forum_integration')->_rss_general();
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				'name'	=> 'Main',
				'url'	=> './?object='.$_GET['object'],
			),
			array(
				'name'	=> 'Forum settings',
				'url'	=> './?object='.$_GET['object'].'&action=user_cp',
			),
			array(
				'name'	=> FORUM_IS_ADMIN ? 'View reported posts' : '',
				'url'	=> FORUM_IS_ADMIN ? './?object='.$_GET['object'].'&action=view_reports' : '',
			),
			array(
				'name'	=> FORUM_IS_ADMIN ? 'Resynchronize Board' : '',
				'url'	=> FORUM_IS_ADMIN ? './?object='.$_GET['object'].'&action=sync_board' : '',
			),
		);
		return $menu;	
	}
	
	/**
	* Unread hook
	*/
	function _unread () {
		$link = process_url('./?object=forum&action=unread');
		$unread = array(
			'count'	=> '',
			'link'	=> $link,
		);
		return $unread;
	}
}
