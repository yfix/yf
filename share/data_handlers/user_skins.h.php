<?php

$Q = db()->query("SELECT `id`,`name` FROM `".db("skins")."` WHERE `for_user`='1' AND `active`='1'");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A["name"];