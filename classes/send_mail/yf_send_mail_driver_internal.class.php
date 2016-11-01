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
		$CRLF = "\r\n";
		$TAB = "\t";

		$params['charset'] = $params['charset'] ?: conf('charset') ?: $this->PARENT->DEFAULT_CHARSET ?: 'utf-8';
		$params['mailer_name'] = $params['mailer_name'] ?: $this->PARENT->DEFAULT_MAILER_NAME;
		$params['text'] = $params['text'] ?: 'Sorry, but you need an html mailer to read this mail.';

		$OB = '----=_OuterBoundary_000';
		$IB = '----=_InnerBoundery_001';

		$headers  = 'MIME-Version: 1.0'. $CRLF;
		$headers .= $params['email_from'] ? 'From:'.$params['name_from'].'<'.$params['email_from'].'>'. $CRLF : '';
		$headers .= $params['email_to']	? 'To:'.$params['name_to'].'<'.$params['email_to'].'>'. $CRLF : '';
		$headers .= $params['email_from'] ? 'Reply-To:'.$params['name_from'].'<'.$params['email_from'].'>'. $CRLF : '';

		$params['priority'] && $headers .= 'X-Priority:'.intval($params['priority']). $CRLF;
		$headers .= 'X-Mailer:'.$params['mailer_name']. $CRLF;
		$headers .= 'Content-Type:multipart/mixed;'. $CRLF. $TAB. 'boundary="'.$OB.'"'.$CRLF;
		// Messages start with text/html alternatives in OB
		$msg  = 'This is a multi-part message in MIME format.'. $CRLF;
		$msg .= $CRLF. '--'. $OB. $CRLF;
		if (strlen($params['text']) || strlen($params['html'])) {
			$msg .= 'Content-Type: multipart/alternative;'. $CRLF. $TAB. 'boundary="'. $IB. '"'. $CRLF. $CRLF;
		}
		// plaintext section
		if (strlen($params['text'])) {
			$msg .= $CRLF. '--'. $IB. $CRLF;
			$msg .= 'Content-Type: text/plain;'. $CRLF. $TAB. 'charset="'.$params['charset'].'"'. $CRLF;
			$msg .= 'Content-Transfer-Encoding: quoted-printable'. $CRLF. $CRLF;
			// plaintext goes here
			$msg .= $params['text']. $CRLF. $CRLF;
		}
		// html section
		if (strlen($params['html'])) {
			$msg .= $CRLF. '--'. $IB. $CRLF;
			$msg .= 'Content-Type: text/html;'. $CRLF. $TAB. 'charset="'.$params['charset'].'"'.$CRLF;
			$msg .= 'Content-Transfer-Encoding: base64'. $CRLF. $CRLF;
			// html goes here
			$msg .= chunk_split(base64_encode($params['html'])). $CRLF. $CRLF;
		}
		// end of IB
		if (strlen($params['text']) || strlen($params['html'])) {
			$msg .= $CRLF.'--'.$IB.'--'.$CRLF;
		}
		// attachments
		if ($this->ALLOW_ATTACHMENTS) {
			foreach ((array)$params['attaches'] as $att_file) {
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
		return mail(
			$params['email_to']
			, $params['subject']
			, $msg
			, $headers
			, implode('', $params['mta_params'])
		);
	}
}
