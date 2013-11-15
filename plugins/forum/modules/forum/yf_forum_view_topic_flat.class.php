<?php

/**
* Show topic contents (flat-style)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_view_topic_flat {

	/**
	* Constructor
	*/
	function _init () {
		// Apply moderator rights here
		if (FORUM_IS_MODERATOR) {
			module('forum')->_apply_moderator_rights();
		}
	}
	
	/**
	* Show Main
	*/
	function _show_main() {
		$_GET['id'] = intval($_GET['id']);
		// Show only one post if specified
		if (!empty($GLOBALS['show_only_post_id'])) {
			if (!empty($_GET['id'])) {
				$this->_post_info = db()->query_fetch('SELECT * FROM '.db('forum_posts').' WHERE id='.intval($_GET['id']));
			}
			if (empty($this->_post_info['id'])) {
				return module('forum')->_show_error('No such post!');
			}
			$topic_id = $this->_post_info['topic'];
		} else {
			$topic_id = $_GET['id'];
		}
		if (!empty($topic_id)) {
			$this->_topic_info = db()->query_fetch('SELECT * FROM '.db('forum_topics').' WHERE id='.intval($topic_id).' '.(!FORUM_IS_ADMIN ? ' AND approved=1 ' : '').' LIMIT 1');
			module('forum')->_topic_info = $this->_topic_info;
		}
		if (empty($this->_topic_info['id'])) {
			return module('forum')->_show_error('No such topic!');
		}
		if (!module('forum')->USER_RIGHTS['view_other_topics'] && FORUM_USER_ID != $this->_topic_info['user_id']) {
			return module('forum')->_show_error('You cannot view topics except those ones you have started!');
		}
		if (!module('forum')->USER_RIGHTS['view_post_closed'] && $this->_topic_info['status'] != 'a') {
			return module('forum')->_show_error('You cannot view closed topics!');
		}
		$this->_forum_info	= &module('forum')->_forums_array[$this->_topic_info['forum']];
		$this->_cat_info	= &module('forum')->_forum_cats_array[$this->_forum_info['category']];
		// Skip non-active forums and categories
		if ($this->_forum_info['status'] != 'a' || $this->_cat_info['status'] != 'a') {
			return module('forum')->_show_error('Forum is inactive!');
		}
		// Check user group access rights to the current forum
		$only_for_groups = $this->_forum_info['user_groups'] ? explode(',', $this->_forum_info['user_groups']) : '';
		if (!empty($only_for_groups) && !in_array(FORUM_USER_GROUP_ID, $only_for_groups) && !FORUM_IS_ADMIN) {
			return module('forum')->_show_error('Private Forum!');
		}
		// Count user view
		db()->_add_shutdown_query('UPDATE '.db('forum_topics').' SET num_views=num_views+1 WHERE id='.intval($this->_topic_info['id']));
		// Add read topic record
		if (FORUM_USER_ID && module('forum')->SETTINGS['USE_READ_MESSAGES']) {
			module('forum')->_set_topic_read($this->_topic_info);
		}
		$posts_per_page = !empty(module('forum')->USER_SETTINGS['POSTS_PER_PAGE']) ? module('forum')->USER_SETTINGS['POSTS_PER_PAGE'] : module('forum')->SETTINGS['NUM_POSTS_ON_PAGE'];
		$path = './?object='.'forum'.'&action=view_topic&id='.$this->_topic_info['id'];

		$order_by = ' ORDER BY created ASC ';
		$sql = 'SELECT * FROM '.db('forum_posts').' WHERE topic='.$this->_topic_info['id'];
		// For user hide unapproved topics
		$sql .= !FORUM_IS_ADMIN ? ' AND status="a" ' : '';
		// Limit to display only one post
		if (!empty($GLOBALS['show_only_post_id']) && !empty($this->_post_info['id'])) {
			$sql .= ' AND id='.intval($this->_post_info['id']).' ';
			$Q = db()->query($sql. $order_by. $add_sql);
			while ($A = db()->fetch_assoc($Q)) {
				$this->_posts_array[] = $A;
			}
		} else {
			// Optimized vesion for MySQL >= 4.1.x
			if (module('forum')->SETTINGS['USE_OPTIMIZED_SQL'] 
				&& false !== strpos(db()->DB_TYPE, 'mysql') 
			) {
				$first_record = intval(($_GET['page'] ? $_GET['page'] - 1 : 0) * $posts_per_page);
				if ($first_record < 0) {
					$first_record = 0;
				}
				$sql .= $order_by.' LIMIT '.intval($first_record).','.intval($posts_per_page);
				// Get ids
				$Q = db()->query(str_replace('SELECT *', 'SELECT SQL_CALC_FOUND_ROWS id', $sql));
				while ($A = db()->fetch_assoc($Q)) {
					$this->_posts_array[$A['id']] = $A['id'];
				}
				if (!empty($this->_posts_array)) {
					$topic_num_posts = (int)db()->get_one('SELECT FOUND_ROWS()');
					list(, $topic_pages, ) = common()->divide_pages(null, $path, null, $posts_per_page, $topic_num_posts, 'forum/pages_1/');
					if (!empty($topic_num_posts)) {
						$Q = db()->query('SELECT * FROM '.db('forum_posts').' WHERE id IN('.implode(',', array_keys($this->_posts_array)).')');
						while ($A = db()->fetch_assoc($Q)) {
							$this->_posts_array[$A['id']] = $A;
						}
					}
				}
			// Common version
			} else {
				list($add_sql, $topic_pages, $topic_num_posts) = common()->divide_pages(str_replace('SELECT * ', 'SELECT id ', $sql), $path, null, $posts_per_page, null, 'forum/pages_1/');
				// Get posts info
				$Q = db()->query($sql. $order_by. $add_sql);
				while ($A = db()->fetch_assoc($Q)) {
					$this->_posts_array[] = $A;
				}
			}
		}
		// Get topic users info
		foreach ((array)$this->_posts_array as $post_info) {
			if (empty($post_info['user_id'])) {
				continue;
			}
			$users_ids[$post_info['user_id']] = $post_info['user_id'];
		}
		// Process users
		if (!empty($users_ids)) {
			$this->_users_array = module('forum')->_get_users_infos($users_ids);
		}
		// Init post item object
		if (!empty($this->_posts_array)) {
			$POST_ITEM_OBJ = _class('forum_post_item', 'modules/forum/');
		}
		// Set required params
		$forum_is_closed	= $this->_forum_info['options'] == '2' ? 1 : 0;
		$topic_is_closed	= intval($this->_topic_info['status'] != 'a');
		$allow_reply		= intval(!$forum_is_closed && !$topic_is_closed);
		$allow_new_topic	= !$forum_is_closed && module('forum')->USER_RIGHTS['post_new_topics'];
		$allow_new_poll		= $allow_new_topic && module('forum')->SETTINGS['ALLOW_POLLS'] && module('forum')->USER_RIGHTS['make_polls'];
		// Additional rights checkin
		if (FORUM_USER_ID && $this->_topic_info['user_id'] == FORUM_USER_ID && module('forum')->USER_RIGHTS['reply_own_topics']) {
			$allow_reply = 1;
		}
		if (((FORUM_USER_ID && $this->_topic_info['user_id'] != FORUM_USER_ID) || !FORUM_USER_ID) && module('forum')->USER_RIGHTS['reply_other_topics']) {
			$allow_reply = 1;
		}
		// Deny guests posting (if needed)
		if (!FORUM_USER_ID && !module('forum')->SETTINGS['ALLOW_GUESTS_POSTS']) {
			$allow_reply		= 0;
			$allow_new_topic	= 0;
		}
		$use_fast_reply		= intval(module('forum')->SETTINGS['USE_FAST_REPLY'] && $allow_reply);
		$use_topic_options	= intval(FORUM_USER_ID && module('forum')->SETTINGS['USE_TOPIC_OPTIONS'] && $allow_reply);
		// Process users reputation
		$REPUT_OBJ = module('reputation');
		if (is_object($REPUT_OBJ)) {
			$users_reput_info	= $REPUT_OBJ->_get_reput_info_for_user_ids($users_ids);
			foreach ((array)$users_reput_info as $reput_user_id => $reput_info) {
				module('forum')->_reput_texts[$reput_user_id] = $REPUT_OBJ->_show_for_user($reput_user_id, $users_reput_info[$reput_user_id]);
			}
		}
		// Process posts
		if (is_object($POST_ITEM_OBJ)) {
			foreach ((array)$this->_posts_array as $post_info) {
				if (!empty($users_reput_info)) {
					module('forum')->_reput_texts_for_posts[$post_info['id']] = $REPUT_OBJ->_show_for_user($post_info['user_id'], $users_reput_info[$post_info['user_id']], false, array('forum_posts', $post_info['id']));
				}
				$user_info		= $this->_users_array[$post_info['user_id']];
				$is_first_post	= $this->_topic_info['first_post_id'] != $post_info['id'];
				$topic_posts	.= $POST_ITEM_OBJ->_show_post_item($post_info, $user_info, null, '/view_topic_flat/post_item', $is_first_post, $allow_reply);
				if (!empty($users_reput_info)) {
					unset(module('forum')->_reput_texts_for_posts[$post_info['id']]);
				}
			}
		}
		$STATS_OBJ = _class('forum_stats', 'modules/forum/');
		$poll = '';
		if (module('forum')->SETTINGS['ALLOW_POLLS']) {
			$POLL_OBJ = module('poll');
			$_method = $GLOBALS['POLL_ONLY_RESULTS'] ? 'view' : 'show';
			$poll = is_object($POLL_OBJ) ? $POLL_OBJ->$_method(array(
				'silent'		=> 1,
				'form_action'	=> './?object='.$_GET['object'].'&action=poll_vote&id='.intval($this->_topic_info['id']),
				'stpl_main'		=> $_GET['object'].'/poll_vote',
				'stpl_view'		=> $_GET['object'].'/poll_results',
				'results_link'	=> './?object='.$_GET['object'].'&action=poll_results&id='.intval($this->_topic_info['id']),
				'object_name'	=> 'forum',
				'object_id'		=> intval($topic_id),
			)) : '';
		}
		// Process template
		$replace = array(
			'is_admin'			=> intval(FORUM_IS_ADMIN),
			'is_moderator'		=> intval(FORUM_IS_ADMIN || (FORUM_IS_MODERATOR && module('forum')->_moderate_forum_allowed($this->_forum_info['id']))),
			'cat_link'			=> './?object='.'forum'._add_get(array('page')),
			'forum_link'		=> module('forum')->_link_to_forum($this->_topic_info['forum']),
			'topic_link'		=> './?object='.'forum'.'&action=view_topic&id='.$this->_topic_info['id']._add_get(array('page')),
			'new_topic_link'	=> $allow_new_topic ? './?object='.'forum'.'&action=new_topic&id='.$this->_topic_info['forum']._add_get(array('page')) : '',
			'new_poll_link'		=> $allow_new_poll ? './?object='.'forum'.'&action=new_poll&id='.$this->_topic_info['forum']._add_get(array('page')) : '',
			'add_post_link'		=> $allow_reply ? './?object='.'forum'.'&action=new_post&id='.$this->_topic_info['id']._add_get() : '',
			'track_topic_link'	=> FORUM_USER_ID && module('forum')->SETTINGS['ALLOW_TRACK_TOPIC'] ? './?object='.'forum'.'&action=subscribe_topic&id='.$this->_topic_info['id']._add_get() : '',
			'email_topic_link'	=> FORUM_USER_ID && module('forum')->SETTINGS['ALLOW_EMAIL_TOPIC'] ? './?object='.'forum'.'&action=email_topic&id='.$this->_topic_info['id']._add_get() : '',
			'print_topic_link'	=> module('forum')->SETTINGS['ALLOW_PRINT_TOPIC'] ? './?object='.'forum'.'&action=print_topic&id='.$this->_topic_info['id']._add_get() : '',
			'cat_name'			=> _prepare_html($this->_cat_info['name']),
			'forum_id'			=> $this->_forum_info['id'],
			'forum_name'		=> _prepare_html($this->_forum_info['name']),
			'topic_id'			=> $this->_topic_info['id'],
			'topic_name'		=> _prepare_html($this->_topic_info['name']),
			'topic_pages'		=> $topic_pages,
			'posts'				=> $topic_posts,
			'tree_view_link'	=> './?object='.'forum'.'&action=change_topic_view&id=1'._add_get(),
			'flat_view_link'	=> './?object='.'forum'.'&action=change_topic_view&id=2'._add_get(),
			'link_to_post_base'	=> process_url('./?object='.'forum'.'&action=view_post&id=0'._add_get(array('page'))),
			'board_fast_nav'	=> module('forum')->SETTINGS['ALLOW_FAST_JUMP_BOX'] ? module('forum')->_board_fast_nav_box() : '',
			'topic_online'		=> is_object($STATS_OBJ) ? $STATS_OBJ->_show_topic_stats() : '',
			'search_form_action'=> module('forum')->USER_RIGHTS['use_search'] && module('forum')->SETTINGS['ALLOW_SEARCH'] ? './?object='.'forum'.'&action=search'. _add_get() : '',
			'forum_closed'		=> intval($forum_is_closed),
			'topic_closed'		=> !$forum_is_closed ? $topic_is_closed : '',
			'use_fast_reply'	=> $use_fast_reply,
			'use_topic_options'	=> $use_topic_options,
			'fast_reply_form'	=> $use_fast_reply ? $this->_show_fast_reply_form() : '',
			'topic_options_form'=> $use_topic_options ? $this->_show_topic_options_form() : '',
			'mod_options_box'	=> FORUM_IS_ADMIN && module('forum')->SETTINGS['SHOW_TOPIC_MOD_BOX']? $this->_show_topic_mod_box() : '',
			'p_act_box'			=> FORUM_IS_ADMIN || FORUM_IS_MODERATOR ? $this->_p_act_box() : '',
			'rss_topic_button'	=> module('forum')->_show_rss_link('./?object='.'forum'.'&action=rss_forum&id='.$this->_topic_info['forum'], 'RSS feed for topic: '.$this->_topic_info['name']),
			'allow_change_view'	=> intval((bool) module('forum')->SETTINGS['ALLOW_CHANGE_TOPIC_VIEW']),
			'poll'				=> $poll,
		);
		// Administration methods
		if (FORUM_IS_ADMIN || FORUM_IS_MODERATOR) {
			$replace = array_merge($replace, array(
				'admin_action'	=> './?object='.'forum'.'&action=admin&id='.$this->_topic_info['id']._add_get(array('page')),
			));
		}
		return module('forum')->_show_main_tpl(tpl()->parse('forum/view_topic_flat/main', $replace));
	}
	
	/**
	* Show Fast Reply Form
	*/
	function _show_fast_reply_form() {
		$replace = array(
			'post_form_action'	=> './?object='.'forum'.'&action=save_post&id='.$_GET['id']. _add_get(),
			'topic_id'			=> intval($this->_topic_info['id']),
			'forum_id'			=> intval($this->_forum_info['id']),
			'act_name' 			=> 'new_post',
		);
		return tpl()->parse('forum/view_topic_flat/fast_reply', $replace);
	}
	
	/**
	* Show Topic Options Form
	*/
	function _show_topic_options_form() {
		$replace = array(
			'track_topic_link'		=> FORUM_USER_ID ? './?object='.'forum'.'&action=subscribe_topic&id='.$this->_topic_info['id']._add_get() : '',
			'subscribe_forum_link'	=> FORUM_USER_ID ? './?object='.'forum'.'&action=subscribe_forum&id='.$this->_forum_info['id']._add_get() : '',
		);
		return tpl()->parse('forum/view_topic_flat/topic_options', $replace);
	}
	
	/**
	* Show Topic Mod Box
	*/
	function _show_topic_mod_box() {
		$replace = array(
			'admin_action'	=> './?object='.'forum'.'&action=admin&id='.$this->_topic_info['id']. _add_get(array('page')),
			'topic_id'		=> intval($this->_topic_info['id']),
			'forum_id'		=> intval($this->_forum_info['id']),
		);
		return tpl()->parse('forum/view_topic_flat/topic_mod_box', $replace);
	}

	/**
	* Post Act Box
	*/
	function _p_act_box ($name = 'p_act') {
		if (module('forum')->USER_RIGHTS['split_merge'])		$p_actions['merge']		= t('Merge Posts');
		if (module('forum')->USER_RIGHTS['move_posts'])			$p_actions['move']		= t('Move Posts');
		if (module('forum')->USER_RIGHTS['delete_other_posts'])	$p_actions['delete']	= t('Delete Posts');
		if (module('forum')->USER_RIGHTS['split_merge'])		$p_actions['split']		= t('Split Topic');
		if (module('forum')->USER_RIGHTS['approve_posts'])		$p_actions['approve']	= t('Set Visible').' ('.t('Approve Post').')';
		if (module('forum')->USER_RIGHTS['unapprove_posts'])	$p_actions['unapprove']	= t('Set Invisible').' ('.t('Unapprove Post').')';
		return !empty($p_actions) ? common()->select_box($name, $p_actions, '', 0, 2, '', false) : '';
	}
}
