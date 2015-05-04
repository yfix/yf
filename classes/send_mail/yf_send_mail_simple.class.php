<?php

class yf_send_mail_simple {

	/**
	*/
	function send ($params = array(), &$error = null, $mail) {
		return mail($params['email_to'], $params['subject'], $params['text']/*, ($params['email_from'] ? 'From: '.$params['email_from'] : null)*/);
	}
}
