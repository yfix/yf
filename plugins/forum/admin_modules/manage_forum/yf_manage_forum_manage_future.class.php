<?php

/**
* Manage future posts
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_forum_manage_future {

	/** @var int Next auto-date lower limit (in seconds) */
	public $NEXT_DATE_MIN	= 3600;
	/** @var int Next auto-date higher limit (in seconds) */
	public $NEXT_DATE_MAX	= 7200;

	/**
	*/
	function _show_future_posts() {
		if (!in_array($_SESSION['admin_group'], array(1, 6))) {
			return 'Access denied';
		}
		$Q = db()->query('SELECT * FROM '.db('admin').' /*WHERE `group`=6*/ ORDER BY first_name ASC');
		while ($A = db()->fetch_assoc($Q)) $forum_posters[$A['id']] = $A;

		$sql = 'SELECT * FROM '.db('forum_future_posts').' ';
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql('posts') : '';
		$sql .= strlen($filter_sql) ? ' WHERE 1=1 '. $filter_sql : ' ORDER BY date ASC ';
		list($add_sql, $pages, $total) = common()->divide_pages($sql);

		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$future_posts[$A['id']]		= $A;
			$users_ids[$A['user_id']]	= $A['user_id'];
			if ($A['topic_id']) {
				$topics_ids[$A['topic_id']]	= $A['topic_id'];
			}
		}
		if (!empty($users_ids)) {
			$Q = db()->query('SELECT * FROM '.db('user').' WHERE id IN('.implode(',', $users_ids).')');
			while ($A = db()->fetch_assoc($Q)) $users_infos[$A['id']] = $A;
		}
		if (!empty($topics_ids)) {
			$Q = db()->query('SELECT * FROM '.db('forum_topics').' WHERE id IN('.implode(',', $topics_ids).')');
			while ($A = db()->fetch_assoc($Q)) $topics_infos[$A['id']] = $A;
		}
		foreach ((array)$future_posts as $A) {
			$poster_info	= $forum_posters[$A['poster_id']];
			$user_info		= $users_infos[$A['user_id']];
			$_forum_info	= module('manage_forum')->_forums_array[$A['forum_id']];
			$_topic_info	= $A['topic_id'] ? $topics_infos[$A['topic_id']] : false;
			$replace2 = array(
				'bg_class'		=> $i++ % 2 ? 'bg1' : 'bg2',
				'id'			=> intval($A['id']),
				'poster_name'	=> _prepare_html($poster_info['first_name'].' '.$poster_info['last_name']),
				'by_poster_link'=> './?object='.$_GET['object'].'&action=show_posters&id='.intval($A['poster_id']),
				'user_name'		=> _prepare_html(_display_name($user_info)),
				'profile_link'	=> _profile_link($A['user_id']),
				'date'			=> _format_date($A['date'], 'long'),
				'type'			=> $A['new_topic'] ? 'topic' : 'post',
				'subject'		=> _prepare_html($A['subject']),
				'forum_link'	=> './?object='.$_GET['object'].'&action=view_forum&id='.intval($A['forum_id']),
				'forum_name'	=> _prepare_html($_forum_info['name']),
				'topic_link'	=> $_topic_info ? './?object='.$_GET['object'].'&action=view_topic&id='.intval($A['topic_id']) : '',
				'topic_name'	=> $_topic_info ? _prepare_html($_topic_info['name']) : '',
				'edit_link'		=> './?object='.$_GET['object'].'&action=edit_future_post&id='.intval($A['id']),
				'delete_link'	=> './?object='.$_GET['object'].'&action=delete_future_post&id='.intval($A['id']),
			);
			$items .= tpl()->parse('manage_forum/future_posts_item', $replace2);
		}
		$replace = array(
			'items'				=> $items,
			'pages'				=> $pages,
			'total'				=> intval($total),
			'filter'			=> $this->USE_FILTER ? $this->_show_filter('posts') : '',
			'future_topic_link'	=> './?object='.$_GET['object'].'&action=add_future_topic&id='.$_GET['id'],
			'mass_delete_action'=> './?object='.$_GET['object'].'&action=delete_future_post&id='.$_GET['id'],
		);
		return tpl()->parse('manage_forum/future_posts_main', $replace);
	}

	/**
	* Add new future topic
	*/
	function _add_topic() {
		if (!in_array($_SESSION['admin_group'], array(1, 6))) {
			return 'Access denied';
		}
		$FORUM_ID = $_GET['id'];
		// Get child accouts for the current poster
		$all_posters_users = main()->get_data('forum_posters_users', 3600);
		$_users_array = $all_posters_users[$_SESSION['admin_id']];
		unset($all_posters_users);
		if (empty($_users_array)) {
			return _e('No user accounts specified for you.');
		}
		if (main()->is_post()) {
			$_POST['user_id'] = intval($_POST['user_id']);
			if (empty($_POST['user_id']) || !isset($_users_array[$_POST['user_id']])) {
				_re('User id required');
			}
			if (empty($_POST['name'])) {
				_re('Topic name required');
			}
			if (empty($_POST['text'])) {
				_re('Topic text required');
			}
			if (!common()->_error_exists()) {
				db()->INSERT('forum_future_posts', array(
					'poster_id'			=> intval($_SESSION['admin_id']),
					'forum_id'			=> intval($_POST['forum']),
					'topic_id'			=> 0,
					'future_topic_id'	=> 0,
					'user_id'			=> intval($_POST['user_id']),
					'user_name'			=> _es($_users_array[$_POST['user_id']]),
					'date'				=> strtotime($_POST['date']),
					'subject'			=> _es($_POST['desc']),
					'text'				=> _es($_POST['text']),
					'new_topic'			=> 1,
					'topic_title'		=> _es($_POST['name']),
					'active'			=> 1,
				));
				return js_redirect('./?object='.$_GET['object'].'&action=view_forum&id='.$_POST['forum']);
			}
		}
		if (empty($_POST['date'])) {
			$_POST['date'] = time() + rand($this->NEXT_DATE_MIN, $this->NEXT_DATE_MAX);
		}
		$_parents_array = module('manage_forum')->_prepare_parents_for_select();
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'error_message'	=> _e(),
			'name'			=> _prepare_html($_POST['name']),
			'desc'			=> _prepare_html($_POST['desc']),
			'text'			=> _prepare_html($_POST['text']),
			'forum_box'		=> common()->select_box('forum', $_parents_array, $_GET['id'], false),
			'users_box'		=> common()->select_box('user_id', $_users_array, $_POST['user_id'], false),
			'date'			=> date('Y-m-d H:i:s', !is_numeric($_POST['date']) ? strtotime($_POST['date']) : $_POST['date']),
			'back'			=> back('./?object='.$_GET['object'].'&action=view_forum&id='.$_GET['id']),
			'next_date_min'	=> intval($this->NEXT_DATE_MIN),
			'next_date_max'	=> intval($this->NEXT_DATE_MAX),
		);
		return tpl()->parse('manage_forum/future_topic_form', $replace);
	}

	/**
	* Add new future post
	*/
	function _add_post() {
		if (!in_array($_SESSION['admin_group'], array(1, 6))) {
			return 'Access denied';
		}
		$_GET['id'] = intval($_GET['id']);
		$TOPIC_ID = $_GET['id'];
		if (!empty($_GET['id'])) {
			$topic_info = db()->query_fetch('SELECT * FROM '.db('forum_topics').' WHERE id='.$_GET['id'].' LIMIT 1');
		}
		if (empty($topic_info['id'])) {
			return module('manage_forum')->_show_error('No such topic');
		}
		// Get child accouts for the current poster
		$all_posters_users = main()->get_data('forum_posters_users', 3600);
		$_users_array = $all_posters_users[$_SESSION['admin_id']];
		unset($all_posters_users);
		if (empty($_users_array)) {
			return _e('No user accounts specified for you.');
		}
		$parent_forum_id = module('manage_forum')->_forums_array[$topic_info['forum']]['parent'];
		$forum_name = module('manage_forum')->_forums_array[$topic_info['forum']]['name'];
		$topic_name = $topic_info['name'];
		$cat_name	= $topic_info['category'] ? module('manage_forum')->_forum_cats_array[$topic_info['category']]['name'] : module('manage_forum')->_forum_cats_array[module('manage_forum')->_forums_array[$topic_info['forum']]['category']]['name'];
		if (main()->is_post()) {
			foreach ((array)$_POST['text'] as $_item_id => $_tmp) {
				$DATA = array(
					'user_id'	=> $_POST['user_id'][$_item_id],
					'date'		=> $_POST['date'][$_item_id],
					'text'		=> $_POST['text'][$_item_id],
					'subject'	=> $_POST['subject'][$_item_id],
				);
				$DATA['user_id'] = intval($DATA['user_id']);
				if (empty($DATA['user_id']) || !isset($_users_array[$DATA['user_id']])) {
					continue;
				}
				if (empty($DATA['text'])) {
					continue;
				}
				db()->INSERT('forum_future_posts', array(
					'poster_id'			=> intval($_SESSION['admin_id']),
					'forum_id'			=> intval($topic_info['forum']),
					'topic_id'			=> intval($_GET['id']),
					'future_topic_id'	=> 0,
					'user_id'			=> intval($DATA['user_id']),
					'user_name'			=> _es($_users_array[$DATA['user_id']]),
					'date'				=> strtotime($DATA['date']),
					'subject'			=> _es($DATA['subject']),
					'text'				=> _es($DATA['text']),
					'new_topic'			=> 0,
					'topic_title'		=> '',
					'active'			=> 1,
				));
			}
			return js_redirect('./?object='.$_GET['object'].'&action=view_topic&id='.$_GET['id']);
		}
		if (empty($_POST['date'])) {
			$_POST['date'] = time() + rand($this->NEXT_DATE_MIN, $this->NEXT_DATE_MAX);
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'error_message'	=> _e(),
			'cat_name'		=> _prepare_html($cat_name),
			'forum_name'	=> _prepare_html($forum_name),
			'topic_name'	=> _prepare_html($topic_name),
			'cat_link'		=> './?object='.$_GET['object'],
			'forum_link'	=> './?object='.$_GET['object'].'&action=view_forum&id='.$topic_info['forum'],
			'topic_link'	=> './?object='.$_GET['object'].'&action=view_topic&id='.$_GET['id'],
			'subject'		=> 'Re:'._prepare_html($topic_info['name']),
			'text'			=> _prepare_html($_POST['text']),
			'users_box'		=> common()->select_box('user_id[]', $_users_array, $_POST['user_id'], false),
			'date'			=> date('Y-m-d H:i:s', !is_numeric($_POST['date']) ? strtotime($_POST['date']) : $_POST['date']),
			'time'			=> (!is_numeric($_POST['date']) ? strtotime($_POST['date']) : $_POST['date']),
			'back'			=> back('./?object='.$_GET['object'].'&action=view_topic&id='.$_GET['id']),
			'next_date_min'	=> intval($this->NEXT_DATE_MIN),
			'next_date_max'	=> intval($this->NEXT_DATE_MAX),
		);
		return tpl()->parse('manage_forum/future_post_form', $replace);
	}

	/**
	*/
	function _edit_future_post() {
		if (!in_array($_SESSION['admin_group'], array(1, 6))) {
			return 'Access denied';
		}
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$post_info = db()->query_fetch('SELECT * FROM '.db('forum_future_posts').' WHERE id='.intval($_GET['id']));
		}
		if (empty($post_info)) {
			return 'No such post';
		}
		$all_posters_users = main()->get_data('forum_posters_users', 3600);
		$_users_array = $all_posters_users[$_SESSION['admin_id']];
		unset($all_posters_users);
		if (empty($_users_array)) {
			return _e('No user accounts specified for you.');
		}
		$is_new_topic = $post_info['new_topic'] ? 1 : 0;
		$_forum_info = module('manage_forum')->_forums_array[$post_info['forum_id']];
		$forum_name = $_forum_info['name'];
		$cat_name	= module('manage_forum')->_forum_cats_array[$_forum_info['category']]['name'];
		if (!$is_new_topic) {
			$topic_info = db()->query_fetch('SELECT * FROM '.db('forum_topics').' WHERE id='.$post_info['topic_id'].' LIMIT 1');
			$topic_name = $topic_info['name'];
		}

		$Q = db()->query('SELECT * FROM '.db('admin').' ORDER BY first_name ASC');
		while ($A = db()->fetch_assoc($Q)) $forum_posters[$A['id']] = $A;

		if (main()->is_post()) {
			db()->UPDATE('forum_future_posts', array(
				'forum_id'			=> intval($_POST['forum'] ? $_POST['forum'] : $post_info['forum_id']),
				'user_id'			=> intval($_POST['user_id']),
				'user_name'			=> _es($_users_array[$_POST['user_id']]),
				'date'				=> strtotime($_POST['date']),
				'subject'			=> _es($_POST['subject']),
				'text'				=> _es($_POST['text']),
				'topic_title'		=> _es($_POST['name'] ? $_POST['name'] : $post_info['topic_title']),
			), 'id='.intval($_GET['id']));
			return js_redirect('./?object='.$_GET['object'].'&action=show_future_posts');
		}
		$_parents_array = module('manage_forum')->_prepare_parents_for_select();
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'is_new_topic'	=> $is_new_topic,
			'error_message'	=> _e(),
			'cat_name'		=> _prepare_html($cat_name),
			'forum_name'	=> _prepare_html($forum_name),
			'topic_name'	=> _prepare_html($topic_name),
			'cat_link'		=> './?object='.$_GET['object'],
			'forum_link'	=> './?object='.$_GET['object'].'&action=view_forum&id='.$topic_info['forum'],
			'topic_link'	=> !$is_new_topic ? './?object='.$_GET['object'].'&action=view_topic&id='.$_GET['id'] : '',
			'name'			=> _prepare_html($post_info['topic_title']),
			'subject'		=> _prepare_html($post_info['subject']),
			'text'			=> _prepare_html($post_info['text']),
			'forum_box'		=> common()->select_box('forum', $_parents_array, $post_info['forum_id'], false),
			'users_box'		=> common()->select_box('user_id', $_users_array, $post_info['user_id'], false),
			'date'			=> date('Y-m-d H:i:s', !is_numeric($post_info['date']) ? strtotime($post_info['date']) : $post_info['date']),
			'back'			=> back('./?object='.$_GET['object'].'&action=show_future_posts'),
		);
		return tpl()->parse($_GET['object'].'/admin/future_edit_post', $replace);
	}

	/**
	*/
	function _delete_future_post() {
		if ($_SESSION['admin_group'] != 1) {
			return 'Access denied';
		}
		$_GET['id'] = intval($_GET['id']);
		if (isset($_POST['items'])) {
			$ids_to_delete = array();
			foreach ((array)$_POST['items'] as $_cur_id) {
				if (empty($_cur_id)) {
					continue;
				}
				$ids_to_delete[$_cur_id] = $_cur_id;
			}
			if (!empty($ids_to_delete)) {
				db()->query('DELETE FROM '.db('forum_future_posts').' WHERE id IN('.implode(',',$ids_to_delete).')');
			}
		} else {
			if (!empty($_GET['id'])) {
				$post_info = db()->query_fetch('SELECT * FROM '.db('forum_future_posts').' WHERE id='.intval($_GET['id']));
			}
			if (!empty($post_info)) {
				db()->query('DELETE FROM '.db('forum_future_posts').' WHERE id='.intval($_GET['id']));
			}
		}
		if (is_ajax()) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect($_SERVER['HTTP_REFERER'], 0);
		}
	}

	/**
	*/
	function _show_posters() {
		if ($_SESSION['admin_group'] != 1) {
			return 'Access denied';
		}
		$POSTER_ID = intval($_GET['id']);

		$Q = db()->query('SELECT * FROM '.db('admin').' WHERE `group`=6 ORDER BY first_name ASC');
		while ($A = db()->fetch_assoc($Q)) $forum_posters[$A['id']] = $A;

		$all_posters_users = main()->get_data('forum_posters_users', 3600);
		foreach ((array)$forum_posters as $A) {
			$users_array = array();
			foreach ((array)$all_posters_users[$A['id']] as $_user_id => $_user_name) {
				$users_array[] = array(
					'name'	=> _prepare_html($_user_name),
					'link'	=> _profile_link($_user_id),
				);
			}
			$replace2 = array(
				'bg_class'		=> $i++ % 2 ? 'bg1' : 'bg2',
				'user_id'		=> intval($A['id']),
				'user_name'		=> _prepare_html($A['first_name'].' '.$A['last_name']),
				'users_array'	=> $users_array,
				'start_date'	=> _format_date($A['add_date']),
				'edit_link'		=> './?object='.$_GET['object'].'&action=edit_forum_poster&id='.intval($A['id']),
				'stats_link'	=> './?object='.$_GET['object'].'&action=show_poster_stats&id='.intval($A['id']),
				'delete_link'	=> './?object=admin',
			);
			$items .= tpl()->parse('manage_forum/forum_posters_item', $replace2);
		}
		$replace = array(
			'add_link'	=> './?object=admin&action=add',
			'items'		=> $items,
			'pages'		=> $pages,
			'total'		=> intval($total),
			'filter'	=> $this->USE_FILTER ? $this->_show_filter('stats') : '',
		);
		return tpl()->parse('manage_forum/forum_posters_main', $replace);
	}

	/**
	*/
	function _show_poster_stats() {
		if ($_SESSION['admin_group'] != 1) {
			return 'Access denied';
		}
		$_GET['id'] = intval($_GET['id']);
		$POSTER_ID = $_GET['id'];

		$Q = db()->query('SELECT * FROM '.db('admin').' /*WHERE `group`=6*/ ORDER BY first_name ASC');
		while ($A = db()->fetch_assoc($Q)) $forum_posters[$A['id']] = $A;

		$poster_info = $forum_posters[$POSTER_ID];
		if (!isset($poster_info)) {
			return 'No such poster';
		}
		$all_posters_users = main()->get_data('forum_posters_users', 3600);
		$users_ids = array();
		foreach ((array)$all_posters_users[$POSTER_ID] as $_user_id => $_user_name) {
			$users_ids[$_user_id] = $_user_id;
		}
		ksort($users_ids);

		$START_DATE	= $poster_info['add_date'];
		$WORK_DAYS	= floor((time() - $START_DATE) / 86400);
		list($themes_total)	= db()->query_fetch('SELECT COUNT(*) AS `0` FROM '.db('forum_topics').' WHERE auto_poster_id='.intval($POSTER_ID));
		list($posts_total)	= db()->query_fetch('SELECT COUNT(*) AS `0` FROM '.db('forum_posts').' WHERE new_topic != 1 AND auto_poster_id='.intval($POSTER_ID));
		$words_total	= 0;
		$symbols_total	= 0;

		$Q = db()->query('SELECT text FROM '.db('forum_posts').' WHERE auto_poster_id='.intval($POSTER_ID));
		while ($A = db()->fetch_assoc($Q)) {
			$cur_text = $this->_cleanup_text($A['text']);
			$_cur_length = strlen($cur_text);
			if (!$_cur_length) {
				continue;
			}
			$symbols_total += $_cur_length;
			$_num_words = strlen(preg_replace('/[^\s]/ims', '', $cur_text)) + 1;
			$words_total += $_num_words;
		}
		// Get number of responses by normal users
		list($total_responses)	= db()->query_fetch(
			'SELECT COUNT(*) AS `0` 
			FROM '.db('forum_posts').' 
			WHERE new_topic != 1 
				AND auto_poster_id=0 
				AND topic IN ( 
					SELECT id FROM '.db('forum_topics').' WHERE auto_poster_id = '.intval($POSTER_ID).'
				)'
		);
		// Get posts inside other themes
		$Q = db()->query(
			'SELECT text FROM '.db('forum_posts').' '.db('forum_posts').' 
			WHERE new_topic != 1 
				AND auto_poster_id = '.intval($POSTER_ID).'
				AND topic NOT IN ( 
					SELECT id FROM '.db('forum_topics').' WHERE auto_poster_id = '.intval($POSTER_ID).'
				)'
		);
		$others_themes_posts	= 0;
		$others_themes_length	= 0;
		while ($A = db()->fetch_assoc($Q)) {
			$others_themes_posts++;
			$cur_text = $this->_cleanup_text($A['text']);
			$_cur_length = strlen($cur_text);
			if (!$_cur_length) {
				continue;
			}
			$others_themes_length += $_cur_length;
		}
		$stats = array(
			'themes_total'			=> intval($themes_total),
			'themes_per_month'		=> round($WORK_DAYS ? ($themes_total / $WORK_DAYS * 30) : 0, 2),
			'themes_per_day'		=> round($WORK_DAYS ? ($themes_total / $WORK_DAYS) : 0, 2),
			'posts_total'			=> intval($posts_total),
			'posts_per_month'		=> round($WORK_DAYS ? ($posts_total / $WORK_DAYS * 30) : 0, 2),
			'posts_per_day'			=> round($WORK_DAYS ? ($posts_total / $WORK_DAYS) : 0, 2),
			'words_total'			=> intval($words_total),
			'words_per_month'		=> round($WORK_DAYS ? ($words_total / $WORK_DAYS * 30) : 0, 2),
			'words_per_day'			=> round($WORK_DAYS ? ($words_total / $WORK_DAYS) : 0, 2),
			'symbols_total'			=> intval($symbols_total),
			'symbols_per_month'		=> round($WORK_DAYS ? ($symbols_total / $WORK_DAYS * 30) : 0, 2),
			'symbols_per_day'		=> round($WORK_DAYS ? ($symbols_total / $WORK_DAYS) : 0, 2),
			'total_responses'		=> intval($total_responses),
			'responses_per_topic'	=> round($total_responses / $themes_total, 2),
			'others_themes_posts'	=> round($posts_total ? $others_themes_posts / $posts_total : 0, 2),
			'others_themes_length'	=> round($symbols_total ? $others_themes_length / $symbols_total : 0, 2),
		);
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'users_ids'		=> implode(',', $users_ids),
			'poster_id'		=> intval($poster_info['id']),
			'poster_name'	=> _prepare_html($poster_info['first_name'].' '.$poster_info['last_name']),
			'filter'		=> $this->USE_FILTER ? $this->_show_filter('stats') : '',
			'start_date'	=> _format_date($START_DATE),
			'work_days'		=> intval($WORK_DAYS),
			'back'			=> back('./?object='.$_GET['object'].'&action=show_future_posts'),
		);
		foreach ((array)$stats as $k => $v) {
			$replace[$k] = $v;
		}
		return tpl()->parse('manage_forum/forum_poster_stats', $replace);
	}

	/**
	*/
	function _cleanup_text($cur_text = '') {
		if (!strlen($cur_text)) {
			return '';
		}
		$cur_text = str_replace(array("\r", "\n", "\t"), array("\n", ' ', ' '), $cur_text);
		$cur_text = preg_replace('/\[quote(=[^\]]+){0,1}\].*\[\/quote\]/ims', '', $cur_text);
		$cur_text = preg_replace('/[\s]{2,}/ims', ' ', trim($cur_text));
		return $cur_text;
	}

	/**
	*/
	function _edit_poster() {
		if ($_SESSION['admin_group'] != 1) {
			return 'Access denied';
		}
		$_GET['id'] = intval($_GET['id']);
		$POSTER_ID = $_GET['id'];

		$Q = db()->query('SELECT * FROM '.db('admin').' /*WHERE `group`=6*/ ORDER BY first_name ASC');
		while ($A = db()->fetch_assoc($Q)) $forum_posters[$A['id']] = $A;

		$poster_info = $forum_posters[$POSTER_ID];
		if (!isset($poster_info)) {
			return 'No such poster';
		}
		$all_posters_users = main()->get_data('forum_posters_users', 5/* !Do not touch! */);
		$users_ids = array();
		foreach ((array)$all_posters_users[$POSTER_ID] as $_user_id => $_user_name) {
			$users_ids[$_user_id] = $_user_id;
		}
		ksort($users_ids);
		if (main()->is_post()) {
			$new_users_ids = array();
			foreach (explode(',', $_POST['users_ids']) as $_user_id) {
				$_user_id = intval($_user_id);
				if (empty($_user_id)) {
					continue;
				}
				$new_users_ids[$_user_id] = $_user_id;
			}
			if (!empty($users_ids)) {
				$ids_to_delete = array();
				foreach ((array)$users_ids as $_user_id) {
					$_user_id = intval($_user_id);
					if (empty($_user_id)) {
						continue;
					}
					if (!isset($new_users_ids[$_user_id])) {
						$ids_to_delete[$_user_id] = $_user_id;
					}
				}
				if (!empty($ids_to_delete)) {
					db()->UPDATE('user', array('poster_id' => 0), 'id IN('.implode(',', $ids_to_delete).')');
				}
			}
			if (!empty($new_users_ids)) {
				$ids_to_add = array();
				foreach ((array)$new_users_ids as $_user_id) {
					$_user_id = intval($_user_id);
					if (empty($_user_id)) {
						continue;
					}
					if (!isset($users_ids[$_user_id])) {
						$ids_to_add[$_user_id] = $_user_id;
					}
				}
				if (!empty($ids_to_add)) {
					db()->UPDATE('user', array('poster_id' => $POSTER_ID), 'id IN('.implode(',', $ids_to_add).')');
				}
			}
			cache_del('forum_posters_users');
			return js_redirect('./?object='.$_GET['object'].'&action=show_forum_posters');
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'users_ids'		=> implode(',', $users_ids),
			'poster_id'		=> intval($poster_info['id']),
			'poster_name'	=> _prepare_html($poster_info['first_name'].' '.$poster_info['last_name']),
			'back'			=> back('./?object='.$_GET['object'].'&action=show_forum_posters'),
		);
		return tpl()->parse('manage_forum/edit_forum_poster', $replace);
	}

	/**
	*/
	function _do_cron_job() {
		$ids_to_delete = array();
		$Q = db()->query('SELECT * FROM '.db('forum_future_posts').' WHERE date < '.time().' AND active="1" ORDER BY date DESC');
		while ($A = db()->fetch_assoc($Q)) {
			$NEW_POST_ID	= 0;
			$NEW_TOPIC_ID	= 0;
			if ($A['new_topic']) {
				db()->INSERT('forum_topics', array(
					'forum'		=> $A['forum_id'],
					'active'	=> 0,
				));
				$NEW_TOPIC_ID = db()->INSERT_ID();
				if (empty($NEW_TOPIC_ID)) {
					continue;
				}
				db()->INSERT('forum_posts', array(
					'forum'			=> intval($A['forum_id']),
					'topic'			=> intval($NEW_TOPIC_ID),
					'auto_poster_id'=> intval($A['poster_id']),
					'user_id'		=> intval($A['user_id']),
					'user_name'		=> _es($A['user_name']),
					'created'		=> intval($A['date']),
					'subject'		=> _prepare_html($A['subject']),
					'text'			=> _prepare_html($A['text']),
					'new_topic'		=> 1,
					'active'		=> 1,
				));
				$NEW_POST_ID = db()->INSERT_ID();
				if (empty($NEW_POST_ID)) {
					db()->query('DELETE FROM '.db('forum_topics').' WHERE id='.intval($NEW_TOPIC_ID));
					continue;
				}
				db()->UPDATE('forum_topics', array(
					'forum'				=> intval($A['forum_id']),
					'auto_poster_id'	=> intval($A['poster_id']),
					'user_id'			=> intval($A['user_id']),
					'user_name'			=> _es($A['user_name']),
					'created'			=> intval($A['date']),
					'name'				=> _prepare_html($A['topic_title']),
					'desc'				=> _prepare_html($A['subject']),
					'first_post_id'		=> intval($NEW_POST_ID),
					'last_post_id'		=> intval($NEW_POST_ID),
					'last_poster_id'	=> intval($A['user_id']),
					'last_poster_name'	=> _es($A['user_name']),
					'active'			=> 1,
					'approved'			=> 1,
				), 'id='.intval($NEW_TOPIC_ID));
			} else {
				db()->INSERT('forum_posts', array(
					'forum'			=> intval($A['forum_id']),
					'topic'			=> intval($A['topic_id']),
					'auto_poster_id'=> intval($A['poster_id']),
					'user_id'		=> intval($A['user_id']),
					'user_name'		=> _es($A['user_name']),
					'created'		=> intval($A['date']),
					'subject'		=> _prepare_html($A['subject']),
					'text'			=> _prepare_html($A['text']),
					'new_topic'		=> 1,
					'active'		=> 1,
				));
				$NEW_POST_ID = db()->INSERT_ID();
				if (empty($NEW_POST_ID)) {
					continue;
				}
			}
			$ids_to_delete[$A['id']] = $A['id'];
		}
		if (!empty($ids_to_delete)) {
			db()->query('DELETE FROM '.db('forum_future_posts').' WHERE id IN('.implode(',', $ids_to_delete).')');
			_class('forum_sync', USER_MODULES_DIR.'forum/')->_sync_board(true);
		}
	}

	/**
	*/
	function _prepare_filter_data () {
	}

	/**
	*/
	function _create_filter_sql ($filter_for = 'posts') {
	}

	/**
	*/
	function _show_filter ($filter_for = 'posts') {
	}

	/**
	*/
	function _save_filter ($silent = false) {
	}

	/**
	*/
	function _clear_filter ($silent = false) {
	}

	/**
	*/
	function _box ($name = '', $selected = '') {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval('return common()->'.$this->_boxes[$name].';');
	}
}
