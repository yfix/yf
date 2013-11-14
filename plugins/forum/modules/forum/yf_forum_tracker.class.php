<?php

/**
* Topics and forums tracker module
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_tracker {

	/**
	* Constructor
	*/
	function _init () {
		// Load user cp module
		$this->USER_CP_OBJ = _class('forum_user', 'modules/forum/');
	}
	
	/**
	* Subscribe Forum
	*/
	function _subscribe_forum() {
		if (!module('forum')->SETTINGS['ALLOW_TRACK_FORUMS']) {
			return module('forum')->_show_error('Forums tracker is disabled');
		}
// TODO
		$body = 'Forum subscribe will be here!';
		return module('forum')->_show_main_tpl($body);
	}
	
	/**
	* Subscribe Topic
	*/
	function _subscribe_topic() {
		if (!module('forum')->SETTINGS['ALLOW_TRACK_TOPIC']) {
			return module('forum')->_show_error('Topic tracker is disabled');
		}
// TODO
		$replace = array(

		);
		$body = tpl()->parse('forum'.'/user_cp/subscribe_topic', $replace);
		if (is_object($this->USER_CP_OBJ)) $body = $this->USER_CP_OBJ->_user_cp_main_tpl($body);
		return module('forum')->_show_main_tpl($body);
	}
	
	/**
	* Manage Forums
	*/
	function _manage_forums() {
		if (!module('forum')->SETTINGS['ALLOW_TRACK_FORUMS']) {
			return module('forum')->_show_error('Forums tracker is disabled');
		}
// TODO
		$replace = array(

		);
		$body = tpl()->parse('forum'.'/user_cp/tracker_topics_main', $replace);
		if (is_object($this->USER_CP_OBJ)) $body = $this->USER_CP_OBJ->_user_cp_main_tpl($body);
		return module('forum')->_show_main_tpl($body);
	}
	
	/**
	* Manage Topics
	*/
	function _manage_topics() {
		if (!module('forum')->SETTINGS['ALLOW_TRACK_TOPIC']) {
			return module('forum')->_show_error('Topic tracker is disabled');
		}
// TODO
		$replace = array(

		);
		$body = tpl()->parse('forum'.'/user_cp/tracker_forums_main', $replace);
		if (is_object($this->USER_CP_OBJ)) $body = $this->USER_CP_OBJ->_user_cp_main_tpl($body);
		return module('forum')->_show_main_tpl($body);
	}
	
	/**
	* Send emails digest
	*/
	function _send_digest($time_type = 'daily', $item_type = 'forum') {
// TODO
		echo 'Send digest will be here...';
	}

	/**
	* Set email notification
	*/
	function _show_main() {
// TODO
/*
		$_GET['id']		= intval($_GET['id']);
		$_GET['page']	= intval($_GET['page']);
		if (FORUM_USER_ID) {
			$EXISTS = db()->query_num_rows('SELECT * FROM '.db('forum_email_notify').' WHERE user_id='.intval(FORUM_USER_ID).' AND topic_id='.intval($_GET['id']));
			if ($EXISTS) $sql = 'DELETE FROM '.db('forum_email_notify').' WHERE user_id='.intval(FORUM_USER_ID).' AND topic_id='.intval($_GET['id']);
			else $sql = 'INSERT INTO '.db('forum_email_notify').' VALUES ('.intval(FORUM_USER_ID).', '.intval($_GET['id']).', '.time().')';
			db()->query($sql);
			js_redirect('./?object='.'forum'.'&action=view_topic&id='.$_GET['id']. ($_GET['page'] ? '&page='.$_GET['page'] : ''));
		}
*/
	}

	/**
	* Send email notifications to all required addresses if allowed
	*/
	function _send_email_notifications () {
// TODO
/*
		if (module('forum')->SETTINGS['SEND_NOTIFY_EMAILS']) return false;
		// Get emails to process
		$Q5 = db()->query('SELECT * FROM '.db('forum_email_notify').' WHERE topic_id='.intval($topic_info['id']));
		while ($A5 = db()->fetch_assoc($Q5)) if (!FORUM_USER_ID || (FORUM_USER_ID != $A5['user_id'])) $notify_user_ids[$A5['user_id']] = $A5['user_id'];
		if (is_array($notify_user_ids) && count($notify_user_ids)) {
			// Process users that wanted to receive notifications for this topic
			$topic_name		= $this->BB_OBJ->_process_text($topic_info['name']);
			$post_text		= $this->BB_OBJ->_process_text(_substr($_POST['text'], 0, 100)).'...';
			$view_topic_url	= process_url('./?object='.'forum'.'&action=view_topic&id='.$topic_info['id']);
			$dont_notify_url= process_url('./?object='.'forum'.'&action=notify_me&id='.$topic_info['id']);
			// Get users details
			$Q6 = db()->query('SELECT user_email AS `0`, name AS 1 FROM '.db('forum_users').' WHERE id IN('.implode(',', $notify_user_ids).') AND status='a'');
			while (list($notify_email, $user_login) = db()->fetch_assoc($Q6)) {
				$replace = array(
					'notify_email'		=> $notify_email,
					'user_name'			=> _prepare_html($user_login),
					'topic_name'		=> $topic_name,
					'post_text'			=> $post_text,
					'view_topic_url'	=> $view_topic_url,
					'dont_notify_url'	=> $dont_notify_url,
					'website'			=> conf('website_name'),
				);
				$text = tpl()->parse('forum'.'/emails/post_notify', $replace);
				common()->send_mail(module('forum')->SETTINGS['ADMIN_EMAIL_FROM'], t('administrator').' '.conf('website_name'), $notify_email, $user_login, t('Post_Notification'), $text, $text);
			}
		}
		// Save user notification
		if (FORUM_USER_ID && $_POST['email_notify']) {
			db()->query('REPLACE INTO '.db('forum_email_notify').' VALUES ('.intval(FORUM_USER_ID).', '.intval($topic_info['id']).', '.time().')');
		}
*/
	}
}
