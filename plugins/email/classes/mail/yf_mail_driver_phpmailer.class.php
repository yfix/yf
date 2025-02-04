<?php

load('mail_driver', '', 'classes/mail/');
class yf_mail_driver_phpmailer extends yf_mail_driver
{
    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }


    public function _init()
    {
        $this->PARENT = _class('send_mail');
    }


    public function send(array $params = [], &$error_message = '')
    {
        require_php_lib('phpmailer');

        $mail = new PHPMailer(true); // defaults to using php 'mail()'
        try {
            $mail->CharSet = $params['charset'] ?: conf('charset') ?: $this->PARENT->DEFAULT_CHARSET ?: 'utf-8';
            $mail->From = $params['email_from'];
            $mail->FromName = $params['name_from'];
            if (DEBUG_MODE && $this->PARENT->MAIL_DEBUG) {
                $mail->SMTPDebug = 1;
                $mail->Debugoutput = $params['phpmailer_debug_output'] ?: 'error_log';
            }
            if (is_array($params['email_to'])) {
                list($name, $email) = [ key($params['email_to']), current($params['email_to']) ];
                array_shift($params['email_to']);
                $mail->AddAddress($email, $name);
            } else {
                $mail->AddAddress($params['email_to'], $params['name_to']);
            }
            $mail->Subject = $params['subject'];
            if (empty($params['html'])) {
                $mail->Body = $params['text'];
            } else {
                $mail->IsHTML(true);
                $mail->Body = $params['html'];
                $mail->AltBody = $params['text'];
            }
            if ($this->PARENT->ALLOW_ATTACHMENTS) {
                foreach ((array) $params['attaches'] as $name => $file) {
                    $file_name = is_string($name) ? $name : '';
                    $mail->AddAttachment($file, $file_name);
                }
            }
            $smtp = $params['smtp'];
            if ($smtp['smtp_host']) {
                $mail->IsSMTP();
                $mail->Host = $smtp['smtp_host'];
                $mail->Port = $smtp['smtp_port'];
                $mail->SMTPAuth = $smtp['smtp_auth'];
                $mail->Username = $smtp['smtp_user_name'];
                $mail->Password = $smtp['smtp_password'];
                $mail->SMTPSecure = $smtp['smtp_secure'] ?: false;
            }
            if (is_callable($params['on_before_send'])) {
                $callback = $params['on_before_send'];
                $callback($mail, $params, $this->PARENT);
            }
            $result = $mail->Send();
            if (is_array($params['email_to']) && ! empty($params['email_to'])) {
                foreach ($params['email_to'] as $name => $email) {
                    $mail->clearAddresses();
                    $mail->AddAddress($email, $name);
                    $r = $mail->Send();
                    $result = $result && $r;
                }
            }
        } catch (phpmailerException $e) {
            $error_message .= $e->errorMessage(); // Pretty error messages from PHPMailer
        } catch (Exception $e) {
            $error_message .= $e->getMessage(); // Boring error messages from anything else!
        }
        if ( ! $result) {
            $error_message .= $mail->ErrorInfo;
        }
        if (is_callable($params['on_after_send'])) {
            $callback = $params['on_after_send'];
            $callback($mail, $params, $result, $error_message, $this->PARENT);
        }
        if (@$error_message && DEBUG_MODE && $this->PARENT->MAIL_DEBUG_ERROR) {
            trigger_error($error_message, E_USER_WARNING);
        }
        $this->PARENT->_last_error_message = $error_message;
        return $result;
    }
}
