<?php
$GLOBALS['no_graphics'] = true;

include ("./index.php");

$text = $GLOBALS['main']->_execute("graphics","_show_menu","name=admin_left_menu;force_stpl_name=left_frame_menu");

echo $GLOBALS['common']->show_empty_page($text);
?>