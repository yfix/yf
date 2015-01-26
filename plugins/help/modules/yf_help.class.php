<?php

// Help section module
class yf_help extends yf_module {

	/** @var string */
	public $TICKET_DELIM	= 'yf';
	/** @var array @conf_skip */
	public $_comments_params = array(
		'return_action'		=> 'view_answers',
		'stpl_main'			=> 'help/answers_main',
		'stpl_item'			=> 'help/answers_item',
		'stpl_add'			=> 'help/answers_add_form',
		'stpl_edit'			=> 'help/answers_edit_form',
		'allow_guests_posts'=> 1,
	);
	/** @var array */
	public $_priorities = array(
		1	=> 'Low',
		2	=> 'Medium',
		3	=> 'High',
		4	=> 'Urgent',
	);
	/** @var array */
	public $_ticket_statuses = array(
		'new'		=> 'new',
		'read'		=> 'read',
		'open'		=> 'open',
		'closed'	=> 'closed',
	);
	/** @var bool */
	public $ALLOW_CLOSE_OWN_TICKETS	= 1;
	/** @var bool */
	public $ALLOW_DELETE_OWN_TICKETS	= 0;
	/** @var bool */
	public $ALLOW_REOPEN_OWN_TICKETS	= 1;
	/** @var bool */
	public $ALLOW_EDIT_OWN_ANSWERS		= 0;
	/** @var bool */
	public $ALLOW_DELETE_OWN_ANSWERS	= 0;

	/**
	*/
	function _init () {
		$GLOBALS['PROJECT_CONF']['comments']['AUTO_FILTER_INPUT_TEXT'] = 0;
		$this->_priorities = t($this->_priorities);
		$this->_ticket_statuses = t($this->_ticket_statuses);
		$this->_help_cats	= array('' => '') + (array)_class('cats')->_prepare_for_box('', 0);
		$this->CAPTCHA = _class('captcha');
	}

	/**
	*/
	function show () {
		return tpl()->parse($_GET['object'].'/main', array(
			'add_link_url'	=> main()->USER_ID ? './?object=links&action=account'._add_get() : './?object=links&action=login',
		));
	}

	/**
	*/
	function email_form ($a = array()) {
		if (!count($a) && main()->USER_ID) {
			$a = user(main()->USER_ID, array('name','email','nick')) + (array)$a;
		}
		$a['name'] = _display_name($a);
		return form($a + $_POST, array(
				'legend' => 'Contact us',
			))
			->validate(array(
				'name' => 'trim|required',
				'email' => 'trim|required|valid_email',
				'subject' => 'trim|required',
				'message' => 'trim|required',
				'captcha' => 'trim|captcha',
			))
			->on_validate_ok(function($data){
				$this->_send_email($data);
			})
			->text('name')
			->email('email')
			->text('subject')
			->textarea('message')
			->select_box('priority', $this->_priorities)
			->select_box('category', $this->_help_cats)
			->textarea('urls', 'Related URLs')
			->captcha()
			->save('', 'Send')
		;
	}

	/**
	*/
	function _send_email ($data) {
		$RUN_TIME = time();
		if (main()->USER_ID) {
			$user_id = main()->USER_ID;
		} else {
			$user_id = db()->from('user')->select('id')->where('email', $data['email'])->get_one();
		}
		$TICKET_ID = md5(microtime(true). $user_id. $data['email']. $data['message']);
		db()->insert_safe('help_tickets', array(
			'ticket_key'		=> $TICKET_ID,
			'user_id'			=> $user_id,
			'name'				=> $data['name'],
			'email'				=> $data['email'],
			'subject'			=> $data['subject'],
			'user_priority'		=> $data['priority'],
			'admin_priority'	=> $data['priority'],
			'category_id'		=> $data['cat_id'],
			'message'			=> $data['message'],
			'urls'				=> $data['urls'],
			'opened_date'		=> $RUN_TIME,
			'closed_date'		=> 0,
			'admin_comments'	=> '',
			'status'			=> 'new',
			'user_agent'		=> $_SERVER['HTTP_USER_AGENT'],
			'ip'				=> common()->get_ip(),
			'cookies_enabled'	=> (int)conf('COOKIES_ENABLED'),
			'site_id'			=> (int)conf('SITE_ID'),
			'referer'			=> $_SERVER['HTTP_REFERER'],
		));

		// Send emails in background
#		js_redirect('./?object='.$_GET['object'].'&action=email_sent&id='.$TICKET_ID);
#		ignore_user_abort(1);

		$admin_email = defined('SITE_ADMIN_HELP_EMAIL') ? SITE_ADMIN_HELP_EMAIL : (defined('SITE_ADMIN_EMAIL') ? SITE_ADMIN_EMAIL : 'admin@'.parse_url(WEB_PATH, PHP_URL_HOST));
		$admin_name = (defined('SITE_ADVERT_NAME') ? SITE_ADVERT_NAME : parse_url(WEB_PATH, PHP_URL_HOST)).' admin';
		$subject = 'Help: '.$data['priority']. ':'. $data['category']. $data['subject'];

		// Try to send mail to admin
		common()->send_mail(array(
			'from_mail' => $data['email'],
			'from_name'	=> $data['name'],
			'to_mail'	=> $admin_email,
			'to_name'	=> $admin_name,
			'subj'		=> $subject,
			'text'		=> $text,
			'html'		=> nl2br($html),
		));
		// Try to send mail to user
		$text = tpl()->parse('help/email_to_user', array(
			'request_subject'	=> $data['subject'],
			'request_message'	=> $data['message'],
			'view_answers_link'	=> process_url('./?object=help&action=view_answers&id='.$TICKET_ID),
		));
		common()->send_mail(array(
			'from_mail' => $admin_email,
			'from_name'	=> $admin_name,
			'to_mail'	=> $data['email'],
			'to_name'	=> _prepare_html($data['name']),
			'subj'		=> $subject,
			'text'		=> $text,
			'html'		=> nl2br($html),
		));

		js_redirect('./?object='.$_GET['object'].'&action=email_sent&id='.$TICKET_ID);
	}

