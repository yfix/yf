<?php

load('mail_driver', '', 'classes/mail/');
class yf_mail_driver_sendgrid extends yf_mail_driver
{
    /** @var string The SendGrid API key. */
    public $key;

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

    /**
     * https://github.com/sendgrid/sendgrid-php/blob/master/examples/helpers/mail/example.php.
     */
    public function send(array $params = [], &$error_message = '')
    {
        require_php_lib('sendgrid');

        $error_message = null;
        try {
            $sg = new \SendGrid($this->key);

            $from = new SendGrid\Email($params['name_from'], $params['email_from']);
            $subject = $params['subject'];
            $to = new SendGrid\Email($params['name_to'], $params['email_to']);
            $text_content = new SendGrid\Content('text/plain', $params['text']);
            $mail = new SendGrid\Mail($from, $subject, $to, $text_content);

            $html_content = new SendGrid\Content('text/html', $params['html']);
            $mail->addContent($html_content);

            if ($params['reply_to']) {
                $reply_to = new SendGrid\ReplyTo($params['reply_to']);
                $mail->setReplyTo($reply_to);
            }
            if ($this->PARENT->ALLOW_ATTACHMENTS) {
                foreach ((array) $params['attaches'] as $name => $file) {
                    $attachment = new SendGrid\Attachment();
                    $attachment->setContent(file_get_contents($file));
                    $attachment->setFilename($name);
                    $mail->addAttachment($attachment);
                }
            }
            $response = $sg->client->mail()->send()->post($mail);
        } catch (Exception $e) {
            $error_message = 'A sendgrid error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
        }
        if (@$error_message && DEBUG_MODE && $this->PARENT->MAIL_DEBUG_ERROR) {
            trigger_error($error_message, E_USER_WARNING);
        }
        if (is_callable($params['on_after_send'])) {
            $callback = $params['on_after_send'];
            $callback($mail, $params, $result, $error_message, $this->PARENT);
        }
        $this->PARENT->_last_error_message = $error_message;
        return $response && ! $error_message ? true : false;
    }
}
