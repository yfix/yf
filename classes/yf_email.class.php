<?php

/**
* High level html emails wrapper
*/
class yf_email {

	/** @const */
	const table_tpls = 'emails_templates';
	/** @const */
	const table_history = 'emails_history';
	/** @var string */
	public $ADMIN_EMAIL = '';
	/** @var string */
	public $ADMIN_NAME	= '';
	/** @var string */
	public $EMAIL_FROM	= '';
	/** @var string */
	public $NAME_FROM	= '';
	/** @var string */
	public $SITE_NAME	= '';
	/** @var string */
	public $SITE_URL	= '';
	/** @var array List of emails addresses to send all copies to */
	public $SEND_ALL_COPY_TO = array(
	);
	/** @var array List of emails addresses to send user-addressing email copies to */
	public $SEND_ADMIN_COPY_TO = array(
	);
	/** @var array List of emails addresses to send admin-addressing email copies to */
	public $SEND_USER_COPY_TO = array(
	);
	/** @var */
	public $SMTP_CONFIG_DEFAULT = array(
	);
	/** @var */
	public $SMTP_CONFIG_ALTERNATE = array(
	);

	/**
	*/
	function _init() {
		$this->ADMIN_EMAIL	= defined('SITE_ADMIN_EMAIL') && strlen(SITE_ADMIN_EMAIL) ? SITE_ADMIN_EMAIL : 'support@'.$_SERVER['HTTP_HOST'];
		$this->ADMIN_NAME	= defined('SITE_ADMIN_NAME') && strlen(SITE_ADMIN_NAME) ? SITE_ADMIN_NAME : SITE_ADVERT_TITLE.' support';
		$this->EMAIL_FROM	= SITE_ADMIN_EMAIL;
		$this->NAME_FROM	= SITE_ADVERT_NAME;
		$this->SITE_NAME	= SITE_ADVERT_NAME;
		$this->SITE_URL		= SITE_ADVERT_URL;
	}

	/**
	*/
	function _is_mailru ($email) {
		list(,$host) = explode('@', $email);
		if (in_array($host, array('mail.ru','bk.ru','inbox.ru','mail.ua','list.ru'))) {
			return true;
		}
		return false;
	}
	
	/**
	*/
	function _send_email_to_user($user_id, $template_name, $data = array(), $instant_send = true, $require_verified_email = false, $is_wall_update = true, $template_group = '') {
		$instant_send = false;
		$user_data = user($user_id);
		if (empty($user_data)) {
			return false;
		}
		if (empty($data['name'])) {
			$data['name'] = $user_data['name'];
		}
		if (substr($user_data['email'],0,6) == 'oauth.') {
			// no support for oauth-based auths
			return false;
		}
		if ($require_verified_email && ($user_data['email'] != $user_data['email_validated'])) {
			return false;
		}
		if ($is_wall_update) {
			return false;
		}
		if ($is_wall_update && $template_group != '') {
			$a = array();
			if ($user_data['email_wall_updates_config'] != '') {
				$data_config = json_decode($user_data['email_wall_updates_config'], true);
				foreach ($data_config as $v) {
					$a[$v] = $v;
				}
				if (empty($a[$template_group])) {
					return false;
				}				
			} else {
				return false;
			}
		}
		return $this->_send_email_safe($user_data['email'], $user_data['name'], $template_name, $data, $instant_send);
	}

	/**	
	* send_email_from_admin
	*/
	function _send_email_safe($email_to, $name_to, $template_name, $data = array(), $instant_send = true, $override = array()) {
		if (defined('TEST_MODE') && TEST_MODE && !$override['force_send']) {
			common()->message_error('Test mode enabled. Email real sending is disabled');
			return false;
		}
		if (empty($email_to)) {
			return false;
		}
		if (empty($name_to)) {
			$name_to = $email_to;
		}
		list($subject, $html) = $this->_get_email_text($data, array('tpl_name' => $template_name));
		if ($override['subject']) {
			$subject = $override['subject'];
		}
		db()->insert_safe(self::table_history, array(
			'email_to'	=> $email_to,
			'name_to'	=> $name_to,
			'subject'	=> $subject,
			'text'		=> $html,
			'date'		=> $_SERVER['REQUEST_TIME'],
		));
		if ($instant_send) {
			$email_id = db()->insert_id();
			$params = array(
				'from_mail'	=> $this->EMAIL_FROM,
				'from_name'	=> $this->NAME_FROM,
				'to_mail'	=> $email_to,
				'to_name'	=> $name_to,
				'subj'		=> $subject,
				'text'		=> $this->_text_from_html($html),
				'html'		=> $this->_css_to_inline_styles($html),
				'smtp'		=> $this->_is_mailru($email_to) ? $this->SMTP_CONFIG_ALTERNATE : $this->SMTP_CONFIG_DEFAULT,
			);
			$result = common()->send_mail((array)$params);
			if (!$result) {
				trigger_error('Email not sent', E_USER_WARNING);
			} else {
				db()->update_safe(self::table_history, array('status' => 1), 'id='.(int)$email_id);
				$this->_send_copies($params);
			}
			return $result;
		}
		return true;
	}

	/**
	*/
	function _send_to_default_admin($extra = array()) {
		$params = array(
			'from_mail' => $this->EMAIL_FROM,
			'from_name' => $this->NAME_FROM,
			'to_mail'   => $this->ADMIN_EMAIL,
			'to_name'   => $this->ADMIN_NAME,
			'subj'		=> $extra['subject'],
			'text'		=> $this->_text_from_html($extra['html']),
			'html'		=> $this->_css_to_inline_styles($extra['html']),
			'smtp'		=> $this->SMTP_CONFIG_DEFAULT,
		);
		$result = common()->send_mail((array)$params);
		$this->_send_copies($params);
		return $result;
	}

