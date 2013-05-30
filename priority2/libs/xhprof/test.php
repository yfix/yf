<?

//xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
xhprof_enable();

// GLOBALS PROJECT MODULES CONFIG VARS


$array1 = array(
	// CORE CLASSES
	"main"	=> array(
		"USE_CUSTOM_ERRORS"		=> 1,
		"USE_SYSTEM_CACHE"		=> 1,
		"USE_TASK_MANAGER"		=> 1,
		"NO_CACHE_HEADERS"		=> 1,
		"SPIDERS_DETECTION"		=> 1,
		"OVERLOAD_PROTECTION"	=> 0,
		"ALLOW_FAST_INIT"		=> 1,
		"USE_GEO_IP"			=> 1,
		"OUTPUT_CACHING"		=> 1,
		"OUTPUT_GZIP_COMPRESS"	=> 1,
		"USER_INFO_DYNAMIC"		=> 1,
		"TRACK_USER_PAGE_VIEWS" => 1, 
		"STATIC_PAGES_ROUTE_TOP"=> 1,
	),
	"auth_user" => array(
		"URL_SUCCESS_LOGIN" => "./?object=account", 
		"EXEC_AFTER_LOGIN"		=> array(
			array("_add_login_activity"),
		),
		"SESSION_LOCK_TO_IP" => 0,
	),
	"graphics" => array(
		"CACHE_CSS" => 1,
		"QUICK_MENU_ENABLED" => false,
	), 
	"send_mail"	=> array(
		"USE_MAILER"	=> "simple",
	),
	"tpl" => array(
		"ALLOW_LANG_BASED_STPLS" => 1,
		"REWRITE_MODE"			=> 1,
		
		"COMPILE_TEMPLATES"		=> 1,
		"COMPILE_CHECK_STPL_CHANGED"=> 1,

	),
	"locale" => array(
		"TRACK_TRANSLATED"  => 1,
	),
	"debug_info" => array(
		"_SHOW_NOT_TRANSLATED"  => 1,
		"_SHOW_I18N_VARS"   => 1,
	),
	"rewrite"	=> array(
		"_rewrite_add_extension"	=> "/",
	),
	"comments"	=> array(
		"USE_TREE_MODE" => 1,
		"ANTI_FLOOD_TIME"	=> 5,
		"COMMENT_LINKS"	=> array(
			"news"		=> "./?object=news&action=full_news&id=",
			"articles"	=> "./?object=articles&action=view&id=",
			"place"		=> "./?object=place&action=view&id=",
		),
	),
	"_forum"	=> array(
		"USE_GLOBAL_USERS"		=> 1,
		"ALLOW_WYSIWYG_EDITOR"	=> 0,
		"BB_CODE"				=> 1,
		"ENABLE_SMILIES"		=> 1,
		"SMILIES_IMAGES"		=> 1,
		"SMILIES_SET"			=> 2,
		"ALLOW_POLLS"			=> 1,
		
		"SHOW_TOTALS"			=> 1,
		"ONLINE_USERS_STATS"	=> 1,
		"USE_READ_MESSAGES"		=> 1,
		
		"ALLOW_TOPICS_FILTER"	=> 1,
		"ALLOW_FAST_JUMP_BOX"	=> 1,
		"ALLOW_SEARCH"			=> 1,
		"ALLOW_SEARCH_ALL_POSTS"=> 1,
		"SEND_NOTIFY_EMAILS"	=> 1,
		"SHOW_TOPIC_PAGES"		=> 1,
		"ALLOW_ANNOUNCES"		=> 1,
		"ALLOW_TRACK_TOPIC"		=> 1,
		
		"USE_FAST_REPLY"		=> 1,
		"USE_TOPIC_OPTIONS"		=> 1,
		"SHOW_USER_RANKS"		=> 1,
		"SHOW_USER_LEVEL"		=> 1,
		"SHOW_TOPIC_MOD_BOX"		=> 1,
		"SHOW_MEMBERS_LIST"			=> 1,
		"USER_RIGHTS"	=> array(
			"delete_own_topics"	=> 1,
		),
	),
	"_forum_def_rights"	=> array(
		"make_polls"	=> 1,
		"vote_polls"	=> 1,
	),
	"gallery"	=> array(
		"ALLOW_RATE"	=> 1,
		"ALLOW_TAGGING"	=> 1,
	),
	"logs"	=> array(
		"_LOGGING"			=> 1,
		"STORE_USER_AUTH"	=> 1,
		"UPDATE_LAST_LOGIN"	=> 1,
	),
	"register"	=> array(
		"NICK_ALLOWED_SYMBOLS"	=> array("à-ÿ","³º¿","a-z","0-9","_","\-","@","#"," "),
		"CONFIRM_REGISTER"		=> 0,
	),
	"validate"	=> array(
		"NICK_ALLOWED_SYMBOLS"	=> array("à-ÿ","³º¿","a-z","0-9","_","\-","@","#"," "),
	),
	"news"		=> array(
		"USE_CAPTCHA"	=> 0,
	),
	"bb_codes"		=> array(
		"DEFAULT_SHOW_CODES"	=> array(
			"font_family"	=> 0,
			"font_size"		=> 1,
			"font_color"	=> 1,
			"extra_fields"	=> 1,
			"help_box"		=> 0,
			"open_tags"		=> 0,
			"youtube"		=> 1,
		),
		"SMILIES_DIR"	=> "uploads/forum/smilies/",
	),
	"email"		=> array(
		"USE_CAPTCHA"	=> 0,
	),
	"articles"		=> array(
		"USE_CAPTCHA"	=> 0,
	),
	"place"		=> array(
		"USE_CAPTCHA"	=> 0,
	),
	"home_page"		=> array(
		"NUM_NEWEST_USERS"	=> 4,
	),
	"unread"	=> array(
		"UNREAD_ENABLED"	=> 1,
	),
	"map"	=> array(
		"MARKERS_ENABLE"	=> true,
	),
	"site_map" => array(
		"SITE_MAP_ENABLED" 	=> 1,
		"NOTIFY_GOOGLE"		=> 1,
		"ALLOW_REWRITE"		=> 1,
	), 
);
$array2 = array(
	// CORE CLASSES
	"main"	=> array(
		"USE_CUSTOM_ERRORS"		=> "###",
		"USE_SYSTEM_CACHE"		=> 1,
		"USE_TASK_MANAGER"		=> 1,
		"NO_CACHE_HEADERS"		=> 1,
		"SPIDERS_DETECTION"		=> 1,
		"OVERLOAD_PROTECTION"	=> 0,
		"ALLOW_FAST_INIT"		=> 1,
		"USE_GEO_IP"			=> 1,
		"OUTPUT_CACHING"		=> 1,
		"OUTPUT_GZIP_COMPRESS"	=> 1,
		"USER_INFO_DYNAMIC"		=> 1,
		"TRACK_USER_PAGE_VIEWS" => 1, 
		"STATIC_PAGES_ROUTE_TOP"=> 1,
	),
	"auth_user" => array(
		"URL_SUCCESS_LOGIN" => "./?object=account", 
		"EXEC_AFTER_LOGIN"		=> array(
			array("_add_login_activity"),
		),
		"SESSION_LOCK_TO_IP" => 0,
	),
	"graphics" => array(
		"CACHE_CSS" => 1,
		"QUICK_MENU_ENABLED" => false,
	), 
	"send_mail"	=> array(
		"USE_MAILER"	=> "simple",
	),
	"tpl" => array(
		"ALLOW_LANG_BASED_STPLS" => 1,
		"REWRITE_MODE"			=> 1,
		
		"COMPILE_TEMPLATES"		=> 1,
		"COMPILE_CHECK_STPL_CHANGED"=> 1,

	),
	"locale" => array(
		"TRACK_TRANSLATED"  => 1,
	),
	"debug_info" => array(
		"_SHOW_NOT_TRANSLATED"  => 1,
		"_SHOW_I18N_VARS"   => 1,
	),
	"rewrite"	=> array(
		"_rewrite_add_extension"	=> "/",
	),
	"comments"	=> array(
		"USE_TREE_MODE" => 1,
		"ANTI_FLOOD_TIME"	=> 5,
		"COMMENT_LINKS"	=> array(
			"news"		=> "./?object=news&action=full_news&id=",
			"articles"	=> "./?object=articles&action=view&id=",
			"place"		=> "./?object=place&action=view&id=",
		),
	),
	"_forum"	=> array(
		"USE_GLOBAL_USERS"		=> 1,
		"ALLOW_WYSIWYG_EDITOR"	=> 0,
		"BB_CODE"				=> 1,
		"ENABLE_SMILIES"		=> 1,
		"SMILIES_IMAGES"		=> 1,
		"SMILIES_SET"			=> 2,
		"ALLOW_POLLS"			=> 1,
		
		"SHOW_TOTALS"			=> 1,
		"ONLINE_USERS_STATS"	=> 1,
		"USE_READ_MESSAGES"		=> 1,
		
		"ALLOW_TOPICS_FILTER"	=> 1,
		"ALLOW_FAST_JUMP_BOX"	=> 1,
		"ALLOW_SEARCH"			=> 1,
		"ALLOW_SEARCH_ALL_POSTS"=> 1,
		"SEND_NOTIFY_EMAILS"	=> 1,
		"SHOW_TOPIC_PAGES"		=> 1,
		"ALLOW_ANNOUNCES"		=> 1,
		"ALLOW_TRACK_TOPIC"		=> 1,
		
		"USE_FAST_REPLY"		=> 1,
		"USE_TOPIC_OPTIONS"		=> 1,
		"SHOW_USER_RANKS"		=> 1,
		"SHOW_USER_LEVEL"		=> 1,
		"SHOW_TOPIC_MOD_BOX"		=> 1,
		"SHOW_MEMBERS_LIST"			=> 1,
		"USER_RIGHTS"	=> array(
			"delete_own_topics"	=> 1,
		),
	),
	"_forum_def_rights"	=> array(
		"make_polls"	=> 1,
		"vote_polls"	=> 1,
	),
	"gallery"	=> array(
		"ALLOW_RATE"	=> 1,
		"ALLOW_TAGGING"	=> 1,
	),
	"logs"	=> array(
		"_LOGGING"			=> 1,
		"STORE_USER_AUTH"	=> 1,
		"UPDATE_LAST_LOGIN"	=> 1,
	),
	"register"	=> array(
		"NICK_ALLOWED_SYMBOLS"	=> array("à-ÿ","³º¿","a-z","0-9","_","\-","@","#"," ","###"),
		"CONFIRM_REGISTER"		=> 0,
	),
	"validate"	=> array(
		"NICK_ALLOWED_SYMBOLS"	=> array("à-ÿ","³º¿","a-z","0-9","_","\-","@","#","###"),
	),
	"news"		=> array(
		"USE_CAPTCHA"	=> 0,
	),
	"bb_codes"		=> array(
		"DEFAULT_SHOW_CODES"	=> array(
			"font_family"	=> 0,
			"font_size"		=> 1,
			"font_color"	=> 1,
			"extra_fields"	=> 1,
			"help_box"		=> 0,
			"open_tags"		=> 0,
			"youtube"		=> 1,
		),
		"SMILIES_DIR"	=> "uploads/forum/smilies/",
	),
	"email"		=> array(
		"USE_CAPTCHA"	=> 0,
	),
	"articles"		=> array(
		"USE_CAPTCHA"	=> 0,
	),
	"place"		=> array(
		"USE_CAPTCHA"	=> 0,
	),
	"home_page"		=> array(
		"NUM_NEWEST_USERS"	=> 4,
	),
	"unread"	=> array(
		"UNREAD_ENABLED"	=> 1,
	),
	"map"	=> array(
		"MARKERS_ENABLE"	=> true,
	),
	"site_map" => array(
		"SITE_MAP_ENABLED" 	=> "###",
		"NOTIFY_GOOGLE"		=> 1,
		"ALLOW_REWRITE"		=> 1,
	), 
);

