<?php

/**
* Show topic contents
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_announce {
	
	/**
	* Show announce with given id
	*/
	function _view_announce() {
		$_GET['id'] = intval($_GET['id']);
		$announce_info = db()->query_fetch('SELECT * FROM '.db('forum_announce').' WHERE id='.$_GET['id'].' LIMIT 1');
		// Check if anounce is expired or not need to show yet
		if ($announce_info['start_time'] != 0 && time() < $announce_info['start_time']) {
			unset($announce_info);
		}
		if ($announce_info['end_time'] != 0 && time() > $announce_info['end_time']) {
			unset($announce_info);
		}
		// Check existance
		if (empty($announce_info['id']) || empty($announce_info['active'])) {
			return module('forum')->_show_error('No such announce!');
		}
		// Get user details
		$users_array = module('forum')->_get_users_infos(array($announce_info['author_id'] => $announce_info['author_id']));
		$user_info = $users_array[$announce_info['author_id']];
		// Get number of user's posts
		$user_num_posts = intval($user_info['user_posts']);
		// Get user avatar
		if (!empty($user_info['user_avatar'])) {
			$img_src = module('forum')->SETTINGS['AVATARS_DIR']. $user_info['user_avatar'];
			$user_avatar_src = file_exists(REAL_PATH. $img_src) ? WEB_PATH. $img_src : '';
		} else {
			$user_avatar_src = '';
		}
		// Process user ranks
		$rank_num = 0;
		$ranks_array = array();
		foreach ((array)module('forum')->_ranks_array as $rank_info) {
			if ($rank_info['special'] == 1) {
				continue;
			}
			$ranks_array[++$rank_num] = $rank_info;
		}
		// Get user rank
		if ($user_info['group'] == 3) {
			foreach ((array)$ranks_array as $rank_num => $rank_info) {
				if ($user_num_posts > $rank_info['min']) {
					$user_rank_name = $rank_info['title'];
					$user_rank_num	= $rank_num;
				}
			}
		} else {
			$user_rank_num	= 0;
			$user_rank_name = $user_group;
		}
		// Process template
		$replace = array(
			'announce_name'		=> $announce_info['title'],
			'announce_text'		=> _class('bb_codes')->_process_text($announce_info['post']),
			'end_date'			=> $announce_info['end_date'] ? module('forum')->_show_date($announce_info['end_date'], 'announce_date') : '',
			'user_name'			=> _prepare_html($user_info['name']),
			'user_profile_link'	=> !module('forum')->SETTINGS['HIDE_USERS_INFO'] ? process_url(module('forum')->_user_profile_link($announce_info['user_id'])) : '',
			'user_num_posts'	=> $user_num_posts,
			'user_group'		=> t(module('forum')->FORUM_USER_GROUPS[$user_info['group']]),
			'user_avatar_src'	=> $user_avatar_src,
			'user_rank_name'	=> $user_rank_name,
			'user_location'		=> $user_info['user_from'],
			'user_reg_date'		=> module('forum')->_show_date($user_info['user_regdate'], 'user_reg_date'),
		);
		return module('forum')->_show_main_tpl(tpl()->parse('forum'.'/view_announce_main', $replace));
	}
	
	/**
	* Manage announces main handler
	*/
	function _edit_main() {
		if (!FORUM_IS_ADMIN) {
			return module('forum')->_show_error('Not allowed!');
		}
		$_GET['id'] = intval($_GET['id']);
		// Init user cp module
		$this->USER_CP_OBJ = _class('forum_user', 'modules/forum/');
		// Switch between actions
		if (!empty($_POST['add']))		$content = $this->_add();
		elseif (!empty($_POST['edit']))	$content = $this->_edit();
		elseif (!empty($_POST['edit']))	$content = $this->_delete();
		else							$content = $this->_list();
		// Show main template
		return module('forum')->_show_main_tpl($this->USER_CP_OBJ->_user_cp_main_tpl($content));
	}
	
	/**
	* List
	*/
	function _list() {
		$Q = db()->query('SELECT * FROM '.db('forum_announce').'');
		while ($A = db()->fetch_assoc($Q)) {
			$announces[$A['id']] = $A;
		}
		foreach ((array)$announces as $ann_info) {
			$users_ids[$ann_info['author_id']] = $ann_info['author_id'];
		}
		if (is_array($users_ids)) {
			$users_array = module('forum')->_get_users_infos($users_ids);
		}
		foreach ((array)$users_array as $user_info) {
			$users_names[$user_info['id']] = $user_info['name'];
		}
		foreach ((array)$announces as $A) {
			$forums = array();
			if ($A['forum'] != '*') {
				$tmp_forums = explode(',', $A['forum']);
				foreach ((array)$tmp_forums as $forum_id) {
					if (empty($forum_id)) {
						continue;
					}
					$forums[$forum_id] = array(
						'forum_link'	=> module('forum')->_link_to_forum($forum_id),
						'forum_name'	=> module('forum')->_forums_array[$forum_id]['name'],
					);
				}
			}
			$replace2 = array(
				'css_class_1'		=> module('forum')->_CSS['topic_'.($A['active'] ? 'a' : 'u').'_1'],
				'form_action'		=> './?object='.'forum'.'&action='.$_GET['action'].'&id='.$A['id']._add_get(array('page')),
				'view_announce_link'=> './?object='.'forum'.'&action=view_announce&id='.$A['id']._add_get(array('page')),
				'announce_id'		=> $A['id'],
				'announce_title'	=> $A['title'],
				'creator_name'		=> $users_names[$A['author_id']],
				'start_date'		=> $A['start_time'] ? date('Y-m-d', $A['start_time']) : '',
				'end_date'			=> $A['end_time'] ? date('Y-m-d', $A['end_time']) : '',
				'all_forums'		=> intval($A['forum'] == '*'),
				'forums'			=> $forums,
			);
			$items .= tpl()->parse('forum'.'/user_cp/announces_list_item', $replace2);
		}
		$replace = array(
			'form_action'	=> './?object='.'forum'.'&action='.$_GET['action']._add_get(array('page')),
			'items'			=> $items,
		);
		return tpl()->parse('forum'.'/user_cp/announces_list_main', $replace);
	}
	
	/**
	* Add
	*/
	function _add() {
		if (!empty($_POST['save'])) {
			if ($_POST['forum'] == '*') {
				$forum = '*';
			} else {
				$forum = is_array($_POST['forum']) ? implode(',', $_POST['forum']) : intval($_POST['forum']);
			}
			db()->INSERT('forum_announce', array(
				'author_id'	=> intval(FORUM_USER_ID),
				'title'		=> _es($_POST['announce_title']),
				'post'		=> _es($_POST['announce_post']),
				'forum'		=> _es($forum),
				'start_time'=> $_POST['announce_start'] ? strtotime($_POST['announce_start']) : 0,
				'end_time'	=> $_POST['announce_end']	? strtotime($_POST['announce_end']) : 0,
				'active'	=> intval($_POST['announce_active']),
			));
			cache_del('forum_announces');
			return js_redirect('./?object='.'forum'.'&action=edit_announces'._add_get(array('page')));
		}
		$replace = array(
			'form_action'		=> './?object='.'forum'.'&action='.$_GET['action']._add_get(array('page')),
			'announce_title'	=> '',
			'announce_start'	=> '',
			'announce_end'		=> '',
			'announce_content'	=> '',
			'announce_active'	=> '',
			'forums_box'		=> $this->_forums_box('forum'),
		);
		return tpl()->parse('forum'.'/user_cp/announce_add', $replace);
	}
	
	/**
	* Edit method
	*/
	function _edit() {
		if (empty($_GET['id'])) {
			return module('forum')->_show_error('No ID!');
		}
		if (!empty($_POST['save'])) {
			if ($_POST['forum'] == '*') {
				$forum = '*';
			} else {
				$forum = is_array($_POST['forum']) ? implode(',', $_POST['forum']) : intval($_POST['forum']);
			}
			db()->update(db('forum_announce'), _es(array(
				'title'		=> $_POST['announce_title'],
				'post'		=> $_POST['announce_post'],
				'forum'		=> $forum,
				'start_time'=> $_POST['announce_start'] ? strtotime($_POST['announce_start']) : 0,
				'end_time'	=> $_POST['announce_end'] ? strtotime($_POST['announce_end']) : 0,
				'active'	=> intval($_POST['announce_active']),
			)), 'id='.intval($_GET['id']));
			cache_del('forum_announces');
			return js_redirect('./?object='.'forum'.'&action=edit_announces'._add_get(array('page')));
		}
		$announce_info = db()->query_fetch('SELECT * FROM '.db('forum_announce').' WHERE id='.$_GET['id'].' LIMIT 1');
		$forum_selected = array();
		if ($announce_info['forum'] != '*') {
			$tmp = explode(',', $announce_info['forum']);
			foreach ((array)$tmp as $forum_id) {
				$forum_selected[$forum_id] = $forum_id;
			}
		}
		$replace = array(
			'form_action'		=> './?object='.'forum'.'&action='.$_GET['action'].'&id='.$_GET['id']._add_get(array('page')),
			'announce_id'		=> $announce_info['id'],
			'announce_title'	=> $announce_info['title'],
			'announce_start'	=> $announce_info['start_time'] ? date('Y-m-d', $announce_info['start_time']) : '',
			'announce_end'		=> $announce_info['end_time'] ? date('Y-m-d', $announce_info['end_time']) : '',
			'announce_content'	=> _class('bb_codes')->_process_text($announce_info['post']),
			'announce_active'	=> intval($announce_info['active']),
			'forums_box'		=> $this->_forums_box('forum', $forum_selected),
		);
		return tpl()->parse('forum'.'/user_cp/announce_edit', $replace);
	}
	
	/**
	* Delete method
	*/
	function _del() {
		if (!empty($_GET['id'])) {
			db()->query('DELETE FROM '.db('forum_announce').' WHERE id='.intval($_GET['id']));
		}
		cache_del('forum_announces');
		return js_redirect('./?object='.'forum'.'&action=edit_announces'._add_get(array('page')));
	}
	
	/**
	* Set inactive expired announces
	*/
	function _retire_expired() {
		db()->query('UPDATE '.db('forum_announce').' SET active=0 WHERE end_time != 0 AND end_time < '.time());
		if (module('forum')->SETTINGS['ALLOW_ANNOUNCES']) {
			cache_del('forum_announces');
		}
	}

	/**
	* Forums Box
	*/
	function _forums_box($name_in_form = 'new_forum_id', $selected = '') {
		// Create forum jump array
		$forum_divider	= '&nbsp;&nbsp;&#0124;-- ';
		$forums_array	= array('*' => '-- '.t('All Forums').' --');
		foreach ((array)module('forum')->_forum_cats_array as $cat_info) {
			foreach ((array)module('forum')->_forums_array as $forum_info) {
				if ($forum_info['category'] != $cat_info['id']) {
					continue;
				}
				$forums_array[$cat_info['name']][$forum_info['id']] = $forum_divider. $forum_info['name'];
			}
		}
		return common()->multi_select($name_in_form, $forums_array, $selected, false, 2, ' size=10 ', false);
	}	
}
