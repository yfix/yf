<?php

/**
 * Bootstrap acceptance testing methods.
 */
class yf_test_html5fw
{
    public function text()
    {
        return form()->text('name');
    }
    public function textarea()
    {
        return form()->textarea('name');
    }
    public function container()
    {
        return form()->container('name');
    }
    public function hidden()
    {
        return form()->hidden('name');
    }
}