/*
$array1 = array(
	"register"	=> array(
		"NICK_ALLOWED_SYMBOLS"	=> array("a", "b"),
		"CONFIRM_REGISTER"		=> 0,
	),
);
$array2 = array(
	"register"	=> array(
		"NICK_ALLOWED_SYMBOLS"	=> array("a", "b", "c"),
		"CONFIRM_REGISTER"		=> 1,
	),
);
*/



function my_array_merge($arr_1, $arr_2, $_level = 0) {
	$arr_new = array();
	// Get keys
	$arr_1_keys = array_keys((array)$arr_1);
	$arr_2_keys = array_keys((array)$arr_2);
	// Get differencies
	foreach (array_diff($arr_1_keys, $arr_2_keys) as $_key_1) {
		$arr_new[$_key_1] = $arr_1[$_key_1];
	}
	foreach (array_diff($arr_2_keys, $arr_1_keys) as $_key_2) {
		$arr_new[$_key_2] = $arr_2[$_key_2];
	}
	foreach (array_intersect($arr_1_keys, $arr_2_keys) as $_key) {
		if (is_array($arr_1[$_key]) && is_array($arr_2[$_key]) && $_level < 2) {
			$arr_new[$_key] = my_array_merge($arr_1[$_key], $arr_2[$_key], $_level + 1);
		} else {
			$arr_new[$_key] = $arr_2[$_key];
		}
	}
	return $arr_new;
}

function _my_array_merge($Arr1, $Arr2)
 {
   foreach($Arr2 as $key => $Value)
   {
     if(isset($Arr1[$key]) && is_array($Value))
       $Arr1[$key] = _my_array_merge($Arr1[$key], $Arr2[$key]);
     else
       $Arr1[$key] = $Value;
   }

   return $Arr1;

 }
 
 


$result1 = my_array_merge($array1, $array2);
//array_merge_recursive($array1, $array2);
$result2 = _my_array_merge($array1, $array2);




$xhprof_data = xhprof_disable();

include_once "xhprof_lib/utils/xhprof_lib.php";
include_once "xhprof_lib/utils/xhprof_runs.php";
$xhprof_runs = new XHProfRuns_Default();
$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_test");
echo "<br>Report: <a href='http://localhost/xhprof_html/index.php?run=".$run_id."&source=xhprof_test' target='_blank'>debug</a>"; 
