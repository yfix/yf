<?php

class yf_send_mail_internal {

	/**
	*/
	function send ($params = array(), &$error = null, $mail) {
		if (!strlen($params['charset'])) {
			$params['charset'] = conf('charset');
		}
		if (!strlen($params['charset'])) {
			$params['charset'] = $mail->DEFAULT_CHARSET ?: 'utf-8';
		}
		$mailer_name = 'YF PHP Mailer';

		$text = $params['text'] ?: 'Sorry, but you need an html mailer to read this mail.';

		$OB = '----=_OuterBoundary_000';
		$IB = '----=_InnerBoundery_001';
		$headers  = 'MIME-Version: 1.0'."\r\n";
		// Strange behaviour on windows,
		if (OS_WINDOWS) {
			$headers .= $params['email_from']	? 'From:'.$params['email_from']."\r\n"		: '';
			$headers .= $params['email_to']		? 'To:'.$params['email_to']."\r\n"			: '';
			$headers .= $params['email_from']	? 'Reply-To:'.$params['email_from']."\r\n"	: '';
		} else {
			$headers .= $params['email_from']	? 'From:'.$params['name_from'].'<'.$params['email_from'].">\r\n"		: '';
			$headers .= $params['email_to']		? 'To:'.$params['name_to'].'<'.$params['email_to'].">\r\n"				: '';
			$headers .= $params['email_from']	? 'Reply-To:'.$params['name_from'].'<'.$params['email_from'].">\r\n"	: '';
		}
		$headers .= "X-Priority:".intval($params['priority'] ?: 3)."\r\n";
		$headers .= "X-Mailer:".$mailer_name."\r\n";
		$headers .= "Content-Type:multipart/mixed;\r\n\tboundary=\"".$OB."\"\r\n";
		// Messages start with text/html alternatives in OB
		$msg  = "This is a multi-part message in MIME format.\r\n";
		$msg .= "\r\n--".$OB."\r\n";
		if (strlen($params['text']) || strlen($params['html'])) {
			$msg .= "Content-Type: multipart/alternative;\r\n\tboundary=\"".$IB."\"\r\n\r\n";
		}
		// plaintext section
		if (strlen($params['text'])) {
			$msg .= "\r\n--".$IB."\r\n";
			$msg .= "Content-Type: text/plain;\r\n\tcharset=\"".$params['charset']."\"\r\n";
			$msg .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
			// plaintext goes here
			$msg .= $params['text']."\r\n\r\n";
		}
		// html section
		if (strlen($params['html'])) {
			$msg .= "\r\n--".$IB."\r\n";
			$msg .= "Content-Type: text/html;\r\n\tcharset=\"".$params['charset']."\"\r\n";
			$msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
			// html goes here
			$msg .= chunk_split(base64_encode($params['html']))."\r\n\r\n";
		}
		// end of IB
		if (strlen($params['text']) || strlen($params['html'])) {
			$msg .= "\r\n--".$IB."--\r\n";
		}
		// attachments
		if ($mail->ALLOW_ATTACHMENTS) {
			foreach ((array)$params['attaches'] as $att_file) {
				$file_name = basename($att_file);
				$msg .= "\r\n--".$OB."\r\n";
				$msg .= "Content-Type: application/octetstream;\r\n\tname=\"".$file_name."\"\r\n";
				$msg .= "Content-Transfer-Encoding: base64\r\n";
				$msg .= "Content-Disposition: attachment;\r\n\tfilename=\"".$file_name."\"\r\n\r\n";
				// file goes here
				$msg .= chunk_split(base64_encode(@file_get_contents($att_file)));
				$msg .= "\r\n\r\n";
			}
		}
		// message ends
		$msg .= "\r\n--".$OB."--\r\n";
		// Send composed email
		return mail($params['email_to'], $params['subject'], $msg, $headers);
	}
}
