<?php

class yf_shop__short_search_form
{
    public function _short_search_form()
    {
        $replace = [
            'search_string' => '',
            'form_action' => process_url('./?object=shop&action=search&id=fast'),
        ];
        return tpl()->parse('shop/short_search_form', $replace);
    }
}