	/**
	*/
	function _get_email_text($replace = array(), $extra = array()){
		if ($extra['tpl_name']) { 
			$a = db()->from(self::table_tpls)->where('name', $extra['tpl_name'])->get();
		}
		if ($extra['subject']) {
			$a['subject'] = $extra['subject'];
		}
		$body = $a['text'] ?: $extra['body'];
		if ($a['parent_id']) {
			$parent = db()->from(self::table_tpls)->whereid($a['parent_id'])->get();
			if ($parent) {
				$body = tpl()->parse_string($parent['text'], array(
					'main_content' => $body,
				));
			}
		}
		$subject = strip_tags($a['subject']);
		if (empty($subject) && empty($body)) {
			return false;
		}
		$replace = (array)$replace + array(
			'site_name'			=> $this->SITE_NAME,
			'site_url'			=> $this->SITE_URL,
			'unsubscribe_url'	=> url_user('/unsubscribe/show/'.$extra['tpl_name'].'-'.time()),
		);
		return array(
			tpl()->parse_string($subject, $replace),
			tpl()->parse_string($body, $replace),
		);
	}

	/**
	* Send copies, mostly for debug and more control on what is going on
	*/
	function _send_copies($params = array()) {
		if (!$params) {
			return false;
		}
		$copy_to = array();
		foreach ((array)$this->SEND_ALL_COPY_TO as $mail_to) {
			$copy_to[$mail_to] = $mail_to;
		}
		if ($email_to === $this->ADMIN_EMAIL) {
			foreach ((array)$this->SEND_ADMIN_COPY_TO as $mail_to) {
				$copy_to[$mail_to] = $mail_to;
			}
		} else {
			foreach ((array)$this->SEND_USER_COPY_TO as $mail_to) {
				$copy_to[$mail_to] = $mail_to;
			}
		}
		$orig_to_mail = strtolower(trim($params['to_mail']));
		$orig_subj = $params['subj'];
		$params['subj'] = '[AUTO-COPY] '.$params['subj'];
		foreach ((array)$copy_to as $mail_to) {
			$mail_to = trim($mail_to);
			if (!$mail_to || strtolower($mail_to) == $orig_to_mail) {
				continue;
			}
			$params['to_mail'] = $mail_to;
			common()->send_mail((array)$params);
		}
		return true;
	}

	/**
	*/
	function _css_to_inline_styles($html = '', $extra = array()) {
		if (!strlen($html) || false === strpos($html, '<')/* || false === strpos($html, 'style=')*/) {
			return $html;
		}
		if (false === strpos($html, '<html') && false === strpos($html, '<body')) {
			$need_raw = true;
			$html = '<!DOCTYPE html><html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"><meta charset="utf-8"></head><body>'.$html.'</body></html>';
		}
		require_php_lib('css_to_inline_styles');
		$cti = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles($html);
		$cti->setEncoding('UTF-8');
		$cti->setUseInlineStylesBlock();
		// $cti->setHTML($html);
		// $cti->setCSS($css);
		$result = $cti->convert();
		if ($need_raw) {
			preg_match('|<body.*>(.*)</body>|isU', $result, $matches);
			$result = $matches[1] ?: $result;
		}
		return $result;
	}

	/**	
	*/
	function _text_from_html($html = '') {
		if (!strlen($html)) {
			return $html;
		}

		$text = trim($html);
#		$text = services()->phpmailer()->html2text($text, $advanced = true);
		$text = trim($this->strip_html_tags($text));
		$text = str_replace("\t", '  ', $text);
		$text = preg_replace("~[\r\n][ ]+~m", PHP_EOL, $text);
		$text = preg_replace("~[\r\n]{2,}~m", PHP_EOL, $text);
		return $text;
	}

	/**
	* Remove HTML tags, including invisible text such as style and
	* script code, and embedded objects.  Add line breaks around
	* block-level tags to prevent word joining after tag removal.
	*
	* source article: http://nadeausoftware.com/articles/2007/09/php_tip_how_strip_html_tags_web_page
	*/
	function strip_html_tags($text) {
		$r = array(
			// Remove invisible content
			'@<head[^>]*?>.*?</head>@siu'			=> ' ',
			'@<style[^>]*?>.*?</style>@siu'			=> ' ',
			'@<script[^>]*?.*?</script>@siu'		=> ' ',
			'@<object[^>]*?.*?</object>@siu'		=> ' ',
			'@<embed[^>]*?.*?</embed>@siu'			=> ' ',
			'@<applet[^>]*?.*?</applet>@siu'		=> ' ',
			'@<noframes[^>]*?.*?</noframes>@siu'	=> ' ',
			'@<noscript[^>]*?.*?</noscript>@siu'	=> ' ',
			'@<noembed[^>]*?.*?</noembed>@siu'		=> ' ',
			// Add line breaks before and after blocks
			'@</?((address)|(blockquote)|(center)|(del))@iu'			=> "\n\$0",
			'@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu'			=> "\n\$0",
			'@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu'		=> "\n\$0",
			'@</?((table)|(th)|(td)|(caption))@iu'						=> "\n\$0",
			'@</?((form)|(button)|(fieldset)|(legend)|(input))@iu'		=> "\n\$0",
			'@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu'	=> "\n\$0",
			'@</?((frameset)|(frame)|(iframe))@iu'						=> "\n\$0",
		);
		$text = preg_replace(array_keys($r), array_values($r), $text);
		return strip_tags($text);
	}
}
