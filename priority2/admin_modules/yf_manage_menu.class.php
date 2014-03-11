<?php

# Internal symlink
load('menus_editor','framework','admin_modules/');
$_GET['object'] = 'menus_editor';
class yf_manage_menu extends yf_menus_editor { }
