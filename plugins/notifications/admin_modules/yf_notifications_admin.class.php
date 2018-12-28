<?php

class yf_notifications_admin
{
    // hack - we need admin data for this
    public function check()
    {
        return _class('notifications', 'modules/')->check();
    }

    public function read()
    {
        return _class('notifications', 'modules/')->read();
    }
}
