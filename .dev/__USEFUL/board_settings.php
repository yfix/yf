<? die('go away!');
// General board settings
$this->SETTINGS = array_merge($this->SETTINGS, array(
	// Most important options
	"USE_GLOBAL_USERS"		=> 1,		// Use global user accounts or only forum internals
	"TOPIC_VIEW_TYPE"		=> 2,		// 1 = Tree view, 2 = Flat view
	"SHOW_TOTALS"			=> 0,		// Show board totals block
	"ONLINE_USERS_STATS"	=> 0, 		// Use online users tracking (stats)
	"POSTS_NEED_APPROVE"	=> 0,		// Approve posts and topics creation on/off
	"ALLOW_GUESTS_POSTS"	=> 0, 		// Allow unauthorized users make posts
	"POST_CUT_BAD_WORDS"	=> 0, 		// Try to cut "bad" words inside posts
	"USE_BAN_IP_FILTER"		=> 0, 		// Check if user need to be blocked by IP
	"CONFIRM_REGISTER"		=> 1,		// Confirm registration with email
	"SEND_NOTIFY_EMAILS"	=> 0,		// Send emails to notify users about new posts
	"USE_CAPTCHA"			=> 0, 		// Use CAPTCHA on some actions (register, forgot_pswd etc)
	"SEO_KEYWORDS"			=> 0,		// Enable/disable search keywords
	"RECOGNIZE_SPIDERS"		=> 1, 		// Recognition of well-known search engines
	"USE_READ_MESSAGES"		=> 0,		// Track user read messages or not
	// Different numbers
	"NUM_TOPICS_ON_PAGE"	=> 20,		// Number of topics to show on one page
	"NUM_POSTS_ON_PAGE"		=> 15,		// Number of messages to show on one page
	"NUM_MEMBERS_ON_PAGE"	=> 10,		// Number of members to show on one page
	"NUM_NEW_POSTS"			=> 20,		// Number of new posts to show on one page
	"SUBJECT_TRIM"			=> 30,		// Number of symbols to trim in subject (on "main page" and "view_forum")
	"MSG_TEXT_TRIM"			=> 65535,	// Number of symbols to trim comments, etc
	"LAST_POSTS_MAX_LENGTH"	=> 1000, 	// Max length of last posts (in new post form)
	"MIN_SEARCH_WORD"		=> 3, 		// Minimal search keyword length
	"MIN_USER_NAME"			=> 4,		// Min user name length
	"MAX_USER_NAME"			=> 32,		// Max user name length
	"REGISTRATION_TTL"		=> 86400,	// 24 hours (24 * 60 * 60) // Time to live of the register confirmation link (in seconds)
	"ANTISPAM_TIME"			=> 10,		// Min time between posts (posts from IP address with less time period are denied)
	"SESSION_EXPIRE_TIME"	=> 900,		// Session expiration (in seconds) if no activity detected
	"AVATAR_MAX_X"			=> 80,		// Avatar max width
	"AVATAR_MAX_Y"			=> 80,		// Avatar max height
	"AVATAR_MAX_BYTES"		=> 50000,	// Avatar max size
	// BB code options
	"BB_CODE"				=> 1,		// BB Codes
	"ENABLE_SMILIES"		=> 1,		// Smilies highlight status (obly when BB codes allowed)
	"SMILIES_IMAGES"		=> 1,		// Show smilies images or CSS based boxes (works only if smilies are allowed)
	"SMILIES_SET"			=> 2,		// Smilies set to use
	"ENABLE_POST_ICONS"		=> 0,		// Post icons on/off
	// Interface options
	"ALLOW_SKIN_CHANGE"		=> 0, 		// Allow or not skins changing
	"ALLOW_LANG_CHANGE"		=> 0, 		// Allow or not language changing
	"ALLOW_TOPICS_FILTER"	=> 0, 		// Allow topics filter or not
	"ALLOW_FAST_JUMP_BOX"	=> 1, 		// Allow fast jump box
	"ALLOW_SEARCH"			=> 1, 		// Turn searching on/off
	"ALLOW_SEARCH_ALL_POSTS"=> 1,		// Allow not to speciafy search keywords or user name (show all records)
	"ALLOW_ANNOUNCES"		=> 0, 		// Turn anounces on/off
	"ALLOW_TRACK_TOPIC"		=> 0, 		// Track topic feature on/off
	"ALLOW_TRACK_FORUM"		=> 0, 		// Track forum feature on/off
	"ALLOW_EMAIL_TOPIC"		=> 0, 		// Email topic feature on/off
	"ALLOW_PRINT_TOPIC"		=> 0, 		// Print topic feature on/off
	"USE_FAST_REPLY"		=> 1, 		// Allow ability to use fast reply form
	"USE_TOPIC_OPTIONS"		=> 0, 		// Show topic options inline
	"HIDE_USERS_INFO"		=> 0,		// Hide some links (for using as separate discussion boards for different objects)
	"SHOW_USER_RANKS"		=> 1,		// Show user ranks
	"SHOW_USER_LEVEL"		=> 0,		// Switch images-based user level on/off
	"SHOW_TOPIC_PAGES"		=> 1,		// Show direct links to pages inside forum
	"SHOW_TOPIC_MOD_BOX"	=> 0, 		// Show topic moderation options box
	"SHOW_HELP"				=> 0,		// Display board help or not
	"SHOW_MEMBERS_LIST"		=> 0,		// Display members list on/off
	"SHOW_EMPTY_CATS"		=> 0,		// Show or not empty forum categories
	"ALLOW_CHANGE_TOPIC_VIEW"=> 0,		// Topic view type changing
	"FAST_VIEW_REPLIERS"	=> 1,		// Fast view who replied in selected topic (for view_forum)
	"FAST_TEXT_PREVIEW"		=> 1,		// Fast text preview (for view_forum)
	// Date options
	"GLOBAL_TIME_OFFSET"	=> "0",		// Default time offset for the board (depends on where server is located)
	"DATE_FORMAT"			=> "d/m/Y H:i:s",
	"DATE_FORMAT_2"			=> "D M d H:i:s Y",
	// RSS related
	"RSS_EXPORT"			=> 0,		// Turn RSS export on/off
	"RSS_LATEST_IN_BOARD"	=> 15,		// Latest posts for feed in board
	"RSS_LATEST_IN_FORUM"	=> 15,		// Latest posts for feed in forum
	"RSS_LATEST_IN_TOPIC"	=> 15,		// Latest posts for feed in topic
	// Other options
	"AVATAR_IMAGE_TYPES"	=> "gif,jpg,jpeg,png",	// Allowed avatar image types
	"ADMIN_EMAIL_FROM"		=> "admin@profy.net",	// Email from
	"SECRET_KEY"			=> "_#@%^&_secret_key",	// Secret key used for encryption data
	"AVATARS_DIR"			=> "uploads/avatars/",	// Avatars folder
	"SMILIES_DIR"			=> "uploads/forum/smilies/",	// Smilies folder
	"POST_ICONS_DIR"		=> "uploads/forum/post_icons/",	// Folder for the post icons
));
?>