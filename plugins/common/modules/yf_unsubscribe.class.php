<?php

class yf_unsubscribe
{
    /**
     * @param mixed $method
     */
    public function _module_action_handler($method)
    {
        if ( ! $method || substr($method, 0, 1) === '_' || ! method_exists($this, $method)) {
            $method = 'show';
            // For links like this: /news/uvazhaemie-polzovateli-i-posetiteli
            if (($_GET['action'] ?? null) && ! ($_GET['id'] ?? null)) {
                $_GET['id'] = ($_GET['action'] ?? null);
                $_GET['action'] = $method;
            }
        }
        return $this->$method();
    }


    public function show()
    {
        common()->message_success(t('You just have been unsubscribed'));
        return js_redirect('./');
    }
}
