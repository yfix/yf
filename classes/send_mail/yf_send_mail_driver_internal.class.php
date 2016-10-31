<?php

class yf_send_mail_driver_internal {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function _init() {
		$this->PARENT = _class('send_mail');
	}

	/**
	*/
	function send ($params = [], &$error_message = '') {
		$charset = $charset ?: conf('charset') ?: $this->PARENT->DEFAULT_CHARSET ?: 'utf-8';
		$CRLF = "\r\n";
		$TAB = "\t";

		$mailer_name = 'YF PHP Mailer';
		$text = $text ?: 'Sorry, but you need an html mailer to read this mail.';

		$OB = '----=_OuterBoundary_000';
		$IB = '----=_InnerBoundery_001';
		$headers  = 'MIME-Version: 1.0'. $CRLF;
		$headers .= $email_from	? 'From:'.$name_from.'<'.$email_from.'>'. $CRLF		: '';
		$headers .= $email_to	? 'To:'.$name_to.'<'.$email_to.'>'. $CRLF			: '';
		$headers .= $email_from ? 'Reply-To:'.$name_from.'<'.$email_from.'>'. $CRLF	: '';

		$headers .= 'X-Priority:'.intval($priority). $CRLF;
		$headers .= 'X-Mailer:'.$mailer_name. $CRLF;
		$headers .= 'Content-Type:multipart/mixed;'. $CRLF. $TAB. 'boundary="'.$OB.'"'.$CRLF;
		// Messages start with text/html alternatives in OB
		$msg  = 'This is a multi-part message in MIME format.'. $CRLF;
		$msg .= $CRLF. '--'. $OB. $CRLF;
		if (strlen($text) || strlen($html)) {
			$msg .= 'Content-Type: multipart/alternative;'. $CRLF. $TAB. 'boundary="'. $IB. '"'. $CRLF. $CRLF;
		}
		// plaintext section
		if (strlen($text)) {
			$msg .= $CRLF. '--'. $IB. $CRLF;
			$msg .= 'Content-Type: text/plain;'. $CRLF. $TAB. 'charset="'.$charset.'"'. $CRLF;
			$msg .= 'Content-Transfer-Encoding: quoted-printable'. $CRLF. $CRLF;
			// plaintext goes here
			$msg .= $text. $CRLF. $CRLF;
		}
		// html section
		if (strlen($html)) {
			$msg .= $CRLF. '--'. $IB. $CRLF;
			$msg .= 'Content-Type: text/html;'. $CRLF. $TAB. 'charset="'.$charset.'"'.$CRLF;
			$msg .= 'Content-Transfer-Encoding: base64'. $CRLF. $CRLF;
			// html goes here
			$msg .= chunk_split(base64_encode($html)). $CRLF. $CRLF;
		}
		// end of IB
		if (strlen($text) || strlen($html)) {
			$msg .= $CRLF.'--'.$IB.'--'.$CRLF;
		}
		// attachments
		if ($this->ALLOW_ATTACHMENTS) {
			foreach ((array)$attaches as $att_file) {
				$file_name = basename($att_file);
				$msg .= $CRLF. '--'. $OB. $CRLF;
				$msg .= 'Content-Type: application/octetstream;'. $CRLF. $TAB. 'name="'.$file_name.'"'. $CRLF;
				$msg .= 'Content-Transfer-Encoding: base64'. $CRLF;
				$msg .= 'Content-Disposition: attachment;'. $CRLF. $TAB. 'filename="'.$file_name.'"'. $CRLF. $CRLF;
				// file goes here
				$msg .= chunk_split(base64_encode(@file_get_contents($att_file)));
				$msg .= $CRLF. $CRLF;
			}
		}
		// message ends
		$msg .= $CRLF. '--'. $OB. '--'. $CRLF;
		// Send composed email
		return mail($email_to, $subject, $msg, $headers);
	}
}
