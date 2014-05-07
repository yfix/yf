<?php

/**
* Manage main forum content (categories, forums, topics, posts)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_forum_manage_main {

	// Admin: edit category
	function _edit_category () {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$cat_info = db()->query_fetch('SELECT * FROM '.db('forum_categories').' WHERE id='.$_GET['id']);
		}
		if (empty($cat_info)) {
			return _e('No such category');
		}
		if (main()->is_post()) {
			db()->update_safe('forum_categories', array(
				'name'	=> $_POST['name'],
				'desc'	=> $_POST['description'],
				'status'=> $_POST['activity'],
				'order'	=> $_POST['display_order'],
			), $_GET['id']);
			cache_del('forum_categories');
			return js_redirect('./?object='.$_GET['object']);
		}
		$replace = array(
			'header_text'	=> t('edit_category'),
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id']._add_get(),
			'name'			=> stripslashes($cat_info['name']),
			'display_order'	=> intval($cat_info['order']),
			'description'	=> stripslashes($cat_info['desc']),
			'activity'		=> common()->radio_box('activity', module('forum')->_active_select, $cat_info['status']),
			'back'			=> back('./?object='.$_GET['object']),
		);
		return tpl()->parse('manage_forum/admin/category_form', $replace);
	}

	// Admin: add category
	function _add_category () {
		if (main()->is_post()) {
			db()->insert_safe('forum_categories', array(
				'name'	=> $_POST['name'],
				'desc'	=> $_POST['description'],
				'status'=> $_POST['activity'],
				'order'	=> $_POST['display_order'],
			));
			cache_del('forum_categories');
			return js_redirect('./?object='.$_GET['object']);
		}
		$replace = array(
			'header_text'	=> t('add_category'),
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action']._add_get(),
			'name'			=> '',
			'display_order'	=> '0',
			'description'	=> '',
			'activity'		=> common()->radio_box('activity', module('forum')->_active_select, 'a'),
			'back'			=> back('./?object='.$_GET['object']),
		);
		return tpl()->parse('manage_forum/admin/category_form', $replace);
	}

	// Delete category
	function _delete_category () {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$cat_info = db()->query_fetch('SELECT * FROM '.db('forum_categories').' WHERE id='.$_GET['id']);
		}
		if (!empty($cat_info)) {
			db()->query('DELETE FROM '.db('forum_categories').' WHERE id='.$_GET['id'].' LIMIT 1');
			$Q = db()->query('SELECT * FROM '.db('forum_forums').' WHERE category='.$_GET['id']);
			while ($forum_info = db()->fetch_assoc($Q)) {
// TODO: need to make recurse sub-forums deletion
				db()->query('DELETE FROM '.db('forum_posts').' WHERE forum='.$forum_info['id']);
				db()->query('DELETE FROM '.db('forum_topics').' WHERE forum='.$forum_info['id']);
			}
			db()->query('DELETE FROM '.db('forum_forums').' WHERE category='.$_GET['id']);
		}
		cache_del('forum_categories');
		cache_del('forum_forums');
		cache_del('forum_totals');
		cache_del('forum_home_page_posts');

		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	// Admin: edit forum
	function _edit_forum () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e('No forum id');
		}
		$forum_info = db()->query_fetch('SELECT * FROM '.db('forum_forums').' WHERE id='.$_GET['id']);
		if (empty($forum_info)) {
			return _e('No such forum');
		}
		if (main()->is_post()) {
			$_parent_forum_id = intval($_POST['forum']);
			if (substr($_POST['forum'], 0, 2) == 'c_') {
				$_cat_id = intval(substr($_POST['forum'], 2));
			} elseif (!empty($_parent_forum_id)) {
				$parent_forum_info = db()->query_fetch('SELECT * FROM '.db('forum_forums').' WHERE id='.intval($_parent_forum_id));
				$_cat_id = $parent_forum_info['category'];
			}
			if (!empty($_POST['user_groups'])) {
				$_tmp_user_groups = array();
				$_tmp_array = is_array($_POST['user_groups']) ? $_POST['user_groups'] : explode(',', $_POST['user_groups']);
				foreach ((array)$_tmp_array as $_group_id) {
					$_group_id = intval($_group_id);
					if (empty($_group_id) || !isset(module('forum')->_forum_groups[$_group_id])) {
						continue;
					}
					$_tmp_user_groups[$_group_id] = $_group_id;
				}
				$_POST['user_groups'] = implode(',', $_tmp_user_groups);
			}
			if ($_GET['id'] && !empty($_cat_id)) {
				db()->UPDATE('forum_forums', array(
					'name'			=> _es($_POST['name']),
					'desc'			=> _es($_POST['description']),
					'status'		=> _es($_POST['activity']),
					'order'			=> intval($_POST['display_order']),
					'category'		=> intval($_cat_id),
					'parent'		=> intval($_parent_forum_id),
					'options'		=> $_POST['postings'] == '2' ? '2' : '',
					'user_groups'	=> _es($_POST['user_groups']),
				), 'id='.intval($_GET['id']));
			}
			cache_del('forum_forums');
			return js_redirect('./?object='.$_GET['object']);
		}
		$DATA = $forum_info;
		foreach ((array)$_POST as $k => $v) {
			if (isset($DATA[$k])) {
				$DATA[$k] = $v;
			}
		}
		$groups_select = array();
		foreach ((array)module('forum')->_forum_groups as $_group_id => $_group_info) {
			$groups_select[$_group_id] = _prepare_html($_group_info['title']);
		}
		$groups_selected = array();
		foreach ((array)explode(',', $DATA['user_groups']) as $_group_id) {
			$groups_selected[$_group_id] = $_group_id;
		}
		foreach ((array)module('forum')->_forum_cats_array as $_cat_info) {
			$categories[$_cat_info['id']] = $_cat_info['name'];
		}
		$_parents_array = module('forum')->_prepare_parents_for_select($_GET['id']);
		$replace = array(
			'header_text'		=> t('edit_forum'),
			'form_action'		=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id']._add_get(),
			'name'				=> stripslashes($forum_info['name']),
			'category_box'		=> common()->select_box('category',	$categories,		$forum_info['category'], false),
			'parent_box'		=> common()->select_box('forum',		$_parents_array,	$forum_info['parent'] ? $forum_info['parent'] : 'c_'.$forum_info['category'], false),
			'display_order'		=> intval($forum_info['order']),
			'description'		=> stripslashes($forum_info['desc']),
			'activity_box'		=> common()->radio_box('activity', module('forum')->_active_select, $forum_info['status']),
			'postings_box'		=> common()->radio_box('postings', module('forum')->_postings_select, $forum_info['options'] == '2'),
			'user_groups_box'	=> common()->multi_select('user_groups', $groups_select, $groups_selected, false, 2, ' size=7 class=small_for_select ', false),
			'back'				=> back('./?object='.$_GET['object']),
		);
		return tpl()->parse('manage_forum/admin/forum_form', $replace);
	}

	// Admin: add forum
	function _add_forum () {
		$_GET['id'] = intval($_GET['id']);
		if (main()->is_post()) {
			$_parent_forum_id = intval($_POST['forum']);
			if (substr($_POST['forum'], 0, 2) == 'c_') {
				$_cat_id = intval(substr($_POST['forum'], 2));
			} elseif (!empty($_parent_forum_id)) {
				$parent_forum_info = db()->query_fetch('SELECT * FROM '.db('forum_forums').' WHERE id='.intval($_parent_forum_id));
				$_cat_id = $parent_forum_info['category'];
			}
			if (!empty($_cat_id)) {
				db()->INSERT('forum_forums', array(
					'name'		=> _es($_POST['name']),
					'desc'		=> _es($_POST['description']),
					'status'	=> _es($_POST['activity']),
					'order'		=> intval($_POST['display_order']),
					'category'	=> intval($_cat_id),
					'parent'	=> intval($_parent_forum_id),
					'options'	=> $_POST['postings'] == '2' ? '2' : '',
				));
			}
			cache_del('forum_forums');
			return js_redirect('./?object='.$_GET['object']);
		}
		foreach ((array)module('forum')->_forum_cats_array as $_cat_info) {
			$categories[$_cat_info['id']] = $_cat_info['name'];
		}
		$_parents_array = module('forum')->_prepare_parents_for_select();
		
		$groups_select = array();
		foreach ((array)module('forum')->_forum_groups as $_group_id => $_group_info) {
			$groups_select[$_group_id] = _prepare_html($_group_info['title']);
		}
		$replace = array(
			'header_text'		=> t('add_forum'),
			'form_action'		=> './?object='.$_GET['object'].'&action='.$_GET['action']._add_get(),
			'name'				=> '',
			'category_box'		=> common()->select_box('category',	$categories,	$_GET['id'], false),
			'parent_box'		=> common()->select_box('forum',		$_parents_array,'c_'.$_GET['id'], false),
			'display_order'		=> '0',
			'description'		=> '',
			'activity_box'		=> common()->radio_box('activity', module('forum')->_active_select, 'a'),
			'postings_box'		=> common()->radio_box('postings', module('forum')->_postings_select, $forum_info['options']),
			'back'				=> back('./?object='.$_GET['object']),
			'user_groups_box'	=> common()->multi_select('user_groups', $groups_select, $groups_selected, false, 2, ' size=7 class=small_for_select ', false),
		);
		return tpl()->parse('manage_forum/admin/forum_form', $replace);
	}

	// Admin: delete forum
	function _delete_forum () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$forum_info = db()->query_fetch('SELECT * FROM '.db('forum_forums').' WHERE id='.$_GET['id']);
		}
		if (!empty($forum_info)) {
// TODO: need to make recurse sub-forums deletion
			db()->query('DELETE FROM '.db('forum_posts').' WHERE forum='.$forum_info['id']);
			db()->query('DELETE FROM '.db('forum_topics').' WHERE forum='.$forum_info['id']);
			db()->query('DELETE FROM '.db('forum_forums').' WHERE id='.$_GET['id']);
		}
		cache_del('forum_forums');
		cache_del('forum_totals');
		cache_del('forum_home_page_posts');

		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	// Admin: edit topic
	function _edit_topic () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e('No topic id');
		}
		$topic_info = db()->query_fetch('SELECT * FROM '.db('forum_topics').' WHERE id='.$_GET['id']);
		if (empty($topic_info)) {
			return _e('No such topic');
		}
		if (main()->is_post()) {
			$_POST['forum'] = intval($_POST['forum']);
			if (empty($_POST['forum'])) {
				_re('Forum id required');
			}
			if (!common()->_error_exists()) {
				db()->update_safe('forum_topics', array(
					'name'		=> $_POST['name'],
					'status'	=> $_POST['activity'],
					'forum'		=> $_POST['forum'],
					'user_name'	=> $_POST['user_name'],
					'user_id'	=> $_POST['user_id'],
					'created'	=> strtotime($_POST['created'])
				), $_GET['id']);

				db()->update_safe('forum_posts', array(
					'subject'	=> $_POST['name'],
					'status'	=> $_POST['activity'],
					'forum'		=> $_POST['forum'],
					'text'		=> $_POST['text'],
					'user_name'	=> $_POST['user_name'],
					'user_id'	=> $_POST['user_id'],
					'created'	=> strtotime($_POST['created']),
				), $topic_info['first_post_id']);

				$update_flag = false;
				if ($_POST['forum'] != $topic_info['forum']) {
					db()->update_safe('forum_posts', array('forum' => $_POST['forum']), 'topic='.$_GET['id']);
					module('forum')->_update_forum_record($topic_info['forum']);
					$update_flag = true;
				}
				if ($_POST['activity'] != $topic_info['status']) {
					db()->update_safe('forum_posts', array('status' => $_POST['activity']), 'topic='.$_GET['id']);
					$update_flag = true;
				}
				if ($update_flag) {
					module('forum')->_update_forum_record($_POST['forum']);
				}
				cache_del('forum_forums');
				cache_del('forum_totals');
				cache_del('forum_home_page_posts');
				return js_redirect('./?object='.$_GET['object'].'&action=view_forum&id='.$_POST['forum']);
			}
		}
		foreach ((array)module('forum')->_forum_cats_array as $_cat_info) {
			$categories[$_cat_info['id']] = $_cat_info['name'];
		}
		foreach ((array)module('forum')->_forums_array as $_forum_info) {
			$forums_with_cats[$_forum_info['id']] = $categories[$_forum_info['category']].' / '.$_forum_info['name'];
		}
		list($text) = db()->query_fetch('SELECT text AS `0` FROM '.db('forum_posts').' WHERE id='.$topic_info['first_post_id']);
		$replace = array(
			'header_text'	=> t('edit_topic'),
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id']._add_get(),
			'name'			=> stripslashes($topic_info['name']),
			'text'			=> stripslashes($text),
			'forum'			=> common()->select_box('forum', $forums_with_cats, $topic_info['forum'], false),
			'user_name'		=> stripslashes($topic_info['user_name']),
			'user_id'		=> $topic_info['user_id'],
			'created'		=> date('Y-m-d H:i:s', $topic_info['created']),
			'activity'		=> common()->radio_box('activity', module('forum')->_active_select, $topic_info['status']),
			'back'			=> back('./?object='.$_GET['object'].'&action=view_forum&id='.$topic_info['forum']),
		);
		return tpl()->parse('manage_forum/admin/topic_form', $replace);
	}

	// Admin: delete topic
	function _delete_topic () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$topic_info = db()->query_fetch('SELECT * FROM '.db('forum_topics').' WHERE id='.$_GET['id']);
		}
		if (!empty($topic_info)) {
			$Q = db()->query('SELECT id FROM '.db('forum_posts').' WHERE topic='.$topic_info['id']);
			while ($post_info = db()->fetch_assoc($Q)) {
				common()->_remove_activity_points($post_info['user_id'], 'forum_post', $post_info['id']);
			}
			db()->query('DELETE FROM '.db('forum_posts').' WHERE topic='.$topic_info['id']);
			db()->query('DELETE FROM '.db('forum_topics').' WHERE id='.$topic_info['id']);

			module('forum')->_update_forum_record($topic_info['forum']);
		}
		cache_del('forum_forums');
		cache_del('forum_totals');
		cache_del('forum_home_page_posts');
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object'].'&action=view_forum&id='.$topic_info['forum']);
		}
	}

	// Admin: edit post
	function _edit_post () {
		$_GET['id']		= intval($_GET['id']);
		if (isset($_GET['msg_id'])) {
			$_GET['msg_id'] = intval($_GET['msg_id']);
		}
		$POST_ID = 0;
		if ($_GET['id'] && !$_GET['msg_id']) {
			$POST_ID = $_GET['id'];
		} elseif ($_GET['id'] && $_GET['msg_id']) {
			$POST_ID = $_GET['msg_id'];
		}
		if ($POST_ID) {
			$post_info = db()->query_fetch('SELECT * FROM '.db('forum_posts').' WHERE id='.intval($POST_ID));
		}
		if (empty($post_info)) {
			return _e('No such post');
		}
		if (main()->is_post()) {
			db()->update_safe('forum_posts', array( 
				'subject'	=> $_POST['subject'],
				'status'	=> $_POST['activity'],
				'text'		=> $_POST['text'],
				'user_name' => $_POST['user_name'],
				'poster_ip' => $_POST['poster_ip'],
				'user_id'	=> $_POST['user_id'],
				'created'	=> strtotime($_POST['created']),
			), $post_info['id']);
			if ($_POST['activity'] != $post_info['status']) {
				module('forum')->_update_forum_record($post_info['forum']);
				module('forum')->_update_topic_record($post_info['topic']);
			}
			cache_del('forum_forums');
			cache_del('forum_totals');
			cache_del('forum_home_page_posts');
			return js_redirect('./?object='.$_GET['object'].'&action=view_topic&id='.$post_info['topic']);
		}
		$replace = array(
			'header_text'	=> t('edit_post'),
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'].($_GET['msg_id'] ? '&msg_id='.$_GET['msg_id'] : ''). _add_get(),
			'subject'		=> stripslashes($post_info['subject']),
			'text'			=> stripslashes($post_info['text']),
			'user_name'		=> stripslashes($post_info['user_name']),
			'user_id'		=> $post_info['user_id'],
			'poster_ip'		=> $post_info['poster_ip'],
			'created'		=> date('Y-m-d H:i:s', $post_info['created']),
			'activity'		=> common()->radio_box('activity', module('forum')->_active_select, $post_info['status']),
			'back'			=> back('./?object='.$_GET['object'].'&action=view_topic&id='.$post_info['topic']),
		);
		return tpl()->parse('manage_forum/admin/post_form', $replace);
	}

	// Admin: delete post
	function _delete_post () {
		$_GET['id'] = intval($_GET['id']);
		$_GET['msg_id'] = intval($_GET['msg_id']);
		if ($_GET['id'] && $_GET['msg_id']) {
			$post_info = db()->query_fetch('SELECT * FROM '.db('forum_posts').' WHERE id='.$_GET['msg_id']);
			db()->query('DELETE FROM '.db('forum_posts').' WHERE id='.$_GET['msg_id']);
			module('forum')->_update_forum_record($post_info['forum']);
			module('forum')->_update_topic_record($post_info['topic']);
			common()->_remove_activity_points($post_info['user_id'], 'forum_post', $_GET['msg_id']);
		}
		cache_del('forum_forums');
		cache_del('forum_totals');
		cache_del('forum_home_page_posts');
		// Return user back
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['msg_id'];
		} else {
			return js_redirect('./?object='.$_GET['object'].'&action=view_topic&id='.$_GET['id']);
		}
	}

	/**
	*/
	function _change_category_activity () {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$cat_info = db()->query_fetch('SELECT * FROM '.db('forum_categories').' WHERE id='.$_GET['id']);
		}
		if (!empty($cat_info)) {
			db()->UPDATE('forum_categories', array('status' => $cat_info['status'] == 'a' ? 'p' : 'a'), 'id='.intval($cat_info['id']));
			cache_del('forum_categories');
			cache_del('forum_forums');
			cache_del('forum_totals');
			cache_del('forum_home_page_posts');
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($cat_info['status'] == 'a' ? 0 : 1);
		} else {
			js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function _change_forum_activity () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$forum_info = db()->query_fetch('SELECT * FROM '.db('forum_forums').' WHERE id='.$_GET['id']);
		}
		if (!empty($forum_info)) {
			db()->UPDATE('forum_forums', array('status' => $forum_info['status'] == 'a' ? 'p' : 'a'), 'id='.intval($forum_info['id']));
			cache_del('forum_forums');
			cache_del('forum_totals');
			cache_del('forum_home_page_posts');
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($forum_info['status'] == 'a' ? 0 : 1);
		} else {
			js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function _change_topic_activity () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$topic_info = db()->query_fetch('SELECT * FROM '.db('forum_topics').' WHERE id='.$_GET['id']);
		}
		if (!empty($topic_info)) {
			db()->UPDATE('forum_topics', array('status' => $topic_info['status'] == 'a' ? 'p' : 'a'), 'id='.intval($topic_info['id']));
			db()->UPDATE('forum_posts', array('status' => $topic_info['status'] == 'a' ? 'p' : 'a'), 'topic='.intval($topic_info['id']));
			module('forum')->_update_forum_record($topic_info['forum']);
			cache_del('forum_forums');
			cache_del('forum_totals');
			cache_del('forum_home_page_posts');
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($topic_info['status'] == 'a' ? 0 : 1);
		} else {
			js_redirect('./?object='.$_GET['object'].($topic_info['forum'] ? '&action=view_forum&id='.$topic_info['forum'] : ''));
		}
	}

	/**
	*/
	function _change_post_activity () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$post_info = db()->query_fetch('SELECT * FROM '.db('forum_posts').' WHERE id='.$_GET['id']);
		}
		if (!empty($post_info)) {
			db()->UPDATE('forum_posts', array('status' => $post_info['status'] == 'a' ? 'p' : 'a'), 'id='.intval($post_info['id']));
			module('forum')->_update_forum_record($post_info['forum']);
			module('forum')->_update_topic_record($post_info['topic']);
			cache_del('forum_forums');
			cache_del('forum_totals');
			cache_del('forum_home_page_posts');
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($post_info['status'] == 'a' ? 0 : 1);
		} else {
			js_redirect('./?object='.$_GET['object'].($post_info['topic'] ? '&action=view_topic&id='.$post_info['topic'] : ''));
		}
	}
}
