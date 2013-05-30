<?php

$Q = db()->query("SELECT `user_id` FROM `".db("auto_connected")."` WHERE `active`='1'");
while ($A = db()->fetch_assoc($Q)) $data[$A["user_id"]] = $A["user_id"];