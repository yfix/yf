<?php

load('mail_driver', '', 'classes/mail/');
class yf_mail_driver_mailgun extends yf_mail_driver
{
    /** @var string The Mailgun API key */
    public $key;
    /** @var string The Mailgun domain. */
    public $domain;
    /** @var string THe Mailgun API end-point. */
    public $url;

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
     * https://documentation.mailgun.com/api-sending.html#examples.
     * @param mixed $error_message
     */
    public function send(array $params = [], &$error_message = '')
    {
        require_php_lib('mailgun');

        $error_message = null;
        try {
            $mg = new Mailgun\Mailgun($this->key);
            $opts = $this->PARENT->ALLOW_ATTACHMENTS ? ['attachment' => array_values($params['attaches'])] : [];
            // Now, compose and send your message.
            $result = $mg->sendMessage($this->domain, [
                'from' => $params['email_from'],
                'to' => $params['email_to'],
                'subject' => $params['subject'],
                'text' => $params['text'],
                'html' => $params['html'],
            ], $opts);
        } catch (Exception $e) {
            $error_message = 'A mailgun error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
        }
        if (@$error_message && DEBUG_MODE && $this->PARENT->MAIL_DEBUG_ERROR) {
            trigger_error($error_message, E_USER_WARNING);
        }
        if (is_callable($params['on_after_send'])) {
            $callback = $params['on_after_send'];
            $callback($mail, $params, $result, $error_message, $this->PARENT);
        }
        $this->PARENT->_last_error_message = $error_message;
        return $result && ! $error_message ? true : false;
    }
}
