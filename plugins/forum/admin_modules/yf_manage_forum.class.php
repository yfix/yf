<?php

/**
* Forum administration class
*/
class yf_manage_forum {

	/** @var string @conf_skip Current forum version */
	public $VERSION		= 'admin 1.1.2';
	/** @var int Number of topics to show on one page */
	public $topics_on_page = 50;
	/** @var int Number of messages to show on one page */
	public $posts_on_page	= 20;
	/** @var int Number of symbols to trim comments, etc */
	public $text_trim		= 300;
	/** @var array @conf_skip CSS classes */
	public $css = array (
		'show1'	=> 'forum1',
		'show2'	=> 'forum2',
		'quote'	=> 'forum_quote',
		'code'	=> 'forum_code',
		'smile'	=> 'forum_smile',
	);
	/** @var array Date/Time formats (arguments for date() function) */
	public $format = array(
		'date'	=> 'd/m/Y',
		'time'	=> 'H:i:s',
	);
	/** @var array Folder where avatars are storing */
	public $avatars_dir = 'uploads/avatars/';
	/** @var bool Approve posts and topics creation */
	public $APPROVE				= false;
	/** @var bool Confirm registration with email */
	public $CONFIRM_REGISTER	= true;
	/** @var bool Tree mode */
	public $TREE_MODE			= false;
	/** @var bool BB Codes */
	public $BB_CODE				= true;
	/** @var bool Show smilies images or CSS based boxes (works only if smilies are allowed) */
	public $SMILIES_IMAGES		= false;
	/** @var bool Show user ranks */
	public $SHOW_USER_RANKS		= true;
	/** @var bool Show totals */
	public $SHOW_TOTALS			= false;
	/** @var bool Show direct links to pages inside forum */
	public $SHOW_TOPIC_PAGES	= true;
	/** @var bool Use global user accounts or only forum internals */
	public $USE_GLOBAL_USERS	= true;
	/** @var bool Hide some links (for using as separate discussion boards for different objects) */
	public $HIDE_USERS_INFO		= true;
	/** @var string @conf_skip Unique for each installation identifier (you can set it manually here) */
	public $salt				= '';
	/** @var int Minimal time between posts (posts from IP address with less time period are denied) */
	public $ANTISPAM_TIME		= 0;
	/** @var array */
	public $_group_triggers = array(
		'view_board'			=> 'Allow to view forums',
		'view_ip'				=> 'Allow to view posters saved IP address',
		'view_member_info'		=> 'Allow to view forum member info',
		'view_other_topics'		=> 'Allow to view topic created by others',
		'view_post_closed'		=> 'Allow to view closed posts',
		'post_new_topics'		=> 'Allow to create new topics',
		'reply_own_topics'		=> 'Allow to reply inside own topics',
		'reply_other_topics'	=> 'Allow to reply inside others topics',
		'delete_own_topics'		=> 'Allow to delete own topics',
		'delete_other_topics'	=> 'Allow to delete others topics',
		'edit_own_topics'		=> 'Allow to edit own topics',
		'edit_other_topics'		=> 'Allow to edit others topics',
		'open_topics'			=> 'Allow to open (change status) topics',
		'close_topics'			=> 'Allow to close (change status) topics',
		'pin_topics'			=> 'Allow to pin topics',
		'unpin_topics'			=> 'Allow to unpin topics',
		'move_topics'			=> 'Allow to move topics',
		'approve_topics'		=> 'Allow to approve topics',
		'unapprove_topics'		=> 'Allow to unapprove topics',
		'open_close_posts'		=> 'Allow to open/close posts',
		'delete_own_posts'		=> 'Allow to delete own posts',
		'delete_other_posts'	=> 'Allow to delete others posts',
		'edit_own_posts'		=> 'Allow to edit own posts',
		'edit_other_posts'		=> 'Allow to edit others posts',
		'move_posts'			=> 'Allow to move posts',
		'approve_posts'			=> 'Allow to approve posts',
		'unapprove_posts'		=> 'Allow to unapprove posts',
		'split_merge'			=> 'Allow to use split/merge topics',
		'edit_own_profile'		=> 'Allow to edit own profile',
		'edit_other_profile'	=> 'Allow to edit others profile',
		'use_search'			=> 'Allow to use search',
		'make_polls'			=> 'Allow to create Polls',
		'vote_polls'			=> 'Allow to vote in Polls',
//		'hide_from_list'		=> 'Hide from online users list',
//		'avatar_upload'			=> 'Allow uploading avatar',
//		'use_pm'				=> 'Allow to use Private Messages',
		'is_admin'				=> 'Apply all other Admin rights (IS_ADMIN)',
		'is_moderator'			=> 'Apply all other Moderator rights (IS_MODERATOR)',
	);
	/** @var array */
	public $_moderator_triggers = array(
		'view_ip'				=> 'Allow to view posters saved IP address',
		'delete_own_topics'		=> 'Allow to delete own topics',
		'delete_other_topics'	=> 'Allow to delete others topics',
		'edit_own_topics'		=> 'Allow to edit own topics',
		'edit_other_topics'		=> 'Allow to edit others topics',
		'open_topics'			=> 'Allow to open (change status) topics',
		'close_topics'			=> 'Allow to close (change status) topics',
		'pin_topics'			=> 'Allow to pin topics',
		'unpin_topics'			=> 'Allow to unpin topics',
		'move_topics'			=> 'Allow to move topics',
		'open_close_posts'		=> 'Allow to open/close posts',
		'delete_own_posts'		=> 'Allow to delete own posts',
		'delete_other_posts'	=> 'Allow to delete others posts',
		'edit_own_posts'		=> 'Allow to edit own posts',
		'edit_other_posts'		=> 'Allow to edit others posts',
		'move_posts'			=> 'Allow to move posts',
		'split_merge'			=> 'Allow to use split/merge topics',
		'edit_own_profile'		=> 'Allow to edit own profile',
		'edit_other_profile'	=> 'Allow to edit others profile',
		'make_polls'			=> 'Allow to create Polls',
		'vote_polls'			=> 'Allow to vote in Polls',
	);
	/** @var bool */
	public $ALLOW_FUTURE_POSTS		= 1;

