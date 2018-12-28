<?php

class yf_logout
{
    public function show()
    {
        $_GET['task'] = 'logout';
        main()->init_auth();
    }
}
