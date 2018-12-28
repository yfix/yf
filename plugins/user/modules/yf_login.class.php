<?php

class yf_login
{
    public function show()
    {
        $_GET['task'] = 'login';
        main()->init_auth();
    }
}