	/**
	* Framework constructor
	*/
	function _init () {
		define('FORUM_IS_ADMIN', 1);
		if (!strlen($this->salt)) {
			$this->salt = substr(md5($_SERVER['HTTP_HOST'].'123456'), 0, 8);
		}
		$this->_forum_cats_array	= main()->get_data('forum_categories');
		$this->_forums_array		= main()->get_data('forum_forums');
		$this->_forum_groups		= main()->get_data('forum_groups');
		$this->_forum_moderators	= main()->get_data('forum_moderators');
		$this->_verify_session_vars();
		if ($this->BB_CODE) {
			$this->BB_OBJ = _class('bb_codes');
		}
		if ($this->BB_CODE && in_array($_GET['action'], array('view_topic'))) {
			$this->smiles = main()->get_data('smilies');
		}
		if ($this->SHOW_USER_RANKS && in_array($_GET['action'], array('view_topic'))) {
			$Q = db()->query('SELECT * FROM '.db('forum_ranks').'');// WHERE special=0
			while($A = db()->fetch_assoc($Q)) {
				$this->user_ranks[$A['id']] = $A;
			}
		}
		$this->_std_trigger = array(
			'1' => '<span class=positive>'.t('YES').'</span>',
			'0' => '<span class=negative>'.t('NO').'</span>',
		);
		$this->_active_select = array(
			'a' => '<b style="color:green;">'.t('Active').'</b>',
			'c' => '<b style="color:red;">'.t('Inactive').'</b>',
		);
		$this->_postings_select = array(
			'' => '<b style="color:green;">'.t('Open').'</b>',
			'2' => '<b style="color:red;">'.t('Closed').'</b>',
		);
	}

	/**
	* Catch _ANY_ call to the class methods (yf special hook)
	*/
	function _module_action_handler($called_action = '') {
		if (!$this->_check_acl($called_action)) {
			return 'Access denied';
		}
		$body = $this->$called_action();
		return $body;
	}