	/**
	*/
	function email_sent () {
		return tpl()->parse($_GET['object'].'/email_sent', array(
			'view_answers_link'	=> './?object='.$_GET['object'].'&action=view_answers&id='.$_GET['id'],
		));
	}

	/**
	*/
	function view_tickets () {
		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}
		$sql = 'SELECT * FROM '.db('help_tickets').' WHERE user_id='.intval(main()->USER_ID);
		list($add_sql, $pages, $total) = common()->divide_pages($sql);

		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$CUR_TICKET_ID = $A['ticket_key'];
			$ALLOW_DELETE	= $this->ALLOW_DELETE_OWN_TICKETS;
			$replace2 = array(
				'bg_class'		=> !(++$i % 2) ? 'bg1' : 'bg2',
				'ticket_id'		=> _prepare_html($CUR_TICKET_ID),
				'subject'		=> _prepare_html($A['subject']),
				'priority'		=> $this->_priorities[$A['admin_priority']],
				'cat_name'		=> $this->_help_cats[$A['category_id']],
				'opened_date'	=> _format_date($A['opened_date'], 'long'),
				'closed_date'	=> !empty($A['closed_date']) ? _format_date($A['closed_date'], 'long') : '',
				'status'		=> _prepare_html($A['status']),
				'view_link'		=> './?object='.$_GET['object'].'&action=view_answers&id='.$CUR_TICKET_ID,
				'delete_link'	=> $ALLOW_DELETE ? './?object='.$_GET['object'].'&action=delete_ticket&id='.$CUR_TICKET_ID : '',
			);
			$items .= tpl()->parse($_GET['object'].'/view_tickets_item', $replace2);
		}
		$replace = array(
			'items'		=> $items,
			'pages'		=> $pages,
			'total'		=> intval($total),
		);
		return tpl()->parse($_GET['object'].'/view_tickets_main', $replace);
	}

	/**
	*/
	function close_ticket () {
		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}
		$TICKET_ID		= $_GET['id'];
		if (!empty($TICKET_ID)) {
			$ticket_info = db()->from('help_tickets')->where('ticket_key', $TICKET_ID)->get();
		}
		if (empty($ticket_info)) {
			return _e('No such ticket!');
		}
		if (!$this->ALLOW_CLOSE_OWN_TICKETS) {
			return _e('You are not allowed to close tickets!');
		}
		if ($ticket_info['status'] == 'closed') {
			return _e('This ticket is already closed!');
		}
		if (main()->is_post()) {
			db()->UPDATE('help_tickets', array(
				'status'		=> 'closed',
				'closed_date'	=> time(),
			), 'id='.intval($ticket_info['id']));
		}
		return js_redirect('./?object='.$_GET['object'].'&action=view_answers&id='.$_GET['id']);
	}

	/**
	*/
	function reopen_ticket () {
		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}
		$TICKET_ID = $_GET['id'];
		if (!empty($TICKET_ID)) {
			$ticket_info = db()->from('help_tickets')->where('ticket_key', $TICKET_ID)->get();
		}
		if (empty($ticket_info)) {
			return _e('No such ticket!');
		}
		if (!$this->ALLOW_REOPEN_OWN_TICKETS) {
			return _e('You are not allowed to re-open tickets!');
		}
		if ($_POST && $ticket_info['status'] == 'closed') {
			db()->UPDATE('help_tickets', array(
				'status'		=> 'open',
				'closed_date'	=> 0,
			), 'id='.intval($ticket_info['id']));
		}
		return js_redirect('./?object='.$_GET['object'].'&action=view_answers&id='.$_GET['id']);
	}

	/**
	*/
	function add_comment () {
		$TICKET_ID		= $_GET['id'];
		if (!empty($TICKET_ID)) {
			$ticket_info = db()->from('help_tickets')->where('ticket_key', $TICKET_ID)->get();
		}
		if (empty($ticket_info)) {
			return _e('No such ticket!');
		}
		if ($ticket_info['status'] == 'closed') {
			db()->UPDATE('help_tickets', array(
				'status'		=> 'open',
				'closed_date'	=> 0,
			), 'id='.intval($ticket_info['id']));
		}
		if (!main()->USER_ID && !$_POST['user_name']) {
			$_POST['user_name'] = $ticket_info['name'];
		}
		$this->_comments_params['object_id'] = $ticket_info['id'];

		$COMMENTS_OBJ = module('comments');
		ob_start(); // To prevent wrong redirect
		$COMMENTS_OBJ->_add($this->_comments_params);
		ob_end_clean();

		return js_redirect('./?object='.$_GET['object'].'&action=view_answers&id='.$ticket_info['ticket_key']);
	}

	/**
	*/
	function delete_ticket () {
		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}
		$TICKET_ID		= $_GET['id'];
		if (!empty($TICKET_ID)) {
			$ticket_info = db()->from('help_tickets')->where('ticket_key', $TICKET_ID)->get();
		}
		if (empty($ticket_info)) {
			return _e('No such ticket!');
		}
		if (!$this->ALLOW_DELETE_OWN_TICKETS) {
			return _e('You are not allowed to delete own tickets!');
		}
		common()->_remove_activity_points(main()->USER_ID, 'bug_report', $ticket_info['id']);

		db()->query('DELETE FROM '.db('help_tickets').' WHERE id='.intval($ticket_info['id']));
		db()->query('DELETE FROM '.db('comments').' WHERE object_name="'.$_GET['object'].'" AND object_id='.intval($ticket_info['id']));

		return js_redirect('./?object='.$_GET['object'].'&action=view_tickets');
	}

	/**
	*/
	function view_answers () {
		$TICKET_ID		= $_GET['id'];
		if (!empty($TICKET_ID)) {
			$ticket_info = db()->from('help_tickets')->where('ticket_key', $TICKET_ID)->get();
		}
		if (empty($ticket_info)) {
			return _e('No such ticket!');
		}
		$this->_cur_ticket_info = $ticket_info;
		$this->_ticket_is_closed = $ticket_info['status'] == 'closed' || !empty($ticket_info['closed_date']);
		$num_comments = $this->_get_num_comments($ticket_info['id']);
		$total = $num_comments[$ticket_info['id']];

		$this->_comments_params['object_id'] = $ticket_info['id'];
		$this->_comments_params['add_form_action'] = './?object='.$_GET['object'].'&action=add_comment&id='.$TICKET_ID;

		$replace = array(
			'form_action'			=> !$ticket_is_closed ? './?object='.$_GET['object'].'&action=do_answer&id='.$TICKET_ID : '',
			'items'					=> $items,
			'pages'					=> $pages,
			'total'					=> intval($total),
			'ticket_id'				=> $TICKET_ID,
			'ticket_subject'		=> _prepare_html($ticket_info['subject']),
			'ticket_message'		=> nl2br(_prepare_html($ticket_info['message'])),
			'ticket_opened_date'	=> _format_date($ticket_info['opened_date'], 'long'),
			'ticket_closed_date'	=> !empty($ticket_info['closed_date']) ? _format_date($ticket_info['closed_date'], 'long') : '',
			'ticket_priority'		=> _prepare_html($this->_priorities[$ticket_info['admin_priority']]),
			'ticket_category'		=> _prepare_html($this->_help_cats[$ticket_info['category_id']]),
			'ticket_urls'			=> nl2br(_prepare_html($ticket_info['urls'])),
			'ticket_status'			=> _prepare_html($ticket_info['status']),
			'ticket_is_closed'		=> intval($this->_ticket_is_closed),
			'close_link'			=> main()->USER_ID && $this->ALLOW_CLOSE_OWN_TICKETS && !$this->_ticket_is_closed ? './?object='.$_GET['object'].'&action=close_ticket&id='.$TICKET_ID : '',
			'reopen_link'			=> main()->USER_ID && $this->ALLOW_REOPEN_OWN_TICKETS && $this->_ticket_is_closed ? './?object='.$_GET['object'].'&action=reopen_ticket&id='.$TICKET_ID : '',
			'answers'				=> $this->_view_comments(),
		);
		return tpl()->parse($_GET['object'].'/view_answers_main', $replace);
	}

	/**
	*/
	function _comment_is_allowed ($params = array()) {
		// Check for tickets opened by guests for guests
		if (empty(main()->USER_ID)) {
			if (!empty($this->_cur_ticket_info['user_id']) || $this->_ticket_is_closed) {
				return false;
			}
		}
		return true;
	}

	/**
	*/
	function _comment_edit_allowed ($params = array()) {
		$edit_allowed	= main()->USER_ID && $this->ALLOW_EDIT_OWN_ANSWERS && $params['user_id'] && $params['user_id'] == main()->USER_ID;
		return (bool)$edit_allowed;
	}

	/**
	*/
	function _comment_delete_allowed ($params = array()) {
		$delete_allowed	= main()->USER_ID && $this->ALLOW_DELETE_OWN_ANSWERS && $params['user_id'] && $params['user_id'] == main()->USER_ID;
		return (bool)$delete_allowed;
	}
}
