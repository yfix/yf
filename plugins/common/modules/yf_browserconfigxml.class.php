<?php

class yf_browserconfigxml
{
    public function show()
    {
        header('Content-Type: text/xml', $replace = true);
        echo '<?xml version="1.0" encoding="utf-8"?><browserconfig><msapplication></msapplication></browserconfig>';
        exit;
    }
}
