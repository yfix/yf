<?php

class yf_manage_shop_send_sms
{
    public function send_sms()
    {
        if (main()->is_post()) {
            $phone = $_POST['phone'];
            $text = mb_substr($_POST['text'], 0, 512);

            $turbosms = mysql_connect(SMS_HOST, SMS_USER, SMS_PASS);
            mysql_select_db(SMS_NAME, $turbosms);
            mysql_query('SET NAMES UTF8', $turbosms);

            if (strlen($phone) == 10) {
                $phone = '+38' . $phone;
            }
            if (strlen($phone) == 9) {
                $phone = '+380' . $phone;
            }

            $sql_sms_user = 'INSERT INTO ' . SMS_USER . " ( `sign`, `number`, `message` ) VALUES ( '" . SMS_SIGN . "', '" . $phone . "', '" . $text . "') ";
            mysql_query($sql_sms_user, $turbosms);

            mysql_close($turbosms);

            return 'SMS was sent';
        }

        if ( ! empty($_GET['phone'])) {
            $replace['phone'] = $_GET['phone'];
        }
        return form2($replace)
            ->text('phone', ['required' => 1])
            ->textarea('text', ['required' => 1])
            ->save(['value' => 'Send sms']);
    }
}
