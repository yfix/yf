<?php

# Internal symlink
load('manage_users','framework','admin_modules/');
$_GET['object'] = 'manage_users';
class yf_members extends yf_manage_users { }
