<?php

$Q = db()->query("SELECT `id`,`name` FROM `".db("user_groups")."` WHERE `active`='1'");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A["name"];