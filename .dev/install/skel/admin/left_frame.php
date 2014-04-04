<?php
$GLOBALS['no_graphics'] = true;
include ('./index.php');
$text = _class('graphics')->_show_menu(array('name'=>'admin_left_menu','force_stpl_name'=>'left_frame_menu'));
echo common()->show_empty_page($text);
