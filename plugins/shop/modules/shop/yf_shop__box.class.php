<?php

class yf_shop__box
{
    public function _box($name = '', $selected = '')
    {
        if (empty($name) || empty(module('shop')->_boxes[$name])) {
            return false;
        }
        return eval('return common()->' . module('shop')->_boxes[$name] . ';');
    }
}