	/**
	* Show forum layout (default function)
	*/
	function show () {
		$last_posts	= $this->_create_last_posts('forum');
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$cat_info = db()->query_fetch('SELECT * FROM '.db('forum_categories').' WHERE id='.$_GET['id'].' ORDER BY `order`');
			if ($cat_info['id']) {
				$body = $this->_show_category_contents($cat_info['id']);
			}
		} else {
			if (count($this->_forum_cats_array)) {
				foreach ((array)$this->_forum_cats_array as $_cat_info) {
					$body .= $this->_show_category_contents($_cat_info['id']);
				}
			} else {
				$body = $this->_show_error(t('no_categories'));
			}
		}
		$body .= tpl()->parse('forum/admin/button_add_category');
		return $this->_show_main_tpl($body);
	}

	/**
	*/
	function _show_category_contents ($cat_id = 0) {
		foreach ((array)$this->_forums_array as $_forum_info) {
			if ($_forum_info['category'] != $cat_id) {
				continue;
			}
			if (!empty($_forum_info['parent'])) {
				continue;
			}
			$forums .= $this->_show_forum_item($_forum_info);
		}
		$cat_details = $this->_forum_cats_array[$cat_id];
		$replace = array(
			'cat_name'			=> $this->_forum_cats_array[$cat_id]['name'],
			'cat_link'			=> './?object='.$_GET['object'].'&id='.$cat_id._add_get(array('id')),
			'forums'			=> $forums,
			'activity'			=> $this->_active_select[$cat_details['status']],
			'is_active'			=> $cat_details['status'] == 'a' ? 1 : 0,
			'edit_link'			=> './?object='.$_GET['object'].'&action=edit_category&id='.$cat_id,
			'delete_link'		=> './?object='.$_GET['object'].'&action=delete_category&id='.$cat_id,
			'add_link'			=> './?object='.$_GET['object'].'&action=add_forum&id='.$cat_id,
			'active_link'		=> './?object='.$_GET['object'].'&action=change_category_activity&id='.$cat_id,
			'future_topic_link'	=> module('forum')->ALLOW_FUTURE_POSTS ? './?object='.$_GET['object'].'&action=add_future_topic&id=c_'.$cat_id : '',
		);
		return tpl()->parse('forum/admin/category_main', $replace);
	}

	/**
	*/
	function _show_forum_item ($forum_info = array()) {
		return _class('manage_forum_manage_view', 'admin_modules/manage_forum/')->_show_forum_item($forum_info);
	}

	/**
	*/
	function view_forum () {
		return _class('manage_forum_manage_view', 'admin_modules/manage_forum/')->_view_forum();
	}

	/**
	*/
	function view_topic () {
		return _class('manage_forum_manage_view', 'admin_modules/manage_forum/')->_view_topic();
	}

	/**
	*/
	function new_topic () {
		return _class('manage_forum_manage_view', 'admin_modules/manage_forum/')->_new_topic();
	}

	/**
	*/
	function reply () {
		return _class('manage_forum_manage_view', 'admin_modules/manage_forum/')->_reply();
	}

	/**
	*/
	function search () {
		return $this->_show_main_tpl(tpl()->parse('forum/search_form'));
	}

	/**
	* Process main template
	*/
	function _show_main_tpl($items = '') {
		if ($_GET['action'] == 'show') 				$type = 'main';
		elseif ($_GET['action'] == 'view_forum')	$type = 'forum';
		elseif ($_GET['action'] == 'view_topic')	$type = 'topic';
		$replace = array(
			'user_name'			=> main()->USER_ID ? $this->USER_NAME : t('Guest'),
			'menu'				=> tpl()->parse('forum/menu_member'),
			'items'				=> $items,
			'version'			=> $this->VERSION,
			'totals'			=> '',
			'manage_groups_link'=> './?object='.$_GET['object'].'&action=manage_groups'._add_get(array('id')),
			'manage_mods_link'	=> './?object='.$_GET['object'].'&action=manage_moderators'._add_get(array('id')),
			'forum_users_link'	=> './?object='.$_GET['object'].'&action=manage_users'._add_get(array('id')),
			'forum_posters_link'=> './?object='.$_GET['object'].'&action=show_forum_posters'._add_get(array('id')),
			'future_posts_link'	=> './?object='.$_GET['object'].'&action=show_future_posts'._add_get(array('id')),
			'sync_board_link'	=> FORUM_IS_ADMIN == 1 ? './?object='.$_GET['object'].'&action=sync_board'._add_get(array('id')) : '',
		);
		return tpl()->parse('forum/main', $replace);
	}

	/**
	* Show error message
	*/
	function _show_error($text = '') {
		if (!strlen($text)) {
			$text = t('error');
		}
		return tpl()->parse('manage_forum/error', array('text' => $text));
	}

	/**
	* Count topic view
	*/
	function _add_topic_view($topic_array = array()) {
		db()->query('UPDATE '.db('forum_topics').' SET num_views = num_views + 1 WHERE id='.intval($topic_array['id']));
		db()->query('UPDATE '.db('forum_forums').' SET num_views = num_views + 1 WHERE id='.intval($topic_array['forum']));
	}

	/**
	* Create an array of last posts for forums and topics (unified function)
	*/
	function _create_last_posts ($type = 'forum') {
		if ($type == 'forum') {
			foreach ((array)$this->_forums_array as $_forum_info) {
				$this->last_posts[$_forum_info['id']] = t('no_posts');
				if ($_forum_info['last_post_id']) {
					$add_sql .= $_forum_info['last_post_id'].',';
				}
			}
		} elseif ($type == 'topic') {
			foreach ((array)$this->topics as $_topic_info) {
				$this->last_posts[$_topic_info['id']] = t('no_posts');
				if ($_topic_info['last_post_id']) {
					$add_sql .= $_topic_info['last_post_id'].',';
				}
			}
		}
		if (strlen($add_sql)) {
			$Q = db()->query('SELECT * FROM '.db('forum_posts').' WHERE id IN('.substr($add_sql, 0, -1).')');
			while($post_info = db()->fetch_assoc($Q)) {
				$user_name = $post_info['user_id'] ? (strlen($post_info['user_name']) ? $post_info['user_name'] : $post_info['user_id']) : (strlen($post_info['user_name']) ? $post_info['user_name'] : t('Anonymous'));
				$replace = array(
					'user_name'		=> $user_name,
					'profile_link'	=> $this->_user_profile_link($post_info),
					'time'			=> date($this->format['time'], $post_info['created']),
					'date'			=> date($this->format['date'], $post_info['created']),
					'topic'			=> '<a href="./?object='.$_GET['object'].'&action=view_topic&id='.$post_info['topic']._add_get(array('id')).'">'.(_substr($post_info['subject'], 0, 33).'...').'</a>'.PHP_EOL,
					'post'			=> '<a href="./?object='.$_GET['object'].'&action=view_topic&id='.$post_info['topic']._add_get(array('id')).'">'.(_substr($post_info['subject'], 0, 33).'...').'</a>'.PHP_EOL,
				);
				$this->last_posts[$post_info['id']] = tpl()->parse('manage_forum/last_post_'.$type, $replace);
			}
		}
	}

	/**
	* Get unique users for the current topic
	*/
	function _get_topic_users () {
		foreach ((array)$this->posts as $k => $v) {
			$this->users[$v['user_id']] = 1;
		}
		foreach ((array)$this->users as $k => $v) {
			$add_sql .= $k.',';
		}
		if (strlen($add_sql)) {
			$Q = db()->query('SELECT * FROM '.db('forum_users').' WHERE id IN('.substr($add_sql, 0, -1).')');
			while($A = db()->fetch_assoc($Q)) {
				$this->users[$A['id']] = $A;
			}
		}
	}

	/**
	* Show icon for new messages if exists some
	*/
	function _forum_new_msg ($forum_id) {
		return main()->USER_ID ? (is_array($this->topic_watch) && in_array($forum_id, $this->topic_watch) ? 'N' : '-') : '-';
	}

	/**
	* Show icon for new messages if exists some
	*/
	function _topic_new_msg ($topic_id) {
		return main()->USER_ID ? (is_array($this->topic_watch) && array_key_exists($topic_id, $this->topic_watch) ? 'N' : '-') : '-';
	}

	/**
	*/
	function _verify_session_vars () {
		main()->USER_ID	= $_SESSION['admin_id'];
		$this->GROUP_ID = $_SESSION['admin_group'];

		$admin_info = db()->query_fetch('SELECT * FROM '.db('admin').' WHERE id='.intval(main()->USER_ID));

		$this->USER_NAME = $admin_info['first_name'].' '.$admin_info['last_name'];
		$admin_groups = main()->get_data('admin_groups');
		$this->GROUP_NAME = $admin_groups[$this->GROUP_ID];
	}

	/**
	*/
	function _update_forum_record ($forum_id = 0) {
		if (!$forum_id) {
			return false;
		}
		db()->update_safe('forum_forums', array(
			'num_topics'	=> (int)db()->get_one('SELECT COUNT(id) FROM '.db('forum_topics').' WHERE forum='.(int)$forum_id.' AND status="a"'),
			'num_posts'		=> (int)db()->get_one('SELECT COUNT(id) FROM '.db('forum_posts').' WHERE forum='.(int)$forum_id.' AND status="a"'),
			'last_post_id'	=> (int)db()->get_one('SELECT id FROM '.db('forum_posts').' WHERE forum='.(int)$forum_id.' AND status="a" ORDER BY created DESC LIMIT 1'),
		), (int)$forum_id);
	}

	/**
	* 
	*/
	function _update_topic_record ($topic_id = 0) {
		if (!$topic_id) {
			return false;
		}
		db()->update_safe('forum_topics', array(
			'num_posts'		=> db()->get_one('SELECT COUNT(id) FROM '.db('forum_posts').' WHERE topic='.(int)$topic_id.' AND status="a"'),
			'last_post_id'	=> db()->get_one('SELECT id FROM '.db('forum_posts').' WHERE topic='.(int)$topic_id.' AND status="a" ORDER BY created DESC LIMIT 1'),
		), (int)$topic_id);
	}

	/**
	* Prepare link to user's profile
	*/
	function _user_profile_link ($user_info = '', $user_name = '') {
		if (is_array($user_info)) {
			$user_id	= intval($user_info['user_id']);
			$user_name	= $user_info['user_name'];
		} else {
			$user_id	= intval($user_info);
		}
		$url = './?object='.$_GET['object'].'&action=view_profile&id='.$user_id;
		return $user_id ? '<a class="forum_profile_link" yf:user_id="'.$user_id.'" href="'.$url.'" target="_blank">'.(strlen($user_name) ? $user_name : $user_id).'</a>' : (strlen($user_name) ? $user_name : t('Anonymous'));
	}

	/**
	* 
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
	* 
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
	* View user's profile
	*/
	function view_profile () {
		$_GET['id'] = intval($_GET['id']);
		$user_info = db()->query_fetch('SELECT * FROM '.db('forum_users').' WHERE id='.$_GET['id']);
		if ($user_info['id'] && !$this->HIDE_USERS_INFO) {
			$replace = $user_info;
			$replace['user_regdate'] = date($this->format['date'], $replace['user_regdate']);
			// Send Private message link
			$replace['pm_link'] = '';
			$body .= tpl()->parse('forum/view_profile', $replace);
		} else {
			$body .= $this->_show_error(t('no_such_user'));
		}
		return $this->_show_main_tpl($body);
	}

	//################# ADMINISTRATION METHODS #################//

	/**
	*/
	function _prepare_parents_for_select ($skip_id = 0) {
		$forums = array();
		foreach ((array)$this->_forum_cats_array as $_cat_id => $_cat_info) {
			$_cat_name = $_cat_info['name'];
			$forums['c_'.$_cat_id] = '######## '. $_cat_name;
			foreach ((array)$this->_prepare_forums_for_select($skip_id, $_cat_id) as $k => $v) {
				$forums[$k] = $v;
			}
		}
		return $forums;
	}

	/**
	*/
	function _prepare_forums_for_select ($skip_id = 0, $cat_id = 0, $parent_id = 0, $level = 0) {
		$forums = array();
		$func_name = __FUNCTION__;
		foreach ((array)module('forum')->_forums_array as $_info) {
			if ($_info['id'] == $skip_id) {
				continue;
			}
			if ($_info['parent'] != $parent_id) {
				continue;
			}
			if ($cat_id && $cat_id != $_info['category']) {
				continue;
			}
			$forums[$_info['id']] = str_repeat('&nbsp;', $level * 4). '&#0124;---'. $_info['name'];
			foreach ((array)$this->$func_name($skip_id, $cat_id, $_info['id'], $level + 1) as $_sub_id => $_sub_name) {
				$forums[$_sub_id] = $_sub_name;
			}
		}
		return $forums;
	}

	/**
	* Admin: edit category
	*/
	function edit_category () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_edit_category();
	}

	/**
	* Admin: add category
	*/
	function add_category () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_add_category();
	}

	/**
	* Delete category
	*/
	function delete_category () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_delete_category();
	}

	/**
	* Admin: edit forum
	*/
	function edit_forum () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_edit_forum();
	}

	/**
	* Admin: add forum
	*/
	function add_forum () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_add_forum();
	}

	/**
	* Admin: delete forum
	*/
	function delete_forum () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_delete_forum();
	}

	/**
	* Admin: edit topic
	*/
	function edit_topic () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_edit_topic();
	}

	/**
	* Admin: delete topic
	*/
	function delete_topic () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_delete_topic();
	}

	/**
	* Admin: edit post
	*/
	function edit_post () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_edit_post();
	}

	/**
	* Admin: delete post
	*/
	function delete_post () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_delete_post();
	}

	/**
	* Admin: manage users
	*/
	function manage_users () {
		return _class('forum_manage_users', 'admin_modules/manage_forum/')->_manage_users();
	}

	/**
	* Admin: edit user
	*/
	function edit_user () {
		return _class('forum_manage_users', 'admin_modules/manage_forum/')->_edit_user();
	}

	/**
	* Admin: manage group
	*/
	function manage_groups () {
		return _class('forum_manage_users', 'admin_modules/manage_forum/')->_manage_groups();
	}

	/**
	* Admin: edit group
	*/
	function edit_group () {
		return _class('forum_manage_users', 'admin_modules/manage_forum/')->_edit_group();
	}

	/**
	* Admin: add group
	*/
	function add_group () {
		return _class('forum_manage_users', 'admin_modules/manage_forum/')->_add_group();
	}

	/**
	* Admin: delete group
	*/
	function delete_group () {
		return _class('forum_manage_users', 'admin_modules/manage_forum/')->_delete_group();
	}

	/**
	* Admin: clone group
	*/
	function clone_group () {
		return _class('forum_manage_users', 'admin_modules/manage_forum/')->_clone_group();
	}

	/**
	* Admin: manage moderators
	*/
	function manage_moderators () {
		return _class('forum_manage_users', 'admin_modules/manage_forum/')->_manage_moderators();
	}

	/**
	* Admin: edit moderator
	*/
	function edit_moderator () {
		return _class('forum_manage_users', 'admin_modules/manage_forum/')->_edit_moderator();
	}

	/**
	* Admin: add moderator
	*/
	function add_moderator () {
		return _class('forum_manage_users', 'admin_modules/manage_forum/')->_add_moderator();
	}

	/**
	* Admin: delete moderator
	*/
	function delete_moderator () {
		return _class('forum_manage_users', 'admin_modules/manage_forum/')->_delete_moderator();
	}

	/**
	* Change activity status
	*/
	function change_category_activity () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_change_category_activity();
	}

	/**
	* Change activity status
	*/
	function change_forum_activity () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_change_forum_activity();
	}

	/**
	* Change activity status
	*/
	function change_topic_activity () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_change_topic_activity();
	}

	/**
	* Change activity status
	*/
	function change_post_activity () {
		return _class('forum_manage_main', 'admin_modules/manage_forum/')->_change_post_activity();
	}

	/**
	* Admin: synchronize board
	*/
	function sync_board () {
		return _class('forum_sync', 'modules/forum/')->_sync_board(true);
	}

	/**
	* Check permissions (return false if denied, true if allowed)
	*/
	function _check_acl($action = '') {
		$GID = $_SESSION['admin_group'];
		// Admin allowed to do anything
		if ($GID == 1) {
			return true;
		}
		// Forum poster allowed actions
		if ($GID == 6 && !in_array($action, array(
			'edit_post',
			'new_topic',
			'reply',
			'show',
			'view_forum',
			'view_topic',
		))) {
			return false;
		}
		return true;
	}

	/**
	*/
	function show_future_posts() {
		return _class('forum_manage_future', 'admin_modules/manage_forum/')->_show_future_posts();
	}

	/**
	*/
	function add_future_topic() {
		return _class('forum_manage_future', 'admin_modules/manage_forum/')->_add_topic();
	}

	/**
	*/
	function add_future_post() {
		return _class('forum_manage_future', 'admin_modules/manage_forum/')->_add_post();
	}

	/**
	*/
	function edit_future_post() {
		return _class('forum_manage_future', 'admin_modules/manage_forum/')->_edit_future_post();
	}

	/**
	*/
	function delete_future_post() {
		return _class('forum_manage_future', 'admin_modules/manage_forum/')->_delete_future_post();
	}

	/**
	*/
	function show_forum_posters() {
		return _class('forum_manage_future', 'admin_modules/manage_forum/')->_show_posters();
	}

	/**
	*/
	function edit_forum_poster() {
		return _class('forum_manage_future', 'admin_modules/manage_forum/')->_edit_poster();
	}

	/**
	*/
	function show_poster_stats() {
		return _class('forum_manage_future', 'admin_modules/manage_forum/')->_show_poster_stats();
	}

	/**
	*/
	function future_save_filter() {
		return _class('forum_manage_future', 'admin_modules/manage_forum/')->_save_filter();
	}

	/**
	*/
	function future_clear_filter() {
		return _class('forum_manage_future', 'admin_modules/manage_forum/')->_clear_filter();
	}

	/**
	*/
	function _future_posts_cron_job() {
		return _class('forum_manage_future', 'admin_modules/manage_forum/')->_do_cron_job();
	}

	/**
	* Placeholder for compatibility with user section
	*/
	function _for_user_profile() {
		return '';
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				'name'	=> ucfirst($_GET['object']).' main',
				'url'	=> './?object='.$_GET['object'],
			),
			array(
				'name'	=> 'Manage forum groups',
				'url'	=> './?object='.$_GET['object'].'&action=manage_groups',
			),
			array(
				'name'	=> 'Manage moderators',
				'url'	=> './?object='.$_GET['object'].'&action=manage_moderators',
			),
			array(
				'name'	=> 'Resynchronize Board',
				'url'	=> './?object='.$_GET['object'].'&action=sync_board',
			),
			array(
				'name'	=> 'Forum Posters',
				'url'	=> './?object='.$_GET['object'].'&action=show_forum_posters',
			),
			array(
				'name'	=> 'Future Posts',
				'url'	=> './?object='.$_GET['object'].'&action=show_future_posts',
			),
			array(
				'name'	=> 'Add category',
				'url'	=> './?object='.$_GET['object'].'&action=add_category',
			),
			array(
				'name'	=> '',
				'url'	=> './?object='.$_GET['object'],
			),
		);
		return $menu;	
	}

	function _hook_widget__forum_stats ($params = array()) {
// TODO
	}

	function _hook_widget__forums_list ($params = array()) {
// TODO
	}

	function _hook_widget__latest_topics ($params = array()) {
// TODO
	}

	function _hook_widget__latest_posts ($params = array()) {
// TODO
	}

	function _hook_widget__latest_posters ($params = array()) {
// TODO
	}

	function _hook_widget__top_users ($params = array()) {
// TODO
	}
}
