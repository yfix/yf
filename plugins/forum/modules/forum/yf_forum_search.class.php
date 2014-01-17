<?php

/**
* Common forum search engine
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_search {

	/** @var int @conf_skip */
	public $FORCE_LIMIT			= null;
	/** @var bool @conf_skip */
	public $SKIP_KEYWORDS_CHECK	= null;
	/** @var Array of all allowed fields @conf_skip */
	public $_allowed_fields = array(
		'keywords',
		'user_name',
		'exact_name',
		'user_id',
		'topic_id',
		'topic_starter',
		'sort_by',
		'sort_order',
		'prune_days',
		'prune_type',
		'forums',
		'search_in',
		'result_type',
		'only_unread',
	);
	/** @var Sorting fields @conf_skip */
	public $_sort_by = array(
		'last_post_date'	=> 'Last Posting Date',
		'num_posts'			=> 'Number of Replies',
		'user_name'			=> 'Poster Name',
		'forum_id'			=> 'Forum Name',
	);
	/** @var Sort order @conf_skip */
	public $_sort_order = array(
		'desc'	=> 'descending order',
		'asc'	=> 'ascending order',
	);
	/** @var Prune type @conf_skip */
	public $_prune_type = array(
		'older'	=> 'Older',
		'newer'	=> 'Newer',
	);
	/** @var Search in @conf_skip */
	public $_search_in = array(
		'posts'	=> 'Search entire post',
		'titles'=> 'Search titles only',
	);
	/** @var Result type @conf_skip */
	public $_result_type = array(
		'topics'	=> 'Show results as topics',
		'posts'		=> 'Show results as posts',
	);

	/**
	* Framework constructor
	*/
	function _init () {
		// Get online users ids for those who posted here
		foreach ((array)module('forum')->online_array as $online_info) {
			if (!empty($online_info['user_id']) && !empty($this->_users_array[$online_info['user_id']])) {
				$this->_online_users_ids[$online_info['user_id']] = $online_info['user_id'];
			}
		}
		// Prune days array
		$prune_string = '5,7,10,15,20,25,30,60,90,180,365,1000';
		$prune_array = explode(',', trim($prune_string));
		if (is_array($prune_array)) {
			$this->_prune_days[1] = 'Today';
			foreach ((array)$prune_array as $v) {
				$this->_prune_days[$v] = (count($prune_array) == ++$i) ? 'Any date' : intval($v).' days ago and...';
			}
		}
		// Process forums
		$forum_divider	= '&nbsp;&nbsp;&#0124;-- ';
		$this->_forums	= array(
			'all'	=> '&raquo; '.t('All Forums'),
		);
		foreach ((array)module('forum')->_forum_cats_array as $cat_info) {
			foreach ((array)module('forum')->_forums_array as $forum_info) {
				if ($forum_info['category'] != $cat_info['id']) {
					continue;
				}
				// Check user group access rights to the current forum
				if ($forum_info['user_groups']) {
					$only_for_groups = $forum_info['user_groups'] ? explode(',', $forum_info['user_groups']) : '';
					if (!empty($only_for_groups) && !in_array(FORUM_USER_GROUP_ID, $only_for_groups) && !FORUM_IS_ADMIN) {
						$this->_skip_forums[$forum_info['id']] = $forum_info['id'];
						continue;
					}
				}
				$this->_forums[$cat_info['name']][$forum_info['id']] = $forum_divider. $forum_info['name'];
			}
		}
		// Boxes array
		$this->_boxes = array(
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,	$selected, 0, 2, "", false)',
			"prune_days"	=> 'select_box("prune_days",	$this->_prune_days,	$selected, 0, 2, "", false)',
			"forums"		=> 'multi_select("forums",		$this->_forums,		$selected, 0, 2, " class=\'forminput\' size=\'10\' ", false)',
		);
	}

	/**
	* Show Main
	*/
	function _show_main () {
		if (!module('forum')->SETTINGS['ALLOW_SEARCH'] || !module('forum')->USER_RIGHTS['use_search']) {
			return module('forum')->_show_error('Search is disabled');
		} else {
			$body = (!isset($_POST['q']) && !isset($_GET['q'])) ? $this->_search_form() : $this->_do_search();
		}
		return $body;
	}

	/**
	* Show search form
	*/
	function _search_form ($force_stpl = '') {
		$replace = array(
			'form_action'		=> './?object='.'forum'.'&action='.$_GET['action']. ($_GET['language'] ? '&language='.$_GET['language'] : ''),
			'prune_days_box'	=> $this->_box('prune_days', 1000),
			'forums_box'		=> $this->_box('forums', 'all'),
			'sort_by_box'		=> $this->_box('sort_by', 'last_post_date'),
			'keywords'			=> '',
			'user_name'			=> '',
		);
		// Force to use another template (ex. outer forum search form with another design)
		if (!empty($force_stpl)) {
			$body = tpl()->parse($force_stpl, $replace);
		} else {
			$body = module('forum')->_show_main_tpl(tpl()->parse('forum'.'/search/form_main', $replace));
		}
		return $body;
	}

	/**
	* Show searching results
	*/
	function _do_search ($request_array = '') {
		// Cleanup request vars
		if (!is_array($request_array)) {
			$request_array = array_merge($_GET, $_POST);
		}
		foreach ((array)$request_array as $k => $v) {
			if (!is_array($v)) {
				$request_array[$k] = trim($v);
			}
		}
		// Special processing for the forums array
		if (!empty($request_array['forums'])) {
			if (is_array($request_array['forums'])) {
				foreach ((array)$request_array['forums'] as $k => $v) {
					$request_array['forums'][$k] = intval($v);
					if (empty($request_array['forums'][$k])) {
						unset($request_array['forums'][$k]);
					}
				}
				$request_array['forums'] = implode(',',$request_array['forums']);
			} else {
				$request_array['forums'] = intval($request_array['forums']);
			}
			// Some cleanups
			if (!empty($request_array['forums'])) {
				$_GET['forums'] = $request_array['forums'];
			}
			if (isset($_GET['forums']) && empty($_GET['forums'])) {
				unset($_GET['forums']);
			}
			if (isset($_POST['forums']) && empty($_POST['forums'])) {
				unset($_POST['forums']);
			}
		}
		// Try to grab non-empty fields
		foreach ((array)$this->_allowed_fields as $field_name) {
			if (isset($request_array[$field_name]) && strlen($request_array[$field_name])) {
				$this->_active_fields[$field_name] = _es(trim(stripslashes($request_array[$field_name])));
			}
		}
		// For shorter code
		$AF = &$this->_active_fields;
		// Get current page number
		$_GET['page'] = !empty($_GET['page']) ? intval($_GET['page']) : 1;
		// Process keywords checkin
		if (empty($this->SKIP_KEYWORDS_CHECK)) {
			// Cut long keywords
			$AF['keywords'] = _substr($AF['keywords'], 0, 64);
			// Check if is allowed to search all posts
			if (!module('forum')->SETTINGS['ALLOW_SEARCH_ALL_POSTS']) {
				// Check keywords
				if (empty($AF['keywords']) && empty($AF['user_name']) && empty($AF['user_id']) && empty($AF['topic_id'])) {
					_re('Please specify search keywords');
				} elseif (empty($AF['user_name']) && empty($AF['user_id']) && empty($AF['topic_id'])) {
					if (strlen($AF['keywords']) < module('forum')->SETTINGS['MIN_SEARCH_WORD']) {
						_re('Too short keyword!');
					}
				}
			}
		}
		// Default result type
		if (empty($AF['result_type'])) {
			$AF['result_type'] = 'topics';
		}
		// Check for errors
		if (!common()->_error_exists()) {
			// Show results
			$body = $AF['result_type'] == 'posts' ? $this->_show_result_as_posts($AF) : $this->_show_result_as_topics($AF);
		} else {
			return module('forum')->_show_error(_e());
		}
		return $body;
	}

	/**
	* Show search results as topics
	*/
	function _show_result_as_topics ($AF = array()) {
		// Fetch records from database
		$Q = db()->query($this->_prepare_sql_and_pages($AF));
		while ($A = db()->fetch_assoc($Q)) {
			$topics_ids[$A['topic']] = $A['num_records'];
		}
		// Get topics infos
		if (is_array($topics_ids)) {
			$Q = db()->query('SELECT * FROM '.db('forum_topics').' WHERE '.(!FORUM_IS_ADMIN ? 'approved=1 AND' : '').' id IN('.implode(',', array_keys($topics_ids)).')');
			while ($A = db()->fetch_assoc($Q)) {
				$topic_is_read = module('forum')->SETTINGS['USE_READ_MESSAGES'] ? module('forum')->_get_topic_read($A) : 0;
				if (!empty($AF['only_unread']) && $topic_is_read) {
					continue;
				}
				$topics_array[$A['id']] = $A;
			}
		}
		// Process posts
		if (!empty($topics_array)) {
			list($last_posts, $topic_pages) = $this->_get_topics_last_posts_and_pages($topics_array);
		}
		if (!empty($topics_array)) {
			// Init topic item object
			$TOPIC_ITEM_OBJ = _class('forum_topic_item', 'modules/forum/');
			// Process posts
			if (is_object($TOPIC_ITEM_OBJ)) {
				foreach ((array)$topics_array as $topic_info) {
					$topic_is_moved = intval(!empty($topic_info['moved_to']));
					$moved_id = $topic_is_moved ? array_pop(explode(',', $topic_info['moved_to'])) : 0;
					$topic_is_read = module('forum')->SETTINGS['USE_READ_MESSAGES'] ? module('forum')->_get_topic_read($topic_info) : 0;
					$items	.= $TOPIC_ITEM_OBJ->_show_topic_item($topic_info, $topic_is_read, $last_posts[$moved_id ? $moved_id : $topic_info['id']], $topic_pages[$topic_info['id']], '/search/result_topics_item');
				}
			}
		}
		$replace = array(
			'items'			=> $items,
			'pages'			=> empty($this->FORCE_LIMIT) ? trim($this->pages) : '',
			'board_fast_nav'=> module('forum')->SETTINGS['ALLOW_FAST_JUMP_BOX'] ? module('forum')->_board_fast_nav_box() : '',
		);
		return !empty($items) ? module('forum')->_show_main_tpl(tpl()->parse('forum'.'/search/result_topics_main', $replace)) : module('forum')->_show_error('No topics matching your search query found!');
	}

	/**
	* Show search results as posts
	*/
	function _show_result_as_posts ($AF = array()) {
		// Fetch records from database
		$Q = db()->query($this->_prepare_sql_and_pages($AF));
		while ($A = db()->fetch_assoc($Q)) {
			$posts[$A['id']] = $A;
		}
		// Process posts
		if (is_array($posts)) {
			// Get unique user ids from ads
			foreach ((array)$posts as $A) if(!empty($A['user_id'])) {
				$users_ids[$A['user_id']] = $A['user_id'];
				$topics_ids[$A['topic']] = $A['topic'];
			}
			// Get users info's
			if (!empty($users_ids)) {
				$users_array = module('forum')->_get_users_infos($users_ids);
			}
			// Fetch topics infos
			if (!empty($topics_ids)) {
				$Q3 = db()->query('SELECT * FROM '.db('forum_topics').' WHERE id IN ('.implode(',',$topics_ids).')'.(!FORUM_IS_ADMIN ? ' AND approved=1 ' : ''));
				while ($A = db()->fetch_assoc($Q3)) {
					$topics_array[$A['id']] = $A;
				}
			}
			// Process users reputation
			$REPUT_OBJ = module('reputation');
			if (is_object($REPUT_OBJ)) {
				$users_reput_info	= $REPUT_OBJ->_get_reput_info_for_user_ids($users_ids);
				foreach ((array)$users_reput_info as $reput_user_id => $reput_info) {
					module('forum')->_reput_texts[$reput_user_id] = $REPUT_OBJ->_show_for_user($reput_user_id, $users_reput_info[$reput_user_id], false, array('forum_posts', $post_info['id']));
				}
			}
			// Init post item object
			$POST_ITEM_OBJ = _class('forum_post_item', 'modules/forum/');
			// Process posts
			if (!empty($topics_array) && is_object($POST_ITEM_OBJ)) {
				foreach ((array)$posts as $post_info) {
					$topic_info = &$topics_array[$post_info['topic']];
					if (empty($topic_info)) {
						continue;
					}
					// Check access rights
					if (!module('forum')->USER_RIGHTS['view_other_topics'] && FORUM_USER_ID != $topic_info['user_id']) {
						continue;
					}
					if (!module('forum')->USER_RIGHTS['view_post_closed'] && $topic_info['status'] != 'a') {
						continue;
					}
					if (!empty($users_reput_info)) {
						module('forum')->_reput_texts_for_posts[$post_info['id']] = $REPUT_OBJ->_show_for_user($post_info['user_id'], $users_reput_info[$post_info['user_id']], false, array('forum_posts', $post_info['id']));
					}
					$forum_is_closed	= module('forum')->_forums_array[$post_info['forum']]['options'] == '2' ? 1 : 0;
					$topic_is_closed	= intval($topic_info['status'] != 'a');
					$allow_reply		= intval(!$forum_is_closed && !$topic_is_closed);
					$user_info			= $users_array[$post_info['user_id']];
					$is_first_post		= $topic_info['first_post_id'] != $post_info['id'];
					$items				.= $POST_ITEM_OBJ->_show_post_item($post_info, $user_info, $topic_info, '/search/result_posts_item', $is_first_post, $allow_reply);
					if (!empty($users_reput_info)) {
						unset(module('forum')->_reput_texts_for_posts[$post_info['id']]);
					}
				}
			}
		}
		$replace = array(
			'items'			=> $items,
			'pages'			=> empty($this->FORCE_LIMIT) ? trim($this->pages) : '',
			'board_fast_nav'=> module('forum')->SETTINGS['ALLOW_FAST_JUMP_BOX'] ? module('forum')->_board_fast_nav_box() : '',
		);
		return !empty($items) ? module('forum')->_show_main_tpl(tpl()->parse('forum'.'/search/result_posts_main', $replace)) : module('forum')->_show_error('No posts matching your search query found!');
	}

	/**
	* Get Topics Last Posts And Pages
	*/
	function _get_topics_last_posts_and_pages($topics_array = array()) {
		if (!is_array($topics_array)) {
			return false;
		}
		$last_posts_ids		= array();
		$topic_pages_ids	= array();
		foreach ((array)$topics_array as $topic_info) {
			// Skip empty topics
			if (empty($topic_info['last_post_id'])) {
				continue;
			}
			$last_posts_ids[$topic_info['last_post_id']] = $topic_info['last_post_id'];
			// Skip next action if topics pages not needed
			if (!module('forum')->SETTINGS['SHOW_TOPIC_PAGES']) {
				continue;
			}
			// Check if need to process topic pages
			if ($topic_info['num_posts'] > module('forum')->SETTINGS['NUM_POSTS_ON_PAGE']) {
				$topic_pages_ids[$topic_info['id']] = $topic_info['num_posts'];
			}
		}
		$posts_per_page		= !empty(module('forum')->USER_SETTINGS['POSTS_PER_PAGE']) ? module('forum')->USER_SETTINGS['POSTS_PER_PAGE'] : module('forum')->SETTINGS['NUM_POSTS_ON_PAGE'];
		// Process topic pages
		if (module('forum')->SETTINGS['SHOW_TOPIC_PAGES'] && !empty($topic_pages_ids)) {
			$topic_pages = array();
			foreach ((array)$topic_pages_ids as $topic_id => $topic_num_posts) {
				$topics_per_page = !empty(module('forum')->USER_SETTINGS['TOPICS_PER_PAGE']) ? module('forum')->USER_SETTINGS['TOPICS_PER_PAGE'] : module('forum')->SETTINGS['NUM_TOPICS_ON_PAGE'];
				list(,$topic_pages[$topic_id],,,$_total_pages[$topic_id]) = common()->divide_pages('', './?object='.'forum'.'&action=view_topic&id='.$topic_id, null, $topics_per_page, $topic_num_posts + 1, 'forum'.'/pages_2/');
			}
		}
		// Process last posts records
		if (!empty($last_posts_ids)) {
			$last_posts = array();
			$Q = db()->query('SELECT * FROM '.db('forum_posts').' WHERE id IN('.implode(',',$last_posts_ids).')');
			while ($post_info = db()->fetch_assoc($Q)) {

				$subject = strlen($post_info['subject']) ? $post_info['subject'] : $post_info['text'];
				$subject = module('forum')->_cut_subject_for_last_post($subject);

				$replace3 = array(
					'last_post_author_name'	=> !empty($post_info['user_name']) ? _prepare_html($post_info['user_name']) : t('Anonymous'),
					'last_post_author_link'	=> $post_info['user_id'] ? module('forum')->_user_profile_link($post_info['user_id']) : '',
					'last_post_subject'		=> _prepare_html($subject),
					'last_post_date'		=> module('forum')->_show_date($post_info['created'], 'last_post_date'),
					'last_post_link'		=> './?object='.'forum'.'&action=view_topic&id='.$post_info['topic'].($_total_pages[$post_info['topic']] > 1 ? '&page='.$_total_pages[$post_info['topic']] : '').'#last_post',
				);
				$last_posts[$post_info['topic']] = tpl()->parse('forum'.'/view_forum_last_posts', $replace3);
			}
		}
		return array($last_posts, $topic_pages);
	}

	/**
	* Prepare Sql And Pages
	*/
	function _prepare_sql_and_pages ($AF = array()) {
		if ($AF['result_type'] == 'posts') {
			$sql1_header = 'SELECT id FROM '.db('forum_posts').' WHERE 1=1 ';
			$sql2_header = 'SELECT * FROM '.db('forum_posts').' WHERE 1=1 ';
		} else {
			$sql1_header = 'SELECT COUNT(id) AS `0`,topic AS 1 FROM '.db('forum_posts').' WHERE 1=1 ';
			$sql2_header = 'SELECT COUNT(id) AS num_records, topic FROM '.db('forum_posts').' WHERE 1=1 ';
		}
		// Show all posts for the admin
		$sql1 .= !FORUM_IS_ADMIN ? " AND status='a' " : "";
		// Process keywords
		if (!empty($AF["keywords"])) {
			$sql1 .= " AND (subject LIKE '%".$AF["keywords"]."%' ".($AF["search_in"] == "posts" ? " OR text LIKE '%".$AF["keywords"]."%' " : "").") ";
		}
		// Filter by member
		if (!empty($AF["user_name"])) {
			$sql1 .= " AND user_name LIKE '".(!empty($AF["exact_name"]) ? $AF["user_name"] : "%".$AF["user_name"]."%")."' ";
		} elseif (!empty($AF["user_id"])) {
			$sql1 .= " AND user_id=".intval($AF["user_id"])." ";
		}
		// Add skip forums ids
		if (!empty($this->_skip_forums)) {
			$sql1 .= " AND forum NOT IN(".implode(",",$this->_skip_forums).") ";
		}
		// Process forums ids
		if (!empty($AF["forums"])) {
			$sql1 .= " AND forum IN(".$AF["forums"].") ";
		// Process topic id
		} elseif (!empty($AF["topic_id"])) {
			$sql1 .= " AND topic=".intval($AF["topic_id"])." ";
		}
		// Process prune
		if (!empty($AF["prune_days"])) {
			$sql1 .= " AND created ".($AF["prune_type"] == "older" ? "<=" : ">=")." ".(time() - intval($AF["prune_days"]) * 24 * 3600)." ";
		}
		// Groupping SQL
		if ($AF["result_type"] == "topics") {
			$sql1 .= " GROUP BY topic ";
		}
		// Generate query text according to existing fields
		foreach ((array)$AF as $k => $v) {
			if ($k == "page") {
				continue;
			}
			$q .= "&".urlencode($k)."=".urlencode($v);
			if (isset($_GET[$k])) {
				unset($_GET[$k]);
			}
		}
		if (isset($_GET["q"])) {
			unset($_GET["q"]);
		}
		// If forcing limit not set - then process regular pager
		if (empty($this->FORCE_LIMIT)) {
			// Count total matched records
			// Optimized vesion for MySQL >= 4.1.x
			if (module('forum')->SETTINGS['USE_OPTIMIZED_SQL'] 
				&& false !== strpos(db()->DB_TYPE, 'mysql') 
			) {
				db()->query(str_replace('SELECT ', 'SELECT SQL_CALC_FOUND_ROWS ', $sql1_header).$sql1.' LIMIT 0');
				list($matched_records) = db()->query_fetch('SELECT FOUND_ROWS() AS `0`', false);
			} else {
				$matched_records = db()->query_num_rows($sql1_header.$sql1);
			}
			$matched_records = intval($matched_records);
			// Get pages string
			$path = './?object='.'forum'.'&action='.$_GET['action'].$q.'&q=results'.($_GET['language'] ? '&language='.$_GET['language'] : '');
			$posts_per_page		= !empty(module('forum')->USER_SETTINGS['POSTS_PER_PAGE']) ? module('forum')->USER_SETTINGS['POSTS_PER_PAGE'] : module('forum')->SETTINGS['NUM_POSTS_ON_PAGE'];
			$topics_per_page	= !empty(module('forum')->USER_SETTINGS['TOPICS_PER_PAGE']) ? module('forum')->USER_SETTINGS['TOPICS_PER_PAGE'] : module('forum')->SETTINGS['NUM_TOPICS_ON_PAGE'];
			list($limit_sql, $this->pages, ) = common()->divide_pages(null, $path, null, $AF['result_type'] == 'topics' ? $topics_per_page : $posts_per_page, $matched_records, 'forum'.'/pages_1/');
		} else {
			$limit_sql = ' LIMIT 0,'.$this->FORCE_LIMIT;
		}
		// We not needed it now
		unset($_GET['page']);
		// Process sorting by
		if (!empty($AF['sort_by']) && array_key_exists($AF['sort_by'], $this->_sort_by)) {
			if ($AF['sort_by'] == 'last_post_date')	$sort_by = 'created';
			elseif ($AF['sort_by'] == 'num_posts')	$sort_by = 'num_records';
			elseif ($AF['sort_by'] == 'user_name')	$sort_by = 'user_name';
			elseif ($AF['sort_by'] == 'forum_id')	$sort_by = 'forum';
			if (!empty($sort_by)) $sql2 .= ' ORDER BY '.$sort_by.' '.($AF['sort_order'] == 'asc' ? 'ASC' : 'DESC').' ';
		}
		return $sql2_header. $sql1. $sql2. $limit_sql;
	}

	/**
	* View New Posts
	*/
	function _view_new_posts () {
		$this->FORCE_LIMIT			= module('forum')->SETTINGS['NUM_NEW_POSTS'];
		$this->SKIP_KEYWORDS_CHECK	= true;
		return $this->_do_search(array(
			'sort_by'		=> 'last_post_date',
			'sort_order'	=> 'desc',
			'result_type'	=> 'posts',
		));
	}

	/**
	* View Unread Posts
	*/
	function _view_unread_topics () {
		if (!FORUM_USER_ID) {
			return module('forum')->_show_error(_error_need_login());
		}
		$this->FORCE_LIMIT			= module('forum')->SETTINGS['NUM_NEW_POSTS'];
		$this->SKIP_KEYWORDS_CHECK	= true;
		return $this->_do_search(array(
			'sort_by'		=> 'last_post_date',
			'sort_order'	=> 'desc',
			'result_type'	=> 'topics',
			'only_unread'	=> 1,
		));
	}

	/**
	* Process custom box
	*/
	function _box ($name = '', $selected = '') {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval('return common()->'.$this->_boxes[$name].';');
	}
}
