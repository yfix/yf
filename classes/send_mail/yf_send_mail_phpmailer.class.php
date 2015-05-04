<?php

class yf_send_mail_phpmailer {

	/**
	*/
	function send ($params = array(), &$error = null, $mail) {
		require_php_lib('phpmailer');

		// defaults to using php 'mail()'
		$phpmailer = new PHPMailer(true);
		try {
			$phpmailer->CharSet  = $params['charset'] ?: $mail->DEFAULT_CHARSET;
			$phpmailer->From     = $params['email_from'];
			$phpmailer->FromName = $params['name_from'];
			if (DEBUG_MODE && $mail->MAIL_DEBUG) {
				$phpmailer->SMTPDebug = 1;
				$phpmailer->Debugoutput = 'error_log';
			}
			if (is_array($email_to)) {
				list($name, $email) = each($params['email_to']);
				array_shift($params['email_to']);
				$phpmailer->AddAddress($email, $name);
			} else {
				$phpmailer->AddAddress($params['email_to'], $params['name_to']);
			}
			$phpmailer->Subject = $params['subject'];
			if (empty($html)) {
				$phpmailer->Body = $params['text'];
			} else {
				$phpmailer->IsHTML(true);
				$phpmailer->Body    = $params['html'];
				$phpmailer->AltBody = $params['text'];
			}
			if ($mail->ALLOW_ATTACHMENTS) {
				foreach ((array)$params['attaches'] as $name => $file) {
					$file_name = is_string($name) ? $name : '';
					$phpmailer->AddAttachment($file, $file_name);
				}
			}
			if ($mail->FORCE_USE_SMTP && $mail->SMTP_OPTIONS['smtp_host']) {
				$smtp = $mail->SMTP_OPTIONS;
				$phpmailer->IsSMTP();
				$phpmailer->Host       = $smtp['smtp_host'];
				$phpmailer->Port       = $smtp['smtp_port'];
				$phpmailer->SMTPAuth   = $smtp['smtp_auth'];
				$phpmailer->Username   = $smtp['smtp_user_name'];
				$phpmailer->Password   = $smtp['smtp_password'];
				$phpmailer->SMTPSecure = $smtp['smtp_secure'] ?: false;
			}
			$result = $phpmailer->Send();
/*
			if (is_array($params['email_to']) && !empty($params['email_to'])) {
				foreach ((array)$params['email_to'] as $name => $email) {
					$phpmailer->clearAddresses();
					$phpmailer->AddAddress($email, $name);
					$r = $phpmailer->Send();
					$result = $result && $r;
				}
			}
*/
		} catch (phpmailerException $e) {
			// Pretty error messages from PHPMailer
			$error .= $e->errorMessage();
		} catch (Exception $e) {
			// Boring error messages from anything else!
			$error .= $e->getMessage();
		}
		if (!$result) {
			$error .= $phpmailer->ErrorInfo;
		}
		if (DEBUG_MODE && $mail->MAIL_DEBUG) {
			echo $error;
		}
		return $result;
	}
}
