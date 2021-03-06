<?php

load('mail_driver', '', 'classes/mail/');
class yf_mail_driver_simple extends yf_mail_driver
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
        ! is_array($params['headers']) && $params['headers'] = [];
        $params['email_from'] && $params['headers']['from'] = 'From: ' . $params['email_from'];
        $params['reply_to'] && $params['headers']['reply_to'] = 'Reply-To: ' . $params['reply_to'];
        return mail(
            $params['email_to'],
            $params['subject'],
            $params['text'],
            implode("\r\n", $params['headers']),
            implode('', $params['mta_params'])
        );
    }
}
