<?php

$Q = db()->query("SELECT * FROM ".db("user_menu")." WHERE `order` > 0 ORDER BY `group` ASC, `order` ASC");
while ($menu_info = db()->fetch_assoc($Q)) $data[$menu_info["group"]][$menu_info["id"]] = $menu_info;