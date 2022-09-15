<?php

load('mail_driver', '', 'classes/mail/');
class yf_mail_driver_ses extends yf_mail_driver
{
    /** @var string AWS SES region. */
    public $region = 'eu-west-1';
    /** @var string AWS SES key. */
    public $key;
    /** @var string AWS SES secret. */
    public $secret;

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
     * https://eu-west-1.console.aws.amazon.com/ses/home?region=eu-west-1#verified-senders-domain.
     * @param mixed $error_message
     */
    public function send(array $params = [], &$error_message = '')
    {
        require_php_lib('aws_sdk');

        $error_message = null;
        try {
            $ses = Aws\Ses\SesClient::factory([
                'credentials' => new Aws\Credentials\Credentials($this->key, $this->secret),
                'version' => 'latest',
                'region' => $this->region,
            ]);
            $request = [];
            //			$request['Source'] = urlencode($params['name_from']).'<'.$params['email_from'].'>';
            $request['Source'] = $params['email_from'];
            $request['Destination']['ToAddresses'] = [urlencode($params['name_to']) . ' <' . $params['email_to'] . '>'];
            //			$request['Destination']['ToAddresses'] = [$params['email_to']];
            $request['Message']['Subject']['Data'] = $params['subject'];
            $request['Message']['Body']['Text']['Data'] = $params['text'];
            $request['Message']['Body']['Html']['Data'] = $params['html'];
            // TODO			'attachment'

            $result = $ses->sendEmail($request);
            $msg_id = $result->get('MessageId');
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                d(_prepare_html($e->getMessage()));
            }
            $error_message = 'SES error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
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
